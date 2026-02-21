<?php

use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'company_name' => 'Acme Corp',
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->current_tenant_id)->not->toBeNull();

    $tenant = Tenant::withoutGlobalScopes()->find($user->current_tenant_id);

    expect($tenant)->not->toBeNull()
        ->and($tenant->name)->toBe('Acme Corp')
        ->and($tenant->slug)->toBe('acme-corp');

    expect($tenant->users)->toHaveCount(1);
    expect($tenant->users->first()->pivot->role)->toBe('tenant_owner');
    expect($tenant->users->first()->pivot->is_default)->toBeTruthy();

    expect($user->hasRole('tenant_owner'))->toBeTrue();
});

test('registration requires company name', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('company_name');
});

test('registration generates unique tenant slugs', function () {
    Tenant::withoutGlobalScopes()->create([
        'name' => 'Acme Corp',
        'slug' => 'acme-corp',
    ]);

    $this->post(route('register.store'), [
        'company_name' => 'Acme Corp',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'jane@example.com')->first();
    $tenant = Tenant::withoutGlobalScopes()->find($user->current_tenant_id);

    expect($tenant->slug)->toBe('acme-corp-1');
});
