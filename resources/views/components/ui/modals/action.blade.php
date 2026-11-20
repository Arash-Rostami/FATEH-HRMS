@props([
    'title',
    'action' => null,
    'confirmText' => 'ذخیره',
    'cancelText' => 'انصراف',
    'readonly' => false,
    'confirmWhen' => null,
    'confirmPrompt' => null,
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
            @php
                $clickExpr = $confirmWhen
                    ? "!({$confirmWhen}) ? \$wire.{$action}() : (" . confirmDialog($action, $title, (string) $confirmPrompt, null, 'dispatch', 'danger') . ')'
                    : "\$wire.{$action}()";
            @endphp
            @if($confirmWhen)
                <span class="hidden" x-on:confirmation-confirmed.window="if ($event.detail.method === '{{ $action }}') { show = false; $wire.{{ $action }}() }"></span>
            @endif
            <x-ui.buttons.form
                variant="none"
                class="modal-btn modal-btn-confirm"
                x-on:click="{{ $clickExpr }}"
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
