<?php

namespace App\Livewire;

use Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;

class CustomPersonalInfo extends PersonalInfo
{
    public function submit(): void
    {
        $data = collect($this->form->getState())->only($this->only)->all();
        $user = $this->user;

        $emailChanged = isset($data['email']) && $data['email'] !== $user->email;

        if ($emailChanged) {
            $newEmail = $data['email'];
            unset($data['email']); // Remove it from the immediate update payload

            // Save the pending email
            $user->pending_email = $newEmail;
            $user->save();

            // Send verification email to the new address
            $user->notify(new \App\Notifications\VerifyPendingEmail($newEmail));

            Notification::make()
                ->title('Email Verification Required')
                ->body("A verification link has been sent to {$newEmail}. Please verify it to complete the change.")
                ->warning()
                ->send();
        }

        // Update the rest of the data (e.g. name)
        if (!empty($data)) {
            $user->update($data);
        }

        if (!$emailChanged) {
            $this->sendNotification();
        }
    }
}
