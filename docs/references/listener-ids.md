# Deep Dive: Listener IDs

Listener IDs are unique strings returned by `EventEmitter::on()` and `EventEmitter::once()` that allows for turning the 
listener off and retrieving data about the listener through `EventEmitter::listeners()`. We'll go over critical 
information to know about this important aspect of the library.

### Do not rely on listener ID formats

The most important thing to understand about listener IDs is that you MUST NOT rely on the formats of a listener ID.
They are unique to each implementation and there are no hard rules on what a listener ID looks like other than they 
MUST meet the following requirements:

- Be a non-empty string
- Be able to uniquely identify listeners
- Avoid the possibility for collisions by creating an ID with sufficient uniqueness.

It is also important to note that changing the format of listener IDs is not seen as a breaking change if the change 
is made in such a way that public APIs are unaffected.

**If you rely on the format of listener IDs you are undoubtedly going to cause pain for yourself!**

### Access to the listener ID

There are three ways in which you can access a listener ID.

1. The return method of `on()` and `once()`.
2. The array key used in calls to `listeners()`.
3. As listener data passed to the invoked listener.

I'd like to focus on the 3rd way to access this data. You may have noticed in the ["Handling Events"](./tutorials/handling-events) 
tutorial that the listener data had a key that we did not specify. This key in the listener data `__labrador_kennel_id` 
is the listener ID for the invoked listener. The most important thing to note about this is that if you provide listener 
data with a key that matches `__labrador_kennel_id` your data will be overwritten.

