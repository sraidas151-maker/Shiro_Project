<?php
/**
 * DEBUG VERSION - Shows exact database error
 */

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

session_start();

// Database configuration - TRY MULTIPLE OPTIONS
$db_configs = [
    // Option 1: Standard localhost
    [
        'host' => 'localhost',
        'dbname' => 'shiro_db',
        'username' => 'root',
        'password' => ''
    ],
    // Option 2: 127.0.0.1 (TCP instead of socket)
    [
        'host' => '127.0.0.1',
        'dbname' => 'shiro_db',
        'username' => 'root',
        'password' => ''
    ],
    // Option 3: localhost with port
    [
        'host' => 'localhost:3306',
        'dbname' => 'shiro_db',
        'username' => 'root',
        'password' => ''
    ],
    // Option 4: If you have a password set
    [
        'host' => 'localhost',
        'dbname' => 'shiro_db',
        'username' => 'root',
        'password' => 'root'
    ]
];

$pdo = null;
$connection_error = '';

// Try each configuration
foreach ($db_configs as $index => $config) {
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        error_log("Database connected successfully with config #" . ($index + 1));
        break; // Success! Exit loop
    } catch (PDOException $e) {
        $connection_error .= "Config #" . ($index + 1) . " ({$config['host']}): " . $e->getMessage() . "\n";
        error_log("DB Connection failed config #" . ($index + 1) . ": " . $e->getMessage());
        continue; // Try next config
    }
}

// If all configs failed
if ($pdo === null) {
    error_log("All database connection attempts failed:\n" . $connection_error);
    
    // For AJAX requests, return error
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
         (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false))) {
        
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Database connection failed. Check that MySQL is running and credentials are correct.',
            'debug' => $connection_error
        ]);
        exit();
    }
}

