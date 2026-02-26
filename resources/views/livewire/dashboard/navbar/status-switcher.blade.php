<div x-data="{
        open: false,
        currentStatus: @entangle('status'),
        statuses: {
            @foreach(presenceCases() as $status)
            '{{ $status->value }}': {
                icon: '{{ $status->icon() }}',
                label: '{{ $status->label() }}',
                color: '{{ $status->color() }}',
                sublabel: '{{ $status->sublabel() }}'
            },
            @endforeach
        },
        get active() {
            return this.statuses[this.currentStatus] || this.statuses.onsite;
        },
        setStatus(val) {
            this.currentStatus = val;
            this.open = false;
            $wire.changeStatus(val);
        }
    }"
     class="relative w-full h-full flex items-center justify-center group">

    <button @click="open = !open"
            class="flex items-center gap-2 w-full h-full justify-center outline-none transition-all duration-300">

        <!-- Icon -->
        <span class="material-symbols-rounded text-[20px] transition-colors duration-300"
              :class="'text-' + active.color + '-400'"
              x-text="active.icon">
        </span>

        <!-- Label -->
        <div class="!hidden lg:!block text-xs font-medium tracking-wide opacity-90 transition-opacity group-hover:opacity-100"
             x-text="active.label">
        </div>

        <!-- Chevron -->
        <span class="!hidden lg:!block material-symbols-rounded text-[18px] opacity-50 transition-transform duration-300 group-hover:opacity-80"
              :class="open ? 'rotate-180' : ''">expand_more</span>
    </button>

    <x-dashboard.tooltip text="وضعیت حضور" position="bottom" />

    <!-- Dropdown -->
    <div x-show="open"
         @click.outside="open = false"
         class="absolute top-[calc(100%+12px)] right-0 p-2 rounded-2xl w-64
                bg-[var(--md-sys-color-surface)]/95 animate-slide-down
                border border-[var(--md-sys-color-outline-variant)]/10
                shadow-2xl z-50 text-[var(--md-sys-color-on-surface)]"
         style="display: none;">

        <div class="text-[10px] uppercase tracking-wider opacity-40 font-bold px-3 py-2 mb-1">
            تغییر وضعیت
        </div>

        @foreach(presenceCases() as $status)
            <button @click="setStatus('{{ $status->value }}')"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all duration-200
                           hover:bg-[var(--md-sys-color-surface-container-high)]/50 group/item text-right mb-1 last:mb-0
                           {{ $status->ringClass() }}">

                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-200"
                         :class="currentStatus === '{{ $status->value }}'
                            ? 'bg-{{ $status->color() }}-500 text-white shadow-lg shadow-{{ $status->color() }}-500/25'
                            : 'bg-{{ $status->color() }}-500/10 text-{{ $status->color() }}-400 group-hover/item:bg-{{ $status->color() }}-500 group-hover/item:text-white'">
                        <span class="material-symbols-rounded text-[20px]">{{ $status->icon() }}</span>
                    </div>

                    <div class="flex flex-col items-start">
                        <span class="text-sm font-medium transition-colors duration-200"
                              :class="currentStatus === '{{ $status->value }}'
                                ? 'text-{{ $status->color() }}-500'
                                : 'group-hover/item:text-{{ $status->color() }}-500'">
                            {{ $status->label() }}
                        </span>
                        <span class="text-[10px] opacity-50 group-hover/item:text-{{ $status->color() }}-400/70 text-right transition-colors duration-200">
                            {{ $status->sublabel() }}
                        </span>
                    </div>
                </div>

                <!-- Compact Toggle Switch -->
                <div class="relative w-8 h-4 rounded-full transition-colors duration-200 shrink-0"
                     :class="currentStatus === '{{ $status->value }}' ? 'bg-{{ $status->color() }}-500' : 'bg-[var(--md-sys-color-outline-variant)]/30'">
                    <div class="absolute top-0.5 left-0.5 w-3 h-3 rounded-full bg-white transition-transform duration-200 shadow-sm"
                         :class="currentStatus === '{{ $status->value }}' ? 'translate-x-4' : 'translate-x-0'"></div>
                </div>
            </button>
        @endforeach
    </div>
</div>
