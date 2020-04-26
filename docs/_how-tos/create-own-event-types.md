---
title: Creating Custom Event Types
---
Setup the `StandardEventFactory` to customize the creation process for specific Event types. Labrador async-event 
recommends creating semantic types to describe your application and has easy ways to facilitate doing so baked into this 
library.

It is highly recommended you construct all Events with the `StandardEventFactory`. It is a robust, tested 
implementation that is flexible enough to support custom instantiation when it is necessary. If you do not provide 
custom Event construction for emitted events the `StandardEvent` object will be used.

In our examples we'll use the namespace `Acme\Events`; this should be replaced with the appropriate top-level namespace 
for your application.

### Step 1. Create your new Event class

```php
<?php declare(strict_types = 1);

namespace Acme\Events;

use Acme\Model\FooModel;
use Cspray\Labrador\AsyncEvent\StandardEvent;

class FooEvent extends StandardEvent {

    public function __construct(FooModel $fooModel) {
        parent::__construct('foo', $fooModel);
    }
}
```

### Step 2. Register a new factory for the event

The `StandardEventFactory::register()` method allows you to override how Events are constructed. In our example we'll 
want to handle passing the correct attributes to our custom Event.

```php
<?php declare(strict_types = 1);

namespace Acme\Events;

use Acme\Model\FooModel;
use Cspray\Labrador\AsyncEvent\StandardEventFactory;

$eventFactory = new StandardEventFactory();
$eventFactory->register('foo', function(FooModel $fooModel) {
    return new FooEvent($fooModel);
});
```

Now whenever the `'foo'` event is emitted and the `StandardEventFactory` constructs an Event your factory callable will 
be invoked instead of the normal `StandardEventFactory`. It is important to note that the factory callable should match 
the method signature below. It is possible to make the target type parameter should be made more explicit if you know 
what type of target to expect.

```php
<?php

use Cspray\Labrador\AsyncEvent\Event;

$factoryCallable = function(object $target, array $eventData, ...$createArgs) : Event {};
```

The `StandardEventFactory::create()` method allows passing an arbitrary number of arguments after `$eventData` that will 
be passed to any custom factory callables. If you do not have a custom factory registered for the event additional 
arguments after `$eventData` are ignored.
