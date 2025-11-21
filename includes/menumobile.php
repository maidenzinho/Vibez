<!-- Menu mobile -->
    <nav class="mobile-menu">
        <div class="mobile-menu-nav">
            <a href="/index.php" class="mobile-menu-link active"><span class="mobile-menu-icon material-symbols-outlined">home</span></a>
            <a href="/chat/index.php" class="mobile-menu-link"><span class="mobile-menu-icon material-symbols-outlined">chat</span></a>
            <a href="/search.php" class="mobile-menu-link"><span class="mobile-menu-icon material-symbols-outlined">explore</span></a>
            <a href="/notifications.php" class="mobile-menu-link"><span class="mobile-menu-icon material-symbols-outlined">notifications</span></a>
            <a href="<?php echo SITE_URL; ?>/profile/?user=<?php echo urlencode($_SESSION['username']); ?>" class="mobile-menu-link"><span class="mobile-menu-icon material-symbols-outlined">person</span></a>
        </div>
    </nav>