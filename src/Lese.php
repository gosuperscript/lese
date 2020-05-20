<?php

namespace DigitalRisks\Lese;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\Log;
use Spatie\EventSourcing\AggregateRoot;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Prooph\EventStore\EventData;
use Prooph\EventStore\RecordedEvent;
use Spatie\EventSourcing\StoredEvent;
use Spatie\SchemalessAttributes\SchemalessAttributes;
use Prooph\EventStore\ResolvedEvent;

class Lese
{
    protected $eventStoreUrl;

    public function __construct()
    {
        $url = parse_url(config('lese.http_url'));
        $url = "{$url['scheme']}://{$url['host']}:{$url['port']}/web/index.html#";

        $this->eventStoreUrl = $url;
    }

    public function recordedEventToStoredEvent(RecordedEvent $event)
    {
        $metaModel = new StubModel(['meta_data' => $event->metadata() ?: null]);

        return new StoredEvent([
            'id' => $event->eventNumber(),
            'event_properties' => $event->data(),
            'aggregate_uuid' => Str::after($event->eventStreamId(), '-'),
            'event_class' => $event->eventType(),
            'meta_data' => new SchemalessAttributes($metaModel, 'meta_data'),
            'created_at' => $event->created()->format(DateTimeInterface::ATOM),
        ]);
    }

    public function eventDataToStoredEvent(EventData $event, string $uuid, int $number)
    {
        $metaModel = new StubModel(['meta_data' => $event->metadata() ?: null]);

        return new StoredEvent([
            'id' => $number,
            'event_properties' => $event->data(),
            'aggregate_uuid' => $uuid,
            'event_class' => $event->eventType(),
            'meta_data' => new SchemalessAttributes($metaModel, 'meta_data'),
            'created_at' => Carbon::now(),
        ]);
    }

    public function onEventReceived(ResolvedEvent $event)
    {
        return $this->logEvent($event, 'Received');
    }

    public function onEventProcessed(ResolvedEvent $event)
    {
        return $this->logEvent($event, 'Processed');
    }

    public function onEventFailed(ResolvedEvent $event)
    {
        return $this->logEvent($event, 'Failed');
    }

    protected function logEvent(ResolvedEvent $event, $state)
    {
        $event = $event->event();

        $context = ['type' => $event->eventType()];

        Log::info("{$state} {$this->eventStoreUrl}/streams/{$event->eventStreamId()}/{$event->eventNumber()}", $context);
    }

    public function shouldSkipEvent(ResolvedEvent $event)
    {
        $type = $event->event()->eventType();

        return Str::startsWith($type, '$');
    }

    public function aggregateToStream(AggregateRoot $aggregate, string $uuid)
    {
        return $this->aggregateToCategory($aggregate) . '-' . $uuid;
    }

    public function aggregateToSnapshotStream(AggregateRoot $aggregate, string $uuid)
    {
        return '$' . $this->aggregateToCategory($aggregate) . '-' . $uuid . '-snapshot';
    }

    public function aggregateToCategory(AggregateRoot $aggregate)
    {
        $class = get_class($aggregate);

        if ($map = config('lese.aggregate_category_map.' . $class)) {
            return $map;
        }

        $base = class_basename($class);
        $category = Str::replaceLast('Aggregate', '', $base);

        return Str::snake($category);
    }
}
