@php
    $tabs = [
        ['id' => 'route', 'icon' => 'alt_route', 'label' => __('resources/correspondence/strings.legend.tabs.route')],
        ['id' => 'sign', 'icon' => 'draw', 'label' => __('resources/correspondence/strings.legend.tabs.sign')],
        ['id' => 'template', 'icon' => 'content_copy', 'label' => __('resources/correspondence/strings.legend.tabs.template')],
        ['id' => 'notes', 'icon' => 'info', 'label' => __('resources/correspondence/strings.legend.tabs.notes')],
    ];
    $sections = [
        'route' => [
            'intro' => __('resources/correspondence/strings.legend.route.intro'),
            'rows' => [
                ['icon' => 'draw', 'color' => 'primary', 'label' => __('resources/correspondence/strings.legend.route.approver_label'), 'text' => __('resources/correspondence/strings.legend.route.approver_text')],
                ['icon' => 'visibility', 'color' => 'secondary', 'label' => __('resources/correspondence/strings.legend.route.fyi_label'), 'text' => __('resources/correspondence/strings.legend.route.fyi_text')],
                ['icon' => 'linear_scale', 'color' => 'tertiary', 'label' => __('resources/correspondence/strings.legend.route.mode_label'), 'text' => __('resources/correspondence/strings.legend.route.mode_text')],
                ['icon' => 'groups', 'color' => 'primary', 'label' => __('resources/correspondence/strings.legend.route.party_label'), 'text' => __('resources/correspondence/strings.legend.route.party_text')],
            ],
        ],
        'sign' => [
            'intro' => __('resources/correspondence/strings.legend.sign.intro'),
            'rows' => [
                ['icon' => 'draw', 'color' => 'primary', 'label' => __('resources/correspondence/strings.legend.sign.electronic_label'), 'text' => __('resources/correspondence/strings.legend.sign.electronic_text')],
                ['icon' => 'ink_pen', 'color' => 'tertiary', 'label' => __('resources/correspondence/strings.legend.sign.wet_label'), 'text' => __('resources/correspondence/strings.legend.sign.wet_text')],
                ['icon' => 'password', 'color' => 'secondary', 'label' => __('resources/correspondence/strings.legend.sign.code_label'), 'text' => __('resources/correspondence/strings.legend.sign.code_text')],
                ['icon' => 'local_shipping', 'color' => 'secondary', 'label' => __('resources/correspondence/strings.legend.sign.manual_label'), 'text' => __('resources/correspondence/strings.legend.sign.manual_text')],
            ],
        ],
        'template' => [
            'intro' => __('resources/correspondence/strings.legend.template.intro'),
            'rows' => [
                ['icon' => 'content_copy', 'color' => 'primary', 'label' => __('resources/correspondence/strings.legend.template.use_label'), 'text' => __('resources/correspondence/strings.legend.template.use_text')],
                ['icon' => 'data_object', 'color' => 'tertiary', 'label' => __('resources/correspondence/strings.legend.template.blank_label'), 'text' => __('resources/correspondence/strings.legend.template.blank_text')],
                ['icon' => 'lock', 'color' => 'error', 'label' => __('resources/correspondence/strings.legend.template.scope_label'), 'text' => __('resources/correspondence/strings.legend.template.scope_text')],
                ['icon' => 'edit', 'color' => 'primary', 'label' => __('resources/correspondence/strings.legend.template.save_label'), 'text' => __('resources/correspondence/strings.legend.template.save_text')],
            ],
        ],
    ];
    $notes = [
        __('resources/correspondence/strings.legend.notes.views'),
        __('resources/correspondence/strings.legend.notes.print'),
        __('resources/correspondence/strings.legend.notes.registry'),
        __('resources/correspondence/strings.legend.notes.pin'),
        __('resources/correspondence/strings.legend.notes.freeze'),
        __('resources/correspondence/strings.legend.notes.opened'),
    ];
    $chipClasses = fn ($c) => match ($c) {
        'primary' => 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]',
        'tertiary' => 'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]',
        'secondary' => 'bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]',
        'error' => 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]',
    };
@endphp

<div x-data="correspondenceLegend()">
    <div role="tablist" class="flex p-1 mb-5 bg-[var(--md-sys-color-surface-variant)]/40 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30">
        @foreach($tabs as $tab)
            <button type="button" role="tab" x-on:click="select('{{ $tab['id'] }}')" x-bind:aria-selected="isTab('{{ $tab['id'] }}')" title="{{ $tab['label'] }}"
                    class="text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60 aria-selected:bg-[var(--md-sys-color-primary)] aria-selected:text-[var(--md-sys-color-on-primary)] aria-selected:shadow-md flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-[13px] font-bold transition-all duration-200">
                <span class="material-symbols-rounded text-[17px]">{{ $tab['icon'] }}</span>
                {{ $tab['label'] }}
            </button>
        @endforeach
    </div>

    @foreach($tabs as $tab)
        @if($tab['id'] === 'notes')
            <div x-show="isTab('notes')" x-cloak class="space-y-2">
                @foreach($notes as $note)
                    <div class="flex items-start gap-2 px-1">
                        <span class="material-symbols-rounded text-[15px] mt-0.5 text-[var(--md-sys-color-on-surface-variant)] opacity-70">info</span>
                        <p class="text-[11.5px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $note }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <div x-show="isTab('{{ $tab['id'] }}')" x-cloak class="space-y-2">
                <p class="text-[12px] text-[var(--md-sys-color-on-surface-variant)] px-1 mb-1">{{ $sections[$tab['id']]['intro'] }}</p>
                @foreach($sections[$tab['id']]['rows'] as $s)
                    <div class="flex items-start gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipClasses($s['color']) }}">
                            <span class="material-symbols-rounded text-[16px]">{{ $s['icon'] }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[12px] font-bold text-[var(--md-sys-color-on-surface)] mb-0.5">{{ $s['label'] }}</p>
                            <p class="text-[12px] leading-6 text-[var(--md-sys-color-on-surface-variant)]">{{ $s['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endforeach
</div>
