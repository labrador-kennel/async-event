# Changelog

## v2.0.0-rc2 - 2020-04-26

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