import jsVectorMap from 'jsvectormap';
import 'jsvectormap/dist/maps/world.js';
import 'jsvectormap/dist/maps/world-merc.js';

export function choroplethMap(options = {}) {
    return {
        mapInstance: null,
        containerId: options.containerId || 'choropleth-map-container',
        metricLabel: options.metricLabel || 'Value',
        data: options.data || {}, // { 'US': 1500, 'GB': 800, 'ES': 450 }
        selectedRegions: options.selectedRegions || [],
        isDarkMode: document.documentElement.classList.contains('dark'),

        init() {
            this._resizeObserver = new ResizeObserver((entries) => {
                for (const entry of entries) {
                    const cr = entry.contentRect;
                    if (cr && cr.width > 0 && cr.height > 0) {
                        if (!this.mapInstance) {
                            this.renderMap();
                        } else if (typeof this.mapInstance.updateSize === 'function') {
                            this.mapInstance.updateSize();
                        }
                    }
                }
            });

            this.$nextTick(() => {
                const el = document.getElementById(this.containerId);
                if (el) {
                    this._resizeObserver.observe(el);
                    if (el.clientWidth > 0 && el.clientHeight > 0) {
                        this.renderMap();
                    }
                }
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

            // Handle window resize
            this._resizeHandler = () => {
                const el = document.getElementById(this.containerId);
                if (el && el.clientWidth > 0 && el.clientHeight > 0) {
                    if (this.mapInstance && typeof this.mapInstance.updateSize === 'function') {
                        this.mapInstance.updateSize();
                    }
                }
            };
            window.addEventListener('resize', this._resizeHandler);
        },

        renderMap() {
            const el = document.getElementById(this.containerId);
            if (!el) return;

            // Prevent initializing jsVectorMap when container is hidden (e.g., display: none / zero size)
            // which causes baseScale = 0 / 0 => NaN and transform="scale(NaN) translate(NaN, NaN)"
            if (el.clientWidth === 0 || el.clientHeight === 0) {
                return;
            }

            const values = Object.values(this.data).map(v => Number(v) || 0).filter(v => v > 0);
            const maxVal = values.length ? Math.max(...values) : 1;
            const minVal = values.length ? Math.min(...values) : 0;

            // Generate hex color scale mapping for regions so every country gets proper continuous or quantile gradient
            const colorScaleData = {};
            const startColor = this.isDarkMode ? [15, 62, 109] : [186, 230, 253]; // #0f3e6d or #bae6fd
            const endColor = this.isDarkMode ? [0, 167, 249] : [2, 132, 199];     // #00a7f9 or #0284c7

            for (const [code, val] of Object.entries(this.data)) {
                const num = Number(val) || 0;
                if (num > 0) {
                    // Use logarithmic scaling so smaller countries still visibly show color gradation relative to huge outliers
                    const diff = maxVal - minVal;
                    const ratio = diff > 0
                        ? Math.log(num - minVal + 1) / Math.log(diff + 1)
                        : 0.8;
                    const r = Math.round(startColor[0] + (endColor[0] - startColor[0]) * ratio);
                    const g = Math.round(startColor[1] + (endColor[1] - startColor[1]) * ratio);
                    const b = Math.round(startColor[2] + (endColor[2] - startColor[2]) * ratio);
                    colorScaleData[code] = `#${((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)}`;
                }
            }

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
                            fill: this.isDarkMode ? '#243042' : '#e2e8f0',
                            stroke: this.isDarkMode ? '#334155' : '#cbd5e1',
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
                            scale: colorScaleData,
                            values: Object.keys(colorScaleData).reduce((acc, code) => {
                                acc[code] = code;
                                return acc;
                            }, {})
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

                if (this.selectedRegions && this.selectedRegions.length && this.mapInstance) {
                    this.mapInstance.setSelectedRegions(this.selectedRegions);
                }
            } catch (err) {
                console.error("Error initializing choropleth map:", err);
            }
        },

        updateData(newData, newMetricLabel = null, newSelected = null) {
            const dataChanged = JSON.stringify(this.data) !== JSON.stringify(newData || {});
            const labelChanged = newMetricLabel && this.metricLabel !== newMetricLabel;
            
            this.data = newData || {};
            if (newMetricLabel) this.metricLabel = newMetricLabel;

            if (newSelected !== null) {
                this.selectedRegions = newSelected;
            }

            if (dataChanged || labelChanged || !this.mapInstance) {
                this.destroyMap();
                this.renderMap();
            } else if (newSelected !== null) {
                this.updateSelected(newSelected);
            }
        },

        updateSelected(regions = []) {
            this.selectedRegions = regions || [];
            if (this.mapInstance && typeof this.mapInstance.setSelectedRegions === 'function') {
                if (this.mapInstance.clearSelectedRegions) {
                    this.mapInstance.clearSelectedRegions();
                }
                if (this.selectedRegions.length) {
                    this.mapInstance.setSelectedRegions(this.selectedRegions);
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
