@props([
    'title',
    'message',
    'action',
    'confirmText' => 'بله، انجام شود',
    'cancelText' => 'انصراف'
])

<x-dashboard.modal.base
    :title="$title"
    {{ $attributes }}
>
    <!-- Message Content -->
    <p class="mb-8 leading-relaxed">
        {{ $message }}
    </p>

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
                <i class="fas fa-spinner fa-spin"></i> حذف...
            </span>
        </button>
    </x-slot:actions>
</x-dashboard.modal.base>
