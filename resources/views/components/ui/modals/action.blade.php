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
            <x-ui.buttons.form
                variant="none"
                class="modal-btn modal-btn-confirm"
                x-on:click="$wire.{{ $action }}()"
                wire:loading.attr="disabled"
                wire:target="{{ $action }}"
                :loading="$action"
                loadingText="در حال پردازش..."
                aria-label="{{ $confirmText }}"
            >
                {{ $confirmText }}
            </x-ui.buttons.form>
        @endunless
    </x-slot:actions>
</x-ui.modals.base>
