<?php

namespace DigitalRisks\Lese\Tests\TestClasses;

use Spatie\EventSourcing\ShouldBeStored;

class MoneyMultiplied implements ShouldBeStored
{
    public int $amount;

    public function __construct(int $amount)
    {
        $this->amount = $amount;
    }
}
