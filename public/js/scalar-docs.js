/**
 * Scalar API Reference initialization and real-time theme synchronization
 */
(function() {
    function initScalarReference() {
        const isDark = document.documentElement.classList.contains('dark');
        const scriptTag = document.getElementById('api-reference');
        if (!scriptTag) return;

        const highContrastCss = `
            .dark-mode, .dark-mode .scalar-api-reference, .dark-mode .scalar-app {
                --scalar-color-1: #f8fafc !important;
                --scalar-color-2: #cbd5e1 !important;
                --scalar-color-3: #94a3b8 !important;
                --scalar-background-1: #090d16 !important;
                --scalar-background-2: #0f172a !important;
                --scalar-background-3: #1e293b !important;
                --scalar-color-accent: #00a7f9 !important;
            }
            .dark-mode p, .dark-mode li, .dark-mode td, .dark-mode .markdown {
                color: #cbd5e1 !important;
            }
            .dark-mode h1, .dark-mode h2, .dark-mode h3, .dark-mode h4, .dark-mode h5, .dark-mode strong {
                color: #f8fafc !important;
            }
            .light-mode, .light-mode .scalar-api-reference, .light-mode .scalar-app {
                --scalar-color-1: #0f172a !important;
                --scalar-color-2: #334155 !important;
                --scalar-color-3: #64748b !important;
                --scalar-background-1: #ffffff !important;
                --scalar-background-2: #f8fafc !important;
                --scalar-background-3: #f1f5f9 !important;
                --scalar-color-accent: #0284c7 !important;
            }
            .light-mode p, .light-mode li, .light-mode td, .light-mode .markdown {
                color: #334155 !important;
            }
            .light-mode h1, .light-mode h2, .light-mode h3, .light-mode strong {
                color: #0f172a !important;
            }
        `;

        const locale = document.documentElement.lang && document.documentElement.lang.startsWith('es') ? 'es' : 'en';

        scriptTag.setAttribute('data-configuration', JSON.stringify({
            theme: 'default',
            darkMode: isDark,
            layout: 'modern',
            showSidebar: true,
            hideModels: false,
            customCss: highContrastCss,
            locale: locale,
            localization: {
                locale: locale
            },
            defaultHttpClient: {
                targetKey: 'shell',
                clientKey: 'curl'
            }
        }));
    }

    initScalarReference();

    document.addEventListener('DOMContentLoaded', () => {
        const syncThemeToScalar = () => {
            const isDark = document.documentElement.classList.contains('dark');
            const targets = document.querySelectorAll('.scalar-api-reference, .scalar-container, .scalar-app, [data-scalar-app]');
            targets.forEach(el => {
                if (isDark) {
                    el.classList.remove('light-mode');
                    el.classList.add('dark-mode');
                    el.setAttribute('data-theme', 'dark');
                } else {
                    el.classList.remove('dark-mode');
                    el.classList.add('light-mode');
                    el.setAttribute('data-theme', 'light');
                }
            });
        };

        const observer = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    syncThemeToScalar();
                }
            }
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        });

        setTimeout(syncThemeToScalar, 800);
        setTimeout(syncThemeToScalar, 2000);
    });
})();
