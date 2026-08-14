<div class="contents" @open-release-request.window="$wire.open($event.detail?.type)">

    <button type="button" wire:click="open" class="w-10 h-10 rounded-xl hover:bg-[var(--md-sys-color-surface-container-high)]/50 active:bg-[var(--md-sys-color-surface-container-high)] active:scale-95 transition-all duration-200 flex items-center justify-center relative group" title="پشتیبانی و بازخورد">
        <span class="material-symbols-rounded text-[22px] opacity-70 group-hover:opacity-100 transition-opacity">support</span>
    </button>

    <x-ui.modals.action
        wire:model="show"
        wire:key="release-request-modal"
        title="پشتیبانی و بازخورد"
        action="submit"
        confirm-text="ثبت درخواست"
        cancel-text="انصراف"
        :readonly="$activeTab === 'history'"
        class="!max-w-2xl !w-full"
    >
        <div class="modal-inner-card !w-full !max-w-none !p-5 md:!p-6 space-y-5" dir="rtl">

            <div class="flex justify-end -mb-2">
                <button type="button"
                        @click="$dispatch('open-modal', { name: 'release-request-legend' })"
                        title="راهنمای درخواست‌ها"
                        class="flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
                    <span class="material-symbols-rounded text-[16px]">help</span>
                    راهنما
                </button>
            </div>

            <x-ui.buttons.tab-selector
                :tabs="[
                    ['id' => 'submit', 'icon' => 'edit_note', 'label' => 'ثبت درخواست جدید'],
                    ['id' => 'history', 'icon' => 'history', 'label' => 'درخواست‌های من'],
                ]"
                :active-tab="$activeTab"
                :has-a11y="true"
                class="!mb-0"
            />

            @if($activeTab === 'submit')
                <div class="rounded-md border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-variant)]/25 p-4 space-y-3">
                    <span class="block text-right text-[11px] font-bold uppercase tracking-widest text-[var(--md-sys-color-on-surface-variant)]/70">نوع درخواست</span>

                    <nav role="group" aria-label="نوع درخواست" class="flex flex-wrap gap-2">
                        @foreach($this->types as $type)
                            <button type="button"
                                    wire:click="$set('form.type', '{{ $type->value }}')"
                                    aria-pressed="{{ $form->type === $type->value ? 'true' : 'false' }}"
                                    class="relative px-3.5 py-1.5 rounded-md text-xs font-bold transition-all duration-200 flex items-center gap-1.5 border
                        {{ $form->type === $type->value
                            ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-[var(--md-sys-color-primary)] shadow-[0_2px_10px_color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)]'
                            : 'bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)]/60 hover:border-[var(--md-sys-color-primary)]/50 hover:text-[var(--md-sys-color-on-surface)]' }}">
                                <span class="material-symbols-rounded text-sm">{{ $type->getMaterialIcon() }}</span>
                                {{ $type->getLabel() }}
                            </button>
                        @endforeach
                    </nav>

                    @error('form.type')
                    <div id="form-type-error" class="flex items-center gap-1.5 text-[11px] text-[var(--md-sys-color-error)]">
                        <span class="material-symbols-rounded text-sm">error</span>
                        <span>{{ $message }}</span>
                    </div>
                    @enderror
                </div>

                <x-ui.forms.input label="عنوان" name="form.title" wire:model="form.title" icon="title" required />

                <x-ui.forms.textarea label="متن درخواست" name="form.body" wire:model="form.body" icon="notes" rows="5" required />

                <div>
                    <span class="block text-right text-[11px] font-bold uppercase tracking-widest text-[var(--md-sys-color-on-surface-variant)]/70 mb-2">پیوست (اختیاری)</span>

                    <input type="file" multiple wire:model="form.attachments"
                           class="w-full text-xs rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface)] p-2.5 file:ml-2 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-[var(--md-sys-color-primary-container)] file:text-[var(--md-sys-color-on-primary-container)] file:text-xs file:font-bold"
                    >

                    <div wire:loading wire:target="form.attachments" class="text-xs text-[var(--md-sys-color-primary)] mt-2">
                        در حال آپلود...
                    </div>

                    @if(!empty($form->attachments))
                        <ul class="space-y-1.5 mt-2">
                            @foreach($form->attachments as $index => $file)
                                <li class="flex items-center justify-between gap-2 px-3 py-2 rounded-lg bg-[var(--md-sys-color-surface-container)] text-xs">
                                    <span class="flex items-center gap-1.5 text-[var(--md-sys-color-on-surface)] truncate">
                                        <span class="material-symbols-rounded text-sm">upload_file</span>
                                        {{ $file->getClientOriginalName() }}
                                    </span>
                                    <button type="button" wire:click="removeAttachment({{ $index }})"
                                            class="text-[var(--md-sys-color-error)] flex items-center justify-center">
                                        <span class="material-symbols-rounded text-sm">close</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @foreach(['attachments', 'attachments.*'] as $errorKey)
                        @error($errorKey)
                        <div class="flex items-center gap-1.5 text-[11px] text-[var(--md-sys-color-error)] mt-1.5">
                            <span class="material-symbols-rounded text-sm">error</span>
                            <span>{{ $message }}</span>
                        </div>
                        @enderror
                    @endforeach
                </div>
            @else
                <div class="space-y-2 max-h-[50vh] overflow-y-auto custom-scrollbar pr-1" style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;">
                    @forelse($this->myRequests as $item)
                        @php
                            $reqType = \App\Enums\ReleaseRequestType::from($item->type);
                            $reqStatus = \App\Enums\ReleaseRequestStatus::from($item->status);
                        @endphp
                        <div wire:key="release-request-{{ $item->id }}"
                             class="rounded-md border border-[var(--md-sys-color-outline-variant)]/40 p-3 bg-[var(--md-sys-color-surface)]/70 hover:bg-[var(--md-sys-color-surface-variant)]/40 transition-colors">
                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold"
                                      style="background: color-mix(in srgb, {{ $reqType->getMaterialColor() }} 15%, transparent); color: {{ $reqType->getMaterialColor() }};">
                                    <span class="material-symbols-rounded text-xs">{{ $reqType->getMaterialIcon() }}</span>
                                    {{ $reqType->getLabel() }}
                                </span>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold"
                                      style="background: color-mix(in srgb, {{ $reqStatus->getMaterialColor() }} 15%, transparent); color: {{ $reqStatus->getMaterialColor() }};">
                                    <span class="material-symbols-rounded text-xs">{{ $reqStatus->getMaterialIcon() }}</span>
                                    {{ $reqStatus->getLabel() }}
                                </span>
                            </div>
                            <p class="text-xs font-semibold text-[var(--md-sys-color-on-surface)] line-clamp-1" title="{{ $item->title }}">{{ $item->title }}</p>
                            <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] mt-1 line-clamp-2">{{ $item->body }}</p>

                            @if(!empty($item->attachments))
                                <div class="flex flex-wrap gap-1.5 mt-1.5">
                                    @foreach($item->attachments as $attachment)
                                        <a href="{{ asset('storage/' . $attachment['path']) }}" target="_blank"
                                           class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-[var(--md-sys-color-surface-container)] text-[10px] font-medium text-[var(--md-sys-color-primary)] hover:opacity-80 truncate max-w-[160px]">
                                            <span class="material-symbols-rounded text-xs">attach_file</span>
                                            {{ $attachment['name'] ?? basename($attachment['path']) }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            @if(filled($item->response))
                                @php
                                    $isRejected = $reqStatus === \App\Enums\ReleaseRequestStatus::Rejected;
                                    $responseColor = $isRejected ? 'var(--md-sys-color-error)' : 'var(--md-sys-color-primary)';
                                    $responseBg = $isRejected ? 'var(--md-sys-color-error-container)' : 'var(--md-sys-color-primary-container)';
                                @endphp
                                <div class="mt-1.5 rounded-lg border-r-2 px-3 py-2" style="border-color: {{ $responseColor }}; background: color-mix(in srgb, {{ $responseBg }} 30%, transparent);">
                                    <div class="flex items-center gap-1 text-[10px] font-bold mb-0.5" style="color: {{ $responseColor }};">
                                        <span class="material-symbols-rounded text-xs">{{ $isRejected ? 'cancel' : 'forum' }}</span>
                                        پاسخ
                                    </div>
                                    <p class="text-[11px] leading-relaxed text-[var(--md-sys-color-on-surface)]">{{ $item->response }}</p>
                                </div>
                            @endif

                            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mt-1.5 block">{{ toJalali($item->created_at, 'j F Y - H:i') }}</span>
                        </div>
                    @empty
                        <x-ui.empty icon="inbox" title="درخواستی ثبت نشده" description="درخواست‌های پشتیبانی، پیشنهاد و گزارش باگ شما اینجا نمایش داده می‌شود." variant="list" />
                    @endforelse
                </div>

                @if($this->myRequests->hasMorePages())
                    <div class="flex justify-center pt-1">
                        <x-ui.buttons.load-more
                            action="loadMore"
                            text="موارد بیشتر"
                            loading-text="در حال دریافت..."
                            icon="expand_more"
                            class="font-medium text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-surface)] px-4 py-2 rounded-xl border border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:border-[var(--md-sys-color-primary)] text-xs shadow-sm hover:shadow-md"
                        />
                    </div>
                @endif
            @endif

        </div>
    </x-ui.modals.action>

    <x-ui.modals.dialog name="release-request-legend" title="راهنمای درخواست‌ها">
        @include('livewire.dashboard.release-request.legend')
    </x-ui.modals.dialog>

</div>
