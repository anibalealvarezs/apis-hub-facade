export function initPublicViewEmbed() {
    const body = document.body;
    const token = body?.dataset.pvToken;
    const isEmbedded = body?.dataset.embedded === '1';

    if (token) {
        window.pvToken = token;
    }
    window.isEmbedded = isEmbedded;

    if (!isEmbedded) {
        return;
    }

    const sendResize = () => {
        window.parent.postMessage({ type: 'apis-hub-resize', height: document.body.scrollHeight }, '*');
    };
    window.addEventListener('load', sendResize);
    window.addEventListener('resize', sendResize);

    // While a widget is expanded (pop-out), the parent locks the iframe height to the
    // parent's real viewport so the modal doesn't grow to the full dashboard height.
    const notifyPopOut = (active) => {
        window.parent.postMessage({ type: 'apis-hub-popout', active }, '*');
    };

    window.addEventListener('message', (e) => {
        if (e.data && e.data.type === 'apis-hub-measure') {
            sendResize();
        }
    });

    window.pvNotifyPopOut = notifyPopOut;
}
