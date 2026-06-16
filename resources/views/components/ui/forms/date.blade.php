@props([
    'label',
    'prefix',
    'icon' => 'calendar_month',
    'startYear' => 1300,
    'endYear' => 1410,
    'bgClass' => 'bg-[var(--md-sys-color-surface-variant)]/20',
])

@php
    $yearRange = $startYear > $endYear ? range($startYear, $endYear, -1) : range($startYear, $endYear);
    $persianMonths = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
@endphp

<div class="col-span-1 md:col-span-2 lg:col-span-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/60 {{ $bgClass }} p-4">
    <div class="flex items-center gap-2 text-[var(--md-sys-color-on-surface-variant)] mb-4">
        @if($icon)
            <span class="material-symbols-rounded text-lg">{{ $icon }}</span>
        @endif
        <span class="text-sm font-bold">{{ $label }}</span>
    </div>
    <div class="grid grid-cols-3 gap-3">
        <x-ui.forms.select label="سال" name="{{ $prefix }}Year" wire:model="{{ $prefix }}Year">
            <option value="">سال</option>
            @foreach($yearRange as $year)
                <option value="{{ $year }}">{{ convertToPersian($year) }}</option>
            @endforeach
        </x-ui.forms.select>

        <x-ui.forms.select label="ماه" name="{{ $prefix }}Month" wire:model="{{ $prefix }}Month">
            <option value="">ماه</option>
            @foreach($persianMonths as $i => $monthName)
                <option value="{{ $i + 1 }}">{{ $monthName }}</option>
            @endforeach
        </x-ui.forms.select>

        <x-ui.forms.select label="روز" name="{{ $prefix }}Day" wire:model="{{ $prefix }}Day">
            <option value="">روز</option>
            @foreach(range(1, 31) as $day)
                <option value="{{ $day }}">{{ convertToPersian($day) }}</option>
            @endforeach
        </x-ui.forms.select>
    </div>
</div>
