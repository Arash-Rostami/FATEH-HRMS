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
    class="relative w-full h-full flex items-center justify-center">

    <button @click="open = !open"
            class="flex items-center gap-2 w-full h-full justify-center outline-none group transition-all duration-300">

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

    <!-- Dropdown -->
    <div x-show="open"
         @click.outside="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-2 scale-95"
         class="absolute top-[calc(100%+12px)] right-0 p-1.5 rounded-2xl
                bg-[var(--md-sys-color-surface)]/95 backdrop-blur-xl
                border border-[var(--md-sys-color-outline-variant)]/10
                shadow-2xl z-50 text-[var(--md-sys-color-on-surface)] min-w-[220px]"
         style="display: none;">

        <div class="text-[10px] uppercase tracking-wider opacity-40 font-bold px-3 py-2 mb-1">
            تغییر وضعیت
        </div>

        @foreach(presenceCases() as $status)
            <button @click="setStatus('{{ $status->value }}')"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200
                           hover:bg-[var(--md-sys-color-surface-container-high)]/50 group text-right mb-1 last:mb-0">

                <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-200"
                     :class="currentStatus === '{{ $status->value }}'
                        ? 'bg-{{ $status->color() }}-500 text-white shadow-lg shadow-{{ $status->color() }}-500/25'
                        : 'bg-{{ $status->color() }}-500/10 text-{{ $status->color() }}-400 group-hover:bg-{{ $status->color() }}-500 group-hover:text-white'">
                    <span class="material-symbols-rounded text-[20px]">{{ $status->icon() }}</span>
                </div>

                <div class="flex flex-col items-start">
                    <span class="text-sm font-medium transition-colors duration-200"
                          :class="currentStatus === '{{ $status->value }}'
                            ? 'text-{{ $status->color() }}-500'
                            : 'group-hover:text-{{ $status->color() }}-500'">
                        {{ $status->label() }}
                    </span>
                    <span class="text-[10px] opacity-50 group-hover:text-{{ $status->color() }}-400/70 text-right transition-colors duration-200">
                        {{ $status->sublabel() }}
                    </span>
                </div>

                <span x-show="currentStatus === '{{ $status->value }}'"
                      class="mr-auto material-symbols-rounded text-[18px] text-{{ $status->color() }}-500 animate-fade-in">
                    check
                </span>
            </button>
        @endforeach
    </div>
</div>
