@if($selectedTicket)
    <template wire:key="ths-modal" x-teleport="body">
        <div x-data="thsModal()"
             x-on:keydown.escape.window="onEscape()"
             x-on:keydown.window="onKey($event)"
             x-cloak>

            <div class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-6"
                 x-show="!isClosing"
                 dir="rtl">

                <div class="absolute inset-0 z-0 bg-[var(--md-sys-color-primary)]/60"
                     x-show="!isClosing && !max"
                     x-transition:enter="motion-safe:transition motion-safe:ease-out motion-safe:duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="motion-safe:transition motion-safe:ease-in motion-safe:duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     x-on:click="closeModal()"></div>

                <x-ui.modals.max-backdrop/>

                <div class="relative z-10 isolate w-full max-w-4xl max-h-[90vh] rounded-2xl bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] shadow-2xl dark:shadow-[0_12px_40px_rgba(0,0,0,0.6)] overflow-hidden flex flex-col max-sm:absolute max-sm:inset-x-0 max-sm:bottom-0 max-sm:h-[94dvh] max-sm:max-h-none max-sm:max-w-none max-sm:rounded-t-3xl max-sm:rounded-b-none sm:min-h-[60vh]"
                     :class="{ 'max-widget': max || maxLeaving, 'max-widget-leaving': maxLeaving }"
                     :style="dragY ? { transform: 'translateY(' + dragY + 'px)', transition: 'none' } : null"
                     x-show="!isClosing"
                     x-transition:enter="motion-safe:transition motion-safe:ease-out motion-safe:duration-300"
                     x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="motion-safe:transition motion-safe:ease-in motion-safe:duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="ths-modal-title">

                    @include('livewire.dashboard.ths.modal.header')

                    <div class="flex-1 min-h-0 relative flex bg-[var(--md-sys-color-surface-container-lowest)]">
                        <div data-ths-pane class="flex-1 min-w-0 min-h-0 overflow-y-auto overscroll-contain custom-scrollbar">
                            <div wire:loading.remove wire:target="switchTab('response')" class="min-h-full flex flex-col">
                                @if($modalTab === 'request')
                                    <div wire:key="ths-modal-request"
                                         class="p-4 sm:p-6 pb-[calc(1rem+env(safe-area-inset-bottom))] sm:pb-6 space-y-5"
                                         x-data
                                         x-show="true"
                                         x-transition:enter="motion-safe:transition motion-safe:ease-out motion-safe:duration-300"
                                         x-transition:enter-start="opacity-0 translate-y-2"
                                         x-transition:enter-end="opacity-100 translate-y-0">
                                        @include('livewire.dashboard.ths.modal.request')
                                    </div>
                                @endif

                                @if($modalTab === 'response')
                                    <div wire:key="ths-modal-response" class="flex-1 flex flex-col">
                                        <livewire:dashboard.ths.workspace :ticket-id="$selectedTicket['id']" :key="'ticket-workspace-'.$selectedTicket['id']" />
                                    </div>
                                @endif
                            </div>

                            <div wire:loading.flex wire:target="switchTab('response')" class="flex-col gap-2 p-4 sm:p-6" role="status" aria-label="در حال بارگذاری گفتگو">
                                <x-ui.loaders.skeleton.bar height="h-11"/>
                                <x-ui.loaders.skeleton.avatar-row class="max-w-[60%]"/>
                                <x-ui.loaders.skeleton.avatar-row class="max-w-[45%] self-end flex-row-reverse"/>
                                <x-ui.loaders.skeleton.avatar-row class="max-w-[70%]"/>
                                <x-ui.loaders.skeleton.avatar-row class="max-w-[40%] self-end flex-row-reverse"/>
                                <x-ui.loaders.skeleton.bar height="h-14" class="mt-auto"/>
                            </div>
                        </div>

                        @include('livewire.dashboard.ths.modal.info')
                    </div>
                </div>
            </div>
        </div>
    </template>
@endif
