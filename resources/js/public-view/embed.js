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
}
