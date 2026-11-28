@use('App\Models\AiReport')
@use('App\Services\Menu\BadgeLegendCatalog')
@php
    $report = $this->report;
    $owner = $report !== null && $report->isOwnedBy(auth()->user());
    $limits = $presenter->limits();
    $switchTargets = "toggleView,switchTab('table'),switchTab('report'),switchTab('compose')";
@endphp

<div dir="rtl" x-on:confirmation-confirmed.window="{{ confirmDialogListener(['delete' => false]) }}"
     class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade print:overflow-visible print:h-auto print:p-0">
    <div class="max-w-[88rem] mx-auto page-wrapper flex flex-col gap-4 print:gap-3">

        <div class="print:hidden">
        <x-ui.title icon="auto_awesome" :ai="true" :title="$activeTab === 'table' ? 'گزارش ساز هوشمند (جدول گزارش‌ها)' : 'گزارش ساز هوشمند'" :count="$this->total" countLabel="گزارش">
            <x-slot:actions>
                <x-ui.buttons.module-links :active="$activeTab" :tabs="[
                    ['id' => 'report', 'icon' => 'edit_note', 'label' => 'گزارش و فرم', 'condition' => $activeTab !== 'compose', 'click' => '$wire.switchTab(\'report\')'],
                    ['id' => 'table', 'icon' => 'table_rows', 'label' => 'جدول گزارش‌ها', 'condition' => $activeTab !== 'compose', 'click' => '$wire.switchTab(\'table\')'],
                    ['id' => 'knowledge', 'icon' => 'show_chart', 'label' => 'گزارش‌های سازمانی', 'short' => 'گزارش‌ها', 'condition' => !tenantOff('knowledge'), 'route' => route('knowledge')],
                ]"/>
                <x-ui.buttons.title-more guide="ai-report">
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

                    @if($report !== null && $activeTab !== 'table' && $report->status === AiReport::STATUS_READY)
                        <x-ui.buttons.form wire:key="ai-report-print-button" type="button" variant="tonal" size="icon" icon="print" title="چاپ یا ذخیره PDF: در پنجرهٔ چاپ، مقصد «ذخیره به‌صورت PDF» را انتخاب کنید"
                                            aria-label="چاپ یا ذخیره PDF: در پنجرهٔ چاپ، مقصد «ذخیره به‌صورت PDF» را انتخاب کنید" class="!h-8 !w-8 !p-0 !rounded-lg" x-on:click="window.print()"/>
                        <x-ui.buttons.form wire:key="ai-report-copy-button" type="button" variant="tonal" size="icon" icon="content_copy"
                                           title="رونوشت متن و ارقام" aria-label="رونوشت متن و ارقام" class="print:hidden !h-8 !w-8 !p-0 !rounded-lg"
                                           x-data="aiReportCopy" data-toast="متن و ارقام گزارش رونوشت شد."
                                           x-on:click="copy()"/>
                    @endif

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
                <x-slot:menu>
                    <div x-on:click.stop>
                        <x-ui.modals.menu-row icon="edit_note" label="نمای فشرده" role="menuitemradio" checked="$wire.activeTab === 'compose'" click="if ($wire.activeTab !== 'compose') { $wire.activeTab = 'compose'; $wire.toggleView('compose') }"/>
                        <x-ui.modals.menu-row icon="table_rows" label="نمای کلاسیک" role="menuitemradio" checked="$wire.activeTab !== 'compose'" click="if ($wire.activeTab === 'compose') { $wire.activeTab = 'report'; $wire.toggleView('report') }"/>
                    </div>
                    @if($report !== null)
                        <x-ui.modals.menu-row wire:key="ai-report-menu-new" icon="add" label="گزارش جدید" click="open = false; $wire.close(); $dispatch('ai-report-open')"/>
                    @endif
                    @if($report !== null && $activeTab !== 'table' && $presenter->canShare($report))
                        <x-ui.modals.menu-row wire:key="ai-report-menu-share" icon="forward_to_inbox" label="اشتراک‌گذاری با همکاران" click="open = false; $wire.openShare(); $dispatch('open-modal', { name: 'ai-report-share' })"/>
                    @endif
                    @if($report !== null && $activeTab !== 'table' && $report->status === AiReport::STATUS_READY)
                        <x-ui.modals.menu-row wire:key="ai-report-menu-print" icon="print" label="چاپ یا ذخیره PDF" click="open = false; window.print()"/>
                        <div wire:key="ai-report-menu-copy" x-data="aiReportCopy" data-toast="متن و ارقام گزارش رونوشت شد.">
                            <x-ui.modals.menu-row icon="content_copy" label="رونوشت متن و ارقام" click="open = false; copy()"/>
                        </div>
                    @endif
                    <x-ui.modals.menu-row icon="notifications" label="راهنمای نشانگر اعلان" click="open = false; $dispatch('open-modal', { name: 'ai-report-badge-legend' })"/>
                    <x-ui.modals.menu-row icon="help" label="راهنمای گزارش ساز هوشمند" click="open = false; $dispatch('open-modal', { name: 'ai-report-legend' })"/>
                </x-slot:menu>
                </x-ui.buttons.title-more>
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
                                <x-dashboard.member-picker model="shareRecipients" :candidates="$this->recipientCandidates" mode="multi" bulk :dept-options="$this->deptOptions"/>
                            </div>
                        @endif
                        <p class="text-[10.5px] leading-5 text-[var(--md-sys-color-on-surface-variant)]">تا {{ $limits['recipients'] }} همکار را انتخاب کنید و «ارسال» را بزنید. این انتخاب‌ها دسترسی همکاران را تعیین می‌کنند؛ برداشتن تیک و زدن «ارسال»، دسترسی اشتراکی فرد را قطع می‌کند. با برداشتن همهٔ تیک‌ها، اشتراک پایان می‌یابد. مالک و مدیران بر اساس دسترسی مدیریتی همچنان امکان مشاهده دارند. اشتراک‌گذاری، گزارش را هم ذخیره می‌کند.</p>
                        <p class="text-[10.5px] leading-5 text-[var(--md-sys-color-on-surface-variant)]">تا وقتی گزارش گیرنده دارد، عنوان، ویرایش دستی و بازگشت به نسخهٔ قبل قفل‌اند. اصلاح با هوش مصنوعی، گزارشی تازه و خصوصی برای شما می‌سازد و گزارش همکاران را تغییر نمی‌دهد.</p>
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
                @if($activeTab === 'table')
                    <div wire:key="ai-report-view-table" x-data="aiReportTable" wire:loading.remove wire:target="{{ $switchTargets }}" class="flex flex-col gap-3 print:hidden">
                        @include('livewire.dashboard.ai-report.table')
                    </div>
                @else
                    <div wire:key="ai-report-view-report" wire:loading.remove wire:target="{{ $switchTargets }}" class="flex flex-col gap-4 min-w-0 print:w-full print:max-w-none">
                        @include('livewire.dashboard.ai-report.viewer', ['report' => $report, 'owner' => $owner])

                        @unless($readOnly)
                            @include('livewire.dashboard.ai-report.composer', ['report' => $report, 'owner' => $owner])
                        @endunless
                    </div>
                @endif

                @if($this->hasGenerating && ($activeTab === 'table' || $report === null || $report->status !== AiReport::STATUS_GENERATING))
                    <div wire:key="ai-report-table-poll-anchor" wire:poll.visible.{{ config('ai.report.poll_seconds') }}s="pollList" class="sticky bottom-0 h-px pointer-events-none"></div>
                @endif
            </div>
        @else
            <div wire:key="ai-report-view-compose" wire:loading.remove wire:target="{{ $switchTargets }}" class="grid grid-cols-1 lg:grid-cols-[1fr_minmax(240px,300px)] gap-4 items-start">
                <div class="flex flex-col gap-4 min-w-0 print:w-full print:max-w-none">
                    @include('livewire.dashboard.ai-report.viewer', ['report' => $report, 'owner' => $owner])

                    @unless($readOnly)
                        @include('livewire.dashboard.ai-report.composer', ['report' => $report, 'owner' => $owner])
                    @endunless
                </div>

                @unless($readOnly)
                    @if($this->hasGenerating && ($report === null || $report->status !== AiReport::STATUS_GENERATING))
                        <aside wire:key="ai-report-list-polling"
                               class="flex flex-col gap-2 lg:sticky lg:top-4 p-3 rounded-2xl bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] border border-[var(--md-sys-color-outline-variant)]/40 print:hidden">
                            @include('livewire.dashboard.ai-report.list')
                            <div wire:key="ai-report-list-poll-anchor" wire:poll.visible.{{ config('ai.report.poll_seconds') }}s="pollList" class="sticky bottom-0 h-px pointer-events-none"></div>
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

        <div wire:key="ai-report-switch-skeleton" wire:loading.flex wire:target="{{ $switchTargets }}" class="flex-col gap-2 print:hidden" role="status" aria-label="در حال بارگذاری...">
            <x-ui.loaders.skeleton.bar height="h-11"/>
            <x-ui.loaders.skeleton.table-stripe :columns="4" :rows="6"/>
        </div>
    </div>
</div>
