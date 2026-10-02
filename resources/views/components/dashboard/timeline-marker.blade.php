@props([
    'id',
    'date',
    'caption' => null,
    'right' => '-right-10',
    'z' => 'z-40',
    'translateX' => true,
])

<div
    x-show="showTimeline"
    x-transition:enter="transition ease-out duration-300 delay-100"
    x-transition:enter-start="opacity-0 scale-90 translate-x-4"
    x-transition:enter-end="opacity-100 scale-100 {{ $translateX ? 'translate-x-1/2' : '' }}"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 scale-100 {{ $translateX ? 'translate-x-1/2' : '' }}"
    x-transition:leave-end="opacity-0 scale-90 translate-x-4"
    class="absolute top-1/2 {{ $right }} {{ $z }} hidden md:flex flex-col items-center justify-center -translate-y-1/2 {{ $translateX ? 'translate-x-1/2' : '' }} pointer-events-none"
>
    <x-dashboard.timeline-date-bubble :id="$id" :date="$date"/>

    <div
        class="w-8 h-8 rounded-full bg-[var(--md-sys-color-surface-variant)] border-4 border-[var(--md-sys-color-background)] shadow-sm flex items-center justify-center transition-all duration-500"
        :class="activeId == {{ $id }} ? 'scale-125 border-[var(--md-sys-color-primary)]' : ''"
    >
        <div class="w-2.5 h-2.5 rounded-full bg-[var(--md-sys-color-primary)]"></div>
    </div>

    @if($caption !== null)
        <x-dashboard.timeline-caption :id="$id" :text="$caption"/>
    @endif
</div>
