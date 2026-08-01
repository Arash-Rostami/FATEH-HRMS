@persist('dashboard-tools')
    <x-dashboard.tools.radio/>
    <x-dashboard.tools.calculator/>
    <x-dashboard.tools.stopwatch/>
    <div id="tool-dock" class="fixed bottom-8 right-0 z-[999] flex flex-col-reverse gap-3 items-end pointer-events-none pr-4"></div>
@endpersist

<x-ui.modals.toast/>

<x-dashboard.modal.occasion/>
