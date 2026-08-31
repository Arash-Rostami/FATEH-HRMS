<x-dashboard.modal.badge-legend
    name="messaging-badge-legend"
    :groups="[
        ['id' => 'contacts', 'icon' => 'chat', 'label' => 'پیام‌رسان', 'items' => [\App\Services\Menu\BadgeLegendCatalog::get('contacts-controller')]],
        ['id' => 'channels', 'icon' => 'campaign', 'label' => 'کانال‌ها', 'items' => [\App\Services\Menu\BadgeLegendCatalog::get('channels-controller'), \App\Services\Menu\BadgeLegendCatalog::get('channels-controller:edge')]],
    ]"
    title="راهنمای اعلان پیام‌رسان و کانال"
/>

<x-ui.modals.dialog name="messaging-feature-legend" title="راهنمای پیام‌رسان و کانال">
    @include('livewire.dashboard.messaging.feature-legend')
</x-ui.modals.dialog>