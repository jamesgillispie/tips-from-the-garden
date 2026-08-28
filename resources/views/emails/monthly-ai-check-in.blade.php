<x-mail::message>
# Are these AI settings still right for you?

Hi {{ $user->name ?: 'there' }},

It has been about a month since you last confirmed how AI may work with your
garden memos. Here is what your account currently allows:

- **Transcription:** {{ $user->canTranscribe() ? 'On' : 'Off' }} — recordings become text on our server
- **Journal entry writing:** {{ $user->canWriteArticles() ? 'On' : 'Off' }} — transcript text goes to the configured AI writer
- **Voice learning:** {{ $user->canLearnVoice() ? 'On' : 'Off' }} — selected text samples shape compact style notes

We never send raw audio to the writing model. If Anthropic is configured, only
the transcript text needed for writing is sent to that cloud service. Ollama
and whisper.cpp run on our own server.

<x-mail::button :url="$confirmUrl">
These choices are still right
</x-mail::button>

<x-mail::button :url="$disableUrl" color="secondary">
Stop all AI processing
</x-mail::button>

Both links open a short confirmation page so an email security scanner cannot
change your account by following a link on its own.

You can also tune each feature separately:
[Review AI & privacy settings]({{ $settingsUrl }})

Happy gardening,<br>
{{ config('app.name') }}
</x-mail::message>
