export function dashboardView(config = {}) {
    return {
        loadedCount: 0,
        _loadedWidgets: {},
        _pendingRenders: 0,
        _startedCount: 0,
        totalCount: config.totalCount || 0,
        tenant: config.tenant || '',
        dashboardDefaults: config.dashboardDefaults || { date_start: '', date_end: '', zero_handling: 'remove', show_asset_group_selector: false },
        dashboardOverrides: config.dashboardOverrides || { date_start: '', date_end: '', zero_handling: 'remove' },
        hasUserChangedGlobalDate: false,
        assetGroups: config.assetGroups || {},
        channelAssetGroupMap: config.channelAssetGroupMap || {},
        selectedAssetGroup: config.selectedAssetGroup || '',
        _dashboardConfiguredGroup: config.selectedAssetGroup || '',
        init() {
            const groupKeys = Object.keys(this.assetGroups || {});
            if (!this.selectedAssetGroup && groupKeys.length > 0) {
                this.selectedAssetGroup = groupKeys[0];
            }
            this.$nextTick(() => {
                if (this.selectedAssetGroup && !this._dashboardConfiguredGroup) {
                    this._applyAssetGroupAfterInitialRender();
                }
                const tryInit = () => {
                    if (typeof GridStack !== 'undefined') {
                        GridStack.init({
                            staticGrid: true,
                            float: true,
                            column: 12,
                            cellHeight: 100,
                            margin: 12,
                            minRow: 6,
                            columnOpts: {
                                columnMax: 12,
                                breakpointForWindow: true,
                                layout: 'moveScale',
                                breakpoints: [
                                    { w: 1024, c: 12 },
                                    { w: 1023, c: 1 }
                                ]
                            }
                        }, '#view-grid-stack');
                    } else {
                        setTimeout(tryInit, 50);
                    }
                };
                tryInit();
            });
        },

        _applyAssetGroupAfterInitialRender() {
            const attempt = () => {
                const settled = this._pendingRenders <= 0 && this._startedCount >= this.totalCount;
                if (settled) {
                    this.applyAssetGroup();
                } else {
                    setTimeout(attempt, 50);
                }
            };
            setTimeout(attempt, 50);
        },

        _markLoaded(widgetId) {
            if (this._loadedWidgets[widgetId]) return;
            this._loadedWidgets[widgetId] = true;
            this.loadedCount++;
        },

        _markUnloaded(widgetId) {
            if (this._loadedWidgets[widgetId]) {
                delete this._loadedWidgets[widgetId];
                this.loadedCount = Math.max(0, this.loadedCount - 1);
            }
        },

        applyDateRange() {
            this.hasUserChangedGlobalDate = true;
            this.refreshAll();
        },

        isViewAssetInGroup(channel, assetId) {
            if (!this.selectedAssetGroup || !this.channelAssetGroupMap[channel]) return true;
            const groupAssets = this.channelAssetGroupMap[channel][this.selectedAssetGroup];
            if (!groupAssets) return true;
            return groupAssets.map(String).includes(String(assetId));
        },

        applyAssetGroup() {
            if (this.openSettings) {
                for (const vKey in this.settingsSeriesOptions) {
                    const channel = this.settingsVariables[vKey]?.channel;
                    if (!channel) continue;
                    const currentAssets = this.settingsControls.series_assets[vKey] || [];
                    if (currentAssets.length === 0) continue;

                    const groupId = this.selectedAssetGroup;
                    if (!groupId || !this.channelAssetGroupMap[channel]?.[groupId]) continue;

                    const allowedAssets = this.channelAssetGroupMap[channel][groupId].map(String);
                    const validAssets = currentAssets.filter(a => allowedAssets.includes(String(a)));

                    if (validAssets.length === 0 && allowedAssets.length > 0) {
                        this.settingsControls.series_assets = {
                            ...this.settingsControls.series_assets,
                            [vKey]: [allowedAssets[0]]
                        };
                    }
                }

                const widgetId = this.settingsWidgetId;
                const controls = this.settingsControls;
                window.dispatchEvent(new CustomEvent('reload-widget', {
                    detail: { id: widgetId, controls: controls }
                }));
                this.reloadWidget(widgetId, controls);
                return;
            }

            const widgets = document.querySelectorAll('.grid-stack-item-content .widget-content');
            widgets.forEach(el => {
                const widgetItem = el.closest('.grid-stack-item');
                if (!widgetItem) return;
                const widgetId = widgetItem.getAttribute('gs-id');
                const rawControls = el.getAttribute('data-raw-controls');
                if (!rawControls) return;

                try {
                    const controls = JSON.parse(rawControls);
                    const headerEl = el.closest('.grid-stack-item-content')?.querySelector('[data-variables]');
                    let variables = {};
                    if (headerEl) {
                        try {
                            variables = JSON.parse(headerEl.getAttribute('data-variables') || '{}');
                        } catch (e) {}
                    }

                    let changed = false;

                    for (const vKey in controls.series_assets || {}) {
                        const channel = variables[vKey]?.channel;
                        if (!channel) continue;
                        const currentAssets = controls.series_assets[vKey] || [];
                        if (currentAssets.length === 0) continue;

                        const groupId = this.selectedAssetGroup;
                        const allowedAssets = (groupId && this.channelAssetGroupMap[channel]?.[groupId])
                            ? this.channelAssetGroupMap[channel][groupId].map(String)
                            : [];

                        const validAssets = currentAssets.filter(a => allowedAssets.includes(String(a)));

                        let newAssets;
                        if (validAssets.length > 0) {
                            newAssets = validAssets;
                        } else if (allowedAssets.length > 0) {
                            newAssets = [allowedAssets[0]];
                        } else {
                            newAssets = ['___EMPTY_GROUP___'];
                        }

                        if (JSON.stringify(newAssets) !== JSON.stringify(currentAssets)) {
                            controls.series_assets[vKey] = newAssets;
                            changed = true;
                        }
                    }

                    if (changed) {
                        el.setAttribute('data-raw-controls', JSON.stringify(controls));
                        this.reloadWidget(parseInt(widgetId), controls);
                    }
                } catch (e) {}
            });
        },

        renderWidget(widgetId, el, controls) {
            el.setAttribute('data-raw-controls', JSON.stringify(controls));
            let effectiveControls = { ...controls };

            if (window.pvToken) {
                effectiveControls.pv_token = window.pvToken;
            }

            if (this.selectedAssetGroup) {
                effectiveControls.asset_group = this.selectedAssetGroup;
            }

            if (this.hasUserChangedGlobalDate) {
                if (this.dashboardOverrides.date_start) effectiveControls.date_start = this.dashboardOverrides.date_start;
                if (this.dashboardOverrides.date_end) effectiveControls.date_end = this.dashboardOverrides.date_end;
            } else {
                if (!effectiveControls.date_start && this.dashboardDefaults.date_start) effectiveControls.date_start = this.dashboardDefaults.date_start;
                if (!effectiveControls.date_end && this.dashboardDefaults.date_end) effectiveControls.date_end = this.dashboardDefaults.date_end;
            }

            return new Promise(resolve => {
                const tryRender = () => {
                    if (window.dashboardRenderer) {
                        this._pendingRenders++;
                        this._startedCount++;
                        window.dashboardRenderer.renderWidget(widgetId, el, effectiveControls, this.tenant)
                            .then(() => {
                                this._pendingRenders--;
                                this._markLoaded(widgetId);
                                resolve();
                            })
                            .catch(() => {
                                this._pendingRenders--;
                                this._markLoaded(widgetId);
                                resolve();
                            });
                    } else {
                        setTimeout(tryRender, 50);
                    }
                };
                tryRender();
            });
        },

        refreshAll() {
            this.loadedCount = 0;
            this._loadedWidgets = {};
            const widgets = document.querySelectorAll('.grid-stack-item-content .widget-content');
            widgets.forEach(el => {
                el.innerHTML = '';
                const widgetId = el.closest('.grid-stack-item').getAttribute('gs-id');
                const rawControls = el.getAttribute('data-raw-controls');
                if (rawControls) {
                    try {
                        const controls = JSON.parse(rawControls);
                        if (this.hasUserChangedGlobalDate) {
                            if (this.dashboardOverrides.date_start) controls.date_start = this.dashboardOverrides.date_start;
                            if (this.dashboardOverrides.date_end) controls.date_end = this.dashboardOverrides.date_end;
                        } else {
                            if (!controls.date_start && this.dashboardDefaults.date_start) controls.date_start = this.dashboardDefaults.date_start;
                            if (!controls.date_end && this.dashboardDefaults.date_end) controls.date_end = this.dashboardDefaults.date_end;
                        }
                        window.dispatchEvent(new CustomEvent('reload-widget', {
                            detail: {
                                id: parseInt(widgetId),
                                controls: controls
                            }
                        }));
                        this.renderWidget(widgetId, el, controls);
                    } catch (e) {}
                } else {
                    location.reload();
                }
            });
        },

        reloadWidget(widgetId, controls) {
            const widgetItem = document.querySelector(`.grid-stack-item[gs-id="${widgetId}"]`);
            if (!widgetItem) return Promise.resolve();
            const el = widgetItem.querySelector('.widget-content');
            if (!el) return Promise.resolve();
            el.innerHTML = '';
            this._markUnloaded(widgetId);
            window.dispatchEvent(new CustomEvent('reload-widget', {
                detail: { id: widgetId, controls: controls }
            }));
            const result = this.renderWidget(widgetId, el, controls) || Promise.resolve();
            return result;
        },

        settingsWidgetId: null,
        settingsBuilderControls: { date_start: '', date_end: '' },
        settingsOriginalControls: {
            date_start: '',
            date_end: '',
            granularity: '',
            metrics: [],
            series_assets: {}
        },
        settingsControls: { titles: { en: '', es: '' }, descriptions: { en: '', es: '' }, date_start: '', date_end: '', granularity: '', metrics: [], series_assets: {} },
        settingsSeriesOptions: {},
        settingsVariables: {},
        settingsGranularityOnTheGo: false,
        settingsSourceType: '',
        openSettings: false,
        settingsSearchQueries: {},

        openWidgetSettings(widgetId, controls, builderControls, seriesOptions, variables, granularityOnTheGo, sourceType) {
            this.settingsWidgetId = widgetId;
            this.settingsBuilderControls = JSON.parse(JSON.stringify(builderControls || {}));
            this.settingsOriginalControls = JSON.parse(JSON.stringify(controls || {}));
            this.settingsControls = controls || {};
            const widgetObj = (this.widgets || []).find(w => w.id === widgetId);
            this.settingsControls.titles = {
                en: this.settingsControls.titles?.en || widgetObj?.titles?.en || (typeof widgetObj?.title === 'string' ? widgetObj?.title : ''),
                es: this.settingsControls.titles?.es || widgetObj?.titles?.es || (typeof widgetObj?.title === 'string' ? widgetObj?.title : '')
            };
            this.settingsControls.descriptions = {
                en: this.settingsControls.descriptions?.en || widgetObj?.descriptions?.en || (typeof widgetObj?.description === 'string' ? widgetObj?.description : ''),
                es: this.settingsControls.descriptions?.es || widgetObj?.descriptions?.es || (typeof widgetObj?.description === 'string' ? widgetObj?.description : '')
            };
            if (!this.settingsControls.metrics) this.settingsControls.metrics = [];
            if (!Array.isArray(this.settingsControls.metrics)) {
                this.settingsControls.metrics = Object.values(this.settingsControls.metrics);
            }
            if (!this.settingsControls.series_assets) this.settingsControls.series_assets = {};
            if (!this.settingsControls.hasOwnProperty('remove_unknown')) this.settingsControls.remove_unknown = true;
            this.settingsSeriesOptions = seriesOptions || {};
            this.settingsVariables = variables || {};
            if (!this.settingsVariables || Object.keys(this.settingsVariables).length === 0) {
                this.settingsVariables = {};
                for (const key in this.settingsSeriesOptions) {
                    this.settingsVariables[key] = {
                        index: 0,
                        channel: '',
                        channel_name: this.settingsSeriesOptions[key]?.label || '',
                        metrics: {},
                        selected_metric: ''
                    };
                }
            }
            this.settingsGranularityOnTheGo = granularityOnTheGo;
            this.settingsSourceType = sourceType || '';
            this.openSettings = true;
            this.settingsSearchQueries = {};
            for (const key in seriesOptions) {
                this.settingsSearchQueries[key] = '';
            }
            for (const key in variables) {
                if (!this.settingsSearchQueries[key]) this.settingsSearchQueries[key] = '';
            }

            if (this.selectedAssetGroup) {
                for (const vKey in this.settingsVariables) {
                    const channel = this.settingsVariables[vKey]?.channel;
                    if (!channel) continue;
                    const currentAssets = this.settingsControls.series_assets[vKey] || [];
                    if (currentAssets.length === 0) continue;
                    this.settingsControls.series_assets = {
                        ...this.settingsControls.series_assets,
                        [vKey]: this.ensureValidAssets(vKey, channel, currentAssets)
                    };
                }
            }

            this.openSettings = true;
        },

        closeSettings() {
            this.openSettings = false;
            this.settingsWidgetId = null;
            this.settingsOriginalControls = {
                date_start: '',
                date_end: '',
                granularity: '',
                metrics: [],
                series_assets: {}
            };
            this.settingsControls = {
                titles: { en: '', es: '' },
                descriptions: { en: '', es: '' },
                date_start: '',
                date_end: '',
                granularity: '',
                metrics: [],
                series_assets: {}
            };
            this.settingsVariables = {};
            this.settingsSeriesOptions = {};
        },

        saveSettings() {
            const widgetId = this.settingsWidgetId;
            const controls = this.settingsControls;

            let dateAdjusted = false;
            let minStart = this.settingsBuilderControls.date_start || this.dashboardDefaults.date_start || '';
            let maxEnd = this.settingsBuilderControls.date_end || this.dashboardDefaults.date_end;

            if (controls.date_start && minStart && controls.date_start < minStart) {
                controls.date_start = minStart;
                dateAdjusted = true;
            }
            if (controls.date_end && controls.date_end > maxEnd) {
                controls.date_end = maxEnd;
                dateAdjusted = true;
            }

            if (dateAdjusted) {
                alert("Warning: The customized date range exceeded the allowed limits and was adjusted to comply.");
            }

            if (this.$wire && typeof this.$wire.saveWidgetControls === 'function') {
                this.$wire.saveWidgetControls(widgetId, controls, null, null, controls.titles || {}, controls.descriptions || {});
            }

            window.dispatchEvent(new CustomEvent('reload-widget', {
                detail: {
                    id: widgetId,
                    controls: controls
                }
            }));
            const reloadPromise = this.reloadWidget(widgetId, controls);
            if (this.popOutActive && this.popOutWidgetId === widgetId) {
                Promise.resolve(reloadPromise).then(() => {
                    this.$nextTick(() => {
                        const renderer = window.dashboardRenderer;
                        if (!renderer) return;
                        const target = this.$refs.popOutContent;
                        if (!target) return;
                        const contentEl = document.querySelector(`.grid-stack-item[gs-id="${widgetId}"] .widget-content`);
                        if (!contentEl) return;
                        renderer.popOutWidget(contentEl, target);
                    });
                });
            }
            this.closeSettings();
        },

        settingsToggleAsset(seriesKey, assetId) {
            const mode = this.settingsSeriesOptions[seriesKey].mode || 'multiple';
            const current = this.settingsControls.series_assets[seriesKey] || [];
            let next;

            if (mode === 'single') {
                next = [String(assetId)];
            } else {
                const idx = current.indexOf(String(assetId));
                if (idx > -1) {
                    if (current.length <= 1) {
                        return;
                    }
                    next = current.filter((_, i) => i !== idx);
                } else {
                    next = [...current, String(assetId)];
                }
            }

            this.settingsControls.series_assets = {
                ...this.settingsControls.series_assets,
                [seriesKey]: next
            };
        },

        settingsSelectAll(seriesKey) {
            const mode = this.settingsSeriesOptions[seriesKey].mode || 'multiple';
            if (mode === 'single') return;
            const allIds = Object.keys(this.settingsSeriesOptions[seriesKey].options).map(String);
            const channel = this.settingsVariables[seriesKey]?.channel;
            let validIds = allIds;
            if (this.selectedAssetGroup && channel && this.channelAssetGroupMap[channel]?.[this.selectedAssetGroup]) {
                const groupAssets = this.channelAssetGroupMap[channel][this.selectedAssetGroup].map(String);
                validIds = allIds.filter(id => groupAssets.includes(id));
            }
            this.settingsControls.series_assets = {
                ...this.settingsControls.series_assets,
                [seriesKey]: validIds
            };
        },

        isViewAssetInGroup(channel, assetId) {
            if (!this.selectedAssetGroup || !channel) return true;
            const groupAssets = this.channelAssetGroupMap[channel]?.[this.selectedAssetGroup];
            if (!groupAssets) return false;
            return groupAssets.map(String).includes(String(assetId));
        },

        isAssetAllowedByGroups(seriesKey, channel, assetId) {
            return this.isViewAssetInGroup(channel, assetId);
        },

        ensureValidAssets(seriesKey, channel, selectedAssets) {
            if (!this.selectedAssetGroup || !channel) return selectedAssets;
            const groupAssets = this.channelAssetGroupMap[channel]?.[this.selectedAssetGroup];
            if (!groupAssets) return ['___EMPTY_GROUP___'];
            const allowedAssets = groupAssets.map(String);
            const validAssets = (selectedAssets || []).filter(a => allowedAssets.includes(String(a)));
            if (validAssets.length > 0) return validAssets;
            if (allowedAssets.length > 0) return [allowedAssets[0]];
            return ['___EMPTY_GROUP___'];
        },

        popOutActive: false,
        popOutTitle: '',
        popOutWidgetId: null,

        openPopOut(widgetId) {
            if (this._popOutAnimating) return;

            const widgetEl = document.querySelector(`.grid-stack-item[gs-id="${widgetId}"]`);
            const headerEl = widgetEl?.querySelector('.grid-stack-item-content');
            const title = headerEl?.querySelector('h3')?.textContent?.trim() || '';
            const rect = widgetEl?.getBoundingClientRect();

            this.popOutTitle = title;
            this.popOutWidgetId = widgetId;
            this._popFromRect = rect;
            this._popOutAnimating = true;
            this.popOutActive = true;

            this.$nextTick(() => {
                const card = this.$refs.popOutCard;
                if (card && rect) {
                    const cardRect = card.getBoundingClientRect();
                    const originX = ((rect.left + rect.width / 2) - cardRect.left) / cardRect.width * 100;
                    const originY = ((rect.top + rect.height / 2) - cardRect.top) / cardRect.height * 100;

                    card.style.transition = 'none';
                    card.style.transformOrigin = `${originX}% ${originY}%`;
                    card.style.transform = 'scale(0.25)';
                    card.style.opacity = '0';

                    void card.offsetHeight;

                    card.style.transition = 'transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.4s ease-out';
                    card.style.transform = 'scale(1)';
                    card.style.opacity = '1';

                    card.addEventListener('transitionend', () => {
                        this._popOutAnimating = false;
                        card.style.transition = '';
                        card.style.transform = '';
                        card.style.opacity = '';

                        const renderer = window.dashboardRenderer;
                        if (!renderer) return;
                        const target = this.$refs.popOutContent;
                        if (!target) return;
                        const contentEl = document.querySelector(`.grid-stack-item[gs-id="${widgetId}"] .widget-content`);
                        if (!contentEl) return;
                        renderer.popOutWidget(contentEl, target);
                    }, { once: true });
                } else {
                    const renderer = window.dashboardRenderer;
                    if (!renderer) return;
                    const target = this.$refs.popOutContent;
                    if (!target) return;
                    const contentEl = document.querySelector(`.grid-stack-item[gs-id="${widgetId}"] .widget-content`);
                    if (!contentEl) return;
                    renderer.popOutWidget(contentEl, target);
                }
            });
        },

        closePopOut() {
            if (this._popOutAnimating) return;

            const widgetId = this.popOutWidgetId;

            if (widgetId) {
                const widgetEl = document.querySelector(`.grid-stack-item[gs-id="${widgetId}"]`);
                if (widgetEl) this._popFromRect = widgetEl.getBoundingClientRect();

                const contentEl = widgetEl?.querySelector('.widget-content');
                const renderer = window.dashboardRenderer;
                if (renderer && contentEl) {
                    const target = this.$refs.popOutContent;
                    if (target) {
                        renderer.popInWidget(contentEl, target);
                    }
                }
            }

            const card = this.$refs.popOutCard;
            if (card) {
                this._popOutAnimating = true;
                if (this._popFromRect) {
                    const cardRect = card.getBoundingClientRect();
                    const originX = ((this._popFromRect.left + this._popFromRect.width / 2) - cardRect.left) / cardRect.width * 100;
                    const originY = ((this._popFromRect.top + this._popFromRect.height / 2) - cardRect.top) / cardRect.height * 100;
                    card.style.transformOrigin = `${originX}% ${originY}%`;
                }
                card.style.transition = 'transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.4s ease-out';
                card.style.transform = 'scale(0.25)';
                card.style.opacity = '0';

                card.addEventListener('transitionend', () => {
                    this._popFromRect = null;
                    this._popOutAnimating = false;
                    this.popOutActive = false;
                    this.popOutWidgetId = null;
                    this.popOutTitle = '';
                }, { once: true });
            } else {
                this._popFromRect = null;
                this.popOutActive = false;
                this.popOutWidgetId = null;
                this.popOutTitle = '';
            }
        },

        openModalSettings() {
            const headerEl = document.querySelector(`[data-widget-id="${this.popOutWidgetId}"]`);
            if (!headerEl || !window.Alpine) return;
            const data = Alpine.$data(headerEl);
            window.dispatchEvent(new CustomEvent('open-widget-settings', {
                detail: {
                    widgetId: this.popOutWidgetId,
                    builderControls: JSON.parse(JSON.stringify(data.builderControls || {})),
                    controls: JSON.parse(JSON.stringify(data.controls || {})),
                    seriesOptions: JSON.parse(JSON.stringify(data.seriesOptions || {})),
                    variables: JSON.parse(JSON.stringify(data.variables || {})),
                    granularityOnTheGo: data.granularityOnTheGo,
                    sourceType: data.sourceType
                }
            }));
        }
    };
}

export function widgetHeader() {
    return {
        widgetId: null,
        controls: {},
        seriesOptions: {},
        metricOptions: {},
        variables: {},
        sourceType: '',
        searchQueries: {},
        isTimeGranularity: false,

        init() {
            const el = this.$el;
            this.widgetId = parseInt(el.dataset.widgetId);
            this.builderControls = JSON.parse(el.dataset.controls || '{}');
            this.controls = JSON.parse(el.dataset.controls || '{}');
            this.seriesOptions = JSON.parse(el.dataset.seriesOptions || '{}');
            this.metricOptions = JSON.parse(el.dataset.metricOptions || '{}');
            this.sourceType = el.dataset.sourceType || '';
            this.variables = JSON.parse(el.dataset.variables || '{}');

            this.isTimeGranularity = ['daily', 'weekly', 'monthly', 'quarterly', 'semiannual', 'annually', 'lifetime'].includes(this.builderControls.granularity);

            if (!this.controls.metrics) this.controls.metrics = [];
            if (!Array.isArray(this.controls.metrics)) {
                this.controls.metrics = Object.values(this.controls.metrics);
            }
            if (!this.controls.series_assets) this.controls.series_assets = {};
            const varCount = Object.keys(this.variables).length;
            while (this.controls.metrics.length < varCount) {
                this.controls.metrics.push('');
            }
            if (this.controls.assets && this.controls.assets.length > 0) {
                const firstKey = this.sourceType === 'kpi' ? 'dependent' : '0';
                if (!this.controls.series_assets[firstKey]) {
                    const validIds = this.seriesOptions[firstKey]
                        ? Object.keys(this.seriesOptions[firstKey].options)
                        : [];
                    this.controls.series_assets[firstKey] = this.controls.assets
                        .map(String)
                        .filter(id => validIds.includes(id));
                }
            }
            this.granularityOnTheGo = this.controls.hasOwnProperty('granularity') && this.controls.granularity !== '__NOT_SET__';
            if (!this.controls.granularity || this.controls.granularity === '__NOT_SET__') this.controls.granularity = '';
            for (const key in this.seriesOptions) {
                this.searchQueries[key] = '';
            }
            for (const key in this.variables) {
                if (!this.searchQueries[key]) this.searchQueries[key] = '';
            }
        },

        openDashboardSettings() {
            window.dispatchEvent(new CustomEvent('open-widget-settings', {
                detail: {
                    widgetId: this.widgetId,
                    builderControls: JSON.parse(JSON.stringify(this.builderControls)),
                    controls: JSON.parse(JSON.stringify(this.controls)),
                    seriesOptions: JSON.parse(JSON.stringify(this.seriesOptions)),
                    variables: JSON.parse(JSON.stringify(this.variables)),
                    granularityOnTheGo: this.granularityOnTheGo,
                    sourceType: this.sourceType
                }
            }));
        },

        isSelected(seriesKey, assetId) {
            if (!this.controls.series_assets[seriesKey]) return false;
            return this.controls.series_assets[seriesKey].includes(String(assetId));
        },

        toggleAsset(seriesKey, assetId) {
            const mode = (this.seriesOptions[seriesKey] || {}).mode || 'multiple';
            const current = this.controls.series_assets[seriesKey] || [];
            let next;

            if (mode === 'single') {
                next = [String(assetId)];
            } else {
                const idx = current.indexOf(String(assetId));
                if (idx > -1) {
                    if (current.length <= 1) return;
                    next = current.filter((_, i) => i !== idx);
                } else {
                    next = [...current, String(assetId)];
                }
            }
            this.controls.series_assets[seriesKey] = next;
        },

        selectAll(seriesKey) {
            const mode = (this.seriesOptions[seriesKey] || {}).mode || 'multiple';
            if (mode === 'single') return;
            const allIds = Object.keys(this.seriesOptions[seriesKey].options).map(String);
            this.controls.series_assets[seriesKey] = allIds;
        },

        getActiveFilterCount() {
            let count = 0;
            for (const key in this.seriesOptions) {
                if (this.controls.series_assets[key] && this.controls.series_assets[key].length > 0 && this.controls.series_assets[key].length < Object.keys(this.seriesOptions[key].options).length) {
                    count++;
                }
            }
            return count;
        },

        updateWidget() {
            const raw = JSON.stringify(this.controls);
            const el = document.querySelector(`.grid-stack-item[gs-id="${this.widgetId}"] .widget-content`);
            if (el) {
                el.setAttribute('data-raw-controls', raw);
            }
            const dbView = document.getElementById('dashboard-view-container');
            if (dbView && dbView.__x && dbView.__x.getUnobservedData()) {
                dbView.__x.getUnobservedData().reloadWidget(this.widgetId, this.controls);
            }
        }
    };
}

if (typeof window !== 'undefined') {
    window.dashboardView = dashboardView;
    window.widgetHeader = widgetHeader;
}
