<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('admin:grant {email}', function (string $email) {
    $user = User::where('email', strtolower(trim($email)))->firstOrFail();
    $user->update(['role' => 'admin']);
    $this->info("Admin access granted to {$user->email}.");
})->purpose('Grant admin access to an existing user');

Schedule::command('subscriptions:expire')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('payments:reconcile')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('subscriptions:remind')->dailyAt('09:00')->withoutOverlapping();
