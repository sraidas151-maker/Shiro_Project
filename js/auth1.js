// Auth JavaScript - Form Validation and Interactions

document.addEventListener('DOMContentLoaded', function() {
    // Initialize forms
    initLoginForm();
    initRegisterForm();
    initLogoutButton();
});

// ==================== LOGIN FORM ====================

function initLoginForm() {
    const form = document.getElementById('loginForm');
    if (!form) return;

    form.addEventListener('submit', handleLoginSubmit);

    // Real-time validation
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');

    usernameInput.addEventListener('blur', () => validateUsername(usernameInput));
    usernameInput.addEventListener('input', () => clearError(usernameInput));

    passwordInput.addEventListener('blur', () => validatePassword(passwordInput));
    passwordInput.addEventListener('input', () => clearError(passwordInput));
}

function handleLoginSubmit(e) {
    e.preventDefault();
    
    const username = document.getElementById('username');
    const password = document.getElementById('password');
    const submitBtn = document.getElementById('loginBtn');
    
    let isValid = true;

    // Validate username
    if (!validateUsername(username)) isValid = false;
    
    // Validate password
    if (!validatePassword(password)) isValid = false;

    if (isValid) {
        // Simulate login
        setLoading(submitBtn, true);
        
        // Form submission logic
        fetch('login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `username=${encodeURIComponent(username.value)}&password=${encodeURIComponent(password.value)}`,
        })
        .then(response => response.text())
        .then(data => {
            if (data.includes('login.php')) {
                // Successful login
                setLoading(submitBtn, false);
                showToast('Login successful! Redirecting...', 'success');
                
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 1500);
            } else {
                // Failed login
                setLoading(submitBtn, false);
                const errorElement = document.getElementById('passwordError');
                showError(password, errorElement, 'Invalid username or password');
                showToast('Login failed', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            setLoading(submitBtn, false);
            showToast('An error occurred', 'error');
        });
    }
}

// Registration Form Submission
function handleRegisterSubmit(e) {
    e.preventDefault();
    
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
    
    const submitBtn = document.getElementById('registerBtn');
    let isValid = true;

    // Validate all fields
    if (!validateName(inputs.firstName, 'First name')) isValid = false;
    if (!validateName(inputs.lastName, 'Last name')) isValid = false;
    if (!validateUsername(inputs.username)) isValid = false;
    if (!validateEmail(inputs.email)) isValid = false;
    if (!validatePassword(inputs.password, true)) isValid = false;
    if (!validateConfirmPassword(inputs.confirmPassword, inputs.password)) isValid = false;
    if (!validateBirthdate(inputs.birthdate)) isValid = false;
    if (!validateTerms(inputs.terms)) isValid = false;

    if (isValid) {
        // Simulate registration
        setLoading(submitBtn, true);
        
        // Form submission logic
        fetch('register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `firstName=${encodeURIComponent(inputs.firstName.value)}&lastName=${encodeURIComponent(inputs.lastName.value)}&username=${encodeURIComponent(inputs.username.value)}&email=${encodeURIComponent(inputs.email.value)}&password=${encodeURIComponent(inputs.password.value)}&confirmPassword=${encodeURIComponent(inputs.confirmPassword.value)}&birthdate=${encodeURIComponent(inputs.birthdate.value)}&terms=${inputs.terms.checked ? 'on' : ''}`,
        })
        .then(response => response.text())
        .then(data => {
            if (data.includes('login.php')) {
                // Successful registration
                setLoading(submitBtn, false);
                showToast('Account created successfully! Redirecting...', 'success');
                
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 1500);
            } else {
                // Failed registration
                setLoading(submitBtn, false);
                showError(inputs.password, document.getElementById('passwordError'), data);
                showToast('Registration failed', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            setLoading(submitBtn, false);
            showToast('An error occurred', 'error');
        });
    }
}

// ==================== VALIDATION FUNCTIONS ====================

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
        showError(input, errorElement, 'Username can only contain letters, numbers, and underscores');
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
            showError(input, errorElement, 'Password must contain uppercase, lowercase, and number');
            return false;
        }
    } else {
        if (value.length < 6) {
            showError(input, errorElement, 'Password must be at least 6 characters');
            return false;
        }
    }
    
    showSuccess(input, errorElement);
    return true;
}

