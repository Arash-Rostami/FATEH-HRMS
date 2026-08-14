@props(['title' => 'بازگشت به داشبورد'])

@unless(request()->routeIs('dashboard'))
    <a href="{{ route('dashboard') }}"
       class="group relative w-10 h-10 rounded-xl hover:bg-[var(--md-sys-color-on-primary)]/10 active:bg-[var(--md-sys-color-on-primary)]/20 active:scale-95 transition-all duration-200 flex items-center justify-center"
        {{ $attributes->merge(['class' => '']) }}>
        <span class="material-symbols-rounded text-[24px]">home</span>
        <x-ui.modals.tooltip :text="$title" position="bottom" />
    </a>
@endunless
