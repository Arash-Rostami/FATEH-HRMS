@use('App\Services\Notify\Channels\SmsChannel')
@props(['templates' => [], 'scope' => 'status'])

@unless(tenantOff('notify:sms'))
    @php
        $frame = ['sender' => auth()->user()?->name, 'footer' => SmsChannel::footer()];
        $viewTabs = [
            ['id' => 'write', 'icon' => 'edit_note', 'label' => 'نوشتن', 'alpine' => "view = 'write'", 'activeExpr' => "view === 'write'"],
            ['id' => 'preview', 'icon' => 'smartphone', 'label' => 'پیش‌نمایش موبایل', 'alpine' => "view = 'preview'", 'activeExpr' => "view === 'preview'"],
        ];
    @endphp
    <div wire:key="{{ $scope }}-sms-shell"
         x-data="smsModal($wire.entangle('smsText'), @js($templates), @js($scope), @js($frame))"
         @sms-committed.window="committed()"
         @open-sms.window="open($event.detail)">
        <x-ui.modals.action wire:model="showSmsModal" wire:key="{{ $scope }}-sms-modal"
                            title="ارسال پیامک" action="submitSms" confirm-text="ارسال پیامک" cancel-text="انصراف">
            <div wire:key="{{ $scope }}-sms-body" dir="rtl" class="modal-inner-card !w-full !max-w-none !p-5 space-y-4">
                <div class="flex items-center gap-3 rounded-xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] px-4 py-3">
                    <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">sms</span>
                    <div class="min-w-0">
                        <p class="text-[11.5px] text-[var(--md-sys-color-on-surface-variant)]">گیرنده</p>
                        <p class="text-[13px] font-bold text-[var(--md-sys-color-on-surface)] truncate" x-text="name"></p>
                    </div>
                </div>

                <x-ui.buttons.tab-selector wire:key="{{ $scope }}-sms-tabs" :tabs="$viewTabs" class="!mb-0"
                                           buttonBaseClass="relative px-3 py-1.5 rounded-xl text-xs font-bold transition-all duration-300 z-10 flex items-center gap-1.5 shrink-0 whitespace-nowrap" />

                {{-- WRITE TAB --}}
                <div wire:key="{{ $scope }}-sms-write" x-show="view === 'write'" class="space-y-4">
                    <x-ui.forms.textarea name="smsText" label="متن پیام" icon="edit_note" rows="4" maxlength="140" x-model="t" />
                    @error('smsText')
                    <p wire:key="sms-text-error" x-show="!fresh" class="text-[11.5px] font-bold text-[var(--md-sys-color-error)]">{{ $message }}</p>
                    @enderror
                    @if($templates)
                        <div wire:key="sms-mode-bar" x-show="choices.length" class="flex flex-wrap gap-2">
                            <x-ui.buttons.form wire:key="sms-mode-custom" variant="secondary" icon="edit_note"
                                               class="!h-8 !px-3 !text-xs !rounded-lg [&>span]:!text-[16px] aria-pressed:!bg-[var(--md-sys-color-primary-container)] aria-pressed:!border-[var(--md-sys-color-primary)] aria-pressed:!text-[var(--md-sys-color-on-primary-container)]"
                                               x-bind:aria-pressed="!smart()" @click="custom()">متن دلخواه</x-ui.buttons.form>
                            <x-ui.buttons.form wire:key="sms-mode-smart" variant="ai" icon="auto_awesome"
                                               class="!h-8 !px-3 !text-xs !rounded-lg [&>span]:!text-[16px]"
                                               @click="next()">ساخت هوشمند</x-ui.buttons.form>
                        </div>
                    @endif
                    <div class="flex items-center justify-between gap-3 text-[11.5px] text-[var(--md-sys-color-on-surface-variant)]">
                        <span class="min-w-0 flex-1">پیام با نام شما شروع می‌شود و با نام سامانه پایان می‌یابد.</span>
                        <span dir="ltr" class="tabular-nums font-bold" x-text="(t || '').length + ' / 140'"></span>
                    </div>
                </div>

                {{-- MOBILE SCREEN PREVIEW TAB --}}
                <div wire:key="{{ $scope }}-sms-preview" x-show="view === 'preview'" x-cloak class="space-y-3 pt-1">
                    {{-- Phone Outer Bezel --}}
                    <div class="relative mx-auto w-full max-w-[290px] rounded-3xl border-[6px] border-[color-mix(in_srgb,var(--md-sys-color-on-surface)_18%,var(--md-sys-color-surface-container-highest))] bg-[var(--md-sys-color-surface-container-lowest,var(--md-sys-color-surface))] shadow-[0_14px_34px_-10px_color-mix(in_srgb,var(--md-sys-color-shadow)_28%,transparent)] overflow-hidden select-none">

                        {{-- Side Hardware Buttons (Visual Polish) --}}
                        <div class="pointer-events-none">
                            {{-- Top Dynamic Island / Speaker Notch --}}
                            <div class="absolute top-2 inset-x-0 flex justify-center z-20">
                                <div class="w-20 h-4 rounded-full bg-[color-mix(in_srgb,var(--md-sys-color-on-surface)_85%,black)] flex items-center justify-end px-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[var(--md-sys-color-primary)] opacity-60"></span>
                                </div>
                            </div>

                            {{-- Mobile Status Bar --}}
                            <div dir="ltr" class="flex items-center justify-between px-5 pt-2.5 pb-1.5 text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)]">
                                <span class="tabular-nums">{{ now()->format('H:i') }}</span>
                                <div class="flex items-center gap-1">
                                    <span class="material-symbols-rounded text-[12px]">signal_cellular_alt</span>
                                    <span class="material-symbols-rounded text-[12px]">wifi</span>
                                    <span class="material-symbols-rounded text-[13px] rotate-90">battery_full</span>
                                </div>
                            </div>
                        </div>

                        {{-- Mobile SMS App Header --}}
                        <div class="flex items-center justify-between px-3.5 py-2 bg-[color-mix(in_srgb,var(--md-sys-color-surface-container)_85%,transparent)] backdrop-blur-md border-b border-[var(--md-sys-color-outline-variant)]/30">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="w-7 h-7 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center shrink-0">
                                    <span class="material-symbols-rounded text-[15px]" style="font-variation-settings: 'FILL' 1;">person</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface)] truncate leading-tight" x-text="name || 'گیرنده'"></p>
                                    <p class="text-[9px] text-[var(--md-sys-color-on-surface-variant)] opacity-75">پیامک • اکنون</p>
                                </div>
                            </div>
                            <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-on-surface-variant)] opacity-60">call</span>
                        </div>

                        {{-- Mobile Chat Screen Body --}}
                        <div class="p-3.5 min-h-[185px] max-h-[240px] overflow-y-auto scrollbar-hide flex flex-col justify-end bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-low)_55%,transparent)] space-y-1.5">
                            <div class="text-center mb-1">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-semibold bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] opacity-80">
                                    امروز
                                </span>
                            </div>

                            {{-- Incoming/Outgoing SMS Bubble --}}
                            <div class="relative self-start max-w-[92%] rounded-2xl rounded-tr-xs bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] px-3.5 py-2.5 shadow-xs border border-[color-mix(in_srgb,var(--md-sys-color-primary)_18%,transparent)] select-text">
                                <p x-show="preview()" dir="rtl" class="whitespace-pre-line break-words text-[12px] leading-6" x-text="preview()"></p>
                                <p x-show="!preview()" class="text-[11px] leading-6 opacity-60">هنوز متنی نوشته نشده است.</p>
                                <div class="flex items-center justify-end gap-1 mt-1 opacity-70 text-[9px] tabular-nums">
                                    <span> ارسال شد</span>
                                    <span class="material-symbols-rounded text-[11px]">done_all</span>
                                </div>
                            </div>
                        </div>

                        {{-- Fake Bottom iMessage/Android SMS Input Bar --}}
                        <div class="px-3 py-2 bg-[var(--md-sys-color-surface-container)] border-t border-[var(--md-sys-color-outline-variant)]/30 flex items-center gap-2 pointer-events-none">
                            <div class="flex-1 h-6 rounded-full bg-[var(--md-sys-color-surface-container-highest)]/70 border border-[var(--md-sys-color-outline-variant)]/40 px-3 flex items-center justify-between">
                                <span class="text-[9.5px] text-[var(--md-sys-color-on-surface-variant)] opacity-60">پیامک...</span>
                                {{-- Live SMS Segment Counter inside the phone bar --}}
                                <span class="text-[9px] font-bold text-[var(--md-sys-color-primary)] tabular-nums"
                                      x-text="segments().toLocaleString('fa-IR') + ' پیامک'"></span>
                            </div>
                            <div class="w-6 h-6 rounded-full bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] flex items-center justify-center shrink-0">
                                <span class="material-symbols-rounded text-[13px] -scale-x-100">send</span>
                            </div>
                        </div>

                        {{-- Home Gesture Bar --}}
                        <div class="py-1.5 bg-[var(--md-sys-color-surface-container)] flex justify-center">
                            <div class="w-20 h-1 rounded-full bg-[var(--md-sys-color-on-surface)] opacity-20"></div>
                        </div>
                    </div>

                    {{-- Footer Metadata under the phone --}}
                    <p class="text-center text-[11px] text-[var(--md-sys-color-on-surface-variant)]">
                        طول کل با نام شما و امضای سامانه:
                        <span class="tabular-nums font-bold text-[var(--md-sys-color-on-surface)]" x-text="preview().length.toLocaleString('fa-IR')"></span> نویسه
                        (<span class="tabular-nums font-bold text-[var(--md-sys-color-primary)]" x-text="segments().toLocaleString('fa-IR')"></span> بخش پیامکی)
                    </p>
                </div>
            </div>
        </x-ui.modals.action>
    </div>
@endunless
