<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>نامه</title>
    @vite(['resources/css/app.css'])
    <style>
        .link-fit { overflow: hidden; }
        @media print {
            .link-fit, .link-fit .correspondence-paper { transform: none !important; height: auto !important; }
        }
    </style>
</head>
<body class="m-0 bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface)]">
    @if($void)
        <p class="mx-4 my-8 text-base">@if($superseded === null)این نامه باطل شده است@else این نامه با نامهٔ اصلاحی جایگزین شده است@if($superseded !== '') (شمارهٔ <bdi>{{ $superseded }}</bdi>)@endif @endif</p>
    @else
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 print:hidden">
            <p>
                @if($number !== ''){{ __('resources/correspondence/strings.fields.number') }}: <bdi>{{ $number }}</bdi>@endif
                @if($date !== '') · {{ __('resources/correspondence/strings.fields.letter_date') }}: <bdi>{{ $date }}</bdi>@endif
            </p>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.buttons.form type="button" data-link-print>چاپ / ذخیره PDF</x-ui.buttons.form>
                @if($pdfUrl !== '')
                    <x-ui.buttons.form :href="$pdfUrl" rel="nofollow" class="no-underline">دریافت PDF</x-ui.buttons.form>
                @endif
            </div>
        </div>
        <div class="link-fit">
            @include('livewire.dashboard.correspondence.paper', ['letter' => $letter, 'mode' => 'link'])
        </div>
        <script src="{{ asset('js/correspondence-link.js') }}" defer></script>
    @endif
</body>
</html>
