<x-mail::message>
# We couldn't verify a memo from your address

An email with a voice memo arrived claiming to come from your address, but
we couldn't confirm it was really sent by you — so we set it aside rather
than add it to your garden desk.

**If this was you**, your email provider probably isn't vouching for your
address (missing SPF or DKIM records — common on older custom domains).
You can record or upload the memo on the website instead, or ask whoever
manages your email domain about adding those records.

**If this wasn't you**, there's nothing to do — the memo was discarded and
never touched your account.

Happy gardening,<br>
{{ config('app.name') }}
</x-mail::message>
