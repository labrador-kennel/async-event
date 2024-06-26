# Changelog

## v4.1.0

#### Added 

- Added the `Labrador\AsyncEvent\EventName` interface to allow for enums and other 
objects to be used as event names when appropriate

#### Changed

- Updated the `Labrador\AsyncEvent\Emitter::register()` and `Emitter::listeners` 
methods to accept a `non-empty-string|EventName`.
- Added proper template types to the `Emitter::emit` and `Emitter::queue` methods

## v4.0.0

#### Added

- Added the `Labrador\AsyncEvent\ListenerRemovableBasedOnHandleCount`
- Added template parameters where appropriate to more properly convey typing information
- Added a `Labrador\AsyncEvent\AbstractEvent` to more easily create domain events out of the box.
- Added a `Labrador\AsyncEvent\FinishedNotifier` to represent when a queued event has finished being emitted

#### Changed

- Renamed `Labrador\AsyncEvent\EventEmitter` -> `Labrador\AsyncEvent\Emitter`
- Renamed `Labrador\AsyncEvent\AmpEventEmitter` -> `Labrador\AsyncEvent\AmpEmitter`
- Renamed `Labrador\AsyncEvent\Event::target()` -> `Labrador\AsyncEvent\Event::payload()`
- Changed the `Emitter::register(Listener)` method signature to `Emitter::register(string, Listener)` where the 
first argument is the event name to register.
- Changed the `Emitter::queue(Event) : void` method signature to `Emitter::queue(Event) : FinishedNotifier` to allow
doing something when a queued event is finished being emitted without potentially blocking, negating the intent of a
queued fire & forget event.

#### Removed

- Removed the `Labrador\AsyncEvent\OneTimeListener`. Implement the `Labrador\AsyncEvent\ListenerRemovableBasedOnHandleCount` interface to 
replicate this functionality.
- Removed the `Labrador\AsyncEvent\ListenerProvider` interface and implementations. 
- Removed the `Labrador\AsyncEvent\Listener::setRegistration` method. Registration removal and association to the listener
is handled internally without leaking this detail to listeners.


## v3.0.0

#### Removed

- Removed `cspray/annotated-container` library. The functionality previously provided by the implementations using this
library are available at `labrador-kennel/async-event-autowire`

## v3.0.0-beta1

#### Added

- Support for Amp 3.0 and Fibers.
- Adds the `labrador-kennel/composite-future` library for handling a collection of Futures.
- Adds support for integrating with `cspray/annotated-container` to handle dependency injection and autowiring of listeners.
- Adds an explicit `EventEmitter::queue(Event $event) : void` method for when you want to "fire-and-forget" events. Queued events will be emitted on the next tick of the event loop.

#### Changed

- Renamed the Composer package from `cspray/labrador-async-event` to `labrador-kennel/async-event`.
- The emitter's emit method signature has been changed to: `EventEmitter::emit(Event $event) : CompositeFuture`. 
- The emitter's register method signature has been changed from `Eventemitter::on(string $event, callable $listener, array $data = [])` to `EventEmitter::register(Listener $listener) : ListenerRegistration`
- Type hints in object properties and other places not allowed before 8.0 were added.
- Listeners must now implement a `Cspray\Labrador\AsyncEvent\Listener` interface. Plain callables are no longer supported.
- EventEmitter no longer has an explicit `off()` method for removing Listeners. Instead `ListenerRegistration::remove()` allows for removal of the listener.

#### Removed

- Removed PHP 7 support. This library now only supports PHP 8.1+.
- Removed the concept of a `PromiseCombinator` and all corresponding methods.
- Removed cspray/yape as enums are not necessary without the `PromiseCombinator`.
- Removed the `EventEmitter::off` method. Please see `ListenerRegistration::remove` for details on how to remove a Listener.

## v2.2.0 - 2020-05-02

#### Changed

- Updates the cspray/yape dependency to 3.0+ and updates existing enums to match generated enums.

## v2.1.0 - 2020-04-26

#### Changed

- Updates the cspray/yape dependency to use a 2.0+ generated enum.

## v2.0.0 - 2020-04-26

**This has many breaking changes when coming from previous versions. Please review CHANGELOG closely!**

#### Removed

- Removes the requirement for the ext-ds extension. This was done to conform with the rest of 
Labrador Kennel projects.

#### Changed

- Renames the `Event` methods to conform to prefix getter methods with `get` as expected amongst Labrador 
codebases.
- Makes the AmpEventEmitter class and StandardEventFactory class final.

#### Added

- Added a PromiseCombinator implementation that allows specifying how listener promises should 
be resolved when an event is emitted. You can set a default PromiseCombinator per emitter or 
provide a specific PromiseCombinator to each emit call.

#### Changed

- Modifications to the $listenerData array passed on each emit call has been adjusted. Instead of 
setting the 'id' parameter it now sets a '__labrador_kennel_id' parameter. This value should not 
be depended upon, in future versions it may be removed. If it is determined that listeners need to 
have a normalized access to the ListenerId outside of the use case for off() the design of the 
EventEmitter will be adjusted at that time.
- Renamed the Emitter interface and implementations to be less ambiguous with amphp's Emitter 
concept with which there are no real similarities.
- Updated dependency versions

## v2.0.0-rc1 - 2019-02-16

#### Added

- Now adheres to the Labrador Coding Standard.

#### Changed

- Updated PHP to 7.2 as well as minor version updates to most packages.
- Major doc improvements

## v1.2.0 - 2018-01-13

#### Added

- Adds Travis CI support
- Adds Scrutinizer CI support

#### Changed

- Update amphp/amp, phpunit/phpunit, amphp/phpunit-util

## v1.1.0 - 2017-05-13

#### Removed

- Removed unused dependencies psr/log and monolog/monolog

## v1.0.0 - 2017-05-13

#### Added

- Adds the `Emitter` interface and `AmpEmitter` implementation
- Adds the `Event` interface and `StandardEvent` implementation
- Adds the `EventFactory` interface and `EventFactory` implementation