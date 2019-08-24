# Changelog

## v2.0.0-rc2 - 2019-08-??

- Added a PromiseCombinator implementation that allows specifying how listener promises should 
be resolved when an event is emitted. You can set a default PromiseCombinator per emitter or 
provide a specific PromiseCombinator to each emit call.
- Updated dependency versions

## v2.0.0-rc1 - 2019-02-16

- Updated PHP to 7.2 as well as minor version updates to most packages.
- Now adheres to the Labrador Coding Standard.
- Major doc improvements

## v1.2.0 - 2018-01-13

- Update amphp/amp, phpunit/phpunit, amphp/phpunit-util
- Adds Travis CI support
- Adds Scrutinizer CI support

## v1.1.0 - 2017-05-13

- Removed unused dependencies psr/log and monolog/monolog

## v1.0.0 - 2017-05-13

- Adds the `Emitter` interface and `AmpEmitter` implementation
- Adds the `Event` interface and `StandardEvent` implementation
- Adds the `EventFactory` interface and `EventFactory` implementation