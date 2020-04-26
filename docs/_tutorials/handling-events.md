---
title: Handling Events
---
Triggering events with async-event is meant to be simple out-of-the-box but also to provide a tremendous amount of power 
and flexibility with how you can trigger and respond to events. Here we'll go over the basics for the most common use 
cases.

### Listening to Events

The first thing you'll need to do is setup listeners to respond to events that get triggered. This is accomplished 
through the `on()` and `once()` methods. We'll go over the `on()` API below, the `once()` API is similar with the only 
difference being that the listener will be removed after the first invocation.

<script src="https://gist.github.com/cspray/967b2e310bd5d588bfbba70d426a59ff.js"></script>


