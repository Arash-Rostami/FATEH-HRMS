<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="language" content="fa">
<meta name="theme-color" content="#4e5f66">
<meta name="color-scheme" content="light dark">
<meta name="referrer" content="strict-origin-when-cross-origin">
<meta name="format-detection" content="telephone=no">

<title>{{ config('app.name') }}</title>
<meta name="description" content="{{ config('app.slogan_en') }}">
<meta name="keywords" content="{{ config('app.name') }} {{ config('app.name_en') }}">
<meta name="author" content="{{ config('app.developer') }}">
<meta name="robots" content="noindex,nofollow">
<link rel="canonical" href="{{ request()->url() }}">

<link rel="icon" href="{{ asset(config('app.favicon')) }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">

<meta name="csrf-token" content="{{ csrf_token() }}">

<meta property="og:type" content="website">
<meta property="og:url" content="{{ request()->url() }}">
<meta property="og:title" content="{{ config('app.name') }}">
<meta property="og:description" content="{{ config('app.slogan_en') }}">
<meta property="og:site_name" content="{{ config('app.name') }}">

<meta property="twitter:url" content="{{ request()->url() }}">
<meta name="twitter:card" content="summary_large_image">
<meta property="twitter:title" content="{{ config('app.name') }}">
<meta property="twitter:description" content="{{ config('app.slogan_en') }}">

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload"
      href="/build/assets/material-symbols/material-symbols-rounded.woff2"
      as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/build/assets/material-symbols/rounded.css">

<script type="application/ld+json">

{
  "context": "https://schema.org",
  "type": "Organization",
  "url": @json(request()->url()),
  "name": @json(config('app.organization_name_en')),
  "description": @json(config('app.slogan_en')),
  "logo": @json(asset(config('app.company_logo', 'build/assets/img/logo.svg'))),
  "sameAs": @json(array_values(array_filter([config('app.instagram'), config('app.linkedin')])))
}
</script>
