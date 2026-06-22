@php
    // The address gardeners email memos to. Mirrors config/pipeline.php's fallback
    // to the APP_URL host so it still reads sensibly before INBOUND_EMAIL_ADDRESS
    // is set.
    $inboundEmail = config('pipeline.inbound.address')
        ?: 'memos@'.(parse_url(config('app.url'), PHP_URL_HOST) ?? 'tipsfromthegarden.test');
@endphp

{{-- A copyable email chip: tap Copy (clipboard) or select the text by hand. The
     address font-size clamps with the viewport so a long address never spills
     past the edge on a phone, and break-all wraps it as a last resort. --}}
<div
    x-data="{
        copied: false,
        flash() { this.copied = true; clearTimeout(this.timer); this.timer = setTimeout(() => (this.copied = false), 2000); },
        copy() {
            const el = this.$refs.address;
            const value = (el?.textContent ?? '').trim();
            // Async Clipboard API only exists in secure contexts (https/localhost).
            if (navigator.clipboard?.writeText) {
                navigator.clipboard.writeText(value).then(() => this.flash()).catch(() => this.fallback(el));
            } else {
                this.fallback(el);
            }
        },
        fallback(el) {
            // No async clipboard (plain http, older mobile browsers) or it failed:
            // select the address so it can be copied by hand, and try the legacy
            // command so the button still gives feedback when it's available.
            if (!el) return;
            const range = document.createRange();
            range.selectNodeContents(el);
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
            try { if (document.execCommand('copy')) this.flash(); } catch (e) {}
        },
    }"
    class="flex items-center gap-2 rounded-xl bg-garden-50 p-2 pl-3.5"
>
    {{-- email_off keeps Cloudflare Scrape Shield from rewriting the address into an
         obfuscated "[email protected]" link the gardener can't read or copy. --}}
    <span
        x-ref="address"
        class="min-w-0 flex-1 select-all break-all font-semibold leading-tight text-garden-700"
        style="font-size: clamp(0.75rem, 3.6vw, 1.0625rem);"
    ><!--email_off-->{{ $inboundEmail }}<!--/email_off--></span>

    <button
        type="button"
        x-on:click="copy"
        class="inline-flex min-h-[44px] shrink-0 items-center gap-1.5 rounded-lg bg-garden-700 px-3 py-2.5 text-sm font-semibold text-white transition hover:bg-garden-800"
    >
        <flux:icon.clipboard-document x-show="!copied" class="size-4" />
        <flux:icon.check x-show="copied" x-cloak class="size-4" />
        <span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
    </button>

    {{-- Politely announce the copy to screen readers without changing the
         button's own accessible name. --}}
    <span class="sr-only" aria-live="polite" x-text="copied ? 'Email address copied' : ''"></span>
</div>
