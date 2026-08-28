<x-mail::message>
# Your garden notes are ready

We kept your words as notes and did not ask the AI writer to reshape them.
Here they are, start to finish:

---

{{ $submission->transcript->raw_text }}

---

<x-mail::button :url="$statusUrl">
View the saved notes
</x-mail::button>

You can change transcription, AI writing and voice learning separately in your
account settings whenever you like.

Happy gardening,<br>
{{ config('app.name') }}
</x-mail::message>
