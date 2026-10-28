/**
 * Waitlist Form ReCaptcha Submission Handler (Lazy Loaded to prevent forced reflow)
 */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('waitlist-form');
    if (!form) return;

    const emailInput = document.getElementById('email-input');
    const siteKeyMeta = document.querySelector('meta[name="recaptcha-site-key"]');
    const siteKey = siteKeyMeta ? siteKeyMeta.getAttribute('content') : null;
    let recaptchaLoaded = false;

    function loadRecaptcha() {
        if (recaptchaLoaded || !siteKey) return;
        recaptchaLoaded = true;
        const script = document.createElement('script');
        script.src = `https://www.google.com/recaptcha/enterprise.js?render=${siteKey}`;
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }

    // Lazy load reCAPTCHA when user intends to interact with form (hover or focus)
    if (emailInput) {
        emailInput.addEventListener('focus', loadRecaptcha, { once: true });
        emailInput.addEventListener('pointerenter', loadRecaptcha, { once: true });
    }
    form.addEventListener('pointerenter', loadRecaptcha, { once: true });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const self = this;

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
        } else if (siteKey) {
            // If submitted without prior hover/focus, load immediately and submit
            loadRecaptcha();
            const checkInterval = setInterval(() => {
                if (typeof grecaptcha !== 'undefined' && grecaptcha.enterprise) {
                    clearInterval(checkInterval);
                    grecaptcha.enterprise.ready(function () {
                        grecaptcha.enterprise.execute(siteKey, { action: 'subscribe' }).then(function (token) {
                            const tokenInput = document.getElementById('recaptcha_token');
                            if (tokenInput) {
                                tokenInput.value = token;
                            }
                            self.submit();
                        });
                    });
                }
            }, 100);
        } else {
            self.submit();
        }
    });
});
