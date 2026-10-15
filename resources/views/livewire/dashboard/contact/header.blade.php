@php
    $contact = $this->activeContact;
    $archived = (bool) $this->activeContactArchived;
    $inactive = ! $contact->isActive();
    $presence = $archived ? null : \App\Enums\PresenceStatus::tryFrom($contact->presence->value ?? '');
    $profile = $contact->profile;
    $hasPosition = (bool) $profile?->has_display_position;
    $department = $profile?->department;
    $hasOrgMeta = $hasPosition || $department;
    $lastSeenText = $archived ? null : (toJalaliRelative($contact->last_seen) ?: 'نامشخص');
    $contactIdJs = \Illuminate\Support\Js::from($contact->id);
@endphp

<header class="relative z-20 flex h-16 flex-shrink-0 items-center justify-between gap-3 border-b px-3 sm:px-5 backdrop-blur-xl transition-all duration-300
               bg-[color-mix(in_srgb,var(--md-sys-color-surface)_94%,transparent)]
               border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_45%,transparent)]
               shadow-[0_2px_12px_-2px_color-mix(in_srgb,var(--md-sys-color-on-surface)_5%,transparent)]"
        :class="headerDeclutter ? 'h-0! py-0! overflow-hidden! pointer-events-none! border-b-0! shadow-none!' : ''">

    <div class="flex min-w-0 flex-1 items-center gap-3">
        <button type="button"
                x-on:click="backToList()"
                x-show="!max"
                aria-label="بازگشت به لیست گفتگوها"
                class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl text-[var(--md-sys-color-on-surface-variant)] transition-all duration-150 hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] active:scale-95 md:hidden">
            <span class="material-symbols-rounded text-lg leading-none">arrow_forward</span>
        </button>

        <div class="relative flex-shrink-0">
            <x-dashboard.user-avatar :user="$contact" size="md" :dot="! $archived" :badge="! $archived" />
        </div>

        <div x-data="{ infoOpen: false }"
             x-on:keydown.escape.window="infoOpen = false"
             class="relative min-w-0 flex-1">

            <div class="flex min-w-0 items-center gap-2">
                <h2 class="truncate text-sm font-bold tracking-tight text-[var(--md-sys-color-on-surface)] sm:text-base">
                    {{ $contact->name }}
                </h2>

                @if($inactive)
                    <x-ui.badge wire:key="contact-header-inactive" class="flex-shrink-0 !px-1.5 !py-0.5 !text-[10px]">
                        غیرفعال
                    </x-ui.badge>
                @endif

                @if($hasOrgMeta)
                    <div class="hidden min-w-0 items-center gap-1.5 lg:flex">
                        <span class="h-3 w-px flex-shrink-0 bg-[var(--md-sys-color-outline-variant)] opacity-60"></span>

                        @if($department)
                            <span class="flex-shrink-0 rounded-md bg-[var(--md-sys-color-secondary-container)]/70 px-1.5 py-0.5 text-[10px] font-semibold text-[var(--md-sys-color-on-secondary-container)]"
                                  title="{{ $department->tooltipLabel() }}">
                                {{ $department->displayLabel() }}
                            </span>
                        @endif

                        @if($hasPosition)
                            <span class="truncate text-xs font-medium text-[var(--md-sys-color-on-surface-variant)]"
                                  title="{{ $profile->display_position }}">
                                {{ $profile->display_position }}
                            </span>
                        @endif
                    </div>

                    <button type="button"
                            x-on:click="infoOpen = !infoOpen"
                            x-on:mouseenter="infoOpen = true"
                            x-on:mouseleave="infoOpen = false"
                            :aria-expanded="infoOpen"
                            aria-label="اطلاعات سازمانی مخاطب"
                            class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-md text-[var(--md-sys-color-on-surface-variant)] transition-colors hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] lg:hidden">
                        <span class="material-symbols-rounded text-[15px] leading-none">badge</span>
                    </button>
                @endif
            </div>

            <div class="mt-0.5 flex min-w-0 items-center gap-2 text-xs leading-tight">
                @unless($archived)
                    <div wire:key="contact-header-status"
                         class="flex min-w-0 items-center gap-1.5"
                         data-typing="{{ $this->peerTyping ? '1' : '0' }}">
                        @if($this->peerTyping)
                            <span wire:key="contact-header-typing" class="inline-flex min-w-0 items-center gap-1.5 font-semibold text-[var(--md-sys-color-primary)]">
                                <span class="truncate">در حال نوشتن</span>
                                <span class="inline-flex flex-shrink-0 items-center gap-0.5">
                                    <span class="h-1 w-1 rounded-full bg-current animate-typing-dot-1"></span>
                                    <span class="h-1 w-1 rounded-full bg-current animate-typing-dot-2"></span>
                                    <span class="h-1 w-1 rounded-full bg-current animate-typing-dot-3"></span>
                                </span>
                            </span>
                        @else
                            @if($presence)
                                <span class="inline-flex flex-shrink-0 items-center gap-1 font-medium text-[var(--md-sys-color-on-surface-variant)]"
                                      title="{{ $presence->label() }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $presence->iconBgClass() }}"></span>
                                    <span>{{ $presence->label() }}</span>
                                </span>
                                <span class="flex-shrink-0 text-[var(--md-sys-color-outline)] opacity-50">•</span>
                            @endif

                            <span wire:key="contact-header-last-seen"
                                  class="truncate text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_75%,transparent)]">
                                {{ $lastSeenText }}
                            </span>
                        @endif
                    </div>
                @else
                    <span class="truncate font-medium text-[var(--md-sys-color-on-surface-variant)] opacity-75">
                        گفتگوی آرشیو شده
                    </span>
                @endunless
            </div>

            @if($hasOrgMeta)
                <div x-cloak
                     x-show="infoOpen"
                     x-on:click.outside="infoOpen = false"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                     class="absolute start-0 top-full z-30 mt-2 w-60 rounded-xl border border-[var(--md-sys-color-outline-variant)]/60 bg-[var(--md-sys-color-surface-container-high)] p-3 shadow-lg backdrop-blur-xl lg:hidden">
                    <div class="flex flex-col gap-2 text-xs">
                        @if($hasPosition)
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[var(--md-sys-color-on-surface-variant)]">سمت سازمانی</span>
                                <span class="truncate font-semibold text-[var(--md-sys-color-on-surface)]">{{ $profile->display_position }}</span>
                            </div>
                        @endif
                        @if($department)
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[var(--md-sys-color-on-surface-variant)]">واحد</span>
                                <span class="rounded-md bg-[var(--md-sys-color-secondary-container)] px-1.5 py-0.5 text-[10px] font-semibold text-[var(--md-sys-color-on-secondary-container)]">
                                    {{ $department->displayLabel() }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @include('livewire.dashboard.messaging.search', ['placeholder' => 'جستجو در پیام‌های این گفتگو...', 'overlayTitle' => 'جستجو در پیام‌های گفتگو'])
            @include('livewire.dashboard.messaging.days', ['title' => 'روزهای دارای پیام در این گفتگو', 'empty' => 'پیامی در این گفتگو وجود ندارد'])
            @include('livewire.dashboard.messaging.bookmarks', ['title' => 'پیام‌های نشان‌شدهٔ این گفتگو', 'empty' => 'هنوز پیامی در این گفتگو نشان نکرده‌اید؛ با نگه‌داشتن نشانگر روی پیام، دکمهٔ نشان را بزنید'])
        </div>
    </div>

    <div class="flex flex-shrink-0 items-center gap-1">
        @component('livewire.dashboard.messaging.header-actions', ['closeLabel' => 'بازگشت (بستن گفتگو)'])
            @slot('sound')
                <button type="button"
                        x-on:click="$store.sound.toggleMute({{ $contactIdJs }}, 'contact')"
                        :aria-pressed="$store.sound.isMuted({{ $contactIdJs }}, 'contact')"
                        :aria-label="$store.sound.isMuted({{ $contactIdJs }}, 'contact') ? 'باصدا کردن' : 'بی‌صدا کردن'"
                        :title="$store.sound.isMuted({{ $contactIdJs }}, 'contact') ? 'باصدا کردن' : 'بی‌صدا کردن'"
                        class="h-8 w-8 items-center justify-center rounded-xl transition-all duration-150 active:scale-95"
                        :class="[$store.sound.isMuted({{ $contactIdJs }}, 'contact')
                            ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-sm'
                            : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)]',
                            headerBtnClass(false)]">
                    <span class="material-symbols-rounded text-[18px] leading-none"
                          x-text="$store.sound.isMuted({{ $contactIdJs }}, 'contact') ? 'volume_off' : 'volume_up'"></span>
                </button>
            @endslot

            @slot('overflow')
                <button type="button"
                        x-on:click="open = false; $store.sound.toggleMute({{ $contactIdJs }}, 'contact')"
                        class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-xs font-medium text-[var(--md-sys-color-on-surface)] transition-colors hover:bg-[var(--md-sys-color-surface-container-high)]">
                    <span class="material-symbols-rounded text-[16px] leading-none text-[var(--md-sys-color-on-surface-variant)]"
                          x-text="$store.sound.isMuted({{ $contactIdJs }}, 'contact') ? 'volume_off' : 'volume_up'"></span>
                    <span x-text="$store.sound.isMuted({{ $contactIdJs }}, 'contact') ? 'باصدا کردن گفتگو' : 'بی‌صدا کردن گفتگو'"></span>
                </button>
            @endslot
        @endcomponent
    </div>

</header>
