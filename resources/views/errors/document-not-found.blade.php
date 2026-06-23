@extends('layouts.app')

@section('minimal_layout', true)

@section('content')
    <div class="min-h-[70vh] flex items-center justify-center w-full">
        <div class="max-w-md w-full glass-panel rounded-xl p-10 text-center flex flex-col items-center gap-6 relative overflow-hidden bg-[var(--md-sys-color-primary-container)]">
            <div class="absolute top-0 inset-x-0 h-2 bg-[var(--md-sys-color-error)] opacity-80"></div>

            <div class="w-24 h-24 rounded-3xl bg-[var(--md-sys-color-error-container)] flex items-center justify-center shadow-sm mt-2">
                <span class="material-symbols-rounded font-fill text-5xl text-[var(--md-sys-color-error)]">folder_off</span>
            </div>

            <div class="flex flex-col gap-4">
                <h1 class="text-2xl font-bold text-[var(--md-sys-color-on-surface)]">سند مورد نظر یافت نشد</h1>
                <p class="text-[var(--md-sys-color-on-surface-variant)] text-sm leading-relaxed">
                    فایل فیزیکی این سند در سرور موجود نیست یا مسیر آن تغییر کرده است. لطفاً وضعیت فایل را در سیستم بررسی کنید.
                </p>
            </div>

        </div>
    </div>
@endsection

