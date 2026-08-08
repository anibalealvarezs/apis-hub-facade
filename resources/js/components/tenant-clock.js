export function tenantClock(config = {}) {
    return {
        timeStr: '',
        tzStr: config.timezone || 'UTC',
        interval: null,

        init() {
            this.updateClock();
            this.interval = setInterval(() => this.updateClock(), 1000);
        },

        destroy() {
            if (this.interval) {
                clearInterval(this.interval);
            }
        },

        updateClock() {
            const now = new Date();
            try {
                this.timeStr = new Intl.DateTimeFormat('en-US', {
                    timeZone: this.tzStr,
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                }).format(now);
            } catch (e) {
                this.timeStr = now.toTimeString().split(' ')[0];
            }
        }
    };
}
