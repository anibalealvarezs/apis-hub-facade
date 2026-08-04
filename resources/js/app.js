import './bootstrap';
import Alpine from 'alpinejs';
import { uiAssetSelector } from './components/ui-asset-selector';

window.Alpine = Alpine;
window.uiAssetSelector = uiAssetSelector;

// Theme Controller Component
Alpine.data('themeControl', () => ({
    darkMode: document.documentElement.classList.contains('dark'),
    init() {
        this.$watch('darkMode', val => {
            document.documentElement.classList.toggle('dark', val);
            localStorage.setItem('color-theme', val ? 'dark' : 'light');
        });
    }
}));

Alpine.data('uiAssetSelector', uiAssetSelector);

Alpine.start();

// Portal Link Global Handler
document.addEventListener('click', (e) => {
    const portalLink = e.target.closest('.js-portal-link');
    if (portalLink) {
        const payload = portalLink.dataset.portal;
        if (payload) {
            window.location.href = atob(payload);
        }
    }
});
