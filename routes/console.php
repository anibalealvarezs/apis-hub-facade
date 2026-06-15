<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::command('projects:cleanup-deleted')->daily();
\Illuminate\Support\Facades\Schedule::command('billing:process-grace-periods')->everyFiveMinutes();
\Illuminate\Support\Facades\Schedule::command('billing:expire-pending-assignments')->daily();
\Illuminate\Support\Facades\Schedule::command('bcv:fetch')->everyFifteenMinutes();
