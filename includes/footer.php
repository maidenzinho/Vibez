        </div> <!-- Fecha container principal -->
        
        <footer class="footer">
            <div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> Vibez. Todos os direitos reservados.</p>
                <nav class="footer-nav">
                    <a href="<?php echo SITE_URL; ?>/about">Sobre</a>
                    <a href="<?php echo SITE_URL; ?>/terms">Termos</a>
                    <a href="<?php echo SITE_URL; ?>/privacy">Privacidade</a>
                    <a href="<?php echo SITE_URL; ?>/contact">Contato</a>
                </nav>
            </div>
        </footer>
    </div>
    
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/theme.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/chat.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/posts.js"></script>
    <script>
document.addEventListener('click', async (e) => {
  if (e.target.closest('.like-btn')) {
    const likeBtn = e.target.closest('.like-btn');
    const postId = likeBtn.dataset.postId;
    const likeCount = likeBtn.querySelector('.like-count');

    try {
      const response = await fetch(`/api/like/${postId}`, {
        method: 'POST'
      });

      const result = await response.json();

      if (result.success) {
        likeBtn.classList.toggle('liked');
        likeCount.textContent = result.likeCount;
      } else {
        console.error('Erro na resposta do servidor:', result.error);
      }
    } catch (error) {
      console.error('Erro ao curtir post:', error);
    }
  }
});
</script>
</body>
</html>