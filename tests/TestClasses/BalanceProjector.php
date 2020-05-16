<?php

namespace DigitalRisks\Lese\Tests\TestClasses;

use Spatie\EventSourcing\Projectors\Projector;
use Spatie\EventSourcing\Projectors\ProjectsEvents;

class BalanceProjector implements Projector
{
    use ProjectsEvents;

    protected array $handlesEvents = [
        MoneyAddedEvent::class => 'onMoneyAdded',
    ];

    public function onMoneyAdded(MoneyAddedEvent $event)
    {
    }
}