// Handle AJAX registration request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo !== null) {
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? strtolower($_SERVER['CONTENT_TYPE']) : '';
    $is_json = strpos($contentType, 'application/json') !== false;
    
    if ($is_ajax || $is_json) {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        $json = file_get_contents('php://input');
        
        if (empty($json)) {
            echo json_encode(['success' => false, 'message' => 'No data received']);
            exit();
        }
        
        $data = json_decode($json, true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
            exit();
        }
        
        // Extract data
        $firstName = isset($data['firstName']) ? trim($data['firstName']) : '';
        $lastName = isset($data['lastName']) ? trim($data['lastName']) : '';
        $username = isset($data['username']) ? trim($data['username']) : '';
        $email = isset($data['email']) ? trim($data['email']) : '';
        $password = isset($data['password']) ? $data['password'] : '';
        $birthdate = isset($data['birthdate']) ? $data['birthdate'] : '';
        $terms = isset($data['terms']) ? $data['terms'] : false;
        
        // Convert terms to boolean
        $termsBool = false;
        if (is_bool($terms)) {
            $termsBool = $terms;
        } elseif (is_string($terms)) {
            $termsBool = in_array(strtolower($terms), ['true', '1', 'on', 'yes']);
        } elseif (is_numeric($terms)) {
            $termsBool = (int)$terms === 1;
        }
        
        // Validation
        $validationErrors = [];
        
        if (empty($firstName)) $validationErrors[] = 'First name is required';
        if (empty($lastName)) $validationErrors[] = 'Last name is required';
        if (empty($username)) $validationErrors[] = 'Username is required';
        if (strlen($username) < 3) $validationErrors[] = 'Username must be at least 3 characters';
        if (empty($email)) $validationErrors[] = 'Email is required';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $validationErrors[] = 'Invalid email format';
        if (empty($password)) $validationErrors[] = 'Password is required';
        if (strlen($password) < 8) $validationErrors[] = 'Password must be at least 8 characters';
        if (empty($birthdate)) $validationErrors[] = 'Date of birth is required';
        if (!$termsBool) $validationErrors[] = 'You must agree to the terms and conditions';

        if (!empty($validationErrors)) {
            echo json_encode(['success' => false, 'message' => implode(', ', $validationErrors)]);
            exit();
        }
        
        // Check if username exists
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Username already taken']);
                exit();
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit();
        }
        
        // Check if email exists
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email already registered']);
                exit();
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit();
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $fullName = $firstName . ' ' . $lastName;
        $activityTableName = 'user_activity_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $username) . '_' . time();
        
        // Insert user
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, password, first_name, last_name, full_name, birthdate, activity_table_name, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $executed = $stmt->execute([
                $username,
                $email,
                $passwordHash,
                $password,
                $firstName,
                $lastName,
                $fullName,
                $birthdate,
                $activityTableName
            ]);
            
            if ($executed) {
                $user_id = $pdo->lastInsertId();
                
                // Create activity table
                try {
                    $createTableSQL = "CREATE TABLE IF NOT EXISTS `{$activityTableName}` (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        activity_type ENUM('post', 'like', 'comment', 'share', 'save', 'follow', 'unfollow', 'login', 'logout', 'profile_update', 'password_change') NOT NULL,
                        reference_id INT,
                        reference_type VARCHAR(50),
                        details TEXT,
                        ip_address VARCHAR(45),
                        user_agent TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_activity_type (activity_type),
                        INDEX idx_created_at (created_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    
                    $pdo->exec($createTableSQL);
                    
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                    
                    $logStmt = $pdo->prepare("INSERT INTO `{$activityTableName}` (activity_type, details, ip_address, user_agent) VALUES (?, ?, ?, ?)");
                    $logStmt->execute(['login', 'User registered successfully', $ip, $userAgent]);
                    
                } catch (PDOException $e) {
                    error_log("Failed to create activity table: " . $e->getMessage());
                }
                
                echo json_encode(['success' => true, 'message' => 'Registration successful']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create account']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit();
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Shiro</title>
    <link rel="stylesheet" href="css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .debug-info {
            background: #fee2e2;
            border: 2px solid #ef4444;
            color: #991b1b;
            padding: 20px;
            margin: 20px;
            border-radius: 8px;
            font-family: monospace;
            white-space: pre-wrap;
            display: <?php echo ($pdo === null) ? 'block' : 'none'; ?>;
        }
        .success-info {
            background: #d1fae5;
            border: 2px solid #10b981;
            color: #065f46;
            padding: 20px;
            margin: 20px;
            border-radius: 8px;
            display: <?php echo ($pdo !== null) ? 'block' : 'none'; ?>;
        }
    </style>
</head>
<body>
    <?php if ($pdo === null): ?>
    <div class="debug-info">
        <h2>⚠️ DATABASE CONNECTION FAILED</h2>
        <p><strong>Error Details:</strong></p>
        <?php echo nl2br(htmlspecialchars($connection_error)); ?>
        
        <h3>🔧 Common Solutions:</h3>
        <ol>
            <li><strong>Check if MySQL is running:</strong>
                <ul>
                    <li>XAMPP: Open XAMPP Control Panel → Start MySQL</li>
                    <li>WAMP: Click WAMP icon → Start All Services</li>
                    <li>Windows Services: services.msc → MySQL → Start</li>
                </ul>
            </li>
            <li><strong>Check database exists:</strong>
                <ul>
                    <li>Open phpMyAdmin: http://localhost/phpmyadmin</li>
                    <li>Create database: <code>CREATE DATABASE shiro_db;</code></li>
                </ul>
            </li>
            <li><strong>Check credentials in register.php:</strong>
                <ul>
                    <li>Current: host=localhost, user=root, password=(empty)</li>
                    <li>If you set a password, update the $db_configs array</li>
                </ul>
            </li>
            <li><strong>Try 127.0.0.1 instead of localhost</strong></li>
            <li><strong>Check MySQL port (default is 3306)</strong></li>
        </ol>
        
        <p><strong>Check php_errors.log for more details</strong></p>
    </div>
    <div class="auth-container">
        <div class="auth-left">
            <div class="auth-brand">
                <div class="logo-icon">
                    <div class="logo-circle"></div>
                    <div class="logo-dot"></div>
                </div>
                <span class="logo-text">shiro</span>
            </div>
            <h1>Join Shiro Today</h1>
            <p>Create your account and start sharing your world with friends and family.</p>
            <div class="auth-features">
                <div class="feature"><i class="fas fa-camera"></i><span>Share photos & videos</span></div>
                <div class="feature"><i class="fas fa-comments"></i><span>Connect with friends</span></div>
                <div class="feature"><i class="fas fa-globe"></i><span>Explore the world</span></div>
            </div>
        </div>
        
        <div class="auth-right">
            <div class="auth-box">
                <h2>Create Account</h2>
                <p class="auth-subtitle">Fill in your details to get started</p>
                
                <form id="registerForm" method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">First Name</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user"></i>
                                <input type="text" id="firstName" name="firstName" placeholder="Shiva" required>
                            </div>
                            <span class="error-message" id="firstNameError"></span>
                        </div>
                        <div class="form-group">
                            <label for="lastName">Last Name</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user"></i>
                                <input type="text" id="lastName" name="lastName" placeholder="Raidas" required>
                            </div>
                            <span class="error-message" id="lastNameError"></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <i class="fas fa-at"></i>
                            <input type="text" id="username" name="username" placeholder="srshiva" required>
                        </div>
                        <span class="error-message" id="usernameError"></span>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="shiva@example.com" required>
                        </div>
                        <span class="error-message" id="emailError"></span>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Create a strong password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                            <span class="strength-text" id="strengthText">Password strength</span>
                        </div>
                        <span class="error-message" id="passwordError"></span>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <span class="error-message" id="confirmPasswordError"></span>
                    </div>
                    <div class="form-group">
                        <label for="birthdate">Date of Birth</label>
                        <div class="input-wrapper">
                            <i class="fas fa-calendar"></i>
                            <input type="date" id="birthdate" name="birthdate" required>
                        </div>
                        <span class="error-message" id="birthdateError"></span>
                    </div>
                    <div class="form-group terms">
                        <label class="checkbox-label" id="termsLabel">
                            <input type="checkbox" id="terms" name="terms" required>
                            <span class="checkmark"></span>
                            <span class="terms-text">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span>
                        </label>
                        <span class="error-message" id="termsError" style="color: #ef4444; margin-top: 0.5rem; display: block;"></span>
                    </div>
                    <button type="submit" class="btn-auth" id="registerBtn" <?php echo ($pdo === null) ? 'disabled' : ''; ?>>
                        <span>Create Account</span><i class="fas fa-arrow-right"></i>
                    </button>
                </form>
                
                <div class="auth-divider"><span>or sign up with</span></div>
                <div class="social-login">
                    <button class="btn-social google" onclick="socialLogin('google')"><i class="fab fa-google"></i></button>
                    <button class="btn-social facebook" onclick="socialLogin('facebook')"><i class="fab fa-facebook-f"></i></button>
                    <button class="btn-social twitter" onclick="socialLogin('twitter')"><i class="fab fa-twitter"></i></button>
                    <button class="btn-social apple" onclick="socialLogin('apple')"><i class="fab fa-apple"></i></button>
                </div>
                <p class="auth-switch">Already have an account? <a href="login.php">Sign in</a></p>
            </div>
        </div>
    </div>
    <div id="toast" class="toast"></div>
    
    <script>
    const DEBUG = true;
    
    function debugLog(...args) {
        if (DEBUG) console.log('[AUTH]', ...args);
    }

    document.addEventListener('DOMContentLoaded', function() {
        debugLog('System initialized');
        initRegisterForm();
    });

    function initRegisterForm() {
        const form = document.getElementById('registerForm');
        if (!form) return;
        if (form.getAttribute('data-js-initialized')) return;
        form.setAttribute('data-js-initialized', 'true');
        
        bindValidations();
        form.addEventListener('submit', handleRegisterSubmit);
    }

    function bindValidations() {
        const inputs = {
            firstName: document.getElementById('firstName'),
            lastName: document.getElementById('lastName'),
            username: document.getElementById('username'),
            email: document.getElementById('email'),
            password: document.getElementById('password'),
            confirmPassword: document.getElementById('confirmPassword'),
            birthdate: document.getElementById('birthdate'),
            terms: document.getElementById('terms')
        };

        if (inputs.firstName) {
            inputs.firstName.addEventListener('blur', () => validateName(inputs.firstName, 'First name'));
            inputs.firstName.addEventListener('input', () => clearError(inputs.firstName));
        }

        if (inputs.lastName) {
            inputs.lastName.addEventListener('blur', () => validateName(inputs.lastName, 'Last name'));
            inputs.lastName.addEventListener('input', () => clearError(inputs.lastName));
        }

        if (inputs.username) {
            inputs.username.addEventListener('blur', () => validateUsername(inputs.username));
            inputs.username.addEventListener('input', () => clearError(inputs.username));
        }

        if (inputs.email) {
            inputs.email.addEventListener('blur', () => validateEmail(inputs.email));
            inputs.email.addEventListener('input', () => clearError(inputs.email));
        }

        if (inputs.password) {
            inputs.password.addEventListener('input', () => {
                clearError(inputs.password);
                checkPasswordStrength(inputs.password.value);
            });
            inputs.password.addEventListener('blur', () => validatePassword(inputs.password, true));
        }

        if (inputs.confirmPassword) {
            inputs.confirmPassword.addEventListener('blur', () => validateConfirmPassword(inputs.confirmPassword, inputs.password));
            inputs.confirmPassword.addEventListener('input', () => clearError(inputs.confirmPassword));
        }

        if (inputs.birthdate) {
            inputs.birthdate.addEventListener('blur', () => validateBirthdate(inputs.birthdate));
            inputs.birthdate.addEventListener('input', () => clearError(inputs.birthdate));
        }

        if (inputs.terms) {
            inputs.terms.addEventListener('change', () => validateTerms(inputs.terms));
        }
    }

    async function handleRegisterSubmit(e) {
        e.preventDefault();
        debugLog('Form submission started');
        
        const submitBtn = document.getElementById('registerBtn');
        const inputs = {
            firstName: document.getElementById('firstName'),
            lastName: document.getElementById('lastName'),
            username: document.getElementById('username'),
            email: document.getElementById('email'),
            password: document.getElementById('password'),
            confirmPassword: document.getElementById('confirmPassword'),
            birthdate: document.getElementById('birthdate'),
            terms: document.getElementById('terms')
        };
        
        let isValid = true;

        if (!validateName(inputs.firstName, 'First name')) isValid = false;
        if (!validateName(inputs.lastName, 'Last name')) isValid = false;
        if (!validateUsername(inputs.username)) isValid = false;
        if (!validateEmail(inputs.email)) isValid = false;
        if (!validatePassword(inputs.password, true)) isValid = false;
        if (!validateConfirmPassword(inputs.confirmPassword, inputs.password)) isValid = false;
        if (!validateBirthdate(inputs.birthdate)) isValid = false;
        if (!validateTerms(inputs.terms)) isValid = false;

        if (!isValid) {
            showToast('Please fix the errors above', 'error');
            return;
        }

        const formData = {
            firstName: inputs.firstName.value.trim(),
            lastName: inputs.lastName.value.trim(),
            username: inputs.username.value.trim(),
            email: inputs.email.value.trim(),
            password: inputs.password.value,
            birthdate: inputs.birthdate.value,
            terms: inputs.terms.checked
        };

        debugLog('Form data:', formData);
        setLoading(submitBtn, true);

        try {
            const currentUrl = window.location.href;
            const currentPath = window.location.pathname;
            const currentOrigin = window.location.origin;
            
            let registerUrl;
            if (currentPath.includes('/')) {
                const lastSlashIndex = currentPath.lastIndexOf('/');
                const dir = currentPath.substring(0, lastSlashIndex + 1);
                registerUrl = currentOrigin + dir + 'register.php';
            } else {
                registerUrl = currentOrigin + '/register.php';
            }
            
            debugLog('Fetching from:', registerUrl);

            const response = await fetch(registerUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(formData)
            });

            debugLog('Response status:', response.status);

            if (!response.ok) {
                throw new Error('HTTP error! Status: ' + response.status);
            }

            const responseText = await response.text();
            debugLog('Raw response:', responseText.substring(0, 200));

            if (responseText.trim().startsWith('<')) {
                throw new Error('Server returned HTML instead of JSON');
            }

            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                throw new Error('Invalid JSON response: ' + responseText.substring(0, 100));
            }

            debugLog('Parsed result:', result);

            if (result.success) {
                showToast('Account created successfully! Redirecting...', 'success');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                setLoading(submitBtn, false);
                showToast(result.message || 'Registration failed', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            setLoading(submitBtn, false);
            showToast(error.message || 'Network error', 'error');
        }
    }

    function validateName(input, fieldName) {
        const value = input.value.trim();
        const errorElement = document.getElementById(input.id + 'Error');
        
        if (!value) {
            showError(input, errorElement, fieldName + ' is required');
            return false;
        }
        
        if (value.length < 2) {
            showError(input, errorElement, fieldName + ' must be at least 2 characters');
            return false;
        }
        
        if (!/^[a-zA-Z\s]+$/.test(value)) {
            showError(input, errorElement, 'Only letters and spaces allowed');
            return false;
        }
        
        showSuccess(input, errorElement);
        return true;
    }

    function validateUsername(input) {
        const value = input.value.trim();
        const errorElement = document.getElementById('usernameError');
        
        if (!value) {
            showError(input, errorElement, 'Username is required');
            return false;
        }
        
        if (value.length < 3) {
            showError(input, errorElement, 'Username must be at least 3 characters');
            return false;
        }
        
        if (!/^[a-zA-Z0-9_]+$/.test(value)) {
            showError(input, errorElement, 'Only letters, numbers, and underscores allowed');
            return false;
        }
        
        showSuccess(input, errorElement);
        return true;
    }

    function validateEmail(input) {
        const value = input.value.trim();
        const errorElement = document.getElementById('emailError');
        
        if (!value) {
            showError(input, errorElement, 'Email is required');
            return false;
        }
        
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            showError(input, errorElement, 'Please enter a valid email address');
            return false;
        }
        
        showSuccess(input, errorElement);
        return true;
    }

    function validatePassword(input, isRegister = false) {
        const value = input.value;
        const errorElement = document.getElementById('passwordError');
        
        if (!value) {
            showError(input, errorElement, 'Password is required');
            return false;
        }
        
        if (isRegister) {
            if (value.length < 8) {
                showError(input, errorElement, 'Password must be at least 8 characters');
                return false;
            }
            
            if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(value)) {
                showError(input, errorElement, 'Password needs uppercase, lowercase, and number');
                return false;
            }
        }
        
        showSuccess(input, errorElement);
        return true;
    }

    function validateConfirmPassword(input, passwordInput) {
        const value = input.value;
        const errorElement = document.getElementById('confirmPasswordError');
        
        if (!value) {
            showError(input, errorElement, 'Please confirm your password');
            return false;
        }
        
        if (value !== passwordInput.value) {
            showError(input, errorElement, 'Passwords do not match');
            return false;
        }
        
        showSuccess(input, errorElement);
        return true;
    }

    function validateBirthdate(input) {
        const value = input.value;
        const errorElement = document.getElementById('birthdateError');
        
        if (!value) {
            showError(input, errorElement, 'Date of birth is required');
            return false;
        }
        
        const birthDate = new Date(value);
        const today = new Date();
        const age = today.getFullYear() - birthDate.getFullYear();
        
        if (age < 13) {
            showError(input, errorElement, 'You must be at least 13 years old');
            return false;
        }
        
        showSuccess(input, errorElement);
        return true;
    }

    function validateTerms(input) {
        const errorElement = document.getElementById('termsError');
        
        if (!input.checked) {
            if (errorElement) {
                errorElement.textContent = 'You must agree to the terms and conditions';
            }
            return false;
        }
        
        if (errorElement) {
            errorElement.textContent = '';
        }
        return true;
    }

    function checkPasswordStrength(password) {
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        
        if (!strengthFill || !strengthText) return;
        
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;
        
        strengthFill.className = 'strength-fill';
        
        if (password.length === 0) {
            strengthFill.style.width = '0';
            strengthText.textContent = 'Password strength';
            strengthText.style.color = '';
        } else if (strength <= 2) {
            strengthFill.classList.add('weak');
            strengthText.textContent = 'Weak password';
            strengthText.style.color = '#ef4444';
        } else if (strength <= 3) {
            strengthFill.classList.add('medium');
            strengthText.textContent = 'Medium strength';
            strengthText.style.color = '#f59e0b';
        } else {
            strengthFill.classList.add('strong');
            strengthText.textContent = 'Strong password';
            strengthText.style.color = '#22c55e';
        }
    }

    function showError(input, errorElement, message) {
        if (input) {
            input.classList.add('error');
            input.classList.remove('success');
        }
        if (errorElement) errorElement.textContent = message;
    }

    function showSuccess(input, errorElement) {
        if (input) {
            input.classList.remove('error');
            input.classList.add('success');
        }
        if (errorElement) errorElement.textContent = '';
    }

    function clearError(input) {
        if (input) input.classList.remove('error');
        const errorElement = document.getElementById(input.id + 'Error');
        if (errorElement) errorElement.textContent = '';
    }

    function setLoading(button, isLoading) {
        if (!button) return;
        
        if (isLoading) {
            button.disabled = true;
            button.classList.add('loading');
            button.innerHTML = '<span>Please wait...</span>';
        } else {
            button.disabled = false;
            button.classList.remove('loading');
            button.innerHTML = '<span>Create Account</span><i class="fas fa-arrow-right"></i>';
        }
    }

    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;
        
        const button = input.nextElementSibling;
        if (!button) return;
        
        const icon = button.querySelector('i');
        if (!icon) return;
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        if (!toast) {
            console.log('Toast:', message);
            return;
        }
        
        toast.className = 'toast ' + type;
        
        let icon = 'check-circle';
        if (type === 'error') icon = 'exclamation-circle';
        if (type === 'warning') icon = 'exclamation-triangle';
        
        toast.innerHTML = '<i class="fas fa-' + icon + '"></i> ' + message;
        
        setTimeout(function() {
            toast.classList.add('show');
        }, 10);
        
        setTimeout(function() {
            toast.classList.remove('show');
        }, 3000);
    }

    function socialLogin(provider) {
        showToast('Connecting to ' + provider + '...', 'success');
        
        setTimeout(function() {
            localStorage.setItem('isLoggedIn', 'true');
            localStorage.setItem('currentUser', provider + '_user');
            showToast('Connected! Redirecting...', 'success');
            
            setTimeout(function() {
                window.location.href = 'index.html';
            }, 1500);
        }, 1500);
    }
    </script>
</body>
</html>