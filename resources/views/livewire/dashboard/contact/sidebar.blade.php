@php
    $contactList     = $p->sidebar($this->filteredContacts, auth()->id());
    $totalUnread     = $p->totalUnread($this->contacts);
@endphp
<aside @class([
    'flex-shrink-0 flex flex-col border-l overflow-hidden transition-all duration-500 ease-[cubic-bezier(0.2,0,0,1)]',
    'hidden' => $mobileShowChat,
    'flex' => !$mobileShowChat,
    'md:flex w-full md:w-[320px] lg:w-[360px]',
    'bg-[var(--md-sys-color-surface)]',
    'border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]'
]) aria-label="لیست مکالمات">

    {{-- Header --}}
    <div class="flex-shrink-0 px-4 pt-4 pb-3">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]">
                    <span class="material-symbols-rounded text-[18px] font-fill">forum</span>
                </div>
                <div>
                    <h1 class="text-sm font-semibold tracking-tight text-[var(--md-sys-color-on-surface)]">پیام‌رسان داخلی</h1>
                    <p class="text-[10px] text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_60%,transparent)]">اطلاعات مخاطبین</p>
                </div>
            </div>
            @if($totalUnread)
                <span class="min-w-[20px] h-5 px-1.5 rounded-md text-[10px] font-bold flex items-center justify-center
                             bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]"
                      aria-label="{{ $totalUnread }} پیام خوانده‌نشده">
                    {{ $totalUnread > 99 ? '⁹⁹⁺' : $totalUnread }}
                </span>
            @endif
        </div>

        <x-ui.forms.search model="search" placeholder="جستجوی همکاران..." debounce="200"
                           icon="search" clearable="true" x-ref="searchInput" type="search"
                           aria-label="جستجوی همکاران" class="w-full" />
    </div>

    <div class="h-px mx-4 flex-shrink-0 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]"></div>

    {{-- Filters --}}
    <div class="flex-shrink-0 px-4 pt-2.5 pb-2 flex items-center gap-1.5" role="tablist">
        @foreach([['all','همه'],['unread','خوانده‌نشده'],['online','آنلاین']] as $f)
            <button wire:click="setFilter('{{ $f[0] }}')" role="tab"
                    aria-selected="{{ $filter === $f[0] ? 'true' : 'false' }}"
                @class([ 'px-2.5 py-1 rounded-md text-[10px] font-bold transition-all duration-200',
                    'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' => $filter === $f[0],
                    'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]' => $filter !== $f[0],
                ])>{{ $f[1] }}</button>
        @endforeach
    </div>

    {{-- Contact List --}}
    <div class="flex-1 overflow-y-auto py-1 contact-scrollbar" role="listbox">
        @forelse($contactList as $contact)
            <button wire:key="contact-{{ $contact['id'] }}" wire:click="selectContact({{ $contact['id'] }})"
                    data-rf="people-{{ $contact['id'] }}" role="option"
                    aria-selected="{{ $activeUserId === $contact['id'] ? 'true' : 'false' }}"
                @class([
                    'ripple-effect relative w-full flex items-center gap-3 px-4 py-2.5 text-right transition-all duration-200 cursor-pointer rounded-md',
                    'bg-[color-mix(in_srgb,var(--md-sys-color-primary-container)_40%,transparent)] border-r-2 border-[var(--md-sys-color-primary)]' => $activeUserId === $contact['id'],
                    'hover:bg-[var(--md-sys-color-surface-variant)]' => $activeUserId !== $contact['id'],
                ])>

                <div class="relative flex-shrink-0">
                    <div @class([
                        'w-10 h-10 rounded-xl overflow-hidden flex items-center justify-center text-sm font-bold select-none shadow-sm ring-1',
                        'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] ring-[var(--md-sys-color-tertiary)]' => $activeUserId === $contact['id'],
                        'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]' => $activeUserId !== $contact['id'],
                    ])>
                        <x-ui.avatar :image="null" :existingImage="$contact['avatar']"
                                     :alt="$contact['name']" icon-size="text-xl" class="rounded-xl" />
                    </div>
                    @if($contact['presence'])
                        <span aria-hidden="true"
                              title="{{ $contact['presence']->label() }}"
                              class="absolute -bottom-0.5 -end-0.5 w-3 h-3 rounded-full border-2 border-[var(--md-sys-color-surface)] {{ $contact['presence']->activeClass() }} {{ $contact['is_online'] ? 'animate-pulse' : '' }}"></span>
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-1">
                        <p @class([
                            'text-[13px] truncate',
                            'font-semibold text-[var(--md-sys-color-on-surface)]' => $contact['unread'],
                            'font-medium text-[var(--md-sys-color-on-surface-variant)]' => !$contact['unread'],
                        ])>{{ $contact['name'] }}</p>
                        @if($contact['last_message'])
                            <time @class([
                                'text-[10px] flex-shrink-0',
                                'text-[var(--md-sys-color-primary)] font-medium' => $contact['unread'],
                                'text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_60%,transparent)]' => !$contact['unread'],
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
                            <p class="text-[11px] truncate text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_60%,transparent)]">
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
            </button>
        @empty
            <x-ui.empty icon="person_search" title="کاربری یافت نشد" variant="search" />
        @endforelse
    </div>
</aside>
