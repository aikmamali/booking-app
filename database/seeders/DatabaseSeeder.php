<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Billing\Enums\PaymentMethod;
use App\Modules\Booking\Actions\BookSession;
use App\Modules\Booking\Data\BookingRequest;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Catalog\Models\Service;
use App\Modules\Catalog\Models\ServiceCategory;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Actions\SavePackage;
use App\Modules\Membership\Actions\SellPackage;
use App\Modules\Membership\Data\PackageData;
use App\Modules\Membership\Data\SaleData;
use App\Modules\Scheduling\Actions\GenerateSessions;
use App\Modules\Scheduling\Enums\Weekday;
use App\Modules\Scheduling\Models\ClassSession;
use App\Modules\Scheduling\Models\Schedule;
use App\Shared\Credits;
use Illuminate\Database\Seeder;

/**
 * Demo data for local and staging (spec section 10.4): 3 services, a weekly schedule and
 * 20 customers. Never run in production.
 */
final class DatabaseSeeder extends Seeder
{
    public function run(GenerateSessions $generateSessions): void
    {
        User::factory()->admin()->create([
            'first_name' => 'Διαχειριστής', 'last_name' => 'Demo', 'email' => 'admin@booking.test', 'phone' => null,
        ]);
        /** @var list<int> $instructorIds */
        $instructorIds = User::factory()->instructor()->count(2)->create()->modelKeys();
        User::factory()->customer()->create(['first_name' => 'Πελάτης', 'last_name' => 'Demo', 'email' => 'customer@booking.test']);
        User::factory()->customer()->count(19)->create();

        $category = ServiceCategory::factory()->create();
        $services = [
            $this->service($category, 'Group Training', 1, 60, 5, '1', 1500),
            $this->service($category, 'Mobility', 2, 45, 6, '0.5', null),
            $this->service($category, 'Pilates Reformer', 3, 50, 4, '1', 2000),
        ];

        $slots = [
            [Weekday::Monday, '09:00', 0], [Weekday::Monday, '18:30', 0], [Weekday::Tuesday, '19:00', 1],
            [Weekday::Wednesday, '09:00', 0], [Weekday::Wednesday, '18:30', 2], [Weekday::Thursday, '19:00', 1],
            [Weekday::Friday, '18:30', 0], [Weekday::Saturday, '10:00', 2],
        ];

        foreach ($slots as $i => [$day, $time, $serviceIndex]) {
            $service = $services[$serviceIndex];
            Schedule::query()->create([
                'service_id' => $service->id, 'instructor_id' => $instructorIds[$i % 2], 'weekday' => $day,
                'start_time' => $time, 'capacity' => $service->default_capacity, 'valid_from' => now()->toDateString(),
            ]);
        }

        $generateSessions->handle();

        $this->packagesAndSales(array_map(fn (Service $s): int => $s->id, $services));
    }

    /**
     * @param  list<int>  $serviceIds
     */
    private function packagesAndSales(array $serviceIds): void
    {
        $save = app(SavePackage::class);
        $ten = $save->handle(null, new PackageData(['el' => '10 μαθήματα', 'en' => '10 classes'], Credits::of(10), 90, 15000, 24, true, true, $serviceIds));
        $save->handle(null, new PackageData(['el' => '4 μαθήματα', 'en' => '4 classes'], Credits::of(4), 30, 7000, 24, true, true, $serviceIds));

        $sell = app(SellPackage::class);
        $customers = User::role('customer')->orderBy('id')->limit(10)->pluck('id');

        foreach ($customers as $customerId) {
            $sell->handle(new SaleData($customerId, $ten->id, now()->toDateString(), PaymentMethod::Cash));
        }

        $this->bookings($customers->all());
    }

    /**
     * A few bookings in the coming days so the calendar shows taken seats.
     *
     * @param  array<int, int>  $customerIds
     */
    private function bookings(array $customerIds): void
    {
        $book = app(BookSession::class);
        $sessions = ClassSession::query()->where('starts_at', '>', now()->addHours(4))->orderBy('starts_at')->limit(6)->get();

        foreach ($sessions as $i => $session) {
            foreach (array_slice($customerIds, $i, 3) as $customerId) {
                try {
                    $book->handle(new BookingRequest(user_id: $customerId, session_id: $session->id));
                } catch (BookingRejected) {
                    // daily limit or full: fine for demo data
                }
            }
        }
    }

    private function service(ServiceCategory $category, string $name, int $position, int $minutes, int $seats, string $credits, ?int $priceCents): Service
    {
        return Service::factory()->create([
            'category_id' => $category->id, 'name' => ['el' => $name, 'en' => $name], 'position' => $position,
            'duration_minutes' => $minutes, 'default_capacity' => $seats, 'credit_cost' => Credits::of($credits),
            'drop_in_price_cents' => $priceCents,
        ]);
    }
}
