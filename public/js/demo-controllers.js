/**
 * APIs Hub Interactive Demo Controllers (Alpine.js)
 */

/**
 * Step 1: Data Sources & Asset Quota Controller
 */
function demoDataSources(config) {
    return {
        darkMode: document.documentElement.classList.contains('dark'),
        providers: config.providers || {},
        activeChannelKey: config.activeChannelKey || 'google_search_console',
        tierUsage: config.tierUsage || 21,
        tierMax: config.tierMax || 50,
        theme: config.theme || 'dark',
        currentLang: config.currentLang || 'es',

        init() {
            this.$watch('darkMode', val => {
                this.theme = val ? 'dark' : 'light';
                localStorage.setItem('color-theme', this.theme);
                localStorage.setItem('demo_theme', this.theme);
                document.documentElement.classList.toggle('dark', val);
            });
        },

        get activeChannel() {
            for (const p of Object.values(this.providers)) {
                for (const ch of p.channels) {
                    if (ch.key === this.activeChannelKey) return ch;
                }
            }
            return null;
        },

        getProviderCount(provider) {
            return provider.channels.reduce((sum, ch) => sum + (ch.count || 0), 0);
        },

        selectChannel(key) {
            this.activeChannelKey = key;
        },

        toggleAsset(asset) {
            asset.enabled = !asset.enabled;
            if (asset.enabled) {
                this.tierUsage++;
            } else {
                this.tierUsage = Math.max(0, this.tierUsage - 1);
            }
            if (this.activeChannel) {
                this.activeChannel.count = this.activeChannel.assets.filter(a => a.enabled).length;
            }
        },

        simulateDiscovery() {
            alert(this.currentLang === 'es' 
                ? '¡Descubrimiento completado! Todos los activos disponibles están sincronizados.' 
                : 'Asset discovery completed! All available assets are synced.');
        },

        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('demo_theme', this.theme);
            if (this.theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    };
}

/**
 * Step 2: Cross-Channel Asset Groups Controller
 */
function demoAssetGroups(config) {
    return {
        darkMode: document.documentElement.classList.contains('dark'),
        groups: config.groups || [],
        search: '',
        activeGroup: config.groups && config.groups[0] ? config.groups[0] : null,
        showCreateModal: false,
        theme: config.theme || 'dark',
        currentLang: config.currentLang || 'es',

        init() {
            this.$watch('darkMode', val => {
                this.theme = val ? 'dark' : 'light';
                localStorage.setItem('color-theme', this.theme);
                localStorage.setItem('demo_theme', this.theme);
                document.documentElement.classList.toggle('dark', val);
            });
        },

        get filteredGroups() {
            if (!this.search) return this.groups;
            const q = this.search.toLowerCase();
            return this.groups.filter(g => 
                g.name.toLowerCase().includes(q) || 
                g.description.toLowerCase().includes(q)
            );
        },

        getChannelSummaryBadges(group) {
            const counts = {};
            for (const item of group.items) {
                counts[item.channel] = (counts[item.channel] || 0) + 1;
            }
            const labels = {
                'google_search_console': 'GSC',
                'google_analytics': 'GA4',
                'google_ads': 'GAds',
                'facebook_marketing': 'Meta Ads',
                'facebook_organic': 'Meta Org',
                'mailchimp': 'Mailchimp'
            };
            return Object.entries(counts).map(([ch, cnt]) => `${labels[ch] || ch} (${cnt})`);
        },

        getChannelLabel(channelKey) {
            const map = {
                'google_search_console': 'Google Search Console',
                'google_analytics': 'Google Analytics 4',
                'google_ads': 'Google Ads',
                'facebook_marketing': 'Meta Ads',
                'facebook_organic': 'Meta Organic',
                'mailchimp': 'Mailchimp'
            };
            return map[channelKey] || channelKey;
        },

        getChannelAssetsForGroup(channelKey) {
            if (!this.activeGroup) return [];
            return this.activeGroup.items.filter(i => i.channel === channelKey);
        },

        viewGroup(group) {
            this.activeGroup = group;
            window.scrollTo({ top: document.body.scrollHeight / 2, behavior: 'smooth' });
        },

        viewGroupById(id) {
            const found = this.groups.find(g => String(g.id) === String(id));
            if (found) {
                this.viewGroup(found);
            }
        },

        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('demo_theme', this.theme);
            if (this.theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    };
}

/**
 * Step 3: Omnichannel Interactive Dashboard Controller
 */
function demoDashboard(config) {
    const today = new Date();
    const formatDate = (d) => d.toISOString().split('T')[0];
    const defaultEnd = formatDate(today);
    const defaultStartDate = new Date();
    defaultStartDate.setDate(defaultStartDate.getDate() - 30);
    const defaultStart = formatDate(defaultStartDate);

    return {
        darkMode: document.documentElement.classList.contains('dark'),
        selectedGroup: config.selectedGroup || '1',
        currentLang: config.currentLang || 'es',
        assetGroups: config.assetGroups || {},
        widgets: config.widgets || [],
        isRefreshing: false,
        isFullWidth: localStorage.getItem('demo_full_width') !== 'false',
        datePreset: '30',
        dateStart: defaultStart,
        dateEnd: defaultEnd,
        popOutActive: false,
        popOutWidget: null,
        settingsActive: false,
        activeWidget: null,
        modalGranularity: 'daily',
        selectedMapMetric: 'clicks',
        theme: localStorage.getItem('demo_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),

        init() {
            this.$watch('darkMode', val => {
                this.theme = val ? 'dark' : 'light';
                localStorage.setItem('color-theme', this.theme);
                localStorage.setItem('demo_theme', this.theme);
                document.documentElement.classList.toggle('dark', val);
            });

            if (typeof GridStack !== 'undefined') {
                const grid = GridStack.init({
                    staticGrid: true,
                    float: true,
                    column: 12,
                    margin: 12,
                    cellHeight: 95,
                    animate: true,
                    columnOpts: {
                        columnMax: 12,
                        breakpointForWindow: true,
                        layout: 'moveScale',
                        breakpoints: [
                            { w: 1024, c: 12 },
                            { w: 1023, c: 1 }
                        ]
                    }
                }, '#demo-grid-stack');

                if (grid && window.innerWidth < 1024) {
                    grid.column(1, 'moveScale');
                }
            }

            const renderer = window.dashboardRenderer || window.DashboardRenderer;
            if (renderer && renderer.clearStorageCache) {
                renderer.clearStorageCache();
            }

            this.loadAllWidgets(false);
        },

        toggleFullWidth() {
            this.isFullWidth = !this.isFullWidth;
            localStorage.setItem('demo_full_width', this.isFullWidth ? 'true' : 'false');
            this.$nextTick(() => {
                window.dispatchEvent(new Event('resize'));
            });
        },

        setDatePreset(daysStr) {
            this.datePreset = daysStr;
            const days = parseInt(daysStr, 10);
            const now = new Date();
            const past = new Date();
            past.setDate(past.getDate() - days);

            this.dateEnd = formatDate(now);
            this.dateStart = formatDate(past);

            const renderer = window.dashboardRenderer || window.DashboardRenderer;
            if (renderer && renderer.clearStorageCache) {
                renderer.clearStorageCache();
            }
            this.loadAllWidgets(true);
        },

        onDateInputChange() {
            this.datePreset = 'custom';
            const renderer = window.dashboardRenderer || window.DashboardRenderer;
            if (renderer && renderer.clearStorageCache) {
                renderer.clearStorageCache();
            }
            this.loadAllWidgets(true);
        },

        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('demo_theme', this.theme);
            if (this.theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            this.loadAllWidgets(true);
        },

        onGroupChange() {
            const renderer = window.dashboardRenderer || window.DashboardRenderer;
            if (renderer && renderer.clearStorageCache) {
                renderer.clearStorageCache();
            }
            this.loadAllWidgets(true);
        },

        refreshAll() {
            this.isRefreshing = true;
            const renderer = window.dashboardRenderer || window.DashboardRenderer;
            if (renderer && renderer.clearStorageCache) {
                renderer.clearStorageCache();
            }
            this.loadAllWidgets(true);
            setTimeout(() => {
                this.isRefreshing = false;
            }, 400);
        },

        reloadWidget(widgetId) {
            if (widgetId === 113) {
                document.querySelectorAll('.jvm-tooltip').forEach(el => el.remove());
            }
            const renderer = window.dashboardRenderer || window.DashboardRenderer;
            const el = document.getElementById('widget-container-' + widgetId);
            if (!el || !renderer) return;

            el.innerHTML = renderer.loadingSkeleton();
            renderer._widgetData.delete(el);

            const controls = {
                asset_group: this.selectedGroup,
                lang: this.currentLang,
                date_start: this.dateStart,
                date_end: this.dateEnd
            };
            renderer.renderWidget(widgetId, el, controls, 'demo-tenant');
        },

        openSettings(widgetId) {
            this.activeWidget = this.widgets.find(w => w.id === widgetId);
            this.modalGranularity = 'daily';
            this.settingsActive = true;
        },

        switchMapMetric(metric) {
            this.selectedMapMetric = metric;
            const mapEl = document.getElementById('widget-container-113');
            if (mapEl) {
                const pillIndices = { 'clicks': 0, 'impressions': 1, 'ctr': 2, 'position': 3 };
                const targetPill = mapEl.querySelectorAll('.dash-map-pill')?.[pillIndices[metric]];
                if (targetPill) {
                    targetPill.click();
                }
            }
        },

        applyModalSettings() {
            this.settingsActive = false;
            if (this.activeWidget) {
                this.reloadWidget(this.activeWidget.id);
            }
        },

        openPopOut(widgetId) {
            this.popOutWidget = this.widgets.find(w => w.id === widgetId);
            const renderer = window.dashboardRenderer || window.DashboardRenderer;
            const modalContainer = document.getElementById('demo-modal-container');
            const sourceContainer = document.getElementById('widget-container-' + widgetId);

            if (!modalContainer || !renderer) return;

            this.popOutActive = true;
            this.$nextTick(() => {
                modalContainer.innerHTML = '';
                if (renderer.popOutWidget && sourceContainer) {
                    renderer.popOutWidget(sourceContainer, modalContainer);
                    setTimeout(() => {
                        const inst = renderer._chartInstances?.get(modalContainer);
                        if (inst) {
                            if (typeof inst.resize === 'function') inst.resize();
                            if (typeof inst.updateSize === 'function') inst.updateSize();
                        }
                    }, 50);
                } else {
                    const controls = {
                        asset_group: this.selectedGroup,
                        lang: this.currentLang,
                        date_start: this.dateStart,
                        date_end: this.dateEnd
                    };
                    renderer.renderWidget(widgetId, modalContainer, controls, 'demo-tenant');
                }
            });
        },

        closePopOut() {
            const renderer = window.dashboardRenderer || window.DashboardRenderer;
            const modalContainer = document.getElementById('demo-modal-container');
            if (this.popOutWidget) {
                const sourceContainer = document.getElementById('widget-container-' + this.popOutWidget.id);
                if (renderer && sourceContainer && modalContainer) {
                    if (typeof renderer.popInWidget === 'function') {
                        renderer.popInWidget(sourceContainer, modalContainer);
                    } else if (typeof renderer.returnPoppedOutWidget === 'function') {
                        renderer.returnPoppedOutWidget(sourceContainer, modalContainer);
                    }
                    setTimeout(() => {
                        const inst = renderer._chartInstances?.get(sourceContainer);
                        if (inst) {
                            if (typeof inst.resize === 'function') inst.resize();
                            if (typeof inst.updateSize === 'function') inst.updateSize();
                        }
                    }, 50);
                }
            }
            this.popOutActive = false;
            this.popOutWidget = null;
        },

        loadAllWidgets(force = false) {
            document.querySelectorAll('.jvm-tooltip').forEach(el => el.remove());

            const renderer = window.dashboardRenderer || window.DashboardRenderer;
            if (!renderer) {
                setTimeout(() => this.loadAllWidgets(force), 100);
                return;
            }

            this.widgets.forEach(w => {
                const el = document.getElementById('widget-container-' + w.id);
                if (!el) return;

                if (force) {
                    el.innerHTML = renderer.loadingSkeleton();
                    renderer._widgetData.delete(el);
                }

                const controls = {
                    asset_group: this.selectedGroup,
                    lang: this.currentLang,
                    date_start: this.dateStart,
                    date_end: this.dateEnd
                };

                renderer.renderWidget(w.id, el, controls, 'demo-tenant');
            });
        }
    };
}

/**
 * Step 4: Team Collaboration & Protected Public Views Controller
 */
function demoCollaboration(config) {
    return {
        darkMode: document.documentElement.classList.contains('dark'),
        theme: config.theme || 'dark',
        currentLang: config.currentLang || 'es',
        showInviteModal: false,

        init() {
            this.$watch('darkMode', val => {
                this.theme = val ? 'dark' : 'light';
                localStorage.setItem('color-theme', this.theme);
                localStorage.setItem('demo_theme', this.theme);
                document.documentElement.classList.toggle('dark', val);
            });
        },

        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('demo_theme', this.theme);
            if (this.theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    };
}

/**
 * Step 5: Data Intelligence, Custom Alerts, API & MCP Controller
 */
function demoIntelligence(config) {
    return {
        darkMode: document.documentElement.classList.contains('dark'),
        theme: config.theme || 'dark',
        currentLang: config.currentLang || 'es',
        activeTab: config.activeTab || 'alerts',

        init() {
            this.$watch('darkMode', val => {
                this.theme = val ? 'dark' : 'light';
                localStorage.setItem('color-theme', this.theme);
                localStorage.setItem('demo_theme', this.theme);
                document.documentElement.classList.toggle('dark', val);
            });
        },

        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('demo_theme', this.theme);
            if (this.theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    };
}

// Expose on global window object
window.demoDataSources = demoDataSources;
window.demoAssetGroups = demoAssetGroups;
window.demoDashboard = demoDashboard;
window.demoCollaboration = demoCollaboration;
window.demoIntelligence = demoIntelligence;
