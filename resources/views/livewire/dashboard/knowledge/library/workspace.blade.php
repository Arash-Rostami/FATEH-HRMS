@use('App\Models\Media')
<main class="flex-1 min-w-0 flex flex-col overflow-y-auto custom-scrollbar [scrollbar-gutter:auto]! bg-[var(--md-sys-color-background)] md:flex" :class="{ 'hidden': !mobileDetail, 'flex': mobileDetail }" aria-label="جزئیات رسانه">
    <div wire:key="media-refresh-error" x-show="refreshError" x-cloak class="p-4" role="alert"><p x-text="refreshError" class="text-sm text-[var(--md-sys-color-error)]"></p><x-ui.buttons.form variant="tonal" x-on:click="refreshLibrary()">تلاش دوباره</x-ui.buttons.form></div>
    <div wire:key="media-workspace-empty" x-show="!selected" class="contents"><x-ui.empty icon="video_library" title="برای شروع، یک رسانه انتخاب کنید" description="از فهرست، یک فایل صوتی، ویدیو یا مجموعهٔ آموزشی را باز کنید." :fill="true" /></div>
    @foreach($this->library['items'] as $item)
        <template wire:key="media-workspace-{{ $item['id'] }}" data-id="{{ $item['id'] }}" x-if="selectedId === Number($el.dataset.id)">
        <section>
            <header class="relative flex flex-wrap items-center md:items-end gap-x-3 gap-y-2 md:gap-7 px-3 py-2 md:p-7 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[var(--md-sys-color-surface)] md:border-b-0 md:bg-gradient-to-b md:from-[color-mix(in_srgb,var(--md-sys-color-primary-container)_55%,transparent)] md:to-[var(--md-sys-color-background)]"
                    :class="headerDeclutter ? 'h-0! py-0! overflow-hidden! pointer-events-none! border-b-0! shadow-none!' : ''">
                <x-ui.buttons.form variant="ghost" size="icon" x-on:click="backToList()" class="md:hidden" aria-label="بازگشت به کتابخانه"><span class="material-symbols-rounded">arrow_forward</span></x-ui.buttons.form>
                <div class="size-12 md:w-44 md:h-44 rounded-xl md:rounded-2xl shrink-0 flex items-center justify-center overflow-hidden" x-show="!headerDeclutter" data-accent="{{ $item['accent'] }}" :style="accentStyle($el.dataset.accent)">
                    @if($item['cover_url'])
                        <img wire:key="media-hero-cover" src="{{ $item['cover_url'] }}" alt="" loading="lazy" class="w-full h-full object-cover">
                    @else
                        <span wire:key="media-hero-icon" class="material-symbols-rounded text-2xl md:text-7xl">{{ $item['icon'] }}</span>
                    @endif
                </div>
                <div class="flex-1 min-w-0" x-show="!headerDeclutter">
                    <div class="flex gap-2 mb-1 md:mb-3 max-md:hidden"><x-ui.badge>{{ $item['kind'] === 'video' ? 'تصویری' : 'صوتی' }}</x-ui.badge><x-ui.badge>{{ $item['type'] === 'series' ? 'سری' : 'فایل مستقل' }}</x-ui.badge>@if($item['is_new'])<x-ui.badge wire:key="media-hero-new" x-cloak x-show="!played({{ $item['id'] }})" variant="info">جدید</x-ui.badge>@endif</div>
                    <h2 class="text-base md:text-4xl font-extrabold leading-6 md:leading-tight line-clamp-1 md:line-clamp-none text-[var(--md-sys-color-on-surface)] md:mb-2">{{ $item['title'] }}</h2>
                    <div x-show="{{ $item['description_html'] !== '' ? 'true' : 'false' }} && !aboutEpisode?.description_html" class="text-[13px] leading-6 text-[var(--md-sys-color-on-surface-variant)] mb-3 prose rich-colors max-w-none max-md:hidden">{!! $item['description_html'] !!}</div>
                    <div wire:key="media-episode-about" x-cloak x-show="aboutEpisode?.description_html" class="text-[13px] leading-6 text-[var(--md-sys-color-on-surface-variant)] mb-3"><b class="block text-[var(--md-sys-color-on-surface)]" x-text="aboutEpisode?.title"></b><div class="prose rich-colors max-w-none max-md:line-clamp-2" x-html="aboutEpisode?.description_html"></div></div>
                    <p class="flex flex-wrap gap-x-3 gap-y-1 text-[11px] md:text-xs text-[var(--md-sys-color-on-surface-variant)]"><b>{{ $item['unit'] }}</b><span>{{ $item['department'] }}</span><span>{{ $item['date_label'] }}</span><span x-text="time({{ $item['total_duration'] }})"></span></p>
                </div>
                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto md:ms-auto md:flex-col md:items-end md:justify-between md:self-stretch">
                    <div class="chat-scale flex items-center gap-1.5 max-md:ms-auto">
                        <x-dashboard.reminder-trigger :for="(new Media)->forceFill(['id' => $item['id']])" key-suffix="hero"/>
                        <x-ui.buttons.form variant="primary" x-on:click="heroPlay()" x-bind:disabled="!selected?.episodes.some(ep => ep.file_url)" class="!h-8 !rounded-xl !px-3 text-[12px]"><span class="material-symbols-rounded text-[18px]" x-text="playing && cur.sid === selectedId ? 'pause' : 'play_arrow'"></span><span x-text="heroLabel()"></span></x-ui.buttons.form>
                        <x-ui.buttons.bookmark :id="$item['id']" scope="media" class="!h-8 !w-8 !rounded-xl !p-0 bg-[var(--md-sys-color-surface-variant)]" />
                        <x-ui.buttons.form variant="none" x-on:click="copyMoment()" title="کپی پیوند این لحظه" aria-label="کپی پیوند این لحظه" class="flex h-8 w-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95"><span class="material-symbols-rounded text-base">share</span></x-ui.buttons.form>
                        <x-ui.modals.maximize-button />
                        <x-ui.buttons.form variant="none" x-on:click="toggleHeaderDeclutter()" x-bind:aria-pressed="headerDeclutter" x-bind:title="headerDeclutter ? 'نمایش سربرگ' : 'پنهان کردن سربرگ'" x-bind:aria-label="headerDeclutter ? 'نمایش سربرگ' : 'پنهان کردن سربرگ'" class="max-md:hidden flex h-8 w-8 items-center justify-center rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 ease-out hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] active:scale-95"><span class="material-symbols-rounded text-base rotate-90" x-text="headerDeclutter ? 'unfold_more' : 'unfold_less'"></span></x-ui.buttons.form>
                    </div>
                <div class="hidden md:flex items-center gap-3">
                    <x-ui.decor.progress-ring :size="56" :stroke="6" alpine-percent="percent(progressOf(selectedId))" alpine-label="number(percent(progressOf(selectedId))) + '٪'"/>
                    <div class="text-xs text-[var(--md-sys-color-on-surface-variant)]"><b class="block text-[var(--md-sys-color-on-surface)]" x-text="number(doneCount()) + ' از ' + number(selected?.episodes.length) + ' جلسه تمام شده'"></b><span x-text="number(percent(progressOf(selectedId))) + '٪ پیشرفت'"></span></div>
                </div>
                <div class="flex items-center gap-2 md:hidden">
                    <x-ui.decor.progress-ring :size="32" :stroke="5" label="" alpine-percent="percent(progressOf(selectedId))"/>
                    <div class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]"><b class="block text-[var(--md-sys-color-on-surface)]" x-text="number(doneCount()) + ' از ' + number(selected?.episodes.length) + ' جلسه تمام شده'"></b><span x-text="number(percent(progressOf(selectedId))) + '٪ پیشرفت'"></span></div>
                </div>
                </div>
            </header>
            <div wire:key="media-video-stage" x-show="cur?.sid === selectedId && isVideo" x-cloak class="mx-2 md:mx-7 mb-2 md:mb-4"><x-dashboard.media.player part="stage" /></div>
            <div class="px-2 md:px-7 sticky top-0 z-10 bg-[var(--md-sys-color-background)] pt-2 flex items-start gap-2">
                <button wire:key="media-declutter-restore" type="button" x-on:click="toggleHeaderDeclutter()" x-show="headerDeclutter" x-cloak aria-label="نمایش سربرگ" title="نمایش سربرگ"
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_40%,transparent)] transition-all duration-200 ease-out active:scale-95"><span class="material-symbols-rounded text-base rotate-90">unfold_more</span></button>
                <x-ui.buttons.tab-selector :hasA11y="true" :tabs="[
                    ['id' => 'episodes', 'label' => 'جلسه‌ها', 'icon' => 'list', 'alpine' => 'showEpisodes()', 'activeExpr' => 'tab === \'episodes\'', 'countExpr' => 'selected?.episodes.length'],
                    ['id' => 'transcript', 'label' => 'متن جلسه', 'icon' => 'subtitles', 'alpine' => 'showTranscript()', 'activeExpr' => 'tab === \'transcript\'', 'condition' => collect($item['episodes'])->contains(fn ($ep) => $ep['transcript_url'] || $ep['chapters_url'])],
                    ['id' => 'notes', 'label' => 'یادداشت‌ها', 'icon' => 'edit_note', 'alpine' => 'showNotes()', 'activeExpr' => 'tab === \'notes\'', 'countExpr' => 'currentNotes.length', 'condition' => collect($item['episodes'])->contains(fn ($ep) => (bool) $ep['file_url'])],
                    ['id' => 'materials', 'label' => 'گزارش‌های مرتبط', 'icon' => 'folder_open', 'alpine' => 'showMaterials()', 'activeExpr' => 'tab === \'materials\'', 'countExpr' => 'selected?.materials.length', 'condition' => count($item['materials']) > 0],
                ]" />
            </div>
            <div class="px-2 md:px-5 pb-2 md:pb-7">
                <div wire:key="media-episodes" x-show="tab === 'episodes'">
                    @if(count($item['episodes']) > 0)
                    <div wire:key="media-episodes-table" class="contents">
                    @php($th = 'whitespace-nowrap border-b border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-low)] text-[var(--md-sys-color-on-surface-variant)] uppercase font-medium text-xs px-4 md:px-6 py-3 text-start')
                    <x-ui.table cardClass="relative rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm md:min-h-[300px] md:overflow-hidden" wrapClass="w-full md:min-h-[300px] md:overflow-x-auto" tableClass="media-episode-table min-w-full w-full border-separate border-spacing-0 text-sm text-right">
                        <x-slot:head><tr>
                            <th scope="col" class="{{ $th }} first:rounded-tr-2xl">جلسه</th>
                            <th scope="col" class="{{ $th }}">عنوان</th>
                            <th scope="col" data-col="type" class="{{ $th }} hidden md:table-cell">نوع</th>
                            <th scope="col" data-col="duration" class="{{ $th }} hidden md:table-cell">مدت</th>
                            <th scope="col" data-col="date" class="{{ $th }} hidden lg:table-cell">تاریخ انتشار</th>
                            <th scope="col" data-col="extras" class="{{ $th }} hidden lg:table-cell">متن و بخش‌ها</th>
                            <th scope="col" data-col="notes" class="{{ $th }} hidden md:table-cell">یادداشت</th>
                            <th scope="col" data-col="status" class="{{ $th }} hidden md:table-cell">وضعیت</th>
                            <th scope="col" class="{{ $th }} text-center last:rounded-tl-2xl">
                                <x-ui.table.tools :maximize="false" wire:key="media-episode-table-tools">
                                    <x-ui.table.density-toggle/>
                                    <x-ui.table.col-toggle scope="media" :columns="['type' => 'نوع', 'duration' => 'مدت', 'date' => 'تاریخ انتشار', 'extras' => 'متن و بخش‌ها', 'notes' => 'یادداشت', 'status' => 'وضعیت']"/>
                                </x-ui.table.tools>
                            </th>
                        </tr></x-slot:head>
                        @foreach($item['episodes'] as $index => $episode)
                            <tr wire:key="media-episode-{{ $episode['id'] }}" class="hover:bg-[var(--md-sys-color-surface-variant)]">
                                <td class="px-4 md:px-6 py-3 text-[var(--md-sys-color-on-surface-variant)]">
                                    <span x-show="glyph({{ $item['id'] }}, {{ $index }}) === 'num'">{{ convertToPersian((string) ($index + 1)) }}</span>
                                    <span x-show="glyph({{ $item['id'] }}, {{ $index }}) === 'eq'" x-cloak class="flex gap-0.5 items-end h-4" aria-label="در حال پخش"><i class="animate-eq origin-bottom w-1 h-3 bg-[var(--md-sys-color-primary)]"></i><i class="animate-eq origin-bottom w-1 h-3 bg-[var(--md-sys-color-primary)]"></i><i class="animate-eq origin-bottom w-1 h-3 bg-[var(--md-sys-color-primary)]"></i></span>
                                    <span x-show="glyph({{ $item['id'] }}, {{ $index }}) === 'done'" x-cloak class="material-symbols-rounded text-[20px] font-fill text-[var(--md-sys-color-success)]" aria-label="تمام‌شده">check_circle</span>
                                    <span x-show="glyph({{ $item['id'] }}, {{ $index }}) === 'lock'" x-cloak class="material-symbols-rounded text-[18px]" aria-label="در انتظار انتشار">lock</span>
                                </td>
                                <td class="px-4 md:px-6 py-3">
                                    <x-ui.buttons.form variant="ghost" data-index="{{ $index }}" x-on:click="startEpisode(Number($el.dataset.index))" x-bind:disabled="!selected?.episodes[{{ $index }}]?.file_url && !isLocked(selected?.episodes[{{ $index }}])" class="!h-auto !min-h-10 !justify-start !px-0 text-right">
                                        <span class="material-symbols-rounded text-[20px]">{{ $episode['icon'] }}</span><span><b class="block text-sm">{{ $episode['title'] }}</b><span class="block text-xs font-normal text-[var(--md-sys-color-on-surface-variant)]">{{ $episode['topic'] }}</span><span class="block text-[10px] md:hidden text-[var(--md-sys-color-on-surface-variant)]"><span x-text="time({{ $episode['duration'] ?? 0 }})"></span>@if($episode['date_label'])<span> · {{ $episode['date_label'] }}</span>@endif</span></span>
                                    </x-ui.buttons.form>
                                    @if($episode['unlock_label'])
                                        <p wire:key="media-episode-lock" class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]"><span class="material-symbols-rounded text-[14px]">lock</span>انتشار در {{ $episode['unlock_label'] }}</p>
                                    @endif
                                </td>
                                <td data-col="type" class="hidden md:table-cell px-4 md:px-6 py-3 text-xs whitespace-nowrap"><span class="inline-flex items-center gap-1"><span class="material-symbols-rounded text-[16px]">{{ ($episode['kind'] ?? $item['kind']) === 'video' ? 'smart_display' : 'headphones' }}</span>{{ ($episode['kind'] ?? $item['kind']) === 'video' ? 'تصویری' : 'صوتی' }}@if(! empty($episode['ext']))<span wire:key="media-episode-ext" class="text-[var(--md-sys-color-on-surface-variant)]" dir="ltr">{{ $episode['ext'] }}</span>@endif</span></td>
                                <td data-col="duration" class="hidden md:table-cell px-4 md:px-6 py-3 text-xs tabular-nums" x-text="time({{ $episode['duration'] ?? 0 }})"></td>
                                <td data-col="date" class="hidden lg:table-cell px-4 md:px-6 py-3 text-xs whitespace-nowrap">{{ $episode['date_label'] ?? '' }}</td>
                                <td data-col="extras" class="hidden lg:table-cell px-4 md:px-6 py-3"><span class="inline-flex items-center gap-1 text-[var(--md-sys-color-on-surface-variant)]">@if($episode['transcript_url'])<span wire:key="media-episode-transcript" class="material-symbols-rounded text-[18px]" title="متن جلسه" aria-label="متن جلسه">subtitles</span>@endif @if($episode['chapters_url'])<span wire:key="media-episode-chapters" class="material-symbols-rounded text-[18px]" title="بخش‌ها" aria-label="بخش‌ها">list_alt</span>@endif @if(! $episode['transcript_url'] && ! $episode['chapters_url'])<span wire:key="media-episode-extras-none" aria-hidden="true">—</span>@endif</span></td>
                                <td data-col="notes" class="hidden md:table-cell px-4 md:px-6 py-3 text-xs tabular-nums" x-text="number((libraryNotes[{{ $episode['id'] }}] ?? []).length)"></td>
                                <td data-col="status" class="hidden md:table-cell px-4 md:px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-ui.decor.status-pill title="تمام‌شده" color="tertiary" x-show="episodeState({{ $item['id'] }}, {{ $index }}) === 'done'" x-cloak/>
                                        <x-ui.decor.status-pill title="در حال پخش" color="primary" x-show="episodeState({{ $item['id'] }}, {{ $index }}) === 'current'" x-cloak/>
                                        <x-ui.decor.status-pill title="در انتظار انتشار" x-show="episodeState({{ $item['id'] }}, {{ $index }}) === 'locked'" x-cloak/>
                                        <x-ui.decor.status-pill title="آمادهٔ پخش" x-show="!['done', 'current', 'locked'].includes(episodeState({{ $item['id'] }}, {{ $index }}))" x-cloak/>
                                    </div>
                                    <div class="mt-2"><x-ui.decor.progress-ring :size="30" :stroke="4" alpine-percent="percent(episodeProgress({{ $item['id'] }}, {{ $index }}))" alpine-label="number(percent(episodeProgress({{ $item['id'] }}, {{ $index }}))) + '٪'"/></div>
                                </td>
                                <td class="px-4 md:px-6 py-3 text-center whitespace-nowrap">
                                    <x-ui.buttons.form variant="ghost" size="icon" x-on:click="markDone({{ $item['id'] }}, {{ $index }})" x-show="canMarkDone({{ $item['id'] }}, {{ $index }})" x-cloak title="ثبت پایان جلسه" aria-label="ثبت پایان جلسه"><span class="material-symbols-rounded">done_all</span></x-ui.buttons.form>
                                    @if($episode['file_url'])
                                        <div wire:key="media-episode-download"><x-ui.buttons.form href="{{ $episode['file_url'] }}" variant="ghost" size="icon" download aria-label="دانلود جلسه"><span class="material-symbols-rounded">download</span></x-ui.buttons.form></div>
                                    @else
                                        <span wire:key="media-episode-no-download" class="material-symbols-rounded text-[var(--md-sys-color-on-surface-variant)]" aria-label="دانلود در دسترس نیست">lock</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                    </div>
                    @else
                        <div wire:key="media-episodes-empty" class="contents"><x-ui.empty icon="playlist_play" title="هنوز جلسه‌ای در این سری نیست" /></div>
                    @endif
                </div>
                <div wire:key="media-transcript" x-show="tab === 'transcript'" x-cloak>
                    <div class="relative mb-3 group">
                        <span class="material-symbols-rounded absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[var(--md-sys-color-on-surface-variant)] group-focus-within:text-[var(--md-sys-color-primary)] transition-colors z-10">search</span>
                        <input type="text" id="media-transcript-search" x-model="transcriptQuery" aria-label="جست‌وجوی متن جلسه" placeholder="جست‌وجو در متن جلسه…" autocomplete="off" spellcheck="false"
                               class="md3-input peer w-full h-10 leading-[40px] rounded-xl text-sm outline-none transition-all focus:ring-2 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface)] border border-[var(--md-sys-color-outline-variant)]/50 pr-10 pl-4 placeholder-transparent">
                        <x-ui.modals.close-button close="transcriptQuery = ''" size="sm" class="absolute left-1 top-1.5" x-show="transcriptQuery" x-cloak />
                        <label for="media-transcript-search" class="md3-label absolute top-1/2 -translate-y-1/2 pointer-events-none peer-placeholder-shown:right-10 peer-focus:right-4 right-4 transition-all duration-200">جست‌وجو در متن جلسه…</label>
                    </div>
                    <div x-show="tracksBusy"><x-ui.loaders.skeleton.bar /><x-ui.loaders.skeleton.bar /></div>
                    <div x-show="tracksError" role="alert" class="text-sm text-[var(--md-sys-color-error)]" x-text="tracksError"></div>
                    <div wire:key="media-chapters" x-show="chapters.length" x-cloak class="mb-4">
                        <div class="flex items-center gap-1.5">
                            <button type="button" aria-label="بخش‌های قبلی" x-on:click="$refs.chapterStrip.scrollBy({ left: $refs.chapterStrip.clientWidth * 0.7, behavior: 'smooth' })"
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 hover:!bg-[var(--md-sys-color-primary)] hover:!text-[var(--md-sys-color-on-primary)] active:scale-90"><span class="material-symbols-rounded text-[18px]">chevron_right</span></button>
                            <div x-ref="chapterStrip" role="list" aria-label="بخش‌های جلسه" class="flex min-w-0 flex-1 items-stretch gap-2 overflow-x-auto scroll-smooth [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                                <template x-for="chapter in chapters" :key="chapter.start + ':' + chapter.end">
                                    <button type="button" role="listitem" x-on:click="seekContext(chapter.start)" x-bind:aria-current="activeCue(chapter)" x-bind:title="chapter.text"
                                            class="w-36 shrink-0 rounded-xl px-3 py-2 text-right transition-colors duration-200 md:w-40"
                                            x-bind:class="activeCue(chapter) ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]' : 'bg-[var(--md-sys-color-surface-container-low)] hover:bg-[var(--md-sys-color-surface-variant)]'">
                                        <span class="block text-[10px] tabular-nums opacity-70" x-text="time(chapter.start)"></span>
                                        <span class="line-clamp-2 block text-[12px] font-semibold leading-5" x-text="chapter.text"></span>
                                    </button>
                                </template>
                            </div>
                            <button type="button" aria-label="بخش‌های بعدی" x-on:click="$refs.chapterStrip.scrollBy({ left: -$refs.chapterStrip.clientWidth * 0.7, behavior: 'smooth' })"
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] transition-all duration-200 hover:!bg-[var(--md-sys-color-primary)] hover:!text-[var(--md-sys-color-on-primary)] active:scale-90"><span class="material-symbols-rounded text-[18px]">chevron_left</span></button>
                        </div>
                    </div>
                    <div wire:key="media-cues" x-show="visibleCues.length" x-cloak class="max-h-[26rem] snap-y overflow-y-auto rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container-low)] p-1 custom-scrollbar">
                        <template x-for="cue in visibleCues" :key="cue.start + ':' + cue.end">
                            <button type="button" x-on:click="seekContext(cue.start)" x-bind:aria-current="activeCue(cue)"
                                    class="flex w-full snap-start items-start gap-3 rounded-xl px-3 py-2.5 text-right transition-colors hover:bg-[var(--md-sys-color-surface-variant)]"
                                    x-bind:class="activeCue(cue) && '!bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]'">
                                <span class="mt-1 shrink-0 rounded-lg bg-[var(--md-sys-color-surface-variant)] px-2 py-0.5 text-[11px] font-bold tabular-nums text-[var(--md-sys-color-on-surface-variant)]" x-text="time(cue.start)"></span>
                                <span class="text-[13px] leading-7" x-text="cue.text"></span>
                            </button>
                        </template>
                    </div>
                    <div wire:key="media-transcript-empty" x-show="!tracksBusy && !tracksError && !visibleCues.length" class="contents"><x-ui.empty icon="subtitles_off" title="متن این جلسه در دسترس نیست" /></div>
                </div>
                <div wire:key="media-notes" x-show="tab === 'notes'" x-cloak class="space-y-3 px-2">
                    <div class="relative" x-data="{ fullscreen: false, get value() { return noteBody }, set value(v) { noteBody = String(v).slice(0, 500) } }">
                        <x-ui.forms.textarea label="یادداشت برای این لحظه" name="media-note" x-model="noteBody" maxlength="500" :rows="3" class="pl-10" />
                        <x-ui.forms.maximize-trigger class="top-3 left-0"/>
                        <x-ui.forms.maximize-overlay icon="edit_note" title="یادداشت برای این لحظه"/>
                    </div>
                    <div class="flex flex-wrap gap-2"><x-ui.buttons.form variant="primary" x-on:click="addNote()" x-bind:disabled="noteBusy || !noteBody.trim()"><span class="material-symbols-rounded">bookmark_add</span><span x-text="noteBusy ? 'در حال ذخیره…' : 'ذخیرهٔ یادداشت'"></span></x-ui.buttons.form><x-ui.buttons.form variant="tonal" x-on:click="copyMoment()">کپی پیوند این لحظه</x-ui.buttons.form><x-ui.buttons.form variant="tonal" x-on:click="copyNotes()" x-show="currentNotes.length" x-cloak><span class="material-symbols-rounded">content_copy</span>کپی یادداشت‌ها</x-ui.buttons.form></div>
                    <template x-for="note in currentNotes" :key="note.id"><div class="flex items-start gap-3 p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-low)]"><x-ui.buttons.form variant="ghost" x-on:click="seekContext(note.position)"><span x-text="time(note.position)"></span></x-ui.buttons.form><p class="flex-1 text-[13px] leading-6 whitespace-pre-wrap break-words" x-text="note.body"></p><x-ui.buttons.form variant="ghost" size="icon" x-on:click="deleteNote(note.id)" x-bind:disabled="noteBusy" aria-label="حذف یادداشت"><span class="material-symbols-rounded">delete</span></x-ui.buttons.form></div></template>
                </div>
                <div wire:key="media-materials" x-show="tab === 'materials'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3">
                    @foreach($item['materials'] as $material)
                        <a wire:key="media-material-{{ $material['id'] }}" href="{{ $material['url'] }}" target="_blank" rel="noopener" class="flex items-center gap-3 p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm hover:shadow-md transition-all"><span class="material-symbols-rounded text-[24px] text-[var(--md-sys-color-primary)]">description</span><span><b class="block text-[13px]">{{ $material['title'] }}</b><span class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]">{{ $material['size_label'] }}</span></span><span class="material-symbols-rounded ms-auto">download</span></a>
                    @endforeach
                </div>
            </div>
        </section>
        </template>
    @endforeach
</main>
