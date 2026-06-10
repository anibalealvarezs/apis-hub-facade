/* ─── Dashboard Widget Renderer ───
 * Renders all 7 widget types: tile, line_chart, bar_chart, table, gauge, sparkline, anomaly_list
 * Each widget container calls `fetchWidgetData(widgetId, containerEl)` on mount.
 */

window.dashboardRenderer = {
    /**
     * Fetch data for a single widget and render into its container.
     * @param {number} widgetId
     * @param {HTMLElement} containerEl - The element to render into
     * @param {object} controls - Resolved controls for this widget
     * @param {string} tenant - Project subdomain/ID
     */
    async renderWidget(widgetId, containerEl, controls, tenant) {
        if (!containerEl) return;

        // Show loading
        containerEl.innerHTML = this.loadingSkeleton();

        try {
            const response = await fetch('/api/dashboard/widget/' + widgetId + '/data', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify({ tenant }),
            });

            if (!response.ok) {
                const err = await response.json().catch(() => ({}));
                throw new Error(err.error || 'HTTP ' + response.status);
            }

            const json = await response.json();

            if (!json.success) {
                throw new Error(json.error || 'Unknown error');
            }

            this.render(containerEl, json);
        } catch (e) {
            containerEl.innerHTML = this.errorState(e.message);
        }
    },

    /**
     * Main render dispatcher.
     */
    render(containerEl, json) {
        const { widget_type, data } = json;

        switch (widget_type) {
            case 'tile':       this.renderTile(containerEl, data); break;
            case 'line_chart': this.renderLineChart(containerEl, data); break;
            case 'bar_chart':  this.renderBarChart(containerEl, data); break;
            case 'table':      this.renderTable(containerEl, data); break;
            case 'gauge':      this.renderGauge(containerEl, data); break;
            case 'sparkline':  this.renderSparkline(containerEl, data); break;
            case 'anomaly_list': this.renderAnomalyList(containerEl, data); break;
            default:
                containerEl.innerHTML = '<div class="text-sm text-gray-400 p-4 text-center">Unknown widget type: ' + widget_type + '</div>';
        }
    },

    // ─── Loading / Error States ───

    loadingSkeleton() {
        return `
            <div class="flex items-center justify-center h-full p-4">
                <div class="text-center">
                    <svg class="animate-spin h-6 w-6 text-gray-400 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <p class="text-xs text-gray-400 mt-2">Loading...</p>
                </div>
            </div>`;
    },

    errorState(message) {
        const icon = '<svg class="w-6 h-6 mx-auto text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>';
        return `
            <div class="flex items-center justify-center h-full p-4">
                <div class="text-center">
                    ${icon}
                    <p class="text-xs text-red-400 mt-2">${this.escapeHtml(message)}</p>
                    <button onclick="this.closest('[x-ref]') ? null : location.reload()" class="text-xs text-primary-500 hover:underline mt-1">Retry</button>
                </div>
            </div>`;
    },

    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    // ─── Tile ───

    renderTile(containerEl, data) {
        const value = data?.value ?? data?.current ?? 0;
        const previous = data?.previous ?? null;
        const prefix = data?.prefix ?? '';
        const suffix = data?.suffix ?? '';
        const label = data?.label ?? '';
        const format = data?.format ?? 'number';

        let trendHtml = '';
        let changePercent = null;

        if (previous !== null && previous !== 0) {
            changePercent = ((value - previous) / Math.abs(previous)) * 100;
            const isUp = changePercent >= 0;
            const arrow = isUp ? '▲' : '▼';
            const color = isUp ? 'text-green-500' : 'text-red-500';
            trendHtml = `<span class="${color} text-sm font-medium ml-2">${arrow} ${Math.abs(changePercent).toFixed(1)}%</span>`;
        }

        const formattedValue = format === 'currency'
            ? this.formatCurrency(value)
            : format === 'percentage'
                ? value.toFixed(1) + '%'
                : this.formatNumber(value);

        containerEl.innerHTML = `
            <div class="flex flex-col items-center justify-center h-full p-4">
                ${label ? `<p class="text-xs text-gray-500 dark:text-gray-400 mb-1">${this.escapeHtml(label)}</p>` : ''}
                <div class="flex items-baseline">
                    <span class="text-3xl font-bold text-gray-900 dark:text-white">${prefix}${formattedValue}${suffix}</span>
                    ${trendHtml}
                </div>
                ${changePercent !== null ? `<p class="text-xs text-gray-400 mt-1">vs previous period</p>` : ''}
            </div>`;
    },

    // ─── Line Chart ───

    renderLineChart(containerEl, data) {
        const labels = data?.labels ?? [];
        const datasets = data?.datasets ?? [];

        if (!labels.length || !datasets.length) {
            containerEl.innerHTML = '<div class="text-sm text-gray-400 p-4 text-center">No data available</div>';
            return;
        }

        this.renderChart(containerEl, {
            type: 'line',
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: datasets.length > 1, position: 'bottom', labels: { boxWidth: 12, padding: 12 } },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                let val = ctx.parsed.y;
                                if (ctx.dataset.currency) val = this.formatCurrency(val);
                                else if (ctx.dataset.percentage) val = val.toFixed(1) + '%';
                                else val = this.formatNumber(val);
                                return ctx.dataset.label + ': ' + val;
                            },
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { maxTicksLimit: 8, font: { size: 10 } } },
                    y: { beginAtZero: true, ticks: { font: { size: 10 } } },
                },
                elements: { line: { tension: 0.3 }, point: { radius: 2 } },
            },
        });
    },

    // ─── Bar Chart ───

    renderBarChart(containerEl, data) {
        const labels = data?.labels ?? [];
        const datasets = data?.datasets ?? [];

        if (!labels.length || !datasets.length) {
            containerEl.innerHTML = '<div class="text-sm text-gray-400 p-4 text-center">No data available</div>';
            return;
        }

        this.renderChart(containerEl, {
            type: 'bar',
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: datasets.length > 1, position: 'bottom', labels: { boxWidth: 12, padding: 12 } },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                let val = ctx.parsed.y;
                                if (ctx.dataset.currency) val = this.formatCurrency(val);
                                else val = this.formatNumber(val);
                                return ctx.dataset.label + ': ' + val;
                            },
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { beginAtZero: true, ticks: { font: { size: 10 } } },
                },
            },
        });
    },

    // ─── Table ───

    renderTable(containerEl, data) {
        const columns = data?.columns ?? [];
        const rows = data?.rows ?? [];

        if (!columns.length || !rows.length) {
            containerEl.innerHTML = '<div class="text-sm text-gray-400 p-4 text-center">No data available</div>';
            return;
        }

        let html = '<div class="overflow-auto max-h-full"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">';
        html += '<thead class="bg-gray-50 dark:bg-gray-800"><tr>';
        columns.forEach(col => {
            html += `<th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">${this.escapeHtml(col.label || col)}</th>`;
        });
        html += '</tr></thead><tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">';

        rows.forEach(row => {
            html += '<tr class="hover:bg-gray-50 dark:hover:bg-gray-800">';
            columns.forEach(col => {
                const key = col.key || col;
                const val = row[key] ?? row[col] ?? '';
                const formatted = col.format === 'currency' ? this.formatCurrency(val)
                    : col.format === 'percentage' ? (val != null ? Number(val).toFixed(1) + '%' : '')
                    : col.format === 'number' ? this.formatNumber(val)
                    : val;
                html += `<td class="px-3 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">${this.escapeHtml(String(formatted))}</td>`;
            });
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        if (data.total !== undefined) {
            html += `<div class="px-3 py-2 text-xs text-gray-400 dark:text-gray-500 border-t border-gray-200 dark:border-gray-700">${this.formatNumber(data.total)} results</div>`;
        }

        containerEl.innerHTML = html;
    },

    // ─── Gauge ───

    renderGauge(containerEl, data) {
        const value = data?.value ?? 0;
        const min = data?.min ?? 0;
        const max = data?.max ?? 100;
        const label = data?.label ?? '';
        const thresholds = data?.thresholds ?? [
            { from: 0, to: 33, color: '#ef4444' },
            { from: 33, to: 66, color: '#f59e0b' },
            { from: 66, to: 100, color: '#22c55e' },
        ];

        const pct = Math.min(100, Math.max(0, ((value - min) / (max - min)) * 100));

        let color = thresholds[0]?.color ?? '#22c55e';
        for (const t of thresholds) {
            if (pct >= t.from && pct <= t.to) { color = t.color; break; }
        }

        containerEl.innerHTML = `
            <div class="flex flex-col items-center justify-center h-full p-4">
                <div class="relative w-24 h-24">
                    <svg viewBox="0 0 120 120" class="transform -rotate-90">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="#e5e7eb" stroke-width="10" class="dark:stroke-gray-700"/>
                        <circle cx="60" cy="60" r="52" fill="none" stroke="${color}" stroke-width="10"
                                stroke-dasharray="${(pct / 100) * 326.7} 326.7"
                                stroke-linecap="round" class="transition-all duration-700"/>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-xl font-bold text-gray-900 dark:text-white">${Math.round(pct)}%</span>
                    </div>
                </div>
                ${label ? `<p class="text-xs text-gray-500 dark:text-gray-400 mt-2">${this.escapeHtml(label)}</p>` : ''}
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">${this.formatNumber(value)} / ${this.formatNumber(max)}</p>
            </div>`;
    },

    // ─── Sparkline ───

    renderSparkline(containerEl, data) {
        const points = data?.points ?? data?.values ?? [];

        if (!points.length) {
            containerEl.innerHTML = '<div class="text-sm text-gray-400 p-4 text-center">No data</div>';
            return;
        }

        const current = points[points.length - 1];
        const first = points[0];
        const trend = current >= first ? 'up' : 'down';
        const color = trend === 'up' ? '#22c55e' : '#ef4444';

        const min = Math.min(...points);
        const max = Math.max(...points);
        const range = max - min || 1;
        const w = 160, h = 40;
        const stepX = w / (points.length - 1);

        const pathD = points.map((p, i) => {
            const x = i * stepX;
            const y = h - ((p - min) / range) * h;
            return (i === 0 ? 'M' : 'L') + x.toFixed(1) + ' ' + y.toFixed(1);
        }).join(' ');

        const areaD = pathD + ' L' + (w) + ' ' + h + ' L0 ' + h + ' Z';

        let changePct = 0;
        if (first !== 0) changePct = ((current - first) / Math.abs(first)) * 100;

        containerEl.innerHTML = `
            <div class="flex items-center gap-3 p-4">
                <div class="flex-shrink-0 text-right">
                    <span class="text-xl font-bold text-gray-900 dark:text-white">${this.formatNumber(current)}</span>
                    <span class="text-xs ${trend === 'up' ? 'text-green-500' : 'text-red-500'} ml-1">${trend === 'up' ? '▲' : '▼'}${Math.abs(changePct).toFixed(0)}%</span>
                </div>
                <svg viewBox="0 0 ${w} ${h}" class="flex-1 h-10" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="spark-fill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="${color}" stop-opacity="0.2"/>
                            <stop offset="100%" stop-color="${color}" stop-opacity="0.02"/>
                        </linearGradient>
                    </defs>
                    <path d="${areaD}" fill="url(#spark-fill)"/>
                    <path d="${pathD}" fill="none" stroke="${color}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>`;
    },

    // ─── Anomaly List ───

    renderAnomalyList(containerEl, data) {
        const anomalies = data?.anomalies ?? data?.points ?? [];

        if (!anomalies.length) {
            containerEl.innerHTML = '<div class="flex items-center justify-center h-full text-sm text-green-500 p-4">✓ No anomalies detected</div>';
            return;
        }

        let html = '<div class="overflow-auto max-h-full divide-y divide-gray-200 dark:divide-gray-700">';
        anomalies.slice(0, 20).forEach(a => {
            const severity = a.severity ?? a.anomaly_score ?? 0;
            const severityLabel = severity >= 0.9 ? 'Critical' : severity >= 0.7 ? 'High' : severity >= 0.4 ? 'Medium' : 'Low';
            const color = severity >= 0.9 ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                : severity >= 0.7 ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300'
                : severity >= 0.4 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'
                : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';

            html += `<div class="px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-800">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900 dark:text-white">${this.escapeHtml(a.label || a.name || 'Anomaly')}</span>
                    <span class="text-xs px-1.5 py-0.5 rounded-full font-medium ${color}">${severityLabel}</span>
                </div>
                <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400 mt-1">
                    <span>Value: ${this.formatNumber(a.value ?? a.actual_value)}</span>
                    <span>Expected: ${this.formatNumber(a.expected ?? a.expected_value)}</span>
                </div>
                ${a.date ? `<p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">${this.escapeHtml(a.date)}</p>` : ''}
            </div>`;
        });
        html += '</div>';
        if (anomalies.length > 20) {
            html += `<div class="px-3 py-1.5 text-xs text-gray-400 dark:text-gray-500 border-t border-gray-200 dark:border-gray-700">+${anomalies.length - 20} more</div>`;
        }

        containerEl.innerHTML = html;
    },

    // ─── Chart.js Helper ───

    renderChart(containerEl, config) {
        const isDark = document.documentElement.classList.contains('dark');
        if (config.options && config.options.scales) {
            for (const axis of Object.values(config.options.scales)) {
                if (axis.ticks) {
                    axis.ticks.color = isDark ? '#a1a1aa' : '#71717a';
                }
                if (axis.grid) {
                    axis.grid.color = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.06)';
                }
            }
        }
        if (config.options?.plugins?.legend?.labels) {
            config.options.plugins.legend.labels.color = isDark ? '#d4d4d8' : '#52525b';
        }

        const canvas = document.createElement('canvas');
        containerEl.innerHTML = '';
        containerEl.appendChild(canvas);

        if (typeof Chart === 'undefined') {
            // Load Chart.js dynamically if not available
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js';
            script.onload = () => new Chart(canvas, config);
            document.head.appendChild(script);
        } else {
            new Chart(canvas, config);
        }
    },

    // ─── Formatters ───

    formatNumber(n) {
        if (n == null || isNaN(n)) return '—';
        return Number(n).toLocaleString('en-US', { maximumFractionDigits: 1 });
    },

    formatCurrency(n) {
        if (n == null || isNaN(n)) return '—';
        return '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
};
