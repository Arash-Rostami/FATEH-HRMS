@use('App\Models\Correspondence')
@php
    $v = $letter && !$registryOnly && !$generatingHere ? $presenter->viewerState($letter, (int) auth()->id()) : null;
    $row = $letter && ($registryOnly || ($v['approve'] ?? false)) ? $presenter->listRow($letter, (int) auth()->id(), false) : null;
    $paper = $letter && !$registryOnly && !$generatingHere && $letter->status !== Correspondence::STATUS_FAILED
        ? $presenter->paperData($letter, 'screen')
        : null;
    $tools = $v && ($v['manage'] || $v['route']) ? $presenter->editorTools($this->people->all()) : null;
    $beside = $v && $v['approve'] && collect($v['diff']['segments'] ?? [])->contains(fn ($segment) => $segment['kind'] !== 'same');
@endphp

<div x-data="correspondenceViewer()" class="flex flex-col gap-4">
    @if($v && $v['next'] !== '')
        <p wire:key="correspondence-next-step" class="print:hidden text-sm font-bold text-[var(--md-sys-color-on-surface-variant)]">{{ $v['next'] }}</p>
    @endif
    @if($generatingHere)
        <div wire:key="correspondence-generating" wire:poll.visible.{{ (int) $poll }}s="pollStatus" role="status" aria-live="polite" aria-busy="true" class="flex flex-col items-center justify-center gap-3 py-16 text-center print:hidden">
            <x-ui.loaders.skeleton.report wire:key="correspondence-generating-skeleton" :kpis="0" :rows="3"/>
            <p class="text-sm font-bold text-[var(--ai-color)]">{{ __('resources/correspondence/strings.viewer.generating') }}</p>
        </div>
    @elseif($letter && $letter->status === Correspondence::STATUS_FAILED)
        <div wire:key="correspondence-viewer-failed" class="print:hidden">
            <x-ui.notice-card icon="error">
                <p class="text-sm font-bold text-[var(--md-sys-color-error)]">{{ __('resources/correspondence/strings.viewer.failed') }}</p>
                <p class="text-xs leading-6 mt-1 text-[var(--md-sys-color-on-surface-variant)]">{{ $presenter->failToast($letter->fail_reason) }}</p>
                @unless($readOnly || $registryOnly || tenantOff('correspondence:ai'))
                    <div wire:key="correspondence-retry" class="mt-3">
                        <x-ui.buttons.form type="button" variant="ai" wire:click="retry" loading="retry">{{ __('resources/correspondence/strings.viewer.retry') }}</x-ui.buttons.form>
                    </div>
                @endunless
            </x-ui.notice-card>
        </div>
    @elseif($letter && $registryOnly)
        <div wire:key="correspondence-registry-card" class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm p-4 flex flex-col gap-1">
            <p class="font-bold"><bdi>{{ $row['subject'] }}</bdi></p>
            <p class="text-sm text-[var(--md-sys-color-on-surface-variant)]">
                {{ $row['status_label'] }} · <bdi>{{ $row['type'] }}</bdi>
                @if($row['number'] !== '') · <bdi>{{ $row['number'] }}</bdi>@endif
                @if($row['stamp'] !== '') · <bdi>{{ $row['stamp'] }}</bdi>@endif
            </p>
            <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">دبیرخانه فقط مشخصات نامه را می‌بیند، نه متن آن.</p>
        </div>
    @elseif($letter)
        <div wire:key="correspondence-paper-wrap" x-data="letterPreviewScale()" data-size="{{ $paper['size'] ?? 'a4' }}" class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm p-3 md:p-5 flex justify-start overflow-hidden print:block print:p-0 print:border-0 print:shadow-none print:overflow-visible">
            @include('livewire.dashboard.correspondence.paper', ['letter' => $letter, 'presenter' => $presenter, 'd' => $paper])
        </div>
    @else
        <div wire:key="correspondence-viewer-empty" class="contents">
            <x-ui.empty icon="mail" title="نامه‌ای باز نیست" description="از فهرست یکی را باز کنید یا نامهٔ تازه بسازید."/>
        </div>
    @endif

    @if($v)
        <div wire:key="correspondence-viewer-tools" class="print:hidden flex flex-col gap-4 {{ $v['approve'] ? 'pb-20 md:pb-0' : '' }}">
            <div class="flex flex-wrap items-center gap-2">
                @if($letter->number)
                    <div wire:key="correspondence-copy-number" class="contents">
                        <x-ui.buttons.copy :text="$letter->number" message="شماره نامه رونوشت شد."/>
                    </div>
                @endif
                <div wire:key="correspondence-copy-link" class="contents">
                    <x-ui.buttons.copy :text="$v['link']" message="نشانی نامه در سامانه رونوشت شد." title="کپی نشانی نامه در سامانه" aria-label="کپی نشانی نامه در سامانه"/>
                </div>
                <x-ui.buttons.form type="button" variant="tonal" icon="reply" wire:click="reply({{ (int) $letter->id }})">پاسخ</x-ui.buttons.form>
                <x-ui.buttons.form type="button" variant="tonal" icon="content_copy" loading="copyAsNew" wire:click="copyAsNew">رونوشت تازه</x-ui.buttons.form>
                @if($v['pdf'])
                    <x-ui.buttons.form wire:key="correspondence-pdf-download" :href="route('correspondence.pdf', $letter->id)" rel="nofollow" class="no-underline">دانلود PDF</x-ui.buttons.form>
                @endif
                <x-ui.buttons.form wire:key="correspondence-print" type="button" variant="tonal" size="icon" icon="print" title="چاپ یا ذخیره PDF" aria-label="چاپ یا ذخیره PDF" class="!h-8 !w-8 !p-0 !rounded-lg" x-on:click="print()"/>
                @if($v['deadline'] !== '')
                    <span wire:key="correspondence-viewer-deadline" class="text-xs {{ $letter->deadline_at?->isPast() ? 'text-[var(--md-sys-color-error)]' : 'text-[var(--md-sys-color-on-surface-variant)]' }}">مهلت <bdi>{{ $v['deadline'] }}</bdi>@if($v['deadline_note'] !== '') · {{ $v['deadline_note'] }}@endif</span>
                @endif
            </div>

            @if($v['reply'] || $v['related'] || $v['files'] !== [])
                <div wire:key="correspondence-links" class="flex flex-wrap items-center gap-2 text-xs">
                    @if($v['reply'])
                        <a wire:key="correspondence-reply-link" href="{{ $v['reply']['url'] }}" class="font-bold text-[var(--md-sys-color-primary)]">در پاسخ به <bdi>{{ $v['reply']['label'] }}</bdi></a>
                    @endif
                    @if($v['related'])
                        <a wire:key="correspondence-related-link" href="{{ $v['related']['url'] }}"><x-ui.badge variant="info">{{ $v['related']['label'] }}</x-ui.badge></a>
                    @endif
                    @foreach($v['files'] as $fileIndex => $fileName)
                        <x-ui.buttons.form wire:key="correspondence-attachment-{{ $fileIndex }}" :href="route('correspondence.attachment', [$letter->id, $fileIndex])" target="_blank" rel="nofollow" variant="tonal" icon="attach_file" class="no-underline max-w-full"><span class="min-w-0 truncate"><bdi>{{ $fileName }}</bdi></span></x-ui.buttons.form>
                    @endforeach
                </div>
            @endif

            @if($v['progress'])
                <div wire:key="correspondence-progress" class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-3 flex flex-col gap-1 text-xs text-[var(--md-sys-color-on-surface-variant)]">
                    @if($v['progress']['remaining'] !== null)
                        <p wire:key="correspondence-progress-remaining" class="font-bold">{{ convertToPersian($v['progress']['remaining']) }} امضا باقی مانده</p>
                    @endif
                    @if($v['progress']['signed'] !== [])
                        <p wire:key="correspondence-progress-signed">امضا کرده‌اند: <bdi>{{ implode('، ', $v['progress']['signed']) }}</bdi></p>
                    @endif
                    @if($v['progress']['pending'] !== [])
                        <p wire:key="correspondence-progress-pending">در انتظار امضا: <bdi>{{ implode('، ', $v['progress']['pending']) }}</bdi></p>
                    @endif
                    @if($v['progress']['unopened'] > 0)
                        <p wire:key="correspondence-progress-unopened">{{ convertToPersian($v['progress']['unopened']) }} نفر هنوز باز نکرده‌اند</p>
                    @endif
                </div>
            @endif

            @if($v['versions'] !== [])
                <div wire:key="correspondence-versions" class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-3 flex flex-col gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-bold">نسخه‌ها</p>
                        @unless($beside)
                            <x-ui.buttons.form wire:key="correspondence-diff-toggle" type="button" variant="ghost" icon="history" x-on:click="toggleDiff()">نمایش تغییرات</x-ui.buttons.form>
                        @endunless
                    </div>
                    @foreach($v['versions'] as $index => $version)
                        <div wire:key="correspondence-version-{{ $index }}" class="flex items-center justify-between gap-2 text-xs">
                            <span>نسخه {{ convertToPersian($index + 1) }} · <bdi>{{ $presenter->name($version['user_id'] ?? null) }}</bdi> · {{ isset($version['at']) ? toJalali($version['at'], 'Y/m/d H:i') : '' }}</span>
                            @unless($readOnly)
                                <x-ui.buttons.form wire:key="correspondence-version-restore-{{ $index }}" type="button" variant="ghost" loading="restoreVersion" x-on:click="{{ confirmDialog('restoreVersion', 'بازگشت', 'این نسخه جایگزین متن فعلی شود؟ در مسیر امضا، امضاهای ثبت‌شده پاک می‌شود.', (int) $index) }}">بازگشت</x-ui.buttons.form>
                            @endunless
                        </div>
                    @endforeach
                    @unless($beside)
                        @include('livewire.dashboard.correspondence.diff', ['toggle' => true])
                    @endunless
                </div>
            @endif

            @if($v['events'] !== [])
                <div wire:key="correspondence-timeline" class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-3 flex flex-col gap-1">
                    @if($v['events_hidden'] > 0)
                        <p wire:key="correspondence-timeline-hidden" class="text-xs text-[var(--md-sys-color-on-surface-variant)]">{{ convertToPersian($v['events_hidden']) }} رویداد قدیمی‌تر نمایش داده نمی‌شود.</p>
                    @endif
                    @foreach($v['events'] as $event)
                        <p wire:key="correspondence-timeline-event-{{ $loop->index }}" class="text-sm text-[var(--md-sys-color-on-surface-variant)]">{{ $event['sentence'] }}</p>
                    @endforeach
                </div>
            @endif

            @if($v['comment'])
                <div wire:key="correspondence-comment" class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-3 flex flex-col gap-2">
                    <x-ui.forms.textarea label="نظر" name="commentText" wire:model="commentText"/>
                    <x-ui.buttons.form type="button" variant="tonal" icon="send" loading="commentLetter" wire:click="commentLetter">ثبت نظر</x-ui.buttons.form>
                </div>
            @endif

            @if($v['delivery'] !== [] || $v['manual'])
                <div wire:key="correspondence-delivery" class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-3 flex flex-col gap-2">
                    <p class="text-sm font-bold">تحویل به گیرنده پس از تأیید</p>
                    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">تأیید نهایی به معنی تحویل نامه نیست. برای هر گیرنده، ارسال را انجام دهید یا تحویل خارج از سامانه را ثبت کنید. هر کسی که پیوند مشاهده را داشته باشد، تا پایان اعتبار یا لغو آن بدون ورود به سامانه می‌تواند نامه را باز کند؛ ایمیل گیرنده هویت بازکنندهٔ پیوند را بررسی نمی‌کند.</p>
                    @foreach($v['delivery'] as $item)
                        <div wire:key="correspondence-delivery-{{ $item['index'] }}" class="flex flex-wrap items-center justify-between gap-2">
                            <div class="min-w-0 flex flex-col gap-0.5">
                                <p class="text-sm"><bdi>{{ $item['name'] }}</bdi> · {{ $item['delivery'] }} · <span class="{{ $v['tones'][$item['state']] ?? $v['tones']['default'] }}">{{ $item['label'] }}</span></p>
                                @if($item['sent_at'] !== '')
                                    <p wire:key="correspondence-delivery-sent-{{ $item['index'] }}" class="text-xs text-[var(--md-sys-color-on-surface-variant)]">ارسال: <bdi>{{ $item['sent_at'] }}</bdi>@if($item['opened_at'] !== '') · <span title="{{ __('resources/correspondence/strings.viewer.opened_note') }}">باز شد:</span> <bdi>{{ $item['opened_at'] }}</bdi>@endif @if($item['expires'] !== '') · اعتبار پیوند تا: <bdi>{{ $item['expires'] }}</bdi>@endif</p>
                                @endif
                                @if($item['error'] !== '')
                                    <p wire:key="correspondence-delivery-error-{{ $item['index'] }}" class="text-xs text-[var(--md-sys-color-error)]">{{ $item['error_label'] }}</p>
                                @endif
                            </div>
                            @if($v['close'])
                                <div wire:key="correspondence-delivery-actions-{{ $item['index'] }}" class="flex flex-wrap gap-1 shrink-0">
                                    @if($item['can_send'])
                                        <x-ui.buttons.form wire:key="correspondence-delivery-send-{{ $item['index'] }}" type="button" variant="tonal" icon="send" loading="sendLink" x-on:click="{{ confirmDialog('sendLink', 'ارسال', 'نامه برای این گیرنده ارسال شود؟', [(int) $letter->id, (int) $item['index']]) }}">ارسال</x-ui.buttons.form>
                                    @endif
                                    @if($item['can_remind'])
                                        <x-ui.buttons.form wire:key="correspondence-delivery-remind-{{ $item['index'] }}" type="button" variant="tonal" icon="forward_to_inbox" loading="remindLink" x-on:click="{{ confirmDialog('remindLink', 'ارسال مجدد', 'نامه دوباره برای این گیرنده ارسال شود؟', [(int) $letter->id, (int) $item['index']]) }}">ارسال مجدد</x-ui.buttons.form>
                                    @endif
                                    @if($item['can_revoke'])
                                        <x-ui.buttons.form wire:key="correspondence-delivery-revoke-{{ $item['index'] }}" type="button" variant="ghost" loading="revokeLink" x-on:click="{{ confirmDialog('revokeLink', 'لغو پیوند', 'دسترسی از پیوند این گیرنده بسته شود؟ فایل‌هایی که قبلاً دریافت کرده، پس گرفته نمی‌شوند.', [(int) $letter->id, (int) $item['index']], 'dispatch', 'danger') }}">لغو پیوند</x-ui.buttons.form>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                    @if(!$v['mail'] && $v['delivery'] !== [])
                        <p wire:key="correspondence-mail-off" class="text-xs text-[var(--md-sys-color-on-surface-variant)]">{{ __('resources/correspondence/strings.viewer.mail_off') }}</p>
                    @endif
                    @if($v['mail'] && $v['close'] && $v['delivery'] !== [])
                        <div wire:key="correspondence-mail-text" class="flex flex-col gap-2">
                            <p class="text-sm font-bold text-[var(--md-sys-color-tertiary)]">متن ایمیل ارسالی</p>
                            <x-ui.forms.input label="عنوان ایمیل (خالی = پیش‌فرض)" name="mailSubject" maxlength="150" wire:model="mailSubject"/>
                            <x-ui.forms.textarea label="متن ایمیل (خالی = پیش‌فرض)" name="mailBody" maxlength="1500" wire:model="mailBody"/>
                            <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">این متن فقط همراه ایمیل می‌رود و متن خود نامه را عوض نمی‌کند. اگر خالی بگذارید، متن آمادهٔ سامانه استفاده می‌شود. پس از ذخیره، برای ارسال ایمیل باید دکمهٔ ارسال گیرنده را بزنید.</p>
                            <x-ui.buttons.form type="button" variant="tonal" icon="mail" loading="saveMailText" wire:click="saveMailText">ذخیره متن ایمیل</x-ui.buttons.form>
                        </div>
                    @endif
                    @if($v['manual'])
                        <div wire:key="correspondence-manual" class="flex flex-col gap-2">
                            <p class="text-sm font-bold text-[var(--md-sys-color-tertiary)]">{{ __('resources/correspondence/strings.delivery.manual') }}</p>
                            <x-ui.forms.textarea label="ثبت تحویل یا پیگیری خارج از سامانه" name="deliveryNote" wire:model="deliveryNote"/>
                            <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">بنویسید چه چیزی، به چه کسی و از چه راهی تحویل داده یا پیگیری شده است؛ مثلاً «نسخهٔ کاغذی به دبیرخانهٔ شرکت تحویل شد». ثبت این توضیح، ایمیل ارسال نمی‌کند.</p>
                            <x-ui.buttons.form type="button" variant="tonal" icon="local_shipping" loading="markDeliveredManually" wire:click="markDeliveredManually">ثبت</x-ui.buttons.form>
                        </div>
                    @endif
                </div>
            @endif

            @if($state['canSave'] && $state['smartOn'] && $v['refineBlocks'] !== [])
                <div wire:key="correspondence-refine" class="rounded-2xl border border-[color-mix(in_srgb,var(--ai-color)_40%,transparent)] bg-[var(--ai-bg)] p-3 flex flex-col gap-2">
                    <x-ui.forms.textarea label="{{ __('resources/correspondence/strings.viewer.refine_prompt') }}" name="refineText" wire:model="refineText"/>
                    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">تغییر موردنظر را بنویسید و دکمهٔ همان بخش را بزنید؛ مثلاً «این بند کوتاه‌تر شود و مبلغ تغییر نکند». بازنویسی از سهمیهٔ هوش مصنوعی استفاده می‌کند؛ نتیجه را پیش از امضا بررسی کنید.</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($v['refineBlocks'] as $block)
                            <x-ui.buttons.form wire:key="correspondence-refine-{{ $block['index'] }}" type="button" variant="ai" loading="refine" wire:click="refine({{ (int) $block['index'] }}, '{{ $block['hash'] }}')">{{ __('resources/correspondence/strings.viewer.refine') }} <bdi>{{ $block['label'] }}</bdi></x-ui.buttons.form>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($beside)
                <div wire:key="correspondence-diff-beside" class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-3 flex flex-col gap-2">
                    <p class="text-sm font-bold">تغییرات نسبت به نسخهٔ قبل</p>
                    @include('livewire.dashboard.correspondence.diff', ['toggle' => false])
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-2">
                @if($v['approve'])
                    <x-ui.buttons.form wire:key="correspondence-approve" type="button" variant="primary" icon="draw" x-on:click="openSign()">{{ $v['approve_label'] }}</x-ui.buttons.form>
                @endif
                @if($v['waiting'])
                    <x-ui.buttons.form wire:key="correspondence-reject" type="button" variant="ghost" x-on:click="openReject()">رد</x-ui.buttons.form>
                @endif
                @if($v['manage'])
                    <x-ui.buttons.form wire:key="correspondence-withdraw" type="button" variant="ghost" x-on:click="{{ confirmDialog('withdrawLetter', 'پس گرفتن', 'نامه به پیش‌نویس برگردد؟ امضاهای ثبت‌شده پاک می‌شوند و برای ادامه باید دوباره آن را برای امضا ارسال کنید.') }}">پس گرفتن</x-ui.buttons.form>
                    <x-ui.buttons.form wire:key="correspondence-reroute" type="button" variant="tonal" icon="alt_route" x-on:click="openModal('correspondence-reroute')">تغییر مسیر</x-ui.buttons.form>
                @endif
                @if($v['close'])
                    <x-ui.buttons.form wire:key="correspondence-void" type="button" variant="ghost" x-on:click="{{ confirmDialog('voidLetter', 'باطل کردن', 'این نامه باطل شود؟ شماره و سابقهٔ آن باقی می‌ماند، اما پیوند مشاهدهٔ متن نامه دیگر باز نمی‌شود. این کار برگشت‌پذیر نیست.', null, 'dispatch', 'danger') }}">باطل کردن</x-ui.buttons.form>
                    <x-ui.buttons.form wire:key="correspondence-correct" type="button" variant="tonal" icon="edit_note" loading="correctLetter" wire:click="correctLetter">ایجاد نامه اصلاحی</x-ui.buttons.form>
                @endif
                @if($v['replace'])
                    <x-ui.buttons.form wire:key="correspondence-supersede-original" type="button" variant="primary" icon="task_alt" loading="supersedeOriginal" x-on:click="{{ confirmDialog('supersedeOriginal', 'جایگزین کردن نامهٔ اصلی', 'نامهٔ اصلی با این نامه جایگزین شود؟') }}">جایگزین کردن نامهٔ اصلی</x-ui.buttons.form>
                @endif
                @if($v['receive'])
                    <x-ui.buttons.form wire:key="correspondence-receive" type="button" variant="primary" icon="move_to_inbox" loading="receiveLetter" wire:click="receiveLetter">ثبت وصول</x-ui.buttons.form>
                @endif
                @if($v['route'])
                    <x-ui.buttons.form wire:key="correspondence-route-open" type="button" variant="primary" icon="forward_to_inbox" x-on:click="openModal('correspondence-route')">ارجاع</x-ui.buttons.form>
                    <x-ui.buttons.form wire:key="correspondence-close-incoming" type="button" variant="tonal" icon="task_alt" loading="closeLetter" x-on:click="{{ confirmDialog('closeLetter', 'بستن', 'رسیدگی به نامه تمام شده و بسته شود؟ پس از بستن، ویرایش و ارجاع دوبارهٔ آن ممکن نیست.') }}">بستن</x-ui.buttons.form>
                    <x-ui.buttons.form wire:key="correspondence-void-incoming" type="button" variant="ghost" loading="voidLetter" x-on:click="{{ confirmDialog('voidLetter', 'ابطال', 'این نامه باطل شود؟ شمارهٔ آن محفوظ می‌ماند.', null, 'dispatch', 'danger') }}">ابطال</x-ui.buttons.form>
                @endif
                @if($v['correct'] && !$correcting)
                    <x-ui.buttons.form wire:key="correspondence-registration-open" type="button" variant="tonal" icon="edit_note" loading="startCorrection" wire:click="startCorrection">اصلاح مشخصات ثبت</x-ui.buttons.form>
                @endif
            </div>

            @if($v['correct'] && $correcting)
                <div wire:key="correspondence-registration-panel" class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-3 flex flex-col gap-2">
                    <x-ui.forms.input label="{{ __('resources/correspondence/strings.fields.subject') }}" name="correction.subject" wire:model="correction.subject"/>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <x-ui.forms.input label="شماره نامهٔ فرستنده" name="correction.their_number" dir="ltr" wire:model="correction.their_number"/>
                        <x-ui.forms.input label="تاریخ نامهٔ فرستنده" name="correction.their_date" dir="ltr" wire:model="correction.their_date"/>
                    </div>
                    <x-ui.forms.input label="فرستنده" name="correction.sender" wire:model="correction.sender"/>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.buttons.form type="button" variant="primary" icon="check" loading="correctRegistration" wire:click="correctRegistration">ثبت اصلاح</x-ui.buttons.form>
                        <x-ui.buttons.form type="button" variant="ghost" wire:click="cancelCorrection">انصراف</x-ui.buttons.form>
                    </div>
                </div>
            @endif

            @if($v['wet'])
                <div wire:key="correspondence-wet-steps" class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-3 flex flex-wrap items-center gap-2">
                    <p class="text-sm font-bold w-full">امضای دستی</p>
                    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] w-full">پس از چاپ واقعی و گرفتن امضا روی کاغذ، انجام هر مرحله را ثبت کنید. این دکمه‌ها فقط سابقه ثبت می‌کنند؛ چاپ یا امضا را انجام نمی‌دهند.</p>
                    <x-ui.buttons.form type="button" variant="tonal" icon="{{ $v['printed'] ? 'task_alt' : 'print' }}" loading="markPrinted" wire:click="markPrinted" :disabled="$v['printed']">۱. چاپ شد</x-ui.buttons.form>
                    <x-ui.buttons.form type="button" variant="tonal" icon="{{ $v['wet_signed'] ? 'task_alt' : 'ink_pen' }}" loading="markWetSigned" wire:click="markWetSigned" :disabled="$v['wet_signed'] || !$v['printed']">۲. امضای دستی ثبت شد</x-ui.buttons.form>
                </div>
            @endif

            @if($v['approve'])
                <div wire:key="correspondence-sign-dialog" class="contents">
                    <x-ui.modals.dialog name="correspondence-sign" title="{{ $v['final'] ? 'تأیید پایانی' : 'تأیید' }}">
                        <div class="flex flex-col gap-3" x-data="correspondenceSignGuard()">
                            @if($v['final'])
                                <p wire:key="correspondence-final-warning" class="text-sm font-bold text-[var(--md-sys-color-error)]">این آخرین امضاست؛ بعد از آن نامه شماره می‌گیرد، قفل می‌شود و دیگر ویرایش نمی‌شود.</p>
                                <p wire:key="correspondence-final-signer" class="text-sm">امضاکنندهٔ پایانی: <bdi>{{ $v['signer'] }}</bdi></p>
                            @endif
                            @if($v['instead'])
                                <div wire:key="correspondence-sign-instead" class="flex flex-col gap-3">
                                    <p class="text-sm">به‌جای <bdi>{{ $v['instead_name'] }}</bdi></p>
                                    <x-ui.forms.input label="کد امضای {{ $v['instead_name'] }}" name="signCode" type="password" wire:model="signCode" autocomplete="new-password"/>
                                    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">کد امضای همین شخص لازم است؛ رمز ورود حساب خودتان را اینجا ننویسید.</p>
                                </div>
                            @endif
                            @if($v['targets'] !== [])
                                <div wire:key="correspondence-sign-targets" class="flex flex-col gap-3">
                                    <x-ui.forms.select label="تأیید به‌جای" name="signTarget" wire:model.live="signTarget">
                                        <option value="0">{{ $v['waiting'] ? 'خودم' : 'انتخاب کنید' }}</option>
                                        @foreach($v['targets'] as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </x-ui.forms.select>
                                    @if($signTarget > 0)
                                        <div wire:key="correspondence-sign-target-code" class="contents">
                                            <x-ui.forms.input label="کد امضای فرد انتخاب‌شده" name="signCode" type="password" wire:model="signCode" autocomplete="new-password"/>
                                            <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">کد امضای فرد انتخاب‌شده لازم است؛ رمز ورود حساب خودتان را اینجا ننویسید.</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                            <x-ui.buttons.form type="button" variant="primary" icon="draw" loading="approveLetter" wire:click="approveLetter">{{ $v['final'] ? 'تأیید نهایی و ثبت شماره' : 'تأیید' }}</x-ui.buttons.form>
                        </div>
                    </x-ui.modals.dialog>
                </div>
            @endif

            @if($v['waiting'])
                <div wire:key="correspondence-reject-dialog" class="contents">
                    <x-ui.modals.dialog name="correspondence-reject" title="رد نامه">
                        <div class="flex flex-col gap-3">
                            <x-ui.forms.textarea label="دلیل رد (الزامی)" name="rejectReason" wire:model="rejectReason"/>
                            <label class="flex items-center gap-2 text-sm">
                                <x-ui.forms.checkbox name="resumeRoute"/>
                                حفظ امضاهای قبلی در صورت تغییر نکردن متن
                            </label>
                            <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">اگر این گزینه روشن باشد و نویسنده نامه را بدون تغییر محتوا دوباره بفرستد، امضاهای دیگران حفظ می‌شود و بررسی از همین مرحله ادامه می‌یابد. هر تغییر در محتوا، امضاهای قبلی را پاک می‌کند.</p>
                            <x-ui.buttons.form type="button" variant="primary" loading="rejectLetter" wire:click="rejectLetter">رد</x-ui.buttons.form>
                        </div>
                    </x-ui.modals.dialog>
                </div>
            @endif

            @foreach(['reroute' => $v['manage'], 'route' => $v['route']] as $kind => $show)
                @if($show)
                    <div wire:key="correspondence-{{ $kind }}-dialog" class="contents">
                        <x-ui.modals.dialog name="correspondence-{{ $kind }}" title="{{ $kind === 'reroute' ? 'تغییر مسیر' : 'ارجاع' }}">
                            <div class="flex flex-col gap-3">
                                @if($kind === 'reroute')
                                    <div wire:key="correspondence-reroute-mode" class="contents">
                                        <x-ui.forms.select label="ترتیب گرفتن امضاها" name="routeMode" wire:model="routeMode">
                                            @foreach($presenter->routeModeLabels() as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </x-ui.forms.select>
                                    </div>
                                @else
                                    <div wire:key="correspondence-route-instruction" class="contents">
                                        <x-ui.forms.textarea label="از گیرندگان چه اقدامی می‌خواهید؟" name="instruction" wire:model="instruction"/>
                                        <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]">ارجاع، دسترسی دیدن نامه و متن این درخواست را به افراد انتخاب‌شده می‌دهد؛ برای آن‌ها نوبت امضا ایجاد نمی‌کند.</p>
                                    </div>
                                @endif
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-bold">{{ $kind === 'reroute' ? 'مسیر امضا' : 'ارجاع به' }}</p>
                                    <x-ui.buttons.form type="button" variant="ghost" icon="add" wire:click="addRouteParty">نفر</x-ui.buttons.form>
                                </div>
                                @include('livewire.dashboard.correspondence.composer', ['partyRows' => $routeParties, 'partyModel' => 'routeParties', 'partyKey' => 'route', 'partyLock' => false, 'partyMove' => false, 'partyRemove' => 'removeRouteParty', 'tools' => $tools])
                                <x-ui.buttons.form type="button" variant="primary" loading="{{ $kind }}Letter" data-method="{{ $kind }}Letter" x-on:click="runAndClose($el)">{{ $kind === 'reroute' ? 'ثبت مسیر تازه' : 'ارجاع' }}</x-ui.buttons.form>
                            </div>
                        </x-ui.modals.dialog>
                    </div>
                @endif
            @endforeach

            @if($v['approve'])
                <div wire:key="correspondence-mobile-bar" class="md:hidden fixed inset-x-0 bottom-20 z-20 print:hidden border-t border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] px-4 py-3 flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-sm font-bold truncate"><bdi>{{ $row['subject'] }}</bdi></p>
                        <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] truncate"><bdi>{{ $row['number'] ?: '—' }}</bdi> · <bdi>{{ $row['awaiting'] }}</bdi></p>
                    </div>
                    <div class="flex gap-1 shrink-0">
                        <x-ui.buttons.form type="button" variant="primary" x-on:click="openSign()">{{ $v['approve_label'] }}</x-ui.buttons.form>
                        @if($v['waiting'])
                            <x-ui.buttons.form wire:key="correspondence-reject-bar" type="button" variant="ghost" x-on:click="openReject()">رد</x-ui.buttons.form>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
