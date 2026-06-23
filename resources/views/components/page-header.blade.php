@props([
    // Page title (serif). Subtitle is optional supporting copy under it.
    'title',
    'subtitle' => null,
    // Email/identity subtitles should clamp to one line; descriptive copy shouldn't.
    'truncate' => false,
])

{{-- The desk-style page header: title + optional subtitle on the left, an
     optional action button (passed as the slot) pinned to the right. Shared by
     the garden desk, account settings, and the record/upload screen so they
     read as one continuous app. --}}
<div class="flex items-start justify-between gap-4">
    <div class="min-w-0">
        <h1 class="font-serif text-3xl font-semibold text-garden-800">{{ $title }}</h1>
        @if ($subtitle)
            <p @class(['mt-1 text-base text-zinc-500', 'truncate' => $truncate])>{{ $subtitle }}</p>
        @endif
    </div>
    {{ $slot }}
</div>
