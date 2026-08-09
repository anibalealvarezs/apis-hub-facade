export function dashboardBuilder(config = {}) {
    const dashboardControls = config.dashboardControls || {};
    return {
        // ─── Unsaved Layout State ───
        isDirty: false,
        _isInitializingGrid: true,
        showUnsavedNavModal: false,
        pendingNavUrl: null,

        // ─── Multi-Widget Selection State ───
        selectedWidgetIds: [],

        toggleWidgetSelection(id) {
            const strId = String(id);
            const idx = this.selectedWidgetIds.findIndex(item => String(item) === strId);
            if (idx > -1) {
                this.selectedWidgetIds.splice(idx, 1);
            } else {
                this.selectedWidgetIds.push(isNaN(id) ? id : parseInt(id, 10));
            }
        },

        isWidgetSelected(id) {
            const strId = String(id);
            return this.selectedWidgetIds.some(item => String(item) === strId);
        },

        selectAllWidgets() {
            this.selectedWidgetIds = (this.widgets || []).map(w => w.id);
        },

        clearWidgetSelection() {
            this.selectedWidgetIds = [];
        },

        deleteSelectedWidgets() {
            if (this.selectedWidgetIds.length === 0) return;
            const idsToDelete = [...this.selectedWidgetIds];
            idsToDelete.forEach(id => {
                this.deleteWidget(id);
            });
            this.clearWidgetSelection();
        },

        duplicateSelectedWidgets() {
            if (this.selectedWidgetIds.length === 0) return;
            const idsToDuplicate = [...this.selectedWidgetIds];
            idsToDuplicate.forEach(id => {
                this.duplicateWidget(id);
            });
        },

        confirmDiscardAndLeave() {
            this.isDirty = false;
            this.showUnsavedNavModal = false;
            if (this.pendingNavUrl) {
                window.location.assign(this.pendingNavUrl);
            }
        },

        confirmSaveAndLeave() {
            const currentLayout = this.getLayout();
            if (this.$wire) {
                this.$wire.saveLayout(currentLayout).then(() => {
                    this.isDirty = false;
                    this.showUnsavedNavModal = false;
                    if (this.pendingNavUrl) {
                        window.location.assign(this.pendingNavUrl);
                    }
                });
            }
        },

        // ─── Grid State ───
        widgets: config.widgets || [],
        gridLayout: config.gridState || [],
        grid: null,
        widgetLabels: config.widgetLabels || {},
        widgetDescriptions: config.widgetDescriptions || {},
        widgetSvgs: config.widgetSvgs || {},

        // ─── Channels & Assets ───
        channels: config.channels || {},
        allChannelAssets: {},
        allChannelAssetGroups: {},
        allChannelMetrics: {},
        dashboardAssets: {},
        dashboardMetrics: {},
        availableDependencies: {},
        availableGranularities: {},
        availableLanguages: config.availableLanguages || {},

        // ─── Dashboard Controls ──
        showDashboardControls: false,
        showWidgetControls: false,
        dashboardControls,
        assetGroups: config.assetGroups || {},

        // ─── Widget Controls ──
        activeMobileTab: 'config',
        activeSeriesIndex: 0,
        widgetControlsTarget: {},
        widgetControlsForm: {
            date_inherit: true,
            date_start: '',
            date_end: '',
            zero_inherit: true,
            zero_handling: dashboardControls.zero_handling || 'remove',
            series_inherit: true,
            channel: '',
            asset_mode: 'single',
            asset: '',
            assets: [],
            granularity: dashboardControls.granularity || 'daily',
            titles: {},
            descriptions: {},
            metrics: [],
            series_assets: {},
            series_asset_groups: {},
            dm_assets: {},
        },
        widgetKpiConfig: {},
        widgetAssets: {},
        widgetMetrics: {},
        searchQueries: {},

        // ─── Share ──
        showShareDialog: false,
        isPublic: config.isPublic || false,
        collaborators: [],
        sharedUsers: [],
        shareUserId: '',

        // ─── Add Widget Modal ──
        showAddWidgetModal: false,
        sourceTypes: config.sourceTypes || {},
        kpis: config.kpis || {},
        derivedMetrics: config.derivedMetrics || {},
        addWidgetForm: {
            source_type: '',
            custom_kpi_id: '',
            derived_metric_id: '',
            widget_type: '',
            name: '',
        },

        // ─── Computed ──
        get optimalWidgetTypes() {
            if (this.addWidgetForm.source_type === 'kpi' && this.addWidgetForm.custom_kpi_id) {
                const kpiData = this.kpis[this.addWidgetForm.custom_kpi_id];
                return kpiData ? (kpiData.optimal_widgets || []) : [];
            }
            return [];
        },

        get availableWidgetTypes() {
            if (!this.addWidgetForm.source_type) return {};

            const allTypes = this.widgetLabels || {};
            let filtered = {};

            if (this.addWidgetForm.source_type === 'metric') {
                const allowed = ['tile', 'line_chart', 'bar_chart', 'sparkline', 'table', 'gauge'];
                for (const t of allowed) {
                    if (allTypes[t]) filtered[t] = allTypes[t];
                }
                return filtered;
            }

            if (this.addWidgetForm.source_type === 'kpi') {
                const kpiId = this.addWidgetForm.custom_kpi_id;
                if (!kpiId) return {};

                const kpiData = this.kpis[kpiId];
                const allowed = kpiData ? (kpiData.compatible_widgets || []) : [];

                if (allowed.length === 0) return allTypes;

                for (const t of allowed) {
                    if (allTypes[t]) filtered[t] = allTypes[t];
                }
                return filtered;
            }

            if (this.addWidgetForm.source_type === 'derived_metric') {
                const allowed = config.derivedMetricWidgetTypes || [];
                for (const t of allowed) {
                    if (allTypes[t]) filtered[t] = allTypes[t];
                }
                return filtered;
            }

            return allTypes;
        },

        get availableChartTypesForControls() {
            const allTypes = this.widgetLabels || {};
            const target = this.widgetControlsTarget;
            if (!target || !target.source_type) return allTypes;

            let filtered = {};

            if (target.source_type === 'metric') {
                const allowed = ['tile', 'line_chart', 'bar_chart', 'sparkline', 'table', 'gauge'];
                for (const t of allowed) {
                    if (allTypes[t]) filtered[t] = allTypes[t];
                }
                return filtered;
            }

            if (target.source_type === 'kpi') {
                const kpiId = target.source_config?.custom_kpi_id;
                if (!kpiId) return allTypes;

                const kpiData = this.kpis[kpiId];
                const allowed = kpiData ? (kpiData.compatible_widgets || []) : [];

                if (allowed.length === 0) return allTypes;

                for (const t of allowed) {
                    if (allTypes[t]) filtered[t] = allTypes[t];
                }
                if (target.widget_type && !filtered[target.widget_type]) {
                    filtered[target.widget_type] = allTypes[target.widget_type] || target.widget_type;
                }
                return filtered;
            }

            if (target.source_type === 'derived_metric') {
                const allowed = config.derivedMetricWidgetTypes || [];
                for (const t of allowed) {
                    if (allTypes[t]) filtered[t] = allTypes[t];
                }
                if (target.widget_type && !filtered[target.widget_type]) {
                    filtered[target.widget_type] = allTypes[target.widget_type] || target.widget_type;
                }
                return filtered;
            }

            return allTypes;
        },

        // ─── UI Helpers ───
        getWidgetDescription(type) {
            return this.widgetDescriptions[type] || 'Standard widget';
        },

        getWidgetSvg(type) {
            return this.widgetSvgs[type] || this.widgetSvgs['tile'];
        },

        // ─── Initialization ──
        init() {
            this.$nextTick(() => {
                const container = document.getElementById('grid-stack');
                if (container && !this.grid) {
                    this.initGrid();
                }
                this.initAllAssets();
            });

            window.addEventListener('beforeunload', (e) => {
                if (this.isDirty) {
                    e.preventDefault();
                    e.returnValue = 'You have unsaved changes to your dashboard layout.';
                    return e.returnValue;
                }
            });

            const preventLivewireNav = (e) => {
                if (!this.isDirty) return;
                const el = e.target.closest('a[href], button[url], [wire\\:navigate]');
                if (el) {
                    const href = el.getAttribute('href');
                    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    if (e.type === 'click' || e.type === 'pointerdown') {
                        this.pendingNavUrl = href;
                        this.showUnsavedNavModal = true;
                    }
                    return false;
                }
            };

            window.addEventListener('mouseover', (e) => {
                if (!this.isDirty) return;
                const link = e.target.closest('a[wire\\:navigate], a[href]');
                if (link && link.hasAttribute('wire:navigate')) {
                    link.removeAttribute('wire:navigate');
                    link.setAttribute('data-wire-nav-removed', 'true');
                }
            }, true);

            window.addEventListener('pointerdown', preventLivewireNav, true);
            window.addEventListener('mousedown', preventLivewireNav, true);
            window.addEventListener('click', preventLivewireNav, true);

            const origPushState = window.history.pushState;
            window.history.pushState = (state, title, url) => {
                if (this.isDirty) {
                    this.pendingNavUrl = url;
                    this.showUnsavedNavModal = true;
                    return;
                }
                return origPushState.apply(window.history, [state, title, url]);
            };
        },

        initAllAssets() {
            const channelKeys = Object.keys(this.channels);
            if (!this.$wire || typeof this.$wire.getAssetsForChannel !== 'function') {
                console.error('[dashboard-builder] Livewire $wire unavailable; channel assets will not load.');
                return;
            }
            channelKeys.forEach(ch => {
                this.$wire.getAssetsForChannel(ch).then(assets => {
                    this.allChannelAssets = { ...this.allChannelAssets, [ch]: assets };
                    if (ch === this.dashboardControls.channel) {
                        this.dashboardAssets = assets;
                    }
                });
                this.$wire.getAssetGroupsForChannel(ch).then(groups => {
                    this.allChannelAssetGroups = { ...this.allChannelAssetGroups, [ch]: groups };
                });
                this.$wire.getMetricsForChannel(ch).then(metrics => {
                    this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                    if (ch === this.dashboardControls.channel) {
                        this.dashboardMetrics = metrics;
                    }
                });
            });
        },

        updateDependenciesAndGranularities(explicitSavedDependency = null, explicitSavedGranularity = null) {
            const widget = this.widgetControlsTarget;
            if (!widget || !this.$wire) return;

            let ch = '';
            if (widget.source_type === 'kpi') {
                ch = this.widgetKpiConfig?.dependent_channel || this.widgetControlsForm.channel;
            } else {
                ch = this.widgetControlsForm.raw_series?.[0]?.channel || '';
            }

            if (!ch) {
                this.availableDependencies = {};
                this.availableGranularities = {};
                return;
            }

            const savedDependency = explicitSavedDependency || this.widgetControlsForm.dependency;
            const savedGranularity = explicitSavedGranularity || this.widgetControlsForm.granularity;

            this.$wire.getDependenciesForChannel(ch).then(deps => {
                const willSetDependency = (savedDependency && deps && deps[savedDependency])
                    ? savedDependency
                    : (deps && Object.keys(deps).length > 0 ? Object.keys(deps)[0] : null);

                this.widgetControlsForm.dependency = '';
                this.availableDependencies = deps || {};

                this.$nextTick(() => {
                    this.widgetControlsForm.dependency = willSetDependency;
                    this.updateGranularities(ch, savedGranularity);
                });
            });
        },

        updateGranularities(ch, explicitSavedGranularity = null) {
            if (!ch) {
                ch = (this.widgetControlsTarget?.source_type === 'kpi')
                    ? (this.widgetKpiConfig?.dependent_channel || this.widgetControlsForm.channel)
                    : (this.widgetControlsForm.raw_series?.[0]?.channel || '');
            }
            if (!ch || !this.$wire) return;

            const savedGranularity = explicitSavedGranularity || this.widgetControlsForm.granularity;

            this.$wire.getGranularitiesForChannel(ch, this.widgetControlsForm.dependency).then(grans => {
                const willSetGranularity = (savedGranularity && grans && grans[savedGranularity])
                    ? savedGranularity
                    : (grans && Object.keys(grans).length > 0 ? 'daily' : null);

                this.widgetControlsForm.granularity = '';
                this.availableGranularities = grans || {};

                this.$nextTick(() => {
                    if (willSetGranularity) {
                        this.widgetControlsForm.granularity = willSetGranularity;
                    }
                    this.updateSeriesMetrics();
                });
            });
        },

        updateSeriesMetrics() {
            if (!this.$wire) return;
            const gran = this.widgetControlsForm.granularity;
            const dep = this.widgetControlsForm.dependency;
            const mainCh = (this.widgetControlsTarget?.source_type === 'kpi')
                ? (this.widgetKpiConfig?.dependent_channel || this.widgetControlsForm.channel)
                : (this.widgetControlsForm.raw_series?.[0]?.channel || this.widgetControlsForm.channel);

            if (mainCh) {
                this.$wire.getMetricsForChannel(mainCh, gran, dep).then(metrics => {
                    const metricsCopy = { ...this.allChannelMetrics };
                    metricsCopy[mainCh] = metrics;
                    this.allChannelMetrics = metricsCopy;
                });
            }

            (this.widgetControlsForm.raw_series || []).forEach((series, idx) => {
                const ch = series.channel;
                if (ch && ch !== mainCh) {
                    this.$wire.getMetricsForChannel(ch, gran, dep).then(metrics => {
                        const metricsCopy = { ...this.allChannelMetrics };
                        metricsCopy[ch] = metrics;
                        this.allChannelMetrics = metricsCopy;
                    });
                }
            });
        },

        initGridItem(el, widget) {
            const w = parseInt(widget.grid_w || 4, 10);
            const h = parseInt(widget.grid_h || 3, 10);
            const minW = 2;
            const minH = 2;

            el.setAttribute('gs-id', widget.id);
            el.setAttribute('data-id', widget.id);
            el.setAttribute('gs-w', w);
            el.setAttribute('gs-h', h);
            el.setAttribute('gs-min-w', minW);
            el.setAttribute('gs-min-h', minH);

            const hasX = widget.grid_x !== undefined && widget.grid_x !== null;
            const hasY = widget.grid_y !== undefined && widget.grid_y !== null;

            if (hasX && hasY) {
                el.setAttribute('gs-x', widget.grid_x);
                el.setAttribute('gs-y', widget.grid_y);
                el.removeAttribute('gs-auto-position');
            } else {
                el.setAttribute('gs-auto-position', 'true');
                el.removeAttribute('gs-x');
                el.removeAttribute('gs-y');
            }

            if (this.grid) {
                if (el.gridstackNode) {
                    el.gridstackNode.id = widget.id;
                    this.grid.update(el, {
                        id: widget.id,
                        w: w,
                        h: h,
                        minW: minW,
                        minH: minH,
                        ...(hasX && hasY ? { x: widget.grid_x, y: widget.grid_y } : { autoPosition: true })
                    });
                } else {
                    const node = this.grid.makeWidget(el);
                    if (node) node.id = widget.id;
                }
                // Re-scan custom drag handles so the widget is interactive
                // (makeWidget doesn't auto-bind handles set via GridStack.init draggable.handle)
                this.grid.refreshDragHandles(el);
            }

            if (widget._isNew) {
                setTimeout(() => { widget._isNew = false; }, 2500);
            }
        },

        addSeriesCard() {
            this.widgetControlsForm.raw_series = this.widgetControlsForm.raw_series || [];
            this.widgetControlsForm.raw_series.push({
                channel: this.dashboardControls.channel || '',
                metrics: [],
                assets: []
            });
            if (this.dashboardControls.channel) {
                this.onWidgetRawChannelChange(this.widgetControlsForm.raw_series.length - 1);
            }
        },

        removeSeriesCard(index) {
            if (this.widgetControlsForm.raw_series && index >= 0) {
                this.widgetControlsForm.raw_series.splice(index, 1);
            }
        },

        // ─── Grid ──
        initGrid() {
            if (typeof GridStack === 'undefined') {
                setTimeout(() => this.initGrid(), 50);
                return;
            }

            const container = document.getElementById('grid-stack');
            if (!container) return;

            this.grid = GridStack.init({
                column: 12,
                minRow: 6,
                cellHeight: 100,
                margin: 12,
                float: true,
                acceptWidgets: true,
                removable: false,
                resizable: { handles: 'se' },
                draggable: {
                    handle: '.widget-header, .widget-drag-handle, .widget-body-drag',
                    scroll: false
                },
            });

            let autoScrollTimer = null;
            let multiDragStartPositions = null;

            this.grid.on('dragstart', (event, el) => {
                let lastEvt = null;
                const onPointerMove = (e) => { lastEvt = e; };
                window.addEventListener('pointermove', onPointerMove, { passive: true });
                window.addEventListener('mousemove', onPointerMove, { passive: true });

                const primaryNode = el.gridstackNode;
                const rawId = primaryNode ? (primaryNode.id || el.getAttribute('gs-id') || el.getAttribute('data-id')) : 0;
                const primaryId = parseInt(rawId, 10);

                if (primaryId && this.selectedWidgetIds.includes(primaryId) && this.selectedWidgetIds.length > 1) {
                    multiDragStartPositions = {};
                    this.selectedWidgetIds.forEach(id => {
                        const nodeEl = document.querySelector(`[gs-id="${id}"], [data-id="${id}"]`);
                        if (nodeEl && nodeEl.gridstackNode) {
                            multiDragStartPositions[id] = {
                                x: parseInt(nodeEl.gridstackNode.x, 10) || 0,
                                y: parseInt(nodeEl.gridstackNode.y, 10) || 0,
                                el: nodeEl
                            };
                        }
                    });
                } else {
                    multiDragStartPositions = null;
                }

                autoScrollTimer = setInterval(() => {
                    if (!lastEvt) return;
                    const clientY = lastEvt.clientY;
                    const topThreshold = 40;
                    const bottomThreshold = 40;
                    const viewportHeight = window.innerHeight;

                    if (clientY < topThreshold && window.scrollY > 0) {
                        const speed = Math.max(4, Math.round((topThreshold - clientY) / 2));
                        window.scrollBy({ top: -speed, behavior: 'instant' });
                    } else if (clientY > viewportHeight - bottomThreshold) {
                        const speed = Math.max(4, Math.round((clientY - (viewportHeight - bottomThreshold)) / 2));
                        window.scrollBy({ top: speed, behavior: 'instant' });
                    }
                }, 16);

                const syncGroupMove = () => {
                    if (!multiDragStartPositions) return;
                    const node = el.gridstackNode;
                    if (!node) return;
                    const pId = parseInt(node.id || el.getAttribute('gs-id') || el.getAttribute('data-id'), 10);
                    const pStart = multiDragStartPositions[pId];
                    if (!pStart) return;

                    const dx = (parseInt(node.x, 10) || 0) - pStart.x;
                    const dy = (parseInt(node.y, 10) || 0) - pStart.y;

                    Object.keys(multiDragStartPositions).forEach(idKey => {
                        const otherId = String(idKey);
                        if (otherId !== String(pId)) {
                            const start = multiDragStartPositions[idKey];
                            if (start && start.el && this.grid) {
                                const targetX = Math.max(0, start.x + dx);
                                const targetY = Math.max(0, start.y + dy);
                                this.grid.update(start.el, { x: targetX, y: targetY, autoPosition: false });
                            }
                        }
                    });
                };

                this.grid.on('drag', syncGroupMove);

                const cleanup = () => {
                    syncGroupMove();
                    multiDragStartPositions = null;
                    if (autoScrollTimer) {
                        clearInterval(autoScrollTimer);
                        autoScrollTimer = null;
                    }
                    window.removeEventListener('pointermove', onPointerMove);
                    window.removeEventListener('mousemove', onPointerMove);
                    if (this.grid) {
                        this.grid.off('drag', syncGroupMove);
                        this.grid.off('dragstop', cleanup);
                    }
                };

                this.grid.on('dragstop', cleanup);
            });

            this._initialLayoutSignature = JSON.stringify(this.getLayout());

            this.grid.on('change', (event, items) => {
                const currentSignature = JSON.stringify(this.getLayout());
                if (!this._initialLayoutSignature) {
                    this._initialLayoutSignature = currentSignature;
                    return;
                }
                this.isDirty = (currentSignature !== this._initialLayoutSignature);
            });

            setTimeout(() => {
                this._initialLayoutSignature = JSON.stringify(this.getLayout());
                this.isDirty = false;
            }, 500);
        },

        saveLayout() {
            let currentLayout = [];
            try {
                currentLayout = this.getLayout();
            } catch (error) {
                console.error('[dashboard-builder] getLayout() failed:', error);
                currentLayout = this.grid ? (this.grid.save(false) || []) : [];
            }

            if (!this.$wire || typeof this.$wire.saveLayout !== 'function') {
                console.error('[dashboard-builder] Livewire $wire is unavailable; layout was NOT saved.');
                this.notify('danger', 'Layout not saved', 'Livewire bridge unavailable — try reloading the page.');
                return;
            }

            this.$wire.saveLayout(currentLayout)
                .then(() => {
                    this._initialLayoutSignature = JSON.stringify(currentLayout);
                    this.isDirty = false;
                    this.notify('success', 'Layout saved');
                })
                .catch((error) => {
                    console.error('[dashboard-builder] saveLayout() failed:', error);
                    this.isDirty = true;
                    this.notify('danger', 'Save failed', 'There was a problem saving the layout. Please try again.');
                });
        },

        notify(type, title, body = '') {
            if (typeof FilamentNotification !== 'undefined' && FilamentNotification.make) {
                FilamentNotification.make().title(title).body(body)[type]().send();
            } else {
                console[type === 'danger' ? 'error' : 'log'](`[dashboard-builder] ${title} ${body}`);
            }
        },

        getLayout() {
            if (!this.grid) return [];
            const nodes = (this.grid.engine && this.grid.engine.nodes && this.grid.engine.nodes.length > 0)
                ? this.grid.engine.nodes
                : (this.grid.save(false) || []);

            return nodes.map(node => {
                const el = node.el;
                const rawId = node.id || (el ? (el.getAttribute('gs-id') || el.getAttribute('data-id')) : 0);
                return {
                    id: parseInt(rawId, 10) || 0,
                    x: parseInt(node.x, 10) || 0,
                    y: parseInt(node.y, 10) || 0,
                    w: parseInt(node.w, 10) || 4,
                    h: parseInt(node.h, 10) || 3,
                };
            }).filter(node => node.id !== 0)
              .sort((a, b) => a.id - b.id);
        },

        reloadGrid() {
            if (this.grid) {
                this.grid.destroy(false);
                this.grid = null;
            }
            this.$nextTick(() => this.initGrid());
        },

        syncGridWithWidgets() {},

        // ─── Helpers ──
        inheritedControlLabel(key, value) {
            const labels = {
                zero_handling: { remove: 'Remove zeros', keep: 'Keep zeros', trim: 'Trim zeros' },
                granularity: { daily: 'Daily', weekly: 'Weekly', monthly: 'Monthly', query: 'Query', 'dimensions.page': 'Page', country: 'Country', device: 'Device', post: 'Post' },
                max_ratio: 'Cap at {value}',
            };
            if (key === 'max_ratio') {
                return value !== null && value !== undefined ? `Cap at ${value}` : 'No cap';
            }
            return (labels[key] && labels[key][value]) || value || '—';
        },

        widgetHasCustomControls(widget) {
            if (!widget || !widget.controls) return false;
            const c = widget.controls;
            return c && Object.keys(c).length > 0;
        },

        // ─── Dashboard Controls ──
        openDashboardControls() {
            this.showDashboardControls = true;
        },

        confirmDashboardControls() {
            const c = this.dashboardControls;
            let adjustedWidgets = 0;
            let warningTriggered = false;

            this.widgets.forEach(w => {
                let wc = w.controls || {};
                let hasCustomDate = wc.date_start !== undefined || wc.date_end !== undefined;
                if (hasCustomDate) {
                    let changed = false;

                    if (wc.date_start && c.date_start && wc.date_start < c.date_start) {
                        wc.date_start = c.date_start;
                        changed = true;
                    }

                    let dashEnd = c.date_end || config.defaultEndDate || '';
                    if (wc.date_end && wc.date_end > dashEnd) {
                        wc.date_end = dashEnd;
                        changed = true;
                    }

                    if (changed) {
                        w.controls = wc;
                        if (this.$wire) this.$wire.saveWidgetControls(w.id, wc);
                        adjustedWidgets++;
                        warningTriggered = true;
                    }
                }
            });

            if (warningTriggered) {
                alert("Warning: " + adjustedWidgets + " widget(s) did not comply with the new dashboard date range and were automatically adjusted.");
            }

            const payload = {
                date_start: c.date_start || '',
                date_end: c.date_end || '',
                zero_handling: c.zero_handling || 'remove',
                granularity: c.granularity || 'daily',
                edge_case_weighted: c.edge_case_weighted !== undefined ? !!c.edge_case_weighted : true,
                edge_case_grouping: c.edge_case_grouping || 'none',
                asset_group: c.asset_group || '',
                show_asset_group_selector: c.show_asset_group_selector === true,
            };
            if (this.$wire) this.$wire.saveDashboardControls(payload);
            this.showDashboardControls = false;
        },

        parseLocalizedValue(val, locale = (document.documentElement.lang || 'en')) {
            if (!val) return '';
            if (Array.isArray(val)) return '';
            if (typeof val === 'object') {
                return val[locale] || val['en'] || Object.values(val)[0] || '';
            }
            if (typeof val === 'string') {
                const trimmed = val.trim();
                if (trimmed === '[]' || trimmed === '{}') return '';
                if (trimmed.startsWith('{') && trimmed.endsWith('}')) {
                    try {
                        const parsed = JSON.parse(trimmed);
                        if (typeof parsed === 'object' && parsed !== null) {
                            return parsed[locale] || parsed['en'] || Object.values(parsed)[0] || '';
                        }
                    } catch (e) {}
                }
                if (trimmed.startsWith('[') && trimmed.endsWith(']')) {
                    try {
                        const parsed = JSON.parse(trimmed);
                        if (Array.isArray(parsed)) return '';
                    } catch (e) {}
                }
            }
            return String(val);
        },

        // ─── Widget Controls ──
        widgetControlsError: '',
        activeIdentityLang: 'en',
        openWidgetControls(widget) {
            console.log('[dashboard-builder] openWidgetControls called with widget:', widget);
            this.widgetControlsError = '';
            this.activeIdentityLang = document.documentElement.lang || 'en';
            const wc = widget.controls || {};

            if (widget.source_type === 'derived_metric' && widget.source_config?.derived_metric_id) {
                const dmId = widget.source_config.derived_metric_id;
                const dm = (this.derivedMetrics || {})[dmId] || (this.derivedMetrics || {})[String(dmId)];
                widget.dmSourceSeries = dm ? (dm.source_series || []) : [];
            }

            this.widgetControlsTarget = widget;

            const hasDate = wc.date_start !== undefined || wc.date_end !== undefined;
            const hasZero = wc.zero_handling !== undefined;

            let titles = { en: '', es: '' };
            let descriptions = { en: '', es: '' };

            if (widget.titles && typeof widget.titles === 'object' && !Array.isArray(widget.titles)) {
                titles = { ...widget.titles };
            } else if (widget.title) {
                if (typeof widget.title === 'object' && !Array.isArray(widget.title)) {
                    titles = { ...widget.title };
                } else if (typeof widget.title === 'string' && widget.title.trim().startsWith('{')) {
                    try { 
                        const parsed = JSON.parse(widget.title);
                        if (typeof parsed === 'object' && parsed !== null && !Array.isArray(parsed)) titles = parsed;
                    } catch (e) {}
                }
            }

            if (widget.descriptions && typeof widget.descriptions === 'object' && !Array.isArray(widget.descriptions)) {
                descriptions = { ...widget.descriptions };
            } else if (widget.description) {
                if (typeof widget.description === 'object' && !Array.isArray(widget.description)) {
                    descriptions = { ...widget.description };
                } else if (typeof widget.description === 'string' && widget.description.trim().startsWith('{')) {
                    try { 
                        const parsed = JSON.parse(widget.description);
                        if (typeof parsed === 'object' && parsed !== null && !Array.isArray(parsed)) descriptions = parsed;
                    } catch (e) {}
                }
            }

            const cleanStr = (s) => (s && s !== '[]' && s !== '{}' && !s.startsWith('[')) ? String(s) : '';

            Object.keys(titles).forEach(k => { titles[k] = cleanStr(titles[k]); });
            Object.keys(descriptions).forEach(k => { descriptions[k] = cleanStr(descriptions[k]); });

            const baseTitle = this.parseLocalizedValue(widget.title || widget.name || '');
            const baseDesc = this.parseLocalizedValue(widget.description || '');

            if (!titles.en && !titles.es) {
                titles.en = baseTitle;
                titles.es = baseTitle;
            } else {
                if (!titles.en) titles.en = titles.es || baseTitle;
                if (!titles.es) titles.es = titles.en || baseTitle;
            }

            if (!descriptions.en && !descriptions.es) {
                descriptions.en = baseDesc;
                descriptions.es = baseDesc;
            } else {
                if (!descriptions.en) descriptions.en = descriptions.es || baseDesc;
                if (!descriptions.es) descriptions.es = descriptions.en || baseDesc;
            }

            this.widgetControlsForm = {
                titles: titles,
                descriptions: descriptions,
                title: titles[this.activeIdentityLang] || titles.en || baseTitle,
                description: descriptions[this.activeIdentityLang] || descriptions.en || baseDesc,
                widget_type: widget.widget_type || '',
                date_inherit: wc.date_start === undefined && wc.date_end === undefined,
                date_start: wc.date_start || this.dashboardControls.date_start || '',
                date_end: wc.date_end || this.dashboardControls.date_end || '',
                zero_inherit: wc.zero_handling === undefined,
                zero_handling: wc.zero_handling || this.dashboardControls.zero_handling || 'remove',
                granularity_inherit: wc.granularity === undefined,
                granularity: wc.granularity || this.dashboardControls.granularity || 'daily',
                dependency: wc.dependency || null,
                channel: wc.channel || '',
                assets: wc.assets || [],
                metrics: wc.metrics || [],
                series_assets: wc.series_assets || {},
                series_asset_groups: wc.series_asset_groups || {},
                edge_case_inherit: wc.edge_case_weighted === undefined && wc.edge_case_grouping === undefined,
                edge_case_weighted: wc.edge_case_weighted !== undefined ? wc.edge_case_weighted : (this.dashboardControls.edge_case_weighted ?? true),
                edge_case_grouping: wc.edge_case_grouping !== undefined ? wc.edge_case_grouping : (this.dashboardControls.edge_case_grouping || 'none'),
                max_ratio_inherit: wc.max_ratio === undefined,
                max_ratio: wc.max_ratio !== undefined ? wc.max_ratio : null,
                block_first_col: wc.block_first_col !== undefined ? !!wc.block_first_col : true,
                raw_series: [],
                dm_assets: wc.dm_assets || {},
            };

            if (widget.source_type !== 'kpi') {
                if (wc.raw_series && Array.isArray(wc.raw_series) && wc.raw_series.length > 0) {
                    this.widgetControlsForm.raw_series = wc.raw_series.map(s => ({
                        channel: s.channel || '',
                        metrics: Array.isArray(s.metrics) ? [...s.metrics] : [],
                        assets: Array.isArray(s.assets) ? [...s.assets] : []
                    }));
                } else if (wc.series_channels && Object.keys(wc.series_channels).length > 0) {
                    const seriesKeys = Object.keys(wc.series_channels);
                    const groupedSeries = [];
                    seriesKeys.forEach((key) => {
                        const channel = wc.series_channels[key] || wc.channel || '';
                        const assets = (wc.series_assets && wc.series_assets[key]) ? [...wc.series_assets[key]] : (wc.assets ? [...wc.assets] : []);
                        const metrics = (wc.metrics && Array.isArray(wc.metrics)) ? (wc.metrics[key] ? [wc.metrics[key]] : wc.metrics) : [];
                        groupedSeries.push({ channel, metrics, assets });
                    });
                    this.widgetControlsForm.raw_series = groupedSeries;
                } else if (wc.metrics && wc.metrics.length > 0) {
                    const groupedSeries = [];
                    wc.metrics.forEach((m, i) => {
                        const channel = (wc.series_channels && wc.series_channels[i]) ? wc.series_channels[i] : (wc.channel || '');
                        const assets = (wc.series_assets && wc.series_assets[i]) ? [...wc.series_assets[i]] : (wc.assets ? [...wc.assets] : []);

                        const existing = groupedSeries.find(s => s.channel === channel && JSON.stringify(s.assets) === JSON.stringify(assets));
                        if (existing) {
                            if (m && !existing.metrics.includes(m)) existing.metrics.push(m);
                        } else {
                            groupedSeries.push({ channel, metrics: m ? [m] : [], assets });
                        }
                    });
                    this.widgetControlsForm.raw_series = groupedSeries;
                }
                if (this.widgetControlsForm.raw_series.length === 0) {
                    this.widgetControlsForm.raw_series.push({ channel: wc.channel || '', metrics: [], assets: wc.assets || [] });
                }

                if (this.$wire) {
                    this.widgetControlsForm.raw_series.forEach((series, idx) => {
                        const ch = series.channel;
                        if (ch && !this.allChannelAssets[ch]) {
                            this.$wire.getAssetsForChannel(ch).then(assets => { this.allChannelAssets = { ...this.allChannelAssets, [ch]: assets }; });
                        }
                        if (ch && !this.allChannelAssetGroups[ch]) {
                            this.$wire.getAssetGroupsForChannel(ch).then(groups => { this.allChannelAssetGroups = { ...this.allChannelAssetGroups, [ch]: groups }; });
                        }
                        if (ch && !this.allChannelMetrics[ch]) {
                            this.$wire.getMetricsForChannel(ch).then(metrics => {
                                this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                            });
                        }
                    });
                }

                this.widgetControlsForm.raw_series.forEach(series => {
                    if (series.channel && series.assets && series.assets.length > 0) {
                        series.assets = this.ensureValidAssets(null, series.channel, series.assets);
                    }
                });
            }

            const savedMetrics = wc.metrics || [];

            this.widgetKpiConfig = {};
            if (widget.source_type === 'kpi' && widget.source_config && widget.source_config.custom_kpi_id && this.$wire) {
                this.$wire.getKpiConfiguration(widget.source_config.custom_kpi_id).then(config => {
                    console.log('DEBUG BUILDER getKpiConfiguration result:', config);
                    // Extract the actual UI state from the KPI filters
                    const uiState = config?.filters?._ui_state || config;
                    this.widgetKpiConfig = {
                        dependent_channel: uiState.dependent_channel,
                        dependent_metric: uiState.dependent_metric,
                        dependent_dm_id: uiState.dependent_dm_id,
                        dependent_asset_group: uiState.dependent_asset_group,
                        dependent_asset_filter: uiState.dependent_asset_filter,
                        independent_variables: uiState.independent_variables || {},
                    };
                    console.log('DEBUG BUILDER widgetKpiConfig:', this.widgetKpiConfig);
                    console.log('DEBUG BUILDER dependentDm:', this.derivedMetrics?.[this.widgetKpiConfig.dependent_dm_id], 'allDMs:', Object.keys(this.derivedMetrics || {}));
                    if (this.widgetControlsForm.date_inherit) {
                        this.widgetControlsForm.date_start = config?.start_date || this.dashboardControls.date_start || '';
                        this.widgetControlsForm.date_end = config?.end_date || this.dashboardControls.date_end || '';
                    }
                    if (this.widgetControlsForm.edge_case_inherit) {
                        this.widgetControlsForm.edge_case_weighted = config?.edge_case_weighted !== undefined ? config.edge_case_weighted : (this.dashboardControls.edge_case_weighted !== undefined ? !!this.dashboardControls.edge_case_weighted : true);
                        this.widgetControlsForm.edge_case_grouping = config?.edge_case_grouping || this.dashboardControls.edge_case_grouping || 'none';
                    }
                    if (this.widgetControlsForm.zero_inherit) {
                        this.widgetControlsForm.zero_handling = config?.zero_handling ?? this.dashboardControls.zero_handling ?? 'remove';
                    }
                    if (this.widgetControlsForm.granularity_inherit) {
                        this.widgetControlsForm.granularity = config?.granularity ?? this.dashboardControls.granularity ?? 'daily';
                    }
                    if (this.widgetControlsForm.max_ratio_inherit) {
                        this.widgetControlsForm.max_ratio = config?.max_ratio !== undefined ? config.max_ratio : null;
                    }

                    if (!this.widgetControlsForm.series_assets.dependent) this.widgetControlsForm.series_assets.dependent = [];
                    if (!this.widgetControlsForm.series_asset_groups.dependent) this.widgetControlsForm.series_asset_groups.dependent = '';
                    if (this.widgetKpiConfig.independent_variables) {
                        for (let key in this.widgetKpiConfig.independent_variables) {
                            if (!this.widgetControlsForm.series_assets['independent_' + key]) {
                                this.widgetControlsForm.series_assets['independent_' + key] = [];
                            }
                            if (!this.widgetControlsForm.series_asset_groups['independent_' + key]) {
                                this.widgetControlsForm.series_asset_groups['independent_' + key] = '';
                            }
                        }
                    }

                    const initDmKpiAssets = (prefix, dmId) => {
                        const dm = this.derivedMetrics?.[dmId];
                        if (dm && dm.source_series) {
                            dm.source_series.forEach((_, sIdx) => {
                                const k = prefix + '_dm_' + sIdx;
                                if (!this.widgetControlsForm.series_assets[k]) {
                                    this.widgetControlsForm.series_assets[k] = [];
                                }
                            });
                        }
                    };
                    if (this.widgetKpiConfig.dependent_dm_id) {
                        initDmKpiAssets('dep', this.widgetKpiConfig.dependent_dm_id);
                        // Populate dependent_channel from DM's source_series if not already set
                        const depDm = this.derivedMetrics?.[this.widgetKpiConfig.dependent_dm_id];
                        if (depDm && depDm.source_series && depDm.source_series.length > 0 && !this.widgetKpiConfig.dependent_channel) {
                            this.widgetKpiConfig.dependent_channel = depDm.source_series[0].channel;
                        }
                    }
                    if (this.widgetKpiConfig.independent_variables) {
                        for (let key in this.widgetKpiConfig.independent_variables) {
                            const v = this.widgetKpiConfig.independent_variables[key];
                            if (v.independent_dm_id) {
                                initDmKpiAssets('ind_' + key, v.independent_dm_id);
                            }
                        }
                    }

                    const channelsToLoad = new Set();
                    if (this.widgetKpiConfig.dependent_channel) channelsToLoad.add(this.widgetKpiConfig.dependent_channel);
                    if (this.widgetKpiConfig.independent_variables) {
                        for (let key in this.widgetKpiConfig.independent_variables) {
                            if (this.widgetKpiConfig.independent_variables[key].independent_channel) {
                                channelsToLoad.add(this.widgetKpiConfig.independent_variables[key].independent_channel);
                            }
                        }
                    }
                    if (this.widgetKpiConfig.dependent_dm_id) {
                        const depDm = this.derivedMetrics?.[this.widgetKpiConfig.dependent_dm_id];
                        if (depDm && depDm.source_series) {
                            depDm.source_series.forEach(s => { if (s.channel) channelsToLoad.add(s.channel); });
                        }
                    }
                    if (this.widgetKpiConfig.independent_variables) {
                        for (let key in this.widgetKpiConfig.independent_variables) {
                            const v = this.widgetKpiConfig.independent_variables[key];
                            if (v.independent_dm_id) {
                                const indDm = this.derivedMetrics?.[v.independent_dm_id];
                                if (indDm && indDm.source_series) {
                                    indDm.source_series.forEach(s => { if (s.channel) channelsToLoad.add(s.channel); });
                                }
                            }
                        }
                    }
                    channelsToLoad.forEach(ch => {
                        if (!this.allChannelAssets[ch]) {
                            this.$wire.getAssetsForChannel(ch).then(assets => {
                                this.allChannelAssets = { ...this.allChannelAssets, [ch]: assets };
                            });
                        }
                        if (!this.allChannelAssetGroups[ch]) {
                            this.$wire.getAssetGroupsForChannel(ch).then(groups => {
                                this.allChannelAssetGroups = { ...this.allChannelAssetGroups, [ch]: groups };
                            });
                        }
                        if (!this.allChannelMetrics[ch]) {
                            this.$wire.getMetricsForChannel(ch).then(metrics => {
                                this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                                // Auto-select first metric for KPI dependent series if dynamic and none selected
                                if (this.widgetKpiConfig.dependent_channel === ch && !this.widgetKpiConfig.dependent_metric && metrics && Object.keys(metrics).length > 0) {
                                    const firstMetric = Object.keys(metrics)[0];
                                    this.widgetControlsForm.metrics[0] = firstMetric;
                                }
                                // Auto-select for independent variables
                                if (this.widgetKpiConfig.independent_variables) {
                                    for (let key in this.widgetKpiConfig.independent_variables) {
                                        const v = this.widgetKpiConfig.independent_variables[key];
                                        if (v.independent_channel === ch && !v.independent_metric && metrics && Object.keys(metrics).length > 0) {
                                            const firstMetric = Object.keys(metrics)[0];
                                            this.widgetControlsForm.metrics[parseInt(key) + 1] = firstMetric;
                                        }
                                    }
                                }
                            });
                        }
                    });

                    const allSeriesKeys = ['dependent'];
                    if (this.widgetKpiConfig.independent_variables) {
                        for (let key in this.widgetKpiConfig.independent_variables) {
                            allSeriesKeys.push('independent_' + key);
                        }
                    }
                    allSeriesKeys.forEach(sk => {
                        const ch = sk === 'dependent'
                            ? this.widgetKpiConfig.dependent_channel
                            : this.widgetKpiConfig.independent_variables?.[sk.replace('independent_', '')]?.independent_channel;
                        if (ch && this.widgetControlsForm.series_assets[sk] && this.widgetControlsForm.series_assets[sk].length > 0) {
                            this.widgetControlsForm.series_assets[sk] = this.ensureValidAssets(sk, ch, this.widgetControlsForm.series_assets[sk]);
                        }
                    });

                    this.loadWidgetMetrics(savedMetrics);
                    this.updateDependenciesAndGranularities(wc.dependency, wc.granularity);
                    this.showWidgetControls = true;
                }).catch(err => {
                    console.error('[dashboard-builder] getKpiConfiguration failed:', err);
                    this.loadWidgetMetrics(savedMetrics);
                    this.updateDependenciesAndGranularities(wc.dependency, wc.granularity);
                    this.showWidgetControls = true;
                });
            } else {
                this.loadWidgetMetrics(savedMetrics);
                this.updateDependenciesAndGranularities(wc.dependency, wc.granularity);
                this.showWidgetControls = true;
            }

            if (widget.source_type === 'derived_metric' && widget.source_config?.derived_metric_id && this.$wire) {
                const dm = this.derivedMetrics[widget.source_config.derived_metric_id];
                if (dm) {
                    widget.dmSourceSeries = dm.source_series || [];
                    widget.dmSourceSeries.forEach((series, idx) => {
                        const ch = series.channel;
                        if (ch && !this.allChannelAssets[ch]) {
                            this.$wire.getAssetsForChannel(ch).then(assets => {
                                this.allChannelAssets = { ...this.allChannelAssets, [ch]: assets };
                            });
                        }
                        if (ch && !this.allChannelAssetGroups[ch]) {
                            this.$wire.getAssetGroupsForChannel(ch).then(groups => {
                                this.allChannelAssetGroups = { ...this.allChannelAssetGroups, [ch]: groups };
                            });
                        }
                    });
                }
            }

            this.updateDependenciesAndGranularities(wc.dependency, wc.granularity);
            this.showWidgetControls = true;
        },

        loadWidgetMetrics(savedMetrics) {
            const ch = this.widgetControlsForm.channel || this.dashboardControls.channel;
            if (this.allChannelMetrics[ch]) {
                this.widgetAssets = this.allChannelAssets[ch] || {};
                this.widgetMetrics = this.allChannelMetrics[ch] || {};
                this.restoreWidgetMetrics(savedMetrics);
            } else if (ch && this.$wire) {
                this.$wire.getAssetsForChannel(ch).then(assets => {
                    this.allChannelAssets = { ...this.allChannelAssets, [ch]: assets };
                    this.widgetAssets = assets;
                });
                this.$wire.getAssetGroupsForChannel(ch).then(groups => {
                    this.allChannelAssetGroups = { ...this.allChannelAssetGroups, [ch]: groups };
                });
                this.$wire.getMetricsForChannel(ch).then(metrics => {
                    this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                    this.widgetMetrics = metrics;
                    this.restoreWidgetMetrics(savedMetrics);
                });
            } else {
                this.widgetAssets = {};
                this.widgetMetrics = {};
            }
        },

        restoreWidgetMetrics(savedMetrics) {
            if (savedMetrics.length > 0) {
                this.$nextTick(() => {
                    this.widgetControlsForm.metrics = [...savedMetrics];
                });
            }
        },

        onWidgetChannelChange() {
            const ch = this.widgetControlsForm.channel || this.dashboardControls.channel;
            if (this.allChannelAssets[ch]) {
                this.widgetAssets = this.allChannelAssets[ch];
                this.widgetMetrics = this.allChannelMetrics[ch] || {};
            } else if (ch && this.$wire) {
                this.$wire.getAssetsForChannel(ch).then(assets => {
                    this.allChannelAssets = { ...this.allChannelAssets, [ch]: assets };
                    this.widgetAssets = assets;
                });
                this.$wire.getAssetGroupsForChannel(ch).then(groups => {
                    this.allChannelAssetGroups = { ...this.allChannelAssetGroups, [ch]: groups };
                });
                this.$wire.getMetricsForChannel(ch).then(metrics => {
                    this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                    this.widgetMetrics = metrics;
                });
            } else {
                this.widgetAssets = {};
                this.widgetMetrics = {};
            }
        },

        onWidgetRawChannelChange(index) {
            const ch = this.widgetControlsForm.raw_series[index].channel;

            if (index === 0) {
                this.widgetControlsForm.channel = ch;
                this.updateDependenciesAndGranularities();
            }

            if (ch && !this.allChannelAssets[ch] && this.$wire) {
                this.$wire.getAssetsForChannel(ch).then(assets => {
                    this.allChannelAssets = { ...this.allChannelAssets, [ch]: assets };
                });
            }
            if (ch && !this.allChannelAssetGroups[ch] && this.$wire) {
                this.$wire.getAssetGroupsForChannel(ch).then(groups => {
                    this.allChannelAssetGroups = { ...this.allChannelAssetGroups, [ch]: groups };
                });
            }
            if (ch && !this.allChannelMetrics[ch] && this.$wire) {
                this.$wire.getMetricsForChannel(ch).then(metrics => {
                    this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                });
            }
        },

        toggleRawAsset(index, id) {
            const current = this.widgetControlsForm.raw_series[index].assets || [];
            const strId = String(id);
            if (current.includes(strId)) {
                if (current.length <= 1) return;
                this.widgetControlsForm.raw_series[index].assets = current.filter(a => a !== strId);
            } else {
                this.widgetControlsForm.raw_series[index].assets = [...current, strId];
            }
        },

        selectAllRawAssets(index) {
            const ch = this.widgetControlsForm.raw_series[index].channel;
            const assets = this.allChannelAssets[ch] || {};
            let validIds = Object.keys(assets).map(String);
            const globalGroup = this.dashboardControls?.asset_group;
            if (globalGroup && this.allChannelAssetGroups[ch]?.[globalGroup]) {
                const groupAssets = this.allChannelAssetGroups[ch][globalGroup].assets.map(String);
                validIds = validIds.filter(id => groupAssets.includes(id));
            }
            this.widgetControlsForm.raw_series[index].assets = validIds;
        },

        selectAllKpiAssets(seriesKey, channel, kpiGroup = null) {
            const assets = this.allChannelAssets[channel] || {};
            let validIds = Object.keys(assets).map(String);
            if (kpiGroup && this.allChannelAssetGroups[channel] && this.allChannelAssetGroups[channel][kpiGroup]) {
                const groupAssets = this.allChannelAssetGroups[channel][kpiGroup].assets.map(String);
                validIds = validIds.filter(id => groupAssets.includes(id));
            }
            const widgetGroup = this.widgetControlsForm?.series_asset_groups?.[seriesKey];
            if (!widgetGroup && !kpiGroup) {
                const globalGroup = this.dashboardControls?.asset_group;
                if (globalGroup && this.allChannelAssetGroups[channel]?.[globalGroup]) {
                    const groupAssets = this.allChannelAssetGroups[channel][globalGroup].assets.map(String);
                    validIds = validIds.filter(id => groupAssets.includes(id));
                }
            }
            this.widgetControlsForm.series_assets[seriesKey] = validIds;
        },

        toggleKpiAsset(seriesKey, id) {
            const current = this.widgetControlsForm.series_assets[seriesKey] || [];
            const strId = String(id);

            if (this.kpiSeriesAssetMode === 'single') {
                this.widgetControlsForm.series_assets[seriesKey] = [strId];
                return;
            }

            if (current.includes(strId)) {
                if (current.length <= 1) return;
                this.widgetControlsForm.series_assets[seriesKey] = current.filter(a => a !== strId);
            } else {
                this.widgetControlsForm.series_assets[seriesKey] = [...current, strId];
            }
        },

        toggleDmAsset(index, id) {
            const current = this.widgetControlsForm.dm_assets[index] || [];
            const strId = String(id);
            if (current.includes(strId)) {
                this.widgetControlsForm.dm_assets[index] = current.filter(a => a !== strId);
            } else {
                this.widgetControlsForm.dm_assets[index] = [...current, strId];
            }
        },

        selectAllDmAssets(index) {
            const series = this.widgetControlsTarget.dmSourceSeries[index];
            const ch = series.channel;
            const assets = this.allChannelAssets[ch] || {};
            let validIds = Object.keys(assets).map(String);
            const globalGroup = this.dashboardControls?.asset_group;
            if (globalGroup && this.allChannelAssetGroups[ch]?.[globalGroup]) {
                const groupAssets = this.allChannelAssetGroups[ch][globalGroup].assets.map(String);
                validIds = validIds.filter(id => groupAssets.includes(id));
            }
            this.widgetControlsForm.dm_assets[index] = validIds;
        },

        clearAllRawAssets(index) {
            this.widgetControlsForm.raw_series[index].assets = [];
        },

        clearAllDmAssets(index) {
            this.widgetControlsForm.dm_assets[index] = [];
        },

        clearAllKpiAssets(seriesKey) {
            this.widgetControlsForm.series_assets[seriesKey] = [];
        },

        // ─── Asset Group Helpers ───
        getEffectiveGroup(seriesKey, channel) {
            const widgetGroup = this.widgetControlsForm?.series_asset_groups?.[seriesKey];
            if (widgetGroup) return widgetGroup;

            if (seriesKey === 'dependent' && this.widgetKpiConfig?.dependent_asset_group) {
                return this.widgetKpiConfig.dependent_asset_group;
            }
            if (seriesKey && seriesKey.startsWith('independent_')) {
                const idx = seriesKey.replace('independent_', '');
                const kpiGroup = this.widgetKpiConfig?.independent_variables?.[idx]?.independent_asset_group;
                if (kpiGroup) return kpiGroup;
            }

            if (this.dashboardControls?.asset_group) {
                return this.dashboardControls.asset_group;
            }

            return '';
        },

        isAssetAllowedByGroups(seriesKey, channel, assetId) {
            const groupId = this.getEffectiveGroup(seriesKey, channel);
            if (!groupId) return true;

            const groupData = this.allChannelAssetGroups[channel]?.[groupId];
            if (!groupData) return true;

            return (groupData.assets || []).map(String).includes(String(assetId));
        },

        ensureValidAssets(seriesKey, channel, selectedAssets) {
            const groupId = this.getEffectiveGroup(seriesKey, channel);
            if (!groupId) return selectedAssets;

            const groupData = this.allChannelAssetGroups[channel]?.[groupId];
            if (!groupData) return selectedAssets;

            const allowedAssets = groupData.assets.map(String);
            const validAssets = (selectedAssets || []).filter(a => allowedAssets.includes(String(a)));

            if (validAssets.length > 0) return validAssets;

            if (allowedAssets.length > 0) return [allowedAssets[0]];
            return [];
        },

        resetWidgetControls() {
            this.widgetControlsForm = {
                date_inherit: true,
                date_start: '',
                date_end: '',
                zero_inherit: true,
                zero_handling: 'remove',
                channel: '',
                assets: [],
                granularity: 'daily',
                dependency: null,
                metrics: [],
                series_assets: {},
                series_asset_groups: {},
                edge_case_inherit: true,
                edge_case_weighted: true,
                edge_case_grouping: 'none',
                max_ratio_inherit: true,
                max_ratio: null,
                widget_type: this.widgetControlsTarget?.widget_type || '',
            };
        },

        confirmWidgetControls() {
            const c = this.widgetControlsForm;

            let cDash = this.dashboardControls;
            let dateAdjusted = false;
            if (!c.date_inherit) {
                if (c.date_start && cDash.date_start && c.date_start < cDash.date_start) {
                    c.date_start = cDash.date_start;
                    dateAdjusted = true;
                }
                let maxEnd = cDash.date_end || config.defaultEndDate || '';
                if (c.date_end && c.date_end > maxEnd) {
                    c.date_end = maxEnd;
                    dateAdjusted = true;
                }
            }
            if (dateAdjusted) {
                alert("Warning: The widget date range exceeded the dashboard limits and was adjusted to comply.");
            }

            const payload = {};

            if (!c.title || c.title.trim() === '') {
                this.widgetControlsError = "Please enter a title for the widget.";
                return;
            }

            if (!c.date_inherit) {
                payload.date_start = c.date_start;
                payload.date_end = c.date_end;
            }
            if (!c.zero_inherit) {
                payload.zero_handling = c.zero_handling;
            }

            if (!c.granularity_inherit) {
                payload.granularity = c.granularity;
            }
            if (c.dependency) {
                payload.dependency = c.dependency;
            }

            if (!c.edge_case_inherit) {
                payload.edge_case_weighted = !!c.edge_case_weighted;
                payload.edge_case_grouping = c.edge_case_grouping || 'none';
            }

            if (!c.max_ratio_inherit) {
                payload.max_ratio = c.max_ratio !== '' && c.max_ratio !== null ? parseFloat(c.max_ratio) : null;
            }

            payload.block_first_col = c.block_first_col !== undefined ? !!c.block_first_col : true;

            this.widgetControlsError = '';
            if (this.widgetControlsTarget.source_type === 'derived_metric') {
                payload.dm_assets = c.dm_assets || {};
            } else if (this.widgetControlsTarget.source_type !== 'kpi') {
                if (!c.raw_series || c.raw_series.length === 0) {
                    this.widgetControlsError = "Please add at least one series before saving.";
                    return;
                }

                const missingChannel = c.raw_series.some(s => !s.channel || s.channel.trim() === '');
                if (missingChannel) {
                    this.widgetControlsError = "Please select a channel for all series before saving.";
                    return;
                }

                payload.channel = '';
                payload.assets = [];
                payload.metrics = [];
                payload.series_assets = {};
                payload.series_channels = {};

                c.raw_series.forEach((s, sIdx) => {
                    const metricsToSave = (Array.isArray(s.metrics) && s.metrics.length > 0) ? s.metrics : [''];

                    metricsToSave.forEach(m => {
                        payload.metrics.push(m);
                    });

                    let channelAssets = this.allChannelAssets[s.channel] || {};
                    let validAssets = [...(s.assets || [])];
                    if (Object.keys(channelAssets).length > 0) {
                        validAssets = validAssets.filter(id => channelAssets[id] !== undefined || channelAssets[String(id)] !== undefined);
                    }

                    payload.series_assets[sIdx] = validAssets;
                    payload.series_channels[sIdx] = s.channel || '';
                });
                if (payload.series_channels['0']) {
                    payload.channel = payload.series_channels['0'];
                }
            } else {
                payload.channel = c.channel;
                payload.assets = c.assets;
                payload.metrics = c.metrics;
                payload.series_assets = c.series_assets;
                payload.series_asset_groups = c.series_asset_groups;
            }

            const titles = c.titles || {};
            const descriptions = c.descriptions || {};
            const activeLang = this.activeIdentityLang || document.documentElement.lang || 'en';
            const fallbackTitle = (titles[activeLang] || titles.en || titles.es || c.title || '').trim();
            const fallbackDesc = (descriptions[activeLang] || descriptions.en || descriptions.es || c.description || '').trim();

            if (this.$wire) {
                this.$wire.saveWidgetControls(
                    this.widgetControlsTarget.id,
                    payload,
                    fallbackTitle,
                    fallbackDesc || null,
                    titles,
                    descriptions
                );
            }

            const newType = c.widget_type;
            const oldType = this.widgetControlsTarget.widget_type;
            if (newType && newType !== oldType && this.$wire) {
                this.$wire.changeWidgetType(this.widgetControlsTarget.id, newType);
            }

            this.showWidgetControls = false;
            this.reloadGrid();

            const idx = this.widgets.findIndex(w => w.id === this.widgetControlsTarget.id);
            if (idx !== -1) {
                this.widgets[idx].controls = payload;
                this.widgets[idx].titles = titles;
                this.widgets[idx].descriptions = descriptions;
                this.widgets[idx].title = fallbackTitle;
                this.widgets[idx].description = fallbackDesc;
                this.widgets[idx].widget_type = c.widget_type;
            }
        },

        // ─── Add Widget ──
        openAddWidgetModal() {
            this.addWidgetForm = { source_type: '', custom_kpi_id: '', derived_metric_id: '', widget_type: '', name: '' };
            this.showAddWidgetModal = true;
        },

        canAddWidget() {
            if (!this.addWidgetForm.source_type) return false;
            if (!this.addWidgetForm.widget_type) return false;
            if (this.addWidgetForm.source_type === 'kpi' && !this.addWidgetForm.custom_kpi_id) return false;
            if (this.addWidgetForm.source_type === 'derived_metric' && !this.addWidgetForm.derived_metric_id) return false;
            return true;
        },

        confirmAddWidget() {
            if (!this.canAddWidget() || !this.$wire) return;

            const form = this.addWidgetForm;
            const data = {
                name: form.name || form.widget_type,
                title: form.name || form.widget_type,
                source_type: form.source_type,
                custom_kpi_id: form.source_type === 'kpi' ? form.custom_kpi_id : null,
                derived_metric_id: form.source_type === 'derived_metric' ? form.derived_metric_id : null,
                source_config: form.source_type === 'kpi'
                    ? { custom_kpi_id: form.custom_kpi_id }
                    : form.source_type === 'derived_metric'
                        ? { derived_metric_id: form.derived_metric_id }
                        : {},
                widget_type: form.widget_type,
                controls: {},
                grid_x: null,
                grid_y: null,
                grid_w: 4,
                grid_h: 3,
            };

            this.$wire.addWidget(data).then(widget => {
                widget._isNew = true;
                widget.grid_x = null;
                widget.grid_y = null;
                this.widgets.push(widget);
                this.showAddWidgetModal = false;

                this.$nextTick(() => {
                    setTimeout(() => {
                        if (this.$wire) this.$wire.saveLayout(this.getLayout());
                        const el = document.querySelector(`[gs-id="${widget.id}"]`);
                        if (el) {
                            el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    }, 500);
                });
            });
        },

        // ─── Share ──
        openShareDialog() {
            if (!this.$wire) return;
            this.$wire.getProjectCollaborators().then(users => {
                this.collaborators = users || [];
            });
            this.$wire.getSharedUserIds().then(ids => {
                this.sharedUsers = this.collaborators.filter(u => (ids || []).includes(u.id));
            });
            this.showShareDialog = true;
        },

        isShared(userId) {
            return this.sharedUsers.some(u => u.id === userId);
        },

        addSharedUser() {
            const userId = parseInt(this.shareUserId);
            if (!userId || !this.$wire) return;
            const user = this.collaborators.find(u => u.id === userId);
            if (!user) return;

            this.$wire.shareWithUser(userId).then(() => {
                this.sharedUsers.push(user);
                this.shareUserId = '';
            });
        },

        unshareUser(userId) {
            if (!this.$wire) return;
            this.$wire.unshareUser(userId).then(() => {
                this.sharedUsers = this.sharedUsers.filter(u => u.id !== userId);
            });
        },

        togglePublic() {
            if (!this.$wire) return;
            this.$wire.togglePublic().then(() => {
                this.isPublic = !this.isPublic;
            });
        },

        configureWidget(id) {
            console.log('[dashboard-builder] configureWidget called for id:', id, 'type:', typeof id);
            const widget = this.widgets.find(w => String(w.id) === String(id));
            console.log('[dashboard-builder] configureWidget found widget:', widget);
            if (widget) {
                this.openWidgetControls(widget);
            } else {
                console.error('[dashboard-builder] configureWidget failed to find widget with id:', id, 'among available widgets:', this.widgets.map(w => w.id));
            }
        },

        deleteWidget(id) {
            console.log('[dashboard-builder] deleteWidget requested for id:', id);
            if (confirm('Remove this widget?')) {
                if (this.$wire) {
                    this.$wire.deleteWidget(id).then(() => {
                        console.log('[dashboard-builder] deleteWidget backend confirmed for id:', id);
                        const el = document.querySelector(`[gs-id="${id}"]`) || document.querySelector(`[data-id="${id}"]`);
                        console.log('[dashboard-builder] deleteWidget DOM element found:', el);
                        if (el && this.grid) {
                            this.grid.removeWidget(el, true);
                        }
                        this.widgets = this.widgets.filter(w => String(w.id) !== String(id));
                        console.log('[dashboard-builder] deleteWidget remaining widgets:', this.widgets.map(w => w.id));
                    }).catch(err => {
                        console.error('[dashboard-builder] deleteWidget wire call error:', err);
                    });
                }
            }
        },

        duplicateWidget(id) {
            console.log('[dashboard-builder] duplicateWidget called for id:', id);
            if (!this.$wire) {
                console.error('[dashboard-builder] duplicateWidget: $wire instance missing');
                return;
            }
            this.$wire.duplicateWidget(id).then(rawWidget => {
                console.log('[dashboard-builder] duplicateWidget backend returned widget:', rawWidget);
                const widget = JSON.parse(JSON.stringify(rawWidget));
                widget._isNew = true;
                this.widgets.push(widget);
                console.log('[dashboard-builder] updated widgets array length:', this.widgets.length);

                // initGridItem (called via x-init on the new element) handles
                // makeWidget + refreshDragHandles. We just need to wait for that
                // to finish, then save layout and scroll into view.
                this.$nextTick(() => {
                    setTimeout(() => {
                        const el = document.querySelector(`[data-id="${widget.id}"]`) || document.querySelector(`[gs-id="${widget.id}"]`);
                        console.log('[dashboard-builder] duplicateWidget post-render DOM element:', el, 'gridstackNode:', el ? el.gridstackNode : null);
                        if (this.$wire) this.$wire.saveLayout(this.getLayout());
                        if (el) {
                            el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    }, 200);
                });
            }).catch(err => {
                console.error('[dashboard-builder] duplicateWidget wire call error:', err);
            });
        },

        shouldShowSeries(vKey) {
            // Handle builder data structure (array of objects with independent_dm_id)
            if (vKey && vKey.includes('dm')) return true;
            
            const indepVars = this.widgetKpiConfig?.independent_variables || [];
            // For dependent: hide if dependent_dm_id exists
            if (vKey === 'dependent') {
                return !this.widgetKpiConfig?.dependent_dm_id;
            }
            // For independent_X: check if that index has independent_dm_id
            if (vKey.startsWith('independent_')) {
                const idx = parseInt(vKey.replace('independent_', ''), 10);
                const varCfg = indepVars[idx];
                return !varCfg?.independent_dm_id;
            }
            return true;
        }
    };
}

if (typeof window !== 'undefined') {
    window.dashboardBuilder = dashboardBuilder;
}
