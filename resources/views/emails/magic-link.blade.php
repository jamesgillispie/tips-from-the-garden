<x-mail::message>
# Sign in to Tips From The Garden

Click the button below to sign in. The link works for 30 minutes.

<x-mail::button :url="$loginUrl">
Sign in
</x-mail::button>

If you didn't request this, you can safely ignore this email.

{{ config('app.name') }}
</x-mail::message>
