@if(!empty($open))
    <div wire:key="focus-chip-{{ $open }}" class="focus-chip" dir="rtl" role="status">
        <span class="material-symbols-rounded focus-chip__icon" aria-hidden="true">center_focus_strong</span>
        <span class="focus-chip__text">نتیجهٔ جستجو</span>
        <button type="button" class="focus-chip__close" wire:click="clearFocus"
                wire:loading.attr="disabled" title="پاک‌کردن تمرکز جستجو" aria-label="بستن">
            <span class="material-symbols-rounded">close</span>
        </button>
    </div>
@endif
