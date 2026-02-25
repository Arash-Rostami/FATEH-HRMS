@extends('layouts.app')
<style>
        .liquid-blob {
            position: fixed;
            width: 500px;
            height: 500px;
            background: linear-gradient(45deg, var(--md-sys-color-primary), #8a5a23);
            filter: blur(80px);
            opacity: 0.15;
            z-index: 0;
            border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
            animation: blob-bounce 12s ease-in-out infinite alternate;
            pointer-events: none;
        }

        @keyframes blob-bounce {
            0% { transform: translate(0, 0) scale(1); border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%; }
            50% { transform: translate(100px, 50px) scale(1.1); border-radius: 40% 60% 70% 30% / 40% 70% 30% 60%; }
            100% { transform: translate(-50px, 100px) scale(0.9); border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%; }
        }

        .glass-panel {
            background-color: color-mix(in srgb, var(--md-sys-color-surface), transparent 40%);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid color-mix(in srgb, var(--md-sys-color-outline), transparent 80%);
            box-shadow: var(--md-sys-elevation-1);
        }
    </style>

    <div class="relative min-h-[80vh] flex items-center justify-center p-6 overflow-hidden">

        <div class="liquid-blob top-[-5%] left-[-5%]"></div>
        <div class="liquid-blob bottom-[-5%] right-[-5%]"
             style="animation-delay: -5s; background: linear-gradient(45deg, var(--md-sys-color-tertiary), var(--md-sys-color-primary));">
        </div>

        <main class="relative z-10 max-w-3xl w-full text-center">
            <div class="glass-panel rounded-[3rem] p-12 md:p-24 space-y-10">
                <div class="inline-flex px-6 py-2 rounded-full text-sm font-bold tracking-widest mb-4"
                     style="background: var(--md-sys-color-primary-container); color: var(--md-sys-color-on-primary-container)">
                    🚧 تـحت توسعـه
                </div>

                <h1 class="text-5xl md:text-8xl font-black tracking-tight leading-none">
                    این صفحه به زودی <br/>
                    <span style="color: var(--md-sys-color-primary)">آماده می‌شود</span>
                </h1>

                <p class="text-lg md:text-xl opacity-80 max-w-md mx-auto leading-relaxed">
                    ما در حال طراحی و پیاده‌سازی این بخش با استانداردهای جدید هستیم. صبور باشید، اتفاقات خوبی در راه است.
                </p>

                <div class="pt-8 flex flex-col sm:flex-row gap-6 justify-center items-center">
                    <a href="{{ url('/dashboard') }}" class="md3-btn min-w-[200px]">
                        بازگشت به پیشخوان
                    </a>
                </div>
            </div>
        </main>
    </div>
