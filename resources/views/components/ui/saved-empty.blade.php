@props(['scope'])

<div x-show="onlySaved" x-cloak {{ $attributes }}>
    <div x-show="$store.pinned.isEmpty(@js($scope))">
        <x-ui.empty icon="bookmark" title="هنوز موردی نشان نکرده‌اید" description="روی مورد دلخواه، دکمهٔ «نشان کردن» را بزنید."/>
    </div>
    <p x-show="!$store.pinned.isEmpty(@js($scope))" class="px-4 py-2 text-center text-xs text-[var(--md-sys-color-on-surface-variant)]">فقط موارد نشان‌شدهٔ بارگذاری‌شده نمایش داده می‌شوند.</p>
</div>
