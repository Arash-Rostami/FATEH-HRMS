@use('App\Services\Menu\BadgeLegendCatalog')
@php
    $report = $this->report;
    $owner = $report !== null && $report->isOwnedBy(auth()->user());
    $limits = $presenter->limits();
@endphp

<div dir="rtl" x-on:confirmation-confirmed.window="{{ confirmDialogListener(['delete' => false]) }}"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade print:overflow-visible print:h-auto print:p-0">
    <div class="max-w-[88rem] mx-auto page-wrapper flex flex-col gap-4 print:gap-3">

        <div class="print:hidden">
        <x-ui.title icon="auto_awesome" :ai="true" title="گزارش ساز هوشمند">
            <x-slot:actions>
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.buttons.view-toggle
                        responsive
                        state="$wire.activeTab"
                        action="toggleView"
                        :modes="[
                            ['value' => 'compose', 'icon' => 'edit_note', 'title' => 'فشرده'],
                            ['value' => 'report', 'match' => ['report', 'table'], 'icon' => 'table_rows', 'title' => 'کلاسیک'],
                        ]"
                    />

                    @if($report !== null)
                        <x-ui.buttons.form wire:key="ai-report-new-button" type="button" variant="tonal" size="icon" icon="add" title="گزارش جدید"
                                            aria-label="گزارش جدید" class="!h-8 !w-8 !p-0 !rounded-lg" wire:click="close"
                                            x-on:click="$dispatch('ai-report-open')"/>
                    @endif

                    @if($report !== null && $activeTab !== 'table' && $presenter->canShare($report))
                        <x-ui.buttons.form wire:key="ai-report-share-button" type="button" variant="tonal" size="icon" icon="forward_to_inbox"
                                           title="اشتراک‌گذاری با همکاران" aria-label="اشتراک‌گذاری با همکاران" class="!h-8 !w-8 !p-0 !rounded-lg"
                                           wire:click="openShare"
                                           @click="$dispatch('open-modal', { name: 'ai-report-share' })"/>
                    @endif

                    @if($report !== null && $activeTab !== 'table' && $report->status === \App\Models\AiReport::STATUS_READY)
                        <x-ui.buttons.form wire:key="ai-report-print-button" type="button" variant="tonal" size="icon" icon="print" title="چاپ یا ذخیره PDF: در پنجرهٔ چاپ، مقصد «ذخیره به‌صورت PDF» را انتخاب کنید"
                                            aria-label="چاپ یا ذخیره PDF: در پنجرهٔ چاپ، مقصد «ذخیره به‌صورت PDF» را انتخاب کنید" class="!h-8 !w-8 !p-0 !rounded-lg" x-on:click="window.print()"/>
                        <x-ui.buttons.form wire:key="ai-report-copy-button" type="button" variant="tonal" size="icon" icon="content_copy"
                                           title="رونوشت متن و ارقام" aria-label="رونوشت متن و ارقام" class="print:hidden !h-8 !w-8 !p-0 !rounded-lg"
                                           x-data="aiReportCopy" data-toast="متن و ارقام گزارش رونوشت شد."
                                           x-on:click="copy()"/>
                    @endif

                    @unless(tenantOff('reports'))
                        <a wire:key="ai-report-reports-link" href="{{ route('dashboard', ['tab' => 'reports']) }}" wire:navigate
                           title="گزارشات سازمانی" aria-label="گزارشات سازمانی"
                           class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
                            <span class="material-symbols-rounded text-lg">show_chart</span>
                        </a>
                    @endunless

                    <button
                        type="button"
                        @click="$dispatch('open-modal', { name: 'ai-report-badge-legend' })"
                        title="راهنمای نشانگر اعلان" aria-label="راهنمای نشانگر اعلان"
                        class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                    >
                        <span class="material-symbols-rounded text-lg">notifications</span>
                    </button>
                    <button
                        type="button"
                        @click="$dispatch('open-modal', { name: 'ai-report-legend' })"
                        title="راهنمای گزارش ساز هوشمند" aria-label="راهنمای گزارش ساز هوشمند"
                        class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                    >
                        <span class="material-symbols-rounded text-lg">help</span>
                    </button>
                </div>
            </x-slot:actions>
        </x-ui.title>
        </div>

        <x-dashboard.modal.badge-legend
            name="ai-report-badge-legend"
            :items="array_values(array_filter([BadgeLegendCatalog::get('ai-report-controller'), BadgeLegendCatalog::get('ai-report-controller:nudge')]))"
        />

        <x-ui.modals.dialog name="ai-report-legend" title="راهنمای گزارش ساز هوشمند">
            @include('livewire.dashboard.ai-report.legend')
        </x-ui.modals.dialog>

        @if($report !== null && $presenter->canShare($report))
            <div wire:key="ai-report-share-dialog" class="contents">
                <x-ui.modals.dialog name="ai-report-share" title="اشتراک‌گذاری با همکاران">
                    <div wire:key="ai-report-share-picker" class="flex flex-col gap-3">
                        @if($shareOpen)
                            <div wire:key="ai-report-share-members" class="contents">
                                <x-dashboard.member-picker model="shareRecipients" :candidates="$this->recipientCandidates" mode="multi"/>
                            </div>
                        @endif
                        <p class="text-[10.5px] text-[var(--md-sys-color-on-surface-variant)]">حداکثر {{ $limits['recipients'] }} گیرنده؛ برای حذف یک گیرنده، تیک او را بردارید و ارسال کنید.</p>
                        <x-ui.buttons.form loading="share" wire:click="share">ارسال</x-ui.buttons.form>
                    </div>
                </x-ui.modals.dialog>
            </div>
        @endif

        @if($report !== null && $owner && $presenter->canRevert($report))
            <div wire:key="ai-report-block-editor-dialog" class="contents">
                <x-ui.modals.action wire:model="blockEditorOpen" title="ویرایش دستی بخش" action="saveBlock"
                                    confirm-text="ذخیرهٔ بخش" cancel-text="انصراف">
                    <div class="modal-inner-card !w-full !max-w-none !p-5 md:!p-6" dir="rtl">
                        @include('livewire.dashboard.ai-report.block-editor')
                    </div>
                </x-ui.modals.action>
            </div>
        @endif

        @if($activeTab !== 'compose')
            <div wire:key="ai-report-view-tabs" class="contents">
                <div wire:key="ai-report-view-tab-selector" class="w-fit max-w-full mx-auto md:mx-0 z-1 print:hidden">
                    <x-ui.buttons.tab-selector
                        :active-tab="$activeTab"
                        :has-a11y="true"
                        class="!mb-0"
                        :tabs="[
                            ['id' => 'report', 'icon' => 'edit_note', 'label' => 'گزارش و فرم'],
                            ['id' => 'table', 'icon' => 'table_rows', 'label' => 'جدول گزارش‌ها'],
                        ]"
                    />
                </div>

                @if($activeTab === 'table')
                    <div wire:key="ai-report-view-table" x-data="aiReportTable" class="flex flex-col gap-3 print:hidden">
                        @include('livewire.dashboard.ai-report.table')
                    </div>
                @else
                    <div wire:key="ai-report-view-report" class="flex flex-col gap-4 min-w-0 print:w-full print:max-w-none">
                        @include('livewire.dashboard.ai-report.viewer', ['report' => $report, 'owner' => $owner])

                        @unless($readOnly)
                            @include('livewire.dashboard.ai-report.composer', ['report' => $report, 'owner' => $owner])
                        @endunless
                    </div>
                @endif

                @if($this->hasGenerating && ($activeTab === 'table' || $report === null || $report->status !== \App\Models\AiReport::STATUS_GENERATING))
                    <div wire:key="ai-report-table-poll-anchor" wire:poll.visible.{{ config('ai.report.poll_seconds') }}s="pollList"></div>
                @endif
            </div>
        @else
            <div wire:key="ai-report-view-compose" class="grid grid-cols-1 lg:grid-cols-[1fr_minmax(240px,300px)] gap-4 items-start">
                <div class="flex flex-col gap-4 min-w-0 print:w-full print:max-w-none">
                    @include('livewire.dashboard.ai-report.viewer', ['report' => $report, 'owner' => $owner])

                    @unless($readOnly)
                        @include('livewire.dashboard.ai-report.composer', ['report' => $report, 'owner' => $owner])
                    @endunless
                </div>

                @unless($readOnly)
                    @if($this->hasGenerating && ($report === null || $report->status !== \App\Models\AiReport::STATUS_GENERATING))
                        <aside wire:key="ai-report-list-polling" wire:poll.visible.{{ config('ai.report.poll_seconds') }}s="pollList"
                               class="flex flex-col gap-2 lg:sticky lg:top-4 p-3 rounded-2xl bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] border border-[var(--md-sys-color-outline-variant)]/40 print:hidden">
                            @include('livewire.dashboard.ai-report.list')
                        </aside>
                    @else
                        <aside wire:key="ai-report-list-idle"
                               class="flex flex-col gap-2 lg:sticky lg:top-4 p-3 rounded-2xl bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] border border-[var(--md-sys-color-outline-variant)]/40 print:hidden">
                            @include('livewire.dashboard.ai-report.list')
                        </aside>
                    @endif
                @endunless
            </div>
        @endif
    </div>
</div>
