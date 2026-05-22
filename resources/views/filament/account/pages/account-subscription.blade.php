<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach ($plans as $plan)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 border border-gray-200 dark:border-gray-700 flex flex-col">
                <h3 class="text-xl font-bold mb-2">{{ $plan->name }}</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4 flex-grow">{{ $plan->description }}</p>
                <div class="text-3xl font-bold mb-4">
                    {{ $plan->price > 0 ? '$' . $plan->price : 'Free' }}
                    @if($plan->price > 0)
                        <span class="text-sm font-normal text-gray-500">/ {{ $plan->billing_cycle }}</span>
                    @endif
                </div>

                @if($plan->price > 0)
                    <form action="{{ route('paypal.checkout') }}" method="POST">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Select Billing Profile</label>
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
                @else
                    <button disabled class="w-full bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-bold py-2 px-4 rounded-lg">
                        Current Plan
                    </button>
                @endif
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
