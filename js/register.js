const form = document.getElementById('registerForm');
const password = document.getElementById('password');
const confirmPassword = document.getElementById('confirmPassword');

form.addEventListener('submit', (e) => {
    e.preventDefault();
    
    if (password.value !== confirmPassword.value) {
        showError('As senhas não coincidem');
        return;
    }
    
    console.log('Formulário enviado com sucesso!');
    
    showSuccess('Conta criada com sucesso!');
    form.reset();
});

function showError(message) {
    const notification = createNotification(message, 'error');
    showNotification(notification);
}

function showSuccess(message) {
    const notification = createNotification(message, 'success');
    showNotification(notification);
}

function createNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 2rem;
        border-radius: 0.5rem;
        color: white;
        font-weight: 500;
        animation: slideIn 0.3s ease-out;
        z-index: 1000;
    `;
    
    notification.style.background = type === 'error' ? 
        'var(--error-color)' : 
        'var(--success-color)';
    
    notification.textContent = message;
    return notification;
}

function showNotification(notification) {
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out forwards';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

const themeToggle = document.getElementById('themeToggle');
const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');

function initializeTheme() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        document.documentElement.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);
    } else if (prefersDarkScheme.matches) {
        document.documentElement.setAttribute('data-theme', 'dark');
        updateThemeIcon('dark');
    }
}

function updateThemeIcon(theme) {
    const moonPath = "M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z";
    const sunPath = "M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z";
    
    themeToggle.querySelector('path').setAttribute('d', theme === 'dark' ? moonPath : sunPath);
}

themeToggle.addEventListener('click', () => {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeIcon(newTheme);
});
initializeTheme();