<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'خطای سیستم') | {{ config('app.name') }}</title>
    <script>
        (function () {
            function apply() {
                try {
                    var theme = localStorage.getItem('user-theme') || 'default';
                    var mode = localStorage.getItem('user-mode')
                        || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                    var html = document.documentElement;
                    if (theme && theme !== 'default') html.setAttribute('data-theme', theme);
                    else html.removeAttribute('data-theme');
                    html.classList.toggle('dark', mode === 'dark');
                } catch (e) {}
            }
            apply();
            window.addEventListener('storage', function (e) {
                if (e.key === 'user-theme' || e.key === 'user-mode') apply();
            });
        })();
    </script>
    @vite(['resources/css/app.css'])
    <style>
        .bg-mesh::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 70% 40% at 50% -10%, color-mix(in srgb, var(--md-sys-color-error) 10%, transparent), transparent),
                radial-gradient(ellipse 50% 30% at 85% 85%, color-mix(in srgb, var(--md-sys-color-primary) 6%, transparent), transparent);
            z-index: 0;
            pointer-events: none;
        }
        .bg-noise::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.025'/%3E%3C/svg%3E");
            z-index: 0;
            pointer-events: none;
        }
        .grid-overlay {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(to right, color-mix(in srgb, var(--md-sys-color-outline) 3%, transparent) 1px, transparent 1px),
                linear-gradient(to bottom, color-mix(in srgb, var(--md-sys-color-outline) 3%, transparent) 1px, transparent 1px);
            background-size: 72px 72px;
            mask-image: radial-gradient(ellipse 75% 55% at 50% 45%, rgb(0 0 0) 15%, transparent 75%);
            -webkit-mask-image: radial-gradient(ellipse 75% 55% at 50% 45%, rgb(0 0 0) 15%, transparent 75%);
            pointer-events: none;
            z-index: 0;
        }
        .glass-card {
            background: color-mix(in srgb, var(--md-sys-color-surface) 92%, transparent);
            backdrop-filter: blur(32px) saturate(140%);
            -webkit-backdrop-filter: blur(32px) saturate(140%);
            border: 1px solid color-mix(in srgb, var(--md-sys-color-outline-variant) 60%, transparent);
            box-shadow:
                0 1px 3px color-mix(in srgb, var(--md-sys-color-primary) 6%, transparent),
                0 8px 32px color-mix(in srgb, var(--md-sys-color-error) 8%, transparent),
                0 32px 64px color-mix(in srgb, var(--md-sys-color-primary) 10%, transparent);
        }
        .dark .glass-card {
            background: var(--md-sys-color-surface);
            box-shadow:
                0 1px 3px color-mix(in srgb, var(--md-sys-color-primary) 10%, transparent),
                0 12px 40px color-mix(in srgb, var(--md-sys-color-primary) 25%, transparent);
        }
        .icon-ring {
            position: absolute;
            inset: -5px;
            border-radius: 1rem;
            border: 2px solid color-mix(in srgb, var(--md-sys-color-error) 70%, transparent);
            will-change: transform, opacity;
        }
        .tech-details {
            transform: scaleY(0);
            transform-origin: top;
            opacity: 0;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
            pointer-events: none;
            will-change: transform, opacity;
        }
        .tech-details.open {
            transform: scaleY(1);
            opacity: 1;
            pointer-events: auto;
        }
        .tech-details.hidden { display: none !important; }
        .copy-feedback {
            position: absolute;
            top: -32px;
            left: 50%;
            transform: translateX(-50%) translateY(6px);
            opacity: 0;
            transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.2s ease;
            pointer-events: none;
            white-space: nowrap;
            box-shadow: 0 4px 16px color-mix(in srgb, var(--md-sys-color-primary) 20%, transparent);
        }
        .copy-feedback.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
        .btn-primary {
            transition: transform 0.15s ease, box-shadow 0.2s ease, opacity 0.2s ease, background-color 0.2s ease;
        }
        .btn-primary:hover {
            opacity: 0.92;
            box-shadow: 0 4px 16px color-mix(in srgb, var(--md-sys-color-primary) 25%, transparent);
        }
        .btn-primary:active { transform: scale(0.96); }
        .btn-secondary { transition: all 0.2s ease; }
        .btn-secondary:hover {
            background: var(--md-sys-color-surface-variant);
            border-color: var(--md-sys-color-outline-variant);
        }
        .btn-secondary:active { transform: scale(0.96); }
        .btn-ghost { transition: background-color 0.2s ease, transform 0.15s ease; }
        .btn-ghost:hover { background: color-mix(in srgb, var(--md-sys-color-surface-variant) 60%, transparent); }
        .btn-ghost:active { transform: scale(0.98); }
        *:focus-visible {
            outline: 3px solid var(--md-sys-color-primary);
            outline-offset: 2px;
            border-radius: 4px;
        }
        a:focus-visible, button:focus-visible { border-radius: 0.75rem; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background: color-mix(in srgb, var(--md-sys-color-primary) 20%, transparent);
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover { background: color-mix(in srgb, var(--md-sys-color-primary) 35%, transparent); }
        .mono-font {
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, monospace;
            font-feature-settings: "tnum";
            letter-spacing: -0.01em;
        }
        .ltr { direction: ltr; text-align: left; }
        @media (prefers-reduced-motion: reduce) {
            .tech-details, .icon-ring, .animate-shimmer {
                animation: none !important;
                transition: none !important;
            }
            .glass-card { backdrop-filter: none; -webkit-backdrop-filter: none; }
        }
    </style>
</head>
<body class="antialiased min-h-screen flex flex-col p-4 sm:p-6 relative overflow-hidden font-sans bg-[var(--md-sys-color-background)] bg-mesh bg-noise">

<div class="grid-overlay"></div>

<div class="relative z-10 w-full max-w-xl mx-auto flex-1 flex flex-col justify-between animate-fade-focus">

    <!-- System Status Header -->
    <div class="flex items-center justify-between mb-5 px-1 animate-slide-up-fade animate-delay-100">
        <div class="flex items-center gap-2.5">
            <span class="w-2.5 h-2.5 rounded-full bg-[var(--md-sys-color-error)] animate-pulse"></span>
            <span class="text-[11px] font-bold tracking-[0.15em] uppercase mono-font text-[var(--md-sys-color-error)]">
                    @yield('error_code', 'ERR_UNKNOWN')
                </span>
        </div>
        <div class="flex items-center gap-2 text-[11px] font-medium mono-font text-[var(--md-sys-color-on-surface-variant)]">
            <svg class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span id="error-clock"></span>
        </div>
    </div>

    <!-- Main Card -->
    <div class="glass-card rounded-2xl overflow-hidden">

        <div class="h-1 w-full relative overflow-hidden bg-[var(--md-sys-color-error)]">
            <div class="absolute inset-0 bg-gradient-to-l from-transparent via-[var(--md-sys-color-on-error)]/30 to-transparent w-1/2 animate-shimmer"></div>
        </div>

        <div class="px-6 py-8 sm:px-10 sm:py-10">

            <!-- Icon Section -->
            <div class="flex justify-center mb-7 animate-pop animate-delay-200">
                <div class="relative">
                    <div class="icon-ring animate-pulse-ring"></div>
                    <div class="relative w-16 h-16 rounded-2xl flex items-center justify-center bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]">
                        @yield('icon', '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>')
                    </div>
                </div>
            </div>

            <!-- Text Content -->
            <div class="text-center space-y-3 mb-8 animate-slide-up-fade animate-delay-300">
                <h1 class="text-[1.25rem] sm:text-[1.5rem] font-bold tracking-tight leading-tight text-[var(--md-sys-color-on-surface)]">
                    @yield('heading', 'خطای سیستمی رخ داده است')
                </h1>
                <p class="text-sm sm:text-[0.95rem] leading-[1.7] max-w-lg mx-auto text-[var(--md-sys-color-on-surface-variant)]">
                    @yield('message', 'عملیات مورد نظر با مشکل مواجه شد. تیم فنی از این خطا مطلع شده و در حال بررسی و رفع آن هستند. لطفاً چند لحظه دیگر تلاش کنید یا با پشتیبانی تماس بگیرید.')
                </p>
            </div>

            <!-- Technical Details Accordion -->
            <div class="mb-8 animate-slide-up-fade animate-delay-400">
                <button type="button" id="error-toggle"
                        class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-xs font-semibold btn-ghost bg-[var(--md-sys-color-surface-variant)]/50 text-[var(--md-sys-color-on-surface-variant)]">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                            </svg>
                            اطلاعات فنی و شناسه ردیابی
                        </span>
                    <svg id="error-chevron"
                         class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>

                <div id="error-details" class="tech-details hidden">
                    <div class="rounded-xl p-4 sm:p-5 space-y-3.5 mono-font text-[11px] sm:text-xs bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)]">
                        <div class="flex justify-between items-start gap-4">
                            <span class="opacity-60 shrink-0">شناسه ردیابی</span>
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="font-bold truncate text-[var(--md-sys-color-on-surface)]">{{ $trace_id ?? 'TRC-XXXXXXXX' }}</span>
                                <button type="button" id="error-copy" class="p-1.5 rounded-lg hover:bg-[var(--md-sys-color-on-surface)]/5 transition-colors relative shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5"/>
                                    </svg>
                                    <span id="error-copy-feedback" class="copy-feedback px-2 py-1 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-on-surface)] text-[var(--md-sys-color-surface)]">کپی شد</span>
                                </button>
                            </div>
                        </div>
                        <div class="h-px opacity-25 bg-[var(--md-sys-color-outline)]"></div>
                        <div class="flex justify-between items-center gap-4">
                            <span class="opacity-60">زمان وقوع</span>
                            <span class="font-bold ltr text-[var(--md-sys-color-on-surface)]">{{ $timestamp ?? now()->format('Y-m-d H:i:s') }}</span>
                        </div>
                        <div class="flex justify-between items-center gap-4">
                            <span class="opacity-60 shrink-0">آدرس درخواست</span>
                            <span class="font-bold truncate ltr text-[var(--md-sys-color-on-surface)]">{{ $request_url ?? request()->url() }}</span>
                        </div>
                        <div class="flex justify-between items-center gap-4">
                            <span class="opacity-60">نسخه سیستم</span>
                            <span class="font-bold text-[var(--md-sys-color-on-surface)]">{{ $app_version ?? config('app.version') }}</span>
                        </div>
                        <div class="flex justify-between items-center gap-4">
                            <span class="opacity-60">محیط</span>
                            <span class="font-bold text-[var(--md-sys-color-on-surface)]">{{ app()->environment() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="w-full h-px mb-7 opacity-30 bg-[var(--md-sys-color-outline-variant)]"></div>

            <!-- Primary Actions -->
            <div class="flex flex-col sm:flex-row gap-3 animate-slide-up-fade animate-delay-500">
                <a href="{{ url('/') }}"
                   class="btn-primary flex-1 inline-flex justify-center items-center gap-2.5 px-6 py-3 rounded-xl text-sm font-bold bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                    </svg>
                    بازگشت به داشبورد
                </a>

                @hasSection('action')
                    @yield('action')
                @else
                    <button type="button" onclick="window.location.reload()"
                            class="btn-secondary flex-1 inline-flex justify-center items-center gap-2.5 px-6 py-3 rounded-xl text-sm font-bold text-[var(--md-sys-color-primary)] bg-transparent border border-[var(--md-sys-color-outline)]">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                        </svg>
                        تلاش مجدد
                    </button>
                @endif
            </div>

            @hasSection('secondary_action')
                <div class="mt-3 animate-slide-up-fade animate-delay-600">
                    @yield('secondary_action')
                </div>
            @endif
        </div>

        <!-- Card Footer -->
        <div class="px-6 py-4 sm:px-10 flex flex-col sm:flex-row items-center justify-between gap-3 text-[11px] font-semibold border-t border-[var(--md-sys-color-outline-variant)]"
             style="background: color-mix(in srgb, var(--md-sys-color-surface-variant) 25%, transparent);">
            <div class="flex items-center gap-2 text-[var(--tool-gold-text)]">
                <span class="w-1.5 h-1.5 rounded-full bg-[var(--tool-gold-color)] animate-pulse"></span>
                <span>وضعیت سرویس: <span class="font-bold text-[var(--md-sys-color-on-surface)]">اختلال عملیاتی</span></span>
            </div>
            <div class="flex items-center gap-3 flex-wrap justify-center text-[var(--md-sys-color-on-surface-variant)]">
                <span class="opacity-60">پشتیبانی:</span>
                <a href="tel:{{ config('app.support.phone') }}" class="hover:underline transition-colors font-bold ltr text-[var(--md-sys-color-primary)]">{{ convertToPersian(config('app.support.phone')) }}</a>
                <span class="opacity-30">|</span>
                <a href="mailto:{{ config('app.support.email') }}" class="hover:underline transition-colors font-bold ltr text-[var(--md-sys-color-primary)]">{{ config('app.support.email') }}</a>
            </div>
        </div>
    </div>

    <!-- External Footer -->
    <div class="mt-6 flex items-center justify-center gap-4 text-[11px] font-semibold animate-fade animate-delay-700"
         style="color: color-mix(in srgb, var(--md-sys-color-on-surface-variant) 55%, transparent)">
        <span>© {{ date('Y') }} {{ config('app.name') }}</span>
    </div>
</div>

<script>
    (function () {
        var clockEl = document.getElementById('error-clock');
        if (clockEl) {
            function tick() {
                clockEl.textContent = new Date().toLocaleTimeString('fa-IR', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }
            tick();
            setInterval(tick, 1000);
        }

        var toggleBtn = document.getElementById('error-toggle');
        var details = document.getElementById('error-details');
        var chevron = document.getElementById('error-chevron');
        if (toggleBtn && details) {
            var open = false;
            toggleBtn.addEventListener('click', function () {
                if (open) {
                    details.classList.remove('open');
                    if (chevron) chevron.style.transform = '';
                    open = false;
                    setTimeout(function () { details.classList.add('hidden'); }, 300);
                } else {
                    details.classList.remove('hidden');
                    requestAnimationFrame(function () { details.classList.add('open'); });
                    if (chevron) chevron.style.transform = 'rotate(180deg)';
                    open = true;
                }
            });
        }

        var copyBtn = document.getElementById('error-copy');
        var feedback = document.getElementById('error-copy-feedback');
        if (copyBtn) {
            copyBtn.addEventListener('click', async function () {
                var text = (copyBtn.previousElementSibling.textContent || '').trim();
                try { await navigator.clipboard.writeText(text); }
                catch (e) {
                    var ta = Object.assign(document.createElement('textarea'), { value: text });
                    document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove();
                }
                if (feedback) {
                    feedback.classList.add('show');
                    setTimeout(function () { feedback.classList.remove('show'); }, 2000);
                }
            });
        }
    })();
</script>

</body>
</html>
