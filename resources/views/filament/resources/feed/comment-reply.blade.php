<div class="group/reply relative flex gap-3 transition-colors duration-200 rounded-xl -ml-2 p-2 hover:bg-gray-50 dark:hover:bg-gray-800/50">
    <div class="flex-shrink-0 pt-0.5 relative z-10">
        <div class="flex rounded-md {{ $depth > 2 ? '!h-6 !w-6' : 'h-8 w-8 text-xs' }}">
            <x-ui.avatar
                :existingImage="optional(auth()->user()?->profile)->image"
            />
        </div>
    </div>

    <div class="flex-1 min-w-0">
        <div class="flex items-baseline gap-2 mb-0.5">
            <span class="text-[13px] font-semibold text-gray-900 dark:text-gray-100">
                {{ $reply->user?->name ?? '—' }}
            </span>
            <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 tabular-nums">
                {{ $reply->created_at?->diffForHumans() }}
            </span>
        </div>

        <div class="text-[13px] leading-relaxed text-gray-600 dark:text-gray-300 prose prose-sm dark:prose-invert max-w-none [&>p]:mb-1 [&>p:last-child]:mb-0 break-words">
            {!! $reply->content !!}
        </div>

        @if($reply->replies && $reply->replies->isNotEmpty())
            <div class="relative mt-3 pr-3 sm:pr-4">
                <div class="absolute top-0 bottom-0 right-[-1.15rem] sm:right-[-1.5rem] w-px bg-gray-200 dark:bg-gray-700/60 transition-colors duration-200 group-hover/reply:bg-gray-300 dark:group-hover/reply:bg-gray-600"></div>

                <div class="flex flex-col gap-2 relative z-10 pt-1">
                    @foreach($reply->replies as $nestedReply)
                        @include('filament.resources.feed.comment-reply', ['reply' => $nestedReply, 'depth' => $depth + 1])
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
