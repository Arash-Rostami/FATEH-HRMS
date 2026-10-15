<div
    dir="rtl"
    x-data="contact()"
    x-init="init()"
    data-outgoing-sound="{{ asset('build/assets/audio/outgoing.mp3') }}"
    data-incoming-sound="{{ asset('build/assets/audio/incoming.mp3') }}"
    x-on:keydown.ctrl.k.window="focusSearch()"
    x-on:keydown.escape.window="closeOverlays(); if(max) toggleMaximize(null)"
    role="region"
    aria-label="پیام‌رسان"
    class="w-full h-[calc(100dvh-60px)] md:h-[calc(100dvh-80px)] relative px-4 py-4 md:px-6 md:py-8 overflow-hidden animate-fade"
   >

    <div class="max-w-[88rem] mx-auto page-wrapper h-full flex flex-col">

        <x-ui.title
            icon="perm_contact_calendar"
            :count="$this->totalStaff"
            title="مخاطبین (پیام‌رسان)"
            countLabel="نفر">
            <x-slot:actions>
                <button
                    type="button"
                    @click="$dispatch('open-modal', { name: 'messaging-badge-legend' })"
                    title="راهنمای نشانگر اعلان"
                    class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
                    <span class="material-symbols-rounded text-lg">notifications</span>
                </button>
                <button
                    type="button"
                    @click="$dispatch('open-modal', { name: 'messaging-feature-legend' })"
                    title="راهنمای پیام‌رسان و گروه"
                    class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors">
                    <span class="material-symbols-rounded text-lg">help</span>
                </button>
            </x-slot:actions>
        </x-ui.title>

        @include('livewire.dashboard.messaging.legends')

        @include('components.dashboard.header.focus-chip')

        <livewire:dashboard.messaging.switch-tabs active="contacts"/>

        <x-ui.modals.max-backdrop/>
        <div class="chat-widget flex-1 min-h-0" :class="{ 'max-widget': max || maxLeaving, 'max-widget-leaving': maxLeaving }">

            @island(name: 'sidebar')
                @include('livewire.dashboard.contact.sidebar')
            @endisland

            @island(name: 'messages')
                <main @class([
                            'hidden' => !$mobileShowChat,
                            'flex' => $mobileShowChat,
                            'flex-1 flex flex-col overflow-hidden transition-all duration-500 ease-[cubic-bezier(0.2,0,0,1)] relative bg-[var(--md-sys-color-background)] md:flex',
                        ]) x-bind:class="{ 'hidden': !$wire.mobileShowChat, 'flex': $wire.mobileShowChat }">
                    @if($this->activeContact)
                        <div wire:key="pane-active" class="contents">
                            @include('livewire.dashboard.contact.header')
                            <button type="button" x-on:click="toggleHeaderDeclutter()" :aria-pressed="headerDeclutter"
                                    :aria-label="headerDeclutter ? 'نمایش سربرگ' : 'خلوت‌سازی سربرگ'"
                                    :title="headerDeclutter ? 'نمایش سربرگ' : 'خلوت‌سازی سربرگ'"
                                    class="absolute top-4 end-5 z-30 h-8 w-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)] transition-all duration-200 ease-out active:scale-95"
                                    :class="headerDeclutter ? 'flex' : 'hidden'">
                                <span class="material-symbols-rounded text-base rotate-90">unfold_more</span>
                            </button>

                            <x-ui.decor.chat-pattern x-show="backgroundPattern === 'on'"/>

                            @include('livewire.dashboard.contact.messages')

                            @include('livewire.dashboard.contact.composer')

                            @include('livewire.dashboard.contact.info')
                        </div>
                    @else
                        <div wire:key="pane-empty" class="contents">
                            @include('livewire.dashboard.contact.empty')
                        </div>
                    @endif
                </main>
            @endisland
        </div>

    </div>

    @include('livewire.dashboard.messaging.quote-chip')
</div>
