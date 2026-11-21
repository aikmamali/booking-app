<?php

declare(strict_types=1);

namespace App\Modules\Booking;

use App\Modules\Booking\Contracts\NoRequiredForms;
use App\Modules\Booking\Contracts\RequiredForms;
use App\Modules\Booking\Events\SeatFreed;
use App\Modules\Booking\Listeners\CancelBookingsWhenSessionCancelled;
use App\Modules\Booking\Listeners\CancelFutureBookingsOfDeletedUser;
use App\Modules\Booking\Listeners\PromoteWhenSeatFreed;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Policies\BookingPolicy;
use App\Modules\Booking\Rules\BookingRuleChain;
use App\Modules\Booking\Rules\HasCapacity;
use App\Modules\Booking\Rules\HasValidMembership;
use App\Modules\Booking\Rules\NotAlreadyBooked;
use App\Modules\Booking\Rules\RequiredFormsCompleted;
use App\Modules\Booking\Rules\SessionIsBookable;
use App\Modules\Booking\Rules\WithinActiveLimit;
use App\Modules\Booking\Rules\WithinDailyLimit;
use App\Modules\Booking\Support\SeatCounter;
use App\Modules\Identity\Events\UserDeleted;
use App\Modules\Scheduling\Contracts\SessionOccupancy;
use App\Modules\Scheduling\Events\SessionCancelled;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class BookingServiceProvider extends ServiceProvider
{
    /**
     * Booking rules in the order they run (spec section 4). A new rule is one line here.
     *
     * @var list<class-string<Rules\BookingRule>>
     */
    private const array RULES = [
        SessionIsBookable::class,
        NotAlreadyBooked::class,
        HasCapacity::class,
        WithinDailyLimit::class,
        WithinActiveLimit::class,
        HasValidMembership::class,
        RequiredFormsCompleted::class,
    ];

    public function register(): void
    {
        $this->app->bind(SessionOccupancy::class, SeatCounter::class);
        $this->app->bindIf(RequiredForms::class, NoRequiredForms::class);
        $this->app->bind(BookingRuleChain::class, fn ($app): BookingRuleChain => new BookingRuleChain(
            array_map(fn (string $rule) => $app->make($rule), self::RULES),
        ));
    }

    public function boot(): void
    {
        Gate::policy(Booking::class, BookingPolicy::class);

        Event::listen(SessionCancelled::class, CancelBookingsWhenSessionCancelled::class);
        Event::listen(UserDeleted::class, CancelFutureBookingsOfDeletedUser::class);
        Event::listen(SeatFreed::class, PromoteWhenSeatFreed::class);
    }
}
