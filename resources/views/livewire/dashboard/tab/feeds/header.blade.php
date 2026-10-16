<div class="flex items-center justify-between gap-3 mb-4 p-5" dir="rtl">
    <div class="flex min-w-0 items-center gap-3">
        @if($feed->user)
            <x-dashboard.user-avatar :user="$feed->user" size="md"/>
        @else
            <x-ui.avatar :existingImage="$presenter->avatarUrl(null)" alt="Guest" size="md"/>
        @endif

        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <h4 class="min-w-0 truncate text-sm font-semibold leading-tight text-[var(--md-sys-color-on-surface)]">
                    {{ $feed->user?->name ?? 'کاربر ناشناس' }}
                </h4>

                <span
                    title="{{ $feed->category ?? 'عمومی' }}"
                    class="flex cursor-pointer items-center justify-center rounded-full bg-[var(--md-sys-color-surface)] ring-2 ring-[var(--md-sys-color-surface)] text-[10px] shadow-sm"
                >
                    {{ $presenter->categoryEmoji($feed) }}
                </span>
            </div>

            <p class="mt-0.5 text-xs text-[var(--md-sys-color-on-surface-variant)]">
                {{ $feed->created_at ? jdate($feed->created_at)->ago() : '' }}
            </p>
        </div>
    </div>

    <x-ui.modals.maximize-button toggle="toggleMaximize(feed($el))" state="maximizedFeed === feed($el)" size="tile" class="ripple-effect hidden md:flex"/>
</div>