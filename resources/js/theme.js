/**
 * Synchronous theme initialization to prevent CLS and Flash of Unstyled Content.
 */
(function () {
    const theme = localStorage.getItem('color-theme');
    const prefersLight = window.matchMedia('(prefers-color-scheme: light)').matches;

    if (theme === 'light' || (!theme && prefersLight)) {
        document.documentElement.classList.remove('dark');
    } else {
        document.documentElement.classList.add('dark');
    }

    // Initialize dataLayer for early marketing scripts
    window.dataLayer = window.dataLayer || [];
})();
