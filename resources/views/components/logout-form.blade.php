@props([
    // Wrapping <form class="..."> — e.g. "inline" when sitting in a sentence.
    'class' => '',
])

{{-- POSTs to the logout route with CSRF. The submit control is the slot, so this
     wraps both the header's Flux menu item and the inline "Sign out" link on the
     desk without duplicating the form/route/@csrf boilerplate. --}}
<form method="POST" action="{{ route('logout') }}" @class([$class])>
    @csrf
    {{ $slot }}
</form>
