export function analyticsWarningTooltip(config = {}) {
    return {
        show: false,
        ts: {},
        width: config.width || 320,
        messages: Array.isArray(config.messages) ? config.messages : (config.message ? [config.message] : []),

        toggle($el) {
            if (!this.show) {
                this.computePosition($el);
            }
            this.show = !this.show;
        },

        computePosition($el) {
            const triggerEl = $el || this.$refs?.warningTrigger || this.$el;
            if (!triggerEl || typeof triggerEl.getBoundingClientRect !== 'function') return;

            const r = triggerEl.getBoundingClientRect();
            const gap = 8;
            const tw = Math.min(this.width, window.innerWidth - 16);
            const v = (r.bottom + 180 > window.innerHeight && r.top > 180) ? 'bottom' : 'top';
            const lt = Math.max(8, Math.min(r.left + r.width / 2 - tw / 2, window.innerWidth - tw - 8));

            if (v === 'top') {
                this.ts = {
                    position: 'fixed',
                    top: (r.bottom + gap) + 'px',
                    left: lt + 'px',
                    width: tw + 'px',
                    zIndex: 9999
                };
            } else {
                this.ts = {
                    position: 'fixed',
                    bottom: (window.innerHeight - r.top + gap) + 'px',
                    left: lt + 'px',
                    width: tw + 'px',
                    zIndex: 9999
                };
            }
        }
    };
}
