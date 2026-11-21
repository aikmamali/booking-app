<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Locale;
use App\Modules\Identity\Models\User;

it('redirects the root to the customer area', function (): void {
    $this->get('/')->assertRedirect('/app');
});

it('sends guests to the login page', function (): void {
    $this->get('/app')->assertRedirect(route('app.login'));
});

it('renders the login page in Greek by default', function (): void {
    $this->get(route('app.login'))
        ->assertOk()
        ->assertSee('lang="el"', false)
        ->assertSee('Σύνδεση');
});

it('renders the customer home in the user language', function (Locale $locale, string $heading): void {
    $user = User::factory()->customer()->create(['locale' => $locale]);

    $this->actingAs($user)->get('/app')
        ->assertOk()
        ->assertSee('lang="'.$locale->value.'"', false)
        ->assertSee($heading);
})->with([
    [Locale::Greek, 'Καλώς ήρθες'],
    [Locale::English, 'Welcome'],
]);

it('shows the draft legal pages to guests', function (string $route): void {
    $this->get(route($route))->assertOk()->assertSee('2026-11-draft')->assertSee('Προσχέδιο');
})->with(['app.terms', 'app.privacy']);

it('shows the staff login page of the admin panel', function (): void {
    $this->get('/admin/login')->assertOk();
});

it('sends guests from the admin panel to its login page', function (): void {
    $this->get('/admin')->assertRedirect('/admin/login');
});