function validateName(input, fieldName) {
    const value = input.value.trim();
    const errorElement = document.getElementById(input.id + 'Error');
    
    if (!value) {
        showError(input, errorElement, `${fieldName} is required`);
        return false;
    }
    
    if (value.length < 2) {
        showError(input, errorElement, `${fieldName} must be at least 2 characters`);
        return false;
    }
    
    if (!/^[a-zA-Z\s]+$/.test(value)) {
        showError(input, errorElement, `${fieldName} can only contain letters`);
        return false;
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
    
    if (age > 120) {
        showError(input, errorElement, 'Please enter a valid date of birth');
        return false;
    }
    
    showSuccess(input, errorElement);
    return true;
}

function validateTerms(input) {
    const errorElement = document.getElementById('termsError');
    
    if (!input.checked) {
        if (errorElement) {
            errorElement.textContent = 'You must agree to the terms';
        }
        return false;
    }
    
    if (errorElement) {
        errorElement.textContent = '';
    }
    return true;
}

// ==================== PASSWORD STRENGTH METER ====================

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
        strengthText.style.color = 'var(--text-light)';
    } else if (strength <= 2) {
        strengthFill.classList.add('weak');
        strengthText.textContent = 'Weak password';
        strengthText.style.color = 'var(--danger-color)';
    } else if (strength <= 3) {
        strengthFill.classList.add('medium');
        strengthText.textContent = 'Medium strength';
        strengthText.style.color = 'var(--warning-color)';
    } else {
        strengthFill.classList.add('strong');
        strengthText.textContent = 'Strong password';
        strengthText.style.color = 'var(--success-color)';
    }
}

// ==================== UTILITY FUNCTIONS ====================

function showError(input, errorElement, message) {
    input.classList.add('error');
    input.classList.remove('success');
    if (errorElement) {
        errorElement.textContent = message;
    }
}

function showSuccess(input, errorElement) {
    input.classList.remove('error');
    input.classList.add('success');
    if (errorElement) {
        errorElement.textContent = '';
    }
}

function clearError(input) {
    input.classList.remove('error');
    const errorElement = document.getElementById(input.id + 'Error');
    if (errorElement) {
        errorElement.textContent = '';
    }
}

function setLoading(button, isLoading) {
    if (isLoading) {
        button.disabled = true;
        button.classList.add('loading');
    } else {
        button.disabled = false;
        button.classList.remove('loading');
    }
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    const icon = button.querySelector('i');
    
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
    if (!toast) return;
    
    // Remove existing classes
    toast.className = 'toast';
    
    // Add type class
    toast.classList.add(type);
    
    // Set icon based on type
    let icon = 'check-circle';
    if (type === 'error') icon = 'exclamation-circle';
    if (type === 'warning') icon = 'exclamation-triangle';
    
    toast.innerHTML = `<i class="fas fa-${icon}"></i> ${message}`;
    
    // Show toast
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Hide after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

function socialLogin(provider) {
    showToast(`Connecting to ${provider}...`, 'success');
    
    // Simulate social login
    setTimeout(() => {
        showToast(`Successfully connected with ${provider}!`, 'success');
    }, 1500);
}

// ==================== LOGOUT BUTTON FUNCTIONALITY ====================

function initLogoutButton() {
    // This would be implemented in your navigation bar
    const logoutButton = document.querySelector('.logout-button');
    if (logoutButton) {
        logoutButton.addEventListener('click', function(e) {
            e.preventDefault();
            showToast('Logging out...', 'info');
            
            // Simulate logout
            setTimeout(() => {
                fetch('logout.php')
                .then(response => response.text())
                .then(data => {
                    if (data.includes('login.php')) {
                        showToast('Logged out successfully', 'success');
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 1500);
                    } else {
                        showToast('Logout failed', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred', 'error');
                });
            }, 500);
        });
    }
}

// ==================== REMEMBER ME FUNCTIONALITY ====================

// Load saved email if remember me was checked
window.addEventListener('load', () => {
    const savedEmail = localStorage.getItem('rememberedEmail');
    const emailInput = document.getElementById('email');
    const rememberCheckbox = document.getElementById('remember');
    
    if (savedEmail && emailInput && rememberCheckbox) {
        emailInput.value = savedEmail;
        rememberCheckbox.checked = true;
    }
});

// Save email when logging in with remember me
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (form.id === 'loginForm') {
        const rememberCheckbox = document.getElementById('remember');
        const emailInput = document.getElementById('email');
        
        if (rememberCheckbox && rememberCheckbox.checked) {
            localStorage.setItem('rememberedEmail', emailInput.value);
        } else {
            localStorage.removeItem('rememberedEmail');
        }
    }
});