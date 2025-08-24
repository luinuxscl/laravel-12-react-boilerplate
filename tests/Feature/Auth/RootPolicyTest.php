<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeUserWithRole(?string $role = null): User {
    /** @var User $user */
    $user = User::factory()->create();
    if ($role) {
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);
    }
    return $user;
}

it('admin cannot manage a root user', function () {
    $admin = makeUserWithRole('admin');
    $root = makeUserWithRole('root');

    expect($admin->can('view', $root))->toBeFalse();
    expect($admin->can('update', $root))->toBeFalse();
    expect($admin->can('delete', $root))->toBeFalse();
});

it('root can manage a root user', function () {
    $actor = makeUserWithRole('root');
    $target = makeUserWithRole('root');

    expect($actor->can('view', $target))->toBeTrue();
    expect($actor->can('update', $target))->toBeTrue();
    expect($actor->can('delete', $target))->toBeTrue();
});

it('admin can manage non-root users', function () {
    $admin = makeUserWithRole('admin');
    $user = makeUserWithRole(); // sin rol

    expect($admin->can('view', $user))->toBeTrue();
    expect($admin->can('update', $user))->toBeTrue();
    expect($admin->can('delete', $user))->toBeTrue();
});

it('regular user cannot viewAny but can view self', function () {
    $user = makeUserWithRole();

    // viewAny requiere admin/root
    expect($user->can('viewAny', User::class))->toBeFalse();

    // puede ver su propio perfil
    expect($user->can('view', $user))->toBeTrue();
});
