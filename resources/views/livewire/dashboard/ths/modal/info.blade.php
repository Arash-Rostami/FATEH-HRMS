@php
    $sm = $presenter->statusMeta($selectedTicket['status']);
    $pm = $presenter->priorityMeta($selectedTicket['priority']);
    $deptCode = $selectedTicket['target_department_code'] ?? null;
    $deadlineChip = $presenter->deadlineChip($selectedTicket['completion_deadline'] ?? null, $selectedTicket['completion_date'] ?? null, $selectedTicket['status']);
    $ring = $presenter->deadlineRing($selectedTicket);
    $timeline = $selectedTicket['timeline'] ?? [];
    $isRequester = ($selectedTicket['requester_id'] ?? null) === auth()->id();
    $files = [
        ...array_map(fn ($f) => [...$f, 'by' => 'درخواست‌کننده', 'group' => 'requester'], $selectedTicket['requester_files'] ?? []),
        ...array_map(fn ($f) => [...$f, 'by' => 'مسئول رسیدگی', 'group' => 'assignee'], $selectedTicket['assignee_files'] ?? []),
    ];
    $score = (int) ($selectedTicket['satisfaction_score'] ?? 0);
    $rowClass = 'flex items-center gap-3 min-w-0 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] bg-[var(--md-sys-color-surface-container-low)] px-3 py-2.5';
    $iconClass = 'flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]';
    $labelClass = 'text-[10px] text-[var(--md-sys-color-on-surface-variant)]';
    $valueClass = 'flex items-center gap-1.5 min-w-0 text-xs font-bold text-[var(--md-sys-color-on-surface)]';
    $unassignedClass = 'inline-flex items-center gap-1 text-[var(--md-sys-color-on-surface-variant)] italic font-normal text-[11px] px-2 py-0.5 bg-[var(--md-sys-color-surface-container-low)] rounded border border-dashed border-[var(--md-sys-color-outline-variant)]';
@endphp

