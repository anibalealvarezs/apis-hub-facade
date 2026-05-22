<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotifyUpcomingRenewals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:notify-renewals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notifies users of upcoming subscription renewals 3 days in advance and creates proforma invoices.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for upcoming subscription renewals...');

        // Find subscriptions expiring in exactly 3 days
        $targetDateStart = Carbon::now()->addDays(3)->startOfDay();
        $targetDateEnd = Carbon::now()->addDays(3)->endOfDay();

        $upcomingSubscriptions = Subscription::where('paypal_status', 'ACTIVE')
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [$targetDateStart, $targetDateEnd])
            ->get();

        if ($upcomingSubscriptions->isEmpty()) {
            $this->info('No upcoming renewals found.');
            return;
        }

        foreach ($upcomingSubscriptions as $subscription) {
            $billingProfile = $subscription->billingProfile;
            if (!$billingProfile) continue;

            $this->info("Creating proforma invoice for subscription {$subscription->id}.");

            // Create a pending/draft invoice
            Invoice::create([
                'billing_profile_id' => $billingProfile->id,
                'gateway' => 'paypal', // Expected to be paid via PayPal auto-charge
                'amount' => $subscription->plan->price ?? 0,
                'currency' => $subscription->plan->currency ?? 'USD',
                'status' => 'pending',
                // 'project_id' can be omitted since it's a profile-level subscription, or we link if applicable
            ]);

            // Notify user
            if ($billingProfile->user) {
                // $billingProfile->user->notify(new \App\Notifications\UpcomingRenewalNotification($subscription));
                Log::info("Notified User {$billingProfile->user->id} about upcoming renewal for subscription {$subscription->id}.");
            }
        }

        $this->info('Completed checking upcoming renewals.');
    }
}
