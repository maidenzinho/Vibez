// Função debounce para evitar chamadas excessivas
function debounce(func, wait) {
    let timeout;
    return function () {
        const context = this, args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
}

// Função global para obter a URL correta da imagem de perfil
function getProfilePicURL(profilePic) {
    if (!profilePic || profilePic === 'default-profile.png') {
        return '/assets/images/default-profile.png';
    }
    return `/uploads/${profilePic}`;
}

let loadingPosts = false;
let lastPostId = document.querySelector('.post:last-child')?.dataset.postId || null;

window.addEventListener('scroll', debounce(() => {
    if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 500) {
        loadMorePosts();
    }
}, 200));

async function loadMorePosts() {
    if (loadingPosts || !lastPostId) return;

    loadingPosts = true;

    try {
        const response = await fetch(`/api/posts.php?lastId=${lastPostId}`);
        if (!response.ok) throw new Error(`Erro HTTP! Status: ${response.status}`);

        const text = await response.text();
        console.log("Resposta do servidor:", text);

        try {
            const posts = JSON.parse(text);

            if (Array.isArray(posts) && posts.length > 0) {
                const postsContainer = document.querySelector('.posts');

                posts.forEach(post => {
                    postsContainer.appendChild(createPostElement(post));
                });

                lastPostId = posts[posts.length - 1].id;
            }
        } catch (jsonError) {
            console.error('Erro ao processar JSON:', jsonError);
        }
    } catch (error) {
        console.error('Erro ao carregar mais posts:', error);
    } finally {
        loadingPosts = false;
    }
}

function createPostElement(post) {
    const postElement = document.createElement('div');
    postElement.className = 'post';
    postElement.dataset.postId = post.id;

    let sharedHtml = '';
    if (post.shared_from) {
        sharedHtml = `
            <p class="shared-label">Compartilhado de <a href="/profile/?user=${post.shared_from}">${post.shared_from}</a>:</p>
            <p>${post.original_content}</p>
        `;
    }

    postElement.innerHTML = `
        <div class="post-header">
            <img src="${getProfilePicURL(post.profile_pic)}" alt="${post.username}" class="post-profile-pic">
            <div class="post-user-info">
                <a href="/profile/?user=${post.username}" class="post-username">${post.username}</a>
                <span class="post-time">${new Date(post.created_at).toLocaleString()}</span>
            </div>
        </div>

        <div class="post-content">
            ${sharedHtml}
            <p>${post.content}</p>
            ${post.image ? `<img src="/uploads/${post.image}" alt="Post image" class="post-image">` : ''}
        </div>

        <div class="post-actions">
            <button class="like-btn ${post.user_liked ? 'liked' : ''}" data-post-id="${post.id}">
                <i class="fas fa-heart"></i>
                <span class="like-count">${post.like_count}</span>
            </button>

            <button class="comment-btn" data-post-id="${post.id}">
                <i class="fas fa-comment"></i>
                <span class="comment-count">${post.comment_count}</span>
            </button>

            <button class="share-btn" data-post-id="${post.id}">
                <i class="fas fa-share"></i>
            </button>
        </div>

        <div class="comments-section" id="comments-${post.id}" style="display: none;"></div>
    `;

    return postElement;
}

document.addEventListener('click', async (e) => {
    // Like
    if (e.target.closest('.like-btn')) {
        const likeBtn = e.target.closest('.like-btn');
        const postId = likeBtn.dataset.postId;
        const likeCount = likeBtn.querySelector('.like-count');

        try {
            const response = await fetch('/api/like/index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ post_id: postId })
            });

            const data = await response.json();

            if (data.success) {
                likeBtn.classList.toggle('liked');
                likeCount.textContent = data.new_like_count;
            } else {
                console.error('Erro ao curtir:', data.error);
            }
        } catch (error) {
            console.error('Erro ao curtir post:', error);
        }
    }

    // Comentários
    if (e.target.closest('.comment-btn')) {
        const commentBtn = e.target.closest('.comment-btn');
        const postId = commentBtn.dataset.postId;
        const commentsSection = document.getElementById(`comments-${postId}`);

        if (commentsSection.style.display === 'none') {
            try {
                const response = await fetch(`/api/comments/index.php?post_id=${postId}`);
                const json = await response.json();
                console.log('Resposta da API de comentários:', json);

                if (json.success) {
                    const comments = json.comments;

                    commentsSection.innerHTML = comments.map(comment => `
                        <div class="comment">
                            <img src="${getProfilePicURL(comment.profile_pic)}" alt="${comment.username}" class="comment-profile-pic">
                            <div class="comment-body">
                                <a href="profile/?user=${comment.username}" class="comment-username">${comment.username}</a>
                                <p>${comment.content}</p>
                            </div>
                        </div>
                    `).join('');

                    commentsSection.innerHTML += `
                        <form class="comment-form" data-post-id="${postId}">
                            <input type="text" placeholder="Escreva um comentário..." required>
                            <button type="submit">Comentar</button>
                        </form>
                    `;

                    commentsSection.style.display = 'block';
                } else {
                    commentsSection.innerHTML = `<p style="color:red;">Erro ao carregar comentários.</p>`;
                    commentsSection.style.display = 'block';
                }
            } catch (error) {
                console.error('Erro ao carregar comentários:', error);
                commentsSection.innerHTML = `<p style="color:red;">Erro ao carregar comentários.</p>`;
                commentsSection.style.display = 'block';
            }
        } else {
            commentsSection.style.display = 'none';
        }
    }

    // Compartilhamento
    if (e.target.closest('.share-btn')) {
        const postId = e.target.closest('.share-btn').dataset.postId;

        try {
            const response = await fetch('/api/share.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ post_id: postId })
            });

            const data = await response.json();

            if (data.success) {
                alert('Post compartilhado no seu perfil!');
            } else {
                alert('Erro ao compartilhar: ' + data.error);
            }
        } catch (err) {
            console.error('Erro ao compartilhar:', err);
        }
    }

    // Seguir/Deixar de seguir
    if (e.target.closest('.follow-btn')) {
        const button = e.target.closest('.follow-btn');
        const userId = button.dataset.userId;

        try {
            const response = await fetch('/api/follow.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId })
            });

            const data = await response.json();

            if (data.success) {
                const isFollowing = button.classList.toggle('following');
                button.textContent = isFollowing ? 'Seguindo' : 'Seguir';
            } else {
                alert(data.message || 'Erro ao seguir usuário.');
            }
        } catch (err) {
            console.error('Erro ao seguir:', err);
        }
    }
});

document.addEventListener('submit', async (e) => {
    if (e.target.closest('.comment-form')) {
        e.preventDefault();
        const form = e.target.closest('.comment-form');
        const postId = form.dataset.postId;
        const input = form.querySelector('input');
        const comment = input.value.trim();

        if (comment) {
            try {
                const response = await fetch(`/api/comments/index.php?post_id=${postId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ comment })
                });

                const result = await response.json();

                if (result.success) {
                    input.value = '';

                    const commentBtn = document.querySelector(`.comment-btn[data-post-id="${postId}"]`);
                    // Recarrega comentários
                    const commentsSection = document.getElementById(`comments-${postId}`);
                    commentsSection.style.display = 'none';
                    commentBtn.click();

                    const commentCount = commentBtn.querySelector('.comment-count');
                    commentCount.textContent = result.commentCount;
                }
            } catch (error) {
                console.error('Erro ao postar comentário:', error);
            }
        }
    }
});
