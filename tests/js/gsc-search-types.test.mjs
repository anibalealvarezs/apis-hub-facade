import test from 'node:test';
import assert from 'node:assert/strict';
import { gscDashboard } from '../../resources/js/dashboards/gsc-dashboard.js';
import { dashboardBuilder } from '../../resources/js/dashboards/dashboard-builder.js';

const standard = { tabs: ['queries', 'pages', 'countries', 'devices', 'appearances'], metrics: ['clicks', 'impressions', 'ctr', 'position'] };
const feed = { tabs: ['pages', 'countries'], metrics: ['clicks', 'impressions', 'ctr'] };
const catalog = { search_types: { web: standard, image: standard, video: standard, news: standard, discover: feed, googleNews: feed } };
const makeDashboard = () => gscDashboard({ tenantId: 1, account: '42', dateStart: '2026-10-01', dateEnd: '2026-10-28', searchTypesEnabled: true, analyticsCatalog: catalog });

test('search type scopes every request and cache identity', () => {
    const dashboard = makeDashboard();
    const webKey = dashboard.getCacheKey('chart');
    dashboard.searchType = 'image';
    assert.equal(JSON.parse(dashboard.getFetchOptions().body).searchType, 'image');
    assert.notEqual(dashboard.getCacheKey('chart'), webKey);
    dashboard.destroy();
});

test('feed selection clears incompatible filters, tabs and position without touching page/country filters', () => {
    const dashboard = makeDashboard();
    dashboard.searchType = 'googleNews';
    dashboard.activeMetrics.position = true;
    dashboard.activeFilters = { queries: ['brand'], devices: ['mobile'], intents: ['commercial'], countries: ['USA'], pages: ['/article'] };
    dashboard.normalizeSearchTypeContext();
    assert.equal(dashboard.activeTab, 'pages');
    assert.deepEqual(dashboard.activeFilters.queries, []);
    assert.deepEqual(dashboard.activeFilters.devices, []);
    assert.deepEqual(dashboard.activeFilters.intents, []);
    assert.deepEqual(dashboard.activeFilters.pages, ['/article']);
    assert.deepEqual(dashboard.activeFilters.countries, ['USA']);
    assert.equal(dashboard.activeMetrics.position, false);
    dashboard.destroy();
});

test('type changes share the one-second debounce and request lock', t => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const dashboard = makeDashboard();
    dashboard.syncToUrl = () => {};
    dashboard.saveFilters = () => {};
    let refreshes = 0;
    dashboard.fetchAll = () => { refreshes++; };
    dashboard.setSearchType('image');
    t.mock.timers.tick(700);
    dashboard.setSearchType('video');
    t.mock.timers.tick(999);
    assert.equal(refreshes, 0);
    t.mock.timers.tick(1);
    assert.equal(refreshes, 1);
    dashboard.entityAttributeActiveRequests = 1;
    dashboard.setSearchType('news');
    assert.equal(dashboard.searchType, 'video');
    dashboard.destroy();
});

test('old tenant requests preserve the legacy shape', () => {
    const dashboard = gscDashboard({ tenantId: 1 });
    assert.equal(JSON.parse(dashboard.getFetchOptions().body).searchType, undefined);
    dashboard.destroy();
});

test('entering appearance scope clears incompatible cross-filters and refreshes the overview too', () => {
    const dashboard = makeDashboard();
    dashboard.syncToUrl = () => {};
    dashboard.saveFilters = () => {};
    dashboard.activeFilters.queries = ['brand'];
    let refreshes = 0;
    dashboard.fetchAll = () => { refreshes++; };
    dashboard.setTab('appearances');
    assert.deepEqual(dashboard.activeFilters.queries, []);
    assert.equal(refreshes, 1);
    dashboard.destroy();
});

test('widget multiselects retain multiple types and remove unavailable metric choices', () => {
    const builder = dashboardBuilder({ tenant: 'test', gscAnalyticsCatalog: catalog });
    builder.widgetControlsForm = { series_metrics_map: { 0: { clicks: 'Clicks', position: 'Position' } } };
    const series = { channel: 'google_search_console', filters: [{ dimension: 'dimensions.search_type', operator: 'in', value: ['web', 'googleNews'] }] };
    assert.deepEqual(builder.gscTypesForSeries(series), ['web', 'googleNews']);
    assert.deepEqual(builder.getRawSeriesMetricOptions(0, series), { clicks: 'Clicks' });
    series.filters[0].value = ['image', 'video'];
    assert.deepEqual(builder.getRawSeriesMetricOptions(0, series), { clicks: 'Clicks', position: 'Position' });
});
