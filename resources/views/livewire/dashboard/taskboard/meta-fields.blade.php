@php $customSchema = $this->customSchema(); @endphp

<div x-show="formTab === 'meta'" x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
     class="space-y-4">
    @if(!empty($customSchema))
        <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] px-1">این فیلدها توسط مدیر پروژه برای این پروژه تعریف شده است.</p>

        @foreach($customSchema as $key => $def)
            <x-ui.forms.input label="{{ $def['label'] ?? $key }}" name="form.meta.{{ $key }}"
                              wire:model="form.meta.{{ $key }}" :disabled="$isReadOnly" dir="auto"
                              wire:key="meta-schema-{{ $key }}"/>
        @endforeach
    @else
        <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] px-1">دیتای سفارشی برای این وظیفه؛ بدون طرح پیش‌فرض، کلید و مقدار دلخواه اضافه کنید.</p>

        <div class="space-y-2">
            @forelse($form->meta as $key => $value)
                <div class="flex gap-2 items-center" wire:key="meta-free-{{ $key }}">
                    <div class="flex-1">
                        <x-ui.forms.input label="{{ $key }}" name="form.meta.{{ $key }}"
                                          wire:model="form.meta.{{ $key }}" :disabled="$isReadOnly" dir="auto"/>
                    </div>
                    @unless($isReadOnly)
                        <button type="button" wire:click="removeMetaKey('{{ $key }}')" title="حذف کلید"
                                class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-error)] transition-colors">
                            <span class="material-symbols-rounded text-base">close</span>
                        </button>
                    @endunless
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-[var(--md-sys-color-outline-variant)]/50 py-8 text-center">
                    <span class="material-symbols-rounded text-3xl text-[var(--md-sys-color-outline)]">sell</span>
                    <p class="text-xs text-[var(--md-sys-color-outline)] mt-1">هنوز دیتای سفارشی ثبت نشده است.</p>
                </div>
            @endforelse

            @unless($isReadOnly)
                <div class="flex gap-2 items-end pt-2 border-t border-[var(--md-sys-color-outline-variant)]/30">
                    <div class="w-1/3">
                        <x-ui.forms.input label="کلید (a-z0-9_)" name="newMetaKey" wire:model="newMetaKey" dir="ltr"/>
                    </div>
                    <div class="flex-1">
                        <x-ui.forms.input label="مقدار" name="newMetaValue" wire:model="newMetaValue" dir="auto"/>
                    </div>
                    <button type="button" wire:click="addMetaKey" wire:loading.attr="disabled" wire:target="addMetaKey"
                            class="shrink-0 px-3.5 py-2.5 rounded-lg text-xs font-semibold transition-all duration-150 hover:brightness-110 active:scale-95 disabled:opacity-40 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]">
                        افزودن
                    </button>
                </div>
            @endunless
        </div>
    @endif

    @error('form.meta')
    <p class="text-xs text-[var(--md-sys-color-error)] animate-pulse px-1">{{ $message }}</p>
    @enderror
</div>