<?php

declare(strict_types=1);

use App\Modules\Billing\Actions\RecordPayment;
use App\Modules\Billing\Data\PaymentData;
use App\Modules\Billing\Enums\PaymentMethod;
use App\Modules\Billing\Enums\PaymentStatus;
use App\Modules\Billing\Events\PaymentSucceeded;
use App\Modules\Billing\Exceptions\PaymentRejected;
use App\Modules\Billing\Filament\Resources\PaymentResource\Pages\ListPayments;
use App\Modules\Billing\Models\Payment;
use App\Modules\Catalog\Models\Service;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Actions\SellPackage;
use App\Modules\Membership\Data\SaleData;
use App\Modules\Membership\Models\Package;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(fn () => $this->travelTo('2027-03-20 08:00:00'));

it('records the payment of a package sold at the desk', function (): void {
    Event::fake([PaymentSucceeded::class]);
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->customer()->create();
    $package = Package::factory()->create(['price_cents' => 7550]);
    $package->services()->sync([Service::factory()->create()->id]);

    $membership = app(SellPackage::class)->handle(new SaleData($customer->id, $package->id, '2027-03-20', PaymentMethod::Pos), $admin->id);

    $payment = Payment::query()->sole();
    expect($payment->user_id)->toBe($customer->id)
        ->and($payment->amount_cents)->toBe(7550)
        ->and($payment->method)->toBe(PaymentMethod::Pos)
        ->and($payment->status)->toBe(PaymentStatus::Succeeded)
        ->and($payment->payable_type)->toBe('membership')
        ->and($payment->payable_id)->toBe($membership->id)
        ->and($payment->recorded_by)->toBe($admin->id)
        ->and($payment->paid_at?->toDateTimeString())->toBe('2027-03-20 08:00:00');
    Event::assertDispatched(PaymentSucceeded::class);
});

it('records nothing for a free package', function (): void {
    soldMembership(package: ['price_cents' => 0]);

    expect(Payment::query()->count())->toBe(0);
});

it('rejects a payment without an amount', function (): void {
    app(RecordPayment::class)->handle(new PaymentData(User::factory()->customer()->create()->id, 0, PaymentMethod::Cash));
})->throws(PaymentRejected::class, 'billing.errors.amount');

describe('admin', function (): void {
    beforeEach(fn () => $this->actingAs($this->admin = User::factory()->admin()->create()));

    it('records a payment by hand', function (): void {
        $customer = User::factory()->customer()->create();

        Livewire::test(ListPayments::class)
            ->callAction('record', ['user_id' => $customer->id, 'amount' => '12.50', 'method' => 'transfer', 'note' => 'Έμβασμα Alpha'])
            ->assertHasNoActionErrors()
            ->assertNotified(__('billing.notices.recorded'));

        $payment = Payment::query()->sole();
        expect($payment->amount_cents)->toBe(1250)
            ->and($payment->method)->toBe(PaymentMethod::Transfer)
            ->and($payment->note)->toBe('Έμβασμα Alpha')
            ->and($payment->recorded_by)->toBe($this->admin->id);
    });

    it('lists payments of a period with their total', function (): void {
        $customer = User::factory()->customer()->create();
        $record = fn (int $cents) => app(RecordPayment::class)->handle(new PaymentData($customer->id, $cents, PaymentMethod::Cash));
        $march = [$record(5000), $record(2550)];
        $this->travelTo('2027-04-02 08:00:00');
        $april = $record(1000);

        Livewire::test(ListPayments::class)
            ->filterTable('period', ['from' => '2027-03-01', 'until' => '2027-03-31'])
            ->assertCanSeeTableRecords($march)
            ->assertCanNotSeeTableRecords([$april])
            ->assertSee('75,50');
    });
});

it('keeps customers out of payments', function (): void {
    $this->actingAs(User::factory()->customer()->create())->get('/admin/payments')->assertForbidden();
});
