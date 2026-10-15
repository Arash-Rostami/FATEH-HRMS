@php
    $p = $this->presenter;
    $channel = $this->activeChannel;
    $header = $p->channelHeader($channel);
    $isOwner = $channel
        && $channel->owner_id !== null
        && auth()->check()
        && (int) $channel->owner_id === (int) auth()->id();
    $typingMembers = $this->typingMembers ?? [];
    $typingCount = count($typingMembers);
    $channelIdJs = \Illuminate\Support\Js::from($header['id']);
    $activeMembers = collect($this->channelMembers)->reject(fn (array $m) => $m['inactive']);
    $onlineCount = $activeMembers->where('is_online', true)->count();
    $lastMsgAt = collect($this->channels)->firstWhere('id', $header['id'])['last_message']['created_at'] ?? null;
    $lastActivity = $lastMsgAt && \Illuminate\Support\Carbon::parse($lastMsgAt)->diffInMinutes(now()) >= 5
        ? toJalaliRelative($lastMsgAt, dateAfterDays: 0)
        : null;
@endphp

<header class="relative z-20 flex h-16 flex-shrink-0 items-center justify-between gap-2.5 md:gap-4 border-b px-3 md:px-5 transition-all duration-300
               bg-[color-mix(in_srgb,var(--md-sys-color-surface)_92%,transparent)]
               border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)]
               shadow-[0_8px_32px_color-mix(in_srgb,var(--md-sys-color-primary)_15%,transparent)]"
        :class="headerDeclutter ? 'h-0! py-0! overflow-hidden! pointer-events-none! border-b-0! shadow-none!' : ''">

    <div class="flex min-w-0 flex-1 items-center gap-2.5 md:gap-3.5">
        <button type="button"
                x-on:click="backToList()"
                aria-label="بازگشت"
                x-show="!max"
                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95 md:hidden">
            <span class="material-symbols-rounded text-base">arrow_forward</span>
        </button>

        <div class="relative flex-shrink-0">
            <div
                class="flex h-11 w-11 select-none items-center justify-center rounded-2xl shadow-sm ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]"
                style="background: color-mix(in srgb, {{ $header['type_color'] }} 14%, transparent);">
                <span class="material-symbols-rounded text-xl"
                      style="color: {{ $header['type_color'] }}">{{ $header['type_icon'] }}</span>
            </div>
        </div>

        <div class="relative min-w-0 flex-1"
             x-data="{ membersOpen: false }"
             x-on:keydown.escape.window="membersOpen = false">

            <div class="flex min-w-0 items-center gap-2">
                <h2 class="truncate text-sm md:text-base font-bold tracking-tight text-[var(--md-sys-color-on-surface)] cursor-pointer transition-opacity hover:opacity-80"
                    title="{{ $header['slug'] }}"
                    x-on:click="copyText(@js($header['slug']), 'اسلاگ گروه کپی شد', 'info')">
                    {{ $header['name'] }}
                </h2>

                <span @class([
                        'flex-shrink-0 rounded-md px-2 py-0.5 text-[10px] font-bold tracking-wide',
                        'bg-[color-mix(in_srgb,var(--md-sys-color-success)_15%,transparent)] text-[var(--md-sys-color-success)]' => $header['type'] === 'open',
                        'bg-[color-mix(in_srgb,var(--md-sys-color-warning)_15%,transparent)] text-[var(--md-sys-color-warning)]' => $header['type'] === 'private',
                    ]) title="{{ $header['type_label'] }}">
                    {{ $header['type_label'] }}
                </span>

                <button type="button"
                        x-on:click="membersOpen = !membersOpen"
                        :aria-expanded="membersOpen"
                        aria-label="لیست اعضای گروه"
                        class="inline-flex flex-shrink-0 items-center gap-0.5 rounded-md px-1.5 py-0.5 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] transition-colors hover:bg-[var(--md-sys-color-surface-variant)] md:hidden">
                    <span class="material-symbols-rounded text-[13px]" aria-hidden="true">group</span>
                    <span>{{ convertToPersian($header['members_count']) }}</span>
                </button>
            </div>

            <div class="mt-0.5 flex min-w-0 items-center gap-2 text-xs">
                <button type="button"
                        x-on:click="membersOpen = !membersOpen"
                        :aria-expanded="membersOpen"
                        title=" اعضا"
                        class="hidden md:inline-flex flex-shrink-0 items-center gap-1 font-medium text-[var(--md-sys-color-on-surface-variant)] transition-colors hover:text-[var(--md-sys-color-primary)]">
                    <span class="material-symbols-rounded text-[13px]" aria-hidden="true">group</span>
                </button>

                <span wire:key="channel-header-typing"
                      class="font-medium inline-flex items-center gap-1.5 min-w-0 {{ $typingCount ? 'text-[var(--md-sys-color-primary)]' : 'text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_70%,transparent)]' }}"
                      data-typing="{{ $typingCount }}"
                      @if($typingCount) title="{{ implode('، ', $typingMembers) }}" @endif>
                    @if($typingCount)
                        <span wire:key="channel-header-typing-label" class="truncate">
                            @if($typingCount === 1)
                                {{ $typingMembers[0] }} در حال نوشتن
                            @else
                                {{ convertToPersian($typingCount) }} نفر در حال نوشتن
                            @endif
                        </span>
                        <span wire:key="channel-header-typing-dots" class="inline-flex items-center gap-0.5 shrink-0">
                            <span class="h-1 w-1 rounded-full bg-current animate-typing-dot-1"></span>
                            <span class="h-1 w-1 rounded-full bg-current animate-typing-dot-2"></span>
                            <span class="h-1 w-1 rounded-full bg-current animate-typing-dot-3"></span>
                        </span>
                    @else
                        @if($onlineCount)
                            <span wire:key="channel-header-presence-online"
                                  class="inline-flex min-w-0 items-center gap-1 font-medium text-[var(--md-sys-color-success)]"
                                  title="مالک: {{ $header['owner_name'] }} · {{ convertToPersian($header['members_count']) }} عضو">
                                <span
                                    class="h-1.5 w-1.5 shrink-0 rounded-full bg-[var(--md-sys-color-success)] shadow-[0_0_6px_color-mix(in_srgb,var(--md-sys-color-success)_60%,transparent)]"></span>
                                <span class="truncate">
                                    {{ convertToPersian($onlineCount) }}
                                    <span class="text-[var(--md-sys-color-primary)]">
                                      /{{ convertToPersian($header['members_count']) }}
                                                                           آنلاین
                                    </span>
                                </span>
                            </span>
                        @else
                            <span wire:key="channel-header-owner" class="truncate">{{ $header['owner_name'] }}</span>
                        @endif
                    @endif
                </span>

                @if($lastActivity)
                    <span wire:key="channel-header-activity"
                          class="hidden md:inline-flex flex-shrink-0 items-center gap-1 text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_55%,transparent)]"
                          title="آخرین پیام در این گروه">
                        <span class="material-symbols-rounded text-[13px]" aria-hidden="true">history</span>
                        <span class="truncate">آخرین پیام {{ $lastActivity }}</span>
                    </span>
                @endif
            </div>

            <div x-show="membersOpen"
                 x-cloak
                 x-on:click.outside="membersOpen = false"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                 class="absolute top-full mt-2 start-0 z-40 w-64 max-h-72 overflow-y-auto custom-scrollbar p-1.5 rounded-2xl bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_12px_48px_color-mix(in_srgb,var(--md-sys-color-shadow)_18%,transparent)]">
                @foreach($this->channelMembers as $member)
                    <div
                        class="flex items-center gap-2 px-2 py-1.5 rounded-xl hover:bg-[var(--md-sys-color-surface-variant)]/50 transition-colors">
                        <div class="flex-shrink-0">
                            <x-ui.avatar :existingImage="$member['avatar_url']" :alt="$member['name']"
                                         :inactive="$member['inactive']"
                                         size="sm" :online="$member['is_online']" :status="$member['presence']"
                                         :focus="$member['focus']"/>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1 flex-wrap">
                                <span
                                    class="truncate text-xs font-bold text-[var(--md-sys-color-on-surface)]">{{ $member['name'] }}</span>
                                @if($member['is_owner'])
                                    <span
                                        class="flex-shrink-0 text-[9px] font-bold px-1 py-0.5 rounded-md bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">مالک</span>
                                @endif
                                @if($member['inactive'])
                                    <x-ui.badge class="flex-shrink-0 !px-1.5 !py-0.5 !text-[10px]">غیرفعال</x-ui.badge>
                                @elseif($member['presence_label'])
                                    <span
                                        class="flex-shrink-0 inline-flex items-center px-1 py-0.5 rounded-full text-[9px] font-bold {{ $member['presence_class'] }}">{{ $member['presence_label'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @include('livewire.dashboard.messaging.search', ['placeholder' => 'جستجو در پیام‌های این گروه...', 'overlayTitle' => 'جستجو در پیام‌های گروه'])
            @include('livewire.dashboard.messaging.days', ['title' => 'روزهای دارای پیام در این گروه', 'empty' => 'پیامی در این گروه وجود ندارد'])
            @include('livewire.dashboard.messaging.bookmarks', ['title' => 'پیام‌های نشان‌شدهٔ این گروه', 'empty' => 'هنوز پیامی در این گروه نشان نکرده‌اید؛ با نگه‌داشتن نشانگر روی پیام، دکمهٔ نشان را بزنید'])
        </div>
    </div>

    <div
        class="flex flex-shrink-0 items-center gap-1">
        @component('livewire.dashboard.messaging.header-actions', ['closeLabel' => 'بازگشت (بستن گروه)'])
            @slot('sound')
                <button type="button"
                        x-on:click="$store.sound.toggleMute({{ $channelIdJs }})"
                        :aria-pressed="$store.sound.isMuted({{ $channelIdJs }})"
                        :aria-label="$store.sound.isMuted({{ $channelIdJs }}) ? 'باصدا کردن گروه' : 'بی‌صدا کردن گروه'"
                        :title="$store.sound.isMuted({{ $channelIdJs }}) ? 'باصدا کردن گروه' : 'بی‌صدا کردن گروه'"
                        class="h-8 w-8 items-center justify-center rounded-xl transition-all duration-200 ease-out active:scale-95"
                        :class="[$store.sound.isMuted({{ $channelIdJs }}) ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)]', headerBtnClass(false)]">
                    <span class="material-symbols-rounded text-base"
                          x-text="$store.sound.isMuted({{ $channelIdJs }}) ? 'volume_off' : 'volume_up'"></span>
                </button>
            @endslot
            @slot('overflow')
                <button type="button"
                        x-on:click="open = false; $store.sound.toggleMute({{ $channelIdJs }})"
                        class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium hover:bg-[var(--md-sys-color-surface-container-high)]/50">
                    <span class="material-symbols-rounded text-[16px]"
                          x-text="$store.sound.isMuted({{ $channelIdJs }}) ? 'volume_off' : 'volume_up'"></span>
                    <span
                        x-text="$store.sound.isMuted({{ $channelIdJs }}) ? 'باصدا کردن گروه' : 'بی‌صدا کردن گروه'"></span>
                </button>
                @if(!$isOwner)
                    <button type="button"
                            x-on:click="open = false; {{ confirmDialog('leaveChannel', 'خروج از گروه', 'از این گروه خارج می‌شوید؟', $header['id']) }}"
                            class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium text-[var(--md-sys-color-error)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-error)_10%,transparent)]">
                        <span class="material-symbols-rounded text-[16px]">logout</span>
                        <span>خروج از گروه</span>
                    </button>
                @endif
            @endslot
        @endcomponent

        @if(!$isOwner)
            <button type="button"
                    x-on:click="{{ confirmDialog('leaveChannel', 'خروج از گروه', 'از این گروه خارج می‌شوید؟', $header['id']) }}"
                    aria-label="خروج از گروه"
                    title="خروج از گروه"
                    class="h-8 w-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out hover:bg-[var(--md-sys-color-error)] hover:text-[var(--md-sys-color-on-error)] hover:shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-error)_40%,transparent)] active:scale-95"
                    :class="headerBtnClass(false)">
                <span class="material-symbols-rounded text-base">logout</span>
            </button>
        @endif
    </div>
</header>
