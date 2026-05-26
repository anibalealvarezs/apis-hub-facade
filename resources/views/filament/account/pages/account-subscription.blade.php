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
    <div class="mb-6 p-6 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Billing & Subscription Manager</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Select a billing profile to manage its subscription tier and payments.</p>
        </div>
        <div class="w-full md:w-80">
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Select Active Profile</label>
            <select wire:model.live="selectedProfileId" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 text-sm font-semibold">
                @foreach(auth()->user()->getAvailableBillingProfiles() as $p)
                    <option value="{{ $p->id }}">{{ $p->name }} ({{ ucfirst($p->type) }})</option>
                @endforeach
            </select>
        </div>
    </div>

    @php
        $profile = $this->getSelectedProfileProperty();
    @endphp

    @if($profile)
        <!-- Current Profile Status Card -->
        <div class="mb-8 p-6 bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl shadow-md text-white flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <span class="text-[10px] font-extrabold uppercase tracking-widest bg-white/20 text-white px-2.5 py-1 rounded-full">Active Profile</span>
                <h3 class="text-2xl font-bold mt-2">{{ $profile->name }}</h3>
                <p class="text-xs text-white/80 mt-1">Billing Status: <span class="font-bold uppercase tracking-wider {{ $profile->status === 'active' ? 'text-green-300' : 'text-yellow-300' }}">{{ $profile->status ?? 'Active' }}</span></p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold uppercase tracking-wider opacity-90">Current Plan:</span>
                <span class="bg-white text-indigo-700 font-black px-4 py-2 rounded-lg shadow text-xs uppercase tracking-widest">
                    {{ $profile->tier->value ?? $profile->tier }}
                </span>
            </div>
        </div>
    @endif

    <div x-data="{ billingCycle: 'monthly' }">
        <!-- Monthly/Annual Toggle -->
        <div class="flex justify-center mb-8">
            <div class="bg-gray-100 dark:bg-gray-900 p-1 rounded-xl flex items-center shadow-inner border border-gray-200 dark:border-gray-800">
                <button @click="billingCycle = 'monthly'" 
                        :class="{ 'bg-white dark:bg-gray-800 shadow shadow-gray-200 dark:shadow-none text-gray-900 dark:text-white font-bold': billingCycle === 'monthly', 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 font-medium': billingCycle !== 'monthly' }" 
                        class="px-6 py-2 rounded-lg text-sm transition-all duration-200">
                    Monthly
                </button>
                <button @click="billingCycle = 'annual'" 
                        :class="{ 'bg-white dark:bg-gray-800 shadow shadow-gray-200 dark:shadow-none text-gray-900 dark:text-white font-bold': billingCycle === 'annual', 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 font-medium': billingCycle !== 'annual' }" 
                        class="px-6 py-2 rounded-lg text-sm transition-all duration-200 flex items-center gap-2">
                    Annual
                    <span class="bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 text-[10px] px-2 py-0.5 rounded-full font-extrabold tracking-wider">2 Months Free</span>
                </button>
            </div>
        </div>

        <!-- Subscription Plan Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach ($plans as $plan)
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-800 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $plan->name }}</h3>
                        @if($profile && $profile->tier === $plan->tier)
                            <span class="bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300 text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full border border-green-200 dark:border-green-800">Current</span>
                        @endif
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 mb-6 text-sm leading-relaxed">{{ $plan->description }}</p>
                    <div class="text-3xl font-black text-gray-900 dark:text-white mb-6">
                        @if($plan->price > 0)
                            <div x-show="billingCycle === 'monthly'">
                                ${{ $plan->price }} <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">/ month</span>
                            </div>
                            <div x-show="billingCycle === 'annual'" style="display: none;">
                                ${{ $plan->annual_price }} <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">/ year</span>
                                @if($plan->annual_discount_percentage > 0)
                                    <div class="text-xs text-green-600 dark:text-green-400 mt-1 font-bold">Save {{ $plan->annual_discount_percentage }}%</div>
                                @endif
                            </div>
                        @else
                            Free
                        @endif
                    </div>
                </div>

                <div>
                    @if($profile && $profile->tier === $plan->tier)
                        <button disabled class="w-full bg-green-50 dark:bg-green-950/20 text-green-700 dark:text-green-400 font-bold py-2.5 px-4 rounded-lg border border-green-200 dark:border-green-900/50 text-center text-sm cursor-not-allowed">
                            ✓ Currently Active Plan
                        </button>
                    @elseif($profile && $profile->tier === 'enterprise')
                        <button disabled class="w-full bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-600 font-bold py-2.5 px-4 rounded-lg text-center text-sm cursor-not-allowed">
                            Locked (Enterprise Protected)
                        </button>
                    @elseif($profile && $profile->tier === 'founder')
                        <button disabled class="w-full bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-600 font-bold py-2.5 px-4 rounded-lg text-center text-sm cursor-not-allowed">
                            Founder Exclusive Tier
                        </button>
                    @elseif($plan->price > 0)
                        <div class="space-y-3">
                            @if(app(\App\Settings\PaymentSettings::class)->enable_paypal)
                                <form action="{{ route('paypal.checkout') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                    <input type="hidden" name="billing_profile_id" value="{{ $profile?->id }}">
                                    <input type="hidden" name="billing_cycle" :value="billingCycle">
                                    
                                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-2.5 px-4 rounded-lg flex justify-center items-center gap-2 text-sm shadow transition-all">
                                        <x-heroicon-o-credit-card class="w-4 h-4"/>
                                        Subscribe via PayPal
                                    </button>
                                </form>
                            @endif
                            
                            @if(app(\App\Settings\PaymentSettings::class)->enable_stripe)
                                <form action="{{ route('stripe.checkout') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                    <input type="hidden" name="billing_profile_id" value="{{ $profile?->id }}">
                                    <input type="hidden" name="billing_cycle" :value="billingCycle">
                                    
                                    <div class="mb-3">
                                        <input type="text" name="coupon_code" class="w-full rounded-lg border-gray-300 dark:border-gray-800 dark:bg-gray-850 text-xs text-gray-900 dark:text-white" placeholder="Promo Code (Optional)">
                                    </div>
                                    
                                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2.5 px-4 rounded-lg flex justify-center items-center gap-2 text-sm shadow transition-all">
                                        <x-heroicon-o-credit-card class="w-4 h-4"/>
                                        Subscribe via Stripe
                                    </button>
                                </form>
                            @endif

                            @if(!app(\App\Settings\PaymentSettings::class)->enable_paypal && !app(\App\Settings\PaymentSettings::class)->enable_stripe)
                                <div class="p-3 bg-yellow-50 dark:bg-yellow-950/20 text-yellow-700 dark:text-yellow-400 rounded-lg text-xs text-center border border-yellow-200 dark:border-yellow-900/50">
                                    Suscripciones deshabilitadas
                                </div>
                            @endif
                        </div>
                    @else
                        <button disabled class="w-full bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-600 font-bold py-2.5 px-4 rounded-lg text-center text-sm cursor-not-allowed">
                            Free Tier
                        </button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
