<div class="relative">
    <div class="relative overflow-hidden rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm">

        <div class="w-full overflow-x-auto">
            <table class="dms-doc-table min-w-full w-full border-separate border-spacing-0 text-sm">
                <thead class="bg-[var(--md-sys-color-surface-container-high)] text-xs uppercase tracking-wider text-[var(--md-sys-color-on-surface-variant)]">
                <tr x-show="openSettings" x-collapse>
                    <th colspan="7" class="px-5 pb-3 pt-2">
                        <div class="flex justify-end border-b border-[var(--md-sys-color-outline-variant)]/30 bg-transparent" @click.stop>
                            @php
                                $columns = $this->presenter->columns();
                                $sortIsDefault = $this->presenter->sortIsDefault($sort, $sortDir);
                            @endphp
                            <div class="inline-flex items-center gap-1">
                                <button type="button"
                                        @click="$store.density.toggle()"
                                        :title="$store.density.compact ? 'نمایش عادی' : 'نمایش فشرده'"
                                        :aria-label="$store.density.compact ? 'نمایش عادی' : 'نمایش فشرده'"
                                        :class="{ 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]': $store.density.compact, 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]': !$store.density.compact }"
                                        class="inline-flex items-center justify-center p-1 rounded-lg transition-colors normal-case">
                                    <span class="material-symbols-rounded text-[18px]" x-text="$store.density.compact ? 'view_comfy' : 'view_compact'"></span>
                                </button>

                                <button type="button"
                                        wire:click="resetSort"
                                        {{ $sortIsDefault ? 'disabled' : '' }}
                                        title="بازنشانی ترتیب"
                                        class="inline-flex items-center justify-center p-1 rounded-lg transition-colors normal-case {{ $sortIsDefault ? 'text-[var(--md-sys-color-outline)] cursor-default' : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]' }}">
                                    <span class="material-symbols-rounded text-[18px]">restart_alt</span>
                                </button>

                                <div x-data="{ open: false }" @click.away="open = false" class="relative">
                                    <button type="button"
                                            @click="open = !open"
                                            title="نمایش و مخفی کردن ستون‌ها"
                                            :class="{ 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]': open, 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)]': !open }"
                                            class="inline-flex items-center justify-center p-1 rounded-lg transition-colors normal-case">
                                        <span class="material-symbols-rounded text-[18px]">view_column</span>
                                        <span x-show="$store.colVisibility.hidden.length > 0" class="absolute -left-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-[var(--md-sys-color-error)] px-1 text-[9px] font-bold text-white" x-text="$store.colVisibility.hidden.length" style="display: none;"></span>
                                    </button>

                                    <div x-show="open"
                                         x-transition.origin
                                         style="display: none;"
                                         class="absolute left-0 top-11 z-30 w-60 rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] p-2 shadow-2xl">
                                        <div class="mb-1.5 flex items-center justify-between px-1.5">
                                            <span class="text-[11px] font-bold text-[var(--md-sys-color-on-surface)]">ستون‌ها</span>
                                            <button type="button"
                                                    @click="$store.colVisibility.reset()"
                                                    :disabled="$store.colVisibility.hidden.length === 0"
                                                    class="text-[10px] font-semibold text-[var(--md-sys-color-primary)] transition-opacity hover:opacity-70 disabled:opacity-40 disabled:no-underline">
                                                نمایش همه
                                            </button>
                                        </div>
                                        <div class="flex flex-col gap-0.5">
                                            @foreach($columns as $colKey => $colLabel)
                                                <button type="button" @click="$store.colVisibility.toggle('{{ $colKey }}')" class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-right transition-colors hover:bg-[var(--md-sys-color-surface-container-highest)]">
                                                    <span class="material-symbols-rounded text-[18px]" :class="$store.colVisibility.isHidden('{{ $colKey }}') ? 'text-[var(--md-sys-color-outline)]' : 'text-[var(--md-sys-color-primary)]'" x-text="$store.colVisibility.isHidden('{{ $colKey }}') ? 'check_box_outline_blank' : 'check_box'"></span>
                                                    <span class="flex-1 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)]">{{ $colLabel }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </th>
                </tr>
                <tr>
                    <th data-col="title" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-right font-bold first:rounded-tr-2xl">
                        <button type="button" wire:click="sortBy('title')" class="flex w-full items-center justify-start gap-1.5 transition-colors hover:text-[var(--md-sys-color-primary)] {{ $sort === 'title' ? 'text-[var(--md-sys-color-primary)]' : '' }}">
                            <span class="material-symbols-rounded text-[18px]">description</span>
                            <span>عنوان سند</span>
                            <span class="material-symbols-rounded text-[14px] {{ $sort === 'title' ? '' : 'opacity-40' }}">
                                    {{ $sort === 'title' ? ($sortDir === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}
                                </span>
                        </button>
                    </th>

                    <th data-col="code" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-center font-bold">
                        <button type="button" wire:click="sortBy('code')" class="flex w-full items-center justify-center gap-1.5 transition-colors hover:text-[var(--md-sys-color-primary)] {{ $sort === 'code' ? 'text-[var(--md-sys-color-primary)]' : '' }}">
                            <span class="material-symbols-rounded text-[18px]">tag</span>
                            <span>نسخه</span>
                            <span class="material-symbols-rounded text-[14px] {{ $sort === 'code' ? '' : 'opacity-40' }}">
                                    {{ $sort === 'code' ? ($sortDir === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' }}
                                </span>
                        </button>
                    </th>

                    <th data-col="dept" class="max-w-xs whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-right font-bold">
                        <div class="flex items-center justify-start gap-1.5">
                            <span class="material-symbols-rounded text-[18px]">group</span>
                            <span>واحد(های) ذی نفع</span>
                        </div>
                    </th>

                    <th data-col="status" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-center font-bold">
                        <div class="flex items-center justify-center gap-1.5">
                            <span class="material-symbols-rounded text-[18px]">info</span>
                            <span>وضعیت</span>
                        </div>
                    </th>

                    <th data-col="details" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-right font-bold">
                        <div class="flex items-center justify-start gap-1.5">
                            <span class="material-symbols-rounded text-[18px]">list</span>
                            <span>جزییات</span>
                        </div>
                    </th>

                    <th data-col="desc" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-right font-bold">
                        <div class="flex items-center justify-start gap-1.5">
                            <span class="material-symbols-rounded text-[18px]">comment</span>
                            <span>توضیحات</span>
                        </div>
                    </th>

                    <th data-col="action" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-center font-bold last:rounded-tl-2xl">
                        @include('livewire.dashboard.dms.toolbar')
                    </th>
                </tr>
                </thead>

                <tbody>
                @forelse($this->docs as $doc)
                    @php
                        $r = $this->presenter->rowState($doc, $this->confirmedDocs, $this->readDocs, $this->readCounts);
                        $isConfirmed = $r['isConfirmed'];
                        $isRead = $r['isRead'];
                        $cat = $r['cat'];
                        $extraDetails = $r['extraDetails'];
                        $cleanTitle = $r['cleanTitle'];
                        $statusColor = $r['statusColor'];
                        $deptLabels = $r['deptLabels'];
                        $readCount = $r['readCount'];
                    @endphp

                    <tr wire:key="dms-doc-{{ $doc->id }}"
                        data-rf="dms-{{ $doc->id }}"
                        data-doc-id="{{ $doc->id }}"
                        data-doc-title="{{ $cleanTitle }}"
                        class="group transition-colors duration-200 hover:bg-[var(--md-sys-color-surface-container-low)]">

                        <td data-col="title" class="relative min-w-[240px] border-b border-[var(--md-sys-color-outline-variant)] pb-2 pl-14 pr-1.5 pt-2 text-right align-top"
                            style="box-shadow: inset -3px 0 0 0 {{ $statusColor }};">

                            <div class="absolute right-0 top-0 z-20 cursor-help pr-1 transition-transform duration-200 group-hover:scale-105">
                                @if (!$isConfirmed)
                                    <div class="flex h-10 w-10 animate-pulse items-center justify-center rounded-bl-xl border-b border-l border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]" title="نیازمند تایید دریافت">
                                        <span class="material-symbols-rounded text-[20px]">edit_document</span>
                                    </div>
                                @elseif ($isConfirmed && !$isRead)
                                    <div class="flex h-10 w-10 animate-pulse items-center justify-center rounded-bl-xl border-b border-l border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]" title="نیازمند تایید مطالعه">
                                        <span class="material-symbols-rounded text-[20px]">menu_book</span>
                                    </div>
                                @else
                                    <div class="flex h-10 w-10 items-center justify-center rounded-bl-xl border-b border-l border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]" title="مطالعه شده">
                                        <span class="material-symbols-rounded text-[20px]">check_circle</span>
                                    </div>
                                @endif
                            </div>

                            <div class="relative z-10 flex flex-col items-start gap-1.5 pr-12">
                                    <span title="{{ 'ایجاده شده در: ' . toJalali($doc->created_at, 'j F Y') . ' 📆 بروزرسانی شده در: ' . toJalali($doc->updated_at, 'j F Y') }}"
                                          class="block cursor-help text-base font-bold leading-relaxed text-[var(--md-sys-color-on-surface)] transition-colors hover:text-[var(--md-sys-color-primary)]">
                                        {{ $cleanTitle }}
                                    </span>

                                <div class="flex flex-wrap gap-1.5">
                                    @if($cat)
                                        <span title="مشاهده تمامی تگ های مشابه"
                                              wire:click="$set('search', '{{ $cat }}')"
                                              class="inline-flex w-max cursor-pointer items-center gap-1 rounded-lg bg-[var(--md-sys-color-secondary-container)] px-2 py-1 text-[11px] font-medium text-[var(--md-sys-color-on-secondary-container)] transition-opacity hover:opacity-80">
                                                <span class="material-symbols-rounded text-[12px]">sell</span>
                                                {{ $cat }}
                                            </span>
                                    @endif

                                    @if(is_array($doc->tags))
                                        @foreach($doc->tags as $key => $tagVal)
                                            @foreach((array) $tagVal as $tag)
                                                <span title="مشاهده تمامی تگ های مشابه"
                                                      wire:click="$set('activeFilter', '{{ strtolower($key) }}|{{ $tag }}')"
                                                      class="inline-flex w-max cursor-pointer items-center gap-1 rounded-lg border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-variant)] px-2 py-1 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] transition-opacity hover:opacity-80">
                                                        <span class="material-symbols-rounded !text-[12px]">tag</span>
                                                        {{ $tag }}
                                                    </span>
                                            @endforeach
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td data-col="code" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-4 text-center align-middle font-mono text-[var(--md-sys-color-on-surface-variant)]" dir="ltr">
                            @php
                                $versionPopover = $this->presenter->versionPopover($doc);
                            @endphp
                            @if($versionPopover)
                                <div x-data="{ open: false }" @click.away="open = false" class="relative flex flex-col items-center">
                                    <button type="button" @click="open = !open"
                                            class="inline-flex items-center gap-1 rounded-md border border-[var(--md-sys-color-outline-variant)]/30 bg-[var(--md-sys-color-surface-container)] px-2 py-1 whitespace-nowrap transition-colors hover:bg-[var(--md-sys-color-surface-container-highest)]"
                                            title="تاریخ ایجاد و بروزرسانی">
                                        <span>{{ $doc->code ?? '' }} - {{ $doc->version ?? 'N/A' }}</span>
                                        <span class="material-symbols-rounded text-[12px] opacity-60 transition-transform duration-200" :class="{ 'rotate-180': open }">expand_more</span>
                                    </button>
                                    <div x-show="open" x-transition.origin
                                         class="mt-1 w-max rounded-xl border border-[var(--md-sys-color-outline-variant)]/20 bg-[var(--md-sys-color-surface)] p-2.5 shadow-xl"
                                         style="display: none;">
                                        <div class="flex flex-col gap-1.5 text-right text-[11px] font-sans text-[var(--md-sys-color-on-surface-variant)]" dir="rtl">
                                            @if($doc->created_at)
                                                <div class="flex items-center gap-1.5">
                                                    <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-primary)]">add_circle</span>
                                                    <span>ایجاد: {{ toJalali($doc->created_at, 'j F Y') }}</span>
                                                </div>
                                            @endif
                                            @if($doc->updated_at)
                                                <div class="flex items-center gap-1.5">
                                                    <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-tertiary)]">update</span>
                                                    <span>بروزرسانی: {{ toJalali($doc->updated_at, 'j F Y') }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @else
                                <span class="rounded-md border border-[var(--md-sys-color-outline-variant)]/30 bg-[var(--md-sys-color-surface-container)] px-2 py-1">
                                    {{ $doc->code ?? '' }} - {{ $doc->version ?? 'N/A' }}
                                </span>
                            @endif
                        </td>

                        <td data-col="dept" class="max-w-[150px] border-b border-[var(--md-sys-color-outline-variant)] px-6 py-4 text-right align-middle">
                            <div class="truncate text-xs font-medium leading-relaxed text-[var(--md-sys-color-on-surface-variant)]"
                                 title="{!! $deptLabels ?: 'بدون مالک' !!}">
                                {!! $deptLabels ?: ' فردی (جدا از واحد)' !!}
                            </div>
                        </td>

                        <td data-col="status" class="cursor-help border-b border-[var(--md-sys-color-outline-variant)] px-6 py-4 text-center align-middle">
                                <span title="{{ $doc->getStatusInFarsi() }}"
                                      class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[var(--md-sys-color-surface-container)] text-lg transition-colors hover:bg-[var(--md-sys-color-surface-container-high)]">
                                    {!! $doc->getStatusIcon() ?? '-' !!}
                                </span>
                        </td>

                        <td data-col="details" class="min-w-[150px] border-b border-[var(--md-sys-color-outline-variant)] px-6 py-4 text-right align-middle">
                            @if($extraDetails->count() > 0)
                                <div x-data="{ open: false }" @click.away="open = false" class="relative flex flex-col items-start">
                                    <button type="button" @click="open = !open"
                                            title="نمایش جزییات"
                                            class="inline-flex w-max items-center gap-1.5 rounded-md border border-[var(--md-sys-color-outline-variant)]/30 bg-[var(--md-sys-color-surface-container)] px-2 py-1 text-[11px] font-semibold text-[var(--md-sys-color-on-surface-variant)] transition-colors hover:bg-[var(--md-sys-color-surface-container-highest)]">
                                        <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-primary)]">list</span>
                                        <span>جزییات</span>
                                        <span class="rounded-full bg-[var(--md-sys-color-primary-container)] px-1.5 text-[10px] font-bold text-[var(--md-sys-color-on-primary-container)]">{{ $extraDetails->count() }}</span>
                                        <span class="material-symbols-rounded text-[12px] opacity-60 transition-transform duration-200" :class="{ 'rotate-180': open }">expand_more</span>
                                    </button>
                                    <div x-show="open" x-transition.origin
                                         class="mt-1 w-max rounded-xl border border-[var(--md-sys-color-outline-variant)]/20 bg-[var(--md-sys-color-surface)] p-2 shadow-xl"
                                         style="display: none;">
                                        <div class="flex flex-col gap-1.5 text-xs">
                                            @foreach($extraDetails as $key => $value)
                                                <div class="flex items-center gap-2">
                                                    <span class="whitespace-nowrap rounded bg-[var(--md-sys-color-primary-container)] px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-[var(--md-sys-color-on-primary-container)]">{{ $key }}</span>
                                                    <span class="truncate font-medium text-[var(--md-sys-color-on-surface-variant)]" title="{{ $value }}">{{ $value }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="flex flex-col gap-1.5 text-xs">
                                    @forelse($extraDetails as $key => $value)
                                        <div class="flex items-center gap-2">
                                            <span class="whitespace-nowrap rounded bg-[var(--md-sys-color-primary-container)] px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-[var(--md-sys-color-on-primary-container)]">{{ $key }}</span>
                                            <span class="truncate font-medium text-[var(--md-sys-color-on-surface-variant)]" title="{{ $value }}">{{ $value }}</span>
                                        </div>
                                    @empty
                                        <span class="text-[11px] italic text-[var(--md-sys-color-outline)]">بدون جزییات</span>
                                    @endforelse
                                </div>
                            @endif
                        </td>

                        <td data-col="desc" class="min-w-[180px] border-b border-[var(--md-sys-color-outline-variant)] px-6 py-4 text-right align-middle text-xs text-[var(--md-sys-color-on-surface-variant)]">
                            <div class="flex flex-col gap-2">
                                @if ($doc->file)
                                    @php
                                        $renditions = $doc->renditions();
                                        $primaryIcon = $this->presenter->extensionIcon(pathinfo($doc->file, PATHINFO_EXTENSION));
                                    @endphp

                                    @if ($isConfirmed)
                                        @if (count($renditions) > 1)
                                            <div x-data="{ open: false }" @click.away="open = false" class="relative flex flex-col items-start">
                                                <button type="button"
                                                        @click="open = !open"
                                                        title="نمایش نسخه‌ها"
                                                        class="inline-flex w-max items-center gap-1.5 rounded-md border border-[var(--md-sys-color-outline-variant)]/30 bg-[var(--md-sys-color-surface-container)] px-2 py-1 text-[11px] font-semibold text-[var(--md-sys-color-on-surface-variant)] transition-colors hover:bg-[var(--md-sys-color-surface-container-highest)]">
                                                    <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-primary)]">file_present</span>
                                                    <span>نسخه‌های سند</span>
                                                    <span class="rounded-full bg-[var(--md-sys-color-primary-container)] px-1.5 text-[10px] font-bold text-[var(--md-sys-color-on-primary-container)]">{{ count($renditions) }}</span>
                                                    <span class="material-symbols-rounded text-[12px] opacity-60 transition-transform duration-200" :class="{ 'rotate-180': open }">expand_more</span>
                                                </button>

                                                <div x-show="open"
                                                     x-transition.origin
                                                     style="display: none;"
                                                     class="mt-1 flex flex-col gap-1 rounded-xl border border-[var(--md-sys-color-outline-variant)]/20 bg-[var(--md-sys-color-surface)] p-1 shadow-xl">
                                                    @foreach ($renditions as $rendition)
                                                        @php
                                                            $ri = $this->presenter->renditionData($rendition, $doc->file);
                                                        @endphp
                                                        <a href="{{ $ri['route'] }}"
                                                           target="_blank"
                                                           wire:click="incrementRead({{ $doc->id }})"
                                                           x-on:click="recordClick({ id: $el.closest('tr').dataset.docId, title: $el.closest('tr').dataset.docTitle, url: $el.href })"
                                                           class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-right transition-colors hover:bg-[var(--md-sys-color-primary)]/10">
                                                            <span class="material-symbols-rounded text-[16px] {{ $ri['text'] }}">{{ $ri['icon'] }}</span>
                                                            <span class="flex-1 text-[11px] font-semibold text-[var(--md-sys-color-on-surface)]">{{ $ri['label'] }}</span>
                                                            <span class="text-[9px] font-mono uppercase text-[var(--md-sys-color-on-surface-variant)]/70" dir="ltr">{{ $rendition['ext'] ?: '—' }}</span>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <a href="{{ route('secure-file', $doc->file) }}"
                                               target="_blank"
                                               wire:click="incrementRead({{ $doc->id }})"
                                               x-on:click="recordClick({ id: $el.closest('tr').dataset.docId, title: $el.closest('tr').dataset.docTitle, url: $el.href })"
                                               class="inline-flex w-max items-center gap-1.5 rounded-lg px-2.5 py-1 shadow-sm transition-all duration-200 hover:opacity-80 {{ $primaryIcon['bg'] }} {{ $primaryIcon['text'] }}">
                                                <span class="material-symbols-rounded text-[15px]">{{ $primaryIcon['icon'] }}</span>
                                                <span class="text-[11px] font-semibold">{{ $primaryIcon['label'] }}</span>
                                            </a>
                                        @endif
                                    @else
                                        <div class="inline-flex w-max items-center gap-1.5 rounded-lg px-2.5 py-1 shadow-sm {{ $primaryIcon['bg'] }} {{ $primaryIcon['text'] }}">
                                            <span class="material-symbols-rounded text-[15px]">{{ $primaryIcon['icon'] }}</span>
                                            <span class="text-[11px] font-semibold">{{ $primaryIcon['label'] }}</span>
                                        </div>
                                    @endif
                                @endif

                                @if(!empty($doc->revision))
                                    <div x-data="{ open: false }" @click.away="open = false" class="relative flex flex-col items-start">
                                        <button type="button" @click="open = !open"
                                                title="نمایش توضیحات"
                                                class="inline-flex w-max items-center gap-1.5 rounded-md border border-[var(--md-sys-color-outline-variant)]/30 bg-[var(--md-sys-color-surface-container)] px-2 py-1 text-[11px] font-semibold text-[var(--md-sys-color-on-surface-variant)] transition-colors hover:bg-[var(--md-sys-color-surface-container-highest)]">
                                            <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-tertiary)]">comment</span>
                                            <span>توضیحات</span>
                                            <span class="material-symbols-rounded text-[12px] opacity-60 transition-transform duration-200" :class="{ 'rotate-180': open }">expand_more</span>
                                        </button>
                                        <div x-show="open" x-transition.origin
                                             class="mt-1 w-max max-w-xs rounded-xl border border-[var(--md-sys-color-outline-variant)]/20 bg-[var(--md-sys-color-surface)] p-2.5 shadow-xl"
                                             style="display: none;">
                                            <div class="text-right text-[11px] leading-relaxed whitespace-pre-wrap text-[var(--md-sys-color-on-surface-variant)]" dir="rtl">
                                                {{ $doc->revision }}
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-[11px] italic text-[var(--md-sys-color-outline)]">بدون توضیح</span>
                                @endif
                            </div>
                        </td>

                        <td data-col="action" class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-4 text-center align-middle">
                            @if ($doc->file)
                                @if(!$isConfirmed)
                                    <button type="button"
                                            wire:click="confirmRead({{ $doc->id }})"
                                            class="inline-flex w-[122px] flex-col items-center justify-center gap-1.5 rounded-2xl border border-[var(--md-sys-color-error)]/15 bg-[var(--md-sys-color-error)]/5 px-3 py-2.5 text-[var(--md-sys-color-error)] shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:bg-[var(--md-sys-color-error)] hover:text-white hover:shadow-md">
                                        <span class="material-symbols-rounded text-[22px]">edit_document</span>
                                        <span class="text-[11px] font-bold">تایید دریافت</span>
                                    </button>
                                @elseif ($isConfirmed && !$isRead)
                                    <a href="{{ route('secure-file', $doc->file) }}"
                                       target="_blank"
                                       wire:click="incrementRead({{ $doc->id }})"
                                       x-on:click="recordClick({ id: $el.closest('tr').dataset.docId, title: $el.closest('tr').dataset.docTitle, url: $el.href })"
                                       class="inline-flex w-[122px] flex-col items-center justify-center gap-1.5 rounded-2xl border border-[var(--md-sys-color-tertiary)]/15 bg-[var(--md-sys-color-tertiary)]/5 px-3 py-2.5 text-[var(--md-sys-color-tertiary)] shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:bg-[var(--md-sys-color-tertiary)] hover:text-white hover:shadow-md">
                                        <span class="material-symbols-rounded text-[22px]">menu_book</span>
                                        <span class="text-[11px] font-bold">مشاهده و تایید</span>
                                    </a>
                                @else
                                    <a href="{{ route('secure-file', $doc->file) }}"
                                       target="_blank"
                                       wire:click="incrementRead({{ $doc->id }})"
                                       x-on:click="recordClick({ id: $el.closest('tr').dataset.docId, title: $el.closest('tr').dataset.docTitle, url: $el.href })"
                                       class="inline-flex w-[122px] flex-col items-center justify-center gap-1.5 rounded-2xl border border-transparent px-3 py-2.5 text-[var(--md-sys-color-on-surface-variant)] transition-all duration-300 hover:border-[var(--md-sys-color-primary)]/30 hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)]">
                                        <span class="material-symbols-rounded text-[22px]">visibility</span>
                                        <span class="text-[11px] font-medium">مشاهده مجدد</span>
                                    </a>
                                @endif
                            @else
                                <span class="inline-flex items-center gap-1 rounded-lg bg-[var(--md-sys-color-surface-variant)] px-3 py-2 text-xs text-[var(--md-sys-color-outline)]">
                                        <span class="material-symbols-rounded text-[14px]">link_off</span>
                                        فایل ندارد
                                    </span>
                            @endif

                            @if($readCount > 0)
                                <p class="mt-1.5 text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-70">بازدید شما: {{ convertToPersian($readCount) }} بار</p>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8">
                            @if($pendingFilter)
                                <x-ui.empty icon="task_alt"
                                            title="هیچ سند معوقی باقی نمانده"
                                            description="فیلتر سندهای نیازمند اقدام فعال است"
                                            variant="filtered" />

                                <div class="mt-3 flex justify-center">
                                    <button type="button"
                                            wire:click="clearPendingFilter"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] px-4 py-2 text-sm font-medium text-[var(--md-sys-color-primary)] shadow-sm transition hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)]">
                                        <span class="material-symbols-rounded text-[16px]">filter_alt_off</span>
                                        نمایش همه اسناد
                                    </button>
                                </div>
                            @else
                                <x-ui.empty icon="folder_off"
                                            title="هیچ سندی یافت نشد"
                                            description="لطفاً فیلترها را بررسی کنید"
                                            variant="filtered" />
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($hasMorePages)
    <div class="flex justify-center pb-2 pt-6">
        <x-ui.buttons.load-more
            action="loadMore"
            text="بارگذاری بیشتر"
            loading-text="در حال دریافت..."
            icon="expand_more"
            class="rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] px-5 py-2.5 font-medium text-[var(--md-sys-color-primary)] shadow-sm transition hover:border-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:shadow-md" />
    </div>
@endif
