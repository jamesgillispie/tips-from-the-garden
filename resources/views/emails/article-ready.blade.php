<x-mail::message>
# {{ $article->title }}

Your journal entry is ready — here it is, start to finish. It's also saved online,
where you can download it as a PDF to print or share.

---

{{ $article->body_md }}

---

<x-mail::button :url="$articleUrl">
Read it online or download a PDF
</x-mail::button>

Want future journal entries to sound even more like you? Sign in from the
journal entry page and add a few samples of your own writing — a blog post,
a newsletter, anything.

Happy gardening,<br>
{{ config('app.name') }}
</x-mail::message>
