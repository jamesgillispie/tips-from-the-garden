<x-mail::message>
# Your article is ready

**{{ $article->title }}**

Read it, download it as a PDF or Markdown file, or keep it for your records.

<x-mail::button :url="$articleUrl">
Read your article
</x-mail::button>

Want future articles to sound even more like you? Sign in from the article
page and add a few samples of your own writing — a blog post, a newsletter,
anything.

Happy gardening,<br>
{{ config('app.name') }}
</x-mail::message>
