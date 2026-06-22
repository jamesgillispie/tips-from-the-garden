<x-mail::message>
# Let's get you set up first

Thanks for sending a memo! Before we can turn it into a journal entry, this
email address needs a **{{ config('app.name') }}** account — that's how we
know the memo is yours and keep it on your own garden desk.

It only takes a moment:

1. Create your account using **this same email address**.
2. Email your memo in again from here.
3. We'll get right to work, and it'll land on your desk.

<x-mail::button :url="$registerUrl">
Create your account
</x-mail::button>

Already have an account under a different address? Email your memos from the
address you signed up with, and they'll show up there.

Happy gardening,<br>
{{ config('app.name') }}
</x-mail::message>
