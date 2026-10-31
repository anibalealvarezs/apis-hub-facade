export function ga4Reports(config = {}) {
    return {
        property: '', windowStart: '', windowEnd: '', tenant: config.tenant,
        csrf: config.csrf, dashboards: config.dashboards || {}, catalog: null,
        catalogState: '', report: null, loading: false, error: '', saved: '',
        kind: 'window', preset: '', metrics: ['totalUsers'], dimensions: [],
        filterField: '', filterValues: '', steps: 'page_view,add_to_cart,purchase',
        cohortGranularity: 'DAILY', cohortOffset: 7, dashboard: '', title: '', widgetType: 'table',
        generation: 0, timer: null, pollCount: 0, controller: null,

        setContext(property, start, end) {
            if (String(property || '') === this.property && start === this.windowStart && end === this.windowEnd) return;
            this.destroy(); this.generation++;
            this.property = String(property || ''); this.windowStart = start; this.windowEnd = end;
            this.catalog = null; this.report = null; this.error = ''; this.catalogState = '';
        },
        destroy() { clearTimeout(this.timer); this.controller?.abort(); this.loading = false; },
        async request(path, payload) {
            this.controller = new AbortController();
            const response = await fetch(`/api/ga4/${path}`, {
                method: 'POST', signal: this.controller.signal,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify({ tenant: this.tenant, account: this.property, ...payload }),
            });
            const json = await response.json();
            if (!response.ok || json.success === false || json.error) throw new Error(json.message || json.error || 'The report request could not be completed.');
            return json;
        },
        async loadCatalog(poll = false, refresh = false) {
            if (!this.property || this.loading) return;
            const generation = this.generation; this.loading = true; this.error = '';
            if (!poll) { clearTimeout(this.timer); this.pollCount = 0; }
            try {
                const json = await this.request('catalog', { refresh });
                if (generation !== this.generation) return;
                this.catalog = json.data?.catalog || null; this.catalogState = json.data?.state || 'unavailable';
                if (json.data?.error) this.error = json.data.error;
                if (['pending', 'processing'].includes(this.catalogState)) this.poll(() => this.loadCatalog(true));
            } catch (e) { if (e.name !== 'AbortError' && generation === this.generation) this.error = e.message; }
            finally { if (generation === this.generation) this.loading = false; }
        },
        poll(callback) {
            clearTimeout(this.timer);
            if (this.pollCount++ < 6) this.timer = setTimeout(callback, 10000);
        },
        choosePreset() {
            if (!this.preset || !this.catalog?.recipes?.[this.preset]) return;
            this.kind = 'report';
            this.metrics = [...this.catalog.recipes[this.preset].metrics].map(name => name === 'conversions' && this.catalog.metrics.keyEvents ? 'keyEvents' : name);
            this.dimensions = [...this.catalog.recipes[this.preset].dimensions]; this.report = null;
        },
        chooseKind() {
            this.preset = ''; this.report = null;
            if (this.kind === 'window') { this.metrics = ['totalUsers']; this.dimensions = []; }
            if (this.kind === 'cohort') { this.metrics = ['cohortActiveUsers', 'cohortTotalUsers']; this.dimensions = ['cohort', 'cohortNthDay']; }
        },
        fields(kind) {
            return Object.values(this.catalog?.[kind] || {}).filter(field => {
                if (kind === 'dimensions') return this.kind !== 'window' || field.raw_name !== 'date';
                if (this.kind === 'window') return ['newUsers', 'totalUsers', 'activeUsers'].includes(field.raw_name);
                if (this.kind === 'cohort') return ['cohortActiveUsers', 'cohortTotalUsers'].includes(field.raw_name);
                return field.implemented;
            });
        },
        definition() {
            const dimensions = this.kind === 'cohort' ? this.dimensions.map(name => /^cohortNth(Day|Week|Month)$/.test(name) ? ({ DAILY: 'cohortNthDay', WEEKLY: 'cohortNthWeek', MONTHLY: 'cohortNthMonth' })[this.cohortGranularity] : name) : this.dimensions;
            return {
                kind: this.kind, revision: 1, start_date: this.windowStart, end_date: this.windowEnd,
                metrics: [...this.metrics], dimensions: [...dimensions], max_rows: 10000,
                filters: this.filterField ? [{ field: this.filterField, values: this.filterValues.split(',').map(v => v.trim()).filter(Boolean) }] : [],
                steps: this.steps.split(',').map(v => v.trim()).filter(Boolean),
                cohort_granularity: this.cohortGranularity, cohort_end_offset: Number(this.cohortOffset),
            };
        },
        async run(poll = false) {
            if (!this.property || this.loading || !this.catalog) return;
            if (this.dimensions.length > 9) { this.error = 'Select at most nine dimensions, including date.'; return; }
            const generation = this.generation; this.loading = true; this.error = ''; this.saved = '';
            if (!poll) { clearTimeout(this.timer); this.pollCount = 0; }
            try {
                const json = await this.request('scoped-report', { definition: this.definition() });
                if (generation !== this.generation) return;
                this.report = json.data;
                if (this.report.error) this.error = this.report.error;
                if (['pending', 'processing', 'catalog_pending'].includes(this.report.state)) this.poll(() => this.run(true));
            } catch (e) { if (e.name !== 'AbortError' && generation === this.generation) this.error = e.message; }
            finally { if (generation === this.generation) this.loading = false; }
        },
        async addWidget() {
            if (!this.dashboard || !this.title || this.loading || !this.report?.definition) return;
            this.loading = true; this.error = '';
            try {
                await this.request('report-widget', { definition: this.definition(), dashboard_id: this.dashboard, title: this.title, widget_type: this.widgetType });
                this.saved = 'Report added to the selected dashboard.';
            } catch (e) { this.error = e.message; }
            finally { this.loading = false; }
        },
        get response() { return this.report?.result?.response || null; },
        stateLabel(state) { return ({ pending: 'Queued', processing: 'Preparing report', catalog_pending: 'Preparing property catalog', ready: 'Ready', failed: 'Could not complete', unavailable: 'Unavailable' })[state] || ''; },
        qualityMessages() {
            const quality = this.report?.result?.quality;
            if (!quality || quality.state === 'unknown') return ['Reporting quality is unknown for this cached result.'];
            const details = quality.details || {};
            const messages = [];
            if (details.subjectToThresholding) messages.push('Google applied reporting thresholds.');
            if (details.dataLossFromOtherRow) messages.push('Some detailed rows were grouped by Google.');
            if (details.samplingMetadatas?.length) messages.push('Google reports sampling for this result.');
            if (details.timeZone) messages.push(`Property timezone: ${details.timeZone}`);
            if (details.schemaRestrictionResponse) messages.push('Some fields may be restricted by property access permissions.');
            if (details.currencyCode) messages.push(`Reporting currency: ${details.currencyCode}`);
            if (!messages.length) messages.push('Source metadata is retained. No additional quality flags were reported.');
            return messages;
        },
        get headers() { return [...(this.response?.dimensionHeaders || []), ...(this.response?.metricHeaders || [])].map(header => header.name); },
        label(name) { return this.catalog?.metrics?.[name]?.label || this.catalog?.dimensions?.[name]?.label || name; },
        formatValue(name, value) {
            if (value == null || value === '' || !Number.isFinite(Number(value))) return value ?? '—';
            const field = this.catalog?.metrics?.[name];
            if (!field) return value;
            const unit = field.unit || field.type;
            if (unit === 'ratio') return new Intl.NumberFormat(undefined, { style: 'percent', maximumFractionDigits: 2 }).format(Number(value));
            const currency = this.response?.metadata?.currencyCode || this.catalog?.context?.currency;
            if ((unit === 'property_currency' || unit === 'TYPE_CURRENCY') && currency) return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(Number(value));
            const number = new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(Number(value));
            return unit === 'seconds' || unit === 'TYPE_SECONDS' ? `${number} s` : number;
        },
        rowValues(row) { return [...(row.dimensionValues || []), ...(row.metricValues || [])].map((cell, index) => this.formatValue(this.headers[index], cell.value)); },
    };
}
