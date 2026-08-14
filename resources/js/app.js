import './bootstrap';
import { uiAssetSelector } from './components/ui-asset-selector';
import { uiDropdown } from './components/ui-dropdown';
import { assetSelector } from './components/asset-selector';
import { copyLink } from './components/copy-link';
import { embedCodeConfig } from './components/embed-code';
import { floatingTooltip } from './components/floating-tooltip';
import { dataSources } from './pages/data-sources';
import { jointDashboard } from './dashboards/joint-dashboard';
import { gscDashboard } from './dashboards/gsc-dashboard';
import { fbDashboard } from './dashboards/fbm-dashboard';
import { fboDashboard } from './dashboards/fbo-dashboard';
import { ga4Dashboard } from './dashboards/ga4-dashboard';
import { kpiBrowser } from './dashboards/kpi-reference';
import { dashboardView, widgetHeader } from './dashboards/dashboard-view';
import { dashboardBuilder } from './dashboards/dashboard-builder';
import { publicViewBar } from './public-view/public-view-bar';
import { sharedView, widgetHeaderPv } from './public-view/public-dashboard';
import { initPublicViewEmbed } from './public-view/embed';
import { tenantClock } from './components/tenant-clock';

// Export functions to window
window.uiAssetSelector = uiAssetSelector;
window.uiDropdown = uiDropdown;
window.assetSelector = assetSelector;
window.copyLink = copyLink;
window.embedCodeConfig = embedCodeConfig;
window.floatingTooltip = floatingTooltip;
window.tenantClock = tenantClock;
window.dataSources = dataSources;
window.jointDashboard = jointDashboard;
window.gscDashboard = gscDashboard;
window.fbDashboard = fbDashboard;
window.fboDashboard = fboDashboard;
window.ga4Dashboard = ga4Dashboard;
window.kpiBrowser = kpiBrowser;
window.dashboardView = dashboardView;
window.widgetHeader = widgetHeader;
window.dashboardBuilder = dashboardBuilder;
window.publicViewBar = publicViewBar;
window.sharedView = sharedView;
window.widgetHeaderPv = widgetHeaderPv;

const registerAlpineComponents = (Alpine) => {
    if (!Alpine || Alpine._componentsRegistered) return;
    Alpine._componentsRegistered = true;

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
    Alpine.data('uiDropdown', uiDropdown);
    Alpine.data('assetSelector', assetSelector);
    Alpine.data('copyLink', copyLink);
    Alpine.data('embedCodeConfig', embedCodeConfig);
    Alpine.data('floatingTooltip', floatingTooltip);
    Alpine.data('tenantClock', tenantClock);
    Alpine.data('dataSources', dataSources);
    Alpine.data('jointDashboard', jointDashboard);
    Alpine.data('gscDashboard', gscDashboard);
    Alpine.data('fbDashboard', fbDashboard);
    Alpine.data('fboDashboard', fboDashboard);
    Alpine.data('ga4Dashboard', ga4Dashboard);
    Alpine.data('kpiBrowser', kpiBrowser);
    Alpine.data('dashboardView', dashboardView);
    Alpine.data('widgetHeader', widgetHeader);
    Alpine.data('dashboardBuilder', dashboardBuilder);
    Alpine.data('publicViewBar', publicViewBar);
    Alpine.data('sharedView', sharedView);
    Alpine.data('widgetHeaderPv', widgetHeaderPv);
};

const registerSubNavStore = (Alpine) => {
    if (!Alpine || Alpine.store('subnav')) return;
    if (typeof Alpine.$persist !== 'function') return;

    Alpine.store('subnav', {
        isOpen: Alpine.$persist(true).as('subnavOpen'),

        toggle() {
            this.isOpen = ! this.isOpen;
        },
    });
};

document.addEventListener('alpine:init', () => {
    if (window.Alpine) {
        registerAlpineComponents(window.Alpine);
        registerSubNavStore(window.Alpine);
    }
});

if (window.Alpine) {
    registerAlpineComponents(window.Alpine);
    registerSubNavStore(window.Alpine);
} else {
    import('alpinejs').then((AlpineModule) => {
        const Alpine = AlpineModule.default;
        window.Alpine = Alpine;
        registerAlpineComponents(Alpine);
        Alpine.start();
    });
}

initPublicViewEmbed();

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

