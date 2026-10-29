import test from 'node:test';
import assert from 'node:assert/strict';
import { compileEntityAttributes, withEntityAttributes } from '../../resources/js/dashboards/entity-attributes.js';

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
});
