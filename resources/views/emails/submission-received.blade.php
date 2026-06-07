<x-mail::message>
# We got your memo

Your voice memo is in the queue. We're listening to it now and turning it
into an article — you'll get another email the moment it's ready.

<x-mail::button :url="$statusUrl">
Watch the progress live
</x-mail::button>

Happy gardening,<br>
{{ config('app.name') }}
</x-mail::message>
