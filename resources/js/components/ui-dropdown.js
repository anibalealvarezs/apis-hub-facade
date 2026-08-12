import { computeDropUp } from './dropdown-position';

export function uiDropdown() {
    return {
        open: false,
        searchAccount: '',
        dropUp: false,
        toggle() {
            this.open = !this.open;
            if (this.open) this.dropUp = computeDropUp(this.$refs.trigger, this.$refs.panel);
        }
    };
}

if (typeof window !== 'undefined') {
    window.uiDropdown = uiDropdown;
}
