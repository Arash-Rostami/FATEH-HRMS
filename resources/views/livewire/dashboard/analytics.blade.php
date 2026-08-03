<div
        dir="rtl"
        class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
        style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;"
>
    <div class="max-w-[88rem] mx-auto page-wrapper">

        <x-ui.title
                icon="analytics"
                title="تحلیل‌های سازمانی"
                :count="17"
                countLabel="ماژول آماری"
        />

        <section class="w-full flex flex-col gap-5 lg:gap-6"
                 x-data="analyticsChart(@js($chartData), @js($presenter->chartConfig()), @js($presenter->categories()))"
                 x-init="init()">

            <div class="flex flex-wrap gap-2">
                @foreach($presenter->categories() as $key => $cat)
                    <button type="button" @click="switchCategory('{{ $key }}')"
                            :class="category === '{{ $key }}'
                                ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]'
                                : 'bg-[var(--md-sys-color-surface-variant)]/40 text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/70'"
                            class="animate-slide-up-fade animate-delay-{{ $loop->index * 100 }} flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-semibold transition-colors">
                        <span class="material-symbols-rounded text-lg" aria-hidden="true">{{ $cat['icon'] }}</span>
                        {{ $cat['label'] }}
                    </button>
                @endforeach
            </div>

            @foreach($presenter->categories() as $catKey => $cat)
                <div x-show="category === '{{ $catKey }}'" x-cloak
                     class="analytics-card animate-slide-up-fade rounded-3xl flex flex-col min-h-[420px]">
                    <header class="px-5 py-4 border-b border-[var(--md-sys-color-outline-variant)]/30 flex flex-wrap items-center gap-3 rounded-t-3xl shrink-0">
                        <div class="flex size-8 items-center justify-center rounded-xl bg-[var(--tool-amethyst-bg)] text-[var(--tool-amethyst-color)]">
                            <span class="material-symbols-rounded text-base" aria-hidden="true">{{ $cat['icon'] }}</span>
                        </div>
                        <h2 class="text-sm font-semibold text-[var(--md-sys-color-on-surface)]">{{ $cat['label'] }}</h2>
                    </header>

                    <div class="flex flex-wrap gap-2 px-5 pt-4 shrink-0">
                        @foreach($cat['modules'] as $moduleKey => $moduleLabel)
                            <button type="button" @click="switchModule('{{ $catKey }}', '{{ $moduleKey }}')"
                                    :class="activeModule.{{ $catKey }} === '{{ $moduleKey }}'
                                        ? 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]'
                                        : 'bg-[var(--md-sys-color-surface-variant)]/30 text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                                    class="animate-slide-up-fade animate-delay-{{ $loop->index * 100 }} rounded-full px-3.5 py-1.5 text-xs font-medium transition-colors">
                                {{ $moduleLabel }}
                            </button>
                        @endforeach
                    </div>

                    <p class="px-5 pt-3 text-xs leading-6 text-[var(--md-sys-color-on-surface-variant)] shrink-0"
                       x-text="chartConfig[activeModule.{{ $catKey }}]?.description ?? ''"></p>

                    <div class="relative w-full h-[340px] p-4 lg:p-5 flex items-center justify-center">
                        <div class="h-full"
                             :class="chartConfig[activeModule.{{ $catKey }}]?.type === 'doughnut' ? 'w-full max-w-[300px]' : 'w-full'">
                            <canvas id="analytics-chart-{{ $catKey }}" role="img"
                                    aria-label="{{ $cat['label'] }}"></canvas>
                        </div>
                    </div>
                </div>
            @endforeach

        </section>
    </div>
</div>
