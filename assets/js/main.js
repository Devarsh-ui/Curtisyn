document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
    }
    
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
});

function validateContactForm() {
    const name = document.getElementById('name');
    const email = document.getElementById('email');
    const subject = document.getElementById('subject');
    const message = document.getElementById('message');
    let isValid = true;
    
    clearErrors();
    
    if (!name.value.trim()) {
        showError(name, 'Name is required');
        isValid = false;
    }
    
    if (!email.value.trim()) {
        showError(email, 'Email is required');
        isValid = false;
    } else if (!isValidEmail(email.value)) {
        showError(email, 'Invalid email');
        isValid = false;
    }
    
    if (!subject.value.trim()) {
        showError(subject, 'Subject is required');
        isValid = false;
    }
    
    if (!message.value.trim()) {
        showError(message, 'Message is required');
        isValid = false;
    }
    
    return isValid;
}

function validateRegisterForm() {
    const fullName = document.getElementById('full_name');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    let isValid = true;
    
    clearErrors();
    
    if (!fullName.value.trim()) {
        showError(fullName, 'Full name is required');
        isValid = false;
    }
    
    if (!email.value.trim()) {
        showError(email, 'Email is required');
        isValid = false;
    } else if (!isValidEmail(email.value)) {
        showError(email, 'Invalid email');
        isValid = false;
    }
    
    if (!password.value) {
        showError(password, 'Password is required');
        isValid = false;
    } else if (password.value.length < 6) {
        showError(password, 'Password must be 6+ characters');
        isValid = false;
    }
    
    if (password.value !== confirmPassword.value) {
        showError(confirmPassword, 'Passwords do not match');
        isValid = false;
    }
    
    return isValid;
}

function validateLoginForm() {
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    let isValid = true;
    
    clearErrors();
    
    if (!email.value.trim()) {
        showError(email, 'Email is required');
        isValid = false;
    } else if (!isValidEmail(email.value)) {
        showError(email, 'Invalid email');
        isValid = false;
    }
    
    if (!password.value) {
        showError(password, 'Password is required');
        isValid = false;
    }
    
    return isValid;
}

function showError(input, message) {
    input.classList.add('error');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.color = '#e74c3c';
    errorDiv.style.fontSize = '0.875rem';
    errorDiv.style.marginTop = '0.25rem';
    input.parentNode.appendChild(errorDiv);
}

function clearErrors() {
    document.querySelectorAll('.form-input').forEach(input => {
        input.classList.remove('error');
    });
    document.querySelectorAll('.field-error').forEach(error => {
        error.remove();
    });
}

function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}
