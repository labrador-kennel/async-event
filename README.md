# Labrador Async Event

A library to emit events for applications or libraries running Amp's Event Loop.

## Requirements

- PHP 7.1+
- [amphp/amp](https://github.com/amphp/amp)

## Installation

It is recommended you install Labrador Async Event using Composer.

```
composer require cspray/labrador-async-event
```

## The Basics

It is important to note that AsyncEvent is intended to emit events in a running Amp event 
loop. **If you do not either call `Emitter::emit()` within `Loop::run()` or you use 
`Amp\wait()` on the Promise returned from the `Emitter::emit()` call your listeners will 
never be called!**
or an Amp\Promise and see them resolved.


