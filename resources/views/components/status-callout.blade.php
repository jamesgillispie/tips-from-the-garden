{{-- Flash a one-off success message from session('status') — shown after sign-in
     redirects, password-reset requests, saved account settings, etc. Renders
     nothing when there's no status, so it's safe to drop in unconditionally. --}}
@if (session('status'))
    <flux:callout variant="success" icon="check-circle" {{ $attributes }}>
        <flux:callout.text>{{ session('status') }}</flux:callout.text>
    </flux:callout>
@endif
