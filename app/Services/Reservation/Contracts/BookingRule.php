<?php

namespace App\Services\Reservation\Contracts;


interface BookingRule
{
    public function validate(BookingContext $context): void;
}
