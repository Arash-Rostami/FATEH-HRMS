<div
    dir="rtl"
    x-data="contact()"
    x-on:chat-ready.window="scrollToBottom(true)"
    x-on:keydown.ctrl.k.window="focusSearch()"
    x-on:keydown.escape.window="closeOverlays()"
    role="application"
    aria-label="پیام‌رسان سازمانی"
    class="w-full h-full relative px-4 py-4 md:px-6 md:py-8 overflow-y-auto"
    style="scrollbar-width: thin; scrollbar-color: color-mix(in srgb, var(--md-sys-color-primary) 30%, transparent) transparent;">

    <div class="max-w-[88rem] mx-auto page-wrapper">

        <x-dashboard.tab.title
            icon="perm_contact_calendar"
            :count="count($this->filteredContacts)"
            title="مخاطبین"
            countLabel="نفر"/>


        <div class="flex h-[calc(100vh-4rem)] overflow-hidden rounded-2xl">

            @include('livewire.dashboard.contact.partials.sidebar')

            <main @class([
                        'hidden' => !$mobileShowChat,
                        'flex' => $mobileShowChat,
                        'flex-1 flex flex-col overflow-hidden transition-all duration-500 ease-[cubic-bezier(0.2,0,0,1)] relative bg-[var(--md-sys-color-background)] md:flex relative',
                    ])>
                @if($activeContact)
                    @include('livewire.dashboard.contact.partials.header')
                    @include('livewire.dashboard.contact.partials.messages')
                    @include('livewire.dashboard.contact.partials.composer')
                    @include('livewire.dashboard.contact.partials.info')
                @else
                    @include('livewire.dashboard.contact.partials.empty')
                @endif
            </main>
        </div>
    </div>
</div>
