@php
    $contactList     = $p->sidebar($this->filteredContacts, auth()->id());
    $totalUnread     = $p->totalUnread($this->contacts);
@endphp
<aside @class([
    'flex-shrink-0 flex flex-col border-l border-[var(--md-sys-color-primary-container)] overflow-hidden transition-all duration-500 ease-[cubic-bezier(0.2,0,0,1)]',
    'hidden' => $mobileShowChat,
    'flex' => !$mobileShowChat,
    'md:flex w-full md:w-[320px] lg:w-[360px] bg-[var(--md-sys-color-surface)] border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)]'
]) aria-label="لیست مکالمات">

    <div class="flex-shrink-0 px-5 pt-5 pb-3">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <div
                    class="w-9 h-9 rounded-lg flex items-center justify-center shadow-sm bg-[linear-gradient(135deg,var(--md-sys-color-primary),var(--md-sys-color-primary-container))] text-[var(--md-sys-color-on-primary)]"
                    aria-hidden="true">
                    <span class="material-symbols-rounded text-lg font-fill">forum</span>
                </div>
                <div>
                    <h1 class="text-base font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">
                        پیام‌رسان داخلی
                    </h1>
                    <p class="text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)]">
                        اطلاعات مخاطبین
                    </p>
                </div>
            </div>
            @if($totalUnread)
                <span
                    class="flex items-center justify-center min-w-[24px] h-[24px] px-2 rounded-xl text-[11px] font-bold bg-[linear-gradient(135deg,var(--md-sys-color-primary),var(--md-sys-color-secondary))] text-[var(--md-sys-color-on-primary)]"
                    aria-label="{{ $totalUnread }} پیام خوانده‌نشده">
                    {{ $totalUnread > 99 ? '⁹⁹⁺' : $totalUnread }}
                </span>
            @endif
        </div>

        <x-ui.forms.search
            model="search"
            placeholder="جستجوی همکاران..."
            debounce="200"
            icon="search"
            clearable="true"
            x-ref="searchInput"
            type="search"
            aria-label="جستجوی همکاران"
            class="w-full"
        />
    </div>

    <div
        class="h-px mx-5 flex-shrink-0 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)]"></div>

    <div class="flex-shrink-0 px-5 pt-3 pb-2 flex items-center gap-2" role="tablist">
        @foreach([['all','همه'],['unread','خوانده‌نشده'],['online','آنلاین']] as $f)
            <button wire:click="setFilter('{{ $f[0] }}')"
                    role="tab"
                    aria-selected="{{ $filter === $f[0] ? 'true' : 'false' }}"
                @class([
                    'px-3 py-1 rounded-md text-[11px] font-bold transition-all duration-200',
                    'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' => $filter === $f[0],
                    'bg-transparent text-[var(--md-sys-color-on-surface-variant)]' => $filter !== $f[0]
                ])>
                {{ $f[1] }}
            </button>
        @endforeach
    </div>

    <div class="flex-1 overflow-y-auto py-1 contact-scrollbar" role="listbox">
        @forelse($contactList as $contact)
            <button wire:click="selectContact({{ $contact['id'] }})"
                    role="option"
                    aria-selected="{{ $activeUserId === $contact['id'] ? 'true' : 'false' }}"
                @class([
                    'ripple-effect relative w-full flex items-center gap-3 px-4 py-3 text-right transition-all duration-200 cursor-pointer',
                    'bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)]' => $activeUserId === $contact['id'],
                    'hover:bg-[color-mix(in_srgb,var(--md-sys-color-on-surface)_4%,transparent)]' => $activeUserId !== $contact['id']
                ])>

                @if($activeUserId === $contact['id'])
                    <div
                        class="absolute right-0 top-1/2 -translate-y-1/2 w-[3px] h-8 rounded-xl bg-[linear-gradient(to_bottom,var(--md-sys-color-primary),var(--md-sys-color-secondary))]"
                        aria-hidden="true"></div>
                @endif

                <div class="relative flex-shrink-0">
                    <div @class([
                        'w-11 h-11 rounded-xl flex items-center justify-center text-sm font-bold select-none overflow-hidden shadow-sm',
                        'bg-[linear-gradient(135deg,var(--md-sys-color-primary),var(--md-sys-color-secondary))] text-[var(--md-sys-color-on-primary)]' => $activeUserId === $contact['id'],
                        'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' => $activeUserId !== $contact['id']
                    ])>
                        <x-ui.avatar
                            :image="null"
                            :existingImage="$contact['avatar']"
                            class="rounded-lg" />
                    </div>

                    @if($contact['is_online'])
                        <span
                            class="absolute -bottom-0.5 -left-0.5 w-3 h-3 rounded-xl border-[2.5px] online-pulse bg-[var(--online)] border-[var(--md-sys-color-surface)]"
                            aria-hidden="true"></span>
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-1">
                        <p @class([
                            'text-sm font-semibold truncate',
                            'text-[var(--md-sys-color-on-surface)]' => $contact['unread'],
                            'text-[var(--md-sys-color-on-surface-variant)]' => !$contact['unread']
                        ])>{{ $contact['name'] }}</p>

                        @if($contact['last_message'])
                            <time @class([
                                'text-[10px] flex-shrink-0',
                                'text-[var(--md-sys-color-primary)]' => $contact['unread'],
                                'text-[var(--md-sys-color-on-surface-variant)]' => !$contact['unread']
                            ]) datetime="{{ $contact['last_message']['datetime'] }}">
                                {{ $contact['last_message']['time'] }}
                            </time>
                        @endif
                    </div>

                    <div class="flex items-center justify-between gap-1 mt-0.5">
                        @if($contact['last_message'])
                            <p @class([
                                'text-[11px] truncate flex items-center gap-1',
                                'text-[var(--md-sys-color-on-surface)] font-semibold' => $contact['unread'],
                                'text-[var(--md-sys-color-on-surface-variant)] font-normal' => !$contact['unread']
                            ])>
                                @if($contact['last_message']['is_mine'])
                                    <span @class([
                                        'material-symbols-rounded text-[12px] flex-shrink-0',
                                        'text-[var(--md-sys-color-primary)]' => $contact['last_message']['is_read'],
                                        'text-[var(--md-sys-color-on-surface-variant)]' => !$contact['last_message']['is_read']
                                    ])>
                                        {{ $contact['last_message']['is_read'] ? 'done_all' : 'done' }}
                                    </span>
                                @endif
                                {{ $contact['last_message']['body'] }}
                            </p>
                        @else
                            <p class="text-[11px] truncate text-[var(--md-sys-color-on-surface-variant)]">
                                {{ $contact['position'] }}
                            </p>
                        @endif

                        @if($contact['unread'])
                            <span
                                class="flex-shrink-0 flex items-center justify-center min-w-[20px] h-[20px] px-1.5 rounded-xl text-[10px] font-bold bg-[linear-gradient(135deg,var(--md-sys-color-primary),var(--md-sys-color-secondary))] text-[var(--md-sys-color-on-primary)]"
                                aria-hidden="true">
                                {{ $contact['unread'] > 99 ? '⁹⁹⁺' : $contact['unread'] }}
                            </span>
                        @endif
                    </div>
                </div>
            </button>
        @empty
            <div class="flex flex-col items-center justify-center gap-4 py-20 px-6 text-center">
                <div
                    class="w-16 h-16 rounded-xl flex items-center justify-center opacity-40 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]"
                    aria-hidden="true">
                    <span class="material-symbols-rounded text-4xl">person_search</span>
                </div>
                <p class="text-sm font-medium text-[var(--md-sys-color-on-surface-variant)]">
                    کاربری یافت نشد
                </p>
            </div>
        @endforelse
    </div>
</aside>
