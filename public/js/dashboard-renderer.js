/* ─── Dashboard Widget Renderer ───
 * Renders all 7 widget types: tile, line_chart, bar_chart, table, gauge, sparkline, anomaly_list
 * Each widget container calls `fetchWidgetData(widgetId, containerEl)` on mount.
 */

window.dashboardRenderer = {
    _chartInstances: new Map(),
    _widgetData: new Map(),
    METRIC_FORMATS: {
        'spend': { label: 'Spend', format: 'currency', prefix: '$' },
        'cpc': { label: 'CPC', format: 'currency', prefix: '$' },
        'cpm': { label: 'CPM', format: 'currency', prefix: '$' },
        'revenue': { label: 'Revenue', format: 'currency', prefix: '$' },
        'purchase_roas': { label: 'ROAS', format: 'currency', prefix: '$' },
        'aov': { label: 'AOV', format: 'currency', prefix: '$' },
        'cost_per_result': { label: 'Cost/Result', format: 'currency', prefix: '$' },
        'ctr': { label: 'CTR', format: 'percentage', multiply: 100 },
        'bounce_rate': { label: 'Bounce Rate', format: 'percentage', multiply: 100 },
        'clicks': { label: 'Clicks', format: 'number' },
        'impressions': { label: 'Impressions', format: 'number' },
        'reach': { label: 'Reach', format: 'number' },
        'conversions': { label: 'Conversions', format: 'number' },
        'results': { label: 'Results', format: 'number' },
        'sessions': { label: 'Sessions', format: 'number' },
        'pageviews': { label: 'Pageviews', format: 'number' },
        'new_users': { label: 'New Users', format: 'number' },
        'followers': { label: 'Followers', format: 'number' },
        'engaged_users': { label: 'Engaged Users', format: 'number' },
        'orders': { label: 'Orders', format: 'number' },
        'sends': { label: 'Sends', format: 'number' },
        'opens': { label: 'Opens', format: 'number' },
        'bounces': { label: 'Bounces', format: 'number' },
        'link_clicks': { label: 'Link Clicks', format: 'number' },
        'engagements': { label: 'Engagements', format: 'number' },
        'total_interactions': { label: 'Total Interactions', format: 'number' },
        'average_session_duration': { label: 'Avg Session', format: 'number', suffix: 's' },
        'position': { label: 'Avg Position', format: 'number' },
    },

    // Ratio KPIs produce a result whose type differs from the raw dependent metric.
    // Determine the display format for the KPI output based on metric[0] (dependent) and metric[1] (independent).
    getKpiResultFormat(controls) {
        const m0 = controls?.metrics?.[0];
        const m1 = controls?.metrics?.[1];
        if (!m0) return null;
        const f0 = this.METRIC_FORMATS[m0];
        const f1 = m1 ? this.METRIC_FORMATS[m1] : null;

        // Both metrics exist → the KPI computes a ratio (dependent / independent)
        if (m1) {
            // Known ratio → output type
            const ratioFormats = {
                'clicks/impressions': { label: 'CTR', format: 'percentage', multiply: 100 },
                'conversions/impressions': { label: 'Conv. Rate', format: 'percentage', multiply: 100 },
                'conversions/clicks': { label: 'Conv. Rate', format: 'percentage', multiply: 100 },
                'results/impressions': { label: 'Result Rate', format: 'percentage', multiply: 100 },
                'results/clicks': { label: 'Result Rate', format: 'percentage', multiply: 100 },
                'sessions/clicks': { label: 'Session Rate', format: 'percentage', multiply: 100 },
                'sessions/link_clicks': { label: 'Session Rate', format: 'percentage', multiply: 100 },
                'bounce_rate/clicks': { label: 'Bounce Rate', format: 'number' },
                'spend/clicks': { label: 'CPC', format: 'currency', prefix: '$' },
                'spend/impressions': { label: 'CPM', format: 'currency', prefix: '$' },
                'spend/conversions': { label: 'CPA', format: 'currency', prefix: '$' },
                'spend/results': { label: 'Cost/Result', format: 'currency', prefix: '$' },
                'spend/sessions': { label: 'Cost/Session', format: 'currency', prefix: '$' },
                'revenue/spend': { label: 'ROAS', format: 'currency', prefix: '$' },
                'revenue/clicks': { label: 'RPC', format: 'currency', prefix: '$' },
                'impressions/spend': { label: 'Impr./$', format: 'number' },
                'sessions/spend': { label: 'Sessions/$', format: 'number' },
                'new_users/spend': { label: 'New Users/$', format: 'number' },
                'engaged_users/reach': { label: 'Eng. Rate', format: 'percentage', multiply: 100 },
                'average_session_duration/clicks': { label: 'Session/Click', format: 'number', suffix: 's' },
                'clicks/position': { label: 'Clicks/Pos.', format: 'number' },
            };
            const key = m0 + '/' + m1;
            if (ratioFormats[key]) return ratioFormats[key];

            // Fallback heuristic
            if (f0?.format === 'currency' && f1?.format === 'number') {
                return { label: f0.label, format: 'currency', prefix: '$' };
            }
            if (f0?.format === 'number' && f1?.format === 'number') {
                return { label: f0.label + '/' + f1.label, format: 'percentage', multiply: 100 };
            }
            return f0 || null;
        }

        return f0 || null;
    },
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
            const body = { tenant };
            if (controls) {
                const overrideKeys = ['date_start', 'date_end', 'zero_handling', 'granularity', 'metrics', 'assets', 'series_assets', 'series_channels', 'channel', 'edge_case_weighted', 'edge_case_grouping', 'max_ratio'];
                const overrides = {};
                for (const key of overrideKeys) {
                    if (controls[key] !== undefined) overrides[key] = controls[key];
                }
                if (Object.keys(overrides).length > 0) body.controls = overrides;
            }
            const response = await fetch('/api/dashboard/widget/' + widgetId + '/data', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify(body),
            });

            if (!response.ok) {
                const err = await response.json().catch(() => ({}));
                throw new Error(err.error || 'HTTP ' + response.status);
            }

            const json = await response.json();

            if (!json.success) {
                throw new Error(json.message || json.error || 'Unknown error');
            }

            this.render(containerEl, json);
        } catch (e) {
            if (e.message === 'access_restricted') {
                containerEl.innerHTML = this.accessRestrictedState();
            } else {
                containerEl.innerHTML = this.errorState(e.message);
            }
        }
    },

    /**
     * Main render dispatcher.
     */
    render(containerEl, json) {
        const { widget_type, data, controls } = json;
        this._widgetData.set(containerEl, json);
        this._renderWidget(widget_type, containerEl, data, controls);
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

    accessRestrictedState() {
        return `
            <div class="flex items-center justify-center h-full p-4">
                <div class="text-center">
                    <svg class="w-6 h-6 mx-auto text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m9.364-7.364A9 9 0 1112 3a9 9 0 017.364 4.636z"/>
                    </svg>
                    <p class="text-xs text-amber-500 mt-2 font-medium">Access Restricted</p>
                    <p class="text-xs text-gray-400 mt-1">You don't have permission to view this widget's data.</p>
                    <p class="text-xs text-gray-500 mt-1">Contact your project owner or editor to request access.</p>
                </div>
            </div>`;
    },

    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    // ─── Tile ───

    renderTile(containerEl, data, controls) {
        const resultFormat = this.getKpiResultFormat(controls);

        let value = data?.value ?? data?.current ?? 0;
        const previous = data?.previous ?? null;
        const prefix = data?.prefix ?? resultFormat?.prefix ?? '';
        const suffix = data?.suffix ?? resultFormat?.suffix ?? '';
        const label = data?.label ?? resultFormat?.label ?? '';
        let format = data?.format ?? resultFormat?.format ?? 'number';

        if (format === 'percentage' && resultFormat?.multiply) {
            value = value * resultFormat.multiply;
        }

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

    renderLineChart(containerEl, data, controls) {
        const labels = data?.labels ?? [];
        const datasets = data?.datasets ?? [];
        const reverseY = controls?.metrics?.[0] === 'position';

        const resultFormat = this.getKpiResultFormat(controls);

        if (!labels.length || !datasets.length) {
            containerEl.innerHTML = '<div class="text-sm text-gray-400 p-4 text-center">No data available</div>';
            return;
        }

        this.renderChart(containerEl, {
            type: 'line',
            data: {
                labels,
                datasets: datasets.map(ds => ({
                    ...ds,
                    currency: ds.currency ?? (resultFormat?.format === 'currency' ? true : undefined),
                    percentage: ds.percentage ?? (resultFormat?.format === 'percentage' ? true : undefined),
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: datasets.length > 1, position: 'bottom', labels: { boxWidth: 12, padding: 12 } },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                let val = ctx.parsed.y;
                                if (resultFormat?.multiply) val = val * resultFormat.multiply;
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
                    y: { beginAtZero: !reverseY, reverse: reverseY, ticks: { font: { size: 10 } } },
                },
                elements: { line: { tension: 0.3 }, point: { radius: 2 } },
            },
        });
    },

    // ─── Bar Chart ───

    renderBarChart(containerEl, data, controls) {
        const labels = data?.labels ?? [];
        const datasets = data?.datasets ?? [];
        const reverseY = controls?.metrics?.[0] === 'position';

        const resultFormat = this.getKpiResultFormat(controls);

        if (!labels.length || !datasets.length) {
            containerEl.innerHTML = '<div class="text-sm text-gray-400 p-4 text-center">No data available</div>';
            return;
        }

        this.renderChart(containerEl, {
            type: 'bar',
            data: {
                labels,
                datasets: datasets.map(ds => ({
                    ...ds,
                    currency: ds.currency ?? (resultFormat?.format === 'currency' ? true : undefined),
                    percentage: ds.percentage ?? (resultFormat?.format === 'percentage' ? true : undefined),
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: datasets.length > 1, position: 'bottom', labels: { boxWidth: 12, padding: 12 } },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                let val = ctx.parsed.y;
                                if (resultFormat?.multiply) val = val * resultFormat.multiply;
                                if (ctx.dataset.currency) val = this.formatCurrency(val);
                                else if (ctx.dataset.percentage) val = val.toFixed(1) + '%';
                                else val = this.formatNumber(val);
                                return ctx.dataset.label + ': ' + val;
                            },
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { beginAtZero: !reverseY, reverse: reverseY, ticks: { font: { size: 10 } } },
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

    renderGauge(containerEl, data, controls) {
        const resultFormat = this.getKpiResultFormat(controls);

        let value = data?.value ?? 0;
        const min = data?.min ?? 0;
        const max = data?.max ?? 100;
        const label = data?.label ?? '';
        const thresholds = data?.thresholds ?? [
            { from: 0, to: 33, color: '#ef4444' },
            { from: 33, to: 66, color: '#f59e0b' },
            { from: 66, to: 100, color: '#22c55e' },
        ];

        const rawValue = value;
        if (resultFormat?.multiply) value = value * resultFormat.multiply;

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
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">${resultFormat?.format === 'percentage' ? Number(rawValue).toFixed(1) + '%' : this.formatNumber(rawValue)} / ${this.formatNumber(max)}</p>
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

    // ─── Anomaly Chart ───

    renderAnomalyChart(containerEl, data, controls) {
        const labels = data?.labels ?? [];
        const datasets = data?.datasets ?? [];
        const anomalyDates = data?.anomaly_dates ?? [];
        const reverseY = controls?.metrics?.[0] === 'position';

        const resultFormat = this.getKpiResultFormat(controls);

        if (!labels.length || !datasets.length) {
            containerEl.innerHTML = '<div class="text-sm text-gray-400 p-4 text-center">No data available</div>';
            return;
        }

        const anomalySet = new Set(anomalyDates);

        const anomalyPlugin = {
            id: 'anomalyLines',
            afterDraw(chart) {
                const ctx = chart.ctx;
                const xScale = chart.scales.x;

                ctx.save();
                ctx.setLineDash([4, 4]);
                ctx.lineWidth = 1.5;
                ctx.strokeStyle = '#ef4444';

                labels.forEach((label, i) => {
                    if (anomalySet.has(label)) {
                        const x = xScale.getPixelForValue(i);
                        const y0 = chart.chartArea.top;
                        const y1 = chart.chartArea.bottom;

                        ctx.beginPath();
                        ctx.moveTo(x, y0);
                        ctx.lineTo(x, y1);
                        ctx.stroke();
                    }
                });

                ctx.restore();
            },
        };

        this.renderChart(containerEl, {
            type: 'line',
            data: {
                labels,
                datasets: datasets.map(ds => ({
                    ...ds,
                    currency: ds.currency ?? (resultFormat?.format === 'currency' ? true : undefined),
                    percentage: ds.percentage ?? (resultFormat?.format === 'percentage' ? true : undefined),
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                let val = ctx.parsed.y;
                                if (resultFormat?.multiply) val = val * resultFormat.multiply;
                                if (ctx.dataset.currency) val = val != null ? '$' + Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
                                else if (ctx.dataset.percentage) val = val.toFixed(1) + '%';
                                else val = val != null ? val.toLocaleString('en-US', { maximumFractionDigits: 1 }) : '—';
                                return ctx.dataset.label + ': ' + val;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxTicksLimit: 10, font: { size: 10 } },
                    },
                    y: {
                        beginAtZero: !reverseY,
                        reverse: reverseY,
                        ticks: {
                            font: { size: 10 },
                            callback(val) {
                                if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                                if (val >= 1000) return (val / 1000).toFixed(1) + 'K';
                                return val;
                            },
                        },
                    },
                },
            },
            plugins: [anomalyPlugin],
        });
    },

    // ─── Scatter Plot ───

    renderScatterPlot(containerEl, data, controls) {
        if (!data || !data.datasets || !data.datasets.length) {
            containerEl.innerHTML = '<div class="text-sm text-gray-400 p-4 text-center">No data available</div>';
            return;
        }

        const reverseY = controls?.metrics?.[0] === 'position';
        const reverseX = controls?.metrics?.[1] === 'position';

        const resultFormat = this.getKpiResultFormat(controls);
        const xFmt = controls?.metrics?.[1] ? (this.METRIC_FORMATS[controls.metrics[1]] || null) : null;
        const yFmt = resultFormat || (controls?.metrics?.[0] ? (this.METRIC_FORMATS[controls.metrics[0]] || null) : null);

        console.log('[ScatterPlot] controls:', controls);
        console.log('[ScatterPlot] metrics:', controls?.metrics);
        console.log('[ScatterPlot] resultFormat:', resultFormat);
        console.log('[ScatterPlot] xFmt:', xFmt, '| yFmt:', yFmt);

        const formatPoint = (v, fmt, axis) => {
            if (!fmt) {
                const r = v.toLocaleString('en-US', { maximumFractionDigits: 2 });
                if (v === 0 || (v > 0 && v < 1)) console.log(`[formatPoint][${axis}] v=${v}, fmt=null →`, r);
                return r;
            }
            let val = fmt.multiply ? v * fmt.multiply : v;
            let r;
            if (fmt.format === 'currency') r = '$' + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            else if (fmt.format === 'percentage') r = val.toFixed(1) + '%';
            else r = val.toLocaleString('en-US', { maximumFractionDigits: 2 });
            console.log(`[formatPoint][${axis}] v=${v}, fmt=${JSON.stringify(fmt)}, val=${val} →`, r);
            return r;
        };

        const yAxisLabel = yFmt?.label || 'Value';
        const xAxisLabel = xFmt?.label || (controls?.metrics?.[1] || 'X');

        const scatterDataset = data.datasets.find(d => d.type === 'scatter');
        if (scatterDataset && scatterDataset.data && scatterDataset.data.length > 0) {
            const yValues = scatterDataset.data.map(p => p.y);
            const minY = Math.min(...yValues);
            const maxY = Math.max(...yValues);

            const bgColors = [];
            const borderColors = [];

            scatterDataset.data.forEach(p => {
                let t = 0.5;
                if (maxY > minY) {
                    t = (p.y - minY) / (maxY - minY);
                }
                if (reverseY) {
                    t = 1 - t;
                }
                const hue = t * 120; // 0 is red, 120 is green
                bgColors.push(`hsla(${hue}, 80%, 50%, 0.7)`);
                borderColors.push(`hsla(${hue}, 80%, 45%, 1)`);
            });

            scatterDataset.backgroundColor = bgColors;
            scatterDataset.borderColor = borderColors;
            scatterDataset.borderWidth = 1;
        }

        // For bounded-ratio KPIs, force the ratio axis (Y) to start at 0 if no negative values exist
        const isRatioKpi = controls?.max_ratio != null;
        const yScaleOpts = { type: 'linear', title: { display: true, text: data.y_label || yAxisLabel }, reverse: reverseY, ticks: { callback: (v) => formatPoint(v, yFmt, 'Y') } };
        if (isRatioKpi) {
            const allY = data.datasets?.find(d => d.type === 'scatter')?.data?.map(p => p.y) ?? [];
            if (allY.length > 0 && Math.min(...allY) >= 0) {
                yScaleOpts.suggestedMin = 0;
            }
        }

        this.renderChart(containerEl, {
            type: 'scatter',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        type: 'linear',
                        position: 'bottom',
                        title: { display: true, text: data.x_label || xAxisLabel },
                        reverse: reverseX,
                        ticks: {
                            callback: (v) => formatPoint(v, xFmt, 'X'),
                        },
                    },
                    y: yScaleOpts,
                },
                plugins: {
                    legend: { display: true, position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const point = ctx.raw;
                                const valX = formatPoint(point.x, xFmt);
                                const valY = formatPoint(point.y, yFmt);
                                const baseLabel = point.label ? (point.label + ' - ') : '';
                                return baseLabel + '(' + valX + ', ' + valY + ')';
                            }
                        }
                    }
                }
            }
        });
    },

    // ─── Combo Chart (MACD) ───

    renderComboChart(containerEl, data, controls) {
        if (!data || !data.datasets || !data.datasets.length) {
            containerEl.innerHTML = '<div class="text-sm text-gray-400 p-4 text-center">No data available</div>';
            return;
        }

        const resultFormat = this.getKpiResultFormat(controls);

        this.renderChart(containerEl, {
            type: 'bar',
            data: {
                ...data,
                datasets: data.datasets.map(ds => ({
                    ...ds,
                    currency: ds.currency ?? (resultFormat?.format === 'currency' ? true : undefined),
                    percentage: ds.percentage ?? (resultFormat?.format === 'percentage' ? true : undefined),
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { grid: { display: false } }
                },
                plugins: {
                    legend: { display: true, position: 'bottom' }
                }
            }
        });
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
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js';
            script.onload = () => {
                const chart = new Chart(canvas, config);
                this._chartInstances.set(containerEl, chart);
            };
            document.head.appendChild(script);
        } else {
            const chart = new Chart(canvas, config);
            this._chartInstances.set(containerEl, chart);
        }
    },

    /**
     * Pop a widget's canvas/chart into a different container (fullscreen modal).
     */
    popOutWidget(containerEl, targetEl) {
        const json = this._widgetData.get(containerEl);
        if (!json) return;

        const canvas = containerEl.querySelector('canvas');
        if (canvas) {
            targetEl.innerHTML = '';
            targetEl.appendChild(canvas);
            const chart = this._chartInstances.get(containerEl);
            if (chart) {
                this._chartInstances.set(targetEl, chart);
                this._chartInstances.delete(containerEl);
                if (this._popOutObserver) this._popOutObserver.disconnect();
                this._popOutObserver = new ResizeObserver(() => { chart.resize(); });
                this._popOutObserver.observe(targetEl);
            }
            return;
        }
        // HTML widget — clone content into target
        targetEl.innerHTML = containerEl.innerHTML;
    },

    /**
     * Pop the widget back to its original container.
     * If the container was already re-rendered while popped out, discard the modal's canvas.
     */
    popInWidget(containerEl, targetEl) {
        if (this._popOutObserver) {
            this._popOutObserver.disconnect();
            this._popOutObserver = null;
        }
        const canvas = targetEl.querySelector('canvas');
        if (canvas) {
            const existingCanvas = containerEl.querySelector('canvas');
            if (existingCanvas) {
                // Grid was reloaded while popped out — discard modal's stale chart
                const chart = this._chartInstances.get(targetEl);
                if (chart) {
                    chart.destroy();
                    this._chartInstances.delete(targetEl);
                }
                targetEl.innerHTML = '';
                return;
            }
            containerEl.innerHTML = '';
            containerEl.appendChild(canvas);
            const chart = this._chartInstances.get(targetEl);
            if (chart) {
                this._chartInstances.set(containerEl, chart);
                this._chartInstances.delete(targetEl);
                chart.resize();
            }
            return;
        }
        // HTML widget — content was cloned, not moved. Just clear the modal.
        targetEl.innerHTML = '';
    },

    _renderWidget(widget_type, containerEl, data, controls) {
        switch (widget_type) {
            case 'tile':       this.renderTile(containerEl, data, controls); break;
            case 'line_chart': this.renderLineChart(containerEl, data, controls); break;
            case 'bar_chart':  this.renderBarChart(containerEl, data, controls); break;
            case 'table':      this.renderTable(containerEl, data); break;
            case 'gauge':      this.renderGauge(containerEl, data, controls); break;
            case 'sparkline':  this.renderSparkline(containerEl, data); break;
            case 'anomaly_list':  this.renderAnomalyList(containerEl, data); break;
            case 'anomaly_chart': this.renderAnomalyChart(containerEl, data, controls); break;
            case 'scatter_plot': this.renderScatterPlot(containerEl, data, controls); break;
            case 'combo_chart': this.renderComboChart(containerEl, data, controls); break;
            default:
                containerEl.innerHTML = '<div class="text-sm text-gray-400 p-4 text-center">Unknown widget type: ' + widget_type + '</div>';
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
