<script>
    (() => {
        let theme = null;

        try {
            theme = localStorage.getItem('oiland-theme');
        } catch (error) {
            // Storage can be disabled; fall back to the operating system theme.
        }

        if (theme !== 'light' && theme !== 'dark') {
            theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        document.documentElement.classList.toggle('dark', theme === 'dark');
    })();
</script>
