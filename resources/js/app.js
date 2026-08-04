import './bootstrap';
import Alpine from 'alpinejs';
import { uiAssetSelector } from './components/ui-asset-selector';
import { jointDashboard } from './dashboards/joint-dashboard';
import { gscDashboard } from './dashboards/gsc-dashboard';
import { fbDashboard } from './dashboards/fbm-dashboard';
import { fboDashboard } from './dashboards/fbo-dashboard';
import { ga4Dashboard } from './dashboards/ga4-dashboard';
import { kpiBrowser } from './dashboards/kpi-reference';
import { dashboardView, widgetHeader } from './dashboards/dashboard-view';
import { dashboardBuilder } from './dashboards/dashboard-builder';

window.Alpine = Alpine;
window.uiAssetSelector = uiAssetSelector;
window.jointDashboard = jointDashboard;
window.gscDashboard = gscDashboard;
window.fbDashboard = fbDashboard;
window.fboDashboard = fboDashboard;
window.ga4Dashboard = ga4Dashboard;
window.kpiBrowser = kpiBrowser;
window.dashboardView = dashboardView;
window.widgetHeader = widgetHeader;
window.dashboardBuilder = dashboardBuilder;

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
Alpine.data('jointDashboard', jointDashboard);
Alpine.data('gscDashboard', gscDashboard);
Alpine.data('fbDashboard', fbDashboard);
Alpine.data('fboDashboard', fboDashboard);
Alpine.data('ga4Dashboard', ga4Dashboard);
Alpine.data('kpiBrowser', kpiBrowser);
Alpine.data('dashboardView', dashboardView);
Alpine.data('widgetHeader', widgetHeader);
Alpine.data('dashboardBuilder', dashboardBuilder);

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
