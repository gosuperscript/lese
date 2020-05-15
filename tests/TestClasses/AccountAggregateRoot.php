<?php

namespace DigitalRisks\Lese\Tests\TestClasses;

use DigitalRisks\Lese\EventStoreAggregateRoot;
use DigitalRisks\Lese\EventStoreStoredEventRepository;
use Spatie\EventSourcing\AggregateRoot;
use Spatie\EventSourcing\Snapshots\EloquentSnapshotRepository;
use Spatie\EventSourcing\Snapshots\SnapshotRepository;
use Spatie\EventSourcing\StoredEventRepository;
use DigitalRisks\Lese\EventStoreSnapshotRepository;
use ReflectionClass;
use ReflectionProperty;

class AccountAggregateRoot extends AggregateRoot
{
    public int $balance = 0;

    protected int $aggregateVersion = 0;

    protected int $aggregateVersionAfterReconstitution = 0;

    protected string $snapshotRepository = EloquentSnapshotRepository::class;

    protected function getStoredEventRepository(): StoredEventRepository
    {
        return new EventStoreStoredEventRepository('account');
    }

    protected function getSnapshotRepository(): SnapshotRepository
    {
        return new EventStoreSnapshotRepository('account');
    }

    protected function getState(): array
    {
        $class = new ReflectionClass($this);

        return collect($class->getProperties(ReflectionProperty::IS_PUBLIC))
            ->reject(fn (ReflectionProperty $reflectionProperty) => $reflectionProperty->isStatic())
            ->mapWithKeys(function (ReflectionProperty $property) {
                return [$property->getName() => $this->{$property->getName()}];
            })->toArray();
    }

    public function aggregateVersion()
    {
        return $this->aggregateVersion;
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
