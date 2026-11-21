<?php

declare(strict_types=1);

use App\Modules\Communication\Filament\Resources\AnnouncementResource\Pages\ManageAnnouncements;
use App\Modules\Communication\Models\Announcement;
use App\Modules\Identity\Models\User;
use Filament\Actions\CreateAction;
use Livewire\Livewire;

beforeEach(fn () => $this->travelTo('2027-03-20 08:00:00'));

it('lets staff publish announcements between two dates', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ManageAnnouncements::class)
        ->callAction(CreateAction::class, [
            'title' => ['el' => 'Νέο πρόγραμμα Πάσχα', 'en' => 'Easter timetable'],
            'body' => ['el' => 'Δείτε τις αλλαγές.'],
            'published_from' => '2027-03-20 00:00',
            'published_until' => '2027-04-20 00:00',
        ])
        ->assertHasNoActionErrors();

    expect(Announcement::query()->sole()->getTranslations('title'))->toBe(['el' => 'Νέο πρόγραμμα Πάσχα', 'en' => 'Easter timetable']);
});

it('shows only current announcements on the customer home', function (): void {
    Announcement::query()->create(['title' => ['el' => 'Τρέχουσα'], 'published_from' => '2027-03-19 00:00:00', 'published_until' => '2027-03-21 00:00:00']);
    Announcement::query()->create(['title' => ['el' => 'Μελλοντική'], 'published_from' => '2027-03-25 00:00:00']);
    Announcement::query()->create(['title' => ['el' => 'Παλιά'], 'published_from' => '2027-03-01 00:00:00', 'published_until' => '2027-03-10 00:00:00']);

    $this->actingAs(User::factory()->customer()->create())->get(route('app.home'))
        ->assertOk()
        ->assertSee('Τρέχουσα')
        ->assertDontSee('Μελλοντική')
        ->assertDontSee('Παλιά');
});

it('keeps customers out of the communication admin', function (string $url): void {
    $this->actingAs(User::factory()->customer()->create())->get($url)->assertForbidden();
})->with(['/admin/announcements', '/admin/email-templates']);