// KB cluster tooltip: highlight the anchor matching the current page/section
// when the tippy box becomes visible (tippy v6 emits no DOM events, so watch
// the box `data-state` attribute).
const initSubnavTooltipHighlight = () => {
    const highlight = (box) => {
        const anchors = box.querySelectorAll('.fi-subnav-tooltip-anchors');
        anchors.forEach((ul) => {
            const items = Array.from(ul.querySelectorAll('li'));
            const currentHash = window.location.hash;
            const currentPath = window.location.pathname;
            let matched = false;

            items.forEach((li) => {
                const a = li.querySelector('a');
                let active = false;
                if (a) {
                    try {
                        const url = new URL(a.href);
                        if (currentHash) {
                            active = url.pathname === currentPath && url.hash === currentHash;
                        } else {
                            active = url.pathname === currentPath && ! url.hash;
                        }
                    } catch (e) {
                        // ignore malformed hrefs
                    }
                }
                li.classList.toggle('fi-subnav-tooltip-anchor-active', active);
                if (active) matched = true;
            });

            if (! matched && items[0]) {
                try {
                    const firstUrl = new URL(items[0].querySelector('a').href);
                    if (firstUrl.pathname === currentPath) {
                        items[0].classList.add('fi-subnav-tooltip-anchor-active');
                    }
                } catch (e) {
                    // ignore malformed hrefs
                }
            }
        });
    };

    const clear = (box) => {
        box.querySelectorAll('.fi-subnav-tooltip-anchor-active').forEach((li) => {
            li.classList.remove('fi-subnav-tooltip-anchor-active');
        });
    };

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'attributes' && mutation.attributeName === 'data-state') {
                const box = mutation.target;
                if (box.classList && box.classList.contains('tippy-box')) {
                    if (box.getAttribute('data-state') === 'visible') {
                        highlight(box);
                    } else {
                        clear(box);
                    }
                }
            }

            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType !== 1) return;
                    const box = node.classList && node.classList.contains('tippy-box')
                        ? node
                        : node.querySelector && node.querySelector('.tippy-box');
                    if (box && box.getAttribute('data-state') === 'visible') {
                        highlight(box);
                    }
                });
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['data-state'],
    });
};

initSubnavTooltipHighlight();

// Scroll active main menu item into view on page load & collapse/uncollapse
const initSidebarScrollToActive = () => {
    const scrollToActive = () => {
        const performScroll = () => {
            const sidebarNav = document.querySelector('.fi-sidebar nav, aside.fi-sidebar nav, .fi-sidebar-nav');
            if (!sidebarNav) return;

            // Prioritize exact active sidebar item over group wrapper
            const activeItem = 
                sidebarNav.querySelector('.fi-sidebar-item.fi-active') ||
                sidebarNav.querySelector('.fi-sidebar-item-active') ||
                sidebarNav.querySelector('a.fi-sidebar-item-button.bg-gray-100') ||
                sidebarNav.querySelector('.fi-sidebar-group.fi-active .fi-sidebar-item-button') ||
                sidebarNav.querySelector('.fi-sidebar-group.fi-active');

            if (!activeItem) return;

            const navRect = sidebarNav.getBoundingClientRect();
            const itemRect = activeItem.getBoundingClientRect();

            if (navRect.height === 0 || itemRect.height === 0) return;

            const isVisible = (
                itemRect.top >= navRect.top + 20 &&
                itemRect.bottom <= navRect.bottom - 20
            );

            if (!isVisible) {
                const targetScrollTop = sidebarNav.scrollTop + (itemRect.top - navRect.top) - (navRect.height / 2) + (itemRect.height / 2);
                sidebarNav.scrollTo({
                    top: Math.max(0, targetScrollTop),
                    behavior: 'smooth'
                });
            }
        };

        // Stagger executions to handle Alpine x-collapse and layout transitions
        requestAnimationFrame(() => {
            performScroll();
            setTimeout(performScroll, 100);
            setTimeout(performScroll, 350);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scrollToActive);
    } else {
        scrollToActive();
    }

    window.addEventListener('load', scrollToActive);
    document.addEventListener('livewire:navigated', scrollToActive);

    const attachSidebarWatcher = () => {
        if (window.Alpine && window.Alpine.store('sidebar')) {
            window.Alpine.effect(() => {
                const isOpen = window.Alpine.store('sidebar').isOpen;
                scrollToActive();
            });
        }
    };

    if (window.Alpine) {
        attachSidebarWatcher();
    } else {
        document.addEventListener('alpine:initialized', attachSidebarWatcher);
    }
};

initSidebarScrollToActive();


