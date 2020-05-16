<?php

namespace DigitalRisks\Lese\Tests\TestClasses;

use DigitalRisks\Lese\Tests\Account;
use Spatie\EventSourcing\ShouldBeStored;

class MoneyAddedEvent implements ShouldBeStored
{
    public int $amount;

    public function __construct(int $amount)
    {
        $this->amount = $amount;
    }
}
