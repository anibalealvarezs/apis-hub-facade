import './bootstrap';

document.addEventListener('click', (e) => {
    // Check if the clicked element (or its parent, if needed) is a portal link
    const portalLink = e.target.closest('.js-portal-link');
    
    if (portalLink) {
        const payload = portalLink.dataset.portal;
        if (payload) {
            window.location.href = atob(payload);
        }
    }
});
