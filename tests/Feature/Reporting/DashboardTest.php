<?php

declare(strict_types=1);

use App\Modules\Billing\Actions\RecordPayment;
use App\Modules\Billing\Contracts\BillingStats;
use App\Modules\Billing\Data\PaymentData;
use App\Modules\Billing\Enums\PaymentMethod;
use App\Modules\Billing\Filament\Resources\PaymentResource\Pages\ListPayments;
use App\Modules\Booking\Actions\MarkAttendance;
use App\Modules\Booking\Contracts\BookingStats;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Contracts\MembershipStats;
use App\Modules\Reporting\Filament\Widgets\KeyFigures;
use App\Modules\Reporting\Filament\Widgets\TodaySessions;
use Carbon\CarbonImmutable;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Models\Export;
use Livewire\Livewire;

beforeEach(fn () => $this->travelTo('2027-03-20 08:00:00'));

it('sums the revenue of a period', function (): void {
    $customer = User::factory()->customer()->create();
    app(RecordPayment::class)->handle(new PaymentData($customer->id, 5000, PaymentMethod::Cash));
    app(RecordPayment::class)->handle(new PaymentData($customer->id, 2550, PaymentMethod::Pos));
    $this->travelTo('2027-04-01 08:00:00');
    app(RecordPayment::class)->handle(new PaymentData($customer->id, 9999, PaymentMethod::Cash));

    expect(app(BillingStats::class)->revenue(CarbonImmutable::parse('2027-03-01'), CarbonImmutable::parse('2027-04-01'))->cents)->toBe(7550);
});

it('counts active memberships and those ending this week', function (): void {
    soldMembership(package: ['validity_days' => 2]);   // ends 21 March
    soldMembership(package: ['validity_days' => 30]);
    soldMembership(startsOn: '2027-03-25');             // not started yet

    expect(app(MembershipStats::class)->activeOn('2027-03-20'))->toBe(2)
        ->and(app(MembershipStats::class)->endingBetween('2027-03-20', '2027-03-21'))->toBe(1);
});

it('reports occupancy per session and the no-show rate', function (): void {
    $session = bookableSession(['capacity' => 4]);
    $a = book(customerFor($session), $session);
    $b = book(customerFor($session), $session);
    $this->travelTo('2027-03-23 16:40:00');
    app(MarkAttendance::class)->handle($a, BookingStatus::Attended);
    app(MarkAttendance::class)->handle($b, BookingStatus::NoShow);

    $rows = app(BookingStats::class)->sessions(CarbonImmutable::parse('2027-03-23'), CarbonImmutable::parse('2027-03-24'));
    expect($rows)->toHaveCount(1)
        ->and($rows[0]->booked)->toBe(2)
        ->and($rows[0]->percent())->toBe(50)
        ->and(app(BookingStats::class)->noShowRate(CarbonImmutable::parse('2027-03-01'), CarbonImmutable::parse('2027-04-01')))->toBe(0.5)
        ->and(app(BookingStats::class)->noShowRate(CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2026-02-01')))->toBeNull();
});

it('shows the key figures and today\'s classes on the dashboard', function (): void {
    $this->actingAs(User::factory()->admin()->create());
    $session = bookableSession(['starts_at' => '2027-03-20 16:00:00', 'ends_at' => '2027-03-20 17:00:00', 'capacity' => 5]);
    book(customerFor($session), $session);

    $this->get('/admin')->assertOk()->assertSeeLivewire(KeyFigures::class)->assertSeeLivewire(TodaySessions::class);

    Livewire::test(KeyFigures::class)->assertSee(__('reporting.revenue_month'))->assertSee('150,00');
    Livewire::test(TodaySessions::class)->assertSee('18:00')->assertSee('1/5 · 20%');
});

it('exports a list to Excel', function (): void {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    app(RecordPayment::class)->handle(new PaymentData(User::factory()->customer()->create()->id, 5000, PaymentMethod::Cash));

    Livewire::test(ListPayments::class)->callAction(ExportAction::class, ['xlsx' => true, 'csv' => false]);

    $export = Export::query()->sole();
    expect($export->user_id)->toBe($admin->id)
        ->and($export->successful_rows)->toBe(1)
        ->and($export->completed_at)->not->toBeNull();
});
