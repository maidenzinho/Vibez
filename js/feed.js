import { config } from './config.js';

let isDarkMode = true; 

function toggleTheme() {
    isDarkMode = !isDarkMode;
    const theme = isDarkMode ? config.darkMode : config.lightMode;
    Object.entries(theme).forEach(([key, value]) => {
        document.documentElement.style.setProperty(key, value);
    });
    
    const themeIcon = document.querySelector('.theme-toggle svg');
    themeIcon.innerHTML = isDarkMode ? 
        '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9z" stroke-width="2"/>' :
        '<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2m-3.172-7.172l1.414-1.414M4.929 19.071l1.414-1.414m0-11.314L4.929 4.929m13.557 13.557l-1.414-1.414" stroke-width="2"/>';
}

const posts = [
    {
        id: 1,
        author: 'Alice Johnson',
        username: '@alice.j',
        avatar: 'https://ui-avatars.com/api/?name=Alice',
        content: 'Acabei de finalizar um projeto incrível! 🎉 Mal posso esperar para compartilhar mais detalhes com vocês!',
        time: '2h',
        likes: 24,
        comments: 5,
        shares: 2
    },
    {
        id: 2,
        author: 'Bob Smith',
        username: '@bob.smith',
        avatar: 'https://ui-avatars.com/api/?name=Bob',
        content: 'O nascer do sol hoje estava simplesmente magnífico! 🌅 Começando o dia com muita energia e positividade.',
        time: '4h',
        likes: 42,
        comments: 8,
        shares: 3
    }
];

function createPostElement(post) {
    const postElement = document.createElement('div');
    postElement.className = 'post';
    postElement.innerHTML = `
        <div class="post-header">
            <img src="${post.avatar}" alt="${post.author}" class="avatar">
            <div class="post-info">
                <span class="post-author">${post.author}</span>
                <span class="username">${post.username}</span>
                <span class="post-time">${post.time}</span>
            </div>
        </div>
        <div class="post-content">
            ${post.content}
        </div>
        <div class="post-actions">
            <button class="action-btn like-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3" stroke-width="2"/>
                </svg>
                ${post.likes}
            </button>
            <button class="action-btn comment-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" stroke-width="2"/>
                </svg>
                ${post.comments}
            </button>
            <button class="action-btn share-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8m-4-6l-4-4-4 4m4-4v13" stroke-width="2"/>
                </svg>
                ${post.shares}
            </button>
        </div>
    `;
    return postElement;
}

function renderPosts() {
    const postsContainer = document.getElementById('posts-container');
    postsContainer.innerHTML = ''; 
    posts.forEach(post => {
        postsContainer.appendChild(createPostElement(post));
    });
}

document.addEventListener('DOMContentLoaded', () => {
    toggleTheme(); 
    
    renderPosts();

    const sidebarSvgs = document.querySelectorAll('.sidebar-nav svg');
    sidebarSvgs.forEach(svg => {
        svg.setAttribute('stroke-width', '2');
    });

    const postBtn = document.querySelector('.post-btn');
    const textarea = document.querySelector('textarea');

    postBtn.addEventListener('click', () => {
        const content = textarea.value.trim();
        if (content) {
            const newPost = {
                id: posts.length + 1,
                author: 'Você',
                username: '@user',
                avatar: 'https://ui-avatars.com/api/?name=User',
                content: content,
                time: 'agora',
                likes: 0,
                comments: 0,
                shares: 0
            };
            posts.unshift(newPost);
            renderPosts(); 
            textarea.value = '';
        }
    });

    textarea.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            postBtn.click();
        }
    });

    const themeToggle = document.querySelector('.theme-toggle');
    themeToggle.addEventListener('click', toggleTheme);

    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            navItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');
        });
    });
});