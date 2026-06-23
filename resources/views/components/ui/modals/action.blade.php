@props([
    'title',
    'action' => null,
    'confirmText' => 'ذخیره',
    'cancelText' => 'انصراف',
    'readonly' => false,
])

<x-ui.modals.base
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
            {{ $readonly ? 'بستن' : $cancelText }}
        </button>

        @unless($readonly)
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
        @endunless
    </x-slot:actions>
</x-ui.modals.base>
