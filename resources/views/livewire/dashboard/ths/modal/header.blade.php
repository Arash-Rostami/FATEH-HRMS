@php
    $sm = $presenter->statusMeta($selectedTicket['status']);
    $pm = $presenter->priorityMeta($selectedTicket['priority']);
    $fId = $presenter->formatId($selectedTicket);
    $createdRelative = $presenter->formatTimestamp($selectedTicket, 'created_at');
    $requesterName = ($selectedTicket['requester_id'] ?? null) === auth()->id() ? 'شما' : ($selectedTicket['requester']['name'] ?? '—');
@endphp

<div class="sm:hidden flex justify-center pt-2.5 pb-0.5 shrink-0 touch-none cursor-grab bg-[var(--md-sys-color-surface-container-low)]"
     :class="max ? '!hidden' : ''"
     x-on:pointerdown="startDrag($event)"
     x-on:pointermove="moveDrag($event)"
     x-on:pointerup="endDrag()"
     x-on:pointercancel="endDrag()"
     data-ths-handle
     aria-label="کشیدن برای بستن">
    <span class="w-10 h-1 rounded-full bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_90%,transparent)]"></span>
</div>

<div class="flex items-start gap-3 px-4 sm:px-6 py-3.5 border-b border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-low)] shrink-0">
    <div class="flex items-start gap-3 flex-1 min-w-0">
        <div class="w-10 h-10 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center shadow-sm shrink-0">
            <span class="material-symbols-rounded text-xl">confirmation_number</span>
        </div>
        <div class="flex-1 min-w-0 flex flex-col gap-1">
            <div class="flex items-center gap-2 flex-wrap">
                <p title="📆 {{ $createdRelative }}" dir="ltr"
                   class="cursor-help font-bold text-[var(--md-sys-color-on-surface)] text-[13px] tracking-wide font-mono">{{ $fId }}</p>
                <x-ui.buttons.copy :text="$fId" message="شناسهٔ تیکت کپی شد." aria-label="کپی شناسه" class="!p-0 w-6 h-6 [&_span]:!text-[14px]"/>
                @if($sm)
                    <x-ui.decor.status-pill :title="$sm['title']" :color="$presenter->statusTone($selectedTicket['status'])" class="hidden sm:inline-flex"/>
                @endif
                @if($pm)
                    <span class="hidden sm:inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold {{ $pm['bg'] }}">
                        <span class="material-symbols-rounded text-[12px]">{{ $pm['icon'] }}</span>
                        {{ $pm['title'] }}
                    </span>
                @endif
            </div>
            <h2 id="ths-modal-title" class="text-[15px] font-bold leading-relaxed line-clamp-2 [overflow-wrap:anywhere] text-[var(--md-sys-color-on-surface)]">{{ $selectedTicket['request_subject'] }}</h2>
            <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-1.5 flex-wrap">
                <span>ایجاد شده {{ $createdRelative }}</span>
                <span class="w-[3px] h-[3px] rounded-full bg-current opacity-50"></span>
                <span>{{ $requesterName }}</span>
                <span class="w-[3px] h-[3px] rounded-full bg-current opacity-50"></span>
                <span>{{ $presenter->departmentLabel($selectedTicket['target_department_code'] ?? null) }}</span>
            </p>
        </div>
    </div>

    <div class="flex items-center gap-1.5 shrink-0">
        <x-ui.modals.maximize-button size="lg" class="hidden sm:inline-flex"/>
        <x-ui.modals.close-button close="closeModal()" size="lg" data-ths-close class="max-sm:w-11 max-sm:h-11"/>
    </div>
</div>

<div class="flex items-center justify-between flex-nowrap gap-3 px-4 sm:px-6 py-3 border-b border-[var(--md-sys-color-outline-variant)]/50 bg-[var(--md-sys-color-surface-container-lowest)] shrink-0">
    <x-ui.buttons.tab-selector
        :activeTab="$modalTab"
        class="!mb-0 flex-1 sm:flex-initial min-w-0"
        :buttonBaseClass="'relative flex-1 sm:flex-initial justify-center px-2 py-2 sm:px-6 sm:py-2.5 min-h-[44px] sm:min-h-0 rounded-xl text-xs sm:text-sm font-bold transition-all duration-300 z-10 flex items-center gap-1.5 sm:gap-2 shrink-0 whitespace-nowrap'"
        :tabs="[
            ['id' => 'request', 'label' => 'جزئیات', 'icon' => 'description'],
            ['id' => 'response', 'label' => 'پاسخ و پیگیری', 'icon' => 'forum', 'unread' => (int) ($selectedTicket['replies_count'] ?? 0)],
        ]"
    />

    <button type="button"
            x-on:click="info ? closeInfo() : openInfo()"
            :aria-expanded="info"
            aria-controls="ths-modal-info"
            aria-label="توضیحات تیکت"
            title="توضیحات تیکت"
            data-ths-info-toggle
            class="inline-flex items-center justify-center gap-1.5 shrink-0 self-stretch min-h-[44px] sm:min-h-10 min-w-[44px] sm:min-w-10 px-0 min-[480px]:px-2.5 rounded-xl text-xs font-bold transition-all duration-200 ease-out active:scale-95"
            :class="info
                ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)]'
                : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:text-[var(--md-sys-color-primary)]'">
        <span class="material-symbols-rounded text-base">info</span>
        <span class="hidden min-[480px]:inline">توضیحات</span>
    </button>
</div>
