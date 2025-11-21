document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = document.querySelector('.theme-icon');

    // Verifica se os elementos existem antes de usá-los
    if (!themeToggle || !themeIcon) {
        console.warn("Elemento #theme-toggle ou .theme-icon não encontrado.");
        return;
    }

    // Verifica o tema salvo no localStorage ou define como 'light' por padrão
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);

    // Atualiza o ícone com base no tema salvo
    updateThemeIcon(savedTheme);

    // Alterna entre temas quando o botão for clicado
    themeToggle.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';

        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);

        updateThemeIcon(newTheme);
    });

    // Função para atualizar o ícone do tema
    function updateThemeIcon(theme) {
        if (!themeIcon || !themeIcon.src) return;

        if (theme === 'dark') {
            themeIcon.src = themeIcon.src.replace('moon.svg', 'sun.svg');
            themeIcon.alt = 'Modo Claro';
        } else {
            themeIcon.src = themeIcon.src.replace('sun.svg', 'moon.svg');
            themeIcon.alt = 'Modo Escuro';
        }
    }
});
