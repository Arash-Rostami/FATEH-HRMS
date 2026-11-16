@use('App\Services\Menu\BadgeLegendCatalog')
<div dir="rtl" x-data="launchpadPage" x-on:confirmation-confirmed.window="{{ confirmDialogListener(['delete' => true]) }}"
     class="{{ $openId !== null || $embed ? 'w-full h-[calc(100dvh-60px)] md:h-[max(1000px,calc(100dvh-80px))] overflow-hidden' : 'w-full h-full overflow-y-auto px-4 py-4 md:px-6 md:py-8' }}">
    @if($unavailable !== null)
        <div wire:key="launchpad-unavailable" class="contents"><x-ui.empty icon="block" title="برنامه در دسترس نیست" :description="$unavailable"/></div>
    @elseif($openId !== null && $viewToken !== null)
        <div wire:key="launchpad-viewer" class="flex flex-col w-full h-full">
            @if(!$embed)
                <div wire:key="launchpad-viewer-title" class="p-4"><x-ui.buttons.form variant="tonal" icon="arrow_forward" wire:click="close">بازگشت</x-ui.buttons.form></div>
            @endif
            <iframe sandbox="allow-scripts" referrerpolicy="no-referrer" class="w-full h-full border-0 bg-white" title="پیش‌نمایش برنامه" src="{{ route('launchpad.file', [$openId, $viewToken, 'index.html']) }}"></iframe>
        </div>
    @elseif(!$embed)
        <div wire:key="launchpad-module" class="max-w-[88rem] mx-auto page-wrapper flex flex-col gap-4">
            <x-ui.title icon="apps" title="پیشخوان برنامه‌ها">
                <x-slot:actions>
                    <div class="flex flex-wrap items-center gap-2">
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
            <div class="w-fit max-w-full mx-auto md:mx-0 z-1">
                <x-ui.buttons.tab-selector :active-tab="$activeTab" :has-a11y="true" class="!mb-0" :tabs="[
                    ['id' => 'list', 'icon' => 'table_rows', 'label' => 'فهرست'],
                    ['id' => 'create', 'icon' => 'add', 'label' => 'ساخت برنامه'],
                ]"/>
            </div>
            @if($activeTab === 'list')
                <div wire:key="launchpad-list" class="flex flex-col gap-3">@include('livewire.dashboard.launchpad.list')</div>
            @else
                <div wire:key="launchpad-create" class="flex flex-col gap-3">@include('livewire.dashboard.launchpad.create')</div>
            @endif
        </div>
    @else
        <div wire:key="launchpad-embed-empty" class="contents"><x-ui.empty icon="block" title="برنامه در دسترس نیست"/></div>
    @endif
</div>
