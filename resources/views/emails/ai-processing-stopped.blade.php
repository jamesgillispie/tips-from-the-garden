<x-mail::message>
# AI processing is off

We have turned off transcription, AI journal writing and voice learning for
your account. New recordings will not be processed by a model. Your existing
recordings, transcripts, entries and voice profile have not been deleted.

You can still type and save your own notes without AI, or turn individual
features back on whenever you choose.

<x-mail::button :url="$settingsUrl">
Review AI & privacy settings
</x-mail::button>

Happy gardening,<br>
{{ config('app.name') }}
</x-mail::message>
