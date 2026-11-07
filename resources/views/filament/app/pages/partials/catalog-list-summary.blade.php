@if ($summaryData !== null && ($summaryData['mode'] ?? '') === 'list')
    @php
        $sRow = $summaryData['row'] ?? [];
        $sMeta = $summaryData['meta'] ?? [];
        $sCurrency = $sMeta['currency'] ?? 'USD';
        $sCurrPrefix = $sCurrency ? ($sCurrency . ' ') : '';
        $sMixed = !empty($sMeta['currency_mixed']);
        $chComparison = $summaryData['channel_comparison'] ?? [];
    @endphp
    <div data-catalog-list-summary="{{ $entity }}" class="catalog-summary-panel rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
        @if ($entity === 'orders')
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Canonical Orders') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format((int) ($sRow['canonical_order_count'] ?? $sRow['order_count'] ?? 0)) }}</div>
                    <div class="mt-0.5 text-xs text-gray-500">{{ number_format((int) ($sRow['order_count'] ?? 0)) }} {{ __('channel records') }}</div>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Gross Revenue') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        @if ($sMixed || ($sRow['revenue'] ?? null) === null)
                            <span class="text-sm font-medium text-amber-600 dark:text-amber-400">{{ __('Mixed currencies') }}</span>
                        @else
                            {{ $sCurrPrefix }}{{ number_format((float) $sRow['revenue'], 2) }}
                        @endif
                    </div>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Average Order Value (AOV)') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        @if ($sMixed || ($sRow['aov'] ?? null) === null)
                            —
                        @else
                            {{ $sCurrPrefix }}{{ number_format((float) $sRow['aov'], 2) }}
                        @endif
                    </div>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Discounts') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        @if ($sMixed || ($sRow['total_discount'] ?? null) === null)
                            —
                        @else
                            {{ $sCurrPrefix }}{{ number_format((float) $sRow['total_discount'], 2) }}
                        @endif
                    </div>
                </div>
            </div>
            @if (count($chComparison) > 1)
                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3 text-xs dark:border-white/5">
                    <span class="font-semibold uppercase text-gray-500">{{ __('Cross-Channel Comparison:') }}</span>
                    @foreach ($chComparison as $chRow)
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-2.5 py-1 font-medium text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                            <span class="uppercase">{{ $chRow['channel'] ?? 'unknown' }}:</span>
                            <span>{{ number_format((int) ($chRow['order_count'] ?? 0)) }} {{ __('orders') }}</span>
                            @if (($chRow['revenue'] ?? null) !== null)
                                <span>· {{ $sCurrPrefix }}{{ number_format((float) $chRow['revenue'], 2) }}</span>
                            @endif
                        </span>
                    @endforeach
                </div>
            @endif
        @elseif ($entity === 'customers')
            @php $touches = $summaryData['touches'] ?? []; @endphp
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Canonical Customers') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format((int) ($sRow['canonical_customer_count'] ?? 0)) }}</div>
                    <div class="mt-0.5 text-xs text-gray-500">{{ number_format((int) ($sRow['customer_count'] ?? 0)) }} {{ __('channel profiles') }}</div>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Avg Revenue Across Channels') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        @if ($sMixed || ($sRow['avg_revenue_per_customer'] ?? null) === null)
                            <span class="text-sm font-medium text-amber-600 dark:text-amber-400">{{ __('Mixed currencies') }}</span>
                        @else
                            {{ $sCurrPrefix }}{{ number_format((float) $sRow['avg_revenue_per_customer'], 2) }}
                        @endif
                    </div>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Repeat Buyer Rate') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format((float) ($sRow['repeat_buyer_rate'] ?? 0), 1) }}%</div>
                    <div class="mt-0.5 text-xs text-gray-500">{{ number_format((int) ($sRow['high_frequency_customer_count'] ?? 0)) }} {{ __('buyers with 3+ orders') }}</div>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Avg Pre-Purchase Touches') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format((float) ($touches['avg_touches_per_customer'] ?? 0), 1) }}</div>
                    <div class="mt-0.5 text-xs text-gray-500">{{ number_format((int) ($touches['total_touches'] ?? 0)) }} {{ __('total pre-order touches') }}</div>
                </div>
            </div>
        @elseif ($entity === 'stores')
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Connected Stores') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format((int) ($sRow['store_count'] ?? 0)) }}</div>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Combined Store Revenue') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ ($sMixed || ($sRow['revenue'] ?? null) === null) ? '—' : ($sCurrPrefix . number_format((float) $sRow['revenue'], 2)) }}
                    </div>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Orders') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format((int) ($sRow['order_count'] ?? 0)) }}</div>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Blended Store AOV') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ ($sMixed || ($sRow['aov'] ?? null) === null) ? '—' : ($sCurrPrefix . number_format((float) $sRow['aov'], 2)) }}
                    </div>
                </div>
            </div>
        @elseif ($entity === 'products')
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Canonical Products') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format((int) ($sRow['canonical_product_count'] ?? $sRow['product_count'] ?? 0)) }}</div>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Average Catalog Price') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ ($sMixed || ($sRow['average_price'] ?? null) === null) ? '—' : ($sCurrPrefix . number_format((float) $sRow['average_price'], 2)) }}
                    </div>
                </div>
                <div class="rounded-lg border border-gray-100 bg-gray-50/70 p-4 dark:border-white/5 dark:bg-gray-800/50">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Price Range') }}</div>
                    <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                        @if ($sMixed || ($sRow['min_price'] ?? null) === null)
                            —
                        @else
                            {{ $sCurrPrefix }}{{ number_format((float) $sRow['min_price'], 2) }} – {{ $sCurrPrefix }}{{ number_format((float) ($sRow['max_price'] ?? 0), 2) }}
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif
