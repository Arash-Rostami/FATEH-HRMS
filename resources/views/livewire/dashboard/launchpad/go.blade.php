<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    @unless($notice)
        <meta http-equiv="refresh" content="50">
    @endunless
    <title>{{ $title }} | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="antialiased min-h-screen flex items-center justify-center p-4 font-sans bg-[var(--md-sys-color-background)]">
    @if($notice)
        <div class="flex max-w-md flex-col items-center gap-4 text-center">
            <p class="text-sm text-[var(--md-sys-color-on-surface-variant)]">ورود خودکار برای ایمیل شما فعال نیست؛ ایمیل شما هنوز توسط مدیر تأیید نشده است. می‌توانید سامانه را باز کنید و خودتان وارد شوید.</p>
            <x-ui.buttons.form href="{{ $url }}">باز کردن سامانه</x-ui.buttons.form>
        </div>
    @else
        <form method="POST" action="{{ $receiver }}" class="flex flex-col items-center gap-4 text-center">
            <p class="text-sm text-[var(--md-sys-color-on-surface-variant)]">برای ورود به «{{ $title }}» ادامه دهید.</p>
            <input type="hidden" name="assertion" value="{{ $assertion }}">
            @if($next !== '')
                <input type="hidden" name="next" value="{{ $next }}">
            @endif
            <x-ui.buttons.form type="submit">ادامه و ورود به سامانه</x-ui.buttons.form>
        </form>
    @endif
</body>
</html>
