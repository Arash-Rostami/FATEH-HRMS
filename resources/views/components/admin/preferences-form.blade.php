@props(['form', 'action' => null])
<div
    class="relative w-full h-full p-4 md:p-8 overflow-y-auto scrollbar-hide animate-fade">
    <div class="max-w-[88rem] mx-auto">
        <header class="mb-8">
            <x-ui.title
                icon="palette"
                title=" تنظیمات رابط کاربری ادمین پنل"
                :count="$this->getTogglesCount()"
                countLabel="گزینه"
            />

            <small class="text-sm text-[var(--md-sys-color-on-surface-variant)] leading-relaxed">
                تنظیمات مربوط به ظاهر و نحوه تعامل با پنل مدیریت را در این بخش مدیریت کنید.
            </small>
        </header>

        <form wire:submit="save">
            {{ $form }}

            <div
                class="mt-8 flex {{ $action ? 'justify-start' : 'justify-end'  }} ">
                @if($action === null)
                    <button type="submit" class="fi-btn fi-btn-size-md fi-btn-color-primary fi-color-custom inline-flex items-center justify-center gap-2 px-4">
                        <x-filament::icon
                            icon="heroicon-o-document-check"
                            class="w-5 h-5"
                        />
                        <span>ذخیره</span>
                    </button>
                @else
                    {{ $action }}
                @endif
            </div>
        </form>

        <x-filament-actions::modals/>
    </div>
</div>
