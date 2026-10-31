export function floatingTooltip(config = {}) {
    return {
        show: false,
        pos: '',
        ts: {},
        width: config.width || 384,

        toggle($el) {
            if (!this.show) {
                const r = $el.getBoundingClientRect();
                const gap = 8;
                const tw = Math.min(this.width, window.innerWidth - 16);
                const v = r.top < window.innerHeight / 2 ? 'top' : 'bottom';
                const h = r.left + r.width / 2 < window.innerWidth / 2 ? 'left' : 'right';
                this.pos = v + '-' + h;
                const lt = Math.max(8, Math.min(h === 'left' ? r.left : r.right - tw, window.innerWidth - tw - 8));
                if (v === 'top') {
                    this.ts = { position: 'fixed', top: (r.bottom + gap) + 'px', left: lt + 'px', zIndex: 9999 };
                } else {
                    this.ts = { position: 'fixed', bottom: (window.innerHeight - r.top + gap) + 'px', left: lt + 'px', zIndex: 9999 };
                }
            }
            this.show = !this.show;
        },
    };
}
