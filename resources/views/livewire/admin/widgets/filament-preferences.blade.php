<x-filament-widgets::widget>
    <x-filament::section
        icon="heroicon-o-adjustments-horizontal"
        heading="{{ __('resources/dashboard/strings.preferences_widget.heading') }}"
        collapsible
        collapsed
    >
        <x-filament::fieldset>
            <x-admin.preferences-form :form="$this->form" :show-header="false"/>
        </x-filament::fieldset>
    </x-filament::section>
</x-filament-widgets::widget>
