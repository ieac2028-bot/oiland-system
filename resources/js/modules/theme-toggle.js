const updateThemeButton = (button, isDark) => {
    const label = isDark ? 'Switch to light mode' : 'Switch to dark mode';

    button.setAttribute('aria-label', label);
    button.setAttribute('title', label);
    button.querySelector('[data-theme-icon="moon"]')?.classList.toggle('hidden', isDark);
    button.querySelector('[data-theme-icon="sun"]')?.classList.toggle('hidden', !isDark);
};

document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    updateThemeButton(button, document.documentElement.classList.contains('dark'));

    button.addEventListener('click', () => {
        const isDark = !document.documentElement.classList.contains('dark');
        document.documentElement.classList.toggle('dark', isDark);
        updateThemeButton(button, isDark);

        try {
            localStorage.setItem('oiland-theme', isDark ? 'dark' : 'light');
        } catch (error) {
            // Keep the current page usable when storage is disabled.
        }
    });
});
