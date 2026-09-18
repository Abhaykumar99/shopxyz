<?php

namespace App\Enums;

/**
 * Why a delivery could not be completed (ADR-020). The reason is shown to the
 * customer on their order page, so the wording is written for them.
 */
enum DeliveryFailureReason: string
{
    case NobodyHome = 'nobody_home';
    case NotReachable = 'not_reachable';
    case Refused = 'refused';
    case AddressWrong = 'address_wrong';
    case NoCash = 'no_cash';
    case Rescheduled = 'rescheduled';

    /**
     * What the delivery boy picks.
     */
    public function label(): string
    {
        return match ($this) {
            self::NobodyHome => 'Nobody at the address',
            self::NotReachable => 'Customer did not answer the phone',
            self::Refused => 'Customer refused the order',
            self::AddressWrong => 'Address is wrong or cannot be found',
            self::NoCash => 'Customer did not have the cash',
            self::Rescheduled => 'Customer asked for another time',
        };
    }

    /**
     * What the customer reads on their order.
     */
    public function customerMessage(): string
    {
        return match ($this) {
            self::NobodyHome => 'Nobody was home. We will call you to arrange another time.',
            self::NotReachable => 'We could not reach you on the phone. We will try again and call you.',
            self::Refused => 'The order was refused at the door. We will call you about it.',
            self::AddressWrong => 'We could not find the address. Please check it and we will deliver again.',
            self::NoCash => 'The cash was not ready at delivery. We will call you to arrange payment.',
            self::Rescheduled => 'You asked us to come another time. We will call to confirm when.',
        };
    }

    /**
     * A note from the delivery boy is required when the reason alone is not enough.
     */
    public function needsNote(): bool
    {
        return $this === self::AddressWrong || $this === self::Rescheduled;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $reason): array => [$reason->value => $reason->label()])
            ->all();
    }
}
