<x-layouts.app :title="$article->title.' — '.config('app.name')">
    <article class="rounded-2xl border border-garden-100 bg-white p-8 sm:p-12">
        <header class="mb-8 border-b border-garden-100 pb-6">
            <h1 class="font-serif text-3xl font-semibold text-garden-800 sm:text-4xl">
                {{ $article->title }}
            </h1>
            <p class="mt-3 text-base text-soil-700/70">
                {{ $article->user->name ?: $article->user->email }}
                · {{ $article->created_at->format('F j, Y') }}
            </p>
        </header>

        <div class="prose-garden max-w-none font-serif leading-relaxed
            [&_h2]:font-serif [&_h2]:text-2xl [&_h2]:font-semibold [&_h2]:text-garden-800 [&_h2]:mt-8 [&_h2]:mb-3
            [&_p]:my-4 [&_ul]:my-4 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:my-4 [&_ol]:list-decimal [&_ol]:pl-6">
            {!! $article->bodyHtml() !!}
        </div>

        <footer class="mt-10 flex flex-wrap items-center gap-3 border-t border-garden-100 pt-6">
            <flux:button href="{{ route('articles.download', ['token' => $article->download_token, 'format' => 'pdf']) }}"
                variant="primary" icon="arrow-down-tray">
                Download PDF
            </flux:button>
            <flux:button href="{{ route('articles.download', ['token' => $article->download_token, 'format' => 'md']) }}"
                icon="document-text">
                Download as text
            </flux:button>
            @guest
                <span class="ml-auto text-sm text-soil-700/70">
                    Want it to sound even more like you?
                    <flux:link href="{{ route('dashboard') }}" class="font-medium">Sign in and add writing samples</flux:link>
                </span>
            @endguest
        </footer>
    </article>
</x-layouts.app>
