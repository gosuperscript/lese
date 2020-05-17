<?php

namespace DigitalRisks\Lese\Tests;

use DigitalRisks\Lese\EventStoreSnapshotRepository;
use DigitalRisks\Lese\EventStoreStoredEventRepository;
use DigitalRisks\Lese\ServiceProvider;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Schema;
use Spatie\EventSourcing\EventSourcingServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use WithFaker;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('snapshots');
        include_once __DIR__ . '/../vendor/spatie/laravel-event-sourcing/stubs/create_snapshots_table.php.stub';
        (new \CreateSnapshotsTable())->up();
    }

    protected function getPackageProviders($app)
    {
        return [
            EventSourcingServiceProvider::class,
            ServiceProvider::class,
        ];
    }
}
