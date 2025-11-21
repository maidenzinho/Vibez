<?php
// /admin/settings.php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../index.php');
}

// Simula opções de configuração para demonstrar layout
$settings = [
    'site_name' => 'Vibeeez Social',
    'maintenance_mode' => false
];

require_once '../includes/header.php';
?>

<div class="admin-container">
    <div class="admin-sidebar">
        <h3>Admin Panel</h3>
        <nav class="admin-nav">
            <a href="https://vibez.allsocial.com.br" class="active">Voltar para o site</a>
            <a href="index.php" class="active">Dashboard</a>
            <a href="users.php">Usuários</a>
            <a href="posts.php">Posts</a>
            <a href="comments.php">Comentários</a>
            <a href="reports.php">Reports</a>
            <a href="settings.php">Configurações</a>
        </nav>
    </div>

<div class="admin-container">
    <?php include 'sidebar.php'; ?>
    <div class="admin-content">
        <h2>Configurações do Sistema</h2>
        <div class="admin-section">
            <form method="post" action="#" class="admin-form">
                <label for="site_name">Nome do site</label>
                <input type="text" id="site_name" name="site_name" value="<?= htmlspecialchars($settings['site_name']); ?>">

                <label for="maintenance_mode">
                    <input type="checkbox" id="maintenance_mode" name="maintenance_mode" <?= $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                    Modo Manutenção
                </label>

                <button type="submit" class="action-btn save">Salvar Configuraçes</button>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="../assets/css/admin.css">

<?php require_once '../includes/footer.php'; ?>