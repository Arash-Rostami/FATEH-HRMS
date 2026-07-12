<div
    dir="rtl"
    x-data="channel()"
    x-init="init()"
    data-outgoing-sound="{{ asset('build/assets/audio/outgoing.mp3') }}"
    x-on:keydown.escape.window="closeOverlays(); if(max) toggleMaximize(null)"
    role="region"
    aria-label="کانال‌ها"
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

        <livewire:dashboard.messaging.switch-tabs active="channels"/>
        <x-ui.modals.max-backdrop/>
        @include('livewire.dashboard.channel.manage-members')

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

    <div class="fixed bottom-4 left-4 z-50 flex flex-col gap-2 max-w-[320px]" x-show="inviteToasts.length > 0" x-cloak>
        <template x-for="t in inviteToasts" :key="t.id">
            <div class="bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/60 rounded-2xl shadow-xl p-4 animate-fade" x-transition>
                <div class="flex items-start gap-2.5">
                    <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-primary)] flex-shrink-0">group_add</span>
                    <div class="min-w-0">
                        <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">شما به کانال زیر اضافه شدید</p>
                        <p class="text-[13px] font-bold text-[var(--md-sys-color-on-surface)] truncate" x-text="t.name"></p>
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-3">
                    <button type="button" x-on:click="acceptInvite(t.id)"
                            class="flex-1 px-3 py-2 rounded-lg text-[11px] font-semibold bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:brightness-110 active:scale-95 transition-all">
                        متوجه شدم
                    </button>
                    <button type="button" x-on:click="declineInvite(t.id)"
                            class="flex-1 px-3 py-2 rounded-lg text-[11px] font-semibold bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:brightness-95 active:scale-95 transition-all">
                        خروج از کانال
                    </button>
                </div>
            </div>
        </template>
    </div>

    <div x-show="quoteChip.visible" x-cloak
         :style="`position: fixed; left: ${quoteChip.x}px; top: ${quoteChip.y}px; z-index: 60;`"
         x-on:click.prevent="startReply(quoteChip.id, quoteChip.sender, quoteChip.snippet); quoteChip.visible = false"
         x-on:mousedown.prevent=""
         class="fixed -translate-y-full -translate-x-1/2 -mt-1 px-2.5 py-1.5 rounded-lg shadow-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] text-[11px] font-semibold flex items-center gap-1 cursor-pointer hover:brightness-110 active:scale-95 transition-all"
         role="button" aria-label="پاسخ به متن انتخاب‌شده">
        <span class="material-symbols-rounded text-[14px]">reply</span>
        پاسخ
    </div>

</div>
