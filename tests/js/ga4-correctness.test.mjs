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

test('widget warnings update the heading and never append to the canvas', () => {
    const window = {};
    const events = [];
    const heading = { dispatchEvent: event => events.push(event) };
    const shell = { querySelector: selector => selector === '[data-analytics-warnings]' ? heading : null };
    const container = { innerHTML: '', closest: () => shell, appendChild: () => assert.fail('Warning inserted into canvas') };
    class CustomEvent { constructor(type, options) { this.type = type; this.detail = options.detail; } }
    runInNewContext(readFileSync(new URL('../../public/js/dashboard-renderer.js', import.meta.url), 'utf8'), { window, document: {}, CustomEvent, console, setTimeout, clearTimeout });
    const renderer = window.dashboardRenderer;
    renderer._renderWidget = () => {};
    renderer.render(container, { widget_type: 'tile', data: {}, controls: {}, analytics_warnings: ['<script>ignored()</script> counts are not unique'] });
    assert.equal(events[0].type, 'analytics-warnings');
    assert.equal(events[0].detail[0], '<script>ignored()</script> counts are not unique');
    renderer.render(container, { widget_type: 'tile', data: {}, controls: {}, analytics_warnings: [] });
    assert.equal(events[1].detail.length, 0);
});
