---
layout: docs
---
## Async Event Documentation

The documentation for Labrador Async Event so that you can emit and listen to events triggered by Labrador, 
your Application, or 3rd-party Plugins. Async Event is a powerful, yet simple, event emitter that enables 
listeners to execute within context of a running Loop.


### Installation

You should install Labrador packages through Composer.

```
composer require cspray/labrador-async-event
```

### Quick Start

To get started with Async Event first you must have a solid understanding of your domain and knowledge on 
when and for what reason events should be emitted. Once that's done, which is really the hard part of this 
endeavor, you can start registering listeners and emitting events with ease. Let's assume that we have a 
User authentication system and we want to emit an event when a User registers.

<script src="https://gist.github.com/cspray/994096743ed9960ad05eed443d2032ee.js"></script>

For the most basic use case this is all there is to it. If you'd like to know more about the functionality 
provided by the Emitter you should check out the API documentation inside the source code.
