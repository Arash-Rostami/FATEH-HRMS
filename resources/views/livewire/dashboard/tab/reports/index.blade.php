<div x-data="report"
     class="flex flex-col gap-4  p-4 md:p-8">
    @forelse($reports as $report)
        <div class="relative group bg-[var(--md-sys-color-surface-container-lowest)] rounded-2xl p-4 transition-all hover:bg-[var(--md-sys-color-surface-container-low)] border border-[var(--md-sys-color-outline-variant)] hover:border-[var(--md-sys-color-outline)] shadow-sm hover:shadow-md">
            <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
                {{-- Date & Department --}}
                <div class="flex flex-col items-center justify-center min-w-[80px] text-center gap-1 shrink-0">
                    <span class="text-xs font-medium text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-primary-container)] px-2 py-1 rounded-full whitespace-nowrap">
                        {{ $report->department->name ?? $report->department_id }}
                    </span>
                    <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] font-mono" dir="ltr">
                        {{ jdate($report->created_at)->format('Y/m/d') }}
                    </span>
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0 text-right w-full">
                    <h3 class="text-base font-semibold text-[var(--md-sys-color-on-surface)] truncate mb-1">
                        {{ $report->title }}
                    </h3>
                    <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] line-clamp-2 leading-relaxed text-justify">
                        {{ superClean($report->description) }}
                    </p>
                </div>

                {{-- Action --}}
                <div class="mt-2 md:mt-0 self-end md:self-center shrink-0">
                    <a href="{{ asset($report->file_path) }}"
                       data-fancybox="report"
                       data-caption="{{ $report->title }}"
                       data-type="pdf"
                       class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] hover:bg-[var(--md-sys-color-secondary)] hover:text-[var(--md-sys-color-on-secondary)] transition-colors shadow-sm"
                       title="مشاهده گزارش">
                        <span class="material-symbols-outlined text-xl">visibility</span>
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="flex flex-col items-center justify-center h-64 text-[var(--md-sys-color-on-surface-variant)] opacity-60">
            <div class="p-4 rounded-full bg-[var(--md-sys-color-surface-container-high)] mb-4">
                <span class="material-symbols-outlined text-4xl">folder_off</span>
            </div>
            <p class="text-base font-medium">هیچ گزارشی یافت نشد</p>
        </div>
    @endforelse

    @if($reports->hasMorePages())
        <div class="flex justify-center py-6 pb-12">
            <button wire:click="loadMore" class="group flex items-center gap-2 text-sm font-medium text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-surface)] px-5 py-2.5 rounded-xl border border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:border-[var(--md-sys-color-primary)] transition-all shadow-sm hover:shadow-md">
                <span>بارگذاری بیشتر</span>
                <span class="material-symbols-outlined text-sm group-hover:translate-y-0.5 transition-transform">expand_more</span>
            </button>
        </div>
    @endif
</div>
