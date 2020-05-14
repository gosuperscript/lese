<?php

namespace DigitalRisks\Lese\Tests\TestClasses;

use DigitalRisks\Lese\EventStoreAggregateRoot;
use DigitalRisks\Lese\EventStoreStoredEventRepository;
use Spatie\EventSourcing\AggregateRoot;
use Spatie\EventSourcing\Snapshots\EloquentSnapshotRepository;
use Spatie\EventSourcing\StoredEventRepository;

class AccountAggregateRoot extends AggregateRoot
{
    public int $balance = 0;

    public int $aggregateVersion = 0;

    public int $aggregateVersionAfterReconstitution = 0;

    public string $snapshotRepository = EloquentSnapshotRepository::class;

    protected function getStoredEventRepository(): StoredEventRepository
    {
        return new EventStoreStoredEventRepository('account');
    }

    public function addMoney(int $amount): self
    {
        $this->recordThat(new MoneyAdded($amount));

        return $this;
    }

    public function multiplyMoney(int $amount): self
    {
        $this->recordThat(new MoneyMultiplied($amount));

        return $this;
    }

    public function applyMoneyAdded(MoneyAdded $event)
    {
        $this->balance += $event->amount;
    }

    public function applyMoneyMultiplied(MoneyMultiplied $event)
    {
        $this->balance *= $event->amount;
    }
}
