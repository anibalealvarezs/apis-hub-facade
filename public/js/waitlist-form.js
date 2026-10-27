/**
 * Waitlist Form ReCaptcha Submission Handler
 */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('waitlist-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const self = this;
        const siteKeyMeta = document.querySelector('meta[name="recaptcha-site-key"]');
        const siteKey = siteKeyMeta ? siteKeyMeta.getAttribute('content') : null;

        if (typeof grecaptcha !== 'undefined' && siteKey) {
            grecaptcha.enterprise.ready(function () {
                grecaptcha.enterprise.execute(siteKey, { action: 'subscribe' }).then(function (token) {
                    const tokenInput = document.getElementById('recaptcha_token');
                    if (tokenInput) {
                        tokenInput.value = token;
                    }
                    self.submit();
                });
            });
        } else {
            self.submit();
        }
    });
});
