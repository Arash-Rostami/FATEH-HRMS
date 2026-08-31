@php
    $p = $this->presenter;
    $channelList = $p->sidebar($this->channels, auth()->id());
    $totalUnread = $p->totalUnread($this->channels);
    $allChannelIds = collect($channelList)->pluck('id')->map(fn($id) => (int) $id)->values()->toJson();
    $visibleChannelList = array_slice($channelList, 0, $channelsLimit);
    $hasMoreChannels = count($channelList) > $channelsLimit;
@endphp
<aside @class([
    'flex-shrink-0 flex flex-col border-l overflow-hidden transition-all duration-500 ease-[cubic-bezier(0.2,0,0,1)]',
    'hidden' => $mobileShowChat,
    'flex' => !$mobileShowChat,
    'md:flex w-full md:w-[320px] lg:w-[360px]',
    'bg-[var(--md-sys-color-surface)]',
    'border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]'
]) aria-label="لیست کانال‌ها" data-channel-count="{{ count($this->channels) }}" data-total-unread="{{ $totalUnread }}">

    <div class="flex-shrink-0 px-4 pt-4 pb-3">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]">
                    <span class="material-symbols-rounded text-[18px] font-fill">campaign</span>
                </div>
                <div>
                    <h1 class="text-sm font-semibold tracking-tight text-[var(--md-sys-color-on-surface)]">کانال‌ها</h1>
                    @php
                        $listCount = count($channelList);
                        $openCount = count(array_filter($channelList, fn($c) => ($c['type'] ?? '') === 'open'));
                        $privateCount = $listCount - $openCount;
                        $summaryParts = $listCount ? array_merge([convertToPersian((string) $listCount) . ' کانال'], array_filter([
                            $openCount ? convertToPersian((string) $openCount) . ' عمومی' : '',
                            $privateCount ? convertToPersian((string) $privateCount) . ' خصوصی' : '',
                        ])) : [];
                    @endphp
                    <p class="text-[10px] text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_60%,transparent)]" title="تفکیک کانال‌های شما بر اساس نوع">
                        {{ $listCount ? implode(' · ', $summaryParts) : 'کانال‌های موضوعی' }}
                    </p>
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
                @if(count($channelList))
                    <button type="button"
                            x-on:click="$store.sound.toggleAll({{ $allChannelIds }}, 'channel')"
                            :aria-pressed="$store.sound.isAllMuted({{ $allChannelIds }}, 'channel')"
                            :aria-label="$store.sound.isAllMuted({{ $allChannelIds }}, 'channel') ? 'باصدا کردن همه کانال‌ها' : 'بی‌صدا کردن همه کانال‌ها'"
                            :title="$store.sound.isAllMuted({{ $allChannelIds }}, 'channel') ? 'باصدا کردن همه کانال‌ها' : 'بی‌صدا کردن همه کانال‌ها'"
                            class="w-6 h-6 rounded-lg flex items-center justify-center transition-all bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] active:scale-90"
                            :class="$store.sound.isAllMuted({{ $allChannelIds }}, 'channel') ? '!bg-[var(--md-sys-color-primary)] !text-[var(--md-sys-color-on-primary)]' : 'hover:brightness-95'">
                        <span class="material-symbols-rounded text-[14px]" x-text="$store.sound.isAllMuted({{ $allChannelIds }}, 'channel') ? 'volume_off' : 'volume_up'"></span>
                    </button>
                @endif
                <button type="button" x-show="$store.push.supported" x-cloak
                        x-on:click="$store.push.toggle('channel')"
                        :aria-pressed="$store.push.isEnabled('channel')"
                        :aria-label="$store.push.isEnabled('channel') ? 'غیرفعال کردن اعلان مرورگر' : 'فعال کردن اعلان مرورگر'"
                        :title="$store.push.isEnabled('channel') ? 'غیرفعال کردن اعلان مرورگر' : 'فعال کردن اعلان مرورگر'"
                        class="w-6 h-6 rounded-lg flex items-center justify-center transition-all bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] active:scale-90"
                        :class="$store.push.isEnabled('channel') ? '!bg-[var(--md-sys-color-primary)] !text-[var(--md-sys-color-on-primary)]' : 'hover:brightness-95'">
                    <span class="material-symbols-rounded text-[14px]" x-text="$store.push.isEnabled('channel') ? 'notifications_active' : 'notifications_off'"></span>
                </button>
            </div>
        </div>

        @include('livewire.dashboard.channel.search-field', [
            'model' => 'search',
            'name' => 'search',
            'id' => 'search',
            'debounce' => 200,
            'placeholder' => 'جستجوی کانال...',
            'ariaLabel' => 'جستجوی کانال',
            'overlayTitle' => 'جستجوی کانال',
            'refreshSidebarOnClose' => true,
            'showLabel' => true,
            'loadingDisabled' => false,
            'wireIgnoreSelf' => true,
            'inputClass' => 'md3-input peer pr-10 pl-10 h-10 leading-[40px] rounded-xl text-sm outline-none transition-all focus:ring-2 w-full bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50 placeholder-transparent',
        ])
    </div>

    <div class="h-px mx-4 flex-shrink-0 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]"></div>

    <div class="flex-shrink-0 px-4 pt-2.5 pb-2 flex items-center gap-1.5">
        <div role="tablist" aria-label="فیلتر کانال‌ها" class="flex items-center gap-1.5">
            @foreach([['all','همه'],['unread','خوانده‌نشده']] as $f)
                <button wire:click="setFilter('{{ $f[0] }}')" role="tab"
                        aria-selected="{{ $filter === $f[0] ? 'true' : 'false' }}"
                        aria-controls="channel-list"
                    @class([ 'px-2.5 py-1 rounded-md text-[10px] font-bold transition-all duration-200',
                        'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' => $filter === $f[0],
                        'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]' => $filter !== $f[0],
                    ])>{{ $f[1] }}</button>
            @endforeach
        </div>

        <button x-on:click="openCreate()" type="button" aria-label="ساخت کانال جدید" title="ساخت کانال جدید"
                class="ms-auto w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-200 active:scale-90
                       bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]
                       hover:!bg-[var(--md-sys-color-primary)] hover:!text-[var(--md-sys-color-on-primary)]">
            <span class="material-symbols-rounded text-[18px]">add_circle</span>
        </button>

        <button x-on:click="toggleBrowse()" type="button" aria-label="کاوش و پیوستن به کانال جدید"
                title="کاوش و پیوستن به کانال جدید"
                class="w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-200 active:scale-90
                       bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]"
                :class="$wire.browseMode
                    ? '!bg-[var(--md-sys-color-primary)] !text-[var(--md-sys-color-on-primary)]'
                    : 'hover:!bg-[var(--md-sys-color-primary)] hover:!text-[var(--md-sys-color-on-primary)]'">
            <span class="material-symbols-rounded text-[18px]">explore</span>
        </button>
    </div>

    <div id="channel-list" class="flex flex-col overflow-y-auto py-1 contact-scrollbar" role="listbox">
        @forelse($visibleChannelList as $ch)
            <div wire:key="channel-{{ $ch['id'] }}" x-data="{ tagOpen: false }" x-on:click="selectChannel({{ $ch['id'] }})"
                    x-on:keydown.enter.prevent="selectChannel({{ $ch['id'] }})"
                    x-on:keydown.space.prevent="selectChannel({{ $ch['id'] }})"
                    data-rf="channel-{{ $ch['id'] }}"
                    role="option"
                    tabindex="0"
                    aria-selected="{{ $activeChannelId === $ch['id'] ? 'true' : 'false' }}"
                    :style="{ order: $store.pinned.isPinned({{ $ch['id'] }}, 'channel') ? -1 : 0 }"
                @class([
                    'group ripple-effect relative isolate w-full shrink-0 flex items-center gap-3 px-4 py-2.5 text-right transition-all duration-200 cursor-pointer rounded-md',
                    'bg-[color-mix(in_srgb,var(--md-sys-color-primary-container)_40%,transparent)] border-r-2 border-[var(--md-sys-color-primary)]' => $activeChannelId === $ch['id'],
                    'hover:bg-[var(--md-sys-color-surface-variant)]' => $activeChannelId !== $ch['id'],
                ])>

                <x-ui.row-actions :id="$ch['id']" scope="channel" pin-noun="کانال" mute-noun="کانال"/>

                <div class="relative flex-shrink-0">
                    <div @class([
                        'w-10 h-10 rounded-xl flex items-center justify-center text-sm shadow-sm ring-1 select-none',
                        'bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] ring-[var(--md-sys-color-tertiary)]' => $activeChannelId === $ch['id'],
                        'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]' => $activeChannelId !== $ch['id'],
                    ])>
                        <span class="material-symbols-rounded text-[18px]">{{ $ch['type'] === 'private' ? 'lock' : 'campaign' }}</span>
                    </div>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-1">
                        <p @class([
                            'text-[13px] truncate flex items-center gap-1.5',
                            'font-semibold text-[var(--md-sys-color-on-surface)]' => $ch['unread'],
                            'font-medium text-[var(--md-sys-color-on-surface-variant)]' => !$ch['unread'],
                        ])>
                            {{ $ch['name'] }}
                            @if(empty($ch['is_entered']))
                                <span class="w-2 h-2 rounded-full bg-[var(--md-sys-color-primary)] flex-shrink-0" title="جدید" aria-hidden="true"></span>
                            @endif
                        </p>
                        @if($ch['last_message'])
                            <time @class([
                                'text-[10px] flex-shrink-0',
                                'text-[var(--md-sys-color-primary)] font-medium' => $ch['unread'],
                                'text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_60%,transparent)]' => !$ch['unread'],
                            ]) datetime="{{ $ch['last_message']['datetime'] }}">
                                {{ $ch['last_message']['time'] }}
                            </time>
                        @endif
                    </div>
                    @if(!empty($ch['slug_handle']))
                        <div class="text-[10px] truncate -mt-0.5" dir="auto" style="color: color-mix(in srgb, var(--md-sys-color-on-surface-variant) 70%, transparent);">{{ $ch['slug_handle'] }}</div>
                    @endif

                    <div class="flex items-center justify-between gap-1 mt-px">
                        @if($ch['last_message'])
                            <p @class([
                                'text-[11px] truncate flex items-center gap-1',
                                'text-[var(--md-sys-color-on-surface)] font-medium' => $ch['unread'],
                                'text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_70%,transparent)]' => !$ch['unread'],
                            ])>
                                @if($ch['last_message']['is_mine'])
                                    <span class="material-symbols-rounded text-[11px] flex-shrink-0 text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_50%,transparent)]">send</span>
                                @endif
                                {{ $ch['last_message']['body'] }}
                            </p>
                        @else
                            <p class="text-[11px] truncate text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_60%,transparent)]">
                                {{ $ch['description'] ?: 'پیامی وجود ندارد' }}
                            </p>
                        @endif

                        @if(!empty($ch['members_count']))
                            <span class="flex-shrink-0 inline-flex items-center gap-0.5 text-[10px] font-medium text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_60%,transparent)]" title="تعداد اعضا">
                                <span class="material-symbols-rounded text-[12px]" aria-hidden="true">group</span>{{ convertToPersian((string) $ch['members_count']) }}
                            </span>
                        @endif

                        @if($ch['unread'])
                            <span class="flex-shrink-0 min-w-[18px] h-[18px] px-1 rounded-md text-[10px] font-bold
                                         flex items-center justify-center
                                         bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]">
                                {{ $ch['unread'] > 99 ? '⁹⁹⁺' : $ch['unread'] }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <x-ui.empty icon="campaign" title="کانالی یافت نشد" variant="search" />
        @endforelse

        @if($hasMoreChannels)
            <x-ui.buttons.load-more action="loadMoreChannels" text="نمایش بیشتر" loading-text="در حال بارگذاری…"
                                     class="mx-auto my-2 px-4 py-2 rounded-xl text-xs font-medium bg-[var(--md-sys-color-surface-variant)]/50 text-[var(--md-sys-color-on-surface-variant)]"/>
        @endif
    </div>
</aside>
