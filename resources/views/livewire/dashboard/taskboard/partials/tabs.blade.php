<x-dashboard.button.tab-selector
    :active-tab="$activeTab"
    :tabs="[
        ['id' => 'my-tasks', 'icon' => 'person', 'label' => 'وظایف من'],
        ['id' => 'assigned-tasks', 'icon' => 'assignment_ind', 'label' => 'محول شده']
    ]"
/>
