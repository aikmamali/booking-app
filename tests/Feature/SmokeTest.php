<?php

declare(strict_types=1);

it('redirects the root to the customer area', function (): void {
    $this->get('/')->assertRedirect('/app');
});

it('renders the customer home in Greek by default', function (): void {
    $this->get('/app')
        ->assertOk()
        ->assertSee('lang="el"', false)
        ->assertSee(__('app.home.heading'))
        ->assertSee('Καλώς ήρθες');
});

it('renders the customer home in English', function (): void {
    app()->setLocale('en');

    $this->get('/app')->assertOk()->assertSee('Welcome');
});

it('shows the staff login page of the admin panel', function (): void {
    $this->get('/admin/login')->assertOk();
});

it('sends guests from the admin panel to its login page', function (): void {
    $this->get('/admin')->assertRedirect('/admin/login');
});
