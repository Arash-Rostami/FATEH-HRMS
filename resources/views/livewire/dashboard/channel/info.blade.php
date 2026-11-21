@php
    $p = $this->presenter;
    $channel = $this->activeChannel;
    $header = $p->channelHeader($channel);
    $can = $this->abilities;
    $pendingInvitees = $can['manageMembers'] ? $this->pendingInvitees : [];
@endphp

<div x-show="showInfo"
     x-transition:enter="animate-slide-fade-from-left"
     x-transition:leave="animate-slide-fade-to-left"
     x-on:click.outside="if (!$event.target.closest('.custom-modal, .fancybox__dialog, .fancybox__container, [wire\\:key=channel-ai-entry]')) showInfo = false"
     class="absolute inset-y-0 left-0 z-30 flex w-72 md:w-80 flex-col overflow-hidden rounded-xl border-r border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] shadow-[0_16px_48px_color-mix(in_srgb,var(--md-sys-color-shadow)_24%,transparent)] bg-[var(--md-sys-color-primary-container)] "
     role="dialog"
     aria-label="اطلاعات گروه">

    <div class="flex flex-shrink-0 flex-col gap-3 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-low)_60%,transparent)] px-4 pt-3.5 pb-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="flex h-6 w-6 items-center justify-center rounded-lg"
                      :class="aiOpen ? 'bg-[var(--ai-bg)] text-[var(--ai-text)]' : 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]'">
                    <span class="material-symbols-rounded text-[15px]" x-text="aiOpen ? 'auto_awesome' : 'tag'">tag</span>
                </span>
                <h3 class="text-xs font-bold tracking-tight text-[var(--md-sys-color-on-surface)]" x-text="aiOpen ? 'منشی هوشمند' : 'اطلاعات گروه'">اطلاعات گروه</h3>
            </div>
            <x-ui.modals.close-button close="showInfo = false"/>
        </div>

        <x-ui.buttons.tab-selector
            x-show="!aiOpen"
            hasA11y
            :activeTab="''"
            :buttonBaseClass="'flex-1 justify-center px-2 py-1.5 rounded-lg text-[11px] font-bold transition-all duration-150'"
            :iconBaseClass="'material-symbols-rounded text-[14px]'"
            class="!mb-0 !w-full"
            :tabs="[
                ['id' => 'info', 'icon' => 'info', 'label' => 'مشخصات', 'description' => 'اطلاعات گروه',
                 'alpine' => 'aiOpen = false; if (filesOpen) toggleFiles()', 'activeExpr' => '!filesOpen && !aiOpen'],
                ['id' => 'files', 'icon' => 'folder_open', 'label' => 'پیوست‌ها', 'description' => 'همهٔ فایل‌های ردوبدل‌شدهٔ این گفتگو',
                 'alpine' => 'aiOpen = false; if (!filesOpen) toggleFiles()', 'activeExpr' => 'filesOpen', 'countExpr' => 'filesList.length'],
            ]"/>
    </div>

    <div x-show="!filesOpen && !aiOpen" class="flex flex-1 min-h-0 flex-col justify-between p-4 gap-3 overflow-hidden">
        <div class="flex flex-col gap-2.5 min-h-0 overflow-y-auto contact-scrollbar">
            <div class="flex items-center gap-3 rounded-2xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[var(--md-sys-color-surface-container-low)] p-3">
                <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl shadow-sm bg-[linear-gradient(135deg,var(--md-sys-color-primary),var(--md-sys-color-secondary))] text-[var(--md-sys-color-on-primary)]">
                    <span class="material-symbols-rounded text-2xl">{{ $header['type_icon'] }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-[var(--md-sys-color-on-surface)]">{{ $header['name'] }}</p>
                    <div class="mt-1 flex items-center gap-1.5">
                        <span class="inline-flex items-center rounded-md bg-[var(--md-sys-color-secondary-container)] px-2 py-0.5 text-[10px] font-bold text-[var(--md-sys-color-on-secondary-container)]">
                            {{ $header['type_label'] }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div class="flex items-center gap-2.5 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] bg-[var(--md-sys-color-surface-container-low)] p-2.5">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-base" aria-hidden="true">groups</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">اعضا</p>
                        <p class="truncate text-xs font-bold text-[var(--md-sys-color-on-surface)]">{{ convertToPersian($header['members_count']) }} نفر</p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] bg-[var(--md-sys-color-surface-container-low)] p-2.5">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-base" aria-hidden="true">person</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]">مالک گروه</p>
                        <p class="truncate text-xs font-bold text-[var(--md-sys-color-on-surface)]" title="{{ $header['owner_name'] }}">{{ $header['owner_name'] }}</p>
                    </div>
                </div>
            </div>

            @if($header['description'])
                <div class="rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] bg-[var(--md-sys-color-surface-container-low)] px-3 py-2">
                    <p class="mb-0.5 text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)]">توضیحات</p>
                    <p class="max-h-16 overflow-y-auto contact-scrollbar text-xs leading-relaxed whitespace-pre-wrap text-[var(--md-sys-color-on-surface)]">{{ $header['description'] }}</p>
                </div>
            @endif

            @if($pendingInvitees)
                <div wire:key="channel-info-pending" class="flex min-h-0 flex-1 flex-col rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] bg-[var(--md-sys-color-surface-container-low)] p-2.5 overflow-hidden">
                    <div class="mb-2 flex flex-shrink-0 items-center justify-between gap-1" title="کاربرانی که دعوت شده‌اند اما هنوز یک‌بار وارد گروه نشده‌اند">
                        <span class="flex items-center gap-1 text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)]">
                            <span class="material-symbols-rounded text-[13px] text-[var(--md-sys-color-primary)]" aria-hidden="true">hourglass_top</span>
                            <span>در انتظار ورود ({{ convertToPersian((string) count($pendingInvitees)) }})</span>
                        </span>
                        <span class="text-[9px] text-[var(--md-sys-color-on-surface-variant)] opacity-75">حذف خودکار پس از ورود</span>
                    </div>

                    <div class="flex-1 min-h-0 space-y-1.5 overflow-y-auto contact-scrollbar pe-0.5">
                        @foreach($pendingInvitees as $invitee)
                            <div class="flex items-center gap-2 rounded-lg bg-[var(--md-sys-color-surface)] px-2 py-1.5 border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_25%,transparent)]">
                                <div class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-[var(--md-sys-color-secondary-container)] text-[10px] font-bold text-[var(--md-sys-color-on-secondary-container)]" aria-hidden="true">
                                    {{ mb_substr($invitee['name'], 0, 1) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[11px] font-semibold text-[var(--md-sys-color-on-surface)]">{{ $invitee['name'] }}</p>
                                    @if($invitee['cancellable'])
                                        <p class="truncate text-[9px] text-[var(--md-sys-color-on-surface-variant)] opacity-80">هنوز وارد گروه نشده است</p>
                                    @else
                                        <p class="truncate text-[9px] text-[var(--md-sys-color-on-surface-variant)] opacity-80">دعوت دیده شده است و دیگر نمی‌توانید آن را لغو کنید</p>
                                    @endif
                                </div>

                                @if($invitee['cancellable'])
                                    <button type="button"
                                            x-on:click="{{ confirmDialog('cancelInvite', 'لغو دعوت', 'لغو دعوت ' . $invitee['name'], [$header['id'], $invitee['id']], 'dispatch', 'danger') }}"
                                            class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-md bg-[color-mix(in_srgb,var(--md-sys-color-error-container)_50%,transparent)] text-[var(--md-sys-color-error)] transition-all hover:!bg-[var(--md-sys-color-error-container)] active:scale-90"
                                            title="لغو دعوت"
                                            aria-label="لغو دعوت {{ $invitee['name'] }}">
                                        <span class="material-symbols-rounded text-[14px]" aria-hidden="true">close</span>
                                    </button>
                                @else
                                    <span class="flex h-6 w-6 flex-shrink-0 cursor-not-allowed items-center justify-center rounded-full bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] opacity-60"
                                          title="این فرد دعوت را دیده است. برای برداشتن دسترسی از «مدیریت اعضا» استفاده کنید.">
                                        <span class="material-symbols-rounded text-[14px]" aria-hidden="true">mark_email_read</span>
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        @if($can['manageMembers'])
            <div wire:key="channel-info-tools" class="flex flex-shrink-0 flex-col gap-2 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] pt-3">
                <button x-on:click="openManageMembers({{ $header['id'] }})"
                        type="button"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-surface-container-high)] px-3 py-2 text-xs font-bold text-[var(--md-sys-color-on-surface)] transition-all hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-[0.98]">
                    <span class="material-symbols-rounded text-[17px]">group_add</span>
                    <span>مدیریت اعضا</span>
                </button>

                @if($can['manageAdmins'])
                    <button wire:key="channel-info-admins"
                            x-on:click="openManageAdmins({{ $header['id'] }})"
                            type="button"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-surface-container-high)] px-3 py-2 text-xs font-bold text-[var(--md-sys-color-on-surface)] transition-all hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-[0.98]">
                        <span class="material-symbols-rounded text-[17px]">shield_person</span>
                        <span>مدیران گروه</span>
                    </button>
                @endif

                @if($can['changeType'])
                    <div wire:key="channel-info-type">
                        @if($header['type'] === 'open')
                            <button wire:key="channel-info-type-private"
                                    type="button"
                                    x-on:click="doChangeType({{ $header['id'] }}, 'private')"
                                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-surface-container-high)] px-3 py-2 text-xs font-bold text-[var(--md-sys-color-on-surface)] transition-all hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-[0.98]">
                                <span class="material-symbols-rounded text-[17px]">lock</span>
                                <span>تبدیل به گروه خصوصی</span>
                            </button>
                        @else
                            <button wire:key="channel-info-type-open"
                                    type="button"
                                    x-on:click="{{ confirmDialog('changeChannelType', 'تبدیل به گروه عمومی', 'با عمومی‌شدن، همهٔ کاربران می‌توانند عضو شوند و همهٔ پیام‌های قبلی را ببینند. ادامه می‌دهید؟', [$header['id'], 'open'], 'dispatch', 'danger') }}"
                                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-surface-container-high)] px-3 py-2 text-xs font-bold text-[var(--md-sys-color-on-surface)] transition-all hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-[0.98]">
                                <span class="material-symbols-rounded text-[17px]">campaign</span>
                                <span>تبدیل به گروه عمومی</span>
                            </button>
                        @endif
                    </div>
                @endif

                @if($can['setKey'])
                <div wire:key="channel-info-key" class="flex flex-col gap-2">
                    <button x-show="!keyOpen"
                            x-on:click="openChannelKey()"
                            type="button"
                            style="display: none;"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-surface-container-high)] px-3 py-2 text-xs font-bold text-[var(--md-sys-color-on-surface)] transition-all hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-[0.98]">
                        <span class="material-symbols-rounded text-[17px]">lock</span>
                        <span>کلید دسترسی گروه</span>
                    </button>

                    <div x-show="keyOpen"
                         style="display: none;"
                         class="flex flex-col gap-2 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-surface-container-high)] p-2.5">
                        <p class="text-[11px] font-bold text-[var(--md-sys-color-on-surface)]">کلید دسترسی گروه</p>
                        <p class="text-[10px] leading-5 text-[var(--md-sys-color-on-surface-variant)]">مدیران سامانه برای دیدن این گروه در پنل مدیریت باید این کلید را وارد کنند. دست‌کم ۸ کاراکتر؛ برای برداشتن کلید، «حذف کلید» را بزنید.</p>
                        <x-ui.forms.input type="password" name="channel_key" label="کلید جدید" icon="key" x-model="channelKey" autocomplete="new-password"/>
                        <div class="flex items-center justify-end gap-1.5">
                            <button type="button"
                                    x-on:click="closeChannelKey()"
                                    class="rounded-lg bg-[color-mix(in_srgb,var(--md-sys-color-on-surface)_8%,transparent)] px-3 py-1 text-[11px] font-semibold transition-all hover:brightness-90 active:scale-95">
                                انصراف
                            </button>
                            <button type="button"
                                    x-on:click="saveChannelKey({{ $header['id'] }}, true)"
                                    wire:loading.attr="disabled"
                                    wire:target="saveChannelKey"
                                    class="rounded-lg bg-[color-mix(in_srgb,var(--md-sys-color-error-container)_65%,transparent)] px-3 py-1 text-[11px] font-semibold text-[var(--md-sys-color-on-error-container)] transition-all hover:brightness-105 active:scale-95">
                                حذف کلید
                            </button>
                            <x-ui.buttons.form x-on:click.prevent="saveChannelKey({{ $header['id'] }})"
                                               x-bind:disabled="channelKey.length < 8"
                                               wire:loading.attr="disabled"
                                               wire:target="saveChannelKey"
                                               loading="saveChannelKey"
                                               class="h-auto rounded-lg bg-[var(--md-sys-color-primary)] px-3 py-1 text-[11px] font-bold text-[var(--md-sys-color-on-primary)] transition-all hover:brightness-110">
                                ثبت کلید
                            </x-ui.buttons.form>
                        </div>
                    </div>
                </div>
                @endif

                @if($can['delete'] && (int) $header['members_count'] === 1 && $channel->isProjectLinked())
                    <p wire:key="channel-info-project-linked" class="text-center text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-80">
                        گفتگوی پروژه است و با حذف پروژه حذف می‌شود.
                    </p>
                @elseif($can['delete'] && (int) $header['members_count'] === 1)
                    <div wire:key="channel-info-delete-group">
                        <button type="button"
                                x-on:click="{{ confirmDialog('deleteChannel', 'حذف گروه', 'این گروه حذف خواهد شد. مطمئن هستید؟', $header['id'], 'dispatch', 'danger') }}"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-[color-mix(in_srgb,var(--md-sys-color-error-container)_65%,transparent)] px-3 py-2 text-xs font-bold text-[var(--md-sys-color-on-error-container)] transition-all hover:brightness-105 active:scale-[0.98]">
                            <span class="material-symbols-rounded text-[17px]">delete_forever</span>
                            <span>حذف گروه</span>
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>

    @if(channelAiOn())
        <div x-show="aiOpen" x-cloak class="flex flex-1 min-h-0 flex-col overflow-hidden bg-[var(--md-sys-color-surface)]" role="tabpanel" aria-label="منشی هوشمند">
            <div class="flex min-h-0 flex-1 flex-col gap-3 overflow-y-auto contact-scrollbar p-4">
                <div x-show="aiMeter" x-cloak x-html="aiMeter" aria-live="polite"></div>
                <p x-show="!aiMeter" x-cloak class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">سهمیه نمایش داده نشد.</p>

                <div class="flex items-start gap-2 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-warning)_35%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-warning)_12%,transparent)] px-3 py-2">
                    <span class="material-symbols-rounded mt-0.5 text-[15px] text-[var(--md-sys-color-warning)]" aria-hidden="true">shield_person</span>
                    <p class="text-[11px] leading-5 text-[var(--md-sys-color-on-surface)]"><strong>برای گفتگوهای خصوصی یا محرمانه از منشی استفاده نکنید.</strong> متن پیام‌ها، از جمله پیام سنجاق‌شده، به سرویس هوش مصنوعی فرستاده می‌شود.</p>
                </div>

                <div x-show="!aiAnswer" x-cloak class="flex flex-col gap-2">
                    @foreach(config('ai.channel.modes') as $key => $mode)
                        @continue($key === 'ask')
                        <x-ui.buttons.form wire:key="channel-ai-mode-{{ $key }}" variant="ai" class="w-full !justify-start" x-on:click="askAi('{{ $key }}')" x-bind:disabled="aiBusy">{{ $mode['label'] }}</x-ui.buttons.form>
                    @endforeach
                </div>

                <x-ui.buttons.form x-show="aiAnswer" x-cloak variant="ghost" icon="restart_alt" class="w-full !justify-start" x-on:click="aiReset()" x-bind:disabled="aiBusy">گزینهٔ دیگر</x-ui.buttons.form>

                <div class="flex flex-col gap-2" role="status" aria-live="polite">
                    <div x-show="aiBusy" x-cloak class="relative overflow-hidden rounded-xl border border-[color-mix(in_srgb,var(--ai-color)_40%,transparent)] bg-[var(--md-sys-color-surface)] px-3 py-2.5">
                        <x-ui.loaders.ai-shimmer class="rounded-xl"/>
                        <p class="relative text-[11px] leading-5 text-[var(--ai-text)]">در حال پاسخ‌گویی… تا حدود ۲ دقیقه. درخواست پس از بستن پنل هم ادامه دارد.</p>
                    </div>

                    <div x-show="aiAnswer" x-cloak class="flex flex-col gap-1.5" :class="aiBusy ? 'opacity-50' : ''">
                        <div class="whitespace-pre-line rounded-xl border border-[color-mix(in_srgb,var(--ai-color)_40%,transparent)] bg-[var(--ai-bg)] px-3 py-2.5 text-xs leading-6 text-[var(--ai-text)]" x-text="aiAnswer"></div>
                        <p x-show="aiNote" x-cloak class="px-1 text-[11px] leading-5 text-[var(--md-sys-color-on-surface-variant)]" x-text="aiNote"></p>
                    </div>

                    <p x-show="aiMessage && !aiBusy" x-cloak
                       class="rounded-xl px-3 py-2 text-[11px] leading-5"
                       :class="aiSoft ? 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]' : 'bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]'"
                       x-text="aiMessage"></p>
                </div>
            </div>

            <div class="flex flex-shrink-0 flex-col gap-2 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[var(--md-sys-color-surface-container-low)] p-3">
                <x-ui.forms.input name="channel_ai_question" label="پرسش دربارهٔ ۷ روز اخیر" x-model="aiQuestion" x-on:keydown.enter="askAi('ask')" x-bind:disabled="aiBusy" maxlength="{{ config('ai.channel.question_max') }}"/>
                <x-ui.buttons.form variant="ai" class="w-full" x-on:click="askAi('ask')" x-bind:disabled="aiBusy">بپرس</x-ui.buttons.form>
            </div>
        </div>
    @endif

    @include('livewire.dashboard.messaging.files')
</div>
