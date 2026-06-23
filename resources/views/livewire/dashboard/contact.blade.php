<div
    dir="rtl"
    x-data="contact()"
    wire:poll.10s
    x-on:chat-ready.window="scrollToBottom(true)"
    x-on:keydown.ctrl.k.window="focusSearch()"
    x-on:keydown.escape.window="closeOverlays()"
    role="application"
    class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto animate-fade"
    style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;">

    <div class="max-w-[88rem] mx-auto page-wrapper">

        <x-ui.title
            icon="perm_contact_calendar"
            :count="count($this->filteredContacts)"
            title="مخاطبین (پیام‌رسان)"
            countLabel="نفر"/>

        @include('components.dashboard.header.focus-chip')

        <div x-data="{ isHighlighted: false , backgroundPattern: null, max: false }"
             :class="max ? 'fixed inset-0 z-[100] m-0 !h-screen !rounded-none' : 'h-[calc(100vh-4rem)] rounded-2xl'"
             class="flex overflow-hidden backdrop-blur-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] shadow-[0_8px_32px_color-mix(in_srgb,var(--md-sys-color-primary)_15%,transparent)] transition-all duration-300"
        >

            @include('livewire.dashboard.contact.sidebar')

            <main @class([
                        'hidden' => !$mobileShowChat,
                        'flex' => $mobileShowChat,
                        'flex-1 flex flex-col overflow-hidden transition-all duration-500 ease-[cubic-bezier(0.2,0,0,1)] relative bg-[var(--md-sys-color-background)] md:flex relative',
                    ])>
                @if($activeContact)
                    @include('livewire.dashboard.contact.header')
                    @include('livewire.dashboard.contact.messages')
                    @include('livewire.dashboard.contact.composer')
                    @include('livewire.dashboard.contact.info')
                @else
                    @include('livewire.dashboard.contact.empty')
                @endif
            </main>
        </div>
    </div>
</div>
