<script>
    // TEMPORARY DEBUG: knowledge-base cluster menu tooltip investigation.
    // Runs in <head> BEFORE Alpine/Livewire load, so it can capture init errors
    // and store-registration timing. REMOVE after diagnosis.
    (function () {
        var dbg = window.__tooltipDebug = window.__tooltipDebug || { logs: [], errors: [] };

        dbg.mark = function (msg) {
            var rec = { t: Date.now(), m: msg };
            dbg.logs.push(rec);
            try {
                console.log('[TOOLTIP-DEBUG]', msg);
            } catch (e) {}
        };

        dbg.mark('head script parsed');

        window.addEventListener('error', function (e) {
            var rec = { type: 'error', msg: e.message, file: (e.filename || '').split('/').pop() + ':' + e.lineno };
            dbg.errors.push(rec);
            dbg.mark('window.error: ' + e.message + ' @ ' + rec.file);
        });

        window.addEventListener('unhandledrejection', function (e) {
            var rec = { type: 'rejection', msg: String(e.reason && e.reason.message || e.reason) };
            dbg.errors.push(rec);
            dbg.mark('unhandledrejection: ' + rec.msg);
        });

        document.addEventListener('alpine:init', function () {
            var hasAlpine = typeof window.Alpine !== 'undefined';
            dbg.mark('alpine:init fired. Alpine=' + (hasAlpine ? 'yes v' + window.Alpine.version : 'NO'));
            try {
                dbg.mark('alpine:init subnav=' + (window.Alpine && window.Alpine.store && window.Alpine.store('subnav') ? 'EXISTS' : 'MISSING'));
                dbg.mark('alpine:init persist=$' + (window.Alpine && typeof window.Alpine.$persist === 'function' ? 'ok' : 'MISSING'));
            } catch (err) {
                dbg.mark('alpine:init inspect error: ' + err.message);
            }
        });

        document.addEventListener('alpine:initialized', function () {
            try {
                var subnav = window.Alpine ? window.Alpine.store('subnav') : null;
                dbg.mark('alpine:initialized. subnav=' + (subnav ? 'EXISTS isOpen=' + subnav.isOpen : 'MISSING'));
            } catch (err) {
                dbg.mark('alpine:initialized error: ' + err.message);
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            dbg.mark('DOMContentLoaded. subnav=' + (window.Alpine && window.Alpine.store('subnav') ? 'EXISTS' : 'MISSING'));
        });

        window.addEventListener('livewire:navigated', function () {
            try {
                var subnav = window.Alpine ? window.Alpine.store('subnav') : null;
                dbg.mark('livewire:navigated. subnav=' + (subnav ? 'EXISTS isOpen=' + subnav.isOpen : 'MISSING'));
            } catch (err) {
                dbg.mark('livewire:navigated error: ' + err.message);
            }
        });

        window.__tooltipDump = function () {
            var data = {
                url: window.location.href,
                logs: dbg.logs,
                errors: dbg.errors,
            };
            var safe = function (fn) {
                try {
                    return fn();
                } catch (e) {
                    return 'ERR ' + e.message;
                }
            };

            var els = safe(function () {
                return Array.prototype.slice.call(document.querySelectorAll('.fi-sidebar-item-button'));
            });
            data.items = Array.isArray(els) ? els.length : els;

            var withTooltip = safe(function () {
                return els.filter(function (a) {
                    return a.hasAttribute('x-tooltip') || a.hasAttribute('x-tooltip.html') || a.__x_tippy;
                });
            });
            data.tooltipEls = Array.isArray(withTooltip) ? withTooltip.length : withTooltip;

            data.subnav = safe(function () {
                var subnav = window.Alpine ? window.Alpine.store('subnav') : null;
                return subnav ? 'EXISTS isOpen=' + subnav.isOpen : 'MISSING';
            });
            data.sidebar = safe(function () {
                var sidebar = window.Alpine ? window.Alpine.store('sidebar') : null;
                return sidebar ? 'EXISTS isOpen=' + sidebar.isOpen : 'MISSING';
            });
            data.persistedSubnavOpen = safe(function () {
                return window.Alpine && typeof window.Alpine.$persist === 'function'
                    ? JSON.parse(localStorage.getItem('subnavOpen') ?? 'null')
                    : 'noPersist';
            });

            data.allXTooltipEls = safe(function () {
                return document.querySelectorAll('[x-tooltip], [x-tooltip\\.html]').length;
            });
            data.tippyBoxes = safe(function () {
                return document.querySelectorAll('.tippy-box').length;
            });
            data.anchorBoxes = safe(function () {
                return document.querySelectorAll('.tippy-box .fi-subnav-tooltip-anchors').length;
            });
            data.styleSheets = safe(function () {
                return Array.prototype.map.call(document.styleSheets, function (s) {
                    var status = 'ok';
                    try { s.cssRules; } catch (e) { status = 'CROSS_ORIGIN/ERR'; }
                    return { href: s.href || '(inline)', loaded: status };
                });
            });
            data.tippyCssLoaded = (function () {
                try {
                    for (var i = 0; i < document.styleSheets.length; i++) {
                        var rules;
                        try { rules = document.styleSheets[i].cssRules; } catch (e) { continue; }
                        if (!rules) continue;
                        for (var j = 0; j < rules.length; j++) {
                            if ((rules[j].selectorText || '').indexOf('.tippy-box') !== -1) return true;
                        }
                    }
                    return false;
                } catch (e) {
                    return 'ERR ' + e.message;
                }
            })();
            data.anchorCssLoaded = (function () {
                try {
                    for (var i = 0; i < document.styleSheets.length; i++) {
                        var rules;
                        try { rules = document.styleSheets[i].cssRules; } catch (e) { continue; }
                        if (!rules) continue;
                        for (var j = 0; j < rules.length; j++) {
                            var sel = rules[j].selectorText || '';
                            if (sel.indexOf('fi-subnav-tooltip-anchors a') !== -1) return sel;
                        }
                    }
                    return false;
                } catch (e) {
                    return 'ERR ' + e.message;
                }
            })();
            data.details = safe(function () {
                return withTooltip.map(function (a) {
                    var tooltipVal = 'n/a';
                    try {
                        tooltipVal = window.Alpine && window.Alpine.$data
                            ? JSON.stringify(window.Alpine.$data(a).tooltip)
                            : 'noAlpineData';
                    } catch (e) {
                        tooltipVal = 'ERR ' + e.message;
                    }
                    var tippy = a.__x_tippy;
                    return {
                        href: (a.getAttribute('href') || '').slice(0, 70),
                        hasAnchorAttr: a.hasAttribute('x-tooltip.html'),
                        tooltipVal: (tooltipVal || '').slice(0, 400),
                        tippy: tippy
                            ? 'enabled=' + tippy.state.isEnabled + ' visible=' + tippy.state.isVisible + ' destroyed=' + tippy.state.isDestroyed
                            : 'NO_TIPPY',
                    };
                });
            });

            console.log('[TOOLTIP-DEBUG] DUMP', JSON.stringify(data, null, 2));
            return data;
        };

        // Hover capture: when an item is hovered, inspect the live tippy box.
        function bindHoverCapture() {
            var els = document.querySelectorAll('.fi-sidebar-item-button');
            Array.prototype.forEach.call(els, function (a) {
                if (a.__tdHoverBound) return;
                a.__tdHoverBound = true;
                a.addEventListener('mouseenter', function () {
                    setTimeout(function () {
                        try {
                            var tippy = a.__x_tippy;
                            var box = a._tippy
                                ? a._tippy.popper
                                : (tippy && tippy.popper) || (document.querySelector('.tippy-box:not([style*="display: none"])'));
                            if (!box) {
                                dbg.mark('HOVER ' + (a.getAttribute('href') || '').slice(0, 50) + ' -> NO tippy box in DOM');
                                return;
                            }
                            var inner = box.querySelector('.tippy-box') || box;
                            var anchors = box.querySelectorAll('.fi-subnav-tooltip-anchors a');
                            var first = anchors[0];
                            var cs = getComputedStyle(inner);
                            var color = first ? getComputedStyle(first).color : 'no-anchor-el';
                            dbg.mark('HOVER ' + (a.getAttribute('href') || '').slice(0, 50)
                                + ' theme=' + (inner.getAttribute('data-theme') || 'none')
                                + ' state=' + (inner.getAttribute('data-state') || 'none')
                                + ' anchors=' + anchors.length
                                + ' anchorColor=' + color
                                + ' bg=' + cs.backgroundColor
                                + ' opacity=' + cs.opacity
                                + ' rendered=' + (inner.offsetParent !== null)
                                + ' boxWxH=' + inner.offsetWidth + 'x' + inner.offsetHeight);
                            window.__tooltipDump();
                        } catch (e) {
                            dbg.mark('HOVER ERR ' + e.message);
                        }
                    }, 120);
                });
            });
        }
        if (document.readyState !== 'loading') {
            bindHoverCapture();
        } else {
            document.addEventListener('DOMContentLoaded', bindHoverCapture);
        }
        window.addEventListener('livewire:navigated', bindHoverCapture);

        function autoDump(label) {
            try {
                var d = window.__tooltipDump();
                if (d) d.markedBy = label;
            } catch (e) {}
        }
        setTimeout(function () { autoDump('after 1s'); }, 1000);
        setTimeout(function () { autoDump('after 3s'); }, 3000);
        window.addEventListener('livewire:navigated', function () { setTimeout(function () { autoDump('after navigated'); }, 800); });
    })();
</script>
