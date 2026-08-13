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
            try {
                var els = Array.prototype.slice.call(document.querySelectorAll('.fi-sidebar-item-button'));
                var withTooltip = els.filter(function (a) {
                    return a.hasAttribute('x-tooltip') || a.hasAttribute('x-tooltip.html') || a.__x_tippy;
                });
                var subnav = window.Alpine ? window.Alpine.store('subnav') : null;
                data.subnav = subnav ? 'EXISTS isOpen=' + subnav.isOpen : 'MISSING';
                data.items = els.length;
                data.tooltipEls = withTooltip.length;
                data.allXTooltipEls = document.querySelectorAll('[x-tooltip], [x-tooltip.html]').length;
                data.tippyBoxes = document.querySelectorAll('.tippy-box').length;
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
                data.details = withTooltip.map(function (a) {
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
                        xEffect: (a.getAttribute('x-effect') || '').slice(0, 160),
                        tooltipVal: (tooltipVal || '').slice(0, 160),
                        tippy: tippy
                            ? 'visible=' + tippy.state.isVisible + ' destroyed=' + tippy.state.isDestroyed
                            : 'NO_TIPPY',
                    };
                });
            } catch (err) {
                data.inspectError = err.message;
            }
            console.log('[TOOLTIP-DEBUG] DUMP', JSON.stringify(data, null, 2));
            return data;
        };

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
