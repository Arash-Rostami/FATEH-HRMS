@php
    $skipped = $report !== null ? $presenter->skippedFiles($report) : [];
    $notes = $report !== null ? $presenter->notes($report) : [];
    $versions = $report !== null ? $presenter->versions($report) : [];
@endphp

<div class="flex flex-col gap-4 print:gap-3">
    <p wire:key="ai-report-live" class="sr-only" role="status" aria-live="polite">
        @if($report === null)@elseif($report->status === \App\Models\AiReport::STATUS_GENERATING)در حال تولید گزارش…@elseif($report->status === \App\Models\AiReport::STATUS_FAILED)ساخت گزارش ناموفق بود@elseگزارش آماده است@endif
    </p>
    @if($report !== null && $readOnly)
        <div wire:key="ai-report-recipient-banner" class="print:hidden">
            <x-ui.notice-card icon="forward_to_inbox">
                <p class="text-xs leading-6 text-[var(--md-sys-color-on-surface-variant)]">این گزارش را <bdi>{{ $report->user?->name ?? 'همکار شما' }}</bdi> برای شما فرستاده است و فقط خواندنی است.</p>
            </x-ui.notice-card>
        </div>
    @endif
    @if($report !== null && $owner && !$report->active)
        <div wire:key="ai-report-inactive-notice" class="print:hidden">
            <x-ui.notice-card icon="visibility_off">
                <p class="text-xs leading-6 text-[var(--md-sys-color-on-surface-variant)]">این گزارش غیرفعال است؛ فقط شما آن را می‌بینید و گیرندگان به آن دسترسی ندارند. با «فعال‌سازی دوباره» در پایین صفحه برمی‌گردد.</p>
            </x-ui.notice-card>
        </div>
    @endif
    @if($report === null)
        <div wire:key="ai-report-viewer-empty" class="contents">
            <x-ui.empty icon="article" fill title="گزارشی باز نیست"
                         :description="$activeTab === 'compose' ? 'درخواست خود را در فرم بنویسید یا از فهرست کناری یک گزارش را باز کنید.' : 'درخواست خود را در فرم بنویسید یا از زبانهٔ «جدول گزارش‌ها» یک گزارش را باز کنید.'">
                <div class="flex justify-center">
                    <x-ui.buttons.form type="button" variant="tonal" icon="arrow_downward"
                                       x-on:click="$dispatch('ai-report-open'); document.getElementById('ai-report-composer')?.scrollIntoView({ behavior: 'smooth' })">
                        نوشتن درخواست
                    </x-ui.buttons.form>
                </div>
            </x-ui.empty>
        </div>
    @elseif($report->status === \App\Models\AiReport::STATUS_GENERATING)
        <div wire:key="ai-report-viewer-generating" wire:poll.visible.{{ config('ai_report.poll_seconds') }}s="pollStatus" role="status" aria-live="polite" aria-busy="true"
             class="flex flex-col items-center justify-center gap-3 py-16 text-center print:hidden">
            <x-ui.loaders.skeleton.report wire:key="ai-report-generating-skeleton"/>
            <p class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">در حال تولید گزارش…</p>
            <div wire:key="ai-report-generating-eta" x-data="{ slow: false }" x-init="setTimeout(() => slow = true, {{ (int) config('ai_report.slow_seconds') * 1000 }})">
                <p x-show="!slow" class="text-xs text-[var(--md-sys-color-on-surface-variant)]">معمولاً {{ $limits['eta'] }} ثانیه طول می‌کشد؛ این صفحه خودکار به‌روز می‌شود و می‌توانید آن را ببندید؛ نتیجه در فهرست گزارش‌هایتان می‌ماند.</p>
                <p x-show="slow" x-cloak class="text-xs text-[var(--md-sys-color-on-surface-variant)]">کمی طول کشیده؛ می‌توانید صفحه را ترک کنید، گزارش در فهرست می‌ماند.</p>
            </div>
            @php($generatingVersion = $report->lastVersion())
            @if(filled($generatingVersion['prompt'] ?? ''))
                <p wire:key="ai-report-generating-echo" class="max-w-xl w-full text-start text-xs leading-6 text-[var(--md-sys-color-on-surface-variant)]" dir="auto">{{ \Illuminate\Support\Str::limit($generatingVersion['prompt'], 160) }}</p>
            @endif
            @if(!empty($generatingVersion['files'] ?? []))
                <p wire:key="ai-report-generating-files" class="max-w-xl w-full text-start text-[11px] leading-5 text-[var(--md-sys-color-on-surface-variant)]"><bdi>{{ implode('، ', $generatingVersion['files']) }}</bdi></p>
            @endif
        </div>
    @elseif($report->status === \App\Models\AiReport::STATUS_FAILED)
        @php($lastVersion = $report->lastVersion())
        @php($lastPrompt = $lastVersion['prompt'] ?? '')
        <div wire:key="ai-report-viewer-failed" class="flex flex-col gap-4 print:gap-3">
            <div class="print:hidden">
            <x-ui.notice-card icon="error">
                <p class="text-sm font-bold text-[var(--md-sys-color-error)]">ساخت گزارش ناموفق بود</p>
                <p class="text-xs leading-6 mt-1 text-[var(--md-sys-color-on-surface-variant)]">{{ $presenter->failReason($report->fail_reason) }}</p>
                <p class="text-xs leading-6 mt-0.5 text-[var(--md-sys-color-on-surface-variant)]">{{ $presenter->failHint($report->fail_reason) }}</p>
                @if($owner && filled($lastPrompt))
                    @php($noRefund = in_array($report->fail_reason, config('ai_report.no_refund'), true))
                    @if($noRefund)
                        <p wire:key="ai-report-retry-no-refund" class="mt-2 text-[11px] leading-5 text-[var(--md-sys-color-on-surface-variant)]">لطفاً درخواست را کوتاه‌تر یا دوباره بنویسید و از دکمهٔ «اصلاح گزارش» استفاده کنید؛ «تلاش دوباره» برای این خطا جوابی ندارد.</p>
                    @else
                        <div wire:key="ai-report-retry" class="mt-3 flex flex-col gap-1.5">
                            @if(!empty($lastVersion['files']))
                                <p wire:key="ai-report-retry-files-note" class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">فایل‌های پیوست پس از تولید نگه داشته نمی‌شوند؛ برای تلاش دوباره، آن‌ها را دوباره پیوست کنید.</p>
                            @endif
                            <x-ui.buttons.form type="button" icon="autorenew" wire:click="retry" loading="retry">تلاش دوباره</x-ui.buttons.form>
                            <p wire:key="ai-report-retry-quota-note" class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">این تلاش دوباره یک سهمیه مصرف می‌کند؛ سهمیهٔ تلاش ناموفق قبلی برگردانده شده است.</p>
                        </div>
                    @endif
                @endif
            </x-ui.notice-card>
            </div>

            @if(is_array($report->spec) && $report->spec !== [])
                <div wire:key="ai-report-failed-last-version" class="contents">
                    <x-dashboard.ai-report :spec="$report->spec" :dir="$presenter->dir($report->spec)" :author="$report->user?->name"/>
                </div>
            @endif
        </div>
    @else
        <div wire:key="ai-report-viewer-ready" class="flex flex-col gap-4 print:gap-3">
            @if($owner && (!empty($skipped) || !empty($notes)))
                <div wire:key="ai-report-skipped-strip" class="print:hidden">
                    <x-ui.notice-card icon="warning">
                        @if(!empty($skipped))
                            <div wire:key="ai-report-skipped-list" class="contents">
                                <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">این فایل‌ها خوانده نشدند و در گزارش لحاظ نشدند:</p>
                                <ul class="mt-1.5 flex flex-col gap-1">
                                    @foreach($skipped as $file)
                                        <li class="text-xs text-[var(--md-sys-color-on-surface-variant)]"><bdi>{{ $file['name'] }}</bdi> — {{ $file['label'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if(!empty($notes))
                            <ul wire:key="ai-report-notes-list" class="{{ !empty($skipped) ? 'mt-2 ' : '' }}flex flex-col gap-1">
                                @foreach($notes as $note)
                                    <li class="text-xs text-[var(--md-sys-color-on-surface-variant)]"><bdi>{{ $note }}</bdi></li>
                                @endforeach
                            </ul>
                        @endif
                    </x-ui.notice-card>
                </div>
            @endif

            @include('livewire.dashboard.ai-report.letter')

            <x-dashboard.ai-report :spec="$report->spec" :dir="$presenter->dir($report->spec)" :author="$report->user?->name"
                                   :editable="$owner && $presenter->canRefine($report)" :manual="$presenter->canRevert($report)"/>

            @if($owner)
                <div wire:key="ai-report-owner-strip" class="flex flex-col gap-3 print:hidden">
                    @php($original = $presenter->originalPrompt($report))
                    @if($report->forked_from === null && $original !== null && !$original['trimmed'])
                        <div wire:key="ai-report-reuse" class="contents">
                            <x-ui.buttons.form type="button" wire:click="reuse" loading="reuse" variant="tonal" icon="edit_note" class="self-start"
                                               x-on:click="document.getElementById('ai-report-composer')?.scrollIntoView({ behavior: 'smooth' })">استفادهٔ دوباره از این درخواست</x-ui.buttons.form>
                        </div>
                    @endif

                    @if($presenter->canSave($report))
                        @if($report->saved_at === null)
                            <div wire:key="ai-report-save" class="contents">
                                <x-ui.buttons.form wire:click="save" loading="save" variant="secondary" icon="bookmark_added" class="self-start">ذخیره گزارش</x-ui.buttons.form>
                            </div>
                        @else
                            <div wire:key="ai-report-saved-chip" class="flex items-center gap-1.5 self-start px-2.5 py-1 rounded-lg text-[11px] font-bold bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)]">
                                <span class="material-symbols-rounded text-[14px]">bookmark_added</span>
                                ذخیره‌شده
                            </div>
                        @endif
                    @endif

                    @if($presenter->canRename($report))
                        <div wire:key="ai-report-rename" class="flex items-end gap-2">
                            <div class="flex-1 min-w-0">
                                <x-ui.forms.input label="عنوان گزارش" name="titleDraft" wire:model="titleDraft" wire:keydown.enter="rename" icon="edit" :maxlength="(int) config('ai_report.spec.short')"/>
                            </div>
                            <x-ui.buttons.form variant="secondary" wire:click="rename" loading="rename" class="whitespace-nowrap rounded-xl">ذخیرهٔ عنوان</x-ui.buttons.form>
                        </div>
                    @elseif($presenter->canSave($report) && !empty($report->recipients))
                        <p wire:key="ai-report-shared-frozen-note" class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">برای تغییر عنوان یا ویرایش دستی، اشتراک را بردارید.</p>
                    @endif

                    @if(count($versions) > 1 && $presenter->canRevert($report))
                        <div wire:key="ai-report-versions-rail" class="flex flex-wrap items-center gap-1.5">
                            <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-on-surface-variant)]">history</span>
                            @foreach($versions as $version)
                                <button type="button" wire:key="ai-report-version-{{ $version['index'] }}"
                                        @if($version['is_current'] || ! $version['has_spec']) disabled @endif
                                        wire:click="revert({{ $version['index'] }})"
                                        title="{{ $version['stamp'] }}{{ $version['type'] !== null ? ' · ' . $version['type'] : '' }}"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-colors {{ $version['is_current'] ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)]' }}">
                                    {{ $version['label'] }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif

    @if($report !== null && $owner && $report->status !== \App\Models\AiReport::STATUS_GENERATING)
        <div wire:key="ai-report-delete-strip" class="flex flex-wrap items-center gap-2 print:hidden">
            <button type="button" wire:key="ai-report-active-toggle" wire:click="toggleActive" wire:loading.attr="disabled" wire:target="toggleActive"
                    title="{{ $report->active ? 'گزارش از دسترس گیرندگان خارج می‌شود و ' . ($activeTab === 'compose' ? 'در فهرست کناری، زبانهٔ «غیرفعال» می‌ماند' : 'در زبانهٔ جدول زیر «غیرفعال‌ها» می‌ماند') : 'گزارش دوباره برای شما و گیرندگان در دسترس می‌شود' }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] transition-colors">
                <span class="material-symbols-rounded text-[14px]">{{ $report->active ? 'visibility_off' : 'visibility' }}</span>
                {{ $report->active ? 'غیرفعال‌کردن گزارش' : 'فعال‌سازی دوباره' }}
            </button>
            <button type="button"
                    x-on:click="{{ confirmDialog('delete', 'حذف گزارش', 'این گزارش برای همیشه حذف می‌شود و گیرندگان دیگر به آن دسترسی ندارند. مطمئن هستید؟', null, 'dispatch', 'danger') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold text-[var(--md-sys-color-error)] hover:bg-[var(--md-sys-color-error-container)] transition-colors">
                <span class="material-symbols-rounded text-[14px]">delete</span>
                حذف گزارش
            </button>
        </div>
    @endif
</div>
