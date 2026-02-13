<meta charset="UTF-8">
<meta name="language" content="en">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<meta name="theme-color" content="#1B232E">

<title>{{ config('app.name') }}</title>
<meta name="description" content="Human Resource Management System for handling office and users' tasks">
<meta name="keywords" content="Fateh HRMS {{ config('app.name') }}">
<meta name="author" content="Arash Rostami">
<meta name="robots" content="noindex,nofollow">

<meta name="csrf-token" content="{{ csrf_token() }}">

<meta property="og:type" content="website">
<meta property="og:url" content="{{ request()->url() }}">
<meta property="og:title" content="{{ config('app.name') }}">
<meta property="og:description" content="Human Resource Management System for handling office and users' tasks">

<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="{{ request()->url() }}">
<meta property="twitter:title" content="{{ config('app.name') }}">
<meta property="twitter:description" content="Human Resource Management System for handling office and users' tasks">

<script type="application/ld+json">
    {
      "context": "https://schema.org",
      "type": "Organization",
      "url": "{{ request()->url() }}",
{{--  "name": "{{ config('app.name') }}",--}}
  "description": "Human Resource Management System for handling office and users' tasks",
{{--  "logo": "{{ config('app.logo') }}",--}}
  "sameAs": [
    "https://www.instagram.com/persol_co/",
    "https://www.linkedin.com/company/persol/"
  ]
}
</script>
