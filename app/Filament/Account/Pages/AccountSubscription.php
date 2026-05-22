<?php

namespace App\Filament\Account\Pages;

use Filament\Pages\Page;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Auth;

class AccountSubscription extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static string $view = 'filament.account.pages.account-subscription';

    protected static ?string $navigationGroup = 'Billing & Payments';

    protected static ?string $title = 'My Subscription';

    public $plans;

    public function mount()
    {
        $this->plans = SubscriptionPlan::where('is_active', true)->orderBy('price', 'asc')->get();
    }
}
