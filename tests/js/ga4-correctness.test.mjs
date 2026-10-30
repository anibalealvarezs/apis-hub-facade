import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import { ga4Dashboard } from '../../resources/js/dashboards/ga4-dashboard.js';

test('unavailable rates stay unavailable while measured zero remains zero', () => {
    const dashboard = ga4Dashboard({ tenantId: 1, account: 'fixture' });
    const missing = dashboard.normalizeSummaryMetrics({ bounce_rate: null, average_session_duration: null });
    assert.equal(missing.bounce_rate, null);
    assert.equal(missing.average_session_duration, null);
    assert.equal(dashboard.formatPercent(missing.bounce_rate), '—');
    assert.equal(dashboard.formatDuration(missing.average_session_duration), '—');
    const measured = dashboard.normalizeSummaryMetrics({ bounce_rate: 0, average_session_duration: 0 });
    assert.equal(dashboard.formatPercent(measured.bounce_rate), '0.00%');
    assert.equal(dashboard.formatDuration(measured.average_session_duration), '00:00');
    assert.match(dashboard.getCacheKey('summary'), /^ga4_v2_/);
    dashboard.destroy();
});

test('custom/public widget quality notice uses text and cannot inject markup', () => {
    const window = {};
    const document = { createElement: () => ({ setAttribute(name, value) { this[name] = value; } }) };
    runInNewContext(readFileSync(new URL('../../public/js/dashboard-renderer.js', import.meta.url), 'utf8'), { window, document, console, setTimeout, clearTimeout });
    const renderer = window.dashboardRenderer;
    renderer._renderWidget = () => {};
    const children = [];
    renderer.render({ innerHTML: '', appendChild: node => children.push(node) }, { widget_type: 'tile', data: {}, controls: {}, analytics_warnings: ['<script>ignored()</script> counts are not unique'] });
    assert.equal(children.length, 1);
    assert.equal(children[0].role, 'note');
    assert.equal(children[0].textContent, '<script>ignored()</script> counts are not unique');
    assert.equal(children[0].innerHTML, undefined);
});
