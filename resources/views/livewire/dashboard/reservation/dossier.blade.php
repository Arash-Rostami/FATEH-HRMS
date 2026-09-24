    @if($this->open && count($this->resources) > 1)
        <a wire:key="dossier-skip-link" href="#dossier-card-{{ $this->open }}" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:right-2 focus:z-50 focus:px-4 focus:py-2 focus:rounded-xl focus:bg-[var(--md-sys-color-primary)] focus:text-[var(--md-sys-color-on-primary)] focus:text-sm focus:font-bold focus:shadow-lg">
            رفتن به کارت باز
        </a>
    @endif

    @if($this->resources->isEmpty())
        <div wire:key="reservation-avantgarde-empty" class="contents">
            <x-ui.empty icon="search_off" title="هیچ موردی یافت نشد" description="برای تاریخ و فیلترهای انتخاب شده، هیچ موردی جهت رزرو وجود ندارد. لطفاً تاریخ دیگری را امتحان کنید." variant="list" watermark="event_busy" />
        </div>
    @else
        <div id="reservation-dossier-stage" wire:key="reservation-dossier-stage" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 items-start">
            @foreach($this->resources as $resource)
                @php($isBlocked = $this->bookingBlockReason !== null)
                @php($hero = $resource->metadata['capacity'] ?? $resource->metadata['floor'] ?? null)
                <div
                    id="dossier-card-{{ $resource->id }}"
                    wire:key="dossier-card-{{ $resource->id }}"
                    data-rf="resource-{{ $resource->id }}"
                    data-resource-id="{{ $resource->id }}"
                    tabindex="0"
                    x-on:click="deckOpen({{ $resource->id }})"
                    x-on:keydown.enter.self="deckOpen({{ $resource->id }})"
                    x-on:keydown.space.self.prevent="deckOpen({{ $resource->id }})"
                    x-on:keydown.arrow-left.prevent="deckCycleAndOpen(1)"
                    x-on:keydown.arrow-right.prevent="deckCycleAndOpen(-1)"
                    x-effect="if ($wire.open == {{ $resource->id }}) $el.focus({ preventScroll: true })"
                    :class="deckDepth({{ $resource->id }}) === 0 ? 'col-span-full' : ''"
                    :style="`order: ${deckDepth({{ $resource->id }})};`"
                    class="group rounded-2xl border border-[var(--md-sys-color-outline-variant)]/50 shadow-[var(--md-sys-elevation-1)] bg-[var(--md-sys-color-surface)] outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)] cursor-pointer"
                >
                    <div x-show="deckDepth({{ $resource->id }}) === 0" x-cloak class="relative flex flex-col">
                        <div class="relative h-56 lg:h-80 overflow-hidden rounded-t-2xl flex flex-col">
                            <div class="relative flex-1 min-h-0">
                                @if($resource->display_image_url)
                                    <img src="{{ $resource->display_image_url }}" alt="{{ $resource->name }}" class="absolute inset-0 w-full h-full object-cover">
                                    <button type="button"
                                        wire:click="$set('zoomImageUrl', '{{ $resource->display_image_url }}')"
                                        x-on:click.stop
                                        class="absolute top-3 left-3 w-10 h-10 rounded-xl bg-black/30 hover:bg-black/50 border border-white/10 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 z-20 shadow-xl"
                                        title="بزرگنمایی"
                                    >
                                        <span class="material-symbols-rounded text-[22px]">zoom_in</span>
                                    </button>
                                @else
                                    <div
                                        class="absolute inset-0 w-full h-full flex items-center justify-center bg-[var(--md-sys-color-surface-variant)]"
                                        style="background-image: radial-gradient(color-mix(in srgb, var(--md-sys-color-on-surface) 8%, transparent) 1px, transparent 1px); background-size: 20px 20px;"
                                    >
                                        <span class="material-symbols-rounded !text-6xl text-[var(--md-sys-color-primary)] opacity-50">{{ $resource->icon }}</span>
                                    </div>
                                @endif
                                <div class="absolute bottom-2.5 right-3 z-10 inline-flex items-center gap-2 px-2 py-1.5 bg-black/40 border border-white/10 rounded-lg text-white shadow-lg">
                                    <span class="material-symbols-rounded text-[18px] shrink-0">{{ $resource->icon }}</span>
                                    <span class="font-black text-[13px] whitespace-nowrap">{{ $resource->name }}</span>
                                </div>

                                <div class="contents" x-on:click.stop>
                                    <x-ui.buttons.rail-arrows
                                        position="plate"
                                        prev="deckCycleAndOpen(-1)"
                                        next="deckCycleAndOpen(1)"
                                        prev-show="deckOrder.length > 1"
                                        next-show="deckOrder.length > 1"
                                    />
                                </div>
                            </div>

                            @php($busyCount = count($this->openResourceBusySegments))
                            @if($hero !== null || count($resource->formatted_metadata) > 0 || $busyCount > 0)
                                <div wire:key="dossier-details-rail" class="relative z-10 shrink-0 flex flex-nowrap items-center gap-1.5 h-9 px-2 overflow-x-auto custom-scrollbar bg-[var(--md-sys-color-surface-container)] border-t border-[var(--md-sys-color-outline-variant)]/40">
                                    @if($hero !== null)
                                        <div wire:key="dossier-hero-chip" class="inline-flex items-center gap-1 shrink-0 text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)]">
                                            <span class="material-symbols-rounded text-[15px] text-[var(--md-sys-color-primary)]">group</span>
                                            <span class="whitespace-nowrap">{{ convertToPersian((string) $hero) }}</span>
                                        </div>
                                    @endif
                                    @foreach($resource->formatted_metadata as $item)
                                        <div wire:key="dossier-meta-{{ $loop->iteration }}" class="inline-flex items-center gap-1 shrink-0 text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)]">
                                            <span class="material-symbols-rounded text-[15px] text-[var(--md-sys-color-primary)]">{{ $item->icon ?? 'label' }}</span>
                                            <span class="whitespace-nowrap">{{ $item->label }}{{ $item->value }}</span>
                                        </div>
                                    @endforeach
                                    @if($busyCount > 0)
                                        <div wire:key="dossier-busy-chip" class="inline-flex items-center gap-1 shrink-0 text-[11px] font-bold text-[var(--md-sys-color-on-surface-variant)]">
                                            <span class="material-symbols-rounded text-[15px] text-[var(--md-sys-color-primary)]">schedule</span>
                                            <span class="whitespace-nowrap">{{ convertToPersian((string) $busyCount) }} رزرو دیگر امروز</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="p-5 flex-1 lg:flex-none flex flex-col gap-3">

                            @if($resource->metadata['notes'] ?? null)
                                <details wire:key="dossier-notes" class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">
                                    <summary class="cursor-pointer font-bold text-[var(--md-sys-color-primary)]">یادداشت</summary>
                                    <p class="mt-1 whitespace-pre-wrap leading-6">{{ $resource->metadata['notes'] }}</p>
                                </details>
                            @endif

                            <div class="mt-auto pt-2">
                                @if($isBlocked)
                                    <div wire:key="dossier-block-note" class="flex items-center gap-1.5 mb-2">
                                        <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-error)]">block</span>
                                        <span class="text-[11px] font-semibold text-[var(--md-sys-color-error)] leading-tight">{{ $this->bookingBlockReason }}</span>
                                    </div>
                                @endif

                                <span @click.stop>
                                    <x-ui.buttons.submit
                                        wire:click="book({{ $resource->id }})"
                                        :disabled="$isBlocked"
                                        :target="'book('.$resource->id.')'"
                                        :text="!$isFullDayTab ? 'ثبت زمان ('.convertToPersian($startTime).' - '.convertToPersian($endTime).')' : 'ثبت رزرو کامل'"
                                        loadingText="در حال ثبت..."
                                        icon="add_circle"
                                        iconSize="text-[18px]"
                                        class="w-full h-11 rounded-xl font-black text-[12px] shadow-md shadow-[var(--md-sys-color-primary)]/20"
                                    />
                                </span>
                            </div>
                        </div>
                    </div>

                    <div x-show="deckDepth({{ $resource->id }}) !== 0" x-cloak class="flex flex-col items-center justify-center gap-1.5 px-3 py-4 bg-[var(--md-sys-color-surface-container-high)] text-center rounded-2xl">
                        <x-ui.hover-popover class="!block w-full" alignment="top-full right-0 mt-2 origin-top-right" width="w-56" surface="default">
                            <x-slot:trigger>
                                <div class="flex flex-col items-center justify-center gap-1.5">
                                    <span class="material-symbols-rounded text-2xl text-[var(--md-sys-color-primary)]">{{ $resource->icon }}</span>
                                    <span class="font-bold text-[11px] text-[var(--md-sys-color-on-surface)] truncate w-full">{{ $resource->name }}</span>
                                </div>
                            </x-slot:trigger>
                            <x-slot:body>
                                <div class="px-3 py-2 flex flex-col gap-1.5" dir="auto">
                                    @if($hero !== null)
                                        <div class="flex items-center gap-1.5 text-xs font-bold text-[var(--md-sys-color-on-surface)]">
                                            <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-primary)]">group</span>
                                            <span>{{ convertToPersian((string) $hero) }}</span>
                                        </div>
                                    @endif
                                    @foreach($resource->formatted_metadata as $item)
                                        <div class="flex items-center gap-1.5 text-xs text-[var(--md-sys-color-on-surface-variant)]">
                                            <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-primary)]">{{ $item->icon ?? 'label' }}</span>
                                            <span>{{ $item->label }}{{ $item->value }}</span>
                                        </div>
                                    @endforeach
                                    @if($hero === null && count($resource->formatted_metadata) === 0)
                                        <div class="text-xs text-[var(--md-sys-color-on-surface-variant)]">{{ $resource->name }}</div>
                                    @endif
                                </div>
                            </x-slot:body>
                        </x-ui.hover-popover>
                    </div>
                </div>
            @endforeach
        </div>

        <x-ui.modals.base
            wire:model="zoomImageUrl" :title="null"
            contentClass="!p-0 !w-screen !max-w-none !bg-transparent !border-none !shadow-none md:!w-auto md:!max-w-7xl"
        >
            <div class="relative flex flex-col items-center gap-6 w-full px-4 md:px-0">
                <img :src="$wire.zoomImageUrl"
                     class="w-full h-auto max-h-[85vh] object-contain rounded-lg md:rounded-2xl shadow-xl">
                <div @click="$wire.zoomImageUrl = null"
                     class="px-6 md:px-8 py-3 bg-white/5 border border-white/10 rounded-xl text-white/80 text-sm font-bold flex items-center gap-2 animate-slide-up text-center max-w-full">
                    <span class="material-symbols-rounded text-lg">info</span>
                    برای بازگشت به صفحه کلیک کنید
                </div>
            </div>
        </x-ui.modals.base>

        @if($this->totalResources > count($this->resources))
            <div wire:key="reservation-avantgarde-load-more" class="mt-8 flex justify-center relative z-1">
                <x-ui.buttons.load-more
                    action="loadMoreResources"
                    text="نمایش موارد بیشتر"
                    loadingText="در حال بارگذاری..."
                    icon="expand_more"
                    class="px-6 py-2.5 rounded-xl bg-[var(--md-sys-color-surface)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] text-sm font-semibold shadow-sm"
                />
            </div>
        @endif
    @endif
