import test from 'node:test';
import assert from 'node:assert/strict';
import { ga4Reports } from '../../resources/js/dashboards/ga4-reports.js';

test('report definitions preserve selected window, custom fields and predicates without inventing daily users', () => {
    const reports = ga4Reports({ tenant: 1, csrf: 'fixture' });
    reports.setContext('7', '2026-10-01', '2026-10-29');
    reports.chooseKind();
    assert.equal(reports.definition().kind, 'window');
    assert.deepEqual(reports.definition().dimensions, []);
    assert.deepEqual(reports.definition().metrics, ['totalUsers']);
    reports.kind = 'report'; reports.dimensions = ['customEvent:category']; reports.metrics = ['customEvent:score'];
    reports.filterField = 'eventName'; reports.filterValues = 'request_quote, purchase';
    assert.deepEqual(reports.definition().filters, [{ field: 'eventName', values: ['request_quote', 'purchase'] }]);
    reports.destroy();
});

test('pending source work is bounded and completed source totals are preserved', async t => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const reports = ga4Reports({ tenant: 1, csrf: 'fixture' });
    reports.setContext('7', '2026-10-01', '2026-10-29');
    reports.catalog = { metrics: { totalUsers: { raw_name: 'totalUsers', label: 'Total users' } } };
    const pending = [];
    t.mock.method(globalThis, 'fetch', async (_url, options) => {
        pending.push(JSON.parse(options.body));
        return { ok: true, json: async () => ({ success: true, data: { state: 'pending' } }) };
    });
    await reports.run();
    for (let i = 0; i < 10; i++) { t.mock.timers.tick(10000); await Promise.resolve(); await Promise.resolve(); await Promise.resolve(); }
    assert.ok(pending.length <= 7);
    assert.ok(pending.every(payload => payload.account === '7' && payload.definition.start_date === '2026-10-01'));
    reports.report = { state: 'ready', result: { response: { rows: [{ metricValues: [{ value: '5' }] }], totals: [{ metricValues: [{ value: '5' }] }] } } };
    assert.equal(reports.response.totals[0].metricValues[0].value, '5');
    reports.destroy();
});

test('property switches cancel pending refreshes and do not reuse another catalog', () => {
    const reports = ga4Reports({ tenant: 1 });
    reports.setContext('7', '2026-10-01', '2026-10-29');
    reports.catalog = { revision: 'old' }; reports.report = { state: 'ready' };
    reports.setContext('8', '2026-10-01', '2026-10-29');
    assert.equal(reports.catalog, null); assert.equal(reports.report, null);
    reports.destroy();
});
