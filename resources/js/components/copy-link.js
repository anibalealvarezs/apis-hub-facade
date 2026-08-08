export function copyLink() {
    return {
        copied: false,
        timeout: null,

        copy(target) {
            navigator.clipboard.writeText(window.location.origin + window.location.pathname + '#' + target);
            this.copied = true;
            clearTimeout(this.timeout);
            this.timeout = setTimeout(() => this.copied = false, 2000);
        },
    };
}
