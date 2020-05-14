<?php

namespace DigitalRisks\Lese;

use Illuminate\Support\LazyCollection;
use Prooph\EventStore\EventData;
use Spatie\EventSourcing\ShouldBeStored;
use Spatie\EventSourcing\StoredEvent;
use Spatie\EventSourcing\StoredEventRepository;

class EventStoreStoredEvent extends EventData
{

}
