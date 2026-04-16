// Auth JavaScript - Registration and Login

document.addEventListener('DOMContentLoaded', function() {
    console.log('Auth.js loaded successfully');
    
    // Initialize registration form
    initRegisterForm();
    
    // Initialize login form
    initLoginForm();
});

// ==================== REGISTRATION FORM ====================

function initRegisterForm() {
    const form = document.getElementById('registerForm');
    if (!form) {
        console.log('Register form not found');
        return;
    }
    
    console.log('Register form found, initializing...');
    
    // Mark as initialized to prevent double binding
    if (form.getAttribute('data-js-initialized')) {
        console.log('Form already initialized by JS');
        return;
    }
    form.setAttribute('data-js-initialized', 'true');
    
    // Remove inline fallback if JS loads successfully
    const inlineScript = form.getAttribute('data-initialized');
    if (inlineScript) {
        console.log('Inline script already initialized, skipping');
        return;
    }
    
    // Add real-time validation
    bindValidations();
    
    // Handle form submission
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
    console.log('Form submitted');
    
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

    // Validate all fields
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

        // Prepare data
    const formData = {
        firstName: inputs.firstName.value.trim(),
        lastName: inputs.lastName.value.trim(),
        username: inputs.username.value.trim(),
        email: inputs.email.value.trim(),
        password: inputs.password.value,
        birthdate: inputs.birthdate.value,
        terms: inputs.terms.checked
    };

    // DEBUG: Check what's being sent
    console.log('Sending data:', formData);
    console.log('Terms value:', inputs.terms.checked);
    console.log('Terms element:', inputs.terms);

    // Show loading
    setLoading(submitBtn, true);

    try {
        const response = await fetch('register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();
        console.log('Response:', result);

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
        showToast('Network error. Please try again.', 'error');
    }
}

// ==================== LOGIN FORM ====================

function initLoginForm() {
    const form = document.getElementById('loginForm');
    if (!form) return;
    
    if (form.getAttribute('data-js-initialized')) return;
    form.setAttribute('data-js-initialized', 'true');
    
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');

    if (usernameInput) {
        usernameInput.addEventListener('blur', () => validateField(usernameInput, 'username'));
        usernameInput.addEventListener('input', () => clearError(usernameInput));
    }

    if (passwordInput) {
        passwordInput.addEventListener('blur', () => validateField(passwordInput, 'password'));
        passwordInput.addEventListener('input', () => clearError(passwordInput));
    }
    
    form.addEventListener('submit', handleLoginSubmit);
}

async function handleLoginSubmit(e) {
    e.preventDefault();
    
    const username = document.getElementById('username');
    const password = document.getElementById('password');
    const submitBtn = document.getElementById('loginBtn');
    
    let isValid = true;

    if (!validateField(username, 'username')) isValid = false;
    if (!validateField(password, 'password')) isValid = false;

    if (!isValid) return;

    setLoading(submitBtn, true);

    try {
        const formData = new URLSearchParams();
        formData.append('username', username.value);
        formData.append('password', password.value);

        const response = await fetch('login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData
        });

        const text = await response.text();
        
        if (text.includes('login.php') || response.redirected) {
            showToast('Login successful! Redirecting...', 'success');
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1500);
        } else {
            setLoading(submitBtn, false);
            showError(password, document.getElementById('passwordError'), 'Invalid username or password');
            showToast('Login failed', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        setLoading(submitBtn, false);
        showToast('An error occurred', 'error');
    }
}

// ==================== VALIDATION FUNCTIONS ====================

function validateField(input, fieldType) {
    const value = input.value.trim();
    const errorElement = document.getElementById(input.id + 'Error');
    
    if (!value) {
        showError(input, errorElement, fieldType.charAt(0).toUpperCase() + fieldType.slice(1) + ' is required');
        return false;
    }
    
    if (fieldType === 'password' && value.length < 6) {
        showError(input, errorElement, 'Password must be at least 6 characters');
        return false;
    }
    
    showSuccess(input, errorElement);
    return true;
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
        showError(input, errorElement, 'Only letters allowed');
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
        showError(input, errorElement, 'Only letters, numbers, and underscores');
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
        showError(input, errorElement, 'Please enter a valid email');
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
            showError(input, errorElement, 'Need uppercase, lowercase, and number');
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
            errorElement.textContent = 'You must agree to the terms';
        }
        return false;
    }
    
    if (errorElement) {
        errorElement.textContent = '';
    }
    return true;
}

// ==================== PASSWORD STRENGTH ====================

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

// ==================== UTILITY FUNCTIONS ====================

function showError(input, errorElement, message) {
    if (input) input.classList.add('error');
    if (input) input.classList.remove('success');
    if (errorElement) errorElement.textContent = message;
}

function showSuccess(input, errorElement) {
    if (input) input.classList.remove('error');
    if (input) input.classList.add('success');
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
        console.log('Toast message:', message);
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

// ==================== CHECK LOGIN STATUS ====================

function checkLoginStatus() {
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    const currentUser = localStorage.getItem('currentUser');
    
    if (isLoggedIn && currentUser) {
        console.log('User logged in:', currentUser);
        return true;
    }
    
    return false;
}

// ==================== LOGOUT ====================

function logout() {
    localStorage.removeItem('isLoggedIn');
    localStorage.removeItem('currentUser');
    showToast('Logged out successfully', 'success');
    
    setTimeout(function() {
        window.location.href = 'login.php';
    }, 1500);
}

// Check login on page load (for protected pages)
window.addEventListener('load', function() {
    // If on index.html or other protected pages, check login
    const currentPage = window.location.pathname;
    
    if (currentPage.includes('index.html') || currentPage.includes('home')) {
        if (!checkLoginStatus()) {
            window.location.href = 'login.php';
        }
    }
});