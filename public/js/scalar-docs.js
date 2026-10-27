/**
 * Scalar API Reference initialization and real-time theme synchronization
 */
(function() {
    function initScalarReference() {
        const isDark = document.documentElement.classList.contains('dark');
        const scriptTag = document.getElementById('api-reference');
        if (!scriptTag) return;

        const highContrastCss = `
            .scalar-container.dark-mode, .scalar-container .scalar-api-reference, .scalar-container .scalar-app {
                --scalar-color-1: #f8fafc !important;
                --scalar-color-2: #cbd5e1 !important;
                --scalar-color-3: #94a3b8 !important;
                --scalar-background-1: #090d16 !important;
                --scalar-background-2: #0f172a !important;
                --scalar-background-3: #1e293b !important;
                --scalar-color-accent: #00a7f9 !important;
            }
            .scalar-container p, .scalar-container li, .scalar-container td, .scalar-container .markdown {
                color: #cbd5e1 !important;
            }
            .scalar-container h1, .scalar-container h2, .scalar-container h3, .scalar-container h4, .scalar-container h5, .scalar-container strong {
                color: #f8fafc !important;
            }
            .scalar-container.light-mode, .scalar-container.light-mode .scalar-api-reference, .scalar-container.light-mode .scalar-app {
                --scalar-color-1: #0f172a !important;
                --scalar-color-2: #334155 !important;
                --scalar-color-3: #64748b !important;
                --scalar-background-1: #ffffff !important;
                --scalar-background-2: #f8fafc !important;
                --scalar-background-3: #f1f5f9 !important;
                --scalar-color-accent: #0284c7 !important;
            }
            .scalar-container.light-mode p, .scalar-container.light-mode li, .scalar-container.light-mode td, .scalar-container.light-mode .markdown {
                color: #334155 !important;
            }
            .scalar-container.light-mode h1, .scalar-container.light-mode h2, .scalar-container.light-mode h3, .scalar-container.light-mode strong {
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

            // Ensure document.body dark-mode class added by Scalar is in sync with html.dark
            if (isDark) {
                document.body.classList.remove('light-mode');
                document.body.classList.add('dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
                document.body.classList.add('light-mode');
            }
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
