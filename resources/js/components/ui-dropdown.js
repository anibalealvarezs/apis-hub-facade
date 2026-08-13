import { dropdownFlipBehavior } from './dropdown-position';

export function uiDropdown() {
    return {
        open: false,
        searchAccount: '',
        ...dropdownFlipBehavior()
    };
}

if (typeof window !== 'undefined') {
    window.uiDropdown = uiDropdown;
}
