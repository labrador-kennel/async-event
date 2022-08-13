# Labrador async-event

![Unit Testing & Code Lint](https://github.com/labrador-kennel/async-event/workflows/Unit%20Testing%20&%20Code%20Lint/badge.svg)
![Latest Release](https://img.shields.io/github/v/release/labrador-kennel/async-event)

Labrador async-event provides a way to emit semantic events on the [amphp/amp] event loop. It provides a robust set of features for working with an event system, including:

- First-class representation of an event with the `Cspray\Labrador\AsyncEvent\Event` interface.
- Events include a rich set of data; including the datetime the event was emitted, the target of the event, and an arbitrary array.
- First-class representation of an event listener with the `Cspray\Labrador\AsyncEvent\Listener` interface.
- [Annotated Container](https://github.com/cspray/annoated-container) integration, including the ability to autowire listeners into the emitter.
- No more tracking arbitrary listener ids, remove listeners using an object-oriented API.

## Installation

[Composer] is the only supported method for installing Labrador packages.

```
composer require cspray/labrador-async-event
```

## Governance

All Labrador packages adhere to the rules laid out in the [Labrador Governance repo]

[amphp/amp]: https://amphp.org
[Composer]: https://getcomposer.org
[Labrador Governance repo]: https://github.com/labrador-kennel/governance
