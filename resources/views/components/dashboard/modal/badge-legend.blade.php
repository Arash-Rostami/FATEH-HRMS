@props(['name', 'title' => 'راهنمای نشانگرهای اعلان', 'items' => [], 'groups' => []])

<x-ui.modals.dialog :name="$name" :title="$title">
    <div class="space-y-3 max-h-[calc(50vh-112px)] overflow-y-auto pr-1">
        <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--tool-sapphire-bg)] px-4 py-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-surface)]/50 text-[var(--tool-sapphire-color)]">
                <span class="material-symbols-rounded text-[16px]">fiber_manual_record</span>
            </div>
            <div class="min-w-0">
                <p class="text-[12px] font-bold text-[var(--tool-sapphire-text)] mb-0.5">دات (نشانگر وضعیت زنده)</p>
                <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">تا وقتی شرایطی برقرار است روشن می‌ماند؛ به‌محض برطرف‌شدن شرایط — یا در برخی موارد، با مشاهدهٔ همان بخش — خودکار خاموش می‌شود.</p>
            </div>
        </div>

        <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--tool-gold-bg)] px-4 py-3">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-surface)]/50 text-[var(--tool-gold-color)]">
                <span class="material-symbols-rounded text-[16px]">notifications</span>
            </div>
            <div class="min-w-0">
                <p class="text-[12px] font-bold text-[var(--tool-gold-text)] mb-0.5">زنگوله (اعلان یک‌بارمصرف)</p>
                <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">به‌ازای هر مورد تازه یک‌بار ظاهر می‌شود و تا وقتی خودتان آن را نبندید باقی می‌ماند؛ دوباره ظاهر نمی‌شود. اگر دکمهٔ «مشاهده» داشته باشد، مستقیماً شما را به همان مورد می‌برد؛ اعلان‌های تجمیع‌شده (چند مورد در یک کارت) این دکمه را ندارند.</p>
            </div>
        </div>

        @if(count($groups))
            <div class="mt-5 pt-4 border-t border-[var(--md-sys-color-outline-variant)]/40" x-data="{ tab: '{{ $groups[0]['id'] }}' }">
                <div class="flex flex-wrap gap-1 p-1 mb-4 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
                    @foreach($groups as $group)
                        <button
                            type="button"
                            @click="tab = '{{ $group['id'] }}'"
                            :class="tab === '{{ $group['id'] }}'
                                ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-md'
                                : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60'"
                            class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[12px] font-bold transition-all duration-200"
                        >
                            <span class="material-symbols-rounded text-[16px]">{{ $group['icon'] }}</span>
                            {{ $group['label'] }}
                        </button>
                    @endforeach
                </div>

                @foreach($groups as $group)
                    <div x-show="tab === '{{ $group['id'] }}'" x-cloak class="space-y-2">
                        @foreach($group['items'] as $item)
                            <x-dashboard.modal.badge-legend-row :item="$item"/>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif

        @if(count($items))
            <div class="mt-5 pt-4 border-t border-[var(--md-sys-color-outline-variant)]/40 space-y-2">
                <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] px-1 mb-1">در این صفحه</p>
                @foreach($items as $item)
                    <x-dashboard.modal.badge-legend-row :item="$item"/>
                @endforeach
            </div>
        @endif
    </div>
</x-ui.modals.dialog>
