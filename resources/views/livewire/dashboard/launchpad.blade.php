@use('App\Services\Menu\BadgeLegendCatalog')
@php
    $takeover = ($openId !== null && $activeTab !== 'compact') || $embed;
    $switchTargets = "toggleView,switchTab('list'),switchTab('create'),switchTab('compact')";
@endphp
<div dir="rtl" x-data="launchpadPage" @keydown.escape.window="onEscape($event)" x-on:confirmation-confirmed.window="{{ confirmDialogListener(['delete' => true, 'deactivate' => true, 'confirmReplace' => false]) }}"
     class="{{ $takeover ? 'w-full h-[calc(100dvh-60px)] md:h-[max(1000px,calc(100dvh-80px))] overflow-hidden' : 'w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade' }}">
    @if($unavailable !== null)
        <div wire:key="launchpad-unavailable" class="contents"><x-ui.empty icon="block" title="برنامه در دسترس نیست" :description="$unavailable"/></div>
    @elseif($embed && $openId !== null && $viewToken === null)
        <div wire:key="launchpad-embed-link" class="flex flex-col w-full h-full bg-[var(--md-sys-color-surface)]">
            <div class="p-2 flex shrink-0 flex-wrap items-center justify-between gap-2">
                <span class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]">اگر صفحهٔ ورود سامانه را می‌بینید، «ورود دوباره» را بزنید.</span>
                <div class="flex items-center gap-1">
                    <a href="{{ route('launchpad.go', $openId) }}" target="launchpad-frame" aria-label="ورود دوباره" title="ورود دوباره"
                       class="flex h-8 w-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95">
                        <span class="material-symbols-rounded text-base">refresh</span>
                    </a>
                    <a href="{{ route('launchpad.go', $openId) }}" target="_blank" rel="noopener" aria-label="باز کردن در پنجره جدید" title="باز کردن در پنجره جدید"
                       class="flex h-8 w-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95">
                        <span class="material-symbols-rounded text-base">open_in_new</span>
                    </a>
                </div>
            </div>
            <iframe name="launchpad-frame" sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox allow-modals allow-downloads" referrerpolicy="no-referrer" class="w-full flex-1 min-h-0 border-0 bg-white" title="نمایش برنامه" src="{{ route('launchpad.go', $openId) }}"></iframe>
        </div>
    @elseif($takeover && $viewToken !== null)
        <div wire:key="launchpad-viewer" class="contents">
            @if(!$embed)
                <x-ui.modals.max-backdrop/>
            @endif
            <div :class="{ 'max-widget': max || maxLeaving, 'max-widget-leaving': maxLeaving }" class="flex flex-col w-full h-full bg-[var(--md-sys-color-surface)]">
                @if(!$embed)
                    <div wire:key="launchpad-viewer-title" class="p-2 flex shrink-0 items-center justify-end gap-1">
                        @if($openFresh !== [])
                            @include('livewire.dashboard.launchpad.freshness')
                        @endif
                        <button type="button" wire:click="close" x-on:click="if (max) toggleMaximize()" aria-label="بازگشت به فهرست برنامه‌ها" title="بازگشت به فهرست برنامه‌ها"
                                class="flex h-8 w-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95">
                            <span class="material-symbols-rounded text-base">arrow_forward</span>
                        </button>
                        <x-ui.modals.maximize-button/>
                    </div>
                @elseif($openFresh !== [] && !$openFresh['ok'])
                    <div wire:key="launchpad-embed-stale" class="p-2 shrink-0"><x-ui.badge variant="warning">{{ $openFresh['text'] }}</x-ui.badge></div>
                @endif
                <iframe sandbox="allow-scripts" referrerpolicy="no-referrer" class="w-full flex-1 min-h-0 border-0 bg-white" title="نمایش برنامه" src="{{ route('launchpad.file', [$openId, $viewToken, config('launchpad.entry')]) }}"></iframe>
            </div>
        </div>
    @elseif(!$embed)
        <div wire:key="launchpad-module" class="max-w-[88rem] mx-auto page-wrapper flex flex-col gap-4">
            <x-ui.title icon="apps" title="پیشخوان برنامه‌ها" guide="launchpad">
                <x-slot:actions>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.buttons.view-toggle
                            responsive
                            state="$wire.activeTab"
                            action="toggleView"
                            :modes="[
                                ['value' => 'compact', 'icon' => 'edit_note', 'title' => 'فشرده'],
                                ['value' => 'list', 'match' => ['list', 'create'], 'icon' => 'table_rows', 'title' => 'کلاسیک'],
                            ]"
                        />
                        @if($activeTab === 'compact' && $openId !== null)
                            <x-ui.buttons.form wire:key="launchpad-compact-add" type="button" variant="tonal" size="icon" icon="add" title="افزودن برنامه"
                                               aria-label="افزودن برنامه" class="!h-8 !w-8 !p-0 !rounded-lg" loading="close" wire:click="close"/>
                        @endif
                        <x-ui.buttons.form variant="ai" :href="asset('build/assets/docs/launchpad-artifact-guide.md')" download class="no-underline">ساخت راهنمای برنامه با هوش مصنوعی</x-ui.buttons.form>
                        <button
                            type="button"
                            @click="$dispatch('open-modal', { name: 'launchpad-badge-legend' })"
                            title="راهنمای نشانگر اعلان" aria-label="راهنمای نشانگر اعلان"
                            class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                        >
                            <span class="material-symbols-rounded text-lg">notifications</span>
                        </button>
                        <button
                            type="button"
                            @click="$dispatch('open-modal', { name: 'launchpad-legend' })"
                            title="راهنمای پیشخوان برنامه‌ها" aria-label="راهنمای پیشخوان برنامه‌ها"
                            class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
                        >
                            <span class="material-symbols-rounded text-lg">help</span>
                        </button>
                    </div>
                </x-slot:actions>
            </x-ui.title>
            <x-dashboard.modal.badge-legend
                name="launchpad-badge-legend"
                :items="[BadgeLegendCatalog::get('launchpad-controller')]"
            />
            <x-ui.modals.dialog name="launchpad-legend" title="راهنمای پیشخوان برنامه‌ها">
                @include('livewire.dashboard.launchpad.legend')
            </x-ui.modals.dialog>
            <div wire:key="launchpad-details-dialog" class="contents">
                <x-ui.modals.dialog name="launchpad-details" title="جزئیات برنامه">
                    @if($details !== [])
                        <div wire:key="launchpad-details-body" x-data="{ tab: 'info' }" x-on:open-modal.window="tab = 'info'" class="flex flex-col gap-4">
                            <x-ui.buttons.tab-selector :has-a11y="true" class="!mb-0" :tabs="[
                                ['id' => 'info', 'icon' => 'info', 'label' => 'مشخصات', 'alpine' => 'tab = \'info\'', 'activeExpr' => 'tab === \'info\''],
                                ['id' => 'access', 'icon' => 'lock_open', 'label' => 'دسترسی و داده', 'alpine' => 'tab = \'access\'', 'activeExpr' => 'tab === \'access\''],
                            ]"/>
                            <div wire:key="launchpad-details-info" x-show="tab === 'info'" class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-container-low)] divide-y divide-[var(--md-sys-color-outline-variant)]/40">
                                @foreach($details['rows'] as $i => [$label, $value])
                                    <div wire:key="launchpad-details-row-{{ $i }}" class="flex items-start justify-between gap-4 px-4 py-2.5 text-sm">
                                        <span class="shrink-0 font-semibold text-[var(--md-sys-color-on-surface-variant)]">{{ $label }}</span>
                                        <span dir="auto" class="min-w-0 break-words font-bold text-[var(--md-sys-color-on-surface)]">{{ $value }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <div wire:key="launchpad-details-access" x-show="tab === 'access'" x-cloak class="flex flex-col gap-4">
                            <div class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-container-low)] divide-y divide-[var(--md-sys-color-outline-variant)]/40">
                                @foreach($details['access'] as $i => [$label, $value])
                                    <div wire:key="launchpad-details-access-row-{{ $i }}" class="flex items-start justify-between gap-4 px-4 py-2.5 text-sm">
                                        <span class="shrink-0 font-semibold text-[var(--md-sys-color-on-surface-variant)]">{{ $label }}</span>
                                        <span dir="auto" class="min-w-0 break-words font-bold text-[var(--md-sys-color-on-surface)]">{{ $value }}</span>
                                    </div>
                                @endforeach
                            </div>
                            @if($details['source'])
                                <div wire:key="launchpad-details-source" class="flex flex-col gap-2">
                                    <h4 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">منبع داده</h4>
                                    <div class="rounded-xl border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-container-low)] divide-y divide-[var(--md-sys-color-outline-variant)]/40">
                                        @foreach($details['source'] as $i => [$label, $value])
                                            <div wire:key="launchpad-details-source-row-{{ $i }}" class="flex items-start justify-between gap-4 px-4 py-2.5 text-sm">
                                                <span class="shrink-0 font-semibold text-[var(--md-sys-color-on-surface-variant)]">{{ $label }}</span>
                                                <span dir="auto" class="min-w-0 break-words font-bold text-[var(--md-sys-color-on-surface)]">{{ $value }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            </div>
                            @if($details['manage'])
                                <div wire:key="launchpad-details-edit" class="flex justify-end">
                                    <x-ui.buttons.form variant="tonal" icon="edit" loading="openEdit" wire:click="openEdit({{ $details['id'] }})">ویرایش</x-ui.buttons.form>
                                </div>
                            @endif
                        </div>
                    @endif
                </x-ui.modals.dialog>
            </div>
            @if($editId !== null)
                <div wire:key="launchpad-edit-dialog" class="contents">@include('livewire.dashboard.launchpad.edit')</div>
            @endif
            @if($activeTab !== 'compact')
                <div wire:key="launchpad-view-tabs" class="contents">
                    <div wire:key="launchpad-view-tab-selector" class="w-fit max-w-full mx-auto md:mx-0 z-1">
                        <x-ui.buttons.tab-selector :active-tab="$activeTab" :has-a11y="true" class="!mb-0" :tabs="[
                            ['id' => 'list', 'icon' => 'table_rows', 'label' => 'فهرست برنامه‌ها'],
                            ['id' => 'create', 'icon' => 'add', 'label' => 'افزودن برنامه'],
                        ]"/>
                    </div>
                    @if($activeTab === 'list')
                        <div wire:key="launchpad-list" wire:loading.remove wire:target="{{ $switchTargets }}" class="flex flex-col gap-3">@include('livewire.dashboard.launchpad.list')</div>
                    @else
                        <div wire:key="launchpad-create" wire:loading.remove wire:target="{{ $switchTargets }}" class="flex flex-col gap-3">@include('livewire.dashboard.launchpad.create')</div>
                    @endif
                </div>
            @else
                <div wire:key="launchpad-compact" wire:loading.remove wire:target="{{ $switchTargets }}" class="grid grid-cols-1 lg:grid-cols-[1fr_minmax(240px,300px)] gap-4 items-start">
                    @if($openId !== null && $viewToken !== null)
                        <div wire:key="launchpad-compact-viewer" class="contents">
                            <x-ui.modals.max-backdrop/>
                            <div :class="{ 'max-widget': max || maxLeaving, 'max-widget-leaving': maxLeaving }" class="min-w-0 flex flex-col h-[calc(100dvh-60px)] md:h-[max(1000px,calc(100dvh-80px))] overflow-hidden rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)]">
                                <div wire:key="launchpad-compact-viewer-title" class="p-2 flex shrink-0 items-center justify-end gap-1">
                                    @if($openFresh !== [])
                                        @include('livewire.dashboard.launchpad.freshness')
                                    @endif
                                    <button type="button" wire:click="close" x-on:click="if (max) toggleMaximize()" aria-label="بازگشت به فهرست برنامه‌ها" title="بازگشت به فهرست برنامه‌ها"
                                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95">
                                        <span class="material-symbols-rounded text-base">arrow_forward</span>
                                    </button>
                                    <x-ui.modals.maximize-button/>
                                </div>
                                <iframe sandbox="allow-scripts" referrerpolicy="no-referrer" class="w-full flex-1 min-h-0 border-0 bg-white" title="نمایش برنامه" src="{{ route('launchpad.file', [$openId, $viewToken, config('launchpad.entry')]) }}"></iframe>
                            </div>
                        </div>
                    @else
                        <div wire:key="launchpad-compact-empty" class="flex flex-col gap-4 min-w-0">
                            <x-ui.empty icon="apps" fill title="برنامه‌ای انتخاب نشده است" description="یک برنامه را از فهرست برنامه‌ها انتخاب کنید.">
                                <div class="flex justify-center">
                                    <div class="flex items-center gap-1.5 text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]">
                                        <span class="material-symbols-rounded text-[16px]">arrow_downward</span>
                                        افزودن برنامه
                                    </div>
                                </div>
                            </x-ui.empty>
                            <div wire:key="launchpad-compact-create" class="flex flex-col gap-3">@include('livewire.dashboard.launchpad.create')</div>
                        </div>
                    @endif
                    <aside wire:key="launchpad-compact-side" class="flex flex-col gap-2 lg:sticky lg:top-4 p-3 rounded-2xl bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)] border border-[var(--md-sys-color-outline-variant)]/40">
                        @include('livewire.dashboard.launchpad.side')
                    </aside>
                </div>
            @endif
            <div wire:key="launchpad-switch-skeleton" wire:loading.flex wire:target="{{ $switchTargets }}" class="flex-col gap-2" role="status" aria-label="در حال بارگذاری...">
                <x-ui.loaders.skeleton.bar height="h-11"/>
                <x-ui.loaders.skeleton.table-stripe :columns="4" :rows="6"/>
            </div>
        </div>
    @else
        <div wire:key="launchpad-embed-empty" class="contents"><x-ui.empty icon="block" title="برنامه در دسترس نیست"/></div>
    @endif
</div>
