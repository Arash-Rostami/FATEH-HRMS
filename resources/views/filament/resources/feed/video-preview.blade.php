@if(!empty($videos))
    <div class="grid grid-cols-1 {{ count($videos) > 1 ? 'md:grid-cols-2' : '' }} gap-4" dir="ltr">
        @foreach($videos as $video)
            <div class="relative overflow-hidden rounded-2xl bg-black shadow-md ring-1 ring-gray-900/5 dark:ring-white/10">
                <video
                    controls
                    playsinline
                    preload="metadata"
                    class="aspect-video w-full object-contain"
                >
                    <source src="{{ Storage::disk('public')->url($video) }}" type="video/{{ pathinfo($video, PATHINFO_EXTENSION) }}">
                </video>
            </div>
        @endforeach
    </div>
@else
    <div class="flex items-center justify-center rounded-xl bg-gray-50 p-6 ring-1 ring-inset ring-gray-200 dark:bg-gray-900/50 dark:ring-white/10">
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">ویدیویی برای نمایش وجود ندارد</span>
    </div>
@endif
