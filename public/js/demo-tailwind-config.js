/**
 * APIs Hub Demo Tailwind Configuration & Early Theme Initializer
 */
(function() {
    // 1. Synchronous early theme application (avoids flash of light theme)
    var storageKey = window.demoThemeStorageKey || 'color-theme';
    var theme = localStorage.getItem(storageKey);
    if (!theme) {
        theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    if (theme === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }

    // 2. Filter cdn.tailwindcss.com production warning
    var origWarn = console.warn;
    console.warn = function(...args) {
        if (args[0] && typeof args[0] === 'string' && args[0].includes('cdn.tailwindcss.com')) return;
        origWarn.apply(console, args);
    };

    // 3. Tailwind configuration for CDN runtime
    window.tailwind = window.tailwind || {};
    window.tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    'brand-blue': '#00a7f9',
                    'brand-teal': '#00cac4',
                    'brand-dark': '#3b3b3b',
                    'brand-slate': '#494949',
                    primary: {
                        '50': '#eff6ff', '100': '#dbeafe', '200': '#bfdbfe', '300': '#93c5fd',
                        '400': '#60a5fa', '500': '#3b82f6', '600': '#2563eb', '700': '#1d4ed8',
                        '800': '#1e40af', '900': '#1e3a8a', '950': '#172554'
                    },
                    success: {
                        '500': '#10b981', '600': '#059669', '700': '#047857'
                    },
                    warning: {
                        '50': '#fffbeb', '500': '#f59e0b', '600': '#d97706', '700': '#b45309'
                    }
                },
                boxShadow: {
                    'glow': '0 0 20px -5px #00a7f9',
                    'glow-intense': '0 0 30px -5px #00a7f9'
                }
            }
        }
    };
})();
