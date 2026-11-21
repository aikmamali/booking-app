<?php

declare(strict_types=1);

namespace App\Modules\Billing\Actions;

use App\Modules\Billing\Data\PaymentData;
use App\Modules\Billing\Enums\PaymentStatus;
use App\Modules\Billing\Events\PaymentSucceeded;
use App\Modules\Billing\Exceptions\PaymentRejected;
use App\Modules\Billing\Models\Payment;
use App\Shared\Clock;
use Illuminate\Support\Facades\DB;

/**
 * BL-01: records money collected at the desk (cash, POS, bank transfer). Online card
 * payments arrive from Stripe in phase 2.
 */
final readonly class RecordPayment
{
    public function __construct(private Clock $clock) {}

    public function handle(PaymentData $data): Payment
    {
        if ($data->amount_cents <= 0) {
            throw PaymentRejected::because('billing.errors.amount');
        }

        return DB::transaction(function () use ($data): Payment {
            $payment = Payment::query()->create([
                'user_id' => $data->user_id,
                'payable_type' => $data->payable_type,
                'payable_id' => $data->payable_id,
                'amount_cents' => $data->amount_cents,
                'method' => $data->method,
                'status' => PaymentStatus::Succeeded,
                'note' => $data->note === null ? null : trim($data->note),
                'recorded_by' => $data->recorded_by,
                'paid_at' => $this->clock->now(),
            ]);

            event(new PaymentSucceeded($payment->id));

            return $payment;
        });
    }
}
