export function publicViewBar(config = {}) {
    return {
        theme: localStorage.getItem('pv_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),
        currentLang: config.currentLang || 'en',

        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('pv_theme', this.theme);
            if (this.theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            const html = document.documentElement;
            html.style.overflow = 'hidden';
            requestAnimationFrame(() => {
                html.style.overflow = '';
            });
            if (window.isEmbedded) {
                window.parent.postMessage({ type: 'apis-hub-theme', theme: this.theme }, '*');
            }
        },

        changeLang(newLang) {
            const url = new URL(window.location.href);
            url.searchParams.set('lang', newLang);
            window.location.href = url.toString();
        },
    };
}
