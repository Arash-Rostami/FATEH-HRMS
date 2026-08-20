@if(count($this->todaysOccasions))
    <div id="todays-occasions"
         x-ref='occasions'
         dir="rtl" class="flex flex-col gap-2.5">
        <p class="flex items-center gap-1.5 text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] px-1">
            <span class="material-symbols-rounded text-sm">celebration</span>
            مناسبت‌های امروز
        </p>

        <div class="flex flex-wrap gap-2.5">
            @foreach($this->todaysOccasions as $occasion)
                @php
                    $user = $occasion['user'];
                    $tone = $occasion['tone'];
                @endphp

                <button
                    type="button"
                    wire:key="occasion-{{ $user->id }}-{{ $occasion['type'] }}"
                    style="background: {{ $tone['bg'] }}"
                    title="{{ $tone['label'] }}: {{ $user->name }}"
                    class="group !cursor-help flex items-center gap-2 pl-3 pr-2 py-1.5 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/30
                           shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 animate-fade"
                >
                    <div class="relative w-9 h-9 shrink-0">
                        <img
                            src="{{ $user->getProfileImageUrl() ?? $user->getInitialsAvatarUrl() }}"
                            alt="{{ $user->name }}"
                            loading="lazy"
                            class="w-9 h-9 rounded-full object-cover ring-2 {{ $tone['ring'] }} ring-offset-2 ring-offset-[var(--md-sys-color-surface)]"
                        >
                        <div class="absolute -top-1 -right-1 w-5 h-5 rounded-full {{ $tone['chip'] }} flex items-center justify-center
                                    border-2 border-[var(--md-sys-color-surface)] shadow-sm">
                            <span class="material-symbols-rounded leading-none text-[11px] animate-pulse">{{ $tone['icon'] }}</span>
                        </div>
                    </div>

                    <div class="flex flex-col items-start leading-tight">
                        <span class="flex items-center gap-1 text-[12px] font-bold text-[var(--md-sys-color-on-surface)]">
                            {{ $user->name }}
                            <span class="material-symbols-rounded {{ $tone['text'] }} text-[13px] animate-pulse-slow">auto_awesome</span>
                        </span>
                        <span class="text-[10px] {{ $tone['text'] }} font-medium">{{ $tone['label'] }}</span>
                    </div>
                </button>
            @endforeach
        </div>

        <div class="mt-3.5 mr-3 h-px bg-gradient-to-l from-[var(--md-sys-color-outline)] via-[var(--md-sys-color-outline-variant)] to-transparent opacity-75" role="separator"></div>
    </div>
@endif
