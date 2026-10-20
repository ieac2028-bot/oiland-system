const updateThemeButton = (button, isDark) => {
    const label = isDark ? 'Switch to light mode' : 'Switch to dark mode';

    button.setAttribute('aria-label', label);
    button.setAttribute('title', label);
    button.querySelector('[data-theme-icon="moon"]')?.classList.toggle('hidden', isDark);
    button.querySelector('[data-theme-icon="sun"]')?.classList.toggle('hidden', !isDark);
    const themeLabel = button.querySelector('[data-theme-label]');

    if (themeLabel) {
        themeLabel.textContent = isDark ? 'Light mode' : 'Dark mode';
    }
};

document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    updateThemeButton(button, document.documentElement.classList.contains('dark'));

    button.addEventListener('click', () => {
        const isDark = !document.documentElement.classList.contains('dark');

        const applyTheme = () => {
            document.documentElement.classList.toggle('dark', isDark);
            updateThemeButton(button, isDark);
        };

        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
            || document.documentElement.classList.contains('a11y-reduce-motion');

        if (!prefersReducedMotion && typeof document.startViewTransition === 'function') {
            const bounds = button.getBoundingClientRect();
            const root = document.documentElement;
            root.style.setProperty('--theme-origin-x', `${bounds.left + bounds.width / 2}px`);
            root.style.setProperty('--theme-origin-y', `${bounds.top + bounds.height / 2}px`);

            document.startViewTransition(applyTheme);
        } else {
            applyTheme();
        }

        try {
            localStorage.setItem('oiland-theme', isDark ? 'dark' : 'light');
        } catch (error) {
            // Keep the current page usable when storage is disabled.
        }
    });
});
