document.addEventListener('alpine:init', () => {
    Alpine.data('projectClock', (config) => ({
        timezone: config.timezone || 'UTC',
        formattedTime: '',
        timer: null,

        init() {
            this.updateClock();
            this.timer = setInterval(() => this.updateClock(), 1000);
        },

        updateClock() {
            try {
                const now = new Date();
                const formatter = new Intl.DateTimeFormat('en-US', {
                    timeZone: this.timezone,
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                });
                this.formattedTime = formatter.format(now);
            } catch (e) {
                const now = new Date();
                this.formattedTime = now.toTimeString().split(' ')[0];
            }
        },

        destroy() {
            if (this.timer) {
                clearInterval(this.timer);
            }
        }
    }));
});
