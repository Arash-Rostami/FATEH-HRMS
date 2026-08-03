<div
    dir="rtl"
    x-data="contact()"
    data-outgoing-sound="{{ asset('build/assets/audio/outgoing.mp3') }}"
    x-on:keydown.ctrl.k.window="focusSearch()"
    x-on:keydown.escape.window="closeOverlays()"
    @keydown.escape.window="if(max) toggleMaximize(null)"
    role="region"
    aria-label="پیام‌رسان"
    class="w-full h-[calc(100dvh-60px)] md:h-[calc(100dvh-80px)] relative px-4 py-4 md:px-6 md:py-8 overflow-hidden animate-fade"
    style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;">

    <div class="max-w-[88rem] mx-auto page-wrapper h-full flex flex-col">

        <x-ui.title
            icon="perm_contact_calendar"
            :count="count($this->contacts)"
            title="مخاطبین (پیام‌رسان)"
            countLabel="نفر"/>

        @include('components.dashboard.header.focus-chip')

        <livewire:dashboard.messaging.switch-tabs active="contacts"/>

        <x-ui.modals.max-backdrop/>
        <div class="chat-widget flex-1 min-h-0" :class="{ 'max-widget': max }">

            @island(name: 'sidebar')
                @include('livewire.dashboard.contact.sidebar')
            @endisland

            @island(name: 'messages')
                <main @class([
                            'hidden' => !$mobileShowChat,
                            'flex' => $mobileShowChat,
                            'flex-1 flex flex-col overflow-hidden transition-all duration-500 ease-[cubic-bezier(0.2,0,0,1)] relative bg-[var(--md-sys-color-background)] md:flex',
                        ])>
                    @if($this->activeContact)

                        @include('livewire.dashboard.contact.header')

                        <x-ui.decor.chat-pattern x-show="backgroundPattern === 'on'"/>

                        @include('livewire.dashboard.contact.messages')

                        @include('livewire.dashboard.contact.composer')

                        @include('livewire.dashboard.contact.info')
                    @else
                        @include('livewire.dashboard.contact.empty')
                    @endif
                </main>
            @endisland
        </div>

    </div>

    @include('livewire.dashboard.contact.quote-chip')
</div>
