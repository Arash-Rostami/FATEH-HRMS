@props([
    'apply',
    'clear' => null,
    'startYear' => 1300,
    'endYear' => 1410,
])

<div class="flex flex-col gap-2">
    @if($slot->isNotEmpty())
        <div class="flex gap-1 px-0.5">
            {{ $slot }}
        </div>
    @endif

    <x-ui.forms.date label="از تاریخ" prefix="from" icon="event" :startYear="$startYear" :endYear="$endYear" />
    <x-ui.forms.date label="تا تاریخ" prefix="to" icon="event" :startYear="$startYear" :endYear="$endYear" />

    <div class="flex gap-2">
        <button type="button"
                x-on:click="$wire.{{ $apply }}(); open = false"
                class="flex-1 rounded-xl bg-[var(--md-sys-color-primary)] py-1.5 text-xs font-medium text-[var(--md-sys-color-on-primary)] transition hover:opacity-90">
            اعمال
        </button>
        @if($clear)
            <button type="button"
                    x-on:click="$wire.{{ $clear }}()"
                    class="rounded-xl px-3 py-1.5 text-xs font-medium text-[var(--md-sys-color-error)] transition hover:bg-[var(--md-sys-color-error)]/10">
                حذف فیلتر
            </button>
        @endif
    </div>
</div>