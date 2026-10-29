import test from 'node:test';
import assert from 'node:assert/strict';
import { compileEntityAttributes, withEntityAttributes } from '../../resources/js/dashboards/entity-attributes.js';

test('tab context selects its actual entity and excludes dimensional breakdowns', () => {
    const dashboard = channel => withEntityAttributes({ getFetchOptions() {} }, channel);
    assert.equal(dashboard('facebook_marketing').entityAttributeRelationForTab('campaigns'), 'channeledCampaign');
    assert.equal(dashboard('facebook_marketing').entityAttributeRelationForTab('age'), '');
    assert.equal(dashboard('google_ads').entityAttributeRelationForTab('search_terms'), 'query');
    assert.equal(dashboard('google_search_console').entityAttributeRelationForTab('pages'), '');
    assert.equal(dashboard('google_analytics').entityAttributeRelationForTab('traffic_pages'), '');
    assert.equal(dashboard('google_analytics').entityAttributeRelationForTab('events'), 'event');
    assert.equal(dashboard('google_analytics').entityAttributeRelationForTab('adtouchpoints_content'), 'channeledAd');
    assert.equal(dashboard('facebook_organic').entityAttributeRelationForTab('posts'), 'post');
    assert.equal(dashboard('facebook_organic').entityAttributeRelationForTab('reaction_type'), '');
    assert.equal(dashboard('mailchimp').entityAttributeRelationForTab('audiences'), 'channeledAccount');
    assert.equal(dashboard('mailchimp').entityAttributeRelationForTab('urls'), '');
});

test('multiple choices and zero-valued lower bounds survive compilation', () => {
    const filters = compileEntityAttributes({ 'attributes.channeledCampaign.objective': ['SALES', 'LEADS'] },
        { 'attributes.channeledCampaign.budget': { min: 0, max: 100 } });
    assert.deepEqual(filters['attributes.channeledCampaign.objective'].value, ['SALES', 'LEADS']);
    assert.equal(filters['attributes.channeledCampaign.budget__1000'].value, 0);
    assert.equal(filters['attributes.channeledCampaign.budget__1001'].operator, 'lte');
});

test('every request and cache key includes the same attribute filters', () => {
    const dashboard = withEntityAttributes({
        getFetchOptions: () => ({ body: JSON.stringify({ filters: { channel: 'google_ads' } }) }),
        getCacheKey: () => 'cached', getPostsCacheKey: () => 'posts', fetchAll() {},
    }, 'google_ads');
    const before = dashboard.getCacheKey();
    dashboard.entityAttributeSelections['attributes.channeledCampaign.status'] = ['ENABLED'];
    const payload = JSON.parse(dashboard.getFetchOptions().body);
    assert.equal(payload.filters.channel, 'google_ads');
    assert.deepEqual(payload.filters['attributes.channeledCampaign.status'].value, ['ENABLED']);
    assert.notEqual(dashboard.getCacheKey(), before);
    assert.match(dashboard.getPostsCacheKey(), /ENABLED/);
    dashboard.clearEntityAttributes();
    assert.deepEqual(dashboard.getEntityAttributeFilters(), {});
    dashboard.destroy();
});

test('rapid changes wait for one second of inactivity and lock until the last request settles', async t => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const complete = [];
    let refreshes = 0;
    const dashboard = withEntityAttributes({
        getFetchOptions() {},
        fetchSummary() { return new Promise(resolve => complete.push(resolve)); },
        fetchChart() { return new Promise(resolve => complete.push(resolve)); },
        fetchTable() { return new Promise(resolve => complete.push(resolve)); },
        fetchAll() { refreshes++; this.fetchSummary(); this.fetchChart(); this.fetchTable(); },
    }, 'facebook_marketing');
    dashboard.entityAttributesChanged();
    t.mock.timers.tick(700);
    dashboard.entityAttributesChanged();
    t.mock.timers.tick(700);
    dashboard.entityAttributesChanged();
    t.mock.timers.tick(999);
    assert.equal(refreshes, 0);
    assert.equal(dashboard.areEntityAttributeFiltersBusy(), false);
    t.mock.timers.tick(1);
    assert.equal(refreshes, 1);
    assert.equal(dashboard.areEntityAttributeFiltersBusy(), true);
    complete.shift()();
    complete.shift()();
    await new Promise(setImmediate);
    assert.equal(dashboard.areEntityAttributeFiltersBusy(), true);
    complete.shift()();
    await new Promise(setImmediate);
    assert.equal(dashboard.areEntityAttributeFiltersBusy(), false);
    dashboard.destroy();
});

test('queued changes wait for an existing request and pending refreshes are cancelled on destroy', async t => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    let complete;
    let refreshes = 0;
    const dashboard = withEntityAttributes({
        getFetchOptions() {}, fetchTable() { return new Promise(resolve => { complete = resolve; }); },
        fetchAll() { refreshes++; },
    }, 'google_ads');
    const request = dashboard.fetchTable();
    dashboard.entityAttributesChanged();
    t.mock.timers.tick(1000);
    assert.equal(refreshes, 0);
    complete();
    await request;
    await new Promise(setImmediate);
    assert.equal(refreshes, 1);
    dashboard.entityAttributesChanged();
    dashboard.destroy();
    t.mock.timers.tick(1000);
    assert.equal(refreshes, 1);
});

test('failed requests release the controls', async () => {
    const dashboard = withEntityAttributes({
        getFetchOptions() {}, fetchTable() { throw new Error('Request failed'); },
    }, 'google_ads');
    await assert.rejects(dashboard.fetchTable(), /Request failed/);
    assert.equal(dashboard.areEntityAttributeFiltersBusy(), false);
    dashboard.destroy();
});

test('browser requests are queued with a two-request cap', async t => {
    const resolve = [];
    t.mock.method(globalThis, 'fetch', () => new Promise(done => resolve.push(done)));
    const dashboard = withEntityAttributes({ getFetchOptions() {} }, 'google_analytics');
    const requests = [1, 2, 3].map(id => dashboard.fetchDashboardResponse('/test/'+id));
    await new Promise(setImmediate);
    assert.equal(resolve.length, 2);
    resolve[0](new Response('{}'));
    await requests[0];
    await new Promise(setImmediate);
    assert.equal(resolve.length, 3);
    resolve[1](new Response('{}'));
    resolve[2](new Response('{}'));
    await Promise.all(requests);
    dashboard.destroy();
});

test('rate denial is shown and a manual retry refreshes data rather than just metadata', async t => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    t.mock.method(globalThis, 'fetch', async () => new Response(JSON.stringify({ message: 'Tenant busy' }), { status: 429 }));
    let refreshes = 0;
    const dashboard = withEntityAttributes({ getFetchOptions() {}, fetchAll() { refreshes++; } }, 'google_ads');
    await assert.rejects(dashboard.fetchDashboardResponse('/api/google-ads/table'), /Tenant busy/);
    assert.equal(dashboard.entityAttributeDataError, true);
    dashboard.retryEntityAttributeFilters('channeledCampaign');
    t.mock.timers.tick(1000);
    await new Promise(setImmediate);
    assert.equal(refreshes, 1);
    dashboard.destroy();
});
