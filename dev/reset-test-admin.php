<?php

declare(strict_types=1);
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Contracts\Console\Kernel;

/**
 * Dev-site helper: makes sure a local test administrator (admin@example.test) exists on the teme-dev site
 * and gives it a new throwaway password, printed here. Run from anywhere: php dev/reset-test-admin.php
 * Only ever for the local development site.
 */
$site = dirname(__DIR__, 2).'/teme-dev';

require $site.'/vendor/autoload.php';

$app = require $site.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

(new RoleSeeder)->run();

$password = 'Dev-'.bin2hex(random_bytes(9)).'-Test1!';
$user = User::query()->where('email', 'admin@example.test')->first()
    ?? tap(User::factory()->create(['email' => 'admin@example.test']))->assignRole('administrator');

$user->forceFill(['password' => $app->make('hash')->make($password), 'email_verified_at' => now()])->save();

echo "Sign in at /admin/login\n  email:    {$user->email}\n  password: {$password}\n";
