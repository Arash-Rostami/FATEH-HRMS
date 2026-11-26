@use('App\Models\Media')
<aside aria-label="کتابخانه" :class="{ 'hidden': mobileDetail, 'flex': !mobileDetail, 'sb-collapsed': sbCollapsed, 'animate-sidebar-slide': sbSlide }"
       class="chat-scale flex-shrink-0 flex flex-col border-l overflow-hidden transition-all duration-500 ease-[cubic-bezier(0.2,0,0,1)] md:flex w-full md:w-[320px] lg:w-[360px] bg-[var(--md-sys-color-surface)] border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
    <div class="flex-shrink-0 px-4 pt-4 pb-3">
        <div class="sb-head flex items-center justify-between mb-4">
            <div class="sb-fade flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]"><span class="material-symbols-rounded text-[18px]">video_library</span></div>
                <div><h2 class="text-sm font-semibold text-[var(--md-sys-color-on-surface)]">کتابخانه</h2><p class="text-[10px] text-[var(--md-sys-color-on-surface-variant)]" x-text="number(items.length) + ' مورد'"></p></div>
            </div>
            <div class="sb-cluster ms-auto flex items-center gap-1.5">
                <div class="sb-fade">
                    <x-ui.buttons.icon-toggle state="filtersOpen" icon="filter_alt" title="فیلترهای رسانه"
                                              aria-label="فیلترهای رسانه" aria-controls="media-sidebar-filters"
                                              x-bind:aria-expanded="filtersOpen" class="!flex !w-6 !h-6 !rounded-lg"/>
                </div>
                @include('livewire.dashboard.messaging.sidebar-toggle', ['margin' => 'ms-0'])
            </div>
        </div>
        <div id="media-sidebar-filters" x-show="filtersOpen" x-collapse x-cloak class="sb-fold">
            <div class="pb-3 flex gap-2">
                <x-ui.forms.select label="واحد سازمانی" name="media-department" x-model="department"><option value="">همهٔ واحدها</option><template x-for="name in departments" :key="name"><option :value="name" x-text="name"></option></template></x-ui.forms.select>
                <x-ui.buttons.icon-toggle state="onlySaved" icon="bookmark" title="فقط نشان‌شده‌ها" class="self-end !flex" />
            </div>
        </div>
        <div class="sb-fold relative group" x-data="{ fullscreen: false, get value() { return query }, set value(v) { query = String(v) } }">
            <span class="material-symbols-rounded absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[var(--md-sys-color-on-surface-variant)] group-focus-within:text-[var(--md-sys-color-primary)] transition-colors z-10">search</span>
            <x-ui.forms.maximize-trigger class="inset-y-0 left-10"/>
            <input type="text" id="media-search" x-model="query" aria-label="جست‌وجوی رسانه" placeholder="جست‌وجو در عنوان‌ها و جلسه‌ها…" autocomplete="off" spellcheck="false"
                   class="md3-input peer w-full h-10 leading-[40px] rounded-xl text-sm outline-none transition-all focus:ring-2 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50 pr-10 pl-20 placeholder-transparent">
            <x-ui.modals.close-button close="clearSearch()" size="sm" class="absolute left-1 top-1.5" x-show="query" x-cloak />
            <label for="media-search" class="md3-label absolute top-1/2 -translate-y-1/2 pointer-events-none peer-placeholder-shown:right-10 peer-focus:right-4 right-4 transition-all duration-200">جست‌وجو در عنوان‌ها و جلسه‌ها…</label>
            <x-ui.forms.maximize-overlay icon="search" title="جست‌وجوی رسانه"/>
        </div>
    </div>
    <div class="h-px mx-4 flex-shrink-0 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]"></div>
    <div class="sb-fold flex-shrink-0 px-4 pt-2.5 pb-2 overflow-x-auto custom-scrollbar" role="tablist" aria-label="فیلتر رسانه">
        <div class="flex items-center gap-1.5">
            @foreach(['all' => 'همه', 'audio' => 'صوتی', 'video' => 'تصویری', 'series' => 'سری‌ها', 'continue' => 'در حال یادگیری'] as $value => $label)
                <button type="button" role="tab" data-filter="{{ $value }}" x-on:click="setFilter($el.dataset.filter)" :aria-selected="filter === $el.dataset.filter" aria-controls="media-list"
                        class="px-2.5 py-1 rounded-md text-[10px] font-bold transition-all duration-200 whitespace-nowrap text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] aria-selected:bg-[var(--md-sys-color-primary-container)] aria-selected:text-[var(--md-sys-color-on-primary-container)]">{{ $label }}</button>
            @endforeach
        </div>
    </div>
    <div wire:key="media-recent" x-show="recentItems.length" x-cloak class="sb-fold flex-shrink-0 px-4 pb-3">
        <p class="mb-1.5 text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)]">پخش‌های اخیر</p>
        <div class="flex items-center gap-1.5 overflow-x-auto custom-scrollbar">
            <template x-for="row in recentItems" :key="row.id">
                <button type="button" x-on:click="choose(row.id)" :title="row.title" :aria-selected="selectedId === row.id" x-text="row.title"
                        class="max-w-40 truncate px-2.5 py-1 rounded-md text-[10px] font-bold transition-all duration-200 whitespace-nowrap text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)] aria-selected:bg-[var(--md-sys-color-primary-container)] aria-selected:text-[var(--md-sys-color-on-primary-container)]"></button>
            </template>
        </div>
    </div>
    <div id="media-list" x-effect="rankRows()" class="flex flex-col flex-1 min-h-0 overflow-y-auto py-1 contact-scrollbar" role="listbox" aria-label="رسانه‌ها">
        @foreach($this->library['items'] as $item)
            <div wire:key="media-row-{{ $item['id'] }}" x-data="{ tagOpen: false }" data-id="{{ $item['id'] }}" data-rf="media-{{ $item['id'] }}"
                 x-show="ranks[$el.dataset.id] !== undefined && ranks[$el.dataset.id] < rowLimit" x-bind:style="{ order: ranks[$el.dataset.id] }"
                 x-on:click="choose($el.dataset.id)" x-on:keydown.enter.self.prevent="choose($el.dataset.id)" x-on:keydown.space.self.prevent="choose($el.dataset.id)"
                 role="option" tabindex="0" :aria-selected="selectedId === Number($el.dataset.id)" title="{{ $item['title'] }}"
                 class="sb-row group ripple-effect relative isolate w-full shrink-0 flex items-center gap-3 px-4 py-2.5 text-right transition-all duration-200 cursor-pointer rounded-md hover:bg-[var(--md-sys-color-surface-variant)] aria-selected:bg-[color-mix(in_srgb,var(--md-sys-color-primary-container)_40%,transparent)] aria-selected:border-r-2 aria-selected:border-[var(--md-sys-color-primary)]">
                <x-ui.row-actions :id="$item['id']" scope="media" pin-scope="media-pin" pin-noun="رسانه" :mute="false" :for="(new Media)->forceFill(['id' => $item['id']])"/>
                <div class="relative flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center shadow-sm ring-1 ring-[var(--md-sys-color-outline-variant)]" data-accent="{{ $item['accent'] }}" :style="accentStyle($el.dataset.accent)">
                    @if($item['cover_url'])
                        <img wire:key="media-row-cover" src="{{ $item['cover_url'] }}" alt="" loading="lazy" class="w-full h-full rounded-xl object-cover">
                    @else
                        <span wire:key="media-row-icon" class="material-symbols-rounded text-[20px]">{{ $item['icon'] }}</span>
                    @endif
                    <span x-show="playing && cur.sid === {{ $item['id'] }}" x-cloak class="absolute bottom-0 flex gap-0.5 items-end" aria-label="در حال پخش"><i class="animate-eq origin-bottom w-1 h-3 bg-[var(--md-sys-color-primary)]"></i><i class="animate-eq origin-bottom w-1 h-3 bg-[var(--md-sys-color-primary)]"></i><i class="animate-eq origin-bottom w-1 h-3 bg-[var(--md-sys-color-primary)]"></i></span>
                </div>
                <div class="sb-fade flex-1 min-w-0">
                    <div class="flex items-center gap-1.5 min-w-0">
                        <p class="truncate text-[13px] font-semibold text-[var(--md-sys-color-on-surface)]" data-title="{{ $item['title'] }}" x-html="highlight($el.dataset.title, query)"></p>
                        @if($item['is_new'])
                            <x-ui.badge wire:key="media-row-new" x-cloak x-show="!played({{ $item['id'] }})" variant="info" class="shrink-0">جدید</x-ui.badge>
                        @endif
                        <x-ui.decor.progress-ring :size="24" :stroke="4" x-show="percent(progressOf({{ $item['id'] }})) > 0" x-cloak
                                                  alpine-percent="percent(progressOf({{ $item['id'] }}))" alpine-label="number(percent(progressOf({{ $item['id'] }}))) + '٪'"
                                                  class="shrink-0"/>
                    </div>
                    <p class="text-[11px] truncate text-[var(--md-sys-color-on-surface-variant)]">{{ $item['kind'] === 'video' ? 'تصویری' : 'صوتی' }} · {{ $item['type'] === 'series' ? 'سری' : 'فایل مستقل' }} · {{ convertToPersian((string) count($item['episodes'])) }} جلسه</p>
                    <button type="button" data-id="{{ $item['id'] }}" x-show="episodeHit($el.dataset.id)" x-cloak x-on:click.stop="chooseEpisode($el.dataset.id)"
                            class="block w-full truncate text-right text-[11px] text-[var(--md-sys-color-on-surface-variant)]" x-html="highlight(episodeHit($el.dataset.id)?.title, query)"></button>
                </div>
                <x-ui.buttons.bookmark :id="$item['id']" scope="media" class="shrink-0 self-end" />
            </div>
        @endforeach
        <x-ui.buttons.load-more wire:key="media-list-more" click="rowLimit += 30" text="نمایش بیشتر"
                                x-show="visibleItems.length > rowLimit" x-cloak
                                class="sb-fold order-[9999] mx-auto my-2 px-4 py-2 rounded-xl text-xs font-medium bg-[var(--md-sys-color-surface-variant)]/50 text-[var(--md-sys-color-on-surface-variant)]"/>
        <div wire:key="media-sidebar-empty" x-show="!visibleItems.length" class="contents"><x-ui.empty icon="search_off" variant="search" title="رسانه‌ای پیدا نشد" description="عبارت جست‌وجو یا فیلترها را تغییر دهید." /></div>
        <div class="sb-fold" x-show="onlySaved"><x-ui.saved-empty scope="media" /></div>
    </div>
</aside>
