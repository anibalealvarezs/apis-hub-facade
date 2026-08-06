@props([
    'variant' => 'fb',
    'state' => '',
    'loading' => null,
    'search' => false,
    'searchPlaceholder' => 'Filter rows...',
    'pagination' => true,
])

@php
    $prefix = $state ? $state . '.' : '';
    $containerClass = match ($variant) {
        'ga4' => 'ga4-table-container',
        'gsc' => 'gsc-table-container',
        default => 'fb-table-container',
    };
    $tabNavClass = match ($variant) {
        'ga4' => 'tab-nav-ga4',
        'gsc' => 'tab-nav-gsc',
        default => 'tab-nav-fb',
    };
    $paginationContainer = match ($variant) {
        'ga4' => 'ga4-pagination-container',
        'gsc' => 'gsc-pagination-container',
        default => 'fb-pagination-container',
    };
    $paginationText = match ($variant) {
        'ga4' => 'ga4-pagination-text',
        'gsc' => 'gsc-pagination-text',
        default => 'fb-pagination-text',
    };
    $paginationSelect = match ($variant) {
        'ga4' => 'ga4-pagination-select',
        'gsc' => 'gsc-pagination-select',
        default => 'fb-pagination-select',
    };
    $paginationBadge = match ($variant) {
        'ga4' => 'ga4-pagination-badge',
        'gsc' => 'gsc-pagination-badge',
        default => 'fb-pagination-badge',
    };
    $paginationBtn = match ($variant) {
        'ga4' => 'ga4-pagination-btn',
        'gsc' => 'gsc-pagination-btn',
        default => 'fb-pagination-btn',
    };
@endphp

<div {{ $attributes->merge(['class' => $containerClass . ' relative']) }}>
    @if ($loading)
        <div x-show="{{ $loading }}"
             class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-xl">
            <x-filament::loading-indicator class="h-8 w-8 text-primary-500"/>
        </div>
    @endif

    @if (! empty($header))
        {{ $header }}
    @endif

    @if ($search)
        <div class="p-4 border-b border-gray-200 dark:border-white/5 bg-white dark:bg-transparent">
            <div class="relative w-full max-w-md">
                <div class="absolute inset-y-0 left-0 rtl:right-0 rtl:left-auto w-10 flex items-center justify-center pointer-events-none">
                    <x-heroicon-o-magnifying-glass class="w-4 h-4 text-gray-500 dark:text-gray-400"/>
                </div>
                <input type="text" x-model.debounce.300ms="{{ $prefix }}searchQuery"
class="bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-white/10 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2 dash-search-input"
                       placeholder="{{ $searchPlaceholder }}">
            </div>
        </div>
    @endif

    <div class="overflow-x-auto">
        {{ $slot }}
    </div>

    @if ($pagination)
        <div class="{{ $paginationContainer }}" x-show="{{ $prefix }}rows.length > 0">
            <div class="flex items-center gap-4 mb-4 sm:mb-0">
                <span class="{{ $paginationText }} font-medium">{{ __('Rows per page:') }}</span>
                <select x-model="{{ $prefix }}pageSize" class="{{ $paginationSelect }}">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="250">250</option>
                </select>
            </div>
            <div class="flex items-center gap-6">
                <span class="{{ $paginationText }}">
                    {{ __('Page') }} <strong x-text="{{ $prefix }}currentPage"></strong> {{ __('of') }} <strong
                        x-text="{{ $prefix }}totalPages"></strong>
                    <span class="{{ $paginationBadge }}">(<span x-text="{{ $prefix }}rows.length"></span> {{ __('results') }})</span>
                </span>
                <div class="flex gap-2">
                    <button @click="{{ $prefix }}prevPage()" :disabled="{{ $prefix }}currentPage === 1"
                            class="{{ $paginationBtn }}">{{ __('Prev') }}</button>
                    <button @click="{{ $prefix }}nextPage()" :disabled="{{ $prefix }}currentPage === {{ $prefix }}totalPages"
                            class="{{ $paginationBtn }}">{{ __('Next') }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
