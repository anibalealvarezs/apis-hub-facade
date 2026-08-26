export function dashboardBuilder(config = {}) {
    const dashboardControls = config.dashboardControls || {};
    return {
        tenant: config.tenant || (window.location.pathname.match(/\/app\/([^\/]+)/)?.[1]) || '',

        // ─── Unsaved Layout State ───
        isDirty: false,
        _isInitializingGrid: true,
        showUnsavedNavModal: false,
        pendingNavUrl: null,
        pendingNavReload: false,

        // ─── Unsaved Widget Controls State ───
        showUnsavedWidgetControlsModal: false,
        widgetControlsSnapshot: null,
        hasUserInteractedWithWidgetControls: false,

        // ─── Ephemeral Sandbox Testing State ───
        showSandboxModal: false,
        sandboxTargetWidget: null,
        sandboxForm: {
            date_start: '',
            date_end: '',
            granularity: '',
            zero_handling: '',
            edge_case_weighted: true,
            edge_case_grouping: '',
            max_ratio: null,
            remove_unknown: true,
            block_first_col: true,
            channel: '',
            metrics: [],
            series_assets: {},
            series_asset_groups: {},
            series_dependencies: {},
            combo_chart_config: {}
        },
        sandboxVariables: {},
        sandboxSearchQueries: {},
        activeSandboxMobileTab: 'controls',
        canScrollSandboxSeriesLeft: false,
        canScrollSandboxSeriesRight: false,
        ephemeralOverrides: {},

        openSandboxModal(widget) {
            this.sandboxTargetWidget = widget;
            const current = {
                ...this.resolveWidgetControls(widget),
                ...(this.ephemeralOverrides[widget.id] || {})
            };
            this.sandboxForm = {
                date_start: current.date_start || '',
                date_end: current.date_end || '',
                granularity: current.granularity || 'daily',
                zero_handling: current.zero_handling || 'keep',
                edge_case_weighted: current.edge_case_weighted !== undefined ? current.edge_case_weighted : true,
                edge_case_grouping: current.edge_case_grouping || 'none',
                max_ratio: current.max_ratio ?? null,
                remove_unknown: current.remove_unknown !== undefined ? current.remove_unknown : true,
                block_first_col: current.block_first_col !== undefined ? current.block_first_col : true,
                channel: current.channel || '',
                metrics: Array.isArray(current.metrics) ? [...current.metrics] : (current.metrics ? Object.values(current.metrics) : []),
                series_assets: current.series_assets ? JSON.parse(JSON.stringify(current.series_assets)) : {},
                series_asset_groups: current.series_asset_groups ? JSON.parse(JSON.stringify(current.series_asset_groups)) : {},
                series_dependencies: current.series_dependencies ? JSON.parse(JSON.stringify(current.series_dependencies)) : {},
                combo_chart_config: current.combo_chart_config ? JSON.parse(JSON.stringify(current.combo_chart_config)) : {},
            };

            this.sandboxVariables = {};
            this.sandboxSearchQueries = {};

            const ensureChannelLoaded = (ch) => {
                if (!ch || !this.$wire) return;
                if (!this.allChannelAssets[ch]) {
                    this.$wire.getAssetsForChannel(ch).then(assets => { this.allChannelAssets = { ...this.allChannelAssets, [ch]: assets }; });
                }
                if (!this.allChannelAssetGroups[ch]) {
                    this.$wire.getAssetGroupsForChannel(ch).then(groups => { this.allChannelAssetGroups = { ...this.allChannelAssetGroups, [ch]: groups }; });
                }
                if (!this.allChannelDependencies[ch]) {
                    this.$wire.getDependenciesForChannel(ch).then(deps => { this.allChannelDependencies = { ...this.allChannelDependencies, [ch]: deps }; });
                }
                if (!this.allChannelMetrics[ch]) {
                    this.$wire.getMetricsForChannel(ch).then(metrics => {
                        this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                        if (this.sandboxVariables) {
                            Object.values(this.sandboxVariables).forEach(v => {
                                if (v.channel === ch && !this.sandboxForm.metrics[v.index]) {
                                    const mKeys = Object.keys(metrics || {});
                                    if (mKeys.length > 0) this.sandboxForm.metrics[v.index] = mKeys[0];
                                }
                            });
                        }
                    });
                }
            };

            if (widget.source_type === 'kpi') {
                const kpiId = widget.source_config?.custom_kpi_id;
                const setupKpiVariables = (config) => {
                    const uiState = config?.filters?._ui_state || config || {};
                    const vars = {};
                    let indIndex = 1;

                    // 1. Dependent Variable
                    const depDmId = uiState.dependent_dm_id || (uiState.dependent_source_type === 'derived_metric' ? uiState.dependent_dm_id : null);
                    const depDm = depDmId ? (this.derivedMetrics?.[depDmId] || this.derivedMetrics?.[String(depDmId)]) : null;

                    if (depDm && Array.isArray(depDm.source_series) && depDm.source_series.length > 0) {
                        depDm.source_series.forEach((s, sIdx) => {
                            const key = 'dep_dm_' + sIdx;
                            const ch = s.channel || '';
                            ensureChannelLoaded(ch);
                            const dmAllowed = (current.series_allowed_assets && current.series_allowed_assets[key])
                                ? current.series_allowed_assets[key]
                                : (s.allowed_assets || s.asset_filter || s.asset_ids || (current.series_assets && current.series_assets[key] ? current.series_assets[key] : []));
                            vars[key] = {
                                index: 0,
                                channel: ch,
                                channel_name: ch,
                                selected_metric: s.metric || '',
                                dm_id: depDmId,
                                dm_name: depDm.name || '',
                                dm_source_label: s.label || ('Series ' + (sIdx + 1)),
                                is_dm_source: true,
                                allowed_assets: Array.isArray(dmAllowed) ? dmAllowed.map(String) : [],
                            };
                            this.sandboxSearchQueries[key] = '';
                            if (!this.sandboxForm.series_assets[key]) {
                                const defaultAssets = (current.series_assets && current.series_assets[key]) ? current.series_assets[key] : (s.asset_filter || s.asset_ids || (current.assets && current.assets.length > 0 ? current.assets : []));
                                this.sandboxForm.series_assets[key] = Array.isArray(defaultAssets) ? [...defaultAssets].map(String) : [];
                            }
                            if (s.dependency && !this.sandboxForm.series_dependencies[key]) {
                                this.sandboxForm.series_dependencies[key] = s.dependency;
                            }
                        });
                    } else {
                        const depChannel = uiState.dependent_channel || current.channel || this.dashboardControls.channel || 'facebook_marketing';
                        ensureChannelLoaded(depChannel);
                        const depAllowed = (current.series_allowed_assets && current.series_allowed_assets['dependent'])
                            ? current.series_allowed_assets['dependent']
                            : (uiState.dependent_allowed_assets || uiState.dependent_asset_filter || (current.series_assets && current.series_assets['dependent'] ? current.series_assets['dependent'] : []));
                        vars['dependent'] = {
                            index: 0,
                            channel: depChannel,
                            channel_name: depChannel,
                            selected_metric: uiState.dependent_metric || (current.metrics && current.metrics.length > 0 ? current.metrics[0] : ''),
                            dm_id: depDmId || null,
                            dm_name: depDm?.name || null,
                            allowed_assets: Array.isArray(depAllowed) ? depAllowed.map(String) : [],
                        };
                        this.sandboxSearchQueries['dependent'] = '';
                        if (!this.sandboxForm.series_assets['dependent']) {
                            const defaultAssets = (current.series_assets && current.series_assets['dependent']) ? current.series_assets['dependent'] : (uiState.dependent_asset_filter || (current.assets && current.assets.length > 0 ? current.assets : []));
                            this.sandboxForm.series_assets['dependent'] = Array.isArray(defaultAssets) ? [...defaultAssets].map(String) : [];
                        }
                        const defaultMetric = uiState.dependent_metric || (current.metrics && current.metrics.length > 0 ? current.metrics[0] : null);
                        if (defaultMetric && (!this.sandboxForm.metrics || this.sandboxForm.metrics.length === 0 || !this.sandboxForm.metrics[0])) {
                            this.sandboxForm.metrics[0] = defaultMetric;
                        } else if (!this.sandboxForm.metrics[0] && this.allChannelMetrics[depChannel]) {
                            const mKeys = Object.keys(this.allChannelMetrics[depChannel]);
                            if (mKeys.length > 0) this.sandboxForm.metrics[0] = mKeys[0];
                        }
                        if (uiState.dependent_dependency && !this.sandboxForm.series_dependencies['dependent']) {
                            this.sandboxForm.series_dependencies['dependent'] = uiState.dependent_dependency;
                        }
                    }

                    // 2. Independent Variables
                    if (uiState.independent_variables) {
                        for (let k in uiState.independent_variables) {
                            const iv = uiState.independent_variables[k];
                            const varKey = 'independent_' + k;
                            const indDmId = iv.independent_dm_id || (iv.independent_source_type === 'derived_metric' ? iv.independent_dm_id : null);
                            const indDm = indDmId ? (this.derivedMetrics?.[indDmId] || this.derivedMetrics?.[String(indDmId)]) : null;

                            if (indDm && Array.isArray(indDm.source_series) && indDm.source_series.length > 0) {
                                indDm.source_series.forEach((s, sIdx) => {
                                    const key = 'ind_' + k + '_dm_' + sIdx;
                                    const ch = s.channel || '';
                                    ensureChannelLoaded(ch);
                                    const dmAllowed = (current.series_allowed_assets && current.series_allowed_assets[key])
                                        ? current.series_allowed_assets[key]
                                        : (s.allowed_assets || s.asset_filter || s.asset_ids || (current.series_assets && current.series_assets[key] ? current.series_assets[key] : []));
                                    vars[key] = {
                                        index: indIndex,
                                        channel: ch,
                                        channel_name: ch,
                                        selected_metric: s.metric || '',
                                        dm_id: indDmId,
                                        dm_name: indDm.name || '',
                                        dm_source_label: s.label || ('Series ' + (sIdx + 1)),
                                        is_dm_source: true,
                                        allowed_assets: Array.isArray(dmAllowed) ? dmAllowed.map(String) : [],
                                    };
                                    this.sandboxSearchQueries[key] = '';
                                    if (!this.sandboxForm.series_assets[key]) {
                                        const defaultAssets = (current.series_assets && current.series_assets[key]) ? current.series_assets[key] : (s.asset_filter || s.asset_ids || []);
                                        this.sandboxForm.series_assets[key] = Array.isArray(defaultAssets) ? [...defaultAssets].map(String) : [];
                                    }
                                    if (s.dependency && !this.sandboxForm.series_dependencies[key]) {
                                        this.sandboxForm.series_dependencies[key] = s.dependency;
                                    }
                                });
                            } else {
                                if (iv.independent_channel) {
                                    ensureChannelLoaded(iv.independent_channel);
                                }
                                const indAllowed = (current.series_allowed_assets && current.series_allowed_assets[varKey])
                                    ? current.series_allowed_assets[varKey]
                                    : (iv.independent_allowed_assets || iv.independent_asset_filter || (current.series_assets && current.series_assets[varKey] ? current.series_assets[varKey] : []));
                                vars[varKey] = {
                                    index: indIndex,
                                    channel: iv.independent_channel || '',
                                    channel_name: iv.independent_channel || '',
                                    selected_metric: iv.independent_metric || '',
                                    dm_id: indDmId || null,
                                    dm_name: indDm?.name || null,
                                    allowed_assets: Array.isArray(indAllowed) ? indAllowed.map(String) : [],
                                };
                                this.sandboxSearchQueries[varKey] = '';
                                if (!this.sandboxForm.series_assets[varKey]) {
                                    const defaultAssets = (current.series_assets && current.series_assets[varKey]) ? current.series_assets[varKey] : (iv.independent_asset_filter || []);
                                    this.sandboxForm.series_assets[varKey] = Array.isArray(defaultAssets) ? [...defaultAssets].map(String) : [];
                                }
                                if (!this.sandboxForm.metrics[indIndex]) {
                                    if (iv.independent_metric) {
                                        this.sandboxForm.metrics[indIndex] = iv.independent_metric;
                                    } else if (this.allChannelMetrics[iv.independent_channel]) {
                                        const mKeys = Object.keys(this.allChannelMetrics[iv.independent_channel]);
                                        if (mKeys.length > 0) this.sandboxForm.metrics[indIndex] = mKeys[0];
                                    }
                                }
                                if (iv.independent_dependency && !this.sandboxForm.series_dependencies[varKey]) {
                                    this.sandboxForm.series_dependencies[varKey] = iv.independent_dependency;
                                }
                            }
                            indIndex++;
                        }
                    }
                    this.sandboxVariables = vars;
                };

                if (kpiId && this.$wire) {
                    this.$wire.getKpiConfiguration(kpiId).then(cfg => {
                        setupKpiVariables(cfg);
                    }).catch(() => {
                        setupKpiVariables(this.widgetKpiConfig || {});
                    });
                } else {
                    setupKpiVariables(this.widgetKpiConfig || {});
                }
            } else if (widget.source_type === 'derived_metric') {
                const dmId = widget.source_config?.derived_metric_id;
                const dm = dmId ? (this.derivedMetrics?.[dmId] || this.derivedMetrics?.[String(dmId)]) : null;
                const sourceSeries = dm?.source_series || [];
                const vars = {};
                sourceSeries.forEach((s, idx) => {
                    const key = 'dm_' + idx;
                    ensureChannelLoaded(s.channel);
                    vars[key] = {
                        index: idx,
                        channel: s.channel || '',
                        channel_name: s.channel || '',
                        selected_metric: s.metric || '',
                        dm_name: dm?.name || '',
                        dm_source_label: s.label || ('Series ' + (idx + 1)),
                    };
                    this.sandboxSearchQueries[key] = '';
                    if (!this.sandboxForm.series_assets[key]) {
                        const defaultAssets = s.asset_ids || [];
                        this.sandboxForm.series_assets[key] = Array.isArray(defaultAssets) ? [...defaultAssets].map(String) : [];
                    }
                });
                this.sandboxVariables = vars;
            } else {
                const rawSeries = current.raw_series || widget.controls?.raw_series || [];
                const vars = {};
                if (rawSeries.length > 0) {
                    rawSeries.forEach((s, idx) => {
                        const key = String(idx);
                        ensureChannelLoaded(s.channel);
                        vars[key] = {
                            index: idx,
                            type: s.type || (s.dm_id ? 'derived_metric' : 'metric'),
                            dm_id: s.dm_id || null,
                            dm_name: s.dm_name || null,
                            channel: s.channel || '',
                            channel_name: s.channel || '',
                            allowed_metrics: Array.isArray(s.allowed_metrics) ? s.allowed_metrics : (Array.isArray(s.metrics) ? s.metrics : []),
                            allowed_assets: Array.isArray(s.allowed_assets) ? s.allowed_assets : (Array.isArray(s.assets) ? s.assets : []),
                            selected_metric: '',
                        };
                        this.sandboxSearchQueries[key] = '';
                        if (!this.sandboxForm.series_assets[key]) {
                            const defaultAssets = s.assets || [];
                            this.sandboxForm.series_assets[key] = Array.isArray(defaultAssets) ? [...defaultAssets].map(String) : [];
                        }
                    });
                } else {
                    const ch = current.channel || this.dashboardControls.channel || '';
                    ensureChannelLoaded(ch);
                    vars['0'] = {
                        index: 0,
                        type: 'metric',
                        channel: ch,
                        channel_name: ch,
                        allowed_metrics: [],
                        selected_metric: '',
                    };
                    this.sandboxSearchQueries['0'] = '';
                    if (!this.sandboxForm.series_assets['0']) {
                        const defaultAssets = current.assets || [];
                        this.sandboxForm.series_assets['0'] = Array.isArray(defaultAssets) ? [...defaultAssets].map(String) : [];
                    }
                }
                this.sandboxVariables = vars;
            }

            this.showSandboxModal = true;
            this.$nextTick(() => {
                this.updateSandboxSeriesScrollState();
            });
        },

        sandboxToggleMetric(vKey, metricKey) {
            if (this.sandboxTargetWidget?.source_type === 'kpi') {
                const idx = this.sandboxVariables[vKey]?.index ?? 0;
                this.sandboxForm.metrics[idx] = metricKey;
            } else {
                if (!Array.isArray(this.sandboxForm.metrics)) {
                    this.sandboxForm.metrics = [];
                }
                if (this.sandboxForm.metrics.includes(metricKey)) {
                    this.sandboxForm.metrics = this.sandboxForm.metrics.filter(m => m !== metricKey);
                } else {
                    this.sandboxForm.metrics = [...this.sandboxForm.metrics, metricKey];
                }
            }
        },

        _scopedMetricsCache: {},
        _fetchingScopedMetrics: {},

        getSandboxMetricsForSeries(vKey, vConfig) {
            const ch = vConfig?.channel;
            if (!ch) return {};
            const dep = this.sandboxForm?.series_dependencies?.[vKey] || '';
            const cacheKey = ch + '::' + dep;
            if (this._scopedMetricsCache && this._scopedMetricsCache[cacheKey]) {
                return this._scopedMetricsCache[cacheKey];
            }
            if (this.$wire && (!this._fetchingScopedMetrics || !this._fetchingScopedMetrics[cacheKey])) {
                if (!this._fetchingScopedMetrics) this._fetchingScopedMetrics = {};
                this._fetchingScopedMetrics[cacheKey] = true;
                this.$wire.getMetricsForChannel(ch, this.sandboxForm?.granularity || null, dep || null).then(metrics => {
                    if (!this._scopedMetricsCache) this._scopedMetricsCache = {};
                    this._scopedMetricsCache[cacheKey] = metrics || {};
                    delete this._fetchingScopedMetrics[cacheKey];
                });
            }
            return this._scopedMetricsCache?.[cacheKey] || this.allChannelMetrics[ch] || {};
        },

        getSandboxAssetsForSeries(vKey, vConfig) {
            const ch = vConfig?.channel;
            if (!ch || !this.allChannelAssets[ch]) return {};
            const all = this.allChannelAssets[ch];

            let allowed = vConfig?.allowed_assets;
            if (!allowed || allowed.length === 0) {
                // If allowed_assets wasn't explicitly populated on vConfig, check sandboxForm series_assets / widget series_assets
                const widget = this.sandboxTargetWidget;
                const controls = this.resolveWidgetControls(widget);
                if (controls.series_allowed_assets?.[vKey]) {
                    allowed = controls.series_allowed_assets[vKey];
                } else if (controls.series_assets?.[vKey]) {
                    allowed = controls.series_assets[vKey];
                } else if (widget?.source_type !== 'kpi' && controls.assets) {
                    allowed = controls.assets;
                }
            }

            const allowedStrs = Array.isArray(allowed) && allowed.length > 0 ? allowed.map(String) : null;
            const res = {};

            for (const [id, name] of Object.entries(all)) {
                const strId = String(id);
                if (allowedStrs && !allowedStrs.includes(strId)) {
                    continue;
                }
                if (!this.isAssetAllowedByGroups(vKey, ch, strId)) {
                    continue;
                }
                res[id] = name;
            }

            return res;
        },

        onSandboxDependencyChange(vKey, vConfig) {
            const ch = vConfig?.channel;
            const dep = this.sandboxForm?.series_dependencies?.[vKey] || '';
            if (!ch || !this.$wire) return;
            const cacheKey = ch + '::' + dep;
            this.$wire.getMetricsForChannel(ch, this.sandboxForm?.granularity || null, dep || null).then(metrics => {
                if (!this._scopedMetricsCache) this._scopedMetricsCache = {};
                this._scopedMetricsCache[cacheKey] = metrics || {};
                const availableKeys = Object.keys(metrics || {});
                if (availableKeys.length > 0 && vConfig?.index !== undefined) {
                    const currentM = this.sandboxForm.metrics[vConfig.index];
                    if (!currentM || !availableKeys.includes(currentM)) {
                        this.sandboxForm.metrics[vConfig.index] = availableKeys[0];
                    }
                }
            });
        },

        sandboxIsMetricSelected(vKey, metricKey) {
            if (this.sandboxTargetWidget?.source_type === 'kpi') {
                const idx = this.sandboxVariables[vKey]?.index ?? 0;
                return this.sandboxForm.metrics[idx] === metricKey;
            }
            return (this.sandboxForm.metrics || []).includes(metricKey);
        },

        sandboxSelectAllMetrics(vKey) {
            const vConfig = this.sandboxVariables[vKey];
            const ch = vConfig?.channel;
            const scopedMetrics = this.getSandboxMetricsForSeries(vKey, vConfig);
            const available = vConfig?.allowed_metrics && vConfig.allowed_metrics.length > 0
                ? vConfig.allowed_metrics.filter(m => Object.keys(scopedMetrics).includes(m))
                : Object.keys(scopedMetrics);
            this.sandboxForm.metrics = [...new Set([...(this.sandboxForm.metrics || []), ...available])];
        },

        sandboxToggleAsset(vKey, assetId) {
            const strId = String(assetId);
            const current = this.sandboxForm.series_assets[vKey] || [];
            if (current.includes(strId)) {
                this.sandboxForm.series_assets[vKey] = current.filter(a => a !== strId);
            } else {
                this.sandboxForm.series_assets[vKey] = [...current, strId];
            }
        },

        sandboxIsAssetSelected(vKey, assetId) {
            return ((this.sandboxForm.series_assets || {})[vKey] || []).includes(String(assetId));
        },

        sandboxSelectAllAssets(vKey) {
            const vConfig = this.sandboxVariables[vKey];
            const ch = vConfig?.channel;
            const assets = this.allChannelAssets[ch] || {};
            let validIds = Object.keys(assets).map(String);
            const globalGroup = this.dashboardControls?.asset_group;
            if (globalGroup && this.allChannelAssetGroups[ch]?.[globalGroup]) {
                const groupAssets = this.allChannelAssetGroups[ch][globalGroup].assets.map(String);
                validIds = validIds.filter(id => groupAssets.includes(id));
            }
            this.sandboxForm.series_assets[vKey] = validIds;
        },

        sandboxToggleComboType(index, metricKey) {
            if (!this.sandboxForm.combo_chart_config) {
                this.sandboxForm.combo_chart_config = {};
            }
            const cfgKey = index + '_' + metricKey;
            const current = this.sandboxForm.combo_chart_config[cfgKey]?.type || (index === 0 ? 'bar' : 'line');
            const nextType = current === 'bar' ? 'line' : 'bar';
            this.sandboxForm.combo_chart_config = {
                ...this.sandboxForm.combo_chart_config,
                [cfgKey]: {
                    ...(this.sandboxForm.combo_chart_config[cfgKey] || {}),
                    type: nextType
                }
            };
        },

        sandboxGetComboType(index, metricKey) {
            const cfgKey = index + '_' + metricKey;
            return this.sandboxForm?.combo_chart_config?.[cfgKey]?.type || (index === 0 ? 'bar' : 'line');
        },

        updateSandboxSeriesScrollState() {
            const el = this.$refs.sandboxSeriesScrollContainer;
            if (!el) return;
            this.canScrollSandboxSeriesLeft = el.scrollLeft > 5;
            this.canScrollSandboxSeriesRight = Math.ceil(el.scrollLeft + el.clientWidth) < el.scrollWidth - 5;
        },

        scrollSandboxSeriesByStep(direction = 1) {
            const el = this.$refs.sandboxSeriesScrollContainer;
            if (!el) return;
            const firstCard = el.querySelector('.snap-start');
            const step = firstCard ? (firstCard.offsetWidth + 24) : ((el.clientWidth / 2) + 12);
            el.scrollBy({ left: direction * step, behavior: 'smooth' });
            setTimeout(() => {
                this.updateSandboxSeriesScrollState();
            }, 350);
        },

        applySandboxControls() {
            if (!this.sandboxTargetWidget) return;
            const id = this.sandboxTargetWidget.id;
            this.ephemeralOverrides[id] = JSON.parse(JSON.stringify(this.sandboxForm));
            this.showSandboxModal = false;
            this.$nextTick(() => {
                this.renderWidget(id);
            });
        },

        resetSandboxControls() {
            if (!this.sandboxTargetWidget) return;
            const id = this.sandboxTargetWidget.id;
            delete this.ephemeralOverrides[id];
            this.showSandboxModal = false;
            this.$nextTick(() => {
                this.renderWidget(id);
            });
        },

        hasSandboxOverrides(widgetId) {
            return !!(this.ephemeralOverrides[widgetId] && Object.keys(this.ephemeralOverrides[widgetId]).length > 0);
        },

        renderWidget(widgetId, el) {
            const widget = (this.widgets || []).find(w => String(w.id) === String(widgetId));
            if (!widget) return;
            const targetEl = el || document.getElementById('builder-widget-preview-' + widgetId);
            if (!targetEl) return;
            if (!window.dashboardRenderer) {
                const checkReady = (retries = 0) => {
                    if (window.dashboardRenderer) {
                        this.renderWidget(widgetId, el);
                    } else if (retries < 50) {
                        setTimeout(() => checkReady(retries + 1), 100);
                    }
                };
                checkReady();
                return;
            }
            targetEl.innerHTML = '';
            const effectiveControls = {
                ...this.resolveWidgetControls(widget),
                ...(this.ephemeralOverrides[widget.id] || {})
            };
            window.dashboardRenderer.renderWidget(widget.id, targetEl, effectiveControls, this.tenant);
        },

        renderAllWidgets() {
            (this.widgets || []).forEach(w => {
                this.renderWidget(w.id);
            });
        },

        renderWidgetPreview(widgetId, el) {
            this.renderWidget(widgetId, el);
        },

        getWidgetControlsSignature() {
            if (!this.widgetControlsForm) return '';
            const f = this.widgetControlsForm;
            return JSON.stringify({
                titles: f.titles || {},
                descriptions: f.descriptions || {},
                widget_type: f.widget_type || '',
                date_inherit: !!f.date_inherit,
                date_start: f.date_inherit ? '' : (f.date_start || ''),
                date_end: f.date_inherit ? '' : (f.date_end || ''),
                zero_inherit: !!f.zero_inherit,
                zero_handling: f.zero_inherit ? '' : (f.zero_handling || ''),
                granularity_inherit: !!f.granularity_inherit,
                granularity: f.granularity_inherit ? '' : (f.granularity || ''),
                edge_case_inherit: !!f.edge_case_inherit,
                edge_case_weighted: f.edge_case_inherit ? null : f.edge_case_weighted,
                edge_case_grouping: f.edge_case_inherit ? '' : (f.edge_case_grouping || ''),
                max_ratio_inherit: !!f.max_ratio_inherit,
                max_ratio: f.max_ratio_inherit ? null : f.max_ratio,
                combo_chart_config: f.combo_chart_config || {},
                series_assets: f.series_assets || {},
                series_asset_groups: f.series_asset_groups || {},
                series_dependencies: f.series_dependencies || {},
                channel: f.channel || '',
                assets: Array.isArray(f.assets) ? [...f.assets].sort() : [],
                metrics: Array.isArray(f.metrics) ? [...f.metrics].sort() : [],
                dependency: f.dependency || '',
                raw_series: (f.raw_series || []).map(s => ({
                    type: s.type || '',
                    dm_id: s.dm_id ? String(s.dm_id) : '',
                    channel: s.channel || '',
                    dependency: s.dependency || '',
                    metrics: Array.isArray(s.metrics) ? [...s.metrics].sort() : [],
                    assets: Array.isArray(s.assets) ? [...s.assets].sort() : []
                }))
            });
        },

        captureWidgetControlsSnapshot() {
            this.widgetControlsSnapshot = this.getWidgetControlsSignature();
        },

        markWidgetControlsDirty() {
            this.hasUserInteractedWithWidgetControls = true;
        },

        isWidgetControlsDirty() {
            if (!this.hasUserInteractedWithWidgetControls) return false;
            if (!this.widgetControlsSnapshot || !this.widgetControlsForm) return false;
            return this.getWidgetControlsSignature() !== this.widgetControlsSnapshot;
        },

        attemptCloseWidgetControls() {
            if (this.isWidgetControlsDirty()) {
                this.showUnsavedWidgetControlsModal = true;
            } else {
                this.showWidgetControls = false;
                this.hasUserInteractedWithWidgetControls = false;
                this.widgetControlsSnapshot = null;
            }
        },

        confirmSaveAndCloseWidgetControls() {
            this.confirmWidgetControls();
            this.showUnsavedWidgetControlsModal = false;
        },

        confirmDiscardAndCloseWidgetControls() {
            this.showUnsavedWidgetControlsModal = false;
            this.showWidgetControls = false;
            this.hasUserInteractedWithWidgetControls = false;
            this.widgetControlsSnapshot = null;
        },

        cancelUnsavedWidgetControlsModal() {
            this.showUnsavedWidgetControlsModal = false;
        },

        resolveWidgetControls(widget) {
            const wc = widget.controls || {};
            const dc = this.dashboardControls || {};
            const resolved = { ...wc };

            if (!resolved.date_start && dc.date_start) resolved.date_start = dc.date_start;
            if (!resolved.date_end && dc.date_end) resolved.date_end = dc.date_end;
            if (!resolved.zero_handling && dc.zero_handling) resolved.zero_handling = dc.zero_handling;
            if (!resolved.granularity && dc.granularity) resolved.granularity = dc.granularity;
            if (resolved.edge_case_weighted === undefined && dc.edge_case_weighted !== undefined) {
                resolved.edge_case_weighted = dc.edge_case_weighted;
            }
            if (!resolved.edge_case_grouping && dc.edge_case_grouping) {
                resolved.edge_case_grouping = dc.edge_case_grouping;
            }
            if (dc.asset_group) {
                resolved.asset_group = dc.asset_group;
            }
            return resolved;
        },

        // ─── Delete Confirmation Modal State ───
        deleteConfirmOpen: false,
        deleteConfirmTargets: [],

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
            this.selectedWidgetIds = [...this.selectedWidgetIds];
            console.log('[DB][toggle]', { id: strId, after: this.selectedWidgetIds.map(String) });
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

        confirmDeleteWidget(id) {
            this.deleteConfirmTargets = [id];
            this.deleteConfirmOpen = true;
        },

        confirmDeleteSelectedWidgets() {
            if (this.selectedWidgetIds.length === 0) return;
            this.deleteConfirmTargets = [...this.selectedWidgetIds];
            this.deleteConfirmOpen = true;
        },

        cancelDeleteConfirm() {
            this.deleteConfirmOpen = false;
            this.deleteConfirmTargets = [];
        },

        proceedDelete() {
            const targets = [...this.deleteConfirmTargets];
            this.deleteConfirmOpen = false;
            this.deleteConfirmTargets = [];
            targets.forEach(id => {
                this.deleteWidget(id, true);
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
            if (this.pendingNavReload) {
                window.location.reload();
            } else if (this.pendingNavUrl) {
                window.location.assign(this.pendingNavUrl);
            }
        },

        confirmSaveAndLeave() {
            const currentLayout = this.getLayout();
            if (this.$wire) {
                this.$wire.saveLayout(currentLayout).then(() => {
                    this.isDirty = false;
                    this.showUnsavedNavModal = false;
                    if (this.pendingNavReload) {
                        window.location.reload();
                    } else if (this.pendingNavUrl) {
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
        allChannelDependencies: {},
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
            series_dependencies: {},
            dm_assets: {},
        },
        widgetKpiConfig: {},
        widgetAssets: {},
        searchQueries: {},
        canScrollSeriesLeft: false,
        canScrollSeriesRight: false,

        // ─── Add Series Type Modal (Raw Metric vs Derived Metric) ──
        showAddSeriesTypeModal: false,
        addSeriesSourceType: 'metric',
        addSeriesDerivedMetricId: '',

        // ─── Remove DM Series Group Confirmation Modal ──
        showRemoveDmSeriesModal: false,
        pendingRemoveDmId: '',
        pendingRemoveDmName: '',

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
        selectedSourceType: '',
        selectedWidgetType: '',
        customKpiId: '',
        derivedMetricId: '',
        widgetName: '',
        targetGridX: null,
        targetGridY: null,
        pendingDragSourceType: null,

        get addWidgetForm() {
            return {
                source_type: this.selectedSourceType,
                widget_type: this.selectedWidgetType,
                custom_kpi_id: this.customKpiId,
                derived_metric_id: this.derivedMetricId,
                name: this.widgetName,
            };
        },

        set addWidgetForm(val) {
            if (!val) return;
            this.selectedSourceType = val.source_type || '';
            this.selectedWidgetType = val.widget_type || '';
            this.customKpiId = val.custom_kpi_id || '';
            this.derivedMetricId = val.derived_metric_id || '';
            this.widgetName = val.name || '';
        },

        openAddWidgetModal(preselectedSourceType = null) {
            console.log('[DB][openAddWidgetModal] ENTER', { preselectedSourceType });
            this.selectedSourceType = preselectedSourceType || '';
            this.selectedWidgetType = '';
            this.customKpiId = '';
            this.derivedMetricId = '';
            this.widgetName = '';
            this.showAddWidgetModal = true;
        },

        setSourceType(type) {
            console.log('[DB][setSourceType] CLICKED type:', type);
            this.selectedSourceType = type;
            this.selectedWidgetType = '';
            console.log('[DB][setSourceType] AFTER set selectedSourceType:', this.selectedSourceType);
        },

        get sourceTypesList() {
            const defaultSourceLabels = {
                kpi: 'Custom KPI (Analytics Engine)',
                metric: 'Metric (Raw Aggregation)',
                derived_metric: 'Derived Metric (Computed Series)'
            };
            const sources = (this.sourceTypes && Object.keys(this.sourceTypes).length > 0)
                ? this.sourceTypes
                : defaultSourceLabels;

            return Object.entries(sources).map(([type, label]) => ({ type, label }));
        },

        // ─── Computed ──
        get optimalWidgetTypes() {
            if (this.selectedSourceType === 'kpi' && this.customKpiId) {
                const kpiData = this.kpis ? this.kpis[this.customKpiId] : null;
                return kpiData ? (kpiData.optimal_widgets || []) : [];
            }
            return [];
        },

        get availableWidgetTypes() {
            const sourceType = this.selectedSourceType;
            const kpiId = this.customKpiId;
            console.log('[DB][availableWidgetTypes] ENTER', {
                sourceType,
                kpiId,
                hasWidgetLabels: !!this.widgetLabels,
                widgetLabelsKeys: this.widgetLabels ? Object.keys(this.widgetLabels) : []
            });

            if (!sourceType) return {};

            const defaultLabels = {
                tile: 'Number Tile',
                line_chart: 'Line Chart',
                bar_chart: 'Bar Chart',
                scatter_plot: 'Scatter Plot',
                combo_chart: 'Combo Chart',
                table: 'Table',
                gauge: 'Gauge',
                sparkline: 'Sparkline',
                anomaly_chart: 'Anomaly Chart'
            };

            const allTypes = (this.widgetLabels && Object.keys(this.widgetLabels).length > 0)
                ? this.widgetLabels
                : defaultLabels;

            let filtered = {};

            if (sourceType === 'metric') {
                const allowed = ['tile', 'line_chart', 'bar_chart', 'sparkline', 'combo_chart', 'table', 'gauge'];
                for (const t of allowed) {
                    filtered[t] = allTypes[t] || defaultLabels[t] || t;
                }
            } else if (sourceType === 'kpi') {
                if (!kpiId) {
                    const allowed = ['tile', 'line_chart', 'bar_chart', 'gauge', 'sparkline', 'anomaly_chart', 'scatter_plot', 'combo_chart', 'table'];
                    for (const t of allowed) {
                        filtered[t] = allTypes[t] || defaultLabels[t] || t;
                    }
                } else {
                    const kpiData = (this.kpis && this.kpis[kpiId]) ? this.kpis[kpiId] : null;
                    const allowed = kpiData ? (kpiData.compatible_widgets || []) : [];

                    if (allowed.length === 0) {
                        for (const t of Object.keys(defaultLabels)) {
                            filtered[t] = allTypes[t] || defaultLabels[t] || t;
                        }
                    } else {
                        for (const t of allowed) {
                            filtered[t] = allTypes[t] || defaultLabels[t] || t;
                        }
                    }
                }
            } else if (sourceType === 'derived_metric') {
                const allowed = (config.derivedMetricWidgetTypes && config.derivedMetricWidgetTypes.length > 0)
                    ? config.derivedMetricWidgetTypes
                    : ['tile', 'line_chart', 'bar_chart', 'gauge', 'sparkline', 'combo_chart', 'table'];
                for (const t of allowed) {
                    filtered[t] = allTypes[t] || defaultLabels[t] || t;
                }
            } else {
                filtered = { ...allTypes };
            }

            console.log('[DB][availableWidgetTypes] RETURN', filtered);
            return filtered;
        },

        get availableWidgetTypesList() {
            const typesObj = this.availableWidgetTypes;
            const list = Object.entries(typesObj).map(([type, label]) => ({
                type,
                label,
                description: this.getWidgetDescription(type),
                svg: this.getWidgetSvg(type)
            }));
            console.log('[DB][availableWidgetTypesList] RETURN count:', list.length, list);
            return list;
        },

        get availableChartTypesForControls() {
            const allTypes = this.widgetLabels || {};
            const target = this.widgetControlsTarget;

            let typeMap = {};
            if (!target || !target.source_type) {
                typeMap = allTypes;
            } else if (target.source_type === 'metric') {
                const allowed = ['tile', 'line_chart', 'bar_chart', 'sparkline', 'combo_chart', 'table', 'gauge'];
                for (const t of allowed) {
                    if (allTypes[t]) typeMap[t] = allTypes[t];
                }
            } else if (target.source_type === 'kpi') {
                const kpiId = target.source_config?.custom_kpi_id || target.custom_kpi_id;
                const kpiData = kpiId ? this.kpis[kpiId] : null;
                const allowed = kpiData ? (kpiData.compatible_widgets || []) : [];
                if (allowed.length > 0) {
                    for (const t of allowed) {
                        if (allTypes[t]) typeMap[t] = allTypes[t];
                    }
                } else {
                    typeMap = allTypes;
                }
            } else if (target.source_type === 'derived_metric') {
                const allowed = config.derivedMetricWidgetTypes || ['tile', 'line_chart', 'bar_chart', 'gauge', 'sparkline', 'combo_chart', 'table'];
                for (const t of allowed) {
                    if (allTypes[t]) typeMap[t] = allTypes[t];
                }
            } else {
                typeMap = allTypes;
            }

            if (Object.keys(typeMap).length === 0) {
                typeMap = {
                    tile: 'Tile',
                    line_chart: 'Line Chart',
                    bar_chart: 'Bar Chart',
                    sparkline: 'Sparkline',
                    table: 'Table',
                    gauge: 'Gauge'
                };
            }

            return Object.entries(typeMap).map(([type, label]) => ({ type, label }));
        },

        // ─── UI Helpers ───
        getWidgetDescription(type) {
            const defaultDescriptions = {
                tile: 'Single large number for totals',
                line_chart: 'Track continuous trends over time',
                bar_chart: 'Compare discrete volumes side-by-side',
                scatter_plot: 'Find correlations and trendlines',
                combo_chart: 'Dual-axis bars and lines (e.g. MACD)',
                table: 'Detailed row-by-row data view',
                gauge: 'Percentage or progress to a target',
                sparkline: 'Minimalist trendline without axes',
                anomaly_chart: 'Highlights statistical outliers in red'
            };
            return (this.widgetDescriptions && this.widgetDescriptions[type]) || defaultDescriptions[type] || 'Standard widget';
        },

        getWidgetTitleText(widget) {
            if (!widget) return 'Widget';
            let title = widget.title;
            if (Array.isArray(title)) {
                title = title.length > 0 ? title[0] : '';
            }
            if (typeof title === 'object' && title !== null) {
                const lang = document.documentElement.lang || 'en';
                title = title[lang] || title['en'] || Object.values(title)[0] || '';
            }
            if (typeof title === 'string') {
                const trimmed = title.trim();
                if (trimmed === '[]' || trimmed === '{}') title = '';
            }
            if (!title && widget.name) {
                let name = widget.name;
                if (Array.isArray(name)) name = name.length > 0 ? name[0] : '';
                if (typeof name === 'object' && name !== null) {
                    const lang = document.documentElement.lang || 'en';
                    name = name[lang] || name['en'] || Object.values(name)[0] || '';
                }
                if (typeof name === 'string') {
                    const trimmed = name.trim();
                    if (trimmed === '[]' || trimmed === '{}') name = '';
                }
                title = name;
            }
            return (typeof title === 'string' && title.trim() !== '') ? title.trim() : 'Widget';
        },

        getWidgetDescriptionText(widget) {
            if (!widget) return '';
            let desc = widget.description;
            if (Array.isArray(desc)) desc = desc.length > 0 ? desc[0] : '';
            if (typeof desc === 'object' && desc !== null) {
                const lang = document.documentElement.lang || 'en';
                desc = desc[lang] || desc['en'] || Object.values(desc)[0] || '';
            }
            if (typeof desc === 'string') {
                const trimmed = desc.trim();
                if (trimmed === '[]' || trimmed === '{}') desc = '';
            }
            return (typeof desc === 'string' && desc.trim() !== '') ? desc.trim() : '';
        },

        getWidgetSvg(type) {
            const defaultSvgs = {
                tile: '<svg viewBox="0 0 40 24" class="w-full h-full"><text x="20" y="16" text-anchor="middle" font-weight="bold" font-size="14" class="fill-gray-800 dark:fill-gray-200">12K</text><path d="M 28 8 L 32 4 L 36 8 M 32 4 L 32 16" class="stroke-green-500" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>',
                line_chart: '<svg viewBox="0 0 40 24" class="w-full h-full"><path d="M 4 18 L 12 11 L 20 15 L 28 6 L 36 8" class="stroke-primary-500" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/><circle cx="4" cy="18" r="1.5" class="fill-primary-500"/><circle cx="12" cy="11" r="1.5" class="fill-primary-500"/><circle cx="20" cy="15" r="1.5" class="fill-primary-500"/><circle cx="28" cy="6" r="1.5" class="fill-primary-500"/><circle cx="36" cy="8" r="1.5" class="fill-primary-500"/></svg>',
                bar_chart: '<svg viewBox="0 0 40 24" class="w-full h-full"><rect x="6" y="10" width="6" height="10" rx="1" class="fill-primary-400"/><rect x="17" y="6" width="6" height="14" rx="1" class="fill-primary-600"/><rect x="28" y="14" width="6" height="6" rx="1" class="fill-primary-300"/></svg>',
                scatter_plot: '<svg viewBox="0 0 40 24" class="w-full h-full"><line x1="4" y1="20" x2="36" y2="4" class="stroke-gray-300 dark:stroke-gray-600" stroke-width="1" stroke-dasharray="2 2"/><circle cx="8" cy="17" r="1.5" class="fill-primary-500"/><circle cx="14" cy="13" r="1.5" class="fill-primary-500"/><circle cx="20" cy="15" r="1.5" class="fill-primary-500"/><circle cx="26" cy="8" r="1.5" class="fill-primary-500"/><circle cx="32" cy="6" r="1.5" class="fill-primary-500"/></svg>',
                combo_chart: '<svg viewBox="0 0 40 24" class="w-full h-full"><rect x="6" y="12" width="4" height="8" rx="0.5" class="fill-primary-400 opacity-60"/><rect x="15" y="8" width="4" height="12" rx="0.5" class="fill-primary-400 opacity-60"/><rect x="24" y="14" width="4" height="6" rx="0.5" class="fill-primary-400 opacity-60"/><rect x="33" y="6" width="4" height="14" rx="0.5" class="fill-primary-400 opacity-60"/><path d="M 4 16 L 14 7 L 24 12 L 36 4" class="stroke-amber-500" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>',
                table: '<svg viewBox="0 0 40 24" class="w-full h-full"><rect x="4" y="3" width="32" height="18" rx="2" class="stroke-primary-500 fill-none" stroke-width="1.5"/><path d="M 4 8 L 36 8" class="stroke-primary-500" stroke-width="1.5"/><path d="M 4 14 L 36 14" class="stroke-gray-400 dark:stroke-gray-500" stroke-width="1" stroke-dasharray="1 1"/><path d="M 16 8 L 16 21" class="stroke-gray-400 dark:stroke-gray-500" stroke-width="1"/></svg>',
                gauge: '<svg viewBox="0 0 40 24" class="w-full h-full"><path d="M 7 19 A 13 13 0 0 1 33 19" class="stroke-gray-300 dark:stroke-gray-600 fill-none" stroke-width="4" stroke-linecap="round"/><path d="M 7 19 A 13 13 0 0 1 27 8" class="stroke-primary-500 fill-none" stroke-width="4" stroke-linecap="round"/><circle cx="20" cy="19" r="2" class="fill-gray-800 dark:fill-gray-100"/><line x1="20" y1="19" x2="25" y2="9" class="stroke-gray-800 dark:stroke-gray-100" stroke-width="1.8" stroke-linecap="round"/></svg>',
                sparkline: '<svg viewBox="0 0 40 24" class="w-full h-full"><path d="M 4 17 C 10 17, 12 6, 18 10 C 24 14, 28 4, 36 8" class="stroke-primary-500 fill-none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                anomaly_chart: '<svg viewBox="0 0 40 24" class="w-full h-full"><path d="M 4 17 L 12 15 L 20 7 L 28 14 L 36 12" class="stroke-gray-400 dark:stroke-gray-500 fill-none" stroke-width="1.5" stroke-dasharray="2 2"/><circle cx="20" cy="7" r="3.5" class="fill-red-500/20 stroke-red-500" stroke-width="1.2"/><circle cx="20" cy="7" r="1.5" class="fill-red-600"/></svg>'
            };
            return (this.widgetSvgs && this.widgetSvgs[type]) || defaultSvgs[type] || defaultSvgs['tile'];
        },

        // ─── Initialization ──
        init() {
            console.log('[DB][init] version=observer-2026-08-09', {
                widgetCount: (this.widgets || []).length,
                gridEl: !!document.getElementById('grid-stack'),
                gridReady: !!this.grid
            });

            this.$nextTick(() => {
                const container = document.getElementById('grid-stack');
                console.log('[DB][init] $nextTick', { container: !!container, gridReady: !!this.grid });
                if (container && !this.grid) {
                    this.initGrid();
                }
                this.initAllAssets();
                this.renderAllWidgets();
            });

            window.addEventListener('beforeunload', (e) => {
                if (this.isDirty) {
                    e.preventDefault();
                    e.returnValue = 'You have unsaved changes to your dashboard layout.';
                    return e.returnValue;
                }
            });

            window.addEventListener('keydown', (e) => {
                if (!this.isDirty) return;
                const key = e.key.toLowerCase();
                const isReloadKey = e.key === 'F5' || ((e.ctrlKey || e.metaKey) && key === 'r');
                if (isReloadKey) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    this.pendingNavUrl = null;
                    this.pendingNavReload = true;
                    this.showUnsavedNavModal = true;
                    return false;
                }
            }, true);

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
                        this.pendingNavReload = false;
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
                    this.pendingNavReload = false;
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
            const targetType = this.widgetControlsTarget?.source_type;

            if (targetType === 'kpi') {
                const mainCh = this.widgetKpiConfig?.dependent_channel || this.widgetControlsForm.channel;
                const dep = this.widgetControlsForm.dependency;
                if (mainCh) {
                    this.$wire.getMetricsForChannel(mainCh, gran, dep).then(metrics => {
                        this.allChannelMetrics = { ...this.allChannelMetrics, [mainCh]: metrics };
                    });
                }
            } else {
                (this.widgetControlsForm.raw_series || []).forEach((series, idx) => {
                    const ch = series.channel;
                    const dep = series.dependency || null;
                    if (ch) {
                        this.$wire.getMetricsForChannel(ch, gran, dep).then(metrics => {
                            if (!this.widgetControlsForm.series_metrics_map) {
                                this.widgetControlsForm.series_metrics_map = {};
                            }
                            this.widgetControlsForm.series_metrics_map = {
                                ...this.widgetControlsForm.series_metrics_map,
                                [idx]: metrics
                            };
                            this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                        });
                    }
                });
            }
        },

        onWidgetRawSeriesDependencyChange(index) {
            if (!this.widgetControlsForm.raw_series || !this.widgetControlsForm.raw_series[index] || !this.$wire) return;
            const series = this.widgetControlsForm.raw_series[index];
            const ch = series.channel;
            const dep = series.dependency || null;
            const gran = this.widgetControlsForm.granularity;

            if (ch) {
                this.$wire.getMetricsForChannel(ch, gran, dep).then(metrics => {
                    if (!this.widgetControlsForm.series_metrics_map) {
                        this.widgetControlsForm.series_metrics_map = {};
                    }
                    this.widgetControlsForm.series_metrics_map = {
                        ...this.widgetControlsForm.series_metrics_map,
                        [index]: metrics
                    };
                    this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                });
            }
        },

        initGridItem(el, widget) {
            console.log('[DB][initGridItem] CALL', {
                id: widget.id,
                grid: !!this.grid,
                elConnected: el ? el.isConnected : false,
                elId: el ? (el.getAttribute('gs-id') || el.getAttribute('data-id')) : null,
                hasNode: el ? !!el.gridstackNode : false,
                parent: el ? (el.parentElement ? el.parentElement.id : null) : null,
                isNew: !!widget._isNew,
                gx: widget.grid_x, gy: widget.grid_y, gw: widget.grid_w, gh: widget.grid_h
            });
            const w = parseInt(widget.grid_w || 4, 10);
            const h = parseInt(widget.grid_h || 3, 10);
            const minW = 2;
            const minH = 2;
            const hasX = widget.grid_x !== undefined && widget.grid_x !== null;
            const hasY = widget.grid_y !== undefined && widget.grid_y !== null;

            const widgetOpts = {
                id: widget.id,
                w: w,
                h: h,
                minW: minW,
                minH: minH,
                ...(hasX && hasY ? { x: parseInt(widget.grid_x, 10), y: parseInt(widget.grid_y, 10), autoPosition: false } : { autoPosition: true })
            };

            el.setAttribute('gs-id', widget.id);
            el.setAttribute('data-id', widget.id);
            el.setAttribute('gs-w', w);
            el.setAttribute('gs-h', h);

            if (hasX && hasY) {
                el.setAttribute('gs-x', widget.grid_x);
                el.setAttribute('gs-y', widget.grid_y);
                el.removeAttribute('gs-auto-position');
            } else {
                el.setAttribute('gs-auto-position', 'true');
                el.removeAttribute('gs-x');
                el.removeAttribute('gs-y');
            }

            let attempts = 0;
            const registerNode = () => {
                if (!el.isConnected) {
                    console.log('[DB][initGridItem] retry-abandon el disconnected', { id: widget.id, attempts });
                    return;
                }
                if (!this.grid) {
                    console.log('[DB][initGridItem] retry grid missing', { id: widget.id, attempts });
                    if (++attempts > 600) return;
                    requestAnimationFrame(registerNode);
                    return;
                }
                const header = el.querySelector('.widget-header');
                if (!header) {
                    console.log('[DB][initGridItem] retry header missing', { id: widget.id, attempts });
                    if (++attempts > 600) return;
                    requestAnimationFrame(registerNode);
                    return;
                }

                if (el.gridstackNode) {
                    el.gridstackNode.id = widget.id;
                    console.log('[DB][initGridItem] updating existing node', { id: widget.id });
                    this.grid.update(el, widgetOpts);
                } else {
                    const node = this.grid.makeWidget(el, widgetOpts);
                    console.log('[DB][initGridItem] makeWidget result', {
                        id: widget.id,
                        node: !!node,
                        elHasNode: !!el.gridstackNode,
                        parent: el.parentElement ? el.parentElement.id : null,
                        style: el.getAttribute('style'),
                        classList: el.className
                    });
                    if (node && typeof node === 'object') {
                        node.id = widget.id;
                    }
                }

                if (typeof this.grid.movable === 'function') {
                    this.grid.movable(el, true);
                }
                if (typeof this.grid.resizable === 'function') {
                    this.grid.resizable(el, true);
                }

                this.$nextTick(() => {
                    this.renderWidget(widget.id);
                });
            };

            registerNode();

            if (widget._isNew) {
                setTimeout(() => { widget._isNew = false; }, 2500);
            }
        },

        promptAddSeriesType() {
            this.addSeriesSourceType = 'metric';
            this.addSeriesDerivedMetricId = '';
            this.showAddSeriesTypeModal = true;
        },

        confirmAddSeriesType() {
            if (this.addSeriesSourceType === 'metric') {
                this.addSeriesCard();
                this.showAddSeriesTypeModal = false;
            } else if (this.addSeriesSourceType === 'derived_metric') {
                if (!this.addSeriesDerivedMetricId) return;
                this.addDerivedMetricSeriesCards(this.addSeriesDerivedMetricId);
                this.showAddSeriesTypeModal = false;
            }
        },

        addDerivedMetricSeriesCards(dmId) {
            const dm = (this.derivedMetrics || {})[dmId] || (this.derivedMetrics || {})[String(dmId)];
            if (!dm) return;

            const dmName = dm.name || ('DM #' + dmId);
            const rawSourceSeries = Array.isArray(dm.source_series)
                ? dm.source_series
                : (typeof dm.source_series === 'object' && dm.source_series !== null ? Object.values(dm.source_series) : []);
            const list = [...(this.widgetControlsForm.raw_series || [])];

            if (rawSourceSeries.length === 0) {
                list.push({
                    type: 'derived_metric',
                    dm_id: String(dmId),
                    dm_name: dmName,
                    channel: '',
                    metrics: [],
                    assets: []
                });
            } else {
                rawSourceSeries.forEach((ss, idx) => {
                    const ch = ss.channel || '';
                    const metric = ss.metric || ss.metric_key || ss.metric_name || '';
                    const seriesObj = {
                        type: 'derived_metric',
                        dm_id: String(dmId),
                        dm_name: dmName,
                        dm_series_index: idx,
                        label: ss.label || '',
                        channel: ch,
                        metrics: metric ? [metric] : [],
                        assets: Array.isArray(ss.assets) ? [...ss.assets] : []
                    };
                    list.push(seriesObj);

                    if (ch && !this.allChannelAssets[ch] && this.$wire) {
                        this.$wire.getAssetsForChannel(ch).then(assets => {
                            this.allChannelAssets = { ...this.allChannelAssets, [ch]: assets };
                        });
                    }
                    if (ch && !this.allChannelMetrics[ch] && this.$wire) {
                        this.$wire.getMetricsForChannel(ch).then(metrics => {
                            this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                        });
                    }
                });
            }

            this.widgetControlsForm.raw_series = list;
            this.$nextTick(() => {
                this.updateSeriesScrollState();
                this.scrollSeriesByStep(1);
            });
        },

        addSeriesCard() {
            this.markWidgetControlsDirty();
            const list = [...(this.widgetControlsForm.raw_series || [])];
            list.push({
                type: 'metric',
                channel: this.dashboardControls.channel || '',
                metrics: [],
                assets: []
            });
            this.widgetControlsForm.raw_series = list;
            if (this.dashboardControls.channel) {
                this.onWidgetRawChannelChange(this.widgetControlsForm.raw_series.length - 1);
            }
            this.$nextTick(() => {
                this.updateSeriesScrollState();
                this.scrollSeriesByStep(1);
            });
        },

        removeSeriesCard(index) {
            if (!this.widgetControlsForm.raw_series || index < 0 || !this.widgetControlsForm.raw_series[index]) return;

            const targetSeries = this.widgetControlsForm.raw_series[index];
            if (targetSeries.type === 'derived_metric' && targetSeries.dm_id) {
                this.pendingRemoveDmId = String(targetSeries.dm_id);
                this.pendingRemoveDmName = targetSeries.dm_name || ('DM #' + targetSeries.dm_id);
                this.showRemoveDmSeriesModal = true;
                return;
            }

            this.markWidgetControlsDirty();
            const list = [...this.widgetControlsForm.raw_series];
            list.splice(index, 1);
            this.widgetControlsForm.raw_series = list;
            this.$nextTick(() => {
                this.updateSeriesScrollState();
            });
        },

        confirmRemoveDmSeriesGroup() {
            if (!this.pendingRemoveDmId) {
                this.showRemoveDmSeriesModal = false;
                return;
            }

            this.markWidgetControlsDirty();
            const targetDmId = String(this.pendingRemoveDmId);
            const currentList = this.widgetControlsForm.raw_series || [];
            const filteredList = currentList.filter(s => !(s.type === 'derived_metric' && String(s.dm_id) === targetDmId));

            if (filteredList.length === 0) {
                filteredList.push({
                    type: 'metric',
                    channel: this.dashboardControls.channel || '',
                    metrics: [],
                    assets: []
                });
            }

            this.widgetControlsForm.raw_series = filteredList;
            this.pendingRemoveDmId = '';
            this.pendingRemoveDmName = '';
            this.showRemoveDmSeriesModal = false;

            this.$nextTick(() => {
                this.updateSeriesScrollState();
            });
        },

        updateSeriesScrollState() {
            const el = this.$refs.seriesScrollContainer;
            if (!el) return;
            this.canScrollSeriesLeft = el.scrollLeft > 5;
            this.canScrollSeriesRight = Math.ceil(el.scrollLeft + el.clientWidth) < el.scrollWidth - 5;
        },

        scrollSeriesByStep(direction = 1) {
            const el = this.$refs.seriesScrollContainer;
            if (!el) return;
            const firstCard = el.querySelector('.snap-start');
            const step = firstCard ? (firstCard.offsetWidth + 24) : ((el.clientWidth / 2) + 12);
            el.scrollBy({ left: direction * step, behavior: 'smooth' });
            setTimeout(() => {
                this.updateSeriesScrollState();
            }, 350);
        },

        // ─── Grid ──
        initGrid() {
            if (typeof GridStack === 'undefined') {
                setTimeout(() => this.initGrid(), 50);
                return;
            }

            const container = document.getElementById('grid-stack');
            if (!container) return;

            console.log('[DB][initGrid] initializing', {
                childCount: container.children.length,
                directItems: container.querySelectorAll(':scope > .grid-stack-item').length
            });

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

            // ── Palette drag-in via GridStack native HTML5 drag ──
            GridStack.setupDragIn('.grid-stack-drag-in', { w: 4, h: 3 });

            this.grid.on('dropped', (_event, _previousNode, newNode) => {
                if (!newNode || !newNode.el) return;

                const dragEl = document.querySelector('.grid-stack-drag-in[data-source-type]');
                let sourceType = 'metric';

                const allDragIns = document.querySelectorAll('.grid-stack-drag-in');
                allDragIns.forEach((d) => {
                    if (d.getAttribute('gs-w') == String(newNode.w) && d.getAttribute('gs-h') == String(newNode.h)) {
                        sourceType = d.getAttribute('data-source-type') || sourceType;
                    }
                });

                this.targetGridX = newNode.x;
                this.targetGridY = newNode.y;
                this.pendingDragSourceType = sourceType;

                if (newNode.el && newNode.el.isConnected) {
                    this.grid.removeWidget(newNode.el, false);
                }

                this.openAddWidgetModal(sourceType);
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
                const primaryStrId = String(rawId);

                if (primaryStrId && this.selectedWidgetIds.some(id => String(id) === primaryStrId) && this.selectedWidgetIds.length > 1) {
                    multiDragStartPositions = {};
                    this.selectedWidgetIds.forEach(id => {
                        const strId = String(id);
                        const nodeEl = document.querySelector(`[gs-id="${strId}"], [data-id="${strId}"]`);
                        if (nodeEl && nodeEl.gridstackNode) {
                            multiDragStartPositions[strId] = {
                                x: parseInt(nodeEl.gridstackNode.x, 10) || 0,
                                y: parseInt(nodeEl.gridstackNode.y, 10) || 0,
                                el: nodeEl
                            };
                        }
                    });
                    if (this.grid) this.grid.batchUpdate(true);
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
                    const pStrId = String(node.id || el.getAttribute('gs-id') || el.getAttribute('data-id'));
                    const pStart = multiDragStartPositions[pStrId];
                    if (!pStart) return;

                    const dx = (parseInt(node.x, 10) || 0) - pStart.x;
                    const dy = (parseInt(node.y, 10) || 0) - pStart.y;

                    Object.keys(multiDragStartPositions).forEach(idKey => {
                        const otherId = String(idKey);
                        if (otherId !== pStrId) {
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
                    if (multiDragStartPositions && this.grid) {
                        this.grid.batchUpdate(false);
                    }
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
                if (items && Array.isArray(items)) {
                    items.forEach(item => {
                        const rawId = item.id || (item.el ? (item.el.getAttribute('gs-id') || item.el.getAttribute('data-id')) : null);
                        if (!rawId) return;
                        const widget = (this.widgets || []).find(w => String(w.id) === String(rawId));
                        if (widget) {
                            widget.grid_x = parseInt(item.x, 10) || 0;
                            widget.grid_y = parseInt(item.y, 10) || 0;
                            widget.grid_w = parseInt(item.w, 10) || 4;
                            widget.grid_h = parseInt(item.h, 10) || 3;
                        }
                    });
                }
                const currentSignature = JSON.stringify(this.getLayout());
                if (!this._initialLayoutSignature) {
                    this._initialLayoutSignature = currentSignature;
                    return;
                }
                this.isDirty = (currentSignature !== this._initialLayoutSignature);
            });

            this.grid.on('resizestop', (event, el) => {
                if (!el) return;
                const contentEl = el.querySelector('.widget-content');
                if (contentEl && window.dashboardRenderer?._chartInstances?.has(contentEl)) {
                    const chart = window.dashboardRenderer._chartInstances.get(contentEl);
                    if (chart && typeof chart.resize === 'function') {
                        chart.resize();
                    }
                }
                window.dispatchEvent(new Event('resize'));
            });

            this.registerGridItemsObserver();
            this.syncExistingGridItems();

            console.log('[DB][initGrid] initialized', {
                grid: !!this.grid,
                items: container.querySelectorAll(':scope > .grid-stack-item').length,
                nodes: Array.from(container.querySelectorAll(':scope > .grid-stack-item')).map(el => ({
                    id: el.getAttribute('gs-id') || el.getAttribute('data-id'),
                    node: !!el.gridstackNode
                })),
                observerRegistered: !!this._gridItemsObserver
            });

            setTimeout(() => {
                this._initialLayoutSignature = JSON.stringify(this.getLayout());
                this.isDirty = false;
            }, 500);
        },

        syncExistingGridItems() {
            if (!this.grid) return;
            const container = document.getElementById('grid-stack');
            if (!container) return;
            const items = container.querySelectorAll(':scope > .grid-stack-item');
            console.log('[DB][syncExistingGridItems] found', items.length, 'direct items');
            items.forEach(el => {
                const rawId = el.getAttribute('gs-id') || el.getAttribute('data-id');
                if (rawId === null || rawId === undefined) return;
                const widget = (this.widgets || []).find(w => String(w.id) === String(rawId));
                if (widget) {
                    this.initGridItem(el, widget);
                } else {
                    console.log('[DB][syncExistingGridItems] NO widget match for el', { rawId });
                }
            });
        },

        registerGridItemsObserver() {
            if (this._gridItemsObserver || !this.grid) return;
            const container = document.getElementById('grid-stack');
            if (!container) return;

            this._gridItemsObserver = new MutationObserver((mutations) => {
                if (!this.grid) return;
                mutations.forEach((mutation) => {
                    if (mutation.type !== 'childList') return;
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType !== 1 || !node.classList || !node.classList.contains('grid-stack-item')) return;
                        if (node.gridstackNode) return;
                        const rawId = node.getAttribute('gs-id') || node.getAttribute('data-id');
                        if (rawId === null || rawId === undefined) return;
                        const widget = (this.widgets || []).find(w => String(w.id) === String(rawId));
                        console.log('[DB][observer] added grid-stack-item', {
                            rawId,
                            widgetFound: !!widget,
                            widgetId: widget ? widget.id : null,
                            elHasNode: !!node.gridstackNode,
                            parent: node.parentElement ? node.parentElement.id : null,
                            connected: node.isConnected,
                            isNew: widget ? !!widget._isNew : false
                        });
                        if (widget) {
                            this.initGridItem(node, widget);
                        }
                    });
                });
            });

            this._gridItemsObserver.observe(container, { childList: true });
            console.log('[DB][observer] registered on #grid-stack');

            this.positionPalette();
            this._paletteRAF = null;
            this._onPaletteReposition = () => {
                if (this._paletteRAF) return;
                this._paletteRAF = requestAnimationFrame(() => {
                    this._paletteRAF = null;
                    this.positionPalette();
                });
            };
            window.addEventListener('resize', this._onPaletteReposition, { passive: true });
            window.addEventListener('scroll', this._onPaletteReposition, { passive: true });

            const paletteEl = document.querySelector('.bd-palette-left');
            if (paletteEl) {
                const observePalette = () => {
                    const el = document.querySelector('.bd-palette-left');
                    if (!el) return;
                    if (this._paletteObserver) this._paletteObserver.disconnect();
                    this._paletteObserver = new MutationObserver(() => {
                        if (!this._repositioningPalette) this.positionPalette();
                    });
                    this._paletteObserver.observe(el, { attributes: true, attributeFilter: ['style'] });
                };
                observePalette();
                if (paletteEl.parentElement) {
                    new MutationObserver(observePalette).observe(paletteEl.parentElement, { childList: true, subtree: true });
                }
            }
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
                const id = parseInt(rawId, 10) || 0;
                const x = parseInt(node.x, 10) || 0;
                const y = parseInt(node.y, 10) || 0;
                const w = parseInt(node.w, 10) || 4;
                const h = parseInt(node.h, 10) || 3;

                const widget = (this.widgets || []).find(w => String(w.id) === String(id));
                if (widget) {
                    widget.grid_x = x;
                    widget.grid_y = y;
                    widget.grid_w = w;
                    widget.grid_h = h;
                }

                return { id, x, y, w, h };
            }).filter(node => node.id !== 0)
              .sort((a, b) => a.id - b.id);
        },

        reloadGrid() {
            if (this._gridItemsObserver) {
                this._gridItemsObserver.disconnect();
                this._gridItemsObserver = null;
            }
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
            if (!this.dashboardControls.asset_group) {
                this.dashboardControls.asset_group = [];
            } else if (!Array.isArray(this.dashboardControls.asset_group)) {
                this.dashboardControls.asset_group = [String(this.dashboardControls.asset_group)];
            }
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

            const rawGroup = c.asset_group;
            const assetGroupArray = Array.isArray(rawGroup) ? rawGroup.map(String) : (rawGroup ? [String(rawGroup)] : []);

            const payload = {
                date_start: c.date_start || '',
                date_end: c.date_end || '',
                zero_handling: c.zero_handling || 'remove',
                granularity: c.granularity || 'daily',
                edge_case_weighted: c.edge_case_weighted !== undefined ? !!c.edge_case_weighted : true,
                edge_case_grouping: c.edge_case_grouping || 'none',
                asset_group: assetGroupArray,
                show_asset_group_selector: c.show_asset_group_selector === true,
                allow_pdf_export: c.allow_pdf_export === true,
                pdf_export_roles: Array.isArray(c.pdf_export_roles) ? c.pdf_export_roles : [],
            };
            if (this.$wire) {
                this.$wire.saveDashboardControls(payload).then(() => {
                    this.dashboardControls = { ...payload };
                });
            }
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
            this.hasUserInteractedWithWidgetControls = false;

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

            const validTitle = (widget.title && widget.title !== '[]' && widget.title !== '{}') ? widget.title : (widget.name || '');
            const validDesc = (widget.description && widget.description !== '[]' && widget.description !== '{}') ? widget.description : '';

            const baseTitle = this.parseLocalizedValue(validTitle);
            const baseDesc = this.parseLocalizedValue(validDesc);

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
                series_dependencies: wc.series_dependencies ? { ...wc.series_dependencies } : {},
                edge_case_inherit: wc.edge_case_weighted === undefined && wc.edge_case_grouping === undefined,
                edge_case_weighted: wc.edge_case_weighted !== undefined ? wc.edge_case_weighted : (this.dashboardControls.edge_case_weighted ?? true),
                edge_case_grouping: wc.edge_case_grouping !== undefined ? wc.edge_case_grouping : (this.dashboardControls.edge_case_grouping || 'none'),
                max_ratio_inherit: wc.max_ratio === undefined,
                max_ratio: wc.max_ratio !== undefined ? wc.max_ratio : null,
                block_first_col: wc.block_first_col !== undefined ? !!wc.block_first_col : true,
                raw_series: [],
                dm_assets: wc.dm_assets || {},
                combo_chart_config: wc.combo_chart_config ? JSON.parse(JSON.stringify(wc.combo_chart_config)) : {},
            };

            if (widget.source_type !== 'kpi') {
                if (wc.raw_series && Array.isArray(wc.raw_series) && wc.raw_series.length > 0) {
                    this.widgetControlsForm.raw_series = wc.raw_series.map((s, sIdx) => {
                        const rawAllowed = Array.isArray(s.allowed_metrics) ? [...s.allowed_metrics] : (Array.isArray(s.metrics) ? [...s.metrics] : (s.metric ? [s.metric] : []));
                        const rawSelected = Array.isArray(s.metrics) ? [...s.metrics] : (s.metric ? [s.metric] : []);
                        const rawAllowedAssets = Array.isArray(s.allowed_assets) ? [...s.allowed_assets] : (Array.isArray(s.assets) ? [...s.assets] : []);
                        const rawSelectedAssets = Array.isArray(s.assets) ? [...s.assets] : [];
                        let rawDep = s.dependency || (wc.series_dependencies && (wc.series_dependencies[sIdx] || wc.series_dependencies[String(sIdx)])) || '';
                        if (!rawDep && s.channel === (wc.channel || this.dashboardControls?.channel)) {
                            rawDep = wc.dependency || '';
                        }
                        return {
                            type: s.type || (s.dm_id ? 'derived_metric' : 'metric'),
                            dm_id: s.dm_id ? String(s.dm_id) : undefined,
                            dm_name: s.dm_name || undefined,
                            dm_series_index: s.dm_series_index !== undefined ? s.dm_series_index : undefined,
                            label: s.label || '',
                            channel: s.channel || '',
                            dependency: rawDep,
                            allowed_metrics: rawAllowed,
                            metrics: rawSelected.length > 0 ? rawSelected : [...rawAllowed],
                            allowed_assets: rawAllowedAssets,
                            assets: rawSelectedAssets.length > 0 ? rawSelectedAssets : [...rawAllowedAssets]
                        };
                    });
                } else if (wc.series_channels && Object.keys(wc.series_channels).length > 0) {
                    const seriesKeys = Object.keys(wc.series_channels);
                    const groupedSeries = [];
                    seriesKeys.forEach((key, sIdx) => {
                        const channel = wc.series_channels[key] || wc.channel || '';
                        const assets = (wc.series_assets && wc.series_assets[key]) ? [...wc.series_assets[key]] : (wc.assets ? [...wc.assets] : []);
                        let metrics = [];
                        if (Array.isArray(wc.metrics)) {
                            if (seriesKeys.length === 1) {
                                metrics = [...wc.metrics];
                            } else if (Array.isArray(wc.metrics[key])) {
                                metrics = [...wc.metrics[key]];
                            } else if (wc.metrics[key]) {
                                metrics = [wc.metrics[key]];
                            } else {
                                metrics = [...wc.metrics];
                            }
                        } else if (wc.metrics && typeof wc.metrics === 'object') {
                            metrics = Array.isArray(wc.metrics[key]) ? [...wc.metrics[key]] : (wc.metrics[key] ? [wc.metrics[key]] : []);
                        }
                        const allowed = (wc.series_allowed_metrics && wc.series_allowed_metrics[key]) ? [...wc.series_allowed_metrics[key]] : [...metrics];
                        let rawDep = (wc.series_dependencies && (wc.series_dependencies[key] || wc.series_dependencies[sIdx])) || '';
                        if (!rawDep && channel === (wc.channel || this.dashboardControls?.channel)) {
                            rawDep = wc.dependency || '';
                        }
                        groupedSeries.push({
                            channel,
                            dependency: rawDep,
                            allowed_metrics: allowed,
                            metrics,
                            assets
                        });
                    });
                    this.widgetControlsForm.raw_series = groupedSeries;
                } else if (wc.metrics && Array.isArray(wc.metrics) && wc.metrics.length > 0) {
                    this.widgetControlsForm.raw_series = [{
                        channel: wc.channel || '',
                        dependency: (wc.series_dependencies && (wc.series_dependencies[0] || wc.series_dependencies['0'])) || wc.dependency || '',
                        allowed_metrics: [...wc.metrics],
                        metrics: [...wc.metrics],
                        assets: wc.assets ? [...wc.assets] : []
                    }];
                }

                if (this.widgetControlsForm.raw_series.length === 0) {
                    this.widgetControlsForm.raw_series.push({ channel: wc.channel || '', dependency: '', allowed_metrics: [], metrics: [], assets: wc.assets || [] });
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
                        if (ch && !this.allChannelDependencies[ch]) {
                            this.$wire.getDependenciesForChannel(ch).then(deps => {
                                this.allChannelDependencies = { ...this.allChannelDependencies, [ch]: deps };
                                if (deps && Object.keys(deps).length > 0 && !series.dependency) {
                                    series.dependency = Object.keys(deps)[0];
                                }
                                this.$wire.getMetricsForChannel(ch, wc.granularity, series.dependency).then(metrics => {
                                    if (!this.widgetControlsForm.series_metrics_map) {
                                        this.widgetControlsForm.series_metrics_map = {};
                                    }
                                    this.widgetControlsForm.series_metrics_map = {
                                        ...this.widgetControlsForm.series_metrics_map,
                                        [idx]: metrics
                                    };
                                    this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                                });
                            });
                        } else {
                            if (ch && this.allChannelDependencies[ch] && Object.keys(this.allChannelDependencies[ch]).length > 0 && !series.dependency) {
                                series.dependency = Object.keys(this.allChannelDependencies[ch])[0];
                            }
                            if (ch) {
                                this.$wire.getMetricsForChannel(ch, wc.granularity, series.dependency).then(metrics => {
                                    if (!this.widgetControlsForm.series_metrics_map) {
                                        this.widgetControlsForm.series_metrics_map = {};
                                    }
                                    this.widgetControlsForm.series_metrics_map = {
                                        ...this.widgetControlsForm.series_metrics_map,
                                        [idx]: metrics
                                    };
                                    this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                                });
                            }
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

            if (widget.source_type === 'derived_metric' && widget.source_config?.derived_metric_id && this.$wire) {
                const dm = (this.derivedMetrics || {})[widget.source_config.derived_metric_id];
                if (dm) {
                    widget.dmSourceSeries = dm.source_series || [];
                    widget.dmSourceSeries.forEach((series) => {
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

            if (widget.source_type === 'kpi' && widget.source_config && widget.source_config.custom_kpi_id && this.$wire) {
                this.$wire.getKpiConfiguration(widget.source_config.custom_kpi_id).then(config => {
                    const uiState = config?.filters?._ui_state || config;
                    this.widgetKpiConfig = {
                        dependent_channel: uiState.dependent_channel,
                        dependent_metric: uiState.dependent_metric,
                        dependent_dm_id: uiState.dependent_dm_id,
                        dependent_asset_group: uiState.dependent_asset_group,
                        dependent_asset_filter: uiState.dependent_asset_filter,
                        independent_variables: uiState.independent_variables || {},
                    };
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

                    if (!this.widgetControlsForm.series_allowed_assets) this.widgetControlsForm.series_allowed_assets = {};
                    if (!this.widgetControlsForm.series_assets.dependent) {
                        this.widgetControlsForm.series_assets.dependent = (wc.series_assets && wc.series_assets.dependent)
                            ? [...wc.series_assets.dependent]
                            : (uiState.dependent_asset_filter ? (Array.isArray(uiState.dependent_asset_filter) ? [...uiState.dependent_asset_filter] : [uiState.dependent_asset_filter]) : []);
                    }
                    if (!this.widgetControlsForm.series_allowed_assets.dependent) {
                        this.widgetControlsForm.series_allowed_assets.dependent = (wc.series_allowed_assets && wc.series_allowed_assets.dependent)
                            ? [...wc.series_allowed_assets.dependent]
                            : [...(this.widgetControlsForm.series_assets.dependent || [])];
                    }
                    if (this.widgetControlsForm.series_asset_groups.dependent === undefined) {
                        this.widgetControlsForm.series_asset_groups.dependent = (wc.series_asset_groups && wc.series_asset_groups.dependent !== undefined)
                            ? wc.series_asset_groups.dependent
                            : ((wc.series_assets && wc.series_assets.dependent) ? '' : (uiState.dependent_asset_group || ''));
                    }
                    if (!this.widgetControlsForm.series_dependencies) this.widgetControlsForm.series_dependencies = {};
                    if (!this.widgetControlsForm.series_dependencies.dependent && uiState.dependent_dependency) {
                        this.widgetControlsForm.series_dependencies.dependent = uiState.dependent_dependency;
                    }
                    if (this.widgetKpiConfig.independent_variables) {
                        for (let key in this.widgetKpiConfig.independent_variables) {
                            const indKey = 'independent_' + key;
                            if (!this.widgetControlsForm.series_assets[indKey]) {
                                const ivFilter = this.widgetKpiConfig.independent_variables[key]?.independent_asset_filter;
                                this.widgetControlsForm.series_assets[indKey] = (wc.series_assets && wc.series_assets[indKey])
                                    ? [...wc.series_assets[indKey]]
                                    : (ivFilter ? (Array.isArray(ivFilter) ? [...ivFilter] : [ivFilter]) : []);
                            }
                            if (!this.widgetControlsForm.series_allowed_assets[indKey]) {
                                this.widgetControlsForm.series_allowed_assets[indKey] = (wc.series_allowed_assets && wc.series_allowed_assets[indKey])
                                    ? [...wc.series_allowed_assets[indKey]]
                                    : [...(this.widgetControlsForm.series_assets[indKey] || [])];
                            }
                            if (this.widgetControlsForm.series_asset_groups[indKey] === undefined) {
                                this.widgetControlsForm.series_asset_groups[indKey] = (wc.series_asset_groups && wc.series_asset_groups[indKey] !== undefined)
                                    ? wc.series_asset_groups[indKey]
                                    : ((wc.series_assets && wc.series_assets[indKey]) ? '' : (this.widgetKpiConfig.independent_variables[key]?.independent_asset_group || ''));
                            }
                            if (!this.widgetControlsForm.series_dependencies[indKey] && this.widgetKpiConfig.independent_variables[key]?.independent_dependency) {
                                this.widgetControlsForm.series_dependencies[indKey] = this.widgetKpiConfig.independent_variables[key].independent_dependency;
                            }
                        }
                    }

                    const initDmKpiAssets = (prefix, dmId) => {
                        const dm = this.derivedMetrics?.[dmId];
                        if (dm && dm.source_series) {
                            dm.source_series.forEach((_, sIdx) => {
                                const k = prefix + '_dm_' + sIdx;
                                if (!this.widgetControlsForm.series_assets[k]) {
                                    this.widgetControlsForm.series_assets[k] = (wc.series_assets && wc.series_assets[k])
                                        ? [...wc.series_assets[k]]
                                        : [];
                                }
                                if (!this.widgetControlsForm.series_allowed_assets[k]) {
                                    this.widgetControlsForm.series_allowed_assets[k] = (wc.series_allowed_assets && wc.series_allowed_assets[k])
                                        ? [...wc.series_allowed_assets[k]]
                                        : [...(this.widgetControlsForm.series_assets[k] || [])];
                                }
                            });
                        }
                    };
                    if (this.widgetKpiConfig.dependent_dm_id) {
                        initDmKpiAssets('dep', this.widgetKpiConfig.dependent_dm_id);
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
                        if (!this.allChannelDependencies[ch]) {
                            this.$wire.getDependenciesForChannel(ch).then(deps => {
                                this.allChannelDependencies = { ...this.allChannelDependencies, [ch]: deps };
                            });
                        }
                        if (!this.allChannelMetrics[ch]) {
                            this.$wire.getMetricsForChannel(ch).then(metrics => {
                                this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                                if (this.widgetKpiConfig.dependent_channel === ch && !this.widgetKpiConfig.dependent_metric && metrics && Object.keys(metrics).length > 0) {
                                    if (!this.widgetControlsForm.metrics[0]) {
                                        const firstMetric = Object.keys(metrics)[0];
                                        this.widgetControlsForm.metrics[0] = firstMetric;
                                    }
                                }
                                if (this.widgetKpiConfig.independent_variables) {
                                    for (let key in this.widgetKpiConfig.independent_variables) {
                                        const v = this.widgetKpiConfig.independent_variables[key];
                                        const targetIdx = parseInt(key) + 1;
                                        if (v.independent_channel === ch && !v.independent_metric && metrics && Object.keys(metrics).length > 0) {
                                            if (!this.widgetControlsForm.metrics[targetIdx]) {
                                                const firstMetric = Object.keys(metrics)[0];
                                                this.widgetControlsForm.metrics[targetIdx] = firstMetric;
                                            }
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
                    this.captureWidgetControlsSnapshot();
                    this.$nextTick(() => {
                        this.captureWidgetControlsSnapshot();
                        const el = this.$refs.seriesScrollContainer;
                        if (el) el.scrollLeft = 0;
                        this.updateSeriesScrollState();
                    });
                }).catch(err => {
                    console.error('[dashboard-builder] getKpiConfiguration failed:', err);
                    this.loadWidgetMetrics(savedMetrics);
                    this.updateDependenciesAndGranularities(wc.dependency, wc.granularity);
                    this.showWidgetControls = true;
                    this.captureWidgetControlsSnapshot();
                    this.$nextTick(() => {
                        this.captureWidgetControlsSnapshot();
                        const el = this.$refs.seriesScrollContainer;
                        if (el) el.scrollLeft = 0;
                        this.updateSeriesScrollState();
                    });
                });
            } else {
                this.loadWidgetMetrics(savedMetrics);
                this.updateDependenciesAndGranularities(wc.dependency, wc.granularity);
                this.showWidgetControls = true;
                this.captureWidgetControlsSnapshot();
                this.$nextTick(() => {
                    this.captureWidgetControlsSnapshot();
                    const el = this.$refs.seriesScrollContainer;
                    if (el) el.scrollLeft = 0;
                    this.updateSeriesScrollState();
                });
            }
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
            if (!this.widgetControlsForm.raw_series || !this.widgetControlsForm.raw_series[index]) return;
            const series = this.widgetControlsForm.raw_series[index];
            const ch = series.channel;

            // Reset selected metrics, allowed metrics, dependency, and assets because channel changed
            series.allowed_metrics = [];
            series.metrics = [];
            series.dependency = '';
            series.assets = [];

            if (index === 0) {
                this.widgetControlsForm.channel = ch;
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
            if (ch && !this.allChannelDependencies[ch] && this.$wire) {
                this.$wire.getDependenciesForChannel(ch).then(deps => {
                    this.allChannelDependencies = { ...this.allChannelDependencies, [ch]: deps };
                    if (deps && Object.keys(deps).length > 0 && !series.dependency) {
                        series.dependency = Object.keys(deps)[0];
                    }
                    this.$wire.getMetricsForChannel(ch, this.widgetControlsForm.granularity, series.dependency).then(metrics => {
                        if (!this.widgetControlsForm.series_metrics_map) {
                            this.widgetControlsForm.series_metrics_map = {};
                        }
                        this.widgetControlsForm.series_metrics_map = {
                            ...this.widgetControlsForm.series_metrics_map,
                            [index]: metrics
                        };
                        this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                    });
                });
            } else if (ch && this.allChannelDependencies[ch]) {
                const deps = this.allChannelDependencies[ch];
                if (deps && Object.keys(deps).length > 0 && !series.dependency) {
                    series.dependency = Object.keys(deps)[0];
                }
                if (this.$wire) {
                    this.$wire.getMetricsForChannel(ch, this.widgetControlsForm.granularity, series.dependency).then(metrics => {
                        if (!this.widgetControlsForm.series_metrics_map) {
                            this.widgetControlsForm.series_metrics_map = {};
                        }
                        this.widgetControlsForm.series_metrics_map = {
                            ...this.widgetControlsForm.series_metrics_map,
                            [index]: metrics
                        };
                        this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                    });
                }
            } else if (ch && this.$wire) {
                this.$wire.getMetricsForChannel(ch, this.widgetControlsForm.granularity, series.dependency).then(metrics => {
                    if (!this.widgetControlsForm.series_metrics_map) {
                        this.widgetControlsForm.series_metrics_map = {};
                    }
                    this.widgetControlsForm.series_metrics_map = {
                        ...this.widgetControlsForm.series_metrics_map,
                        [index]: metrics
                    };
                    this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: metrics };
                });
            }
        },

        toggleRawMetricIncluded(index, metricKey) {
            this.markWidgetControlsDirty();
            const series = this.widgetControlsForm.raw_series[index];
            if (!series) return;
            if (!Array.isArray(series.allowed_metrics)) series.allowed_metrics = [];
            if (!Array.isArray(series.metrics)) series.metrics = [];

            if (series.allowed_metrics.includes(metricKey)) {
                // Remove from allowed and active
                series.allowed_metrics = series.allowed_metrics.filter(m => m !== metricKey);
                series.metrics = series.metrics.filter(m => m !== metricKey);
            } else {
                // Add to allowed and by default mark as active
                series.allowed_metrics = [...series.allowed_metrics, metricKey];
                if (!series.metrics.includes(metricKey)) {
                    series.metrics = [...series.metrics, metricKey];
                }
            }
        },

        toggleRawMetricDefaultActive(index, metricKey) {
            this.markWidgetControlsDirty();
            const series = this.widgetControlsForm.raw_series[index];
            if (!series) return;
            if (!Array.isArray(series.allowed_metrics)) series.allowed_metrics = [];
            if (!Array.isArray(series.metrics)) series.metrics = [];

            // If not included yet, include it first
            if (!series.allowed_metrics.includes(metricKey)) {
                series.allowed_metrics = [...series.allowed_metrics, metricKey];
                series.metrics = [...series.metrics, metricKey];
                return;
            }

            // Toggle active state
            if (series.metrics.includes(metricKey)) {
                series.metrics = series.metrics.filter(m => m !== metricKey);
            } else {
                series.metrics = [...series.metrics, metricKey];
            }
        },

        selectAllRawMetrics(index) {
            this.markWidgetControlsDirty();
            const series = this.widgetControlsForm.raw_series[index];
            if (!series || !series.channel) return;
            const availableKeys = Object.keys(this.allChannelMetrics[series.channel] || {});
            series.allowed_metrics = [...availableKeys];
            series.metrics = [...availableKeys];
        },

        toggleRawMetricComboType(index, metricKey) {
            this.markWidgetControlsDirty();
            if (!this.widgetControlsForm.combo_chart_config) {
                this.widgetControlsForm.combo_chart_config = {};
            }
            const cfgKey = index + '_' + metricKey;
            const current = this.widgetControlsForm.combo_chart_config[cfgKey]?.type || this.getRawMetricDefaultComboType(index, metricKey);
            const nextType = current === 'bar' ? 'line' : 'bar';
            
            this.widgetControlsForm.combo_chart_config = {
                ...this.widgetControlsForm.combo_chart_config,
                [cfgKey]: {
                    ...(this.widgetControlsForm.combo_chart_config[cfgKey] || {}),
                    type: nextType
                }
            };
        },

        getRawMetricComboType(index, metricKey) {
            const cfgKey = index + '_' + metricKey;
            return this.widgetControlsForm?.combo_chart_config?.[cfgKey]?.type || this.getRawMetricDefaultComboType(index, metricKey);
        },

        getRawMetricDefaultComboType(index, metricKey) {
            const m = String(metricKey || '').toLowerCase();
            const rateKeywords = ['roas', 'cpc', 'cpm', 'ctr', 'rate', 'aov', 'frequency', 'cost_per', 'percentage', 'ratio', 'bounce'];
            if (rateKeywords.some(kw => m.includes(kw))) return 'line';
            if (['spend', 'cost', 'revenue'].some(kw => m.includes(kw))) return 'bar';
            return index === 0 ? 'bar' : 'line';
        },

        clearAllRawMetrics(index) {
            this.markWidgetControlsDirty();
            const series = this.widgetControlsForm.raw_series[index];
            if (!series) return;
            series.allowed_metrics = [];
            series.metrics = [];
        },

        toggleRawAssetIncluded(index, assetId) {
            this.markWidgetControlsDirty();
            const series = this.widgetControlsForm.raw_series[index];
            if (!series) return;
            const strId = String(assetId);
            if (!Array.isArray(series.allowed_assets)) {
                series.allowed_assets = Array.isArray(series.assets) ? [...series.assets] : [];
            }
            if (!Array.isArray(series.assets)) series.assets = [];

            if (series.allowed_assets.includes(strId)) {
                // Remove from allowed and active
                series.allowed_assets = series.allowed_assets.filter(a => a !== strId);
                series.assets = series.assets.filter(a => a !== strId);
            } else {
                // Add to allowed and mark as active by default
                series.allowed_assets = [...series.allowed_assets, strId];
                if (!series.assets.includes(strId)) {
                    series.assets = [...series.assets, strId];
                }
            }
        },

        toggleRawAssetDefaultActive(index, assetId) {
            this.markWidgetControlsDirty();
            const series = this.widgetControlsForm.raw_series[index];
            if (!series) return;
            const strId = String(assetId);
            if (!Array.isArray(series.allowed_assets)) {
                series.allowed_assets = Array.isArray(series.assets) ? [...series.assets] : [];
            }
            if (!Array.isArray(series.assets)) series.assets = [];

            // If not included yet, include it first
            if (!series.allowed_assets.includes(strId)) {
                series.allowed_assets = [...series.allowed_assets, strId];
                series.assets = [...series.assets, strId];
                return;
            }

            // Toggle active state
            if (series.assets.includes(strId)) {
                series.assets = series.assets.filter(a => a !== strId);
            } else {
                series.assets = [...series.assets, strId];
            }
        },

        toggleRawAsset(index, id) {
            this.toggleRawAssetIncluded(index, id);
        },

        selectAllRawAssets(index) {
            this.markWidgetControlsDirty();
            const ch = this.widgetControlsForm.raw_series[index].channel;
            const assets = this.allChannelAssets[ch] || {};
            let validIds = Object.keys(assets).map(String);
            const globalGroup = this.dashboardControls?.asset_group;
            if (globalGroup && this.allChannelAssetGroups[ch]?.[globalGroup]) {
                const groupAssets = this.allChannelAssetGroups[ch][globalGroup].assets.map(String);
                validIds = validIds.filter(id => groupAssets.includes(id));
            }
            this.widgetControlsForm.raw_series[index].allowed_assets = [...validIds];
            this.widgetControlsForm.raw_series[index].assets = [...validIds];
        },

        selectAllKpiAssets(seriesKey, channel) {
            this.markWidgetControlsDirty();
            const assets = this.allChannelAssets[channel] || {};
            let validIds = Object.keys(assets).map(String);
            const globalGroup = this.dashboardControls?.asset_group;
            if (globalGroup && this.allChannelAssetGroups[channel]?.[globalGroup]) {
                const groupAssets = this.allChannelAssetGroups[channel][globalGroup].assets.map(String);
                validIds = validIds.filter(id => groupAssets.includes(id));
            }
            if (!this.widgetControlsForm.series_allowed_assets) this.widgetControlsForm.series_allowed_assets = {};
            if (!this.widgetControlsForm.series_assets) this.widgetControlsForm.series_assets = {};
            this.widgetControlsForm.series_allowed_assets[seriesKey] = [...validIds];
            this.widgetControlsForm.series_assets[seriesKey] = [...validIds];
        },

        toggleKpiAssetIncluded(seriesKey, id) {
            this.markWidgetControlsDirty();
            if (!this.widgetControlsForm.series_allowed_assets) this.widgetControlsForm.series_allowed_assets = {};
            if (!this.widgetControlsForm.series_assets) this.widgetControlsForm.series_assets = {};

            const allowed = Array.isArray(this.widgetControlsForm.series_allowed_assets[seriesKey])
                ? this.widgetControlsForm.series_allowed_assets[seriesKey]
                : (Array.isArray(this.widgetControlsForm.series_assets[seriesKey]) ? [...this.widgetControlsForm.series_assets[seriesKey]] : []);
            const active = Array.isArray(this.widgetControlsForm.series_assets[seriesKey])
                ? this.widgetControlsForm.series_assets[seriesKey]
                : [];
            const strId = String(id);

            if (allowed.includes(strId)) {
                this.widgetControlsForm.series_allowed_assets[seriesKey] = allowed.filter(a => a !== strId);
                this.widgetControlsForm.series_assets[seriesKey] = active.filter(a => a !== strId);
            } else {
                this.widgetControlsForm.series_allowed_assets[seriesKey] = [...allowed, strId];
                if (!active.includes(strId)) {
                    this.widgetControlsForm.series_assets[seriesKey] = [...active, strId];
                }
            }
        },

        toggleKpiAssetDefaultActive(seriesKey, id) {
            this.markWidgetControlsDirty();
            if (!this.widgetControlsForm.series_allowed_assets) this.widgetControlsForm.series_allowed_assets = {};
            if (!this.widgetControlsForm.series_assets) this.widgetControlsForm.series_assets = {};

            const allowed = Array.isArray(this.widgetControlsForm.series_allowed_assets[seriesKey])
                ? this.widgetControlsForm.series_allowed_assets[seriesKey]
                : (Array.isArray(this.widgetControlsForm.series_assets[seriesKey]) ? [...this.widgetControlsForm.series_assets[seriesKey]] : []);
            const active = Array.isArray(this.widgetControlsForm.series_assets[seriesKey])
                ? this.widgetControlsForm.series_assets[seriesKey]
                : [];
            const strId = String(id);

            if (!allowed.includes(strId)) {
                this.widgetControlsForm.series_allowed_assets[seriesKey] = [...allowed, strId];
                this.widgetControlsForm.series_assets[seriesKey] = [...active, strId];
                return;
            }

            if (active.includes(strId)) {
                this.widgetControlsForm.series_assets[seriesKey] = active.filter(a => a !== strId);
            } else {
                this.widgetControlsForm.series_assets[seriesKey] = [...active, strId];
            }
        },

        toggleKpiAsset(seriesKey, id) {
            this.toggleKpiAssetIncluded(seriesKey, id);
        },

        toggleDmAsset(index, id) {
            this.markWidgetControlsDirty();
            const current = this.widgetControlsForm.dm_assets[index] || [];
            const strId = String(id);
            if (current.includes(strId)) {
                this.widgetControlsForm.dm_assets[index] = current.filter(a => a !== strId);
            } else {
                this.widgetControlsForm.dm_assets[index] = [...current, strId];
            }
        },

        selectAllDmAssets(index) {
            this.markWidgetControlsDirty();
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
            this.markWidgetControlsDirty();
            const series = this.widgetControlsForm.raw_series[index];
            if (!series) return;
            series.allowed_assets = [];
            series.assets = [];
        },

        clearAllDmAssets(index) {
            this.markWidgetControlsDirty();
            this.widgetControlsForm.dm_assets[index] = [];
        },

        clearAllKpiAssets(seriesKey) {
            this.markWidgetControlsDirty();
            if (this.widgetControlsForm.series_allowed_assets) {
                this.widgetControlsForm.series_allowed_assets[seriesKey] = [];
            }
            this.widgetControlsForm.series_assets[seriesKey] = [];
        },

        // ─── Asset Group Helpers ───
        getEffectiveGroup(seriesKey, channel) {
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
            this.markWidgetControlsDirty();
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
                payload.series_allowed_assets = {};
                payload.series_channels = {};
                payload.series_dependencies = {};
                payload.series_allowed_metrics = {};

                c.raw_series.forEach((s, sIdx) => {
                    const metricsToSave = (Array.isArray(s.metrics) && s.metrics.length > 0)
                        ? s.metrics.filter(m => m !== '')
                        : [];

                    metricsToSave.forEach(m => {
                        payload.metrics.push(m);
                    });

                    let channelAssets = this.allChannelAssets[s.channel] || {};
                    let validAssets = [...(s.assets || [])];
                    let validAllowedAssets = Array.isArray(s.allowed_assets) ? [...s.allowed_assets] : [...validAssets];
                    if (Object.keys(channelAssets).length > 0) {
                        validAssets = validAssets.filter(id => channelAssets[id] !== undefined || channelAssets[String(id)] !== undefined);
                        validAllowedAssets = validAllowedAssets.filter(id => channelAssets[id] !== undefined || channelAssets[String(id)] !== undefined);
                    }

                    payload.series_assets[sIdx] = validAssets;
                    payload.series_allowed_assets[sIdx] = validAllowedAssets;
                    payload.series_channels[sIdx] = s.channel || '';
                    payload.series_dependencies[sIdx] = s.dependency || '';
                    payload.series_allowed_metrics[sIdx] = Array.isArray(s.allowed_metrics) ? [...s.allowed_metrics] : [];
                });
                payload.raw_series = c.raw_series.map(s => ({
                    type: s.type || (s.dm_id ? 'derived_metric' : 'metric'),
                    dm_id: s.dm_id ? String(s.dm_id) : undefined,
                    dm_name: s.dm_name || undefined,
                    dm_series_index: s.dm_series_index !== undefined ? s.dm_series_index : undefined,
                    label: s.label || '',
                    channel: s.channel || '',
                    dependency: s.dependency || '',
                    allowed_metrics: Array.isArray(s.allowed_metrics) ? [...s.allowed_metrics] : [],
                    metrics: Array.isArray(s.metrics) ? [...s.metrics] : [],
                    allowed_assets: Array.isArray(s.allowed_assets) ? [...s.allowed_assets] : (Array.isArray(s.assets) ? [...s.assets] : []),
                    assets: Array.isArray(s.assets) ? [...s.assets] : []
                }));
                if (payload.series_channels['0']) {
                    payload.channel = payload.series_channels['0'];
                }
            } else {
                payload.channel = c.channel;
                payload.assets = c.assets;
                payload.metrics = c.metrics;
                payload.series_assets = c.series_assets || {};
                payload.series_allowed_assets = c.series_allowed_assets || {};
                payload.series_asset_groups = c.series_asset_groups || {};
                payload.series_dependencies = c.series_dependencies || {};
            }

            if (c.combo_chart_config && Object.keys(c.combo_chart_config).length > 0) {
                payload.combo_chart_config = c.combo_chart_config;
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
            this.hasUserInteractedWithWidgetControls = false;
            this.widgetControlsSnapshot = null;

            const idx = this.widgets.findIndex(w => w.id === this.widgetControlsTarget.id);
            if (idx !== -1) {
                this.widgets[idx].controls = payload;
                this.widgets[idx].titles = titles;
                this.widgets[idx].descriptions = descriptions;
                this.widgets[idx].title = fallbackTitle;
                this.widgets[idx].description = fallbackDesc;
                this.widgets[idx].widget_type = c.widget_type;
            }

            this.$nextTick(() => {
                this.renderWidget(this.widgetControlsTarget.id);
            });
        },

        // ─── Add Widget ──
        openAddWidgetModal(preselectedSourceType = null) {
            this.addWidgetForm = { source_type: preselectedSourceType || '', custom_kpi_id: '', derived_metric_id: '', widget_type: '', name: '' };
            this.showAddWidgetModal = true;
        },

        canAddWidget() {
            if (!this.addWidgetForm.source_type) return false;
            if (!this.addWidgetForm.widget_type) return false;
            if (this.addWidgetForm.source_type === 'kpi' && !this.addWidgetForm.custom_kpi_id) return false;
            if (this.addWidgetForm.source_type === 'derived_metric' && !this.addWidgetForm.derived_metric_id) return false;
            return true;
        },

        cancelAddWidget() {
            if (this.targetGridX !== null && this.targetGridY !== null && this.grid) {
                const nodes = this.grid.engine?.nodes || [];
                const orphan = nodes.find(n => {
                    if (!n || n.id) return false;
                    return n.x === this.targetGridX && n.y === this.targetGridY;
                });
                if (orphan && orphan.el) {
                    this.grid.removeWidget(orphan.el, false);
                }
            }
            this.targetGridX = null;
            this.targetGridY = null;
            this.pendingDragSourceType = null;
            this.showAddWidgetModal = false;
        },

        positionPalette() {
            const palette = document.querySelector('.bd-palette-left');
            if (!palette) return;
            const container = palette.closest('[x-data]') || palette.offsetParent;
            if (!container) return;
            const containerTop = container.getBoundingClientRect().top;
            const top = (window.innerHeight / 2) - containerTop - (palette.offsetHeight / 2);
            this._repositioningPalette = true;
            palette.style.setProperty('top', `${Math.max(0, top)}px`, 'important');
            this._repositioningPalette = false;
        },

        confirmAddWidget() {
            if (!this.canAddWidget() || !this.$wire) return;

            const form = this.addWidgetForm;
            let sourceType = form.source_type;
            let sourceConfig = {};
            let controls = {};

            if (form.source_type === 'kpi') {
                sourceConfig = { custom_kpi_id: form.custom_kpi_id };
            } else if (form.source_type === 'derived_metric') {
                sourceType = 'metric';
                const dmId = String(form.derived_metric_id);
                const dmInfo = this.derivedMetrics[dmId] || {};
                const dmName = dmInfo.name || ('DM #' + dmId);
                const rawSourceSeries = Array.isArray(dmInfo.source_series) ? dmInfo.source_series : [];

                const rawSeries = [];
                const seriesAssets = {};
                const seriesChannels = {};
                const seriesDependencies = {};

                rawSourceSeries.forEach((ss, idx) => {
                    const ch = ss.channel || '';
                    const metric = ss.metric || ss.metric_key || ss.metric_name || '';
                    let defaultDep = '';
                    if (ch === 'facebook_marketing') defaultDep = 'ad_level';
                    else if (ch === 'facebook_organic') defaultDep = 'instagram_account';
                    else if (ch === 'google_search_console') defaultDep = 'non-searchAppearance';
                    else if (ch === 'google_analytics') defaultDep = 'traffic_matrix';
                    const dep = ss.dependency || defaultDep || '';
                    const assets = Array.isArray(ss.assets) ? [...ss.assets] : [];

                    rawSeries.push({
                        type: 'derived_metric',
                        dm_id: dmId,
                        dm_name: dmName,
                        dm_series_index: idx,
                        label: ss.label || '',
                        channel: ch,
                        dependency: dep,
                        metrics: metric ? [metric] : [],
                        assets: assets
                    });

                    seriesAssets[idx] = assets;
                    seriesChannels[idx] = ch;
                    seriesDependencies[idx] = dep;

                    if (ch && !this.allChannelAssets[ch] && this.$wire) {
                        this.$wire.getAssetsForChannel(ch).then(a => {
                            this.allChannelAssets = { ...this.allChannelAssets, [ch]: a };
                        });
                    }
                    if (ch && !this.allChannelMetrics[ch] && this.$wire) {
                        this.$wire.getMetricsForChannel(ch).then(m => {
                            this.allChannelMetrics = { ...this.allChannelMetrics, [ch]: m };
                        });
                    }
                });

                sourceConfig = {
                    derived_metric_id: form.derived_metric_id,
                    raw_series: rawSeries,
                    granularity: dmInfo.output_granularity || 'daily'
                };
                controls = {
                    granularity: dmInfo.output_granularity || 'daily',
                    raw_series: rawSeries,
                    series_assets: seriesAssets,
                    series_channels: seriesChannels,
                    series_dependencies: seriesDependencies
                };
            }

            const data = {
                name: form.name || form.widget_type,
                title: form.name || form.widget_type,
                source_type: sourceType,
                custom_kpi_id: form.source_type === 'kpi' ? form.custom_kpi_id : null,
                derived_metric_id: form.source_type === 'derived_metric' ? form.derived_metric_id : null,
                source_config: sourceConfig,
                widget_type: form.widget_type,
                controls: controls,
                grid_x: this.targetGridX ?? null,
                grid_y: this.targetGridY ?? null,
                grid_w: 4,
                grid_h: 3,
            };

            this.$wire.addWidget(data).then(widget => {
                widget._isNew = true;
                widget.grid_x = this.targetGridX;
                widget.grid_y = this.targetGridY;
                this.widgets.push(widget);
                this.showAddWidgetModal = false;
                this.targetGridX = null;
                this.targetGridY = null;
                this.pendingDragSourceType = null;

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

        deleteWidget(id, skipConfirm = false) {
            console.log('[dashboard-builder] deleteWidget requested for id:', id);
            if (!skipConfirm) {
                this.confirmDeleteWidget(id);
                return;
            }
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
        },

        duplicateWidget(id) {
            console.log('[DB][duplicateWidget] CALL', { id, source: (this.widgets || []).find(w => String(w.id) === String(id)) });
            if (!this.$wire) {
                console.error('[dashboard-builder] duplicateWidget: $wire instance missing');
                return;
            }
            const currentLayout = this.getLayout();
            this.$wire.duplicateWidget(id, currentLayout).then(rawWidget => {
                const widget = { ...rawWidget };
                widget._isNew = true;
                this.widgets.push(widget);
                this.widgets = [...this.widgets];
                console.log('[DB][duplicateWidget] pushed, widgets now', {
                    newId: widget.id,
                    count: this.widgets.length,
                    gx: widget.grid_x, gy: widget.grid_y, gw: widget.grid_w, gh: widget.grid_h
                });
                this.$nextTick(() => {
                    const strId = String(widget.id);
                    const el = document.querySelector(`[data-id="${strId}"]`) || document.querySelector(`[gs-id="${strId}"]`) || document.getElementById(strId);
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                    this.saveLayout();
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
