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

    <div x-data="{ billingCycle: 'monthly' }">
        <div class="flex justify-center mb-8">
            <div class="bg-gray-100 dark:bg-gray-900 p-1 rounded-xl flex items-center shadow-sm border border-gray-200 dark:border-gray-800">
                <button @click="billingCycle = 'monthly'" 
                        :class="{ 'bg-white dark:bg-gray-800 shadow shadow-gray-200 dark:shadow-none text-gray-900 dark:text-white': billingCycle === 'monthly', 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300': billingCycle !== 'monthly' }" 
                        class="px-6 py-2 rounded-lg text-sm font-medium transition-all duration-200">
                    Monthly
                </button>
                <button @click="billingCycle = 'annual'" 
                        :class="{ 'bg-white dark:bg-gray-800 shadow shadow-gray-200 dark:shadow-none text-gray-900 dark:text-white': billingCycle === 'annual', 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300': billingCycle !== 'annual' }" 
                        class="px-6 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2">
                    Annual
                    <span class="bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 text-xs px-2 py-0.5 rounded-full font-bold">2 Months Free</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach ($plans as $plan)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 border border-gray-200 dark:border-gray-700 flex flex-col">
                <h3 class="text-xl font-bold mb-2">{{ $plan->name }}</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4 flex-grow">{{ $plan->description }}</p>
                <div class="text-3xl font-bold mb-4 min-h-[60px]">
                    @if($plan->price > 0)
                        <div x-show="billingCycle === 'monthly'">
                            ${{ $plan->price }} <span class="text-sm font-normal text-gray-500">/ month</span>
                        </div>
                        <div x-show="billingCycle === 'annual'" style="display: none;">
                            ${{ $plan->annual_price }} <span class="text-sm font-normal text-gray-500">/ year</span>
                            @if($plan->annual_discount_percentage > 0)
                                <div class="text-sm text-green-600 dark:text-green-400 mt-1">Save {{ $plan->annual_discount_percentage }}%</div>
                            @endif
                        </div>
                    @else
                        Free
                    @endif
                </div>

                @if(auth()->user()->tier?->value === $plan->tier || auth()->user()->tier === $plan->tier)
                    <button disabled class="w-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 font-bold py-2 px-4 rounded-lg border border-green-300 dark:border-green-700">
                        Current Plan
                    </button>
                @elseif(auth()->user()->tier?->value === 'enterprise')
                    <button disabled class="w-full bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-bold py-2 px-4 rounded-lg">
                        Locked (Irreversible)
                    </button>
                @elseif(auth()->user()->tier?->value === 'founder')
                    <button disabled class="w-full bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-bold py-2 px-4 rounded-lg">
                        Founder Locked
                    </button>
                @elseif($plan->price > 0)
                    @if(app(\App\Settings\PaymentSettings::class)->enable_paypal)
                        <form action="{{ route('paypal.checkout') }}" method="POST" class="mb-4">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <input type="hidden" name="billing_cycle" :value="billingCycle">
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Select Billing Profile (PayPal)</label>
                                <select name="billing_profile_id" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900" required>
                                    @foreach(auth()->user()->getAvailableBillingProfiles() as $profile)
                                        <option value="{{ $profile->id }}">{{ $profile->name }} ({{ ucfirst($profile->type) }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-4 rounded-lg flex justify-center items-center gap-2">
                                <x-heroicon-o-credit-card class="w-5 h-5"/>
                                Subscribe via PayPal
                            </button>
                        </form>
                    @endif
                    
                    @if(app(\App\Settings\PaymentSettings::class)->enable_stripe)
                        <form action="{{ route('stripe.checkout') }}" method="POST">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <input type="hidden" name="billing_cycle" :value="billingCycle">
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Billing Profile (Stripe)</label>
                                <select name="billing_profile_id" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900" required>
                                    @foreach(auth()->user()->getAvailableBillingProfiles() as $profile)
                                        <option value="{{ $profile->id }}">{{ $profile->name }} ({{ ucfirst($profile->type) }})</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Promo Code (Optional)</label>
                                <input type="text" name="coupon_code" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="e.g. EARLYBIRD">
                            </div>
                            
                            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-500 text-white font-bold py-2 px-4 rounded-lg flex justify-center items-center gap-2">
                                <x-heroicon-o-credit-card class="w-5 h-5"/>
                                Subscribe via Stripe
                            </button>
                        </form>
                    @endif

                    @if(!app(\App\Settings\PaymentSettings::class)->enable_paypal && !app(\App\Settings\PaymentSettings::class)->enable_stripe)
                        <div class="p-4 bg-yellow-50 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-lg text-sm text-center border border-yellow-200 dark:border-yellow-800">
                            <strong>Atención:</strong> Las suscripciones se encuentran temporalmente deshabilitadas.
                        </div>
                    @endif
                @else
                    <button disabled class="w-full bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-bold py-2 px-4 rounded-lg">
                        Free Default
                    </button>
                @endif
            </div>
        @endforeach
        </div>
    </div>
</x-filament-panels::page>
