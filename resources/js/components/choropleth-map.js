import jsVectorMap from 'jsvectormap';
import 'jsvectormap/dist/maps/world.js';
import 'jsvectormap/dist/maps/world-merc.js';

export function choroplethMap(options = {}) {
    return {
        mapInstance: null,
        containerId: options.containerId || 'choropleth-map-container',
        metricLabel: options.metricLabel || 'Value',
        data: options.data || {}, // { 'US': 1500, 'GB': 800, 'ES': 450 }
        selectedCode: options.selectedCode || null,
        isDarkMode: document.documentElement.classList.contains('dark'),

        init() {
            this.$nextTick(() => {
                this.renderMap();
            });

            // Watch theme changes
            const observer = new MutationObserver(() => {
                const dark = document.documentElement.classList.contains('dark');
                if (dark !== this.isDarkMode) {
                    this.isDarkMode = dark;
                    this.destroyMap();
                    this.renderMap();
                }
            });
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        },

        renderMap() {
            const el = document.getElementById(this.containerId);
            if (!el) return;

            const values = Object.values(this.data).map(v => Number(v) || 0);
            const maxVal = values.length ? Math.max(...values, 1) : 1;
            const minVal = values.length ? Math.min(...values, 0) : 0;

            const self = this;

            try {
                this.mapInstance = new jsVectorMap({
                    selector: '#' + this.containerId,
                    map: 'world',
                    zoomOnScroll: false,
                    zoomButtons: true,
                    backgroundColor: 'transparent',
                    regionStyle: {
                        initial: {
                            fill: this.isDarkMode ? '#334155' : '#e2e8f0',
                            stroke: this.isDarkMode ? '#475569' : '#cbd5e1',
                            strokeWidth: 0.5,
                            fillOpacity: 1
                        },
                        hover: {
                            fill: '#0284c7',
                            fillOpacity: 0.85,
                            cursor: 'pointer'
                        },
                        selected: {
                            fill: '#0284c7',
                            stroke: '#38bdf8',
                            strokeWidth: 1.5
                        }
                    },
                    series: {
                        regions: [{
                            attribute: 'fill',
                            legend: false,
                            scale: this.isDarkMode 
                                ? ['#0f3e6d', '#00a7f9'] 
                                : ['#bae6fd', '#0284c7'],
                            values: this.data,
                            min: minVal,
                            max: maxVal
                        }]
                    },
                    onRegionTooltipShow(event, tooltip, code) {
                        const val = self.data[code] || 0;
                        const formatted = new Intl.NumberFormat('en-US').format(val);
                        tooltip.text(
                            `<div class="px-2 py-1 text-xs font-sans rounded bg-slate-900 text-white shadow-lg border border-slate-700">
                                <span class="font-bold">${tooltip.text()}</span>: ${formatted} ${self.metricLabel}
                            </div>`,
                            true
                        );
                    },
                    onRegionSelected(event, code, isSelected, selectedRegions) {
                        self.$dispatch('country-selected', { code, isSelected });
                    }
                });

                if (this.selectedCode && this.mapInstance) {
                    this.mapInstance.setSelectedRegions([this.selectedCode]);
                }
            } catch (err) {
                console.error("Error initializing choropleth map:", err);
            }
        },

        updateData(newData, newMetricLabel = null) {
            this.data = newData || {};
            if (newMetricLabel) this.metricLabel = newMetricLabel;
            this.destroyMap();
            this.renderMap();
        },

        setSelected(code) {
            this.selectedCode = code;
            if (this.mapInstance) {
                if (code) {
                    this.mapInstance.setSelectedRegions([code]);
                } else {
                    this.mapInstance.clearSelectedRegions();
                }
            }
        },

        destroyMap() {
            if (this.mapInstance && typeof this.mapInstance.destroy === 'function') {
                this.mapInstance.destroy();
                this.mapInstance = null;
            }
            const el = document.getElementById(this.containerId);
            if (el) el.innerHTML = '';
        }
    };
}
