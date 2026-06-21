<x-mail::message>
# Set your password

We got a request to set a password for your {{ config('app.name') }} account.
Tap the button below — the link works for {{ $minutes }} minutes.

<x-mail::button :url="$url">
Set your password
</x-mail::button>

If you didn't ask for this, you can safely ignore this email — your account
stays exactly as it is.

{{ config('app.name') }}
</x-mail::message>
