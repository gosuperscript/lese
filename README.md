# Laravel Event Sourcing and Eventstore (lese) Bridge

Or German for `read` which is somewhat applicable to Event Sourcing. It's almost a good name.

This package swaps out the Event and Snapshot storage model for [Laravel Event Souring](https://docs.spatie.be/laravel-event-sourcing/v1/getting-familiar-with-event-sourcing/introduction) with [EventStore](https://eventstore.com/). 

In addition it provides a console command to subscribe to persistent subscriptions to fire events in your application that are from other systems. This can be useful in a microservice architecture.



## @todo

* [ ] Callbacks for received, processed, failed
* [ ] Support reads larger than 4096 events (use yield?)
* [ ] Support reading from $all stream or a user stream
* [ ] Ignore events
* [ ] Metadata callbacks
* [ ] Documentation
* [x] Callbacks for aggregate stream, aggregate snapshots
* [x] Refactor ResolvedEvent to StoredEvent
* [x] Use env connection settings
* [x] persist to persistMany instead of other way round
