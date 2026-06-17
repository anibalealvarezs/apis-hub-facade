<?php

namespace App\Livewire;

use App\Models\OneTimeShareToken;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Livewire\Component;

class ShareCodesTable extends Component
{
    public $codes = [];

    public $showForm = false;

    public $email = '';

    protected $rules = [
        'email' => 'nullable|email',
    ];

    public function mount()
    {
        $this->refreshCodes();
    }

    public function refreshCodes()
    {
        $project = Filament::getTenant();
        if ($project) {
            $this->codes = OneTimeShareToken::where('project_id', $project->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
        }
    }

    public function generate()
    {
        $this->validate();

        $project = Filament::getTenant();
        if (!$project) {
            return;
        }

        $canInvite = app(\App\Services\BillingLifecycleService::class)->canInviteCollaborators(
            $project->billingProfile?->tier ?? \App\Enums\UserTier::FREE
        );

        if (!$canInvite) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title(__('Upgrade to Ultra or Enterprise plan to invite collaborators.'))
                ->send();
            return;
        }

        $code = 'APISHUB-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));

        OneTimeShareToken::create([
            'project_id' => $project->id,
            'token' => $code,
            'email' => $this->email ?: null,
            'created_by' => auth()->id(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->reset('email', 'showForm');
        $this->refreshCodes();
    }

    public function render()
    {
        $project = Filament::getTenant();
        $canInvite = false;
        if ($project) {
            $canInvite = app(\App\Services\BillingLifecycleService::class)->canInviteCollaborators(
                $project->billingProfile?->tier ?? \App\Enums\UserTier::FREE
            );
        }

        return view('livewire.share-codes-table', [
            'canInvite' => $canInvite,
        ]);
    }
}
