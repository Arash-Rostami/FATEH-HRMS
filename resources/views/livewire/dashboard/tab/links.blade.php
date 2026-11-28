<div
        x-data="links"
        @keydown.window="handleHotkey($event)"
        class="animate-fade w-full max-w-[88rem] mx-auto relative space-y-6"
        dir="rtl">

    @php($viewModes = [
        ['value' => 'rail', 'icon' => 'view_carousel', 'title' => 'نمای ریلی'],
        ['value' => 'launch', 'icon' => 'apps', 'title' => 'نمای شبکه‌ای'],
    ])

    <x-ui.title icon="open_in_new" title="لینک‌ها و مسیرهای دیجیتال سازمان" :count="$this->totalLinks" countLabel="لینک">
        <x-slot:actions>
            <x-ui.buttons.title-more guide="links">
            <x-slot:actions>
            <x-ui.buttons.view-toggle module="links" :modes="$viewModes" />
            <button
                type="button"
                @click="$dispatch('open-modal', { name: 'links-legend' })"
                title="راهنمای لینک‌ها"
                class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
            >
                <span class="material-symbols-rounded text-lg">help</span>
            </button>
            </x-slot:actions>
            <x-slot:menu>
                @unless(tenantOff('links:rail') || tenantOff('links:launch'))
                    <div x-on:click.stop>
                        <x-ui.modals.menu-row icon="view_carousel" label="نمای ریلی" role="menuitemradio" checked="view === 'rail'" click="if (view !== 'rail') { view = 'rail'; $wire.toggleView('rail') }"/>
                        <x-ui.modals.menu-row icon="apps" label="نمای شبکه‌ای" role="menuitemradio" checked="view === 'launch'" click="if (view !== 'launch') { view = 'launch'; $wire.toggleView('launch') }"/>
                    </div>
                @endunless
                <x-ui.modals.menu-row icon="help" label="راهنمای لینک‌ها" click="open = false; $dispatch('open-modal', { name: 'links-legend' })"/>
            </x-slot:menu>
            </x-ui.buttons.title-more>
        </x-slot:actions>
    </x-ui.title>

    <x-ui.modals.dialog name="links-legend" title="راهنمای لینک‌ها">
        @include('livewire.dashboard.tab.links.legend')
    </x-ui.modals.dialog>

    @include('components.dashboard.header.focus-chip')

    @include('livewire.dashboard.tab.links.filters')

    <div x-show="view === 'rail'" x-cloak class="space-y-6">
        @include('livewire.dashboard.tab.links.smart')

        <div class="md:hidden">
            <x-ui.buttons.tab-selector
                :tabs="[
                    ['id' => 'internal', 'icon' => 'dataset_linked', 'label' => 'داخلی'],
                    ['id' => 'external', 'icon' => 'public', 'label' => 'خارجی'],
                ]"
                :activeTab="$mobileSection"
            />
        </div>
    </div>

    @island(name: 'links', always: true)
    @php
        $linkPresenter = new \App\Livewire\Dashboard\Tab\Presentation\LinkPresenter();
    @endphp

    <div x-show="view === 'rail'" x-cloak class="space-y-6">
        <div class="{{ $mobileSection === 'internal' ? '' : 'hidden' }} md:block">
            @include('livewire.dashboard.tab.links.internal')
        </div>

        <div class="{{ $mobileSection === 'external' ? '' : 'hidden' }} md:block">
            @include('livewire.dashboard.tab.links.external')
        </div>
    </div>

    <div x-show="view === 'launch'" x-cloak>
        @include('livewire.dashboard.tab.links.launch')
    </div>
    @endisland

</div>
