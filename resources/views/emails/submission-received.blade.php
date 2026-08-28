<x-mail::message>
# We got your memo

@if ($submission->user->canWriteArticles())
Your voice memo is in the queue. We're listening to it now and turning it
into a journal entry — you'll get another email the moment it's ready.
@else
Your voice memo is in the queue. We're transcribing it on our server and will
send your notes back without asking the AI writer to rewrite them.
@endif

<x-mail::button :url="$statusUrl">
Watch the progress live
</x-mail::button>

Happy gardening,<br>
{{ config('app.name') }}
</x-mail::message>
