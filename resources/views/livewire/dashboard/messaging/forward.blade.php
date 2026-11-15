<x-ui.modals.base
    wire:model="isForwardOpen"
    title="بازفرستادن پیام"
>
    <div class="modal-inner-card !w-full !max-w-none !p-5 md:!p-6 space-y-4" dir="rtl">
        @if($isForwardOpen)
            @if($forwardResultUrl)
                <div wire:key="forward-success" class="space-y-4 text-center" role="status">
                    <span class="material-symbols-rounded text-4xl text-[var(--md-sys-color-success)]">check_circle</span>
                    <p class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">پیام بازفرستاده شد.</p>
                </div>
            @else
                <div wire:key="forward-form" class="space-y-4">
                    <div class="rounded-lg p-3 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)]">
                        <p class="flex items-center gap-1.5 text-xs font-bold mb-2 text-[var(--md-sys-color-on-surface-variant)]">
                            <span class="material-symbols-rounded text-base rotate-180">reply</span>
                            پیش‌نمایش پیام
                        </p>
                        @if(filled($forwardPreview['body'] ?? ''))
                            <p dir="auto" class="text-sm leading-relaxed whitespace-pre-wrap break-words max-h-32 overflow-y-auto">{{ $forwardPreview['body'] }}</p>
                        @endif
                        @foreach($forwardPreview['files'] ?? [] as $file)
                            <p class="flex items-center gap-1.5 mt-2 text-xs min-w-0">
                                <span class="material-symbols-rounded text-base shrink-0">attach_file</span>
                                <span dir="auto" class="truncate">{{ $file }}</span>
                            </p>
                        @endforeach
                    </div>

                    <div>
                        <p class="text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-2">ارسال به</p>
                        <x-ui.buttons.tab-selector
                            :tabs="[
                                ['id' => 'forward-contact', 'label' => 'مخاطبین', 'icon' => 'person', 'alpine' => '$wire.setForwardDestinationType(\'contact\')', 'activeExpr' => '$wire.forwardDestinationType === \'contact\''],
                                ['id' => 'forward-channel', 'label' => 'گروه‌ها', 'icon' => 'group', 'alpine' => '$wire.setForwardDestinationType(\'channel\')', 'activeExpr' => '$wire.forwardDestinationType === \'channel\''],
                            ]"
                            :active-tab="'forward-' . $forwardDestinationType"
                            class="!mb-3"
                        />
                        <x-ui.forms.search name="forwardSearch" model="forwardSearch" placeholder="جستجوی مقصد..."/>
                    </div>

                    @php($forwardRows = $this->forwardCandidates)
                    <x-dashboard.member-picker
                        wire:key="forward-destinations-{{ $forwardDestinationType }}"
                        model="forwardDestinationId"
                        mode="single"
                        :search="false"
                        :server="true"
                        :candidates="array_slice($forwardRows, 0, $forwardLimit)"
                        :has-more="count($forwardRows) > $forwardLimit"
                        load-more-action="loadMoreForwardDestinations"
                        height="max-h-48"
                        empty-title="مقصدی یافت نشد."
                    />
                    @error('forwardDestinationId')
                        <p class="text-xs text-[var(--md-sys-color-error)]" role="alert">{{ $message }}</p>
                    @enderror

                    <x-ui.forms.textarea name="forwardNote" wire:model="forwardNote" label="یادداشت همراه پیام (اختیاری)" rows="2" maxlength="500"/>
                    @error('forwardMessage')
                        <p class="text-xs text-[var(--md-sys-color-error)]" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            @endif
        @endif
    </div>
    <x-slot:actions>
        @if($forwardResultUrl)
            <x-ui.buttons.form variant="none" class="modal-btn modal-btn-confirm" :href="$forwardResultUrl">
                مشاهدهٔ گفتگو
            </x-ui.buttons.form>
        @else
            <button type="button" class="modal-btn modal-btn-cancel" x-on:click="show = false">انصراف</button>
            <x-ui.buttons.form variant="none" class="modal-btn modal-btn-confirm"
                               x-on:click="$wire.forwardMessage()"
                               loading="forwardMessage" loading-text="در حال پردازش...">
                بازفرستادن
            </x-ui.buttons.form>
        @endif
    </x-slot:actions>
</x-ui.modals.base>
