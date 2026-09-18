@props(['for', 'tooltipPosition' => 'bottom', 'variant' => 'icon'])

<livewire:dashboard.reminder.main
    :for="$for"
    :tooltip-position="$tooltipPosition"
    :variant="$variant"
    wire:key="reminder-{{ strtolower(class_basename($for)) }}-{{ $for->getKey() }}"/>