<aside id="ths-modal-info"
       x-show="info"
       x-cloak
       x-transition:enter="animate-slide-fade-from-left"
       x-transition:leave="animate-slide-fade-to-left"
       x-on:click.outside="closeInfo()"
       class="absolute inset-y-0 left-0 z-30 flex w-72 md:w-80 max-w-full flex-col overflow-hidden rounded-xl max-sm:rounded-b-none border-r border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--md-sys-color-primary-container)] shadow-[0_16px_48px_color-mix(in_srgb,var(--md-sys-color-shadow)_24%,transparent)]"
       role="dialog"
       aria-label="توضیحات تیکت">

    <div class="flex flex-shrink-0 flex-col gap-3 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-low)_60%,transparent)] px-4 pt-3.5 pb-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                    <span class="material-symbols-rounded text-[15px]">confirmation_number</span>
                </span>
                <h3 class="text-xs font-bold tracking-tight text-[var(--md-sys-color-on-surface)]">توضیحات تیکت</h3>
            </div>
            <x-ui.modals.close-button close="closeInfo()"/>
        </div>

        @php
            $infoTabs = [
                ['id' => 'info', 'icon' => 'info', 'label' => 'مشخصات', 'description' => 'مشخصات تیکت',
                 'alpine' => "infoTab = 'info'", 'activeExpr' => "infoTab === 'info'"],
                ['id' => 'files', 'icon' => 'folder_open', 'label' => 'پیوست‌ها', 'description' => 'فایل‌های درخواست و پاسخ',
                 'alpine' => "infoTab = 'files'", 'activeExpr' => "infoTab === 'files'", 'countExpr' => (string) count($files)],
            ];
        @endphp
        <x-ui.buttons.tab-selector
            hasA11y
            :activeTab="''"
            :buttonBaseClass="'flex-1 justify-center px-2 py-1.5 rounded-lg text-[11px] font-bold transition-all duration-150'"
            :iconBaseClass="'material-symbols-rounded text-[14px]'"
            class="!mb-0 !w-full"
            :tabs="$infoTabs"/>
    </div>

    <div x-show="infoTab === 'info'" class="flex flex-1 min-h-0 flex-col gap-3 p-4 overflow-y-auto contact-scrollbar">
        <div class="flex items-center gap-3 rounded-2xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[var(--md-sys-color-surface-container-low)] p-3">
            <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl shadow-sm bg-[linear-gradient(135deg,var(--md-sys-color-primary),var(--md-sys-color-secondary))] text-[var(--md-sys-color-on-primary)]">
                <span class="material-symbols-rounded text-2xl">{{ $presenter->requestAreaIcon($selectedTicket['request_area'] ?? null, $deptCode) }}</span>
            </div>
            <div class="min-w-0 flex-1 flex flex-col gap-1">
                <p class="truncate text-sm font-bold text-[var(--md-sys-color-on-surface)]">{{ $presenter->requestAreaLabel($selectedTicket['request_type'] ?? null, $selectedTicket['request_area'] ?? null, $deptCode) }}</p>
                <p class="truncate text-[10px] text-[var(--md-sys-color-on-surface-variant)]">{{ $presenter->requestTypeLabel($selectedTicket['request_type'] ?? null, $deptCode) }} · حوزه</p>
                <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                    @if($sm)
                        <x-ui.decor.status-pill :title="$sm['title']" :color="$presenter->statusTone($selectedTicket['status'])"/>
                    @endif
                    @if($pm)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold {{ $pm['bg'] }}">
                            <span class="material-symbols-rounded text-[12px]">{{ $pm['icon'] }}</span>
                            {{ $pm['title'] }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div class="{{ $rowClass }} !px-2.5 !gap-2.5">
                <div class="{{ $iconClass }}"><span class="material-symbols-rounded text-base" aria-hidden="true">person</span></div>
                <div class="min-w-0 flex-1">
                    <p class="{{ $labelClass }}">مسئول رسیدگی</p>
                    @if(!empty($selectedTicket['assignee']))
                        <div wire:key="ths-info-assignee" class="{{ $valueClass }}">
                            <x-ui.avatar :existingImage="$selectedTicket['assignee_avatar']" :alt="$selectedTicket['assignee']['name']" size="xs"/>
                            <span class="truncate">{{ $selectedTicket['assignee']['name'] }}</span>
                        </div>
                    @else
                        <span wire:key="ths-info-unassigned" class="{{ $unassignedClass }}">
                            <span class="material-symbols-rounded text-[12px]">hourglass_empty</span> در انتظار تخصیص
                        </span>
                    @endif
                </div>
            </div>
            <div class="{{ $rowClass }} !px-2.5 !gap-2.5">
                <div class="{{ $iconClass }}"><span class="material-symbols-rounded text-base" aria-hidden="true">badge</span></div>
                <div class="min-w-0 flex-1">
                    <p class="{{ $labelClass }}">درخواست‌کننده</p>
                    <div class="{{ $valueClass }}">
                        @if(!empty($selectedTicket['requester_avatar']))
                            <x-ui.avatar :existingImage="$selectedTicket['requester_avatar']" :alt="$selectedTicket['requester']['name'] ?? ''" size="xs"/>
                        @endif
                        <span class="truncate">{{ $isRequester ? 'شما' : ($selectedTicket['requester']['name'] ?? '—') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="{{ $rowClass }}">
            <div class="{{ $iconClass }}"><span class="material-symbols-rounded text-base" aria-hidden="true">domain</span></div>
            <div class="min-w-0 flex-1">
                <p class="{{ $labelClass }}">واحد مقصد</p>
                <p class="truncate text-xs font-bold text-[var(--md-sys-color-on-surface)]">{{ $presenter->departmentLabel($deptCode) }}</p>
            </div>
        </div>

        <div class="{{ $rowClass }}">
            <div class="{{ $iconClass }}"><span class="material-symbols-rounded text-base" aria-hidden="true">timer</span></div>
            <div class="min-w-0 flex-1">
                <p class="{{ $labelClass }}">مهلت رسیدگی</p>
                @if($deadlineChip)
                    <span wire:key="ths-info-deadline" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg border text-[10px] font-bold {{ $deadlineChip['classes'] }}">
                        <span class="material-symbols-rounded text-[12px]">{{ $deadlineChip['icon'] }}</span>
                        {{ $deadlineChip['text'] }}
                    </span>
                @else
                    <span wire:key="ths-info-no-deadline" class="{{ $unassignedClass }}">{{ !empty($selectedTicket['completion_deadline']) ? toJalali($selectedTicket['completion_deadline'], 'j F Y') : 'بدون مهلت' }}</span>
                @endif
            </div>
            @if($ring)
                <x-ui.decor.progress-ring wire:key="ths-info-ring" :percent="$ring['percent']" :label="$ring['label']" :color="$ring['color']" title="سهم زمان مصرف‌شده از مهلت"/>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div class="{{ $rowClass }} !px-2.5 !gap-2.5">
                <div class="{{ $iconClass }}"><span class="material-symbols-rounded text-base" aria-hidden="true">calendar_month</span></div>
                <div class="min-w-0 flex-1">
                    <p class="{{ $labelClass }}">تاریخ ایجاد</p>
                    <p class="truncate text-xs font-bold text-[var(--md-sys-color-on-surface)]" title="{{ toJalali($selectedTicket['created_at']) }}">{{ toJalali($selectedTicket['created_at'], 'j F Y') }}</p>
                </div>
            </div>
            <div class="{{ $rowClass }} !px-2.5 !gap-2.5">
                <div class="{{ $iconClass }}"><span class="material-symbols-rounded text-base" aria-hidden="true">event_available</span></div>
                <div class="min-w-0 flex-1">
                    <p class="{{ $labelClass }}">تاریخ تکمیل</p>
                    @if($selectedTicket['completion_date'])
                        <p wire:key="ths-modal-completion" class="truncate text-xs font-bold text-[var(--md-sys-color-on-surface)]" title="{{ toJalali($selectedTicket['completion_date']) }}">{{ toJalali($selectedTicket['completion_date'], 'j F Y') }}</p>
                    @else
                        <span wire:key="ths-modal-completion-none" class="{{ $unassignedClass }}">—</span>
                    @endif
                </div>
            </div>
        </div>

        <div wire:key="ths-modal-strip" class="flex flex-col gap-1.5 px-3 py-2.5 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] bg-[var(--md-sys-color-surface-container-low)] text-[11px] text-[var(--md-sys-color-on-surface-variant)]">
            <span class="flex items-center gap-1.5 min-w-0">
                <span class="material-symbols-rounded text-[15px] text-[var(--md-sys-color-primary)]">history</span>
                <span class="truncate">{{ $timeline['opened'] ?? '' }}</span>
            </span>
            @if(!empty($timeline['assignee']))
                <span wire:key="ths-modal-strip-assignee" class="flex items-center gap-1.5 min-w-0">
                    <span class="material-symbols-rounded text-[15px] text-[var(--md-sys-color-primary)]">person</span>
                    <span class="truncate">{{ $timeline['assignee'] }}</span>
                </span>
            @endif
            <span class="flex items-center gap-1.5 min-w-0">
                <span class="material-symbols-rounded text-[15px] text-[var(--md-sys-color-primary)]">chat_bubble</span>
                <span class="truncate">{{ $timeline['response'] ?? '' }}</span>
            </span>
            @if(!empty($timeline['typical']))
                <span wire:key="ths-modal-strip-typical" class="flex items-center gap-1.5 min-w-0">
                    <span class="material-symbols-rounded text-[15px] text-[var(--md-sys-color-primary)]">schedule</span>
                    <span class="truncate">{{ $timeline['typical'] }}</span>
                </span>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div class="{{ $rowClass }} !px-2.5 !gap-2.5">
                <div class="{{ $iconClass }}"><span class="material-symbols-rounded text-base" aria-hidden="true">attach_file</span></div>
                <div class="min-w-0 flex-1">
                    <p class="{{ $labelClass }}">پیوست‌ها</p>
                    <p class="truncate text-xs font-bold text-[var(--md-sys-color-on-surface)]">{{ convertToPersian(count($files)) }} فایل</p>
                </div>
            </div>
            <div class="{{ $rowClass }} !px-2.5 !gap-2.5">
                <div class="{{ $iconClass }}"><span class="material-symbols-rounded text-base" aria-hidden="true">forum</span></div>
                <div class="min-w-0 flex-1">
                    <p class="{{ $labelClass }}">پاسخ‌ها</p>
                    <p class="truncate text-xs font-bold text-[var(--md-sys-color-on-surface)]">{{ convertToPersian((int) ($selectedTicket['replies_count'] ?? 0)) }}</p>
                </div>
            </div>
        </div>

        @if($score > 0)
            <div wire:key="ths-info-rating" class="flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] bg-[var(--md-sys-color-surface-container-low)] text-[11px] font-bold text-[var(--md-sys-color-on-surface)]">
                <span>{{ $isRequester ? 'ارزیابی شما' : 'ارزیابی درخواست‌کننده' }}</span>
                <span class="inline-flex gap-0.5" dir="ltr" aria-label="امتیاز {{ convertToPersian($score) }} از ۵">
                    @for($i = 1; $i <= 5; $i++)
                        <span class="material-symbols-rounded text-[16px] {{ $score >= $i ? 'text-[#FFB300] font-variation-fill' : 'text-[var(--md-sys-color-outline-variant)]/60' }}">star</span>
                    @endfor
                </span>
            </div>
        @endif
    </div>

    <div x-show="infoTab === 'files'" x-cloak class="flex flex-1 min-h-0 flex-col gap-1.5 p-4 overflow-y-auto contact-scrollbar">
        @forelse($files as $file)
            @php
                $name = $file['name'] ?? basename((string) ($file['file'] ?? ''));
                $isImage = Str::contains((string) ($file['file'] ?? ''), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
            @endphp
            <a wire:key="ths-info-file-{{ $file['group'] }}-{{ $loop->index }}"
               href="{{ $file['file_url'] }}"
               @if($isImage) data-fancybox="ticket-{{ $selectedTicket['id'] }}-info-files" data-caption="{{ $name }}" @else target="_blank" @endif
               title="{{ $name }}"
               class="flex items-center gap-2.5 min-w-0 rounded-xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] bg-[var(--md-sys-color-surface-container-low)] px-2.5 py-2 transition-colors hover:bg-[var(--md-sys-color-surface-container)]">
                @if($isImage)
                    <img src="{{ $file['file_url'] }}" alt="{{ $name }}" loading="lazy" class="h-9 w-9 shrink-0 rounded-lg object-cover">
                @else
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]">
                        <span class="material-symbols-rounded text-base" aria-hidden="true">description</span>
                    </div>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[11px] font-bold text-[var(--md-sys-color-on-surface)]" dir="auto">{{ $name }}</p>
                    <p class="truncate text-[10px] text-[var(--md-sys-color-on-surface-variant)]">{{ $file['by'] }}</p>
                </div>
            </a>
        @empty
            <div wire:key="ths-info-files-empty" class="contents">
                <x-ui.empty icon="folder_open" title="پیوستی وجود ندارد"/>
            </div>
        @endforelse
    </div>
</aside>
