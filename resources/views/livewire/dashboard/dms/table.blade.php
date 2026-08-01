<div class="relative">
    <div
        class="relative overflow-hidden rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm">
        <button @click="toggleMaximize()"
                :title="max ? 'کوچک کردن' : 'بزرگ کردن'"
                :class="{ '!bg-[var(--md-sys-color-primary-container)] !text-[var(--md-sys-color-on-primary-container)]': max }"
                class="ripple-effect absolute right-2 top-2 z-20 min-w-[36px] min-h-[36px] p-1.5 rounded-xl text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-highest)] transition-all duration-200 active:scale-95 flex items-center justify-center">
            <span class="material-symbols-rounded text-[20px]"
                  x-text="max ? 'close_fullscreen' : 'open_in_full'"></span>
        </button>
        <div class="w-full overflow-x-auto">
            <table class="w-full min-w-full border-separate border-spacing-0 text-sm">
                <thead
                    class="bg-[var(--md-sys-color-surface-container-high)] text-xs uppercase tracking-wider text-[var(--md-sys-color-on-surface-variant)]">
                <tr>
                    <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] py-3.5 pl-6 pr-14 text-right font-bold first:rounded-tr-2xl">
                        <div class="flex items-center justify-center gap-1.5">
                            <span class="material-symbols-rounded text-[18px]">description</span>
                            <span>عنوان سند</span>
                        </div>
                    </th>
                    <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-center font-bold">
                        <div class="flex items-center justify-center gap-1.5">
                            <span class="material-symbols-rounded text-[18px]">tag</span>
                            <span>نسخه</span>
                        </div>
                    </th>
                    <th class="max-w-xs whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-right font-bold">
                        <div class="flex items-center justify-start gap-1.5">
                            <span class="material-symbols-rounded text-[18px]">group</span>
                            <span>واحد(های) ذی نفع</span>
                        </div>
                    </th>
                    <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-center font-bold">
                        <div class="flex items-center justify-center gap-1.5">
                            <span class="material-symbols-rounded text-[18px]">info</span>
                            <span>وضعیت</span>
                        </div>
                    </th>
                    <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-right font-bold">
                        <div class="flex items-center justify-start gap-1.5">
                            <span class="material-symbols-rounded text-[18px]">list</span>
                            <span>جزییات</span>
                        </div>
                    </th>
                    <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-right font-bold">
                        <div class="flex items-center justify-start gap-1.5">
                            <span class="material-symbols-rounded text-[18px]">comment</span>
                            <span>توضیحات</span>
                        </div>
                    </th>
                    <th class="whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] px-6 py-3.5 text-center font-bold last:rounded-tl-2xl">
                        <div class="flex items-center justify-center gap-1.5">
                            <span class="material-symbols-rounded text-[18px]">visibility</span>
                            <span>مشاهده و تایید</span>
                        </div>
                    </th>
                </tr>
                </thead>

                <tbody>
                @forelse($this->docs as $doc)
                    @php
                        $isConfirmed = in_array($doc->id, $this->confirmedDocs);
                        $isRead = in_array($doc->id, $this->readDocs);
                        $cat = optional($doc->extra)['category'] ?? optional($doc->extra)['Category'];
                        $extraDetails = collect($doc->extra ?? [])->except(['category', 'Category', 'type', 'Type', 'users']);

                        $statusColor = !$isConfirmed
                            ? 'var(--md-sys-color-error)'
                            : (($isConfirmed && !$isRead)
                                ? 'var(--md-sys-color-tertiary)'
                                : 'var(--md-sys-color-primary)');
                    @endphp

                    <tr wire:key="dms-doc-{{ $doc->id }}" class="group transition-colors duration-200 hover:bg-[var(--md-sys-color-surface-container-low)]">
                        <td class="relative min-w-[240px] border-b border-[var(--md-sys-color-outline-variant)] pl-14 pr-1.5 pt-0 text-right align-middle"
                            style="box-shadow: inset -3px 0 0 0 {{ $statusColor }};">
                            <div class="absolute right-0 top-0 z-20 cursor-help pr-1 transition-transform duration-200 group-hover:scale-105">
                                @if (!$isConfirmed)
                                    <div class="flex h-10 w-10 animate-pulse items-center justify-center rounded-bl-xl border-b border-l border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)]"
                                         title="نیازمند تایید دریافت">
                                        <span class="material-symbols-rounded text-[20px]">edit_document</span>
                                    </div>
                                @elseif ($isConfirmed && !$isRead)
                                    <div class="flex h-10 w-10 animate-pulse items-center justify-center rounded-bl-xl border-b border-l border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)]"
                                         title="نیازمند تایید مطالعه">
                                        <span class="material-symbols-rounded text-[20px]">menu_book</span>
                                    </div>
                                @else
                                    <div class="flex h-10 w-10 items-center justify-center rounded-bl-xl border-b border-l border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]"
                                         title="مطالعه شده">
                                        <span class="material-symbols-rounded text-[20px]">check_circle</span>
                                    </div>
                                @endif
                            </div>


                            <div class="flex items-center gap-3.5 align-top pt-2 pr-12 pb-2">
                                <div class="relative z-10 flex flex-col items-start gap-1.5">
                                <span
                                    title="{{ 'ایجاده شده در: ' . toJalali($doc->created_at, 'j F Y') . ' 📆 بروزرسانی شده در: ' . toJalali($doc->updated_at, 'j F Y') }}"
                                    class="block cursor-help text-base font-bold leading-relaxed text-[var(--md-sys-color-on-surface)] transition-colors hover:text-[var(--md-sys-color-primary)]"
                                >
                                    {{ superClean($doc->title ?? 'بدون عنوان') }}
                                </span>

                                    <div class="flex flex-wrap gap-1.5">
                                        @if($cat)
                                            <span
                                                title="مشاهده تمامی تگ های مشابه"
                                                class="inline-flex w-max cursor-pointer items-center gap-1 rounded-full bg-[var(--md-sys-color-secondary-container)] px-2 py-1 text-[11px] font-medium text-[var(--md-sys-color-on-secondary-container)] transition-opacity hover:opacity-80"
                                                wire:click="$set('search', '{{ $cat }}')"
                                            >
                                            <span class="material-symbols-rounded text-[12px]">sell</span>
                                            {{ $cat }}
                                            </span>
                                        @endif

                                        @if(is_array($doc->tags))
                                            @foreach($doc->tags as $key => $tagVal)
                                                @foreach((array) $tagVal as $tag)
                                                    <span
                                                        title="مشاهده تمامی تگ های مشابه"
                                                        class="inline-flex w-max cursor-pointer items-center gap-1 rounded-full border border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-variant)] px-2 py-1 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] transition-opacity hover:opacity-80"
                                                        wire:click="$set('activeFilter', '{{ strtolower($key) }}|{{ $tag }}')"
                                                    >
                                <span class="material-symbols-rounded !text-[12px]">tag</span>
                                {{ $tag }}
                            </span>
                                                @endforeach
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="align-middle border-b border-[var(--md-sys-color-outline-variant)] px-6 py-4 text-center font-mono text-[var(--md-sys-color-on-surface-variant)]"
                            dir="ltr">
                        <span
                            class="rounded-md border border-[var(--md-sys-color-outline-variant)]/30 bg-[var(--md-sys-color-surface-container)] px-2 py-1">
                            {{ $doc->code ?? '' }} - {{ $doc->version ?? 'N/A' }}
                        </span>
                        </td>

                        <td class="max-w-[150px] align-middle border-b border-[var(--md-sys-color-outline-variant)] px-6 py-4 text-right">
                            <div
                                class="truncate text-xs font-medium leading-relaxed text-[var(--md-sys-color-on-surface-variant)]"
                                title="{!! $doc->getDepartmentTooltipLabels() ?: 'بدون مالک' !!}">
                                {!! $doc->getDepartmentDisplayLabels() ?: ' فردی (جدا از واحد)' !!}
                            </div>
                        </td>

                        <td class="cursor-help align-middle border-b border-[var(--md-sys-color-outline-variant)] px-6 py-4 text-center">
                            <span
                                title="{{ $doc->getStatusInFarsi() }}"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[var(--md-sys-color-surface-container)] text-lg transition-colors hover:bg-[var(--md-sys-color-surface-container-high)]"
                            >
                                {!! $doc->getStatusIcon() ?? '-' !!}
                            </span>
                        </td>

                        <td class="min-w-[150px] align-middle border-b border-[var(--md-sys-color-outline-variant)] px-6 py-4 text-right">
                            <div class="flex flex-col gap-1.5 text-xs">
                                @forelse($extraDetails as $key => $value)
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="whitespace-nowrap rounded bg-[var(--md-sys-color-primary-container)] px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-[var(--md-sys-color-on-primary-container)]">{{ $key }}</span>
                                        <span class="truncate font-medium text-[var(--md-sys-color-on-surface-variant)]"
                                              title="{{ $value }}">{{ $value }}</span>
                                    </div>
                                @empty
                                    <span
                                        class="text-[11px] italic text-[var(--md-sys-color-outline)]">بدون جزییات</span>
                                @endforelse
                            </div>
                        </td>

                        <td class="min-w-[180px] align-middle border-b border-[var(--md-sys-color-outline-variant)] px-6 py-4 text-right text-xs text-[var(--md-sys-color-on-surface-variant)]">
                            <div class="flex flex-col gap-2">
                                @if ($doc->file)
                                    @php
                                        $renditions = $doc->renditions();
                                        $extIcon = fn(?string $e): array => match(strtolower($e ?? '')) {
                                            'pdf' => ['picture_as_pdf', 'سند PDF', 'bg-[var(--md-sys-color-error-container)]', 'text-[var(--md-sys-color-on-error-container)]'],
                                            'xlsx', 'xls', 'csv' => ['table_chart', 'فایل اکسل', 'bg-[var(--md-sys-color-tertiary-container)]', 'text-[var(--md-sys-color-on-tertiary-container)]'],
                                            'docx', 'doc' => ['description', 'سند Word', 'bg-[var(--md-sys-color-primary-container)]', 'text-[var(--md-sys-color-on-primary-container)]'],
                                            default => ['insert_drive_file', 'فایل ضمیمه', 'bg-[var(--md-sys-color-surface-variant)]', 'text-[var(--md-sys-color-on-surface-variant)]'],
                                        };
                                        $primaryIcon = $extIcon(pathinfo($doc->file, PATHINFO_EXTENSION));
                                    @endphp

                                    @if ($isConfirmed)
                                        @if (count($renditions) > 1)
                                            <div x-data="{ open: false }" @click.away="open = false" class="relative">
                                                <button type="button" @click="open = !open"
                                                        class="inline-flex w-max items-center gap-1.5 text-[11px] font-semibold text-[var(--md-sys-color-primary)] transition-opacity hover:opacity-70">
                                                    <span class="material-symbols-rounded text-[15px]">file_present</span>
                                                    <span>نسخه‌های سند</span>
                                                    <span class="rounded-full bg-[var(--md-sys-color-primary-container)] px-1.5 text-[10px] font-bold text-[var(--md-sys-color-on-primary-container)]">{{ count($renditions) }}</span>
                                                    <span class="material-symbols-rounded text-[13px] opacity-70 transition-transform duration-200" :class="{ 'rotate-180': open }">expand_more</span>
                                                </button>

                                                <div x-show="open" x-transition.origin
                                                     class="mt-1 flex flex-col gap-1 rounded-xl border border-[var(--md-sys-color-outline-variant)]/20 bg-[var(--md-sys-color-surface)] p-1 shadow-xl"
                                                     style="display: none;">
                                                    @foreach ($renditions as $rendition)
                                                        @php
                                                            $ri = $extIcon($rendition['ext']);
                                                            $rRoute = $rendition['path'] === $doc->file
                                                                ? route('secure-file', $rendition['path'])
                                                                : route('secure-extra-file', $rendition['path']);
                                                        @endphp
                                                        <a href="{{ $rRoute }}" target="_blank" wire:click="incrementRead({{ $doc->id }})"
                                                           class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-right transition-colors hover:bg-[var(--md-sys-color-primary)]/10">
                                                            <span class="material-symbols-rounded text-[16px] {{ $ri[3] }}">{{ $ri[0] }}</span>
                                                            <span class="flex-1 text-[11px] font-semibold text-[var(--md-sys-color-on-surface)]">{{ $ri[1] }}</span>
                                                            <span class="text-[9px] font-mono uppercase text-[var(--md-sys-color-on-surface-variant)]/70" dir="ltr">{{ $rendition['ext'] ?: '—' }}</span>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <a href="{{ route('secure-file', $doc->file) }}" target="_blank" wire:click="incrementRead({{ $doc->id }})"
                                               class="inline-flex w-max items-center gap-1.5 rounded-lg px-2.5 py-1 shadow-sm transition-all duration-200 hover:opacity-80 {{ $primaryIcon[2] }} {{ $primaryIcon[3] }}">
                                                <span class="material-symbols-rounded text-[15px]">{{ $primaryIcon[0] }}</span>
                                                <span class="text-[11px] font-semibold">{{ $primaryIcon[1] }}</span>
                                            </a>
                                        @endif
                                    @else
                                        <div class="inline-flex w-max items-center gap-1.5 rounded-lg px-2.5 py-1 shadow-sm {{ $primaryIcon[2] }} {{ $primaryIcon[3] }}">
                                            <span class="material-symbols-rounded text-[15px]">{{ $primaryIcon[0] }}</span>
                                            <span class="text-[11px] font-semibold">{{ $primaryIcon[1] }}</span>
                                        </div>
                                    @endif
                                @endif

                                <div
                                    class="line-clamp-2 leading-relaxed text-[var(--md-sys-color-on-surface-variant)]/80"
                                    title="{{ $doc->revision ?? '' }}">
                                    {{ $doc->revision ?? 'بدون توضیح' }}
                                </div>
                            </div>
                        </td>

                        <td class="align-middle border-b border-[var(--md-sys-color-outline-variant)] px-6 py-4 text-center whitespace-nowrap">
                            @if ($doc->file)
                                @if(!$isConfirmed)
                                    <button
                                        wire:click="confirmRead({{ $doc->id }})"
                                        class="inline-flex w-[122px] flex-col items-center justify-center gap-1.5 rounded-2xl border border-[var(--md-sys-color-error)]/15 bg-[var(--md-sys-color-error)]/5 px-3 py-2.5 text-[var(--md-sys-color-error)] shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:bg-[var(--md-sys-color-error)] hover:text-white hover:shadow-md"
                                    >
                                        <span class="material-symbols-rounded text-[22px]">edit_document</span>
                                        <span class="text-[11px] font-bold">تایید دریافت</span>
                                    </button>
                                @elseif ($isConfirmed && !$isRead)
                                    <a
                                        href="{{ route('secure-file', $doc->file) }}"
                                        target="_blank"
                                        wire:click="incrementRead({{ $doc->id }})"
                                        class="inline-flex w-[122px] flex-col items-center justify-center gap-1.5 rounded-2xl border border-[var(--md-sys-color-tertiary)]/15 bg-[var(--md-sys-color-tertiary)]/5 px-3 py-2.5 text-[var(--md-sys-color-tertiary)] shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:bg-[var(--md-sys-color-tertiary)] hover:text-white hover:shadow-md"
                                    >
                                        <span class="material-symbols-rounded text-[22px]">menu_book</span>
                                        <span class="text-[11px] font-bold">مشاهده و تایید</span>
                                    </a>
                                @else
                                    <a
                                        href="{{ route('secure-file', $doc->file) }}"
                                        target="_blank"
                                        wire:click="incrementRead({{ $doc->id }})"
                                        class="inline-flex w-[122px] flex-col items-center justify-center gap-1.5 rounded-2xl border border-transparent px-3 py-2.5 text-[var(--md-sys-color-on-surface-variant)] transition-all duration-300 hover:border-[var(--md-sys-color-primary)]/30 hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)]"
                                    >
                                        <span class="material-symbols-rounded text-[22px]">visibility</span>
                                        <span class="text-[11px] font-medium">مشاهده مجدد</span>
                                    </a>
                                @endif
                            @else
                                <span
                                    class="inline-flex items-center gap-1 rounded-lg bg-[var(--md-sys-color-surface-variant)] px-3 py-2 text-xs text-[var(--md-sys-color-outline)]">
                                <span class="material-symbols-rounded text-[14px]">link_off</span>
                                فایل ندارد
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <x-ui.empty icon="folder_off" title="هیچ سندی یافت نشد"
                                        description="لطفاً فیلترها را بررسی کنید" variant="filtered"/>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($hasMorePages)
    <div class="flex justify-center py-6 pb-2">
        <x-ui.buttons.load-more
            action="loadMore"
            text="بارگذاری بیشتر"
            loading-text="در حال دریافت..."
            icon="expand_more"
            class="rounded-xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] px-5 py-2.5 font-medium text-[var(--md-sys-color-primary)] shadow-sm transition hover:border-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:shadow-md"
        />
    </div>
@endif
