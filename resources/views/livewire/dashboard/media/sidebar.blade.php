<aside aria-label="کتابخانه" x-show="!theatre" :class="{ 'hidden': mobileDetail || theatre, 'flex': !mobileDetail, 'sb-collapsed': sbCollapsed, 'animate-sidebar-slide': sbSlide }"
       class="flex-shrink-0 flex flex-col border-l overflow-hidden transition-all duration-500 ease-[cubic-bezier(0.2,0,0,1)] md:flex w-full md:w-[320px] lg:w-[360px] bg-[var(--md-sys-color-surface)] border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
    <div class="flex-shrink-0 px-4 pt-4 pb-3">
        <div class="sb-head flex items-center justify-between mb-4">
            <div class="sb-fade flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]"><span class="material-symbols-rounded text-[18px]">video_library</span></div>
                <div><h2 class="text-sm font-semibold text-[var(--md-sys-color-on-surface)]">کتابخانه</h2><p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]" x-text="number(items.length) + ' مورد'"></p></div>
            </div>
            @include('livewire.dashboard.messaging.sidebar-toggle', ['margin' => 'ms-0', 'size' => 'w-8 h-8'])
        </div>
        <div class="sb-fold relative">
            <span class="material-symbols-rounded absolute right-3 top-2.5 text-[20px] text-[var(--md-sys-color-on-surface-variant)]">search</span>
            <input type="search" x-model="query" aria-label="جستجوی رسانه" placeholder="جستجوی سری یا ویدیو…" class="w-full h-10 rounded-xl text-sm bg-[var(--md-sys-color-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50 pr-10 pl-10 text-[var(--md-sys-color-on-surface)] focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]">
            <x-ui.modals.close-button close="clearSearch()" size="sm" class="absolute left-1 top-1.5" x-show="query" x-cloak />
        </div>
    </div>
    <div class="h-px mx-4 flex-shrink-0 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]"></div>
    <div class="sb-fold flex-shrink-0 px-4 pt-2.5 pb-2 overflow-x-auto custom-scrollbar" role="tablist" aria-label="فیلتر رسانه">
        <div class="flex items-center gap-1.5">
            @foreach(['all' => 'همه', 'audio' => 'صوتی', 'video' => 'تصویری', 'series' => 'سری‌ها', 'continue' => 'ادامه'] as $value => $label)
                <button type="button" role="tab" data-filter="{{ $value }}" x-on:click="setFilter($el.dataset.filter)" :aria-selected="filter === $el.dataset.filter" aria-controls="media-list"
                        class="px-2.5 py-1 rounded-md text-[10px] font-bold transition-all duration-200 whitespace-nowrap text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] aria-selected:bg-[var(--md-sys-color-primary-container)] aria-selected:text-[var(--md-sys-color-on-primary-container)]">{{ $label }}</button>
            @endforeach
        </div>
    </div>
    <div class="sb-fold px-4 pb-3 flex gap-2">
        <x-ui.forms.select label="بخش" name="media-department" x-model="department"><option value="">همهٔ بخش‌ها</option><template x-for="name in departments" :key="name"><option :value="name" x-text="name"></option></template></x-ui.forms.select>
        <x-ui.forms.select label="ترتیب" name="media-sort" x-model="sort"><option value="recent">تازه‌ترین</option><option value="title">عنوان</option><option value="duration">مدت</option></x-ui.forms.select>
        <x-ui.buttons.icon-toggle state="onlySaved" icon="bookmark" title="فقط نشان‌شده‌ها" class="self-end !flex" />
    </div>
    <div id="media-list" x-effect="rankRows()" class="flex flex-col flex-1 min-h-0 overflow-y-auto py-1 contact-scrollbar" role="listbox" aria-label="رسانه‌ها">
        @foreach($this->library['items'] as $item)
            <div wire:key="media-row-{{ $item['id'] }}" data-id="{{ $item['id'] }}" data-rf="media-{{ $item['id'] }}"
                 x-show="ranks[$el.dataset.id] !== undefined" x-bind:style="{ order: ranks[$el.dataset.id] }"
                 x-on:click="choose($el.dataset.id)" x-on:keydown.enter.self.prevent="choose($el.dataset.id)" x-on:keydown.space.self.prevent="choose($el.dataset.id)"
                 role="option" tabindex="0" :aria-selected="selectedId === Number($el.dataset.id)" title="{{ $item['title'] }}"
                 class="sb-row group ripple-effect relative isolate w-full shrink-0 flex items-center gap-3 px-4 py-2.5 text-right transition-all duration-200 cursor-pointer rounded-md hover:bg-[var(--md-sys-color-surface-variant)] aria-selected:bg-[color-mix(in_srgb,var(--md-sys-color-primary-container)_40%,transparent)] aria-selected:border-r-2 aria-selected:border-[var(--md-sys-color-primary)]">
                <div class="relative flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center shadow-sm ring-1 ring-[var(--md-sys-color-outline-variant)]" data-accent="{{ $item['accent'] }}" :style="accentStyle($el.dataset.accent)">
                    @if($item['cover_url'])
                        <img wire:key="media-row-cover" src="{{ $item['cover_url'] }}" alt="" loading="lazy" class="w-full h-full rounded-xl object-cover">
                    @else
                        <span wire:key="media-row-icon" class="material-symbols-rounded text-[20px]">{{ $item['icon'] }}</span>
                    @endif
                    <span x-show="playing && cur.sid === {{ $item['id'] }}" x-cloak class="absolute bottom-0 flex gap-0.5 items-end" aria-label="در حال پخش"><i class="animate-eq origin-bottom w-1 h-3 bg-[var(--md-sys-color-primary)]"></i><i class="animate-eq origin-bottom w-1 h-3 bg-[var(--md-sys-color-primary)]"></i><i class="animate-eq origin-bottom w-1 h-3 bg-[var(--md-sys-color-primary)]"></i></span>
                </div>
                <div class="sb-fade flex-1 min-w-0">
                    <p class="truncate text-[13px] font-semibold text-[var(--md-sys-color-on-surface)]" data-title="{{ $item['title'] }}" x-html="highlight($el.dataset.title, query)"></p>
                    <p class="text-[11px] truncate text-[var(--md-sys-color-on-surface-variant)]">{{ $item['kind'] === 'video' ? 'تصویری' : 'صوتی' }} · {{ $item['type'] === 'series' ? 'سری' : 'تک‌قسمت' }} · {{ convertToPersian((string) count($item['episodes'])) }} جلسه</p>
                    <div class="mt-2 h-1.5 w-full rounded-full bg-[var(--md-sys-color-surface-variant)] overflow-hidden" role="progressbar" aria-label="پیشرفت" aria-valuemin="0" aria-valuemax="100" x-bind:aria-valuenow="percent(progressOf({{ $item['id'] }}))">
                        <div class="h-full rounded-full transition-all duration-500 bg-[var(--md-sys-color-primary)]" x-bind:style="{ width: percent(progressOf({{ $item['id'] }})) + '%' }"></div>
                    </div>
                </div>
                <x-ui.buttons.bookmark :id="$item['id']" scope="media" class="shrink-0" />
            </div>
        @endforeach
        <div wire:key="media-sidebar-empty" x-show="!visibleItems.length" class="contents"><x-ui.empty icon="search_off" title="موردی پیدا نشد" description="جستجو یا فیلتر را تغییر دهید." /></div>
        <div class="sb-fold" x-show="onlySaved"><x-ui.saved-empty scope="media" /></div>
    </div>
</aside>
