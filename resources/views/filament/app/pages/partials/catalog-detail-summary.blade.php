@if ($summaryData !== null && ($summaryData['mode'] ?? '') === 'detail')
    <div data-catalog-detail-summary="{{ $entity }}" class="catalog-panel rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
        @if ($entity === 'customers')
            @php
                $ltvRow = $summaryData['ltv_row'] ?? [];
                $ltvMeta = $summaryData['ltv_meta'] ?? [];
                $ltvCurr = $ltvMeta['currency'] ?? 'USD';
                $ltvPrefix = $ltvCurr ? ($ltvCurr . ' ') : '';
                $ltvMixed = !empty($ltvMeta['currency_mixed']);
                $storeBreakdown = $summaryData['store_breakdown'] ?? [];
                $touches = $summaryData['touches'] ?? [];
                $touchesByChannel = $touches['by_channel'] ?? [];
            @endphp
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Customer 360° Cross-Channel Summary') }}</h3>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Purchases Across Stores') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        @if ($ltvMixed || ($ltvRow['customer_ltv'] ?? null) === null)
                            <span class="text-sm font-medium text-amber-600 dark:text-amber-400">{{ __('Mixed currencies') }}</span>
                        @else
                            {{ $ltvPrefix }}{{ number_format((float) $ltvRow['customer_ltv'], 2) }}
                        @endif
                    </div>
                    <div class="mt-0.5 text-xs text-gray-500">
                        {{ ((int) ($ltvRow['repeat_customer_count'] ?? 0)) > 0 ? __('Repeat multi-order buyer') : __('Single-order / prospect profile') }}
                    </div>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Pre-Purchase Marketing Touches') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format((int) ($touches['total_touches'] ?? 0)) }}</div>
                    <div class="mt-0.5 text-xs text-gray-500">{{ __('Touches prior to first purchase') }}</div>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Linked Channel Profiles') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format((int) ($ltvRow['customer_count'] ?? 1)) }}</div>
                    <div class="mt-0.5 text-xs text-gray-500">{{ __('Across connected channels & stores') }}</div>
                </div>
            </div>

            @if ($touchesByChannel !== [])
                <div class="mt-4 border-t border-gray-100 pt-4 dark:border-white/5">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Touches by Channel Prior to First Order') }}</h4>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($touchesByChannel as $tCh)
                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-medium dark:border-white/10 dark:bg-gray-800">
                                <span class="font-semibold uppercase text-primary-600 dark:text-primary-400">{{ $tCh['channel'] ?? 'unknown' }}</span>
                                <span>{{ number_format((int) ($tCh['touches'] ?? 0)) }} {{ __('touches') }}</span>
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($storeBreakdown !== [])
                <div class="mt-4 border-t border-gray-100 pt-4 dark:border-white/5">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Purchases by Store & Channel') }}</h4>
                    <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach ($storeBreakdown as $sb)
                            <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50/70 px-3 py-2 text-xs dark:border-white/5 dark:bg-gray-800/50">
                                <div>
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $sb['store_name'] ?? ('Store #' . ($sb['store'] ?? '—')) }}</span>
                                    <span class="ml-1 uppercase text-gray-500">({{ $sb['channel'] ?? 'commerce' }})</span>
                                </div>
                                <div class="font-semibold text-gray-900 dark:text-white">
                                    {{ number_format((int) ($sb['order_count'] ?? 0)) }} {{ __('orders') }}
                                    @if (($sb['revenue'] ?? null) !== null)
                                        · {{ $ltvPrefix }}{{ number_format((float) $sb['revenue'], 2) }}
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @elseif ($entity === 'orders')
            @php
                $footprint = $summaryData['channel_footprint'] ?? [];
                $fMeta = $summaryData['meta'] ?? [];
                $fCurr = $fMeta['currency'] ?? $currency ?? 'USD';
                $fPrefix = $fCurr ? ($fCurr . ' ') : '';
            @endphp
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Cross-Channel Order References') }}</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ __('Channels referencing this canonical order across commerce and analytics integrations.') }}
            </p>
            @if ($footprint !== [])
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($footprint as $fp)
                        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50/70 p-3.5 text-xs dark:border-white/10 dark:bg-gray-800/50">
                            <div>
                                <span class="font-semibold uppercase text-primary-600 dark:text-primary-400">{{ $fp['channel'] ?? 'channel' }}</span>
                                @if (!empty($fp['store_name']))
                                    <span class="ml-1 font-medium text-gray-700 dark:text-gray-300">· {{ $fp['store_name'] }}</span>
                                @endif
                                @if (!empty($fp['financial_status']))
                                    <span class="ml-1.5 rounded bg-gray-200 px-1.5 py-0.5 text-[10px] font-semibold uppercase dark:bg-gray-700">{{ $fp['financial_status'] }}</span>
                                @endif
                            </div>
                            <div class="font-semibold text-gray-900 dark:text-white">
                                @if (($fp['revenue'] ?? null) !== null)
                                    {{ $fPrefix }}{{ number_format((float) $fp['revenue'], 2) }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @elseif ($entity === 'stores')
            @php
                $dRow = $summaryData['row'] ?? [];
                $dMeta = $summaryData['meta'] ?? [];
                $dCurr = $dMeta['currency'] ?? $currency ?? 'USD';
                $dPrefix = $dCurr ? ($dCurr . ' ') : '';
            @endphp
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Store Commerce Summary') }}</h3>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Store Revenue') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ ($dRow['revenue'] ?? null) === null ? '—' : ($dPrefix . number_format((float) $dRow['revenue'], 2)) }}
                    </div>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Store Orders') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format((int) ($dRow['order_count'] ?? 0)) }}</div>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Store AOV') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ ($dRow['aov'] ?? null) === null ? '—' : ($dPrefix . number_format((float) $dRow['aov'], 2)) }}
                    </div>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Catalog Products') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format((int) ($dRow['product_count'] ?? 0)) }}</div>
                </div>
            </div>
        @elseif ($entity === 'products')
            @php
                $dRow = $summaryData['row'] ?? [];
                $dMeta = $summaryData['meta'] ?? [];
                $dCurr = $dMeta['currency'] ?? $currency ?? 'USD';
                $dPrefix = $dCurr ? ($dCurr . ' ') : '';
            @endphp
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Product Order Footprint') }}</h3>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Orders Containing Product') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format((int) ($dRow['order_count'] ?? 0)) }}</div>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Order Revenue Involving Product') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ ($dRow['revenue'] ?? null) === null ? '—' : ($dPrefix . number_format((float) $dRow['revenue'], 2)) }}
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif
