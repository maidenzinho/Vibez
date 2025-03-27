import { loadSettings, saveSettings } from './settings.js';

document.addEventListener('DOMContentLoaded', () => {
    const settings = loadSettings();
    initializeSettings(settings);
    setupEventListeners();
});

function initializeSettings(settings) {
    // Dark mode
    const darkModeToggle = document.getElementById('darkMode');
    darkModeToggle.checked = settings.darkMode;
    updateDarkMode(settings.darkMode);

    // Notificações
    document.getElementById('notifications').checked = settings.notifications;

    // Privacidade
    document.getElementById('privacy').value = settings.privacy;

    // Tamanho da Fonte
    const fontSizeInput = document.getElementById('fontSize');
    fontSizeInput.value = settings.fontSize;
    updateFontSize(settings.fontSize);

    // Email
    document.getElementById('email').value = settings.email;

    // Idioma
    document.getElementById('language').value = settings.language;
}

function setupEventListeners() {
    // Dark mode
    document.getElementById('darkMode').addEventListener('change', (e) => {
        updateDarkMode(e.target.checked);
    });

    const fontSizeInput = document.getElementById('fontSize');
    fontSizeInput.addEventListener('input', (e) => {
        updateFontSize(e.target.value);
    });

    // Botão de salvar
    document.getElementById('saveBtn').addEventListener('click', saveCurrentSettings);
}

function updateDarkMode(enabled) {
    document.body.classList.toggle('dark-mode', enabled);
}

function updateFontSize(size) {
    document.body.style.fontSize = `${size}px`;
    document.getElementById('fontSizeValue').textContent = `${size}px`;
}

function validatePassword(password) {
    return password.length >= 6;
}

function saveCurrentSettings() {
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    // Validação de senha
    if (newPassword || confirmPassword) {
        if (newPassword !== confirmPassword) {
            alert('As senhas não coincidem!');
            return;
        }
        if (!validatePassword(newPassword)) {
            alert('A senha deve ter pelo menos 6 caracteres!');
            return;
        }
    }

    const settings = {
        darkMode: document.getElementById('darkMode').checked,
        notifications: document.getElementById('notifications').checked,
        privacy: document.getElementById('privacy').value,
        fontSize: document.getElementById('fontSize').value,
        email: document.getElementById('email').value,
        language: document.getElementById('language').value,
        password: newPassword || undefined
    };

    saveSettings(settings);
    
    document.getElementById('newPassword').value = '';
    document.getElementById('confirmPassword').value = '';
    
    alert('Configurações salvas com sucesso!');
}