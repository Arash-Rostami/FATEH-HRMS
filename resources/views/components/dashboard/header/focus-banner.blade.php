
@if(!empty($open))
    <div
        wire:key="focus-banner-{{ $open }}"
        class="focus-banner"
        role="status"
        aria-live="polite"
        dir="rtl"
    >
        <span class="focus-banner__pulse" aria-hidden="true">
            <span class="material-symbols-rounded">center_focus_strong</span>
        </span>

        <div class="focus-banner__copy">
            <span class="focus-banner__title">نتیجهٔ جستجو</span>
            <span class="focus-banner__subtitle">فقط مورد انتخاب‌شده از پالت فرمان نمایش داده می‌شود.</span>
        </div>

        <button
            type="button"
            class="focus-banner__btn"
            wire:click="clearFocus"
            wire:loading.attr="disabled"
        >
            <span class="material-symbols-rounded">close</span>
            <span>نمایش همه</span>
        </button>
    </div>
@endif
