<x-mail::message>
# We left your memo untouched

AI transcription is turned off for your account, so we did not store or process
the recording you emailed. Nothing from that memo was sent to a writing model.

If you would like emailed recordings transcribed again, turn on AI and
**Transcription** in your privacy settings first.

<x-mail::button :url="$settingsUrl">
Review AI & privacy settings
</x-mail::button>

Happy gardening,<br>
{{ config('app.name') }}
</x-mail::message>
