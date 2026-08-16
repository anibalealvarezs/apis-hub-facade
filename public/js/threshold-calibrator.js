/**
 * Alert Threshold Calibrator & Historical Simulation Component
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('thresholdCalibrator', (config = {}) => ({
        upper: config.upper,
        lower: config.lower,
        unit: config.unit,
        calcLines: config.calcLines,
        sourceType: config.sourceType,
        sourceConfig: config.sourceConfig,
        points: config.points || [],

        get currentVal() {
            if (this.points && this.points.length > 0) {
                return this.points[this.points.length - 1];
            }
            let up = parseFloat(this.upper);
            let low = parseFloat(this.lower);
            if (!isNaN(up) && !isNaN(low)) return (up + low) / 2;
            if (!isNaN(up)) return up * 0.85;
            if (!isNaN(low)) return low * 1.15;
            return null;
        },
        get minVal() {
            if (this.points && this.points.length > 0) {
                return Math.min(...this.points);
            }
            let cur = this.currentVal;
            return cur !== null ? parseFloat((cur * 0.75).toFixed(2)) : null;
        },
        get maxVal() {
            if (this.points && this.points.length > 0) {
                return Math.max(...this.points);
            }
            let cur = this.currentVal;
            return cur !== null ? parseFloat((cur * 1.25).toFixed(2)) : null;
        },
        get avgVal() {
            if (this.points && this.points.length > 0) {
                let sum = this.points.reduce((a, b) => a + b, 0);
                return (sum / (this.points.length || 1));
            }
            return this.currentVal;
        },
        get stdDev() {
            let avg = this.avgVal;
            if (avg === null) return 0;
            if (this.points && this.points.length > 0) {
                let squareDiffs = this.points.map(v => Math.pow(v - avg, 2));
                let avgSquareDiff = squareDiffs.reduce((a, b) => a + b, 0) / (squareDiffs.length || 1);
                return Math.sqrt(avgSquareDiff);
            }
            return Math.abs(avg * 0.10);
        },
        get dataset() {
            if (this.points && this.points.length > 0) {
                return this.points;
            }
            let cur = this.currentVal;
            if (cur === null) return [];

            // Generate a realistic standard Gaussian distribution (30 data points) around current value
            // with realistic fluctuation factors (-2.5σ to +2.5σ)
            let sd = this.stdDev || (Math.abs(cur) * 0.15) || 1.0;
            let pts = [];
            let variations = [
                -2.1, -1.5, -0.8, -0.4, -1.2, 0.2, -0.1, 0.5, -1.8, 0.7,
                -0.2, 0.4, -0.6, 1.4, -0.5, 0.8, -2.4, 0.3, -0.2, 1.6,
                -0.4, 0.2, -1.1, 0.5, -0.3, 1.9, -0.6, 0.2, -1.4, 0.1
            ];

            for (let i = 0; i < variations.length; i++) {
                let v = cur + (variations[i] * sd);
                if (this.unit === 'percentage' && v < 0) v = 0;
                pts.push(parseFloat(v.toFixed(2)));
            }
            return pts;
        },
        get triggerSimulation() {
            let up = (this.upper !== null && this.upper !== '' && !isNaN(parseFloat(this.upper))) ? parseFloat(this.upper) : null;
            let low = (this.lower !== null && this.lower !== '' && !isNaN(parseFloat(this.lower))) ? parseFloat(this.lower) : null;

            let pts = this.dataset;
            let count = pts.length;
            if (count === 0 || (up === null && low === null)) {
                return {
                    triggers: 0,
                    ratePercent: 0,
                    isTooTight: false,
                    isBalanced: true,
                    isConservative: true,
                    hasLimits: (up !== null || low !== null)
                };
            }

            let triggers = 0;
            for (let pt of pts) {
                if (up !== null && pt > up) triggers++;
                else if (low !== null && pt < low) triggers++;
            }

            let rate = triggers / count;
            return {
                triggers: triggers,
                ratePercent: Math.round(rate * 100),
                isTooTight: rate > 0.25,
                isBalanced: rate > 0 && rate <= 0.25,
                isConservative: triggers === 0,
                hasLimits: true
            };
        },
        applyPreset(type) {
            let cur = this.currentVal;
            let sd = this.stdDev || (cur * 0.1);

            if (type === 'plus_minus_10') {
                this.upper = parseFloat((cur * 1.10).toFixed(2));
                this.lower = parseFloat((cur * 0.90).toFixed(2));
            } else if (type === 'plus_minus_20') {
                this.upper = parseFloat((cur * 1.20).toFixed(2));
                this.lower = parseFloat((cur * 0.80).toFixed(2));
            } else if (type === 'std_dev_2') {
                this.upper = parseFloat((cur + (2 * sd)).toFixed(2));
                this.lower = parseFloat(Math.max(0, cur - (2 * sd)).toFixed(2));
            } else if (type === 'r2_fit') {
                this.lower = 0.60;
                this.upper = null;
            }
        },
        formatVal(v) {
            if (v === null || v === undefined || isNaN(v)) return '—';
            if (this.unit === 'currency') return '$' + Number(v).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            if (this.unit === 'percentage') return Number(v).toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 2}) + '%';
            return Number(v).toLocaleString(undefined, {maximumFractionDigits: 2});
        }
    }));
});
