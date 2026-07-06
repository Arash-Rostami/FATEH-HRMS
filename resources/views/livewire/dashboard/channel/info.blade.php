@php
    $p = $this->presenter;
    $header = $p->channelHeader($this->activeChannel);
@endphp
<div x-show="showInfo"
     x-transition:enter="ease-out duration-300"
     x-transition:enter-start="opacity-0 -translate-x-8"
     x-transition:enter-end="opacity-100 translate-x-0"
     x-transition:leave="ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0 -translate-x-8"
     x-on:click.outside="showInfo = false"
     class="absolute inset-y-0 left-0 z-30 w-72 md:w-80 bg-[var(--md-sys-color-primary-container)] overflow-y-auto contact-scrollbar rounded-xl"
     role="dialog">
    <div class="p-5 space-y-5 border-none">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">اطلاعات کانال</h3>
            <button x-on:click="showInfo = false" aria-label="بستن"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-[var(--md-sys-color-on-surface-variant)]">
                <span class="material-symbols-rounded text-base" aria-hidden="true">close</span>
            </button>
        </div>
        <div class="flex flex-col items-center gap-3 py-4">
            <div class="w-20 h-20 rounded-xl flex items-center justify-center text-2xl shadow-lg bg-[linear-gradient(135deg,var(--md-sys-color-primary),var(--md-sys-color-secondary))] text-[var(--md-sys-color-on-primary)]">
                <span class="material-symbols-rounded text-4xl">{{ $header['type_icon'] }}</span>
            </div>
            <p class="text-sm font-bold text-[var(--md-sys-color-on-surface)]">{{ $header['name'] }}</p>
            <span class="text-[11px] font-medium px-2 py-0.5 rounded-md bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">{{ $header['type_label'] }}</span>
        </div>
        <div class="space-y-3">
            @if($header['description'])
                <div class="px-3 py-2.5 rounded-xl bg-[var(--md-sys-color-surface)]">
                    <p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] mb-1">توضیحات</p>
                    <p class="text-xs leading-relaxed text-[var(--md-sys-color-on-surface)]">{{ $header['description'] }}</p>
                </div>
            @endif
            <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[var(--md-sys-color-primary)]">
                <span class="material-symbols-rounded text-base text-[var(--md-sys-color-on-primary)]" aria-hidden="true">groups</span>
                <div>
                    <p class="text-[10px] text-[var(--md-sys-color-on-primary)]">اعضا</p>
                    <p class="text-xs font-medium text-[var(--md-sys-color-on-primary)]">{{ $header['members_count'] }} نفر</p>
                </div>
            </div>
            <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-[var(--md-sys-color-primary)]">
                <span class="material-symbols-rounded text-base text-[var(--md-sys-color-on-primary)]" aria-hidden="true">person</span>
                <div class="min-w-0">
                    <p class="text-[10px] text-[var(--md-sys-color-on-primary)]">مدیر کانال</p>
                    <p class="text-xs font-medium truncate text-[var(--md-sys-color-on-primary)]">{{ $header['owner_name'] }}</p>
                </div>
            </div>
        </div>
    </div>
</div>