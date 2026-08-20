<div
    x-data="{ ...calendarNow({ startIso: @js($startIso), spanHours: @js($spanHours ?? 18) }) }"
    x-show="nowTop >= 0"
    x-cloak
    x-bind:style="'top:' + nowTop + '%'"
    class="absolute left-0 right-0 h-[2px] bg-[var(--md-sys-color-error)] z-30 pointer-events-none"
>
    <span class="absolute -right-1 -top-[5px] w-[12px] h-[12px] rounded-full bg-[var(--md-sys-color-error)] shadow-[0_0_8px_var(--md-sys-color-error)]"></span>
</div>