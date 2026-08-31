<div class="group/thread relative py-4 ">
    <div class="flex gap-4 relative z-10">
        <div class="flex-shrink-0">
            <div class="flex rounded-md h-8 w-8">
                <x-ui.avatar :existingImage="auth()->user()?->getProfileImageUrl()"/>
            </div>
        </div>

        <div class="flex-1 min-w-0 pb-1">
            <div class="flex items-center justify-between gap-2 mb-1">
                <div class="flex items-center gap-2.5">
                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 tracking-tight">
                        {{ $getRecord()->user?->name ?? '—' }}
                    </span>
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-primary-500"></span>
                    </span>
                    <span class="h-1 w-1 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 tabular-nums">
                        {{ $getRecord()->created_at?->diffForHumans() }}
                    </span>
                </div>
            </div>

            <div class="text-sm leading-relaxed text-gray-700 dark:text-gray-300 prose prose-sm dark:prose-invert max-w-none [&>p]:mb-2 [&>p:last-child]:mb-0 [&_ul]:my-2 [&_ol]:my-2 break-words">
                {!! renderComment($getRecord()->content) !!}
            </div>
        </div>
    </div>

    @if($getRecord()->replies->isNotEmpty())
        <div class="relative mt-2 pr-5 sm:pr-12">
            <div class="absolute top-0 bottom-0 right-5 sm:right-[1.35rem] w-px bg-gray-200 dark:bg-gray-800 transition-colors duration-200 group-hover/thread:bg-gray-300 dark:group-hover/thread:bg-gray-700"></div>

            <div class="flex flex-col gap-3 relative z-10 pt-2 pb-1">
                @foreach($getRecord()->replies as $reply)
                    @include('filament.resources.feed.comment-reply', ['reply' => $reply, 'depth' => 1])
                @endforeach
            </div>
        </div>
    @endif
</div>
