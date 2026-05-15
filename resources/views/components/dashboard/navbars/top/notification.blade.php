@props(['title' => 'اعلان‌ها'])

<div class="relative group" {{ $attributes->merge(['class' => '']) }}>
    @livewire('database-notifications')
</div>
