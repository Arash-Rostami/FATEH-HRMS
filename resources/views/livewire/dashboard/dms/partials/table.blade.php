@island('docs')
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
            <th class="py-4 px-6 font-semibold whitespace-nowrap">
                <div class="flex items-center justify-center gap-1.5">
                    <span class="material-symbols-rounded text-[18px]">download</span>فایل
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
                    <span class="material-symbols-rounded text-[18px]">comment</span>توضیحات
                </div>
            </th>
            <th class="py-4 px-6 font-semibold whitespace-nowrap">
                <div class="flex items-center justify-center gap-1.5">
                    <span class="material-symbols-rounded text-[18px]">visibility</span>مشاهده
                </div>
            </th>
        </tr>
        </thead>
        <tbody class="divide-y divide-[var(--md-sys-color-outline-variant)]">
        @forelse($this->docs as $doc)
            @php($isConfirmed = in_array($doc->id, $this->confirmedDocs))
            @php($isRead = in_array($doc->id, $this->readDocs))
            <tr @class([
                       'transition-colors hover:bg-[var(--md-sys-color-surface-container-low)]',
                       'bg-gradient-to-l from-[var(--md-sys-color-error)]/10 to-transparent border-l-3 border-l-[var(--md-sys-color-error)]' => !$isConfirmed && !$isRead ,
                       'bg-gradient-to-l from-[var(--md-sys-color-outline-variant)]/30 to-transparent border-l-3 border-l-[var(--md-sys-color-outline)]' => $isConfirmed && !$isRead ,
                       'border-l-3 border-l-transparent'  => $isConfirmed && $isRead
                    ])>
                <td class="py-4 px-6 min-w-[200px] align-middle text-right">
                    <div class="flex flex-col items-start gap-1.5">
                        <span
                            title="{{ 'ایجاده شده در: ' . jdateOnly($doc->created_at) . ' 📆 بروزرسانی شده در: ' . jdateOnly($doc->updated_at) }}"
                            class="font-bold text-base text-[var(--md-sys-color-on-surface)] leading-relaxed block mt-0.5 cursor-help">
                            {{ $doc->title ?? 'بدون عنوان' }}
                        </span>
                        <span title="مشاهده تمامی تگ های مشابه"
                              class="text-xs font-medium px-2.5 py-1 rounded-md bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] inline-flex items-center gap-1 w-max cursor-pointer hover:opacity-80 transition-opacity"
                              wire:click="$set('search', '{{ optional($doc->extra)['category'] ?? optional($doc->extra)['Category'] }}')">
                            <span class="material-symbols-rounded text-[14px]">sell</span>
                            {{ optional($doc->extra)['category'] ?? optional($doc->extra)['Category'] ?? 'بدون دسته‌بندی' }}
                        </span>
                    </div>
                </td>
                <td class="py-4 px-6 font-mono text-[var(--md-sys-color-on-surface-variant)] align-middle text-center"
                    dir="ltr">
                    {{ $doc->code ?? '' }} - {{ $doc->version ?? 'N/A' }}
                </td>
                <td class="py-4 px-6 align-middle text-center whitespace-nowrap">
                    @if ($doc->file)
                        @if($isConfirmed)
                            <a href="{{ route('secure-file', $doc->file) }}" target="_blank"
                               wire:click.prevent="incrementRead({{ $doc->id }})"
                               class="inline-flex flex-col items-center justify-center gap-1.5 text-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-on-primary-container)] hover:bg-[var(--md-sys-color-primary-container)] p-2.5 rounded-xl transition-colors">
                                <span class="material-symbols-rounded text-2xl">search</span>
                                <span class="text-xs font-medium">مشاهده سند</span>
                            </a>
                        @else
                            <button @click="confirmAndSend({{ $doc->id }}, '{{ $doc->file }}')"
                                    class="inline-flex flex-col items-center justify-center gap-1.5 text-[var(--md-sys-color-error)] hover:text-[var(--md-sys-color-on-error-container)] hover:bg-[var(--md-sys-color-error-container)] p-2.5 rounded-xl transition-colors">
                                <span class="material-symbols-rounded text-2xl">draw</span>
                                <span class="text-xs font-medium">تأیید خواندن</span>
                            </button>
                        @endif
                    @else
                        <span class="text-[var(--md-sys-color-outline)] text-xs">فایل ندارد</span>
                    @endif
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
                <td class="py-4 px-6 text-xs text-[var(--md-sys-color-on-surface-variant)] max-w-[200px] truncate align-middle text-right"
                    title="{{ $doc->revision ?? '' }}">
                    {{ $doc->revision ?? 'بدون توضیح' }}
                </td>
                <td class="py-4 px-6 align-middle text-center font-mono font-medium text-[var(--md-sys-color-primary)]">
                    <span
                        class="bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] px-2.5 py-1.5 rounded-lg inline-flex items-center justify-center gap-1.5">
                        {{ $doc->reads->sum('read_count') ?? 0 }}
                        <span class="material-symbols-rounded text-[14px]">bar_chart</span>
                    </span>
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
        <x-dashboard.button.load-more
            action="loadMore"
            text="بارگذاری بیشتر"
            loading-text="در حال دریافت..."
            icon="expand_more"
            class="font-medium text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-surface)] px-5 py-2.5 rounded-xl border border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:border-[var(--md-sys-color-primary)] shadow-sm hover:shadow-md"
        />
    </div>
@endif
@endisland
