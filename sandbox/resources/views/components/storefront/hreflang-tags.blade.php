@php
    $alternateUrls = localizedRoute()->alternateUrls();
    $currentLocale = currentLocale();
@endphp

{{-- hreflang tags for SEO --}}
@foreach($alternateUrls as $locale => $url)
    <link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}" />
@endforeach

{{-- x-default for language negotiation --}}
<link rel="alternate" hreflang="x-default" href="{{ url('/') }}" />

{{-- Canonical URL for current page --}}
<link rel="canonical" href="{{ $alternateUrls[$currentLocale] ?? url()->current() }}" />
