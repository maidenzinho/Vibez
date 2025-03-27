const SETTINGS_KEY = 'user_settings';

const defaultSettings = {
    darkMode: false,
    notifications: true,
    privacy: 'public',
    fontSize: 16,
    email: '',
    language: 'pt',
    password: ''
};

export function loadSettings() {
    const savedSettings = localStorage.getItem(SETTINGS_KEY);
    if (savedSettings) {
        return JSON.parse(savedSettings);
    }
    return defaultSettings;
}

export function saveSettings(settings) {
    
    const settingsToSave = { ...settings };
    delete settingsToSave.password;
    localStorage.setItem(SETTINGS_KEY, JSON.stringify(settingsToSave));
}

export function validatePassword(password) {
    return password.length >= 6;
}