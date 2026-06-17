<div
    class="overflow-x-auto rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm">
    <table class="w-full border-collapse text-sm">
        <thead
            class="bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-on-surface-variant)] border-b border-[var(--md-sys-color-outline-variant)]">
        <tr>
            <th class="py-4 px-6 font-semibold whitespace-nowrap">
                <div class="flex items-center justify-start gap-1.5">
                    <span class="material-symbols-rounded text-[18px]">description</span>عنوان سند
                </div>
            </th>
            <th class="py-4 px-6 font-semibold whitespace-nowrap">
                <div class="flex items-center justify-center gap-1.5">
                    <span class="material-symbols-rounded text-[18px]">tag</span>نسخه
                </div>
            </th>
            <th class="py-4 px-6 font-semibold whitespace-nowrap max-w-xs">
                <div class="flex items-center justify-start gap-1.5">
                    <span class="material-symbols-rounded text-[18px]">group</span>واحد(های) ذی نفع
                </div>
            </th>
            <th class="py-4 px-6 font-semibold whitespace-nowrap">
                <div class="flex items-center justify-center gap-1.5">
                    <span class="material-symbols-rounded text-[18px]">info</span>وضعیت
                </div>
            </th>
            <th class="py-4 px-6 font-semibold whitespace-nowrap">
                <div class="flex items-center justify-start gap-1.5">
                    <span class="material-symbols-rounded text-[18px]">list</span>جزییات
                </div>
            </th>
            <th class="py-4 px-6 font-semibold whitespace-nowrap">
                <div class="flex items-center justify-start gap-1.5">
                    <span class="material-symbols-rounded text-[18px]">comment</span>توضیحات
                </div>
            </th>
            <th class="py-4 px-6 font-semibold whitespace-nowrap">
                <div class="flex items-center justify-center gap-1.5">
                    <span class="material-symbols-rounded text-[18px]">visibility</span>مشاهده و تایید
                </div>
            </th>
        </tr>
        </thead>
        <tbody class="divide-y divide-[var(--md-sys-color-outline-variant)]">
        @forelse($this->docs as $doc)
            @php($isConfirmed = in_array($doc->id, $this->confirmedDocs))
            @php($isRead = in_array($doc->id, $this->readDocs))
            <tr class="transition-colors hover:bg-[var(--md-sys-color-surface-container-low)] relative">
                <td class="py-4 px-6 min-w-[200px] align-middle text-right relative">
                    <div class="absolute right-0 top-1/2 -translate-y-1/2 w-1.5 h-10 rounded-l-md overflow-hidden">
                        @if (!$isConfirmed)
                            <div class="w-full h-full bg-[var(--md-sys-color-error)]" title="نیازمند تایید دریافت"></div>
                        @elseif ($isConfirmed && !$isRead)
                            <div class="w-full h-full bg-[var(--md-sys-color-tertiary)]" title="نیازمند تایید مطالعه"></div>
                        @else
                            <div class="w-full h-full bg-[var(--md-sys-color-primary)]" title="مطالعه شده"></div>
                        @endif
                    </div>
                    <div class="flex flex-col items-start gap-1.5 pr-2">
                        <span
                            title="{{ 'ایجاده شده در: ' . jdateOnly($doc->created_at) . ' 📆 بروزرسانی شده در: ' . jdateOnly($doc->updated_at) }}"
                            class="font-bold text-base text-[var(--md-sys-color-on-surface)] leading-relaxed block mt-0.5 cursor-help">
                            {{ $doc->title ?? 'بدون عنوان' }}
                        </span>
                        <div class="flex flex-wrap gap-1.5">
                            @php($cat = optional($doc->extra)['category'] ?? optional($doc->extra)['Category'])
                            @if($cat)
                                <span title="مشاهده تمامی تگ های مشابه"
                                      class="text-xs font-medium px-2 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] inline-flex items-center gap-1 w-max cursor-pointer hover:opacity-80 transition-opacity"
                                      wire:click="$set('search', '{{ $cat }}')">
                                    <span class="material-symbols-rounded text-[12px]">sell</span>
                                    {{ $cat }}
                                </span>
                            @endif
                            @if(is_array($doc->tags))
                                @foreach($doc->tags as $tag)
                                    <span title="مشاهده تمامی تگ های مشابه"
                                          class="text-xs font-medium px-2 py-0.5 rounded-md bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] inline-flex items-center gap-1 w-max cursor-pointer hover:opacity-80 transition-opacity border border-[var(--md-sys-color-outline-variant)]"
                                          wire:click="$set('search', '{{ $tag }}')">
                                        <span class="material-symbols-rounded text-[12px]">tag</span>
                                        {{ $tag }}
                                    </span>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </td>
                <td class="py-4 px-6 font-mono text-[var(--md-sys-color-on-surface-variant)] align-middle text-center"
                    dir="ltr">
                    {{ $doc->code ?? '' }} - {{ $doc->version ?? 'N/A' }}
                </td>
                <td class="py-4 px-6 max-w-[150px] align-middle text-right">
                    <div class="truncate text-[var(--md-sys-color-on-surface-variant)] text-xs leading-relaxed"
                         title="{!! $doc->getDepartmentNames() ?: 'بدون مالک' !!}">
                        {!! $doc->getDepartmentNames() ?: ' فردی (جدا از واحد)' !!}
                    </div>
                </td>
                <td class="py-4 px-6 align-middle text-center cursor-help">
                    <span title="{{ $doc->getStatusInFarsi() }}"
                          class="inline-flex items-center justify-center text-lg">
                        {!! $doc->getStatusIcon() ?? '-' !!}
                    </span>
                </td>
                <td class="py-4 px-6 align-middle text-right min-w-[150px]">
                    <div class="flex flex-col gap-1 text-xs">
                        @php($extraDetails = collect($doc->extra ?? [])->except(['category', 'Category', 'type', 'Type', 'users']))
                        @forelse($extraDetails as $key => $value)
                            <div class="flex items-center gap-1.5 border-b border-[var(--md-sys-color-outline-variant)]/30 pb-1 last:border-0 last:pb-0">
                                <span class="font-semibold text-[var(--md-sys-color-primary)] whitespace-nowrap">{{ $key }}:</span>
                                <span class="text-[var(--md-sys-color-on-surface-variant)] truncate" title="{{ $value }}">{{ $value }}</span>
                            </div>
                        @empty
                            <span class="text-[var(--md-sys-color-outline)] italic">بدون جزییات</span>
                        @endforelse
                    </div>
                </td>
                <td class="py-4 px-6 text-xs text-[var(--md-sys-color-on-surface-variant)] align-middle text-right min-w-[180px]">
                    <div class="flex flex-col gap-2">
                        @if ($doc->file)
                            @php
                                $ext = strtolower(pathinfo($doc->file, PATHINFO_EXTENSION));
                                $iconInfo = match($ext) {
                                    'pdf' => ['bg-[var(--md-sys-color-error-container)]', 'text-[var(--md-sys-color-on-error-container)]', 'picture_as_pdf', 'سند PDF'],
                                    'xlsx', 'xls', 'csv' => ['bg-[var(--md-sys-color-tertiary-container)]', 'text-[var(--md-sys-color-on-tertiary-container)]', 'table', 'فایل اکسل'],
                                    'docx', 'doc' => ['bg-[var(--md-sys-color-primary-container)]', 'text-[var(--md-sys-color-on-primary-container)]', 'description', 'سند Word'],
                                    default => ['bg-[var(--md-sys-color-surface-variant)]', 'text-[var(--md-sys-color-on-surface-variant)]', 'insert_drive_file', 'فایل ضمیمه'],
                                };
                            @endphp
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md w-max {{ $iconInfo[0] }} {{ $iconInfo[1] }} border border-current/10">
                                <span class="material-symbols-rounded text-[14px]">{{ $iconInfo[2] }}</span>
                                <span class="font-medium text-[11px]">{{ $iconInfo[3] }}</span>
                            </div>
                        @endif
                        <div class="leading-relaxed line-clamp-2" title="{{ $doc->revision ?? '' }}">
                            {{ $doc->revision ?? 'بدون توضیح' }}
                        </div>
                    </div>
                </td>
                <td class="py-4 px-6 align-middle text-center whitespace-nowrap">
                    @if ($doc->file)
                        @if(!$isConfirmed)
                            <button wire:click="confirmRead({{ $doc->id }})"
                                    class="inline-flex flex-col items-center justify-center gap-1 text-[var(--md-sys-color-error)] hover:text-[var(--md-sys-color-on-error-container)] hover:bg-[var(--md-sys-color-error-container)] px-3 py-2 rounded-xl transition-colors border border-[var(--md-sys-color-error)]/20 shadow-sm w-[110px]">
                                <span class="material-symbols-rounded text-xl">edit_document</span>
                                <span class="text-[11px] font-bold">تایید دریافت</span>
                            </button>
                        @elseif ($isConfirmed && !$isRead)
                            <a href="{{ route('secure-file', $doc->file) }}" target="_blank"
                               wire:click="incrementRead({{ $doc->id }})"
                               class="inline-flex flex-col items-center justify-center gap-1 text-[var(--md-sys-color-tertiary)] hover:text-[var(--md-sys-color-on-tertiary-container)] hover:bg-[var(--md-sys-color-tertiary-container)] px-3 py-2 rounded-xl transition-colors border border-[var(--md-sys-color-tertiary)]/20 shadow-sm w-[110px]">
                                <span class="material-symbols-rounded text-xl">menu_book</span>
                                <span class="text-[11px] font-bold">مشاهده و تایید</span>
                            </a>
                        @else
                            <a href="{{ route('secure-file', $doc->file) }}" target="_blank"
                               wire:click="incrementRead({{ $doc->id }})"
                               class="inline-flex flex-col items-center justify-center gap-1 text-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary-container)] hover:bg-[var(--md-sys-color-primary-container)] px-3 py-2 rounded-xl transition-colors w-[110px]">
                                <span class="material-symbols-rounded text-xl">visibility</span>
                                <span class="text-[11px] font-medium">مشاهده مجدد</span>
                            </a>
                        @endif
                    @else
                        <span class="text-[var(--md-sys-color-outline)] text-xs inline-flex items-center gap-1 bg-[var(--md-sys-color-surface-variant)] px-2 py-1 rounded-md">
                            <span class="material-symbols-rounded text-[14px]">link_off</span>
                            فایل ندارد
                        </span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="py-16 text-center text-[var(--md-sys-color-on-surface-variant)]">
                    <div class="flex flex-col items-center justify-center gap-3 opacity-60">
                        <span class="material-symbols-rounded text-5xl">folder_off</span>
                        <span class="text-base font-medium mt-1">هیچ سندی یافت نشد</span>
                        <span class="text-xs">لطفاً فیلترها را بررسی کنید</span>
                    </div>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
@if($hasMorePages)
    <div class="flex justify-center py-6 pb-2">
        <x-ui.buttons.load-more
            action="loadMore"
            text="بارگذاری بیشتر"
            loading-text="در حال دریافت..."
            icon="expand_more"
            class="font-medium text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-surface)] px-5 py-2.5 rounded-xl border border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:border-[var(--md-sys-color-primary)] shadow-sm hover:shadow-md"
        />
    </div>
@endif
