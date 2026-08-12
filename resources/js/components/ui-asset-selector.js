import { dropdownFlipBehavior } from './dropdown-position';

export function uiAssetSelector() {
    return {
        open: false,
        searchAssetGroup: '',
        ...dropdownFlipBehavior(),
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
        }
    };
}

if (typeof window !== 'undefined') {
    window.uiAssetSelector = uiAssetSelector;
}
