    {{-- Right Column: Scrollable feeds --}}
    <section class="flex-1 min-w-0 h-full overflow-y-auto custom-scrollbar pr-1 pl-1 pb-20">

        {{-- Header --}}
        <div class="flex items-center gap-2 mb-3 px-1">
            <span class="material-symbols-rounded text-[var(--md-sys-color-secondary)]">feed</span>
            <h3 class="text-lg font-bold text-[var(--md-sys-color-on-surface)]">تازه ترین‌ها</h3>
        </div>

        {{-- Posts Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-3 gap-4">
            @island(name: 'posts', lazy: true)
            @if($this->posts->isNotEmpty())
                @foreach($this->posts as $post)
                    @include('livewire.dashboard.tab.posts.partials.card', ['post' => $post])
                @endforeach
            @else
                {{-- Empty State --}}
                <div class="col-span-full text-center p-8 bg-[var(--md-sys-color-surface-container)] rounded-xl">
                    <span class="text-[var(--md-sys-color-outline)]">هیچ پستی یافت نشد.</span>
                </div>
            @endif
            @endisland
        </div>

        @if($hasMorePages)
            @include('livewire.dashboard.tab.posts.partials.load-more')
        @endif
    </section>
