@props(['text' => '', 'position' => 'bottom'])

<span {{ $attributes->merge(['class' => 'hidden']) }} data-tip="{{ $text }}" data-tip-pos="{{ $position }}" role="tooltip">{{ $slot }}</span>
