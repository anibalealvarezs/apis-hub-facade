<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class SetSessionStartTime
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        session()->put('session_start_time', time());
    }
}
