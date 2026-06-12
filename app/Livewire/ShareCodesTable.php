<?php

namespace App\Livewire;

use App\Models\OneTimeShareToken;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Livewire\Component;

class ShareCodesTable extends Component
{
    public $codes = [];

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
        $project = Filament::getTenant();
        if (!$project) {
            return;
        }

        $code = 'APISHUB-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));

        OneTimeShareToken::create([
            'project_id' => $project->id,
            'token' => $code,
            'created_by' => auth()->id(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->refreshCodes();

        $this->dispatch('share-code-copied');
    }

    public function render()
    {
        return view('livewire.share-codes-table');
    }
}
