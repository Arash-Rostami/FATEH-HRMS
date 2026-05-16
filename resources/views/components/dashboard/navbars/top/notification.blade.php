@props(['title' => 'اعلان‌ها'])

<button
    x-data="{}"
    x-on:click="$dispatch('open-modal', { id: 'database-notifications' })"
    class="group relative w-10 h-10 rounded-full hover:bg-[var(--md-sys-color-on-primary)]/10 active:scale-95 transition-all duration-200 flex items-center justify-center"
    {{ $attributes->merge(['class' => '']) }}>

    <span class="material-symbols-rounded text-[22px]">notifications</span>

    @if(auth()->user()->unreadNotifications()->count() > 0)
        <span class="absolute top-2.5 right-2.5 w-2.5 h-2.5 bg-[var(--md-sys-color-error)] rounded-full ring-2 ring-[var(--md-sys-color-primary)]"></span>
    @endif

    <x-ui.modals.tooltip :text="$title" position="bottom" />
</button>
