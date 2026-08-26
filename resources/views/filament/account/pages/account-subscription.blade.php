<x-filament-panels::page>
    @if(session('success'))
        <div class="mb-4 p-4 text-green-800 bg-green-100 rounded-lg dark:bg-green-900 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 text-red-800 bg-red-100 rounded-lg dark:bg-red-900 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <!-- Profile Selector Header -->
    <div
        class="mb-6 p-6 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 flex flex-col md:flex-row md:items-center gap-4">
        <div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Billing & Subscription Manager') }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('Select a billing profile to manage its subscription tier and payments.') }}</p>
        </div>
        <div class="w-full md:w-80">
            <label
                class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">{{ __('Select Active Profile') }}</label>
            <select wire:model.live="selectedProfileId"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 text-sm font-semibold">
                @foreach(auth()->user()->getAvailableBillingProfiles() as $p)
                    <option value="{{ $p->id }}">{{ $p->display_name }} ({{ ucfirst($p->type) }})</option>
                @endforeach
            </select>
        </div>
    </div>

    @php
        $profile = $this->getSelectedProfileProperty();
    @endphp

    @if($profile)
        <!-- Current Profile Status Card -->
        <div class="mb-8 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="bd-text-2xs font-extrabold uppercase tracking-widest bg-primary-500/10 text-primary-600 dark:text-primary-400 border border-primary-500/20 px-2.5 py-1 rounded-full">
                    {{ __('Active Profile') }}
                </span>
                <h3 class="text-2xl font-bold mt-2 text-gray-900 dark:text-white">{{ $profile->display_name }}</h3>
                <p class="text-xs mt-1 text-gray-500 dark:text-gray-400">
                    {{ __('Billing Status:') }} 
                    <span class="font-bold uppercase tracking-wider {{ $profile->status === 'active' ? 'text-success-600 dark:text-success-400' : 'text-warning-600 dark:text-warning-400' }}">
                        {{ $profile->status ?? 'Active' }}
                    </span>
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Current Plan:') }}</span>
                <span class="font-black px-4 py-2 rounded-xl shadow-sm text-xs uppercase tracking-widest bg-primary-600 text-white dark:bg-primary-500">
                    {{ $profile->tier->value ?? $profile->tier }}
                </span>
            </div>
        </div>
    @endif

    <div x-data="{ billingCycle: 'monthly' }">
        <!-- Monthly/Annual Toggle -->
        <div class="flex justify-center">
            <div
                class="bg-gray-100 dark:bg-gray-900 p-1 rounded-xl flex items-center shadow-inner border border-gray-200 dark:border-gray-800">
                <button @click="billingCycle = 'monthly'"
                        :class="{ 'bg-white dark:bg-gray-800 shadow shadow-gray-200 dark:shadow-none text-gray-900 dark:text-white font-bold': billingCycle === 'monthly', 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 font-medium': billingCycle !== 'monthly' }"
                        class="px-6 py-2 rounded-lg text-sm transition-all duration-200">
                    Monthly
                </button>
                <button @click="billingCycle = 'annual'"
                        :class="{ 'bg-white dark:bg-gray-800 shadow shadow-gray-200 dark:shadow-none text-gray-900 dark:text-white font-bold': billingCycle === 'annual', 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 font-medium': billingCycle !== 'annual' }"
                        class="px-6 py-2 rounded-lg text-sm transition-all duration-200 flex items-center gap-2">
                    Annual
                    <span
                        class="bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 bd-text-2xs px-2 py-0.5 rounded-full font-extrabold tracking-wider">{{ __('2 Months Free') }}</span>
                </button>
            </div>
        </div>

        <!-- Monthly Plans Grid -->
        <div x-show="billingCycle === 'monthly'" class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
            @foreach ($monthlyPlans as $plan)
                @include('filament.account.pages.plan-card', ['plan' => $plan, 'cycle' => 'monthly'])
            @endforeach
        </div>

        <!-- Annual Plans Grid -->
        <div x-show="billingCycle === 'annual'" x-cloak
             class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
            @foreach ($annualPlans as $plan)
                @include('filament.account.pages.plan-card', ['plan' => $plan, 'cycle' => 'annual'])
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
