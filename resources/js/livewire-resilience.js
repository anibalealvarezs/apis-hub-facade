// Keep temporary gateway failures out of Livewire's blocking HTML error modal.
// Never replay actions: only existing wire:poll directives resume after backoff.
export function installLivewireResilience(livewire, document, clock = globalThis) {
    let failures = 0;
    let timer;
    let notice;
    const paused = new Map();
    const resume = () => {
        for (const [element, attributes] of paused) {
            if (element.isConnected) {
                for (const [name, value] of attributes) element.setAttribute(name, value);
            }
        }
        paused.clear();
    };
    livewire.hook('request', ({ fail, succeed }) => {
        succeed(() => {
            // An unrelated request succeeding must not cancel a polling cooldown.
            if (paused.size) return;
            failures = 0;
            clock.clearTimeout(timer);
            resume();
            notice?.remove();
            notice = null;
        });
        fail(({ status, preventDefault }) => {
            if (![429, 502, 503, 504, 520, 521, 522, 523, 524, 525, 526].includes(status)) return;
            preventDefault();
            // Removing the directive pauses Livewire's existing polling clock.
            for (const element of document.querySelectorAll('*')) {
                const attributes = [...element.attributes]
                    .filter(attribute => /^wire:poll(?:\.|$)/.test(attribute.name))
                    .map(attribute => [attribute.name, attribute.value]);
                if (!attributes.length) continue;
                paused.set(element, attributes);
                for (const [name] of attributes) element.removeAttribute(name);
            }
            clock.clearTimeout(timer);
            const delay = Math.min(300000, 30000 * 2 ** Math.min(failures++, 4));
            timer = clock.setTimeout(resume, delay + Math.floor(Math.random() * 1000));
            if (!notice) {
                notice = document.createElement('div');
                notice.setAttribute('role', 'status');
                notice.style.cssText = 'position:fixed;bottom:16px;right:16px;z-index:1000;max-width:360px;padding:12px 16px;background:#1f2937;color:white;border-radius:8px;pointer-events:none';
                notice.textContent = document.documentElement.lang.startsWith('es')
                    ? 'Conexión temporalmente interrumpida. Las actualizaciones automáticas se reanudarán. Si una acción falló, vuelve a intentarla.'
                    : 'Connection temporarily interrupted. Background updates will resume. If an action failed, please try it again.';
                document.body.appendChild(notice);
            }
        });
    });
}
