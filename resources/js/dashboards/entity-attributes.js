const TAB_RELATIONS = {
    facebook_marketing: {
        campaigns: 'channeledCampaign', adsets: 'channeledAdGroup', ads: 'channeledAd',
    },
    google_ads: {
        campaigns: 'channeledCampaign', adgroups: 'channeledAdGroup', ads: 'channeledAd',
        devices: 'device', search_terms: 'query', queries: 'query',
    },
    google_search_console: { queries: 'query', countries: 'country', devices: 'device' },
    google_analytics: {
        campaigns: 'channeledCampaign', adgroups: 'channeledAdGroup', events: 'event',
        traffic_countries: 'country', traffic_devices: 'device',
        adtouchpoints_adgroups: 'channeledAdGroup', adtouchpoints_terms: 'channeledAdGroup',
        adtouchpoints_content: 'channeledAd',
    },
    facebook_organic: { posts: 'post' },
    mailchimp: { campaigns: 'channeledCampaign', automations: 'channeledCampaign', audiences: 'channeledAccount' },
};

export function compileEntityAttributes(selections, ranges) {
    const filters = {};
    for (const [path, values] of Object.entries(selections)) {
        if (Array.isArray(values) && values.length) filters[path] = { operator: 'in', value: values };
    }
    for (const [path, range] of Object.entries(ranges)) {
        if (range.min !== '' && range.min != null) filters[path + '__1000'] = { operator: 'gte', value: range.min };
        if (range.max !== '' && range.max != null) filters[path + '__1001'] = { operator: 'lte', value: range.max };
    }
    return filters;
}

