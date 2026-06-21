<x-mail::message>
# We hit a snag

We're sorry — something went wrong on our end while turning your memo into
a journal entry, and it didn't finish. Your recording reached us just fine; the
trouble was all ours.

Please send it once more — that usually clears it right up.

<x-mail::button :url="$retryUrl">
Send it again
</x-mail::button>

If it keeps happening, simply reply to this email and a real person will
take a look.

Happy gardening,<br>
{{ config('app.name') }}
</x-mail::message>
