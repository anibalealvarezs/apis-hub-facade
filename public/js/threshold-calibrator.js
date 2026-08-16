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
        get triggerSimulation() {
            let up = parseFloat(this.upper);
            let low = parseFloat(this.lower);
            let triggers = 0;
            let count = this.points.length;

            for (let pt of this.points) {
                if (!isNaN(up) && pt > up) triggers++;
                else if (!isNaN(low) && pt < low) triggers++;
            }

            let rate = count > 0 ? (triggers / count) : 0;
            return {
                triggers: triggers,
                ratePercent: Math.round(rate * 100),
                isTooTight: rate > 0.40,
                isBalanced: rate > 0 && rate <= 0.20,
                isConservative: triggers === 0
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
