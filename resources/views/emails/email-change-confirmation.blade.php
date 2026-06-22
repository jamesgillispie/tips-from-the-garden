<x-mail::message>
# Confirm your new email

Someone asked to use this address for a {{ config('app.name') }} account. Tap the
button to confirm the change — the link works for {{ $minutes }} minutes.

<x-mail::button :url="$url">
Confirm this email
</x-mail::button>

Until you confirm, nothing changes — the account keeps its current email.
If you weren't expecting this, you can safely ignore it.

{{ config('app.name') }}
</x-mail::message>
