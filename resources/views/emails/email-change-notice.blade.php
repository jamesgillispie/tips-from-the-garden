<x-mail::message>
# Did you ask to change your email?

We got a request to move your {{ config('app.name') }} account to
**{{ $newEmail }}**. We've sent a confirmation link to that new address — the
change won't take effect until it's opened from there.

If this was you, there's nothing else to do.

If it **wasn't** you, someone may know your password. Sign in and change it
right away:

<x-mail::button :url="route('account')">
Go to account settings
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
