document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('theme-toggle');
    const backToTop = document.getElementById('back-to-top');

    themeToggle?.addEventListener('click', () => {
        const isDark = document.documentElement.classList.toggle('dark');

        localStorage.theme = isDark ? 'dark' : 'light';
    });

    window.addEventListener('scroll', () => {
        if (!backToTop) return;

        if (window.scrollY > 400) {
            backToTop.classList.remove('hidden');
        } else {
            backToTop.classList.add('hidden');
        }
    });

    backToTop?.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth',
        });
    });
});
