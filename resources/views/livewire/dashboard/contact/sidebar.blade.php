@php
    $p = $this->presenter;
    $contactList     = $p->sidebar($this->contacts, auth()->id());
    $totalUnread     = $p->totalUnread($this->contacts);
    $allContactIds   = collect($contactList)->pluck('id')->map(fn($id) => (int) $id)->values()->toJson();
    $visibleContactList = array_slice($contactList, 0, $contactsLimit);
    $hasMoreContacts = count($contactList) > $contactsLimit;
@endphp
<aside @class([
    'flex-shrink-0 flex flex-col border-l overflow-hidden transition-all duration-500 ease-[cubic-bezier(0.2,0,0,1)]',
    'hidden' => $mobileShowChat,
    'flex' => !$mobileShowChat,
    'md:flex w-full md:w-[320px] lg:w-[360px]',
    'bg-[var(--md-sys-color-surface)]',
    'border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]'
]) aria-label="لیست مکالمات" data-total-unread="{{ $totalUnread }}">

    {{-- Header --}}
    <div class="flex-shrink-0 px-4 pt-4 pb-3">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <div
                    class="w-8 h-8 rounded-lg flex items-center justify-center bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]">
                    <span class="material-symbols-rounded text-[18px] font-fill">forum</span>
                </div>
                <div>
                    <h1 class="text-sm font-semibold tracking-tight text-[var(--md-sys-color-on-surface)]">پیام‌رسان
                        داخلی</h1>
                    <p class="text-[10px] text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_60%,transparent)]">
                        اطلاعات مخاطبین</p>
                </div>
            </div>
            <div class="flex items-center gap-1.5">
                @if($totalUnread)
                    <span class="min-w-[20px] h-5 px-1.5 rounded-md text-[10px] font-bold flex items-center justify-center
                                 bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]"
                          aria-label="{{ $totalUnread }} پیام خوانده‌نشده">
                        {{ $totalUnread > 99 ? '⁹⁹⁺' : $totalUnread }}
                    </span>
                @endif
                @if(count($contactList))
                    <button type="button"
                            x-on:click="$store.sound.toggleAll({{ $allContactIds }}, 'contact')"
                            :aria-pressed="$store.sound.isAllMuted({{ $allContactIds }}, 'contact')"
                            :aria-label="$store.sound.isAllMuted({{ $allContactIds }}, 'contact') ? 'باصدا کردن همه مخاطبین' : 'بی‌صدا کردن همه مخاطبین'"
                            :title="$store.sound.isAllMuted({{ $allContactIds }}, 'contact') ? 'باصدا کردن همه مخاطبین' : 'بی‌صدا کردن همه مخاطبین'"
                            class="w-6 h-6 rounded-lg flex items-center justify-center transition-all bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] active:scale-90"
                            :class="$store.sound.isAllMuted({{ $allContactIds }}, 'contact') ? '!bg-[var(--md-sys-color-primary)] !text-[var(--md-sys-color-on-primary)]' : 'hover:brightness-95'">
                        <span class="material-symbols-rounded text-[14px]"
                              x-text="$store.sound.isAllMuted({{ $allContactIds }}, 'contact') ? 'volume_off' : 'volume_up'"></span>
                    </button>
                @endif
                <button type="button" x-show="$store.push.supported" x-cloak
                        x-on:click="$store.push.toggle('contact')"
                        :aria-pressed="$store.push.isEnabled('contact')"
                        :aria-label="$store.push.isEnabled('contact') ? 'غیرفعال کردن اعلان مرورگر' : 'فعال کردن اعلان مرورگر'"
                        :title="$store.push.isEnabled('contact') ? 'غیرفعال کردن اعلان مرورگر' : 'فعال کردن اعلان مرورگر'"
                        class="w-6 h-6 rounded-lg flex items-center justify-center transition-all bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] active:scale-90"
                        :class="$store.push.isEnabled('contact') ? '!bg-[var(--md-sys-color-primary)] !text-[var(--md-sys-color-on-primary)]' : 'hover:brightness-95'">
                    <span class="material-symbols-rounded text-[14px]"
                          x-text="$store.push.isEnabled('contact') ? 'notifications_active' : 'notifications_off'"></span>
                </button>
            </div>
        </div>

        @include('livewire.dashboard.channel.search-field', [
            'model' => 'search',
            'name' => 'search',
            'id' => 'search',
            'debounce' => 200,
            'placeholder' => 'جستجوی همکاران...',
            'ariaLabel' => 'جستجوی همکاران',
            'overlayTitle' => 'جستجوی همکاران',
            'refreshSidebarOnClose' => true,
            'showLabel' => true,
            'loadingDisabled' => false,
            'wireIgnoreSelf' => true,
            'inputClass' => 'md3-input peer pr-10 pl-10 h-10 leading-[40px] rounded-xl text-sm outline-none transition-all focus:ring-2 w-full bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50 placeholder-transparent',
        ])
    </div>

    <div
        class="h-px mx-4 flex-shrink-0 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]"></div>

    {{-- Filters --}}
    <div class="flex-shrink-0 px-4 pt-2.5 pb-2 flex items-center gap-1.5" role="tablist" aria-label="فیلتر مخاطبین">
        @foreach([['all','همه'],['unread','خوانده‌نشده'],['online','آنلاین']] as $f)
            <button wire:click="setFilter('{{ $f[0] }}')" role="tab"
                    aria-controls="contact-list"
                    aria-selected="{{ $filter === $f[0] ? 'true' : 'false' }}"
                @class([ 'px-2.5 py-1 rounded-md text-[10px] font-bold transition-all duration-200',
                    'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' => $filter === $f[0],
                    'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]' => $filter !== $f[0],
                ])>{{ $f[1] }}</button>
        @endforeach
    </div>

    {{-- Contact List --}}
    <div id="contact-list" class="flex flex-col overflow-y-auto py-1 contact-scrollbar" role="listbox">
        @forelse($visibleContactList as $contact)
            <div wire:key="contact-{{ $contact['id'] }}" x-data="{ tagOpen: false }"
                 x-on:click="selectContact({{ $contact['id'] }})"
                 x-on:keydown.enter.prevent="selectContact({{ $contact['id'] }})"
                 x-on:keydown.space.prevent="selectContact({{ $contact['id'] }})"
                 data-rf="people-{{ $contact['id'] }}" role="option" tabindex="0"
                 aria-selected="{{ $activeUserId === $contact['id'] ? 'true' : 'false' }}"
                 :style="{ order: $store.pinned.isPinned({{ $contact['id'] }}, 'contact') ? -1 : 0 }"
                @class([
                    'group ripple-effect relative isolate w-full shrink-0 flex items-center gap-3 px-4 py-2.5 text-right transition-all duration-200 cursor-pointer rounded-md',
                    'bg-[color-mix(in_srgb,var(--md-sys-color-primary-container)_40%,transparent)] border-r-2 border-[var(--md-sys-color-primary)]' => $activeUserId === $contact['id'],
                    'hover:bg-[var(--md-sys-color-surface-variant)]' => $activeUserId !== $contact['id'],
                ])>

                <x-ui.row-actions :id="$contact['id']" scope="contact" pin-noun="گفتگو" mute-noun="مخاطب"/>

                <div class="relative flex-shrink-0">
                    <div @class([
                        'w-10 h-10 rounded-xl overflow-hidden flex items-center justify-center text-sm font-bold select-none shadow-sm ring-1',
                        'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] ring-[var(--md-sys-color-tertiary)]' => $activeUserId === $contact['id'],
                        'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]' => $activeUserId !== $contact['id'],
                    ])>
                        <x-ui.avatar :image="null" :existingImage="$contact['avatar']"
                                     :alt="$contact['name']" icon-size="text-xl" class="rounded-xl"/>
                    </div>
                    @if($contact['presence'])
                        <span aria-hidden="true"
                              title="{{ $contact['presence']->label() }}"
                              class="absolute -bottom-0.5 -end-0.5 w-3 h-3 rounded-full border-2 border-[var(--md-sys-color-surface)] {{ $contact['presence']->activeClass() }} {{ $contact['is_online'] ? 'animate-pulse' : '' }}"></span>
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2 min-w-0">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span @class([
                                'truncate text-[13px] leading-tight',
                                'font-bold text-[var(--md-sys-color-on-surface)]' => $contact['unread'],
                                'font-medium text-[var(--md-sys-color-on-surface-variant)]' => !$contact['unread'],
                            ])>
                                {{ $contact['name'] }}
                            </span>
                            @if($contact['occasion'])
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-medium leading-none shrink-0 select-none {{ $contact['occasion_tone']['chip'] ?? 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]' }}">
                                    <span class="material-symbols-rounded text-[11px] shrink-0 leading-none animate-pulse-slow">auto_awesome</span>
                                    <span>{{ $contact['occasion_tone']['label'] }}</span>
                                </span>
                            @endif
                        </div>

                        @if($contact['last_message'])
                            <time @class([
                                'text-[10px] shrink-0 tabular-nums select-none tracking-tight leading-tight',
                                'font-bold text-[var(--md-sys-color-primary)]' => $contact['unread'],
                                'font-normal text-[var(--md-sys-color-on-surface-variant)] opacity-70' => !$contact['unread'],
                            ]) datetime="{{ $contact['last_message']['datetime'] }}">
                                {{ $contact['last_message']['time'] }}
                            </time>
                        @endif
                    </div>
                    <div class="flex items-center justify-between gap-1 mt-px">
                        @if($contact['last_message'])
                            <p @class([
                                'text-[11px] truncate flex items-center gap-1',
                                'text-[var(--md-sys-color-on-surface)] font-medium' => $contact['unread'],
                                'text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_70%,transparent)]' => !$contact['unread'],
                            ])>
                                @if($contact['last_message']['is_mine'])
                                    <span @class([
                                        'material-symbols-rounded text-[11px] flex-shrink-0',
                                        'text-[var(--md-sys-color-primary)]' => $contact['last_message']['is_read'],
                                        'text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_50%,transparent)]' => !$contact['last_message']['is_read'],
                                    ])>{{ $contact['last_message']['is_read'] ? 'done_all' : 'done' }}</span>
                                @endif
                                {{ $contact['last_message']['body'] }}
                            </p>
                        @else
                            <p class="text-[11px] truncate text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_60%,transparent)]"
                               title="{{ $contact['org_title'] }}">
                                {{ $contact['position'] }}
                            </p>
                        @endif

                        @if($contact['unread'])
                            <span class="flex-shrink-0 min-w-[18px] h-[18px] px-1 rounded-md text-[10px] font-bold
                                         flex items-center justify-center
                                         bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                                {{ $contact['unread'] > 99 ? '⁹⁹⁺' : $contact['unread'] }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <x-ui.empty icon="person_search" title="کاربری یافت نشد" variant="search"/>
        @endforelse

        @if($hasMoreContacts)
            <x-ui.buttons.load-more action="loadMoreContacts" text="نمایش بیشتر" loading-text="در حال بارگذاری…"
                                     class="mx-auto my-2 px-4 py-2 rounded-xl text-xs font-medium bg-[var(--md-sys-color-surface-variant)]/50 text-[var(--md-sys-color-on-surface-variant)]"/>
        @endif
    </div>
</aside>
