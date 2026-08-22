@php
    $tenant = Filament\Facades\Filament::getTenant();
    $isPaidTier = $tenant?->billingProfile && !in_array($tenant->billingProfile->tier, [
        \App\Enums\UserTier::FREE,
        \App\Enums\UserTier::SUSPENDED,
    ]);
    $upgradeUrl = \App\Filament\App\Pages\SubscriptionFeatures::getUrl();
@endphp

@if($isPaidTier)
    <button type="button" @click="window.print()" class="export-btn">
        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/>
        </svg>
        <span>{{ __('Export PDF') }}</span>
    </button>
@else
    <a href="{{ $upgradeUrl }}"
       class="export-btn opacity-85 hover:opacity-100 transition-opacity cursor-pointer group inline-flex items-center"
       title="{{ __('PDF Export is available on Pro, Ultra, and Enterprise plans. Click to upgrade.') }}">
        <x-heroicon-m-lock-closed class="w-4 h-4 mr-1.5 text-amber-500 group-hover:text-amber-400 shrink-0"/>
        <span>{{ __('Export PDF') }}</span>
        <span class="ml-1.5 px-1.5 py-0.5 text-[10px] font-bold uppercase rounded bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">{{ __('PRO') }}</span>
    </a>
@endif
