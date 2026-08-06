export function sharedView(config = {}) {
    return {
        loadedCount: 0,
        totalCount: config.totalCount || 0,
        tenant: config.tenant || '',

        init() {
            this.$nextTick(() => {
                const tryInit = () => {
                    if (typeof GridStack !== 'undefined') {
                        GridStack.init({
                            staticGrid: true,
                            float: true,
                            column: 12,
                            cellHeight: 100,
                            margin: 12,
                            minRow: 6
                        }, '#view-grid-stack');
                    } else {
                        setTimeout(tryInit, 50);
                    }
                };
                tryInit();
            });
        },

        renderWidget(widgetId, el, controls) {
            el.setAttribute('data-raw-controls', JSON.stringify(controls));

            const effectiveControls = { ...controls };

            const tryRender = () => {
                if (window.dashboardRenderer) {
                    window.dashboardRenderer.renderWidget(widgetId, el, effectiveControls, this.tenant)
                        .then(() => {
                            this.loadedCount++;
                        })
                        .catch(() => {
                            this.loadedCount++;
                        });
                } else {
                    setTimeout(tryRender, 50);
                }
            };
            tryRender();
        },

        reloadWidget(widgetId, controls) {
            const widgetItem = document.querySelector(`.grid-stack-item[gs-id="${widgetId}"]`);
            if (!widgetItem) return;

            const el = widgetItem.querySelector('.widget-content');
            if (!el) return;

            el.innerHTML = '';
            if (this.loadedCount > 0) this.loadedCount--;
            this.renderWidget(widgetId, el, controls);
        },
    };
}

export function widgetHeaderPv(widgetId, controls, seriesOptions) {
    return {
        widgetId: widgetId,
        controls: controls || {},
        seriesOptions: seriesOptions || {},
        openFilters: false,
        searchQueries: {},

        init() {
            if (!this.controls.series_assets) this.controls.series_assets = {};
            for (const key in this.seriesOptions) {
                this.searchQueries[key] = '';
            }
        },

        isSelected(seriesKey, assetId) {
            if (!this.controls.series_assets[seriesKey]) return false;
            return this.controls.series_assets[seriesKey].includes(String(assetId));
        },

        toggleAsset(seriesKey, assetId) {
            if (!this.controls.series_assets[seriesKey]) {
                this.controls.series_assets[seriesKey] = [];
            }
            const arr = this.controls.series_assets[seriesKey];
            const idx = arr.indexOf(String(assetId));
            if (idx > -1) {
                arr.splice(idx, 1);
            } else {
                arr.push(String(assetId));
            }
            this.controls.series_assets[seriesKey] = arr;
            this.updateWidget();
        },

        selectAll(seriesKey) {
            const allIds = Object.keys(this.seriesOptions[seriesKey].options).map(String);
            this.controls.series_assets[seriesKey] = allIds;
            this.updateWidget();
        },

        clearAll(seriesKey) {
            this.controls.series_assets[seriesKey] = [];
            this.updateWidget();
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
            const dbView = document.getElementById('view-grid-stack');
            if (dbView && dbView.__x && dbView.__x.getUnobservedData()) {
                dbView.__x.getUnobservedData().reloadWidget(this.widgetId, this.controls);
            }
        },
    };
}
