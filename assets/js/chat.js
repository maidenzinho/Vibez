let selectedUserId = null;

// Referências básicas dos elementos
const chatForm          = document.getElementById('chat-form');
const messageInput      = document.getElementById('message-input');
const attachmentInput   = document.getElementById('attachment');
const btnAttachment     = document.getElementById('btn-attachment');
const chatMessages      = document.getElementById('chat-messages');
const chatUsernameLabel = document.getElementById('chat-username');
const searchInput       = document.getElementById("search-user");
const userList          = document.getElementById("user-list");

// -----------------------------
// Envio de mensagens (texto + mídia)
// -----------------------------
if (chatForm) {
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!selectedUserId) return;

        const message = messageInput ? messageInput.value.trim() : '';
        const hasFile = attachmentInput && attachmentInput.files && attachmentInput.files[0];

        // Nada pra enviar
        if (!hasFile && message === '') return;

        const formData = new FormData();
        formData.append('receiver_id', selectedUserId);
        formData.append('message', message);

        if (hasFile) {
            formData.append('attachment', attachmentInput.files[0]);
        }

        try {
            const res  = await fetch('/chat/send_message.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (!data.success) {
                console.error(data.error || 'Erro ao enviar mensagem');
                return;
            }

            if (messageInput) messageInput.value = '';
            if (attachmentInput) attachmentInput.value = '';

            loadMessages();
        } catch (err) {
            console.error('Erro na requisição de envio:', err);
        }
    });
}

// Botão de clipe abre o input de arquivo
if (btnAttachment && attachmentInput) {
    btnAttachment.addEventListener('click', () => {
        attachmentInput.click();
    });
}

// -----------------------------
// Carregar mensagens do usuário selecionado
// -----------------------------
async function loadMessages() {
    if (!selectedUserId || !chatMessages) return;

    try {
        const res  = await fetch(`/chat/get_messages.php?user_id=${encodeURIComponent(selectedUserId)}`);
        const data = await res.json();

        chatMessages.innerHTML = '';

        if (!Array.isArray(data) || data.length === 0) {
            const p = document.createElement('p');
            p.classList.add('no-chat');
            p.textContent = 'Nenhuma mensagem ainda';
            chatMessages.appendChild(p);
            return;
        }

        data.forEach(msg => {
            const isMe = (parseInt(msg.sender_id) === LOGGED_IN_USER_ID);

            const wrapper = document.createElement('div');
            wrapper.classList.add('chat-message');
            wrapper.classList.add(isMe ? 'me' : 'other');

            // Avatar
            const avatar = document.createElement('img');
            avatar.classList.add('chat-avatar');
            avatar.src = msg.profile_pic || '/assets/images/default-profile.png';
            avatar.alt = msg.username || '';
            
            // Bolha
            const bubble = document.createElement('div');
            bubble.classList.add('chat-bubble');

            const tipo = msg.message_type || 'text';

            if (tipo === 'image' && msg.file_path) {
                const img = document.createElement('img');
                img.src = msg.file_path;
                img.classList.add('chat-image');
                bubble.appendChild(img);

                if (msg.message) {
                    const caption = document.createElement('p');
                    caption.textContent = msg.message;
                    bubble.appendChild(caption);
                }

            } else if (tipo === 'audio' && msg.file_path) {
                const audio = document.createElement('audio');
                audio.controls = true;
                audio.src = msg.file_path;
                bubble.appendChild(audio);

                if (msg.message) {
                    const caption = document.createElement('p');
                    caption.textContent = msg.message;
                    bubble.appendChild(caption);
                }

            } else if (tipo === 'video' && msg.file_path) {
                const video = document.createElement('video');
                video.controls = true;
                video.classList.add('chat-video');

                const source = document.createElement('source');
                source.src = msg.file_path;
                video.appendChild(source);

                bubble.appendChild(video);

                if (msg.message) {
                    const caption = document.createElement('p');
                    caption.textContent = msg.message;
                    bubble.appendChild(caption);
                }

            } else {
                // Texto puro
                bubble.textContent = msg.message || '';
            }

            wrapper.appendChild(avatar);
            wrapper.appendChild(bubble);
            chatMessages.appendChild(wrapper);
        });

        chatMessages.scrollTop = chatMessages.scrollHeight;
    } catch (err) {
        console.error('Erro ao carregar mensagens:', err);
    }
}

// Atualização periódica
setInterval(() => {
    if (selectedUserId) loadMessages();
}, 3000);

// -----------------------------
// Busca AJAX de usuários da rede
// -----------------------------
if (searchInput && userList) {
    searchInput.addEventListener("input", function () {
        const query = this.value.trim();

        if (query === "") {
            userList.innerHTML = "<li class='no-results'>Digite um nome de usuário</li>";
            return;
        }

        fetch(`/api/search_users.php?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                userList.innerHTML = "";

                if (!Array.isArray(data) || data.length === 0) {
                    userList.innerHTML = "<li class='no-results'>Nenhum usuário encontrado</li>";
                    return;
                }

                data.forEach(user => {
                    const li = document.createElement("li");
                    li.classList.add("user-item");
                    li.dataset.id = user.id;

                    li.innerHTML = `
                        <img class="chat-avatar" src="${user.profile_pic}" alt="${user.username}">
                        <span class="chat-username">${user.username}</span>
                    `;

                    li.addEventListener('click', () => {
                        selectedUserId = user.id;
                        if (chatUsernameLabel) {
                            chatUsernameLabel.textContent = user.username;
                        }
                        loadMessages();
                    });

                    userList.appendChild(li);
                });
            })
            .catch(err => console.error('Erro na busca de usuários:', err));
    });
}

// -----------------------------
// Clique nos usuários já renderizados no HTML
// -----------------------------
function setupUserClickEvents() {
    document.querySelectorAll('.user-item').forEach(userItem => {
        userItem.addEventListener('click', () => {
            selectedUserId = userItem.dataset.id;
            if (chatUsernameLabel) {
                const nameSpan = userItem.querySelector('.chat-username');
                chatUsernameLabel.textContent = nameSpan ? nameSpan.textContent : userItem.textContent;
            }
            loadMessages();
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    setupUserClickEvents();
});
