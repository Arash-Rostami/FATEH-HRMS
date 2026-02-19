@props([
    'title',
    'action',
    'confirmText' => 'ذخیره',
    'cancelText' => 'انصراف'
])

<x-dashboard.modal.base
    :title="$title"
    {{ $attributes }}
>
    <!-- Form Content Slot -->
    <div class="space-y-4">
        {{ $slot }}
    </div>

    <!-- Actions Slot -->
    <x-slot:actions>
        <button
            type="button"
            class="modal-btn modal-btn-cancel"
            @click="show = false"
        >
            {{ $cancelText }}
        </button>

        <button
            type="button"
            class="modal-btn modal-btn-confirm"
            wire:click="{{ $action }}"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove wire:target="{{ $action }}">{{ $confirmText }}</span>
            <span wire:loading wire:target="{{ $action }}">
                <i class="fas fa-spinner fa-spin"></i> در حال پردازش...
            </span>
        </button>
    </x-slot:actions>
</x-dashboard.modal.base>
