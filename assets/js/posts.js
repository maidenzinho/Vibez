document.addEventListener("DOMContentLoaded", () => {
    const postForm = document.getElementById('create-post-form');

    if (!postForm) {
        console.error("Erro: Formulário de post não encontrado.");
        return;
    }

    postForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(postForm);
        const submitButton = postForm.querySelector(".post-button");

        submitButton.disabled = true; // Evita posts duplicados

        try {
            const response = await fetch('post.php', {
                method: 'POST',
                body: formData
            });

            const text = await response.text();
            console.log("Resposta do servidor:", text);

            const post = JSON.parse(text);

            if (post.success) {
                location.reload(); // Recarrega a página para exibir o novo post
            } else {
                alert("Erro: " + post.error);
            }
        } catch (error) {
            console.error('Erro ao criar post:', error);
            alert("Erro ao postar. Verifique o console.");
        } finally {
            submitButton.disabled = false;
        }
    });
});
