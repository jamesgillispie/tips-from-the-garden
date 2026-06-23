{{-- The "Back to desk" link-button shared by the record/upload screen and
     account settings. Ghost so it stays secondary to each page's primary action;
     the label collapses to just the arrow on narrow screens. --}}
<flux:button :href="route('dashboard')" variant="ghost" size="sm" icon="arrow-left"
    aria-label="Back to desk" class="shrink-0">
    <span class="hidden sm:inline">Back to desk</span>
</flux:button>
