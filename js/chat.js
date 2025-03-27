import { GiphyFetch } from '@giphy/js-fetch-api';
import 'emoji-picker-element';
import { CONFIG } from './configchat.js';

const gf = new GiphyFetch(CONFIG.GIPHY_API_KEY);

class ChatApp {
    constructor() {
        this.initializeElements();
        this.bindEvents();
        this.loadUserProfile();
        this.loadContacts();
        this.activeChat = null;
        this.loadTheme();
    }

    initializeElements() {
        this.searchInput = document.getElementById('searchInput');
        this.searchResults = document.getElementById('searchResults');
        this.contactsList = document.getElementById('contactsList');
        this.messagesContainer = document.getElementById('messagesContainer');
        this.messageInput = document.getElementById('messageInput');
        this.sendButton = document.getElementById('sendButton');
        this.emojiButton = document.getElementById('emojiButton');
        this.gifButton = document.getElementById('gifButton');
        this.emojiPicker = document.getElementById('emojiPicker');
        this.gifPicker = document.getElementById('gifPicker');
        this.userProfileImage = document.getElementById('userProfileImage');
        this.userProfileName = document.getElementById('userProfileName');
        
        const picker = document.createElement('emoji-picker');
        this.emojiPicker.appendChild(picker);
        
        this.themeSwitch = document.createElement('button');
        this.themeSwitch.className = 'theme-switch';
        this.themeSwitch.innerHTML = `
            <svg viewBox="0 0 24 24">
                <path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5s5-2.24 5-5s-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1zM11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1zm0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1zM5.99 4.58c-.39-.39-1.03-.39-1.41 0c-.39.39-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41L5.99 4.58zm12.37 12.37c-.39-.39-1.03-.39-1.41 0c-.39.39-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0c.39-.39.39-1.03 0-1.41l-1.06-1.06zm1.06-10.96c.39-.39.39-1.03 0-1.41c-.39-.39-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06zM7.05 18.36c.39-.39.39-1.03 0-1.41c-.39-.39-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06z"/>
            </svg>`;
        document.body.appendChild(this.themeSwitch);

        this.emojiButton.innerHTML = '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-5-6c.78 2.34 2.72 4 5 4s4.22-1.66 5-4H7zm8-4c.55 0 1-.45 1-1s-.45-1-1-1-1 .45-1 1 .45 1 1 1zm-6 0c.55 0 1-.45 1-1s-.45-1-1-1-1 .45-1 1 .45 1 1 1z"/></svg>';
        this.gifButton.innerHTML = `
            <svg viewBox="0 0 24 24">
                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-8 7.5H8v1h2c.55 0 1 .45 1 1V14c0 .55-.45 1-1 1H7.5c-.55 0-1-.45-1-1v-4c0-.55.45-1 1-1H11v1.5zm3.5 4.5h-1.5V9H14v6zm4-3h-1v1.5h-1.5V10h2.5c.55 0 1 .45 1 1v1c0 .55-.45 1-1 1z"/>
            </svg>`;
    }

