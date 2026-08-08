export function jointDashboard(config = {}) {
    return {
        isLoading: false,
        chartRendered: false,
        chartInstance: null,
        dateStart: config.dateStart || '',
        dateEnd: config.dateEnd || '',
        channels: config.channels || {},
        metricsDict: config.metricsDict || {},
        availableAccounts: config.availableAccounts || {},
        analysisLevelOptions: config.analysisLevelOptions || {},
        lagOptions: config.lagOptions || {},
        curveA: { channel: '', asset: '', metric: '', level: 'zscore', lag: '0' },
        curveB: { channel: '', asset: '', metric: '', level: 'zscore', lag: '0' },
        chartData: null,
        correlation: null,
        subtitle: '',
        scatterChartInstance: null,
        rollingChartInstance: null,
        selectedPlay: null,

        allPlays: config.allPlays || [],

        getAvailablePlays() {
            let availableKeys = Object.keys(this.channels);
            return this.allPlays.filter(play => {
                return play.requires.length === 0 || play.requires.every(req => availableKeys.includes(req));
            });
        },

        applyPlay(play) {
            this.selectedPlay = play;
            
            let assetA = this.curveA.channel === play.config.curveA.channel ? this.curveA.asset : '';
            let assetB = this.curveB.channel === play.config.curveB.channel ? this.curveB.asset : '';
            
            if (play.config.curveA.channel && play.config.curveA.channel === play.config.curveB.channel && assetA) {
                assetB = assetA;
            }
            
            this.curveA.channel = play.config.curveA.channel;
            this.curveB.channel = play.config.curveB.channel;

            this.$nextTick(() => {
                this.curveA = { ...play.config.curveA, asset: assetA };
                this.curveB = { ...play.config.curveB, asset: assetB };
            });
        },

        transformData(dates, values, level, lag, targetStart, targetEnd) {
            let resDates = [...dates];
            let resValues = [...values];

            if (level === 'diff1' || level === 'diff2') {
                let newDates = [];
                let newVals = [];
                for (let i = 1; i < resValues.length; i++) {
                    newDates.push(resDates[i]);
                    if (resValues[i] === null || resValues[i-1] === null) {
                        newVals.push(null);
                    } else {
                        newVals.push(resValues[i] - resValues[i-1]);
                    }
                }
                resDates = newDates;
                resValues = newVals;
            }
            if (level === 'diff2') {
                let newDates = [];
                let newVals = [];
                for (let i = 1; i < resValues.length; i++) {
                    newDates.push(resDates[i]);
                    if (resValues[i] === null || resValues[i-1] === null) {
                        newVals.push(null);
                    } else {
                        newVals.push(resValues[i] - resValues[i-1]);
                    }
                }
                resDates = newDates;
                resValues = newVals;
            }

            let l = parseInt(lag, 10);
            if (l !== 0) {
                let shiftedVals = [];
                for (let i = 0; i < resDates.length; i++) {
                    let sourceIdx = i - l;
                    if (sourceIdx >= 0 && sourceIdx < resValues.length) {
                        shiftedVals.push(resValues[sourceIdx]);
                    } else {
                        shiftedVals.push(null);
                    }
                }
                resValues = shiftedVals;
            }

            let finalDates = [];
            let finalValues = [];
            for (let i = 0; i < resDates.length; i++) {
                if (resDates[i] >= targetStart && resDates[i] <= targetEnd) {
                    finalDates.push(resDates[i]);
                    finalValues.push(resValues[i]);
                }
            }

            if (level === 'zscore') {
                let validVals = finalValues.filter(v => v !== null);
                if (validVals.length > 1) {
                    let mean = validVals.reduce((a, b) => a + b, 0) / validVals.length;
                    let variance = validVals.reduce((a, b) => a + Math.pow(b - mean, 2), 0) / validVals.length;
                    let stdDev = Math.sqrt(variance);
                    if (stdDev === 0) stdDev = 1;

                    for (let i = 0; i < finalValues.length; i++) {
                        if (finalValues[i] !== null) {
                            finalValues[i] = (finalValues[i] - mean) / stdDev;
                        }
                    }
                }
            }

            return { dates: finalDates, values: finalValues };
        },

        calculatePearson(arr1, arr2) {
            let valid1 = [];
            let valid2 = [];
            for (let i = 0; i < arr1.length; i++) {
                if (arr1[i] !== null && arr2[i] !== null) {
                    valid1.push(arr1[i]);
                    valid2.push(arr2[i]);
                }
            }

            if (valid1.length < 3) return null;

            const n = valid1.length;
            const sum1 = valid1.reduce((a, b) => a + b, 0);
            const sum2 = valid2.reduce((a, b) => a + b, 0);
            const sum1Sq = valid1.reduce((a, b) => a + b * b, 0);
            const sum2Sq = valid2.reduce((a, b) => a + b * b, 0);
            const pSum = valid1.reduce((acc, val, i) => acc + val * valid2[i], 0);

            const num = pSum - (sum1 * sum2 / n);
            const den = Math.sqrt((sum1Sq - sum1 * sum1 / n) * (sum2Sq - sum2 * sum2 / n));

            if (den === 0) return 0;
            return num / den;
        },

        calculateRollingPearson(arr1, arr2, windowSize) {
            let rolling = [];
            for (let i = 0; i < arr1.length; i++) {
                if (i < windowSize - 1) {
                    rolling.push(null);
                } else {
                    let slice1 = arr1.slice(i - windowSize + 1, i + 1);
                    let slice2 = arr2.slice(i - windowSize + 1, i + 1);
                    rolling.push(this.calculatePearson(slice1, slice2));
                }
            }
            return rolling;
        },

        initDashboard() {
            window.addEventListener('joint-data-loaded', (e) => {
                let payload = e.detail;
                if (payload && payload[0]) payload = payload[0];
                if (payload && payload.data) payload = payload.data;
                
                this.chartData = payload;
                if (this.chartData && this.chartData.curveA && this.chartData.curveB) {
                    this.renderChart();
                } else {
                    console.error("Invalid chart data payload received:", e.detail);
                }
                this.isLoading = false;
                this.chartRendered = true;
            });
        },

        isReadyToFetch() {
            return this.curveA.channel && this.curveA.asset && this.curveA.metric &&
                   this.curveB.channel && this.curveB.asset && this.curveB.metric &&
                   this.dateStart && this.dateEnd;
        },

        fetchData(wireFetch) {
            if (!this.isReadyToFetch()) return;
            this.isLoading = true;
            if (this.chartRendered) this.chartRendered = true;
            if (typeof wireFetch === 'function') {
                wireFetch(this.curveA, this.curveB, this.dateStart, this.dateEnd);
            }
        },

        getCorrelationClass() {
            if (!this.correlation) return 'corr-weak';
            const coef = this.correlation;
            if (coef > 0.4) return 'corr-strong-pos';
            if (coef < -0.4) return 'corr-strong-neg';
            return 'corr-weak';
        },

        getCorrelationIcon() {
            if (!this.correlation) return '≈';
            const coef = this.correlation;
            if (coef > 0.4) return '↗';
            if (coef < -0.4) return '↘';
            return '≈';
        },

        destroyCharts() {
            ['jointChart', 'scatterChart', 'rollingChart'].forEach(id => {
                const canvas = document.getElementById(id);
                if (!canvas) return;
                const existing = Chart.getChart(canvas);
                if (existing) {
                    existing.stop();
                    existing.destroy();
                }
            });
            this.chartInstance = null;
            this.scatterChartInstance = null;
            this.rollingChartInstance = null;
        },

        renderChart() {
            if (typeof Chart === 'undefined' && window.importChartJs) {
                window.importChartJs().then(module => {
                    window.Chart = module.default;
                    this.renderChart();
                }).catch(err => console.error("Failed to load Chart.js", err));
                return;
            }

            this.destroyCharts();

            const targetStart = this.chartData.originalStartDate;
            const targetEnd = this.chartData.originalEndDate;

            const rawA = this.chartData.curveA;
            const rawB = this.chartData.curveB;

            const dataA = this.transformData(rawA.dates, rawA.values, this.curveA.level, this.curveA.lag, targetStart, targetEnd);
            const dataB = this.transformData(rawB.dates, rawB.values, this.curveB.level, this.curveB.lag, targetStart, targetEnd);

            const pearson = this.calculatePearson(dataA.values, dataB.values);
            this.correlation = pearson;

            let titleA = rawA.name;
            if (this.curveA.level === 'diff1') titleA = 'Δ ' + titleA;
            if (this.curveA.level === 'diff2') titleA = 'ΔΔ ' + titleA;
            if (this.curveA.level === 'zscore') titleA = 'Z-Score ' + titleA;
            if (parseInt(this.curveA.lag) !== 0) titleA += ` (Lag ${this.curveA.lag})`;

            let titleB = rawB.name;
            if (this.curveB.level === 'diff1') titleB = 'Δ ' + titleB;
            if (this.curveB.level === 'diff2') titleB = 'ΔΔ ' + titleB;
            if (this.curveB.level === 'zscore') titleB = 'Z-Score ' + titleB;
            if (parseInt(this.curveB.lag) !== 0) titleB += ` (Lag ${this.curveB.lag})`;

            this.subtitle = `${titleA} vs ${titleB}`;

            const isDarkMode = document.documentElement.classList.contains('dark');
            const textColor = isDarkMode ? '#9ca3af' : '#6b7280';
            const gridColor = isDarkMode ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';

            const ctx = document.getElementById("jointChart").getContext('2d');
            this.chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dataA.dates,
                    datasets: [
                        {
                            label: titleA,
                            data: dataA.values,
                            borderColor: '#00a7f9',
                            backgroundColor: 'rgba(0, 167, 249, 0.1)',
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 6,
                            yAxisID: 'yA',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: titleB,
                            data: dataB.values,
                            borderColor: '#f43f5e',
                            backgroundColor: 'rgba(244, 63, 94, 0.1)',
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 6,
                            yAxisID: 'yB',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    layout: {
                        padding: { top: 20, bottom: 20, left: 10, right: 10 }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            labels: { color: textColor }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: gridColor },
                            ticks: { color: textColor }
                        },
                        yA: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            grid: { color: gridColor },
                            ticks: { color: '#00a7f9' },
                            title: {
                                display: true,
                                text: titleA,
                                color: '#00a7f9',
                                font: { weight: 'bold' }
                            }
                        },
                        yB: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: { drawOnChartArea: false },
                            ticks: { color: '#f43f5e' },
                            title: {
                                display: true,
                                text: titleB,
                                color: '#f43f5e',
                                font: { weight: 'bold' }
                            }
                        }
                    }
                }
            });

            const scatterData = [];
            for(let i=0; i<dataA.values.length; i++) {
                if (dataA.values[i] !== null && dataB.values[i] !== null) {
                    scatterData.push({ x: dataA.values[i], y: dataB.values[i] });
                }
            }

            const scatterCtx = document.getElementById("scatterChart").getContext('2d');
            this.scatterChartInstance = new Chart(scatterCtx, {
                type: 'scatter',
                data: {
                    datasets: [{
                        label: 'Distribution',
                        data: scatterData,
                        backgroundColor: '#8b5cf6',
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    layout: {
                        padding: { top: 20, bottom: 20, left: 10, right: 10 }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `(${context.parsed.x}, ${context.parsed.y})`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: gridColor },
                            ticks: { color: textColor },
                            title: {
                                display: true,
                                text: titleA,
                                color: textColor
                            }
                        },
                        y: {
                            grid: { color: gridColor },
                            ticks: { color: textColor },
                            title: {
                                display: true,
                                text: titleB,
                                color: textColor
                            }
                        }
                    }
                }
            });

            const rollingData = this.calculateRollingPearson(dataA.values, dataB.values, 7);
            const rollingCtx = document.getElementById("rollingChart").getContext('2d');
            this.rollingChartInstance = new Chart(rollingCtx, {
                type: 'line',
                data: {
                    labels: dataA.dates,
                    datasets: [{
                        label: '7-Day Rolling Pearson Correlation',
                        data: rollingData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    layout: {
                        padding: { top: 20, bottom: 20, left: 10, right: 10 }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: { labels: { color: textColor } }
                    },
                    scales: {
                        x: {
                            grid: { color: gridColor },
                            ticks: { color: textColor }
                        },
                        y: {
                            min: -1,
                            max: 1,
                            grid: { color: gridColor },
                            ticks: { color: textColor }
                        }
                    }
                }
            });
        }
    };
}

if (typeof window !== 'undefined') {
    window.jointDashboard = jointDashboard;
}
