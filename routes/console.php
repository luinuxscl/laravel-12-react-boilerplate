<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('user:create {--name=} {--email=} {--password=} {--role=}', function () {
    $name = (string) ($this->option('name') ?? 'Admin');
    $email = (string) ($this->option('email') ?? 'admin@example.com');
    $password = (string) ($this->option('password') ?? 'password');
    $role = (string) ($this->option('role') ?? 'Admin');

    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Invalid email. Use --email="user@example.com"');
        return 1;
    }

    // Ensure role exists (Spatie)
    $roleModel = Role::firstOrCreate(['name' => $role]);

    // Create or update the user
    $user = User::firstOrNew(['email' => $email]);
    $user->name = $name;
    if (! $user->exists) {
        $user->password = Hash::make($password);
    } elseif ($this->option('password')) {
        $user->password = Hash::make($password);
    }
    $user->email_verified_at = now();
    $user->save();

    // Assign role (idempotent)
    if (! $user->hasRole($roleModel->name)) {
        $user->assignRole($roleModel->name);
    }

    $this->info('User created/updated successfully.');
    $this->line("Email: {$user->email}");
    $this->line("Name:  {$user->name}");
    $this->line("Role:  {$roleModel->name}");
    if ($this->option('password')) {
        $this->line('Password: (updated)');
    } elseif (! $this->option('password') && ! $user->wasRecentlyCreated) {
        $this->line('Password: (unchanged)');
    } else {
        $this->line('Password: (generated/default)');
    }

    $this->comment('Login with the provided credentials at /login');
    return 0;
})->purpose('Create or update a user and assign a role (Spatie)');