    bindEvents() {
        this.searchInput.addEventListener('input', () => this.handleSearch());
        this.sendButton.addEventListener('click', () => this.sendMessage());
        this.messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.sendMessage();
        });
        this.emojiButton.addEventListener('click', () => this.toggleEmojiPicker());
        this.gifButton.addEventListener('click', () => this.toggleGifPicker());
        
        document.addEventListener('emoji-click', event => {
            this.messageInput.value += event.detail.unicode;
            this.emojiPicker.style.display = 'none';
        });
        
        this.themeSwitch.addEventListener('click', () => this.toggleTheme());
    }

    loadUserProfile() {
        this.userProfileImage.src = CONFIG.USER_PROFILE.image;
        this.userProfileName.textContent = CONFIG.USER_PROFILE.name;
    }

    loadContacts() {
        CONFIG.MOCK_USERS.forEach(user => {
            const contactElement = this.createContactElement(user);
            this.contactsList.appendChild(contactElement);
        });
    }

    createContactElement(user) {
        const div = document.createElement('div');
        div.className = 'contact-item';
        div.innerHTML = `
            <img src="${user.image}" alt="${user.name}" class="profile-pic">
            <span>${user.name}</span>
        `;
        div.addEventListener('click', () => this.openChat(user));
        return div;
    }

    async handleSearch() {
        const query = this.searchInput.value.toLowerCase();
        if (!query) {
            this.searchResults.style.display = 'none';
            return;
        }

        const filteredUsers = CONFIG.MOCK_USERS.filter(user => 
            user.name.toLowerCase().includes(query)
        );

        this.searchResults.innerHTML = '';
        filteredUsers.forEach(user => {
            const result = this.createContactElement(user);
            this.searchResults.appendChild(result);
        });
        
        this.searchResults.style.display = 'block';
    }

    openChat(user) {
        this.activeChat = user;
        document.getElementById('activeChatUserImage').src = user.image;
        document.getElementById('activeChatUsername').textContent = user.name;
        this.messagesContainer.innerHTML = '';
        this.searchResults.style.display = 'none';
        this.searchInput.value = '';
    }

    sendMessage() {
        const message = this.messageInput.value.trim();
        if (!message || !this.activeChat) return;

        const messageElement = document.createElement('div');
        messageElement.className = 'message sent';
        messageElement.textContent = message;
        this.messagesContainer.appendChild(messageElement);
        
        this.messageInput.value = '';
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;

        // Simulate received message
        setTimeout(() => {
            const response = document.createElement('div');
            response.className = 'message received';
            response.textContent = `Isso é uma resposta automática de ${this.activeChat.name}`;
            this.messagesContainer.appendChild(response);
            this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
        }, 1000);
    }

    toggleEmojiPicker() {
        const isVisible = this.emojiPicker.style.display === 'block';
        this.emojiPicker.style.display = isVisible ? 'none' : 'block';
        this.gifPicker.style.display = 'none';
    }

    async toggleGifPicker() {
        const isVisible = this.gifPicker.style.display === 'block';
        this.gifPicker.style.display = isVisible ? 'none' : 'block';
        this.emojiPicker.style.display = 'none';

        if (!isVisible) {
            try {
                const { data } = await gf.trending({ limit: 12, rating: 'g' });
                this.gifPicker.innerHTML = '';
                data.forEach(gif => {
                    const img = document.createElement('img');
                    img.src = gif.images.fixed_height_small.url;
                    img.className = 'gif-item';
                    img.alt = gif.title;
                    img.addEventListener('click', () => {
                        this.sendGif(gif.images.fixed_height.url);
                        this.gifPicker.style.display = 'none';
                    });
                    this.gifPicker.appendChild(img);
                });
            } catch (error) {
                console.error('Error fetching GIFs:', error);
                this.gifPicker.innerHTML = '<p style="color: var(--text-color); padding: 10px;">Por favor, configure uma chave válida da API Giphy no config.js</p>';
            }
        }
    }

    sendGif(url) {
        const messageElement = document.createElement('div');
        messageElement.className = 'message sent';
        const img = document.createElement('img');
        img.src = url;
        img.style.maxWidth = '100%';
        img.style.borderRadius = '8px';
        messageElement.appendChild(img);
        this.messagesContainer.appendChild(messageElement);
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }

    loadTheme() {
        const currentTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', currentTheme);
    }

    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        // Update theme switch icon
        if (newTheme === 'dark') {
            this.themeSwitch.innerHTML = `
                <svg viewBox="0 0 24 24">
                    <path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9s9-4.03 9-9c0-.46-.04-.92-.1-1.36c-.98 1.37-2.58 2.26-4.4 2.26c-3.03 0-5.5-2.47-5.5-5.5c0-1.82.89-3.42 2.26-4.4c-.44-.06-.9-.1-1.36-.1z"/>
                </svg>`;
        } else {
            this.themeSwitch.innerHTML = `
                <svg viewBox="0 0 24 24">
                    <path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5s5-2.24 5-5s-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1zM11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1zm0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1zM5.99 4.58c-.39-.39-1.03-.39-1.41 0c-.39.39-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41L5.99 4.58zm12.37 12.37c-.39-.39-1.03-.39-1.41 0c-.39.39-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0c.39-.39.39-1.03 0-1.41l-1.06-1.06zm1.06-10.96c.39-.39.39-1.03 0-1.41c-.39-.39-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06zM7.05 18.36c.39-.39.39-1.03 0-1.41c-.39-.39-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06z"/>
                </svg>`;
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new ChatApp();
});