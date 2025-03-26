const themeToggle = document.querySelector('.theme-toggle');
        const themeIcon = themeToggle.querySelector('i');

        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
            themeIcon.classList.replace('bi-moon-fill', 'bi-sun-fill');
        }

        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            if (document.body.classList.contains('dark-mode')) {
                themeIcon.classList.replace('bi-moon-fill', 'bi-sun-fill');
                localStorage.setItem('theme', 'dark');
            } else {
                themeIcon.classList.replace('bi-sun-fill', 'bi-moon-fill');
                localStorage.setItem('theme', 'light');
            }
        });