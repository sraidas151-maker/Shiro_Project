<?php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'shiro_db';
$db_username = 'root';
$db_password = '';

// Error message variable
$error = '';

try {
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle login form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']) ? true : false;

        // Validate empty fields
        if (empty($username) || empty($password)) {
            $error = "Please enter both username and password.";
        } 
        // Validate remember checkbox
        else if (!$remember) {
            $error = "Please check the 'Remember me' checkbox.";
        }
        else {
            // Fetch user from database - check both username and email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify user exists
            if ($user) {
                // Check password (hashed or plain)
                $passwordValid = false;
                
                // Try hashed password first
                if (password_verify($password, $user['password'])) {
                    $passwordValid = true;
                }
                // Fallback to plain text (for old data)
                else if ($password === $user['password']) {
                    $passwordValid = true;
                }

                if ($passwordValid) {
                    // Store all user data in session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];
                    $_SESSION['user_avatar'] = 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&crop=face';
                    
                    // Redirect to home page
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Invalid password. Please try again.";
                }
            } else {
                $error = "User not found. Please check your username.";
            }
        }
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "Database connection failed. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Shiro</title>
    <link rel="stylesheet" href="css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    
    <!-- Extra CSS for error display -->
    <style>
        .error-alert {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            padding: 0.875rem 1rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1rem;
            font-size: 0.9375rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        .error-alert i {
            font-size: 1rem;
        }
        .input-wrapper input.error {
            border-color: #ef4444;
            background: rgba(239, 68, 68, 0.05);
        }
        
        /* Checkbox error state */
        .checkbox-label.error .checkmark {
            border-color: #ef4444;
            background-color: rgba(239, 68, 68, 0.05);
        }

        .checkbox-label.error .terms-text {
            color: #ef4444;
        }

        /* Shake animation */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .shake {
            animation: shake 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-left">
            <div class="auth-brand">
                <div class="logo-icon">
                    <div class="logo-circle"></div>
                    <div class="logo-dot"></div>
                </div>
                <span class="logo-text">shiro</span>
            </div>
            <h1>Welcome back to Shiro</h1>
            <p>Connect with friends, share your moments, and discover new experiences.</p>
            <div class="auth-features">
                <div class="feature">
                    <i class="fas fa-users"></i>
                    <span>Join 10M+ users worldwide</span>
                </div>
                <div class="feature">
                    <i class="fas fa-shield-alt"></i>
                    <span>Secure & private</span>
                </div>
                <div class="feature">
                    <i class="fas fa-bolt"></i>
                    <span>Real-time connections</span>
                </div>
            </div>
        </div>
        
        <div class="auth-right">
            <div class="auth-box">
                <h2>Sign In</h2>
                <p class="auth-subtitle">Enter your credentials to access your account</p>
                
                <!-- Error Message Display -->
                <?php if (!empty($error)): ?>
                    <div class="error-alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="loginForm" novalidate>
                    <div class="form-group">
                        <label for="username">Username or Email</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="username" name="username" placeholder="Enter username or email" required 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                   class="<?php echo !empty($error) ? 'error' : ''; ?>">
                        </div>
                        <span class="error-message" id="usernameError"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required
                                   class="<?php echo !empty($error) ? 'error' : ''; ?>">
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <span class="error-message" id="passwordError"></span>
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-label" id="rememberLabel">
                            <input type="checkbox" id="remember" name="remember">
                            <span class="checkmark"></span>
                            <span class="terms-text">Remember me</span><br>
                        </label>
                        <br><span class="error-message" id="rememberError" style="color: #ef4444; margin-left: 0.5rem; font-size: 0.875rem;"></span>
                        <a href="#" class="forgot-password">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="btn-auth" id="loginBtn">
                        <span>Sign In</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
                
                <div class="auth-divider">
                    <span>or continue with</span>
                </div>
                
                <div class="social-login">
                    <button class="btn-social google" type="button">
                        <i class="fab fa-google"></i>
                    </button>
                    <button class="btn-social facebook" type="button">
                        <i class="fab fa-facebook-f"></i>
                    </button>
                    <button class="btn-social twitter" type="button">
                        <i class="fab fa-twitter"></i>
                    </button>
                    <button class="btn-social apple" type="button">
                        <i class="fab fa-apple"></i>
                    </button>
                </div>
                
                <p class="auth-switch">
                    Don't have an account? <a href="register.php">Create account</a>
                </p>
                
                
            </div>
        </div>
    </div>
    
    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>
    
    <script>
        // Simple password toggle
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = event.currentTarget.querySelector('i');
            
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

        // Validate remember checkbox
        function validateRemember(input) {
            const errorElement = document.getElementById('rememberError');
            const label = document.getElementById('rememberLabel');
            
            if (!input.checked) {
                // Show error message
                if (errorElement) {
                    errorElement.textContent = 'Please check out this field';
                }
                // Add error styling
                if (label) {
                    label.classList.add('error');
                    label.classList.add('shake');
                    setTimeout(() => label.classList.remove('shake'), 300);
                }
                return false;
            }
            
            // Clear error
            clearRememberError();
            return true;
        }

        // Clear remember error
        function clearRememberError() {
            const errorElement = document.getElementById('rememberError');
            const label = document.getElementById('rememberLabel');
            
            if (errorElement) {
                errorElement.textContent = '';
            }
            if (label) {
                label.classList.remove('error');
            }
        }

        // Event listener for checkbox
        document.getElementById('remember').addEventListener('change', function() {
            if (this.checked) {
                clearRememberError();
            }
        });

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const remember = document.getElementById('remember');
            
            let isValid = true;
            
            if (!username || !password) {
                e.preventDefault();
                showToast('Please fill in all fields', 'error');
                isValid = false;
            }
            
            // Checkbox validation
            if (!validateRemember(remember)) {
                e.preventDefault();
                isValid = false;
                remember.focus();
                remember.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            
            return isValid;
        });

        // Toast function
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type;
            setTimeout(() => toast.classList.add('show'), 10);
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
    </script>
</body>
</html>