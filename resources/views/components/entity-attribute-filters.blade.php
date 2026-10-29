@props(['tab' => 'activeTab'])
<div x-data="{ attributeRelation: '' }"
     x-init="await initEntityAttributes(); attributeRelation = entityAttributeRelationForTab({{ $tab }}); if (attributeRelation) loadEntityAttributeOptions(attributeRelation); $watch(@js($tab), value => { attributeRelation = entityAttributeRelationForTab(value); if (attributeRelation) loadEntityAttributeOptions(attributeRelation); }); $watch(typeof accounts !== 'undefined' ? 'accounts' : 'account', () => { if (attributeRelation) loadEntityAttributeOptions(attributeRelation); });"
     x-show="attributeRelation !== ''" x-cloak data-testid="entity-attribute-filters"
     class="p-4 border-b border-gray-200 dark:border-gray-700 space-y-3">
    <div class="flex items-center flex-wrap gap-3">
        <span class="text-sm font-semibold">{{ __('Entity attributes') }}</span>
        <span x-show="entityAttributeLoading" class="text-xs text-gray-500">{{ __('Loading attributes…') }}</span>
        <button type="button" @click="clearEntityAttributes()" class="text-sm text-primary-600">{{ __('Clear attribute filters') }}</button>
        <span class="text-xs text-gray-500" x-text="Object.keys(getEntityAttributeFilters()).length + ' {{ __('active') }}'"></span>
    </div>
    <p x-show="entityAttributeError" x-text="entityAttributeError" role="alert" class="text-sm text-red-600"></p>
    <button type="button" x-show="entityAttributeError && !entityAttributeLoading"
            @click="loadEntityAttributeOptions(attributeRelation)" class="text-sm text-primary-600">{{ __('Retry attribute filters') }}</button>
    <div class="flex flex-wrap gap-4">
        <template x-for="([path, field]) in getEntityAttributeFields(attributeRelation)" :key="path">
            <div class="space-y-1">
                <label class="block text-xs font-medium" x-text="field.field.replace(/([A-Z])/g, ' $1')"></label>
                <template x-if="!isEntityAttributeRange(field)">
                    <div>
                        <x-ui.asset-selector model="entityAttributeSelections[path]" options="field.options" :multiple="true"
                            placeholder="{{ __('All values') }}" size="xs" changeEvent="entityAttributesChanged()" />
                        <span x-show="field.truncated" class="text-xs text-gray-500">{{ __('First 200 values; use widget rules for other values.') }}</span>
                    </div>
                </template>
                <template x-if="isEntityAttributeRange(field)">
                    <div class="flex items-center gap-2">
                        <input :type="entityAttributeInputType(field)" step="any" x-model="entityAttributeRanges[path].min"
                            @change="entityAttributesChanged()" aria-label="{{ __('Minimum') }}" placeholder="{{ __('Minimum') }}"
                            class="w-32 text-xs rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-600">
                        <span>–</span>
                        <input :type="entityAttributeInputType(field)" step="any" x-model="entityAttributeRanges[path].max"
                            @change="entityAttributesChanged()" aria-label="{{ __('Maximum') }}" placeholder="{{ __('Maximum') }}"
                            class="w-32 text-xs rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-600">
                    </div>
                </template>
            </div>
        </template>
    </div>
    <p class="text-xs text-gray-500">{{ __('Filters use the latest synced entity attributes and apply to the tables, chart and totals. Numeric bounds use stored entity units.') }}</p>
</div>
