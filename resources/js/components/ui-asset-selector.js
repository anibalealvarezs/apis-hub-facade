export function uiAssetSelector() {
    return {
        open: false,
        dropUp: false,
        searchAssetGroup: '',
        stripHtml(html) {
            if (!html) return '';
            if (typeof html !== 'string') return String(html);
            if (!html.includes('<')) return html;
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            const firstSpan = tmp.querySelector('span');
            return (firstSpan ? firstSpan.textContent : tmp.textContent || '').trim();
        },
        getOptionLabel(optionsObj, idVal, placeholder) {
            if (idVal === null || idVal === undefined || idVal === '') return placeholder || '';
            if (!optionsObj) return String(idVal);
            let val = optionsObj[idVal] ?? optionsObj[String(idVal)] ?? optionsObj[Number(idVal)];
            if (val === null || val === undefined) return String(idVal);
            if (typeof val === 'object') return val.label || val.name || val.title || val.text || String(idVal);
            return this.stripHtml(String(val));
        },
        getItemTitle(val, id) {
            if (val === null || val === undefined) return String(id || '');
            if (typeof val === 'object') return val.label || val.name || val.title || val.text || String(id || '');
            if (typeof val === 'string' && val.includes('<')) return this.stripHtml(val);
            return String(val);
        },
        getItemDescription(val) {
            if (typeof val === 'object' && val !== null) {
                return val.description || val.desc || val.subtitle || null;
            }
            return null;
        },
        isHtml(val) {
            return typeof val === 'string' && val.includes('<');
        },
        toggle() {
            const trigger = this.$refs.trigger;
            if (!trigger || trigger.disabled || trigger.hasAttribute('disabled')) return;
            this.open = !this.open;
            if (this.open) this.computeDirection();
        },
        computeDirection() {
            const trigger = this.$refs.trigger;
            const panel = this.$refs.panel;
            if (!trigger || !panel) return;
            const panelHeight = this.measurePanel(panel);
            const rect = trigger.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom;
            this.dropUp = spaceBelow < panelHeight + 8 && rect.top > panelHeight + 8;
        },
        measurePanel(panel) {
            const style = panel.style;
            const prev = {
                display: style.display,
                position: style.position,
                top: style.top,
                left: style.left,
                visibility: style.visibility,
                zIndex: style.zIndex,
            };
            style.display = 'block';
            style.position = 'fixed';
            style.top = '-9999px';
            style.left = '-9999px';
            style.visibility = 'hidden';
            style.zIndex = '-1';
            const height = panel.offsetHeight;
            Object.assign(style, prev);
            return height || 320;
        }
    };
}

if (typeof window !== 'undefined') {
    window.uiAssetSelector = uiAssetSelector;
}
