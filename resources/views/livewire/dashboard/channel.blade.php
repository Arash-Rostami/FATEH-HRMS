<div
    dir="rtl"
    x-data="channel()"
    x-init="init()"
    x-on:keydown.escape.window="closeOverlays(); if(max) toggleMaximize(null)"
    role="application"
    class="w-full h-[calc(100dvh-60px)] md:h-[calc(100dvh-80px)] relative px-4 py-4 md:px-6 md:py-8 overflow-hidden animate-fade"
    style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;">

    <div class="max-w-[88rem] mx-auto page-wrapper h-full flex flex-col">

        <x-ui.title icon="campaign" title="کانال‌ها">
            <x-slot:actions>
                <span x-text="channelCount + ' ' + 'کانال'"
                      class="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-tertiary-container)]"></span>
            </x-slot:actions>
        </x-ui.title>

        @include('components.dashboard.header.focus-chip')

        <x-ui.modals.max-backdrop/>
        <div class="chat-widget flex-1 min-h-0" :class="{ 'max-widget': max }">

            @island(name: 'sidebar')
                @include('livewire.dashboard.channel.sidebar')
            @endisland

            @island(name: 'messages')
                <main @class([
                    'hidden' => !$mobileShowChat,
                    'flex' => $mobileShowChat,
                    'flex-1 flex flex-col overflow-hidden transition-all duration-500 ease-[cubic-bezier(0.2,0,0,1)] relative bg-[var(--md-sys-color-background)] md:flex',
                ])>
                    @if($createMode)
                        @include('livewire.dashboard.channel.create')
                    @elseif($browseMode)
                        @include('livewire.dashboard.channel.browse')
                    @elseif($activeChannelId && $this->activeChannel)
                        @include('livewire.dashboard.channel.header')
                        @include('livewire.dashboard.channel.search')
                        <x-ui.decor.chat-pattern x-show="backgroundPattern === 'on'"/>
                        @include('livewire.dashboard.channel.messages')
                        @include('livewire.dashboard.channel.composer')
                        @include('livewire.dashboard.channel.info')
                    @else
                        @include('livewire.dashboard.channel.empty')
                    @endif
                </main>
            @endisland
        </div>

    </div>

</div>