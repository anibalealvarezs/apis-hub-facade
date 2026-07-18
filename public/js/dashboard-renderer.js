/* ─── Dashboard Widget Renderer ───
 * Renders all 7 widget types: tile, line_chart, bar_chart, table, gauge, sparkline, anomaly_list
 * Each widget container calls `fetchWidgetData(widgetId, containerEl)` on mount.
 */

window.dashboardRenderer = {
    _chartInstances: new Map(),
    _widgetData: new Map(),
    _pinnedTooltips: new Map(),
    METRIC_FORMATS: {
        'spend': {label: 'Spend', format: 'currency', prefix: '$'},
        'cpc': {label: 'CPC', format: 'currency', prefix: '$'},
        'cpm': {label: 'CPM', format: 'currency', prefix: '$'},
        'revenue': {label: 'Revenue', format: 'currency', prefix: '$'},
        'purchase_roas': {label: 'ROAS', format: 'currency', prefix: '$'},
        'aov': {label: 'AOV', format: 'currency', prefix: '$'},
        'cost_per_result': {label: 'Cost/Result', format: 'currency', prefix: '$'},
        'ctr': {label: 'CTR', format: 'percentage', multiply: 100},
        'bounce_rate': {label: 'Bounce Rate', format: 'percentage', multiply: 100, lower_is_better: true},
        'clicks': {label: 'Clicks', format: 'number'},
        'impressions': {label: 'Impressions', format: 'number'},
        'reach': {label: 'Reach', format: 'number'},
        'conversions': {label: 'Conversions', format: 'number'},
        'results': {label: 'Results', format: 'number'},
        'sessions': {label: 'Sessions', format: 'number'},
        'pageviews': {label: 'Pageviews', format: 'number'},
        'new_users': {label: 'New Users', format: 'number'},
        'followers': {label: 'Followers', format: 'number'},
        'engaged_users': {label: 'Engaged Users', format: 'number'},
        'orders': {label: 'Orders', format: 'number'},
        'sends': {label: 'Sends', format: 'number'},
        'opens': {label: 'Opens', format: 'number'},
        'bounces': {label: 'Bounces', format: 'number'},
        'link_clicks': {label: 'Link Clicks', format: 'number'},
        'engagements': {label: 'Engagements', format: 'number'},
        'total_interactions': {label: 'Total Interactions', format: 'number'},
        'average_session_duration': {label: 'Avg Session', format: 'number', suffix: 's'},
        'position': {label: 'Avg Position', format: 'number', lower_is_better: true},
    },

    // Ratio KPIs produce a result whose type differs from the raw dependent metric.
    // Determine the display format for the KPI output based on metric[0] (dependent) and metric[1] (independent).
    getKpiResultFormat(controls) {
        const m0 = controls?.metrics?.[0];
        const m1 = controls?.metrics?.[1];
        if (!m0) return null;
        const f0 = this.METRIC_FORMATS[m0];
        const f1 = m1 ? this.METRIC_FORMATS[m1] : null;

        // If multiple metrics exist AND this is a KPI widget, apply ratio heuristics.
        // For non-KPI widgets, multiple metrics just mean multiple chart series.
        if (m1 && controls?.source_type === 'kpi') {
            // Known ratio → output type
            const ratioFormats = {
                'clicks/impressions': {label: 'CTR', format: 'percentage', multiply: 100},
                'conversions/impressions': {label: 'Conv. Rate', format: 'percentage', multiply: 100},
                'conversions/clicks': {label: 'Conv. Rate', format: 'percentage', multiply: 100},
                'results/impressions': {label: 'Result Rate', format: 'percentage', multiply: 100},
                'results/clicks': {label: 'Result Rate', format: 'percentage', multiply: 100},
                'sessions/clicks': {label: 'Session Rate', format: 'percentage', multiply: 100},
                'sessions/link_clicks': {label: 'Session Rate', format: 'percentage', multiply: 100},
                'bounce_rate/clicks': {label: 'Bounce Rate', format: 'percentage', multiply: 100},
                'spend/clicks': {label: 'CPC', format: 'currency', prefix: '$'},
                'spend/impressions': {label: 'CPM', format: 'currency', prefix: '$'},
                'spend/conversions': {label: 'CPA', format: 'currency', prefix: '$'},
                'spend/results': {label: 'Cost/Result', format: 'currency', prefix: '$'},
                'spend/sessions': {label: 'Cost/Session', format: 'currency', prefix: '$'},
                'revenue/spend': {label: 'ROAS', format: 'currency', prefix: '$'},
                'revenue/clicks': {label: 'RPC', format: 'currency', prefix: '$'},
                'impressions/spend': {label: 'Impr./$', format: 'number'},
                'sessions/spend': {label: 'Sessions/$', format: 'number'},
                'new_users/spend': {label: 'New Users/$', format: 'number'},
                'engaged_users/reach': {label: 'Eng. Rate', format: 'percentage', multiply: 100},
                'average_session_duration/clicks': {label: 'Session/Click', format: 'number', suffix: 's'},
                'clicks/position': {label: 'Clicks/Pos.', format: 'number'},
            };
            const key = m0 + '/' + m1;
            if (ratioFormats[key]) return ratioFormats[key];

            // Fallback heuristic
            if (f0?.format === 'currency' && f1?.format === 'number') {
                return {label: f0.label, format: 'currency', prefix: '$'};
            }
            if (f0?.format === 'number' && f1?.format === 'number') {
                return {label: f0.label + '/' + f1.label, format: 'percentage', multiply: 100};
            }
            return f0 || null;
        }

        return f0 || null;
    },
    /**
     * Fetch data for a single widget and render into its container.
     * Data fetch happens immediately; Chart.js rendering is deferred until
     * the widget container enters the viewport (IntersectionObserver).
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
            const body = {tenant};
            if (controls) {
                const overrideKeys = ['date_start', 'date_end', 'zero_handling', 'granularity', 'metrics', 'assets', 'series_assets', 'series_channels', 'channel', 'edge_case_weighted', 'edge_case_grouping', 'max_ratio', 'remove_unknown'];
                const overrides = {};
                for (const key of overrideKeys) {
                    if (controls[key] !== undefined) overrides[key] = controls[key];
                }
                if (Object.keys(overrides).length > 0) body.controls = overrides;
            }
            const response = await fetch('/api/dashboard/widget/' + widgetId + '/data', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
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

            this._widgetData.set(containerEl, json);

            // Render immediately if already in viewport, otherwise defer
            this._lazyRenderWhenVisible(containerEl, json);
        } catch (e) {
            if (e.message === 'access_restricted') {
                containerEl.innerHTML = this.accessRestrictedState();
            } else {
                containerEl.innerHTML = this.errorState(e.message);
            }
        }
    },

    /**
     * Render a widget from cached data, or defer until it enters the viewport.
     */
    _lazyRenderWhenVisible(containerEl, json) {
        if (this._renderObservers?.has(containerEl)) return;

        const rect = containerEl.getBoundingClientRect();
        const isVisible = rect.top < window.innerHeight + 100 && rect.bottom > -100;

        if (isVisible) {
            this.render(containerEl, json);
            return;
        }

        if (!this._renderObservers) this._renderObservers = new Map();

        const observer = new IntersectionObserver((entries) => {
            for (const entry of entries) {
                if (!entry.isIntersecting) continue;
                observer.unobserve(containerEl);
                this._renderObservers.delete(containerEl);
                const data = this._widgetData.get(containerEl);
                if (data) this.render(containerEl, data);
            }
        }, { rootMargin: '100px' });

        this._renderObservers.set(containerEl, observer);
        observer.observe(containerEl);
    },

    /**
     * Force-render a widget immediately from cached data, regardless of viewport.
     * Used by pop-out to ensure the chart exists before moving its canvas.
     */
    flushRender(containerEl) {
        if (containerEl.querySelector('canvas')) return; // already rendered

        const observer = this._renderObservers?.get(containerEl);
        if (observer) {
            observer.unobserve(containerEl);
            this._renderObservers.delete(containerEl);
        }

        const json = this._widgetData.get(containerEl);
        if (json) this.render(containerEl, json);
    },

    /**
     * Main render dispatcher.
     */
    render(containerEl, json) {
        const {widget_type, source_type, data, controls} = json;
        if (controls && source_type) {
            controls.source_type = source_type;
        }
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

        const yMetric = controls?.metrics?.[0];
        const yFmt = resultFormat || (yMetric ? (this.METRIC_FORMATS[yMetric] || null) : null);
        const yUnit = yFmt?.format === 'percentage' ? '%' : yFmt?.format === 'currency' ? (yFmt?.prefix || '$') : yFmt?.suffix || '';
        const yAxisLabel = (yFmt?.label || 'Value') + (yUnit ? ' (' + yUnit + ')' : '');
        const yMetricName = this.getMetricName(yMetric);

        if (!labels.length || !datasets.length) {
            containerEl.innerHTML = '<div class="text-sm text-gray-400 p-4 text-center">No data available</div>';
            return;
        }

        const mappedDatasets = datasets.map(ds => ({
            ...ds,
            currency: ds.currency ?? (resultFormat?.format === 'currency' ? true : undefined),
            percentage: ds.percentage ?? (resultFormat?.format === 'percentage' ? true : undefined),
            pointRadius: 6,
            pointHoverRadius: 10,
            pointHitRadius: 15,
            pointBackgroundColor: ds.borderColor || ds.backgroundColor || '#3B82F6',
            pointBorderColor: ds.borderColor || ds.backgroundColor || '#3B82F6',
            pointBorderWidth: 2,
            pointHoverBorderWidth: 2,
        }));

        let chartScales = {
            x: {grid: {display: false}, ticks: {maxTicksLimit: 8, font: {size: 10}}}
        };

        if (datasets.length === 1) {
            chartScales.y = {
                beginAtZero: !reverseY,
                reverse: reverseY,
                title: {display: true, text: yAxisLabel},
                ticks: {font: {size: 10}},
            };
            mappedDatasets[0].yAxisID = 'y';
        } else {
            if (data?.scales) {
                for (const [axisId, axisConf] of Object.entries(data.scales)) {
                    chartScales[axisId] = {
                        ...axisConf,
                        title: {display: false},
                        ticks: {display: false},
                    };
                }
            } else {
                mappedDatasets.forEach((ds, idx) => {
                    if (ds.yAxisID) {
                        chartScales[ds.yAxisID] = {
                            type: 'linear',
                            display: true,
                            title: {display: false},
                            ticks: {display: false},
                            grid: {drawOnChartArea: idx === 0}
                        };
                    }
                });
            }
        }

        const config = {
            type: 'line',
            data: {labels, datasets: mappedDatasets},
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onHover(e) {
                    const found = e.chart.getElementsAtEventForMode(e, 'nearest', {intersect: true}, false);
                    e.native.target.style.cursor = found.length ? 'pointer' : 'default';
                },
                plugins: {
                    legend: {display: datasets.length > 1, position: 'bottom', labels: {boxWidth: 12, padding: 12}},
                    tooltip: {
                        callbacks: {
                            title: (ctx) => ctx[0]?.chart.data.labels?.[ctx[0].dataIndex] || '',
                            label: (ctx) => {
                                const dsLabel = ctx.dataset.label || yMetricName;
                                const valY = this.formatMetricValue(ctx.parsed.y, yMetric);
                                return valY + ' ' + dsLabel;
                            },
                        },
                    },
                },
                scales: chartScales,
                elements: {line: {tension: 0.3}},
            },
        };
        this._setAnimation(config, true);
        this.renderChart(containerEl, config);
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

        const mappedDatasets = datasets.map(ds => ({
            ...ds,
            currency: ds.currency ?? (resultFormat?.format === 'currency' ? true : undefined),
            percentage: ds.percentage ?? (resultFormat?.format === 'percentage' ? true : undefined),
        }));

        let chartScales = {
            x: {grid: {display: false}, ticks: {font: {size: 10}}}
        };

        if (datasets.length === 1) {
            chartScales.y = {
                beginAtZero: !reverseY,
                reverse: reverseY,
                ticks: {font: {size: 10}}
            };
            mappedDatasets[0].yAxisID = 'y';
        } else {
            if (data?.scales) {
                for (const [axisId, axisConf] of Object.entries(data.scales)) {
                    chartScales[axisId] = {
                        ...axisConf,
                        title: {display: false},
                        ticks: {display: false},
                    };
                }
            } else {
                mappedDatasets.forEach((ds, idx) => {
                    if (ds.yAxisID) {
                        chartScales[ds.yAxisID] = {
                            type: 'linear',
                            display: true,
                            title: {display: false},
                            ticks: {display: false},
                            grid: {drawOnChartArea: idx === 0}
                        };
                    }
                });
            }
        }

        const config = {
            type: 'bar',
            data: {labels, datasets: mappedDatasets},
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {display: datasets.length > 1, position: 'bottom', labels: {boxWidth: 12, padding: 12}},
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                let val = ctx.parsed.y;
                                if (resultFormat?.multiply) val = val * resultFormat.multiply;
                                if (ctx.dataset?.currency) return this.formatCurrency(val);
                                if (ctx.dataset?.percentage) return val.toFixed(1) + '%';
                                return this.formatNumber(val);
                            },
                        },
                    },
                },
                scales: chartScales,
            },
        };
        this._setAnimation(config, false);
        this.renderChart(containerEl, config);
    },

    // ─── Table ───

    renderTable(containerEl, data) {
        const columns = data?.columns ?? [];
        const rows = data?.rows ?? [];

        if (!columns.length || !rows.length) {
            containerEl.style.padding = '1rem';
            containerEl.style.overflow = '';
            containerEl.innerHTML = '<div class="text-sm text-gray-400 p-4 text-center">No data available</div>';
            return;
        }

        containerEl.style.padding = '0';
        containerEl.style.overflow = 'hidden';
        containerEl._tableData = data;

        if (!containerEl._tableSort) {
            // Default sort by first column: DESC for time-based, ASC for dimensions
            const firstCol = columns[0];
            const firstKey = firstCol?.key || firstCol;
            const timeKeys = ['date', 'daily', 'weekly', 'monthly', 'quarterly', 'semiannual', 'annually'];
            const isTimeBased = timeKeys.includes(firstKey);
            containerEl._tableSort = { column: firstKey, direction: isTimeBased ? 'desc' : 'asc' };
        }
        const sort = containerEl._tableSort;

        let sortedRows = [...rows];
        if (sort.column) {
            const colDef = columns.find(c => c.key === sort.column || c === sort.column);
            const isNumeric = colDef?.format === 'number' || colDef?.format === 'currency' || colDef?.format === 'percentage';
            sortedRows.sort((a, b) => {
                let va = a[sort.column], vb = b[sort.column];
                if (isNumeric) { va = Number(va) || 0; vb = Number(vb) || 0; }
                else { va = String(va).toLowerCase(); vb = String(vb).toLowerCase(); }
                return va < vb ? (sort.direction === 'asc' ? -1 : 1)
                     : va > vb ? (sort.direction === 'asc' ? 1 : -1) : 0;
            });
        }

        let html = '<div class="table-wrap" style="overflow:auto;height:100%;border-radius:inherit;">';
        html += '<table style="width:100%;table-layout:fixed;border-collapse:collapse;">';

        html += '<thead style="position:sticky;top:0;z-index:1;">';
        html += '<tr class="bg-gray-50 dark:bg-gray-800">';
        columns.forEach(col => {
            const key = col.key || col;
            const isActive = sort.column === key;
            const isNumeric = col.format === 'currency' || col.format === 'percentage' || col.format === 'number';
            const arrow = isActive ? (sort.direction === 'asc' ? ' \u25B2' : ' \u25BC') : '';
            const thAlign = isNumeric ? 'text-right' : 'text-left';
            const thStyle = isNumeric ? 'width:140px;' : '';
            html += `<th class="px-3 py-2 ${thAlign} font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer select-none hover:text-gray-700 dark:hover:text-gray-200" data-sort-key="${key}" style="${thStyle}">${this.escapeHtml(col.label || col)}<span class="sort-arrow" style="font-size:10px;margin-left:2px;">${arrow}</span></th>`;
        });
        html += '</tr></thead>';

        html += '<tbody class="bg-white dark:bg-gray-900">';
        sortedRows.forEach((row, ri) => {
            const rowClass = ri % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50/50 dark:bg-gray-800/30';
            html += `<tr class="${rowClass} hover:bg-gray-100 dark:hover:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">`;
            columns.forEach(col => {
                const key = col.key || col;
                const val = row[key] ?? row[col] ?? '';
                const isNumeric = col.format === 'currency' || col.format === 'percentage' || col.format === 'number';
                const formatted = isNumeric && col.format === 'currency' ? this.formatCurrency(val)
                    : isNumeric && col.format === 'percentage' ? (val != null ? Number(val).toFixed(1) + '%' : '')
                        : isNumeric && col.format === 'number' ? this._formatTableNumber(val)
                            : val;
                const tdClass = isNumeric
                    ? 'px-3 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300 text-right'
                    : 'px-3 py-2 text-gray-700 dark:text-gray-300';
                const tdStyle = 'overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
                html += `<td class="${tdClass}" title="${this.escapeHtml(String(val))}" style="${tdStyle}">${this.escapeHtml(String(formatted))}</td>`;
            });
            html += '</tr>';
        });
        html += '</tbody></table></div>';

        if (data.total !== undefined) {
            html += `<div class="px-3 py-2 text-xs text-gray-400 dark:text-gray-500 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">${this._formatTableNumber(data.total)} results</div>`;
        }

        containerEl.innerHTML = html;

        containerEl.querySelectorAll('th[data-sort-key]').forEach(th => {
            th.addEventListener('click', () => {
                const key = th.dataset.sortKey;
                if (sort.column === key) {
                    sort.direction = sort.direction === 'asc' ? 'desc' : 'asc';
                } else {
                    sort.column = key;
                    sort.direction = 'asc';
                }
                this.renderTable(containerEl, data);
            });
        });
    },

    // ─── Gauge ───

    renderGauge(containerEl, data, controls) {
        const resultFormat = this.getKpiResultFormat(controls);

        let value = data?.value ?? 0;
        const min = data?.min ?? 0;
        const max = data?.max ?? 100;
        const label = data?.label ?? '';
        const thresholds = data?.thresholds ?? [
            {from: 0, to: 33, color: '#EF4444'},
            {from: 33, to: 66, color: '#F59E0B'},
            {from: 66, to: 100, color: '#22C55E'},
        ];

        const rawValue = value;
        if (resultFormat?.multiply) value = value * resultFormat.multiply;

        const pct = Math.min(100, Math.max(0, ((value - min) / (max - min)) * 100));

        let color = thresholds[0]?.color ?? '#22C55E';
        for (const t of thresholds) {
            if (pct >= t.from && pct <= t.to) {
                color = t.color;
                break;
            }
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

    renderSparkline(containerEl, data, controls) {
        const points = data?.points ?? data?.values ?? [];

        if (!points.length) {
            containerEl.innerHTML = '<div class="text-sm text-gray-400 p-4 text-center">No data</div>';
            return;
        }

        const metricKey = controls?.metrics?.[0];
        const metricFmt = metricKey ? this.METRIC_FORMATS[metricKey] : null;
        const lowerIsBetter = metricFmt?.lower_is_better ?? false;

        const current = points[points.length - 1];
        const first = points[0];
        const isUp = lowerIsBetter ? current < first : current >= first;
        const trend = isUp ? 'up' : 'down';
        const color = trend === 'up' ? '#22C55E' : '#EF4444';

        const min = Math.min(...points);
        const max = Math.max(...points);
        const range = max - min || 1;
        const w = 160, h = 40;
        const stepX = w / (points.length - 1);

        const pathD = points.map((p, i) => {
            const x = i * stepX;
            const y = lowerIsBetter ? ((p - min) / range) * h : h - ((p - min) / range) * h;
            return (i === 0 ? 'M' : 'L') + x.toFixed(1) + ' ' + y.toFixed(1);
        }).join(' ');

        const areaD = pathD + ' L' + (w) + ' ' + h + ' L0 ' + h + ' Z';

        let changePct = 0;
        if (first !== 0) changePct = ((current - first) / Math.abs(first)) * 100;

        const gradientId = 'spark-fill-' + Math.random().toString(36).substr(2, 9);

        containerEl.innerHTML = `
            <div class="flex items-stretch gap-3 p-4 h-full">
                <div class="flex-shrink-0 text-right self-center flex flex-col">
                    <span class="text-xl font-bold text-gray-900 dark:text-white">${this.formatNumber(current)}</span>
                    <span class="text-xs ${trend === 'up' ? 'text-green-500' : 'text-red-500'}">${trend === 'up' ? '▲' : '▼'} ${Math.abs(changePct).toFixed(0)}%</span>
                </div>
                <svg viewBox="0 0 ${w} ${h}" class="flex-1 h-full" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="${gradientId}" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="${color}" stop-opacity="0.2"/>
                            <stop offset="100%" stop-color="${color}" stop-opacity="0.02"/>
                        </linearGradient>
                    </defs>
                    <path d="${areaD}" fill="url(#${gradientId})"/>
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

        const yMetric = controls?.metrics?.[0];
        const yFmt = resultFormat || (yMetric ? (this.METRIC_FORMATS[yMetric] || null) : null);
        const yUnit = yFmt?.format === 'percentage' ? '%' : yFmt?.format === 'currency' ? (yFmt?.prefix || '$') : yFmt?.suffix || '';
        const yAxisLabel = (yFmt?.label || 'Value') + (yUnit ? ' (' + yUnit + ')' : '');
        const yMetricName = this.getMetricName(yMetric);

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
                ctx.strokeStyle = '#EF4444';

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

        const mappedDatasets = datasets.map(ds => ({
            ...ds,
            currency: ds.currency ?? (resultFormat?.format === 'currency' ? true : undefined),
            percentage: ds.percentage ?? (resultFormat?.format === 'percentage' ? true : undefined),
            pointRadius: 6,
            pointHoverRadius: 10,
            pointHitRadius: 15,
            pointBackgroundColor: ds.borderColor || ds.backgroundColor || '#3B82F6',
            pointBorderColor: ds.borderColor || ds.backgroundColor || '#3B82F6',
            pointBorderWidth: 2,
            pointHoverBorderWidth: 2,
        }));

        const config = {
            type: 'line',
            data: {labels, datasets: mappedDatasets},
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onHover(e) {
                    const found = e.chart.getElementsAtEventForMode(e, 'nearest', {intersect: true}, false);
                    e.native.target.style.cursor = found.length ? 'pointer' : 'default';
                },
                plugins: {
                    legend: {display: false},
                    tooltip: {
                        callbacks: {
                            title: (ctx) => ctx[0]?.chart.data.labels?.[ctx[0].dataIndex] || '',
                            label: (ctx) => {
                                const dsLabel = ctx.dataset.label || yMetricName;
                                const valY = this.formatMetricValue(ctx.parsed.y, yMetric);
                                return valY + ' ' + dsLabel;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: {display: false},
                        ticks: {maxTicksLimit: 10, font: {size: 10}},
                    },
                    y: {
                        beginAtZero: !reverseY,
                        reverse: reverseY,
                        title: {display: true, text: yAxisLabel},
                        ticks: {
                            font: {size: 10},
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
        };
        this._setAnimation(config, true);
        this.renderChart(containerEl, config);
    },

    // ─── Scatter Plot ───

    renderScatterPlot(containerEl, data, controls) {
        if (!data || !data.datasets || !data.datasets.length) {
            containerEl.innerHTML = '<div class="text-sm text-gray-400 p-4 text-center">No data available</div>';
            return;
        }

        const higherIsWorse = ['position', 'bounce_rate', 'bouncerate', 'cpc', 'cpa', 'cost_per_click'];
        const reverseYColor = higherIsWorse.includes(controls?.metrics?.[0]);
        const reverseXColor = higherIsWorse.includes(controls?.metrics?.[1]);
        const reverseYAxis = controls?.metrics?.[0] === 'position';
        const reverseXAxis = controls?.metrics?.[1] === 'position';

        const resultFormat = this.getKpiResultFormat(controls);
        const xFmt = controls?.metrics?.[1] ? (this.METRIC_FORMATS[controls.metrics[1]] || null) : null;
        const yFmt = resultFormat || (controls?.metrics?.[0] ? (this.METRIC_FORMATS[controls.metrics[0]] || null) : null);

        /* console.log('[ScatterPlot] controls:', controls);
        console.log('[ScatterPlot] metrics:', controls?.metrics);
        console.log('[ScatterPlot] resultFormat:', resultFormat);
        console.log('[ScatterPlot] xFmt:', xFmt, '| yFmt:', yFmt); */

        const formatPoint = (v, fmt, axis) => {
            if (!fmt) {
                const r = v.toLocaleString('en-US', {maximumFractionDigits: 2});
                /* if (v === 0 || (v > 0 && v < 1)) console.log(`[formatPoint][${axis}] v=${v}, fmt=null →`, r); */
                return r;
            }
            let val = fmt.multiply ? v * fmt.multiply : v;
            let r;
            if (fmt.format === 'currency') r = '$' + val.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            else if (fmt.format === 'percentage') r = val.toFixed(1) + '%';
            else r = val.toLocaleString('en-US', {maximumFractionDigits: 2});
            /* console.log(`[formatPoint][${axis}] v=${v}, fmt=${JSON.stringify(fmt)}, val=${val} →`, r); */
            return r;
        };

        const formatUnit = (fmt) => {
            if (!fmt) return '';
            if (fmt.format === 'percentage') return '%';
            if (fmt.format === 'currency') return fmt.prefix || '$';
            if (fmt.suffix) return fmt.suffix;
            return '';
        };
        const yUnit = formatUnit(yFmt);
        const xUnit = formatUnit(xFmt);
        const yAxisLabel = (yFmt?.label || 'Value') + (yUnit ? ' (' + yUnit + ')' : '');
        const xAxisLabel = (xFmt?.label || (controls?.metrics?.[1] || 'X')) + (xUnit ? ' (' + xUnit + ')' : '');

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
                if (reverseYColor) {
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

        const yScaleOpts = {
            type: 'linear',
            title: {display: true, text: yAxisLabel},
            reverse: reverseYAxis,
            ticks: {callback: (v) => formatPoint(v, yFmt, 'Y')}
        };

        const xMetricName = this.getMetricName(controls?.metrics?.[1]);
        const yMetricName = this.getMetricName(controls?.metrics?.[0]);

        const mappedData = JSON.parse(JSON.stringify(data));
        if (mappedData.datasets) {
            mappedData.datasets = mappedData.datasets.map(ds => ({
                ...ds,
                pointRadius: 6,
                pointHoverRadius: 10,
                pointHitRadius: 15,
                pointBorderWidth: 2,
                pointHoverBorderWidth: 2,
            }));
        }

        const config = {
            type: 'scatter',
            data: mappedData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onHover(e) {
                    const found = e.chart.getElementsAtEventForMode(e, 'nearest', {intersect: true}, false);
                    e.native.target.style.cursor = found.length ? 'pointer' : 'default';
                },
                scales: {
                    x: {
                        type: 'linear',
                        position: 'bottom',
                        title: {display: true, text: xAxisLabel},
                        reverse: reverseXAxis,
                        ticks: {
                            callback: (v) => formatPoint(v, xFmt, 'X'),
                        },
                    },
                    y: yScaleOpts,
                },
                plugins: {
                    legend: {display: false},
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const point = ctx.raw;
                                const valX = this.formatMetricValue(point.x, controls?.metrics?.[1]);
                                const valY = this.formatMetricValue(point.y, controls?.metrics?.[0]);
                                const baseLabel = point.label ? (point.label + ' — ') : '';
                                return baseLabel + '(' + valX + ' ' + xMetricName + ', ' + valY + ' ' + yMetricName + ')';
                            },
                        },
                    },
                },
            },
        };
        const hasLineDs = mappedData.datasets.some(ds => ds.type === 'line');
        if (hasLineDs) {
            config.options = config.options || {};
            config.options.animation = {
                x: {
                    type: 'number',
                    easing: 'easeOutQuart',
                    duration: 1000,
                    from(ctx) {
                        if (ctx.type !== 'data' || ctx.mode !== 'default') return undefined;
                        const ds = ctx.chart.data.datasets[ctx.datasetIndex];
                        if (ds?.type === 'line') return NaN;
                        return ctx.raw?.x;
                    },
                },
                y: {
                    type: 'number',
                    easing: 'easeOutQuart',
                    duration: 1000,
                    from(ctx) {
                        if (ctx.type !== 'data' || ctx.mode !== 'default') return undefined;
                        const ds = ctx.chart.data.datasets[ctx.datasetIndex];
                        if (ds?.type === 'line') return 0;
                        return ctx.raw?.y;
                    },
                },
            };
        }

        // Constrain axes to scatter data only (ignore trend line endpoints),
        // with 10 % breathing room so edge points aren't clipped by the grid border.
        // Remove ticks and grid lines in the grace area via afterBuildTicks.
        let scatterPoints = mappedData.datasets?.find(d => d.type === 'scatter')?.data;
        if (scatterPoints && scatterPoints.length > 0) {
            const xs = scatterPoints.map(p => p.x);
            const ys = scatterPoints.map(p => p.y);
            const dataMinX = Math.min(...xs), dataMaxX = Math.max(...xs);
            const dataMinY = Math.min(...ys), dataMaxY = Math.max(...ys);
            let xMin = dataMinX, xMax = dataMaxX;
            let yMin = dataMinY, yMax = dataMaxY;
            const xRange = xMax - xMin || 1;
            const yRange = yMax - yMin || 1;
            xMin -= xRange * 0.1;
            xMax += xRange * 0.1;
            yMin -= yRange * 0.1;
            yMax += yRange * 0.1;
            config.options.scales.x.min = xMin;
            config.options.scales.x.max = xMax;
            config.options.scales.x.afterBuildTicks = (axis) => {
                // When zoomed (axis range differs from initial), let Chart.js auto-generate ticks
                if (Math.abs(axis.min - xMin) > 0.001 || Math.abs(axis.max - xMax) > 0.001) return;
                const filtered = axis.ticks.filter(t => t.value >= dataMinX && t.value <= dataMaxX);
                const values = filtered.map(t => t.value);
                if (values.length > 0) {
                    const maxVal = Math.max(...values);
                    if (maxVal < dataMaxX) filtered.push({value: dataMaxX});
                    const minVal = Math.min(...values);
                    if (minVal > dataMinX) filtered.push({value: dataMinX});
                }
                axis.ticks = filtered;
            };
            config.options.scales.y.min = yMin;
            config.options.scales.y.max = yMax;
            config.options.scales.y.afterBuildTicks = (axis) => {
                // When zoomed (axis range differs from initial), let Chart.js auto-generate ticks
                if (Math.abs(axis.min - yMin) > 0.001 || Math.abs(axis.max - yMax) > 0.001) return;
                const filtered = axis.ticks.filter(t => t.value >= dataMinY && t.value <= dataMaxY);
                const values = filtered.map(t => t.value);
                // Keep the 0 reference tick if it falls within the visible axis range (Bug 1 fix)
                if (0 >= yMin && 0 <= yMax && !values.includes(0)) {
                    filtered.push({value: 0});
                    values.push(0);
                }
                if (values.length > 0) {
                    const maxVal = Math.max(...values);
                    if (maxVal < dataMaxY) filtered.push({value: dataMaxY});
                    const minVal = Math.min(...values);
                    if (minVal > dataMinY) filtered.push({value: dataMinY});
                }
                axis.ticks = filtered;
            };
        }

        // Split cluster point ([[[others]]]) into its own hidden dataset for show/hide toggle
        const mainScatterDs = mappedData.datasets?.find(d => d.type === 'scatter');
        let clusterPoint = null;
        if (mainScatterDs) {
            const ci = mainScatterDs.data.findIndex(p => p._isCluster);
            if (ci >= 0) {
                clusterPoint = mainScatterDs.data.splice(ci, 1)[0];
                delete clusterPoint._isCluster;
                mappedData.datasets.push({
                    type: 'scatter',
                    label: 'Clustered',
                    data: [clusterPoint],
                    backgroundColor: 'rgba(107, 114, 128, 0.5)',
                    borderColor: 'rgba(107, 114, 128, 1)',
                    borderWidth: 2,
                    pointRadius: 8,
                    pointHoverRadius: 14,
                });
            }
        }
        this.renderChart(containerEl, config);

        // Add show/hide toggle for the cluster point
        if (clusterPoint && mainScatterDs) {
            const toggleId = 'cluster-toggle-' + (containerEl._clusterToggleCount = (containerEl._clusterToggleCount || 0) + 1);
            const wrapper = document.createElement('div');
            wrapper.style.cssText = 'display:flex;align-items:center;justify-content:center;gap:6px;margin-top:4px;';
            wrapper.innerHTML = '<label for="' + toggleId + '" style="font-size:11px;color:#9ca3af;cursor:pointer;user-select:none;">Show clustered</label>';
            const input = document.createElement('input');
            input.type = 'checkbox';
            input.id = toggleId;
            input.checked = true;
            input.style.cssText = 'cursor:pointer;accent-color:#6b7280;';
            wrapper.prepend(input);
            containerEl.appendChild(wrapper);

            input.addEventListener('change', () => {
                const chart = Chart.getChart(containerEl.querySelector('canvas'));
                if (!chart || !chart.data || !chart.data.datasets) return;
                const ds = chart.data.datasets.find(d => d.type === 'scatter' && d.label === 'Clustered');
                if (!ds) return;
                const meta = chart.getDatasetMeta(chart.data.datasets.indexOf(ds));
                meta.hidden = !input.checked;
                chart.update();
            });
        }
    },

    // ─── Combo Chart (MACD) ───

    renderComboChart(containerEl, data, controls) {
        if (!data || !data.datasets || !data.datasets.length) {
            containerEl.innerHTML = '<div class="text-sm text-gray-400 p-4 text-center">No data available</div>';
            return;
        }

        const resultFormat = this.getKpiResultFormat(controls);

        const mappedDatasets = data.datasets.map(ds => ({
            ...ds,
            currency: ds.currency ?? (resultFormat?.format === 'currency' ? true : undefined),
            percentage: ds.percentage ?? (resultFormat?.format === 'percentage' ? true : undefined),
        }));

        const config = {
            type: 'bar',
            data: {...data, datasets: mappedDatasets},
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {grid: {display: false}},
                },
                plugins: {
                    legend: {display: true, position: 'bottom'},
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                let val = ctx.parsed.y;
                                if (resultFormat?.multiply) val = val * resultFormat.multiply;
                                if (ctx.dataset?.currency) return this.formatCurrency(val);
                                if (ctx.dataset?.percentage) return val.toFixed(1) + '%';
                                return this.formatNumber(val);
                            },
                        },
                    },
                },
            },
        };
        this._setAnimation(config, false);
        this.renderChart(containerEl, config);
    },

    // ─── Chart.js Helper ───

    _setAnimation(config, isLine) {
        if (isLine) {
            config.options = config.options || {};
            config.options.animation = {
                x: {
                    type: 'number',
                    easing: 'linear',
                    duration: 200,
                    from: NaN,
                    delay(ctx) {
                        if (ctx.type !== 'data' || ctx.mode !== 'default') return 0;
                        const count = ctx.chart.data.datasets[ctx.datasetIndex]?.data?.length || 1;
                        return ((1000 - 200) / (count - 1 || 1)) * ctx.dataIndex;
                    },
                },
                y: {
                    type: 'number',
                    easing: 'easeOutQuart',
                    duration: 200,
                    from: (ctx) => ctx.type === 'data' && ctx.mode === 'default' ? 0 : undefined,
                },
            };
        } else {
            config.options = config.options || {};
            config.options.animation = {
                duration: 200,
                easing: 'easeOutQuart',
                delay(ctx) {
                    if (ctx.type !== 'data' || ctx.mode !== 'default') return 0;
                    const count = ctx.chart.data.labels?.length || 1;
                    return ((1000 - 200) / (count - 1 || 1)) * ctx.dataIndex;
                },
            };
        }
    },

    _ensureZoomPlugin(callback) {
        if (typeof Chart === 'undefined') {
            callback();
            return;
        }
        if (Chart.registry.plugins.get('zoom')) {
            callback();
            return;
        }
        const existing = document.querySelector('script[src*="chartjs-plugin-zoom"]');
        if (existing) {
            existing.addEventListener('load', callback, {once: true});
            existing.addEventListener('error', callback, {once: true});
            return;
        }
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.2.0/dist/chartjs-plugin-zoom.min.js';
        script.onload = callback;
        script.onerror = callback;
        document.head.appendChild(script);
    },

    renderChart(containerEl, config) {
        this._pinnedTooltips.delete(containerEl);
        const isDark = document.documentElement.classList.contains('dark');
        if (config.options && config.options.scales) {
            for (const axis of Object.values(config.options.scales)) {
                if (axis.ticks) {
                    axis.ticks.color = isDark ? '#A1A1AA' : '#71717A';
                }
                if (axis.grid) {
                    axis.grid.color = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.06)';
                }
            }
        }
        if (config.options?.plugins?.legend?.labels) {
            config.options.plugins.legend.labels.color = isDark ? '#D4D4D8' : '#52525B';
        }

        config.options = config.options || {};
        config.options.plugins = config.options.plugins || {};
        config.options.plugins.tooltip = config.options.plugins.tooltip || {};
        config.options.plugins.tooltip.enabled = false;
        config.options.plugins.tooltip.external = (ctx) => this._externalTooltipHandler(ctx);
        config.options.plugins.zoom = {
            zoom: {
                wheel: {enabled: true, speed: 0.05, modifierKey: 'ctrl'},
                pinch: {enabled: true},
                mode: 'xy',
            },
            pan: {
                enabled: true,
                mode: 'xy',
                threshold: 10,
            },
        };

        const canvas = document.createElement('canvas');
        containerEl.innerHTML = '';
        containerEl.appendChild(canvas);

        const createChart = () => {
            const chart = new Chart(canvas, config);
            this._chartInstances.set(containerEl, chart);
            this._attachTooltipPin(chart, canvas, containerEl);
            canvas.addEventListener('dblclick', () => {
                if (chart.options?.plugins?.zoom) chart.resetZoom();
            });
        };

        if (typeof Chart === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js';
            script.onload = () => {
                this._ensureZoomPlugin(createChart);
            };
            document.head.appendChild(script);
        } else {
            this._ensureZoomPlugin(createChart);
        }
    },

    _attachTooltipPin(chart, canvas, containerEl) {
        canvas.addEventListener('click', (e) => {
            // Resolve container from canvas current parent — handles popOut/popIn
            const container = canvas.parentNode || containerEl;
            const pinned = this._pinnedTooltips.get(container);
            if (pinned) {
                this._pinnedTooltips.delete(container);
                const tooltipEl = container.querySelector('.chart-tooltip');
                if (tooltipEl) {
                    tooltipEl.style.pointerEvents = 'none';
                    tooltipEl.style.opacity = '0';
                    tooltipEl.innerHTML = '';
                }
                return;
            }
            const elements = chart.getElementsAtEventForMode(e, 'nearest', {intersect: true}, false);
            if (elements.length === 0) return;
            const widgetJson = this._widgetData.get(container);
            const controls = widgetJson?.controls;
            const resultFormat = this.getKpiResultFormat(controls);
            const chartType = chart.config.type;
            const xMetricName = this.getMetricName(controls?.metrics?.[1]);
            const yMetricName = this.getMetricName(controls?.metrics?.[0]);

            const anomalyDates = widgetJson?.data?.anomaly_dates ?? [];
            const anomalySet = anomalyDates.length ? new Set(anomalyDates) : null;

            let html = '';
            elements.forEach((el) => {
                const ds = chart.data.datasets[el.datasetIndex];
                const raw = ds.data[el.index];
                let val;
                if (chartType === 'scatter') {
                    const point = raw;
                    const valX = this.formatMetricValue(point.x, controls?.metrics?.[1]);
                    const valY = this.formatMetricValue(point.y, controls?.metrics?.[0]);
                    const baseLabel = point.label ? (point.label + ' — ') : '';
                    val = baseLabel + '(' + valX + ' ' + xMetricName + ', ' + valY + ' ' + yMetricName + ')';
                    } else {
                    const label = chart.data.labels?.[el.index] || '';
                    let v = typeof raw === 'object' ? (raw.y ?? 0) : raw;
                    if (resultFormat?.multiply) v = v * resultFormat.multiply;
                    if (ds.currency || resultFormat?.format === 'currency') {
                        val = this.formatCurrency(v);
                    } else if (ds.percentage || resultFormat?.format === 'percentage') {
                        val = v.toFixed(1) + '%';
                    } else {
                        val = this.formatNumber(v);
                    }
                    const dsLabel = ds.label || yMetricName;
                    val = (label ? label + ' — ' : '') + val + ' ' + dsLabel;
                }
                const isLine = ds.type === 'line';
                const color = ds.borderColor || ds.backgroundColor || '#3B82F6';
                const colorStr = Array.isArray(color) ? color[el.index] : color;
                html += '<div style="display:flex;align-items:center;gap:6px;padding:1px 0;">' +
                    '<span style="width:8px;height:8px;border-radius:50%;background:' + colorStr + ';flex-shrink:0;"></span>' +
                    (isLine ? '<span style="font-weight:500;color:#9ca3af;font-size:11px;">Trend: </span>' : '') +
                    '<span style="font-weight:600;">' + val + '</span>' +
                    '</div>';

                if (anomalySet) {
                    const dateLabel = chart.data.labels?.[el.index];
                    if (dateLabel && anomalySet.has(dateLabel)) {
                        html += '<div style="display:flex;align-items:center;gap:4px;padding:1px 0;margin-top:2px;">' +
                            '<span style="color:#EF4444;font-weight:600;font-size:11px;">⚠ Anomaly detected</span>' +
                            '</div>';
                    }
                }
            });
            const meta = chart.getDatasetMeta(elements[0].datasetIndex);
            const element = meta.data[elements[0].index];
            this._pinnedTooltips.set(container, {
                html,
                caretX: element.x,
                caretY: element.y,
            });
            this._renderPinnedTooltip(container);
        });
    },

    /**
     * Pop a widget's canvas/chart into a different container (fullscreen modal).
     */
    popOutWidget(containerEl, targetEl) {
        const json = this._widgetData.get(containerEl);
        if (!json) return;

        // Ensure the chart is rendered before moving its canvas
        this.flushRender(containerEl);

        // Transfer widget data and pinned tooltip to the modal container
        this._widgetData.set(targetEl, json);
        if (this._pinnedTooltips.has(containerEl)) {
            this._pinnedTooltips.set(targetEl, this._pinnedTooltips.get(containerEl));
            this._pinnedTooltips.delete(containerEl);
        }

        const canvas = containerEl.querySelector('canvas');
        if (canvas) {
            const tooltipEl = containerEl.querySelector('.chart-tooltip');
            targetEl.innerHTML = '';
            targetEl.appendChild(canvas);
            if (tooltipEl) targetEl.appendChild(tooltipEl);
            // Move remaining children (cluster toggle, etc.) into the modal
            while (containerEl.children.length > 0) {
                targetEl.appendChild(containerEl.children[0]);
            }
            const chart = this._chartInstances.get(containerEl);
            if (chart) {
                this._chartInstances.set(targetEl, chart);
                this._chartInstances.delete(containerEl);
                if (this._popOutObserver) this._popOutObserver.disconnect();
                this._popOutObserver = new ResizeObserver(() => {
                    chart.resize();
                });
                this._popOutObserver.observe(targetEl);
                // Resize synchronously after the DOM move — Chart.js reads
                // canvas.parentElement dimensions immediately, so the chart
                // renders at the correct modal container size on first paint.
                chart.resize();
                // Re-render pinned tooltip in the new container if one was pinned
                if (this._pinnedTooltips.has(targetEl)) {
                    this._renderPinnedTooltip(targetEl);
                }
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
            const tooltipEl = targetEl.querySelector('.chart-tooltip');
            containerEl.innerHTML = '';
            containerEl.appendChild(canvas);
            if (tooltipEl) containerEl.appendChild(tooltipEl);
            // Move remaining children (cluster toggle, etc.) back to the widget
            while (targetEl.children.length > 0) {
                containerEl.appendChild(targetEl.children[0]);
            }
            // Transfer widget data and pinned tooltip back to original container
            if (this._widgetData.has(targetEl)) {
                this._widgetData.set(containerEl, this._widgetData.get(targetEl));
                this._widgetData.delete(targetEl);
            }
            if (this._pinnedTooltips.has(targetEl)) {
                this._pinnedTooltips.set(containerEl, this._pinnedTooltips.get(targetEl));
                this._pinnedTooltips.delete(targetEl);
            }
            const chart = this._chartInstances.get(targetEl);
            if (chart) {
                this._chartInstances.set(containerEl, chart);
                this._chartInstances.delete(targetEl);
                chart.resize();
                // Re-render pinned tooltip in the original container if one was pinned
                if (this._pinnedTooltips.has(containerEl)) {
                    this._renderPinnedTooltip(containerEl);
                }
            }
            return;
        }
        // HTML widget — content was cloned, not moved. Just clear the modal.
        targetEl.innerHTML = '';
    },

    _renderWidget(widget_type, containerEl, data, controls) {
        containerEl.style.padding = '';
        containerEl.style.overflow = '';
        switch (widget_type) {
            case 'tile':
                this.renderTile(containerEl, data, controls);
                break;
            case 'line_chart':
                this.renderLineChart(containerEl, data, controls);
                break;
            case 'bar_chart':
                this.renderBarChart(containerEl, data, controls);
                break;
            case 'table':
                this.renderTable(containerEl, data);
                break;
            case 'gauge':
                this.renderGauge(containerEl, data, controls);
                break;
            case 'sparkline':
                this.renderSparkline(containerEl, data, controls);
                break;
            case 'anomaly_list':
                this.renderAnomalyList(containerEl, data);
                break;
            case 'anomaly_chart':
                this.renderAnomalyChart(containerEl, data, controls);
                break;
            case 'scatter_plot':
                this.renderScatterPlot(containerEl, data, controls);
                break;
            case 'combo_chart':
                this.renderComboChart(containerEl, data, controls);
                break;
            default:
                containerEl.innerHTML = '<div class="text-sm text-gray-400 p-4 text-center">Unknown widget type: ' + widget_type + '</div>';
        }
    },

    // ─── Formatters ───

    formatNumber(n) {
        if (n == null || isNaN(n)) return '—';
        return Number(n).toLocaleString('en-US', {maximumFractionDigits: 1});
    },

    _formatTableNumber(n) {
        if (n == null || isNaN(n)) return '—';
        const num = Number(n);
        if (Number.isInteger(num)) return num.toLocaleString('en-US', {maximumFractionDigits: 0});
        return num.toLocaleString('en-US', {minimumFractionDigits: 4, maximumFractionDigits: 4});
    },

    formatCurrency(n) {
        if (n == null || isNaN(n)) return '—';
        return '$' + Number(n).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    },

    // ─── Metric Display Helpers ───

    formatMetricValue(value, metricKey) {
        const fmt = this.METRIC_FORMATS[metricKey];
        if (!fmt) return this.formatNumber(value);
        let v = value;
        if (fmt.multiply) v = v * fmt.multiply;
        if (fmt.format === 'currency') return (fmt.prefix || '$') + this.formatNumber(v);
        if (fmt.format === 'percentage') return v.toFixed(1) + '%';
        return this.formatNumber(v);
    },

    getMetricName(metricKey) {
        const fmt = this.METRIC_FORMATS[metricKey];
        return fmt?.label || metricKey || 'Value';
    },

    // ─── External HTML Tooltip ───

    _setupTooltip(chart, containerEl) {
        let tooltipEl = containerEl.querySelector('.chart-tooltip');
        if (!tooltipEl) {
            tooltipEl = document.createElement('div');
            tooltipEl.className = 'chart-tooltip';
            tooltipEl.style.position = 'absolute';
            tooltipEl.style.pointerEvents = 'none';
            tooltipEl.style.opacity = '0';
            tooltipEl.style.transition = 'opacity 0.15s ease';
            tooltipEl.style.zIndex = '100';
            tooltipEl.style.whiteSpace = 'nowrap';
            containerEl.appendChild(tooltipEl);
        }
        return tooltipEl;
    },

    _positionTooltip(tooltipEl, containerEl, caretX, caretY) {
        const containerRect = containerEl.getBoundingClientRect();
        const canvas = containerEl.querySelector('canvas');
        if (!canvas) return;
        const chartRect = canvas.getBoundingClientRect();
        const offsetLeft = chartRect.left - containerRect.left;
        const offsetTop = chartRect.top - containerRect.top;

        const cx = caretX + offsetLeft;
        const cy = caretY + offsetTop;
        const tw = tooltipEl.offsetWidth;
        const th = tooltipEl.offsetHeight;
        const cw = containerRect.width;
        const ch = containerRect.height;

        // Dynamic max-width: fit inside container with 16px margin
        tooltipEl.style.maxWidth = Math.max(160, cw - 32) + 'px';

        // Re-check width after maxWidth constraint
        const finalTw = tooltipEl.offsetWidth;

        // Smart quadrant-based positioning
        const gap = 8;
        const preferRight = cx < cw / 2;
        const preferBelow = cy < ch / 2;

        let left, top;

        // Horizontal: prefer opposite side from caret
        if (preferRight) {
            left = cx + gap;
            if (left + finalTw > cw - gap) left = cx - finalTw - gap;
        } else {
            left = cx - finalTw - gap;
            if (left < gap) left = cx + gap;
        }
        // Clamp horizontal
        left = Math.max(gap, Math.min(left, cw - finalTw - gap));

        // Vertical: prefer opposite side from caret
        if (preferBelow) {
            top = cy + gap;
            if (top + th > ch - gap) top = cy - th - gap;
        } else {
            top = cy - th - gap;
            if (top < gap) top = cy + gap;
        }
        // Clamp vertical
        top = Math.max(gap, Math.min(top, ch - th - gap));

        tooltipEl.style.left = Math.round(left) + 'px';
        tooltipEl.style.top = Math.round(top) + 'px';
    },

    _externalTooltipHandler(context) {
        try {
            const {chart, tooltip} = context;
            if (!chart || !chart.canvas) return;
            const container = chart.canvas.parentNode;
            if (!container) return;
            const tooltipEl = this._setupTooltip(chart, container);

            // If pinned, always show pinned data regardless of hover
            const pinned = this._pinnedTooltips.get(container);
            if (pinned) {
                this._renderPinnedTooltip(container);
                return;
            }

            if (!tooltip || tooltip.opacity < 0.01) {
                tooltipEl.style.opacity = '0';
                tooltipEl.innerHTML = '';
                tooltipEl.style.pointerEvents = 'none';
                return;
            }

            const isDark = document.documentElement.classList.contains('dark');
            const bg = isDark ? '#1F2937' : '#FFF';
            const textColor = isDark ? '#F3F4F6' : '#111827';
            const borderColor = isDark ? '#374151' : '#E5E7EB';

            tooltipEl.style.background = bg;
            tooltipEl.style.color = textColor;
            tooltipEl.style.border = '1px solid ' + borderColor;
            tooltipEl.style.borderRadius = '8px';
            tooltipEl.style.padding = '8px 12px';
            tooltipEl.style.fontSize = '12px';
            tooltipEl.style.lineHeight = '1.5';
            tooltipEl.style.boxShadow = isDark ? '0 4px 16px rgba(0,0,0,0.4)' : '0 4px 16px rgba(0,0,0,0.12)';
            tooltipEl.style.fontFamily = 'inherit';

            const widgetJson = this._widgetData.get(container);
            const ctrl = widgetJson?.controls;
            const rFmt = this.getKpiResultFormat(ctrl);
            const chartType = chart.config.type;
            const xMN = this.getMetricName(ctrl?.metrics?.[1]);
            const yMN = this.getMetricName(ctrl?.metrics?.[0]);

            const anomalyDates = widgetJson?.data?.anomaly_dates ?? [];
            const anomalySet = anomalyDates.length ? new Set(anomalyDates) : null;

            let html = '';

            if (tooltip.body?.length) {
                tooltip.body.forEach((body, i) => {
                    const dp = tooltip.dataPoints?.[i];
                    if (!dp) return;
                    let val;
                    if (chartType === 'scatter') {
                        const point = dp.raw;
                        const vX = this.formatMetricValue(point.x, ctrl?.metrics?.[1]);
                        const vY = this.formatMetricValue(point.y, ctrl?.metrics?.[0]);
                        const baseLabel = point.label ? (point.label + ' — ') : '';
                        val = baseLabel + '(' + vX + ' ' + xMN + ', ' + vY + ' ' + yMN + ')';
                    } else {
                        const label = chart.data.labels?.[dp.dataIndex] || '';
                        let v = typeof dp.raw === 'object' ? (dp.raw.y ?? 0) : dp.raw;
                        if (rFmt?.multiply) v = v * rFmt.multiply;
                        if (dp.dataset.currency || rFmt?.format === 'currency') {
                            val = this.formatCurrency(v);
                        } else if (dp.dataset.percentage || rFmt?.format === 'percentage') {
                            val = v.toFixed(1) + '%';
                        } else {
                            val = this.formatNumber(v);
                        }
                        const dsLabel = dp.dataset.label || yMN;
                        val = (label ? label + ' — ' : '') + val + ' ' + dsLabel;
                    }
                    const isLine = dp.dataset.type === 'line';
                    const color = dp.dataset.borderColor || dp.dataset.backgroundColor || '#3B82F6';
                    html += '<div style="display:flex;align-items:center;gap:6px;padding:1px 0;">' +
                        '<span style="width:8px;height:8px;border-radius:50%;background:' + color + ';flex-shrink:0;"></span>' +
                        (isLine ? '<span style="font-weight:500;color:#9ca3af;font-size:11px;">Trend: </span>' : '') +
                        '<span style="font-weight:600;">' + val + '</span>' +
                        '</div>';

                    if (anomalySet) {
                        const dateLabel = chart.data.labels?.[dp.dataIndex];
                        if (dateLabel && anomalySet.has(dateLabel)) {
                            html += '<div style="display:flex;align-items:center;gap:4px;padding:1px 0;margin-top:2px;">' +
                                '<span style="color:#EF4444;font-weight:600;font-size:11px;">⚠ Anomaly detected</span>' +
                                '</div>';
                        }
                    }
                });
            }

            tooltipEl.innerHTML = html;
            tooltipEl.style.pointerEvents = 'none';

            this._positionTooltip(tooltipEl, container, tooltip.caretX, tooltip.caretY);
            tooltipEl.style.opacity = '1';
        } catch (e) {
            console.warn('[Tooltip]', e);
        }
    },

    _renderPinnedTooltip(container) {
        const pinned = this._pinnedTooltips.get(container);
        if (!pinned) return;
        const tooltipEl = container.querySelector('.chart-tooltip');
        if (!tooltipEl) return;

        const isDark = document.documentElement.classList.contains('dark');
        const bg = isDark ? '#1F2937' : '#FFF';
        const textColor = isDark ? '#F3F4F6' : '#111827';
        const borderColor = isDark ? '#374151' : '#E5E7EB';

        tooltipEl.style.background = bg;
        tooltipEl.style.color = textColor;
        tooltipEl.style.border = '1px solid ' + borderColor;
        tooltipEl.style.borderRadius = '8px';
        tooltipEl.style.padding = '8px 12px';
        tooltipEl.style.fontSize = '12px';
        tooltipEl.style.lineHeight = '1.5';
        tooltipEl.style.boxShadow = isDark ? '0 4px 16px rgba(0,0,0,0.4)' : '0 4px 16px rgba(0,0,0,0.12)';
        tooltipEl.style.fontFamily = 'inherit';
        tooltipEl.style.pointerEvents = 'auto';
        tooltipEl.innerHTML = pinned.html;

        this._positionTooltip(tooltipEl, container, pinned.caretX, pinned.caretY);
        tooltipEl.style.opacity = '1';
    },
};
