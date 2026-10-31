@props(['reportDashboards' => []])
<section x-data="ga4Reports({ tenant: tenantId, csrf: csrfToken, dashboards: @js($reportDashboards) })"
         x-effect="setContext(account, dateStart, dateEnd)"
         class="mt-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
    <h2 class="text-lg font-semibold">{{ __('Property reports') }}</h2>
    <p class="mt-1 text-sm text-gray-500">{{ __('Explore exact-window users, engagement, ecommerce, registered custom fields and cohorts. Reports keep their selected scope and filters; their totals are not sums of overlapping breakdowns.') }}</p>
    <button type="button" @click="loadCatalog(false, true)" :disabled="loading || !property" class="my-3 rounded-lg bg-primary-600 px-3 py-2 text-sm text-white disabled:opacity-50">{{ __('Load property catalog') }}</button>
    <span class="ml-2 text-sm" x-text="stateLabel(catalogState)"></span>
    <p role="alert" class="text-sm text-red-600 dark:text-red-400" x-show="error" x-text="error"></p>
    <div x-show="catalog" x-cloak>
        <fieldset :disabled="loading" class="grid gap-4 md:grid-cols-2 disabled:opacity-60">
            <label class="text-sm">{{ __('Report purpose') }}
                <select x-model="kind" @change="chooseKind()" class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-800">
                    <option value="window">{{ __('Users for the selected window') }}</option>
                    <option value="report">{{ __('Scoped source report') }}</option>
                    <option value="cohort">{{ __('Cohort retention') }}</option>
                    <template x-if="catalog?.context?.funnel_enabled"><option value="funnel">{{ __('Ordered event funnel (alpha)') }}</option></template>
                </select>
            </label>
            <label class="text-sm" x-show="kind === 'report'">{{ __('Start from a reporting recipe') }}
                <select x-model="preset" @change="choosePreset()" class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-800">
                    <option value="">{{ __('Choose fields') }}</option>
                    <template x-for="[key, recipe] in Object.entries(catalog?.recipes || {})" :key="key"><option :value="key" x-text="recipe.label || key.replaceAll('_', ' ')"></option></template>
                </select>
            </label>
            <label class="text-sm" x-show="kind !== 'funnel'">{{ __('Metrics') }}
                <select multiple x-model="metrics" size="6" class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-800">
                    <template x-for="field in fields('metrics')" :key="field.raw_name"><option :value="field.raw_name" x-text="field.label + (field.custom ? ' · custom' : '')"></option></template>
                </select>
            </label>
            <label class="text-sm" x-show="kind !== 'funnel'">{{ __('Breakdowns') }} <span x-text="dimensions.length + ' / 9'"></span>
                <select multiple x-model="dimensions" size="6" class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-800">
                    <template x-for="field in fields('dimensions')" :key="field.raw_name"><option :value="field.raw_name" x-text="field.label + (field.custom ? ' · custom' : '')"></option></template>
                </select>
            </label>
            <label class="text-sm">{{ __('Filter by report dimension') }}
                <select x-model="filterField" class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-800">
                    <option value="">{{ __('No additional filter') }}</option>
                    <template x-for="field in fields('dimensions')" :key="field.raw_name"><option :value="field.raw_name" x-text="field.label"></option></template>
                </select>
            </label>
            <label class="text-sm" x-show="filterField">{{ __('Exact values, separated by commas') }}<input x-model="filterValues" class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-800"></label>
            <label class="text-sm" x-show="kind === 'funnel'">{{ __('Ordered event names, separated by commas') }}<input x-model="steps" class="mt-1 block w-full rounded-lg border-gray-300 dark:bg-gray-800"></label>
            <label class="text-sm" x-show="kind === 'cohort'">{{ __('Cohort intervals') }}
                <select x-model="cohortGranularity" class="mt-1 rounded-lg border-gray-300 dark:bg-gray-800"><option>DAILY</option><option>WEEKLY</option><option>MONTHLY</option></select>
                <input type="number" min="0" max="90" x-model="cohortOffset" aria-label="{{ __('Follow-up intervals') }}" class="mt-1 w-24 rounded-lg border-gray-300 dark:bg-gray-800">
            </label>
            <div><button type="button" @click="run()" :disabled="!metrics.length || dimensions.length > 9" class="rounded-lg bg-primary-600 px-3 py-2 text-sm text-white disabled:opacity-50">{{ __('Request report') }}</button></div>
        </fieldset>
        <p class="my-3 text-sm" aria-live="polite" x-text="stateLabel(report?.state)"></p>
        <p class="text-sm text-gray-500" x-show="report && report.state !== 'ready'">{{ __('The report is queued separately from normal syncing. Completed data will appear after background processing. Refresh if automatic status checks stop.') }}</p>
        <template x-if="response">
            <div>
                <details class="my-3 text-sm"><summary>{{ __('Reporting quality and coverage') }}</summary><p>{{ __('Complete source response for the selected property, dates, fields and filters.') }}</p><p x-show="report.definition.kind === 'window'">{{ __('Users were requested for this whole window; daily unique counts were not summed.') }}</p><template x-for="message in qualityMessages()" :key="message"><p x-text="message"></p></template></details>
                <div class="overflow-auto"><table class="w-full text-sm"><thead><tr><template x-for="header in headers" :key="header"><th class="p-2 text-left" x-text="label(header)"></th></template></tr></thead><tbody><template x-for="(row, index) in response.rows || []" :key="index"><tr><template x-for="(value, column) in rowValues(row)" :key="column"><td class="border-t p-2" x-text="value"></td></template></tr></template></tbody></table></div>
                <p class="my-2 text-sm" x-show="!(response.rows || []).length">{{ __('No observations were returned for this exact report definition.') }}</p>
                <template x-if="response.totals?.length"><div class="mt-3 text-sm"><strong>{{ __('Source-reported totals') }}</strong><template x-for="(total, index) in response.totals" :key="index"><p x-text="(response.metricHeaders || []).map((header, position) => label(header.name) + ': ' + formatValue(header.name, total.metricValues[position].value)).join(' · ')"></p></template></div></template>
            </div>
        </template>
        <div x-show="report?.definition && Object.keys(dashboards).length" class="mt-4 flex flex-wrap gap-2">
            <input x-model="title" placeholder="{{ __('Widget title') }}" class="rounded-lg border-gray-300 dark:bg-gray-800">
            <select x-model="dashboard" aria-label="{{ __('Dashboard') }}" class="rounded-lg border-gray-300 dark:bg-gray-800"><option value="">{{ __('Select dashboard') }}</option><template x-for="[id, name] in Object.entries(dashboards)" :key="id"><option :value="id" x-text="name"></option></template></select>
            <select x-model="widgetType" aria-label="{{ __('Report display') }}" class="rounded-lg border-gray-300 dark:bg-gray-800"><option value="table">{{ __('Table') }}</option><option value="tile" :disabled="dimensions.length > 0 || !['window','report'].includes(kind)">{{ __('Metric cards (no breakdowns)') }}</option></select>
            <button type="button" @click="addWidget()" :disabled="loading || !dashboard || !title" class="rounded-lg bg-primary-600 px-3 py-2 text-sm text-white disabled:opacity-50">{{ __('Add report widget') }}</button>
            <span class="text-sm" x-text="saved"></span>
        </div>
    </div>
</section>
