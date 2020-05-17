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

    protected function getStoredEventRepository(): StoredEventRepository
    {
        return resolve(EventStoreStoredEventRepository::class, ['aggregate' => $this]);
    }

    protected function getSnapshotRepository(): SnapshotRepository
    {
        return resolve(EventStoreSnapshotRepository::class, ['aggregate' => $this]);
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
        $this->recordThat(new MoneyAddedEvent($amount));

        return $this;
    }

    public function multiplyMoney(int $amount): self
    {
        $this->recordThat(new MoneyMultipliedEvent($amount));

        return $this;
    }

    public function applyMoneyAddedEvent(MoneyAddedEvent $event)
    {
        $this->balance += $event->amount;
    }

    public function applyMoneyMultipliedEvent(MoneyMultipliedEvent $event)
    {
        $this->balance *= $event->amount;
    }
}
