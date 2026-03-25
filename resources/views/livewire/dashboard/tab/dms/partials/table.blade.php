<div class="overflow-x-auto rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm">
    <table class="w-full text-right border-collapse text-sm">
        <thead class="bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface-variant)] border-b border-[var(--md-sys-color-outline-variant)]">
            <tr>
                <th class="py-4 px-4 font-semibold whitespace-nowrap"><span class="material-symbols-rounded align-middle ml-1 text-[18px]">description</span>عنوان سند</th>
                <th class="py-4 px-4 font-semibold whitespace-nowrap"><span class="material-symbols-rounded align-middle ml-1 text-[18px]">tag</span>نسخه</th>
                <th class="py-4 px-4 font-semibold whitespace-nowrap text-center"><span class="material-symbols-rounded align-middle ml-1 text-[18px]">download</span>فایل</th>
                <th class="py-4 px-4 font-semibold whitespace-nowrap max-w-xs"><span class="material-symbols-rounded align-middle ml-1 text-[18px]">group</span>واحد(های) ذی نفع</th>
                <th class="py-4 px-4 font-semibold whitespace-nowrap text-center"><span class="material-symbols-rounded align-middle ml-1 text-[18px]">info</span>وضعیت</th>
                <th class="py-4 px-4 font-semibold whitespace-nowrap"><span class="material-symbols-rounded align-middle ml-1 text-[18px]">comment</span>توضیحات</th>
                <th class="py-4 px-4 font-semibold whitespace-nowrap text-center"><span class="material-symbols-rounded align-middle ml-1 text-[18px]">visibility</span>مشاهده</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[var(--md-sys-color-outline-variant)]">
            @forelse($this->docs as $doc)
                @php
                    $isConfirmed = in_array($doc->id, $this->confirmedDocs);
                    $isRead = in_array($doc->id, $this->readDocs);
                @endphp
                <tr class="transition-colors hover:bg-[var(--md-sys-color-surface-container-low)]
                           {{ !$isConfirmed && !$isRead ? 'bg-gradient-to-l from-[var(--md-sys-color-error-container)]/30 to-transparent border-l-4 border-l-[var(--md-sys-color-error)]' : '' }}
                           {{ $isConfirmed && !$isRead ? 'bg-gradient-to-l from-[var(--md-sys-color-outline-variant)]/30 to-transparent border-l-4 border-l-[var(--md-sys-color-outline)]' : '' }}
                           {{ $isConfirmed && $isRead ? 'border-l-4 border-l-transparent' : '' }}">

                    {{-- Title --}}
                    <td class="py-3 px-4 min-w-[200px]">
                        <div class="flex flex-col gap-1">
                            <span class="text-xs font-medium px-2 py-0.5 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] inline-flex items-center gap-1 w-max cursor-pointer hover:opacity-80 transition-opacity"
                                  wire:click="$set('search', '{{ optional($doc->extra)['category'] ?? optional($doc->extra)['Category'] }}')">
                                <span class="material-symbols-rounded text-[14px]">sell</span>
                                {{ optional($doc->extra)['category'] ?? optional($doc->extra)['Category'] ?? 'بدون دسته‌بندی' }}
                            </span>
                            <span class="font-bold text-[var(--md-sys-color-on-surface)] leading-relaxed block mt-1">
                                {{ $doc->title ?? 'بدون عنوان' }}
                            </span>
                        </div>
                    </td>

                    {{-- Version --}}
                    <td class="py-3 px-4 font-mono text-[var(--md-sys-color-on-surface-variant)]" dir="ltr">
                        {{ $doc->code ?? '' }} - {{ $doc->version ?? 'N/A' }}
                    </td>

                    {{-- File Actions --}}
                    <td class="py-3 px-4 text-center whitespace-nowrap">
                        @if ($doc->file)
                            @if($isConfirmed)
                                <a href="{{ route('secure-file', $doc->file) }}"
                                   target="_blank"
                                   wire:click.prevent="incrementRead({{ $doc->id }})"
                                   class="inline-flex flex-col items-center justify-center gap-1 text-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary-container)] hover:bg-[var(--md-sys-color-primary-container)] p-2 rounded-xl transition-colors">
                                    <span class="material-symbols-rounded text-2xl">search</span>
                                    <span class="text-xs font-medium">مشاهده سند</span>
                                </a>
                            @else
                                <button @click="confirmAndSend({{ $doc->id }}, '{{ $doc->file }}')"
                                        class="inline-flex flex-col items-center justify-center gap-1 text-[var(--md-sys-color-error)] hover:text-[var(--md-sys-color-on-error-container)] hover:bg-[var(--md-sys-color-error-container)] p-2 rounded-xl transition-colors">
                                    <span class="material-symbols-rounded text-2xl">draw</span>
                                    <span class="text-xs font-medium">تأیید خواندن</span>
                                </button>
                            @endif
                        @else
                            <span class="text-[var(--md-sys-color-outline)] text-xs">فایل ندارد</span>
                        @endif
                    </td>

                    {{-- Owners --}}
                    <td class="py-3 px-4 max-w-[150px]">
                        <div class="truncate text-[var(--md-sys-color-on-surface-variant)] text-xs" title="{!! $doc->getAllDepartmentNamesInFarsi() ?: 'بدون مالک' !!}">
                            {!! $doc->getAllDepartmentNamesInFarsi() ?: 'بدون مالک' !!}
                        </div>
                    </td>

                    {{-- Status --}}
                    <td class="py-3 px-4 text-center">
                        <span title="{{ $doc->getStatusInFarsi() }}" class="inline-flex items-center justify-center">
                            {!! $doc->getStatusIcon() ?? '-' !!}
                        </span>
                    </td>

                    {{-- Revision --}}
                    <td class="py-3 px-4 text-xs text-[var(--md-sys-color-on-surface-variant)] max-w-[200px] truncate" title="{{ $doc->revision ?? '' }}">
                        {{ $doc->revision ?? 'بدون توضیح' }}
                    </td>

                    {{-- Reads --}}
                    <td class="py-3 px-4 text-center font-mono font-medium text-[var(--md-sys-color-primary)]">
                        <span class="bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] px-2 py-1 rounded-lg inline-flex items-center gap-1">
                            {{ $doc->reads->sum('read_count') ?? 0 }}
                            <span class="material-symbols-rounded text-[14px]">bar_chart</span>
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-[var(--md-sys-color-on-surface-variant)]">
                        <div class="flex flex-col items-center justify-center gap-2 opacity-60">
                            <span class="material-symbols-rounded text-5xl">folder_off</span>
                            <span class="text-base font-medium mt-2">هیچ سندی یافت نشد</span>
                            <span class="text-xs">لطفاً فیلترها را بررسی کنید</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
