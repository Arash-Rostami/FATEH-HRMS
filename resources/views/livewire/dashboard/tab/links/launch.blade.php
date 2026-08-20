@include('livewire.dashboard.tab.links.smart', ['openDefault' => true])

@php
    $ip = request()->ip();
    $hk = 0;
    $launch = $linkPresenter->launchData($this->internalLinks, $this->externalLinks);
    $sections = $launch['sections'];
    $hasLinks = $launch['hasLinks'];
@endphp

@if($hasLinks)
    <div class="flex items-center justify-center gap-1.5 py-1 text-[11px] text-[var(--md-sys-color-on-surface-variant)]">
        <span class="material-symbols-rounded text-[14px]">keyboard</span>
        <span>کلیدهای ۱ تا ۹ = دسترسی سریع به لینک‌های ابتدایی</span>
    </div>

    <div class="space-y-6">
        @foreach($sections as $section)
            @if($section['links']->isNotEmpty())
                <section class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="flex items-center justify-center w-6 h-6 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                            <span class="material-symbols-rounded text-[16px]">{{ $section['icon'] }}</span>
                        </span>
                        <h3 class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">{{ $section['label'] }}</h3>
                        <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)]">{{ convertToPersian($section['links']->count()) }}</span>
                        <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/20"></div>
                    </div>

                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 xl:grid-cols-10 gap-3 md:gap-4">
                        @foreach($section['links'] as $link)
                            @php
                                $d = $linkPresenter->launchLinkData($link, $section['kind'], $ip, $hk);
                                $resolved = $d['resolved'];
                                $hotkey = $d['hotkey'];
                                $target = $d['target'];
                                $rel = $d['rel'];
                                $initial = $d['initial'];
                                $pickedIcon = $d['pickedIcon'];
                                $clickPayload = $d['clickPayload'];
                            @endphp
                            <a
                                wire:key="launch-{{ $link->id }}"
                                data-rf="links-{{ $link->id }}"
                                href="{{ $resolved }}"
                                target="{{ $target }}"
                                rel="{{ $rel }}"
                                @if($hotkey) data-hotkey="{{ $hotkey }}" @endif
                                @if($link->url_description) title="{{ $link->url_description }}" @endif
                                x-on:click="recordClick({{ $clickPayload }})"
                                class="group/launch relative flex flex-col items-center gap-2 p-3 rounded-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/20 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md hover:border-[var(--md-sys-color-primary)]/30 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]"
                            >
                                @if($hotkey)
                                    <span class="absolute top-1.5 right-1.5 z-10 min-w-5 h-5 px-1 rounded-md bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] text-[10px] font-bold flex items-center justify-center group-hover/launch:bg-[var(--md-sys-color-primary)] group-hover/launch:text-[var(--md-sys-color-on-primary)] transition-colors">{{ convertToPersian($hotkey) }}</span>
                                @endif

                                <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_60%,transparent)] ring-1 ring-transparent flex items-center justify-center overflow-hidden transition-all duration-300 group-hover/launch:ring-[var(--md-sys-color-primary)]/30 group-hover/launch:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_8%,transparent)]">
                                    @if($link->image)
                                        <img src="{{ $link->image_url }}" alt="{{ $link->image_description ?: $link->url_title }}" class="w-full h-full object-contain transition-transform duration-500 group-hover/launch:scale-110" loading="lazy">
                                    @elseif($link->icon)
                                        <img src="{{ $link->icon_url }}" alt="{{ $link->icon_description ?: $link->url_title }}" class="w-7 h-7 object-contain transition-transform duration-500 group-hover/launch:scale-110" loading="lazy">
                                    @elseif($pickedIcon)
                                        <span class="material-symbols-rounded text-2xl text-[var(--md-sys-color-primary)] transition-transform duration-500 group-hover/launch:scale-110" title="{{ $pickedIcon->getLabel() }}">{{ $pickedIcon->value }}</span>
                                    @elseif($initial !== '')
                                        <span class="text-2xl font-bold text-[var(--md-sys-color-primary)] transition-transform duration-500 group-hover/launch:scale-110">{{ $initial }}</span>
                                    @else
                                        <span class="material-symbols-rounded text-2xl text-[var(--md-sys-color-primary)]">{{ $section['kind'] === 'internal' ? 'dataset_linked' : 'public' }}</span>
                                    @endif
                                </div>

                                <span class="text-[11px] md:text-xs font-medium text-[var(--md-sys-color-on-surface)] text-center line-clamp-2 leading-tight w-full group-hover/launch:text-[var(--md-sys-color-primary)] transition-colors">{{ $link->url_title }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    </div>
@else
    @if($search !== '' || $activeFilter !== 'all')
        <x-ui.empty icon="search_off" title="نتیجه‌ای یافت نشد" description="جستجو یا فیلتر را تغییر دهید." variant="list" />
    @else
        <x-ui.empty icon="link_off" title="هیچ لینکی تعریف نشده" description="لینک‌ها توسط مدیر تعریف می‌شوند." variant="list" />
    @endif
@endif