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
            console.log('[choroplethMap] init called for container:', this.containerId, 'initial data keys:', Object.keys(this.data || {}).length);

            this._resizeObserver = new ResizeObserver((entries) => {
                for (const entry of entries) {
                    const cr = entry.contentRect;
                    if (cr && cr.width > 0 && cr.height > 0) {
                        const el = document.getElementById(this.containerId);
                        const hasSvg = el && el.querySelector('svg');
                        if ((!this.mapInstance || !hasSvg) && !this._rendering) {
                            this.renderMap();
                        } else if (this.mapInstance && typeof this.mapInstance.updateSize === 'function') {
                            this.mapInstance.updateSize();
                        }
                    }
                }
            });

            this.$nextTick(() => {
                const el = document.getElementById(this.containerId);
                if (el) {
                    this._resizeObserver.observe(el);
                    if (el.clientWidth > 0 && el.clientHeight > 0 && !this.mapInstance) {
                        this.renderMap();
                    }
                }
            });

            // Watch theme changes
            const observer = new MutationObserver(() => {
                const dark = document.documentElement.classList.contains('dark');
                if (dark !== this.isDarkMode) {
                    console.log('[choroplethMap] Dark mode toggled:', dark);
                    this.isDarkMode = dark;
                    if (this.mapInstance) {
                        this.applyColors();
                    } else {
                        this.renderMap();
                    }
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

        computeColorScale() {
            const values = Object.values(this.data || {}).map(v => Number(v) || 0).filter(v => v > 0);
            const maxVal = values.length ? Math.max(...values) : 1;
            const minVal = values.length ? Math.min(...values) : 0;

            const colorScaleData = {};
            const startColor = this.isDarkMode ? [15, 62, 109] : [186, 230, 253]; // #0f3e6d or #bae6fd
            const endColor = this.isDarkMode ? [0, 167, 249] : [2, 132, 199];     // #00a7f9 or #0284c7

            for (const [code, val] of Object.entries(this.data || {})) {
                const num = Number(val) || 0;
                if (num > 0) {
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
            return colorScaleData;
        },

        applyColors() {
            if (!this.mapInstance || !this.mapInstance.regions) {
                console.log('[choroplethMap] applyColors skipped: no mapInstance or regions');
                return;
            }
            const colorScaleData = this.computeColorScale();
            console.log('[choroplethMap] applyColors applying to', Object.keys(colorScaleData).length, 'countries');
            const defaultFill = this.isDarkMode ? '#243042' : '#e2e8f0';
            const defaultStroke = this.isDarkMode ? '#334155' : '#cbd5e1';

            for (const [code, regionObj] of Object.entries(this.mapInstance.regions)) {
                if (regionObj && regionObj.element) {
                    const isSelected = this.selectedRegions && this.selectedRegions.includes(code);
                    const color = isSelected ? '#10b981' : (colorScaleData[code] || defaultFill);
                    const strokeColor = isSelected ? '#34d399' : defaultStroke;
                    const shape = regionObj.element.shape;
                    if (shape && shape.style && shape.style.initial) {
                        shape.style.initial.fill = color;
                        shape.style.initial.stroke = strokeColor;
                    }
                    if (typeof regionObj.element.setStyle === 'function') {
                        regionObj.element.setStyle('fill', color);
                        regionObj.element.setStyle('stroke', strokeColor);
                    }
                }
            }
        },

        renderMap() {
            console.log('[choroplethMap] renderMap invoked for', this.containerId, 'isRendering:', this._rendering);
            if (this._rendering) return;
            const el = document.getElementById(this.containerId);
            if (!el) {
                console.warn('[choroplethMap] renderMap container not found:', this.containerId);
                return;
            }

            console.log('[choroplethMap] renderMap element client dimensions:', el.clientWidth, 'x', el.clientHeight);
            if (el.clientWidth === 0 || el.clientHeight === 0) {
                console.log('[choroplethMap] renderMap skipped because width or height is 0');
                return;
            }

            this._rendering = true;

            // Clear previous DOM contents cleanly if needed
            el.innerHTML = '';

            const self = this;
            const colorScaleData = this.computeColorScale();
            console.log('[choroplethMap] renderMap creating new jsVectorMap instance with colorScaleData entries:', Object.keys(colorScaleData).length);

            try {
                this.mapInstance = new jsVectorMap({
                    selector: '#' + this.containerId,
                    map: 'world',
                    zoomOnScroll: false,
                    zoomButtons: true,
                    regionsSelectable: true,
                    regionsSelectableOne: false,
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
                            fill: '#10b981',
                            stroke: '#34d399',
                            strokeWidth: 2
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
                    onRegionSelected(code, isSelected, selectedRegions) {
                        console.log('[choroplethMap] onRegionSelected triggered:', code, isSelected);
                        self.$dispatch('country-selected', { code, isSelected });
                    }
                });

                console.log('[choroplethMap] jsVectorMap created successfully, regions count:', Object.keys(this.mapInstance.regions || {}).length);

                if (this.selectedRegions && this.selectedRegions.length && this.mapInstance) {
                    this.mapInstance.setSelectedRegions(this.selectedRegions);
                }
            } catch (err) {
                console.error("[choroplethMap] Error initializing choropleth map:", err);
            } finally {
                this._rendering = false;
            }
        },

        updateData(newData, newMetricLabel = null, newSelected = null) {
            console.log('[choroplethMap] updateData called for', this.containerId, 'newData entries:', Object.keys(newData || {}).length, 'hasMapInstance:', !!this.mapInstance);
            const dataChanged = JSON.stringify(this.data) !== JSON.stringify(newData || {});
            const labelChanged = newMetricLabel && this.metricLabel !== newMetricLabel;
            
            this.data = newData || {};
            if (newMetricLabel) this.metricLabel = newMetricLabel;

            if (newSelected !== null) {
                this.selectedRegions = newSelected;
            }

            const el = document.getElementById(this.containerId);
            const hasSvg = el && el.querySelector('svg');

            if (!this.mapInstance || !hasSvg) {
                this.renderMap();
            } else if (dataChanged) {
                this.applyColors();
            } else if (el && el.clientWidth > 0 && typeof this.mapInstance.updateSize === 'function') {
                this.mapInstance.updateSize();
            }

            if (newSelected !== null && this.mapInstance) {
                this.updateSelected(newSelected);
            }
        },

        updateSelected(regions = []) {
            console.log('[choroplethMap] updateSelected called:', regions);
            this.selectedRegions = regions || [];
            if (this.mapInstance && typeof this.mapInstance.setSelectedRegions === 'function') {
                if (this.mapInstance.clearSelectedRegions) {
                    this.mapInstance.clearSelectedRegions();
                }
                if (this.selectedRegions.length) {
                    this.mapInstance.setSelectedRegions(this.selectedRegions);
                }
            }
            this.applyColors();
        },

        destroyMap() {
            console.warn('[choroplethMap] destroyMap called for', this.containerId);
            if (this.mapInstance && typeof this.mapInstance.destroy === 'function') {
                try {
                    this.mapInstance.destroy();
                } catch (e) {
                    console.warn("[choroplethMap] Map destroy error:", e);
                }
                this.mapInstance = null;
            }
            const el = document.getElementById(this.containerId);
            if (el) el.innerHTML = '';
        }
    };
}
