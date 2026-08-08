export function embedCodeConfig(config) {
    return {
        embedUrl: config.embedUrl,
        publicUrl: config.publicUrl,
        maxHeight: 800,
        get jsScript() {
            const attr = this.maxHeight ? ` data-max-height="${this.maxHeight}"` : '';
            return `<script src="${this.embedUrl}"${attr} defer><\/script>`;
        },
        get iframeSnippet() {
            const style = this.maxHeight
                ? `width:100%;height:${this.maxHeight}px;max-height:${this.maxHeight}px;border:none;`
                : 'width:100%;height:600px;border:none;';
            return `<iframe src="${this.publicUrl}?embedded=1" style="${style}"></iframe>`;
        },
        copy(text) {
            navigator.clipboard.writeText(text).then(() => {
                if (window.FilamentNotification && window.FilamentNotification.make) {
                    window.FilamentNotification.make()
                        .title('Code copied to clipboard')
                        .success()
                        .send();
                }
            }).catch(() => {});
        },
    };
}
