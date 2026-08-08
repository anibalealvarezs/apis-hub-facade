@php
    $isFounder = $plan->tier === 'founder';
@endphp
<div class="bg-white dark:bg-gray-900 rounded-xl p-6 flex flex-col justify-between {{ $isFounder ? 'shadow-lg relative pc-founder-card' : 'border border-gray-200 dark:border-gray-800' }}">
    <div>
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $plan->name }}</h3>
            @if($profile && $profile->tier?->value === $plan->tier)
                <span class="bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300 text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full border border-green-200 dark:border-green-800">{{ __('Current') }}</span>
            @endif
        </div>
        <p class="text-gray-500 dark:text-gray-400 mb-6 text-sm leading-relaxed">{{ $plan->description }}</p>
        <div class="text-3xl font-black text-gray-900 dark:text-white mb-6">
            @if($plan->price > 0)
                ${{ $plan->price }} <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $cycle === 'monthly' ? __('/ month') : __('/ year') }}</span>
            @else
                Free
            @endif
        </div>
    </div>

    <div>
        @if($profile && $profile->tier?->value === $plan->tier)
            <button disabled class="w-full bg-green-50 dark:bg-green-950/20 text-green-700 dark:text-green-400 font-bold py-2.5 px-4 rounded-lg border border-green-200 dark:border-green-900/50 text-center text-sm cursor-not-allowed">
                {{ __('✓ Currently Active Plan') }}
            </button>
        @elseif($profile && $profile->tier?->value === 'enterprise')
            <button disabled class="w-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 font-bold py-2.5 px-4 rounded-lg text-center text-sm cursor-not-allowed">
                {{ __('Locked (Enterprise Protected)') }}
            </button>
        @elseif($profile && $profile->tier?->value === 'founder')
            <button disabled class="w-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 font-bold py-2.5 px-4 rounded-lg text-center text-sm cursor-not-allowed">
                {{ __('Founder Exclusive Tier') }}
            </button>
        @elseif($plan->price > 0)
            <div class="space-y-3">
                @if(app(\App\Settings\PaymentSettings::class)->enable_paypal)
                    <form action="{{ route('paypal.checkout') }}" method="POST">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <input type="hidden" name="billing_profile_id" value="{{ $profile?->id }}">
                        <input type="hidden" name="billing_cycle" value="{{ $cycle }}">
                        
                        <button type="submit" class="w-full font-bold py-2.5 px-4 rounded-lg flex justify-center items-center gap-2 text-sm shadow transition-all hover:opacity-90 pc-paypal-btn">
                            <x-heroicon-o-credit-card class="w-4 h-4"/>
                            {{ __('Subscribe via PayPal') }}
                        </button>
                    </form>
                @endif
                
                @if(app(\App\Settings\PaymentSettings::class)->enable_stripe)
                    <form action="{{ route('stripe.checkout') }}" method="POST">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <input type="hidden" name="billing_profile_id" value="{{ $profile?->id }}">
                        <input type="hidden" name="billing_cycle" value="{{ $cycle }}">
                        
                        <div class="mb-3">
                            <input type="text" name="coupon_code" class="w-full rounded-lg border-gray-300 dark:border-gray-800 dark:bg-gray-800 text-xs text-gray-900 dark:text-white" placeholder="{{ __('Promo Code (Optional)') }}">
                        </div>
                        
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 dark:bg-indigo-700 dark:hover:bg-indigo-600 text-white font-bold py-2.5 px-4 rounded-lg flex justify-center items-center gap-2 text-sm shadow transition-all">
                            <x-heroicon-o-credit-card class="w-4 h-4"/>
                            {{ __('Subscribe via Stripe') }}
                        </button>
                    </form>
                @endif

                @if(!app(\App\Settings\PaymentSettings::class)->enable_paypal && !app(\App\Settings\PaymentSettings::class)->enable_stripe)
                    <div class="p-3 bg-yellow-50 dark:bg-yellow-950/20 text-yellow-700 dark:text-yellow-400 rounded-lg text-xs text-center border border-yellow-200 dark:border-yellow-900/50">
                        {{ __('Subscriptions disabled') }}
                    </div>
                @endif
            </div>
        @else
            <button disabled class="w-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 font-bold py-2.5 px-4 rounded-lg text-center text-sm cursor-not-allowed">
                {{ __('Free Tier') }}
            </button>
        @endif
    </div>
</div>
