<div
        dir="rtl"
        x-data="{
    allOpen: false,
    toggleAll(state) {
        this.$dispatch('authority-toggle-all', { open: state });}
}"
        class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
        style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;"
>
    <div class="max-w-[88rem] mx-auto page-wrapper">

        <x-ui.title
                icon="verified_user"
                title="اختیارات سازمانی"
                :count="$this->totalCount"
                countLabel="اختیار"
        />

        @include('components.dashboard.header.focus-banner')

        @include('livewire.dashboard.authority.filters')

        <div class="flex items-center justify-between gap-3 flex-wrap">

            <x-ui.buttons.tab-selector
                    :tabs="[
                        ['id' => 'employee', 'icon' => 'person',          'label' => 'منظر اجمالی'],
                        ['id' => 'manager',  'icon' => 'manage_accounts', 'label' => 'منظر مدیریتی'],
                    ]"
                    :activeTab="$activeTab"
            />

            <x-ui.buttons.toggle
                    alpine
                    alpine-state="allOpen"
                    x-text="allOpen ? 'بستن همه' : 'باز کردن همه'"
                    @click="allOpen = !allOpen; toggleAll(allOpen)"
                    bordered="false"
            />
        </div>

        <x-ui.title
                icon="list_alt"
                title=" "
                :count="$this->authorities->count()"
                countLabel="مورد"
        />

        <div class="relative">
            @include('livewire.dashboard.authority.list')
        </div>

    </div>
</div>
