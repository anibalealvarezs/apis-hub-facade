<?php

use App\Filament\App\Pages\ProjectSettings;
use App\Mail\ProjectTransferMail;
use App\Models\Project;
use App\Models\ProjectTransfer;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->receiver = User::factory()->create();
    $this->outsider = User::factory()->create();

    $this->project = Project::factory()->create([
        'user_id' => $this->owner->id,
        'subdomain' => 'test-project',
        'is_active' => true,
    ]);

    // Add them to the project
    $this->project->users()->attach([$this->owner->id, $this->receiver->id]);
});

it('hides the transfer action from non-owners', function () {
    // Act as receiver (who is in the project but NOT the trueOwner)
    actingAs($this->receiver);
    
    // Filament uses panels with tenants, but Livewire can mount the page
    Livewire::test(ProjectSettings::class, ['tenant' => $this->project->subdomain])
        ->assertActionHidden('transfer')
        ->assertActionHidden('delete');
});

it('sends an email and creates a token when a true owner initiates a transfer', function () {
    Mail::fake();
    actingAs($this->owner);

    Livewire::test(ProjectSettings::class, ['tenant' => $this->project->subdomain])
        ->callAction('transfer', data: [
            'to_user_id' => $this->receiver->id,
        ])
        ->assertHasNoActionErrors();

    // Verify token generated
    $transfer = ProjectTransfer::where('project_id', $this->project->id)
        ->where('to_user_id', $this->receiver->id)
        ->first();

    expect($transfer)->not->toBeNull();

    // Verify Mail was queued/sent
    Mail::assertSent(ProjectTransferMail::class, function ($mail) use ($transfer) {
        return $mail->hasTo($this->receiver->email) &&
               $mail->transfer->id === $transfer->id;
    });
});

it('rejects transfer acceptance if user is logged out', function () {
    $transfer = ProjectTransfer::create([
        'project_id' => $this->project->id,
        'from_user_id' => $this->owner->id,
        'to_user_id' => $this->receiver->id,
        'token' => Str::random(64),
        'expires_at' => now()->addHours(48),
    ]);

    get(route('transfers.accept', ['token' => $transfer->token]))
        ->assertRedirect(route('filament.app.auth.login'))
        ->assertSessionHas('warning', 'Por favor, inicia sesión para aceptar la transferencia.');
});

it('rejects transfer acceptance if authenticated as a different user', function () {
    $transfer = ProjectTransfer::create([
        'project_id' => $this->project->id,
        'from_user_id' => $this->owner->id,
        'to_user_id' => $this->receiver->id,
        'token' => Str::random(64),
        'expires_at' => now()->addHours(48),
    ]);

    actingAs($this->outsider);

    get(route('transfers.accept', ['token' => $transfer->token]))
        ->assertRedirect(route('filament.app.auth.login'))
        ->assertSessionHas('warning');

    // Should be logged out
    expect(auth()->check())->toBeFalse();
});

it('successfully transfers ownership when accepted by the correct user', function () {
    $transfer = ProjectTransfer::create([
        'project_id' => $this->project->id,
        'from_user_id' => $this->owner->id,
        'to_user_id' => $this->receiver->id,
        'token' => Str::random(64),
        'expires_at' => now()->addHours(48),
    ]);

    actingAs($this->receiver);

    get(route('transfers.accept', ['token' => $transfer->token]))
        ->assertRedirect(route('filament.app.pages.dashboard', ['tenant' => $this->project->subdomain]))
        ->assertSessionHas('success');

    // Assert DB changed
    $this->project->refresh();
    expect($this->project->user_id)->toBe($this->receiver->id);
    expect(ProjectTransfer::count())->toBe(0); // Token consumed
});

it('rejects expired transfer links', function () {
    $transfer = ProjectTransfer::create([
        'project_id' => $this->project->id,
        'from_user_id' => $this->owner->id,
        'to_user_id' => $this->receiver->id,
        'token' => Str::random(64),
        'expires_at' => now()->subMinutes(1),
    ]);

    actingAs($this->receiver);

    get(route('transfers.accept', ['token' => $transfer->token]))
        ->assertRedirect('/')
        ->assertSessionHasErrors('transfer');
});
