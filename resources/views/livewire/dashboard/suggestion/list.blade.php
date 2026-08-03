<x-ui.forms.card class="!p-0 gap-0 overflow-hidden h-[calc(130vh-6rem)] flex flex-col">

    <div class="p-3 flex flex-col gap-3 animate-enter stagger-1 shadow-lg">

        <x-ui.title
            icon="format_list_bulleted"
            title="لیست"
            count="{{ $this->perPage }}"
        />

        @if($this->authDeptCode !== 'MA')
        <x-ui.buttons.form
            class="w-full justify-center gap-2 hover:brightness-95 active:scale-[0.97] transition-all glow-sm"
            icon="add"
            wire:click="openCreate"
        >
            ایجاد
        </x-ui.buttons.form>
        @endif

        <x-ui.forms.search
            name="search"
            model="search"
            placeholder="جستجو در پیشنهادات ..."
            debounce="400"
            icon="manage_search"
            :clearable="true"
            class="max-w-md mx-auto"
        />
    </div>

    {{-- LIST --}}
    <div class="flex-1 overflow-y-auto p-3 space-y-2" id="suggestionsList" style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;">

        @forelse($this->suggestions as $item)
            @php($p = $this->presenter($item))
            @php($stage = $p->stageConfig())

            <button
                wire:click="selectSuggestion({{ $item->id }})"
                wire:key="s-{{ $item->id }}"
                data-rf="suggestion-{{ $item->id }}"
                @class([
                    'group relative w-full text-right rounded-2xl border p-4 transition-all text-left hover:-translate-y-[1px]',
                    'bg-[var(--md-sys-color-surface)] border-[var(--md-sys-color-outline-variant)] hover:shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:border-[var(--md-sys-color-primary)]/30' => $selectedId !== $item->id,
                    'border-[var(--md-sys-color-primary)] shadow-[0_8px_24px_color-mix(in_srgb,var(--md-sys-color-primary)_18%,transparent)]' => $selectedId === $item->id,
                    'bg-gradient-to-l from-[var(--md-sys-color-error)]/10 to-transparent border-l-3 border-l-[var(--md-sys-color-error)] !border-r-none' => $p->requiresMyAction(),
                ])
            >

                @if($selectedId === $item->id)
                    <div
                        class="absolute right-0 top-4 bottom-4 w-0.5 rounded-r-full bg-[var(--md-sys-color-primary)] animate-expand origin-center"></div>
                @endif

                <div class="flex justify-between items-start gap-3 mb-2">
                    <span class="text-[11px] font-mono text-[var(--md-sys-color-on-surface-variant)] dir-ltr">
                        SN-{{ $item->created_at->format('Ymd') }}-{{ str_pad($item->id, 6, '0', STR_PAD_LEFT) }}
                    </span>

                    <div class="flex items-center gap-2">
                        @if($p->requiresMyAction())
                            <x-ui.notification-badge />
                        @endif

                        <span
                            class="px-2.5 py-1 rounded-lg text-[10px] font-bold flex items-center gap-1 {{ $p->stageBadgeClasses() }}">
                            <span> {{ $stage['badge_icon'] }}</span>
                            <span>{{ $stage['label']  }}</span>
                        </span>
                    </div>
                </div>

                <h3 title="{{ $item->title }}"
                    class="font-bold text-sm mb-2 line-clamp-1 text-[var(--md-sys-color-on-surface)]"
                >
                    {{ $item->title }}
                </h3>

                <div class="flex items-center gap-2 text-[11px]
                            text-[var(--md-sys-color-on-surface-variant)] mb-3">

                    <span class="flex items-center gap-1 min-w-0">
                        <span class="material-symbols-rounded text-sm">person</span>
                        <span class="truncate">{{ $item->user->name ?? '—' }}</span>
                    </span>

                    <span class="flex items-center gap-1 min-w-0">
                        <span class="material-symbols-rounded text-sm">domain</span>

                        <span class="truncate">
                            {{ $p->showDeptDetails($item,'truncated_deps') }}
                        </span>

                        @if($p->showDeptDetails($item,'deps_count') > 2)
                            <span title=" {{ $p->showDeptDetails($item,'all_deps') }}"
                                  class="cursor-help shrink-0 px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-primary-container)]
                                       text-[var(--md-sys-color-on-surface-variant)] text-[10px]">
                                {{ $p->showDeptDetails($item,'deps_count') - 2 }}+
                            </span>
                        @endif
                    </span>
                </div>

                <div class="flex justify-between items-center pt-2
                            border-t border-[var(--md-sys-color-outline-variant)]/50 text-xs">

                    <div class="flex gap-3">

                        @if(($item->agree_count ?? 0) > 0)
                            <span class="flex items-center gap-1 text-[var(--md-sys-color-primary)]">
                                <span class="material-symbols-rounded !text-sm font-fill">thumb_up</span>
                                {{ $item->agree_count }}
                            </span>
                        @endif

                        @if(($item->neutral_count ?? 0) > 0)
                            <span class="flex items-center gap-1 text-[var(--md-sys-color-secondary)]">
                                <span class="material-symbols-rounded !text-sm font-fill">thumbs_up_down</span>
                                {{ $item->neutral_count }}
                            </span>
                        @endif

                        @if(($item->disagree_count ?? 0) > 0)
                            <span class="flex items-center gap-1 text-[var(--md-sys-color-error)]">
                                <span class="material-symbols-rounded !text-sm font-fill">thumb_down</span>
                                {{ $item->disagree_count }}
                            </span>
                        @endif

                    </div>

                    {{-- DATE --}}
                    <span class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">
                        {{ toJalali($item->created_at, 'j F Y') }}
                    </span>
                </div>

            </button>

        @empty
            <x-ui.empty icon="inbox" title="موردی یافت نشد" variant="list" />
        @endforelse

        <div wire:loading wire:target="search" class="p-4 text-center justify-center">
            <span class="material-symbols-rounded animate-spin text-base align-middle ml-1
                         text-[var(--md-sys-color-primary)]">
                progress_activity
            </span>
            <span class="text-sm text-[var(--md-sys-color-on-surface-variant)]">
                در حال جستجو...
            </span>
        </div>

    </div>

    @if($this->suggestions->hasMorePages())
        <div class="flex justify-center py-2">
            <x-ui.buttons.load-more
                action="loadMore"
                text="بارگذاری بیشتر"
                loading-text="در حال دریافت..."
                icon="expand_more"
                class="font-medium text-[var(--md-sys-color-primary)]
                       bg-[var(--md-sys-color-surface)]
                       px-5 py-2.5 rounded-xl
                       border border-[var(--md-sys-color-outline-variant)]
                       hover:bg-[var(--md-sys-color-primary-container)]
                       hover:text-[var(--md-sys-color-on-primary-container)]
                       hover:border-[var(--md-sys-color-primary)]
                       shadow-sm hover:shadow-md"
            />
        </div>
    @endif

</x-ui.forms.card>