/** Adds the same attribute UI/request semantics to every explorer. */
export function withEntityAttributes(dashboard, channel) {
    let refreshTimer = null;
    let refreshReady = false;
    let disposed = false;
    let activeHttpRequests = 0;
    const queuedHttpRequests = [];
    const pumpHttpRequests = () => {
        while (!disposed && activeHttpRequests < 2 && queuedHttpRequests.length) {
            const task = queuedHttpRequests.shift();
            activeHttpRequests++;
            Promise.resolve().then(task.run).then(task.resolve, task.reject).finally(() => {
                activeHttpRequests--;
                pumpHttpRequests();
            });
        }
    };
    Object.assign(dashboard, {
        entityAttributeCatalog: {}, entityAttributeSelections: {}, entityAttributeRanges: {},
        entityAttributeLoading: false, entityAttributeError: '', entityAttributeDataError: false,
        entityAttributeOptionsKeys: {}, entityAttributeRequest: {}, entityAttributeInitPromise: null, entityAttributeScope: null,
        entityAttributeActiveRequests: 0, entityAttributeRefreshing: false, entityAttributeRefreshScheduled: false,

        fetchDashboardResponse(url, options) {
            return new Promise((resolve, reject) => {
                if (disposed || queuedHttpRequests.length >= 32) {
                    reject(new Error(disposed ? 'Filter view closed' : 'Too many pending requests. Please retry shortly.'));
                    return;
                }
                queuedHttpRequests.push({ resolve, reject, run: async () => {
                    const response = await fetch(url, options);
                    if ([429, 503].includes(response.status)) {
                        const body = await response.clone().json().catch(() => ({}));
                        this.entityAttributeDataError = !url.includes('/entity-attributes');
                        this.entityAttributeError = body.message || body.error || 'This tenant is busy. Please retry shortly.';
                        throw new Error(this.entityAttributeError);
                    }
                    return response;
                } });
                pumpHttpRequests();
            });
        },

        areEntityAttributeFiltersBusy() {
            return this.entityAttributeRefreshing || this.entityAttributeActiveRequests > 0;
        },
        cancelEntityAttributeRefresh() {
            clearTimeout(refreshTimer);
            refreshTimer = null;
            refreshReady = false;
            this.entityAttributeRefreshScheduled = false;
        },
        async flushEntityAttributeChanges() {
            if (disposed || !refreshReady || this.areEntityAttributeFiltersBusy()) return;
            refreshReady = false;
            this.entityAttributeRefreshScheduled = false;
            this.entityAttributeRefreshing = true;
            this.entityAttributeError = '';
            this.entityAttributeDataError = false;
            try {
                await this.fetchAll();
            } catch (error) {
                this.entityAttributeError = error.message || 'Unable to refresh attribute filters';
            } finally {
                this.entityAttributeRefreshing = false;
                if (refreshReady) this.flushEntityAttributeChanges();
            }
        },

        getEntityAttributeFilters() {
            const scope = JSON.stringify([this.accounts !== undefined ? this.accounts : this.account,
                channel === 'facebook_organic' ? this.activeTab : null]);
            if (this.entityAttributeScope !== null && this.entityAttributeScope !== scope) {
                this.cancelEntityAttributeRefresh();
                for (const path of Object.keys(this.entityAttributeCatalog)) {
                    this.entityAttributeSelections[path] = [];
                    this.entityAttributeRanges[path] = { min: '', max: '' };
                }
            }
            this.entityAttributeScope = scope;
            return compileEntityAttributes(this.entityAttributeSelections, this.entityAttributeRanges);
        },
        getEntityAttributeFields(relation) {
            return Object.entries(this.entityAttributeCatalog).filter(([, field]) => field.relation === relation
                && !['id', 'createdAt', 'updatedAt', 'platformId', 'canonicalId',
                    'version', 'platformCreatedAt', 'platform_created_at', 'startDate', 'start_date', 'endDate', 'end_date'].includes(field.field));
        },
        isEntityAttributeRange(field) {
            return ['integer', 'smallint', 'bigint', 'float', 'decimal', 'date', 'datetime', 'datetime_immutable', 'date_immutable'].includes(field.type);
        },
        entityAttributeInputType(field) {
            return field.type.includes('date') ? 'date' : 'number';
        },
        async initEntityAttributes() {
            if (this.entityAttributeInitPromise) return this.entityAttributeInitPromise;
            if (channel === 'facebook_organic') {
                this.$watch('activeTab', () => {
                    for (const relation of Object.keys(this.entityAttributeOptionsKeys)) this.loadEntityAttributeOptions(relation);
                });
            }
            this.$watch(this.accounts !== undefined ? 'accounts' : 'account', () => {
                this.cancelEntityAttributeRefresh();
                this.entityAttributeSelections = {};
                this.entityAttributeRanges = {};
                this.entityAttributeOptionsKeys = {};
                for (const path of Object.keys(this.entityAttributeCatalog)) {
                    this.entityAttributeSelections[path] = [];
                    this.entityAttributeRanges[path] = { min: '', max: '' };
                }
            });
            this.entityAttributeInitPromise = this.loadEntityAttributeOptions('');
            return this.entityAttributeInitPromise;
        },
        entityAttributeRelationForTab(tab) {
            return TAB_RELATIONS[channel]?.[tab] || '';
        },
        async loadEntityAttributeOptions(relation = '') {
            const accounts = (this.accounts !== undefined ? this.accounts : this.account) || [];
            const selected = (Array.isArray(accounts) ? accounts : [accounts]).map(String);
            const key = JSON.stringify([selected, relation, this.activeTab]);
            if (!this.tenantId || key === this.entityAttributeOptionsKeys[relation]) return;
            const requestId = (this.entityAttributeRequest[relation] || 0) + 1;
            this.entityAttributeRequest[relation] = requestId;
            this.entityAttributeLoading = true;
            this.entityAttributeError = '';
            try {
                // Keep compound selections intact until the facade authorizes
                // the asset, then resolves its current account context.
                const values = !!relation && selected.length > 0 && selected.every(id => /^\d+$/.test(id)
                    || (channel === 'facebook_organic' && id.includes('|')));
                const response = await this.fetchDashboardResponse('/api/entity-attributes', {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                    body: JSON.stringify({ tenant: this.tenantId, channel, account: selected, relation: relation || null, values, assetContext: this.activeTab || '' }),
                });
                const data = await response.json();
                if (!response.ok || data.status === 'error') throw new Error(data.error || data.message || 'Unable to load entity attributes');
                if (requestId !== this.entityAttributeRequest[relation]) return;
                this.entityAttributeCatalog = { ...this.entityAttributeCatalog, ...(data.data || {}) };
                for (const [path, field] of Object.entries(data.data || {})) {
                    this.entityAttributeSelections[path] ??= [];
                    this.entityAttributeRanges[path] ??= { min: '', max: '' };
                    // Keep selected values available while another facet narrows options.
                    field.options = { ...field.options };
                    for (const value of this.entityAttributeSelections[path]) field.options[value] ??= value;
                }
                this.entityAttributeOptionsKeys[relation] = key;
            } catch (error) {
                if (requestId === this.entityAttributeRequest[relation]) this.entityAttributeError = error.message;
            } finally {
                if (requestId === this.entityAttributeRequest[relation]) this.entityAttributeLoading = false;
            }
        },
        entityAttributesChanged() {
            if (this.tableState) this.tableState.currentPage = 1;
            clearTimeout(refreshTimer);
            refreshReady = false;
            this.entityAttributeRefreshScheduled = true;
            refreshTimer = setTimeout(() => {
                refreshTimer = null;
                refreshReady = true;
                this.flushEntityAttributeChanges();
            }, 1000);
        },
        retryEntityAttributeFilters(relation) {
            if (this.entityAttributeDataError) {
                this.entityAttributesChanged();
            } else {
                this.loadEntityAttributeOptions(relation);
            }
        },
        clearEntityAttributes() {
            if (this.areEntityAttributeFiltersBusy()) return;
            this.entityAttributeSelections = {};
            this.entityAttributeRanges = {};
            for (const path of Object.keys(this.entityAttributeCatalog)) {
                this.entityAttributeSelections[path] = [];
                this.entityAttributeRanges[path] = { min: '', max: '' };
            }
            this.entityAttributesChanged();
        },
    });
    // Several dashboards start their requests without awaiting them in
    // fetchAll(). Track each promise so controls unlock only after all finish.
    for (const method of ['fetchSummary', 'fetchChart', 'fetchTable', 'fetchBreakdownTable', 'fetchSection', 'loadEntityAttributeOptions']) {
        if (!dashboard[method]) continue;
        const original = dashboard[method];
        dashboard[method] = async function (...args) {
            this.entityAttributeActiveRequests++;
            try {
                return await original.apply(this, args);
            } finally {
                this.entityAttributeActiveRequests--;
                if (this.entityAttributeActiveRequests === 0 && refreshReady) this.flushEntityAttributeChanges();
            }
        };
    }
    const originalDestroy = dashboard.destroy;
    dashboard.destroy = function () {
        disposed = true;
        for (const task of queuedHttpRequests.splice(0)) task.reject(new Error('Filter view closed'));
        this.cancelEntityAttributeRefresh();
        originalDestroy?.call(this);
    };
    const originalOptions = dashboard.getFetchOptions;
    dashboard.getFetchOptions = function (...args) {
        const options = originalOptions.apply(this, args);
        const payload = JSON.parse(options.body);
        payload.filters = { ...payload.filters, ...this.getEntityAttributeFilters() };
        return { ...options, body: JSON.stringify(payload) };
    };
    for (const method of ['getCacheKey', 'getPostsCacheKey']) {
        if (!dashboard[method]) continue;
        const original = dashboard[method];
        dashboard[method] = function (...args) {
            return original.apply(this, args) + '_attributes_' + JSON.stringify(this.getEntityAttributeFilters());
        };
    }
    return dashboard;
}
