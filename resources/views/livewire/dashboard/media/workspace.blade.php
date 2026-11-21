<main class="flex-1 min-w-0 flex flex-col overflow-y-auto custom-scrollbar bg-[var(--md-sys-color-background)] md:flex" :class="{ 'hidden': !mobileDetail, 'flex': mobileDetail }" aria-label="فضای رسانه">
    <div wire:key="media-refresh-error" x-show="refreshError" x-cloak class="p-4" role="alert"><p x-text="refreshError" class="text-sm text-[var(--md-sys-color-error)]"></p><x-ui.buttons.form variant="tonal" x-on:click="refreshLibrary()">تلاش دوباره</x-ui.buttons.form></div>
    <div wire:key="media-workspace-empty" x-show="!selected" class="contents"><x-ui.empty icon="video_library" title="رسانه‌ای انتخاب کنید" description="یک سری، فایل صوتی یا ویدیو را از کتابخانه باز کنید." :fill="true" /></div>
    @foreach($this->library['items'] as $item)
        <template wire:key="media-workspace-{{ $item['id'] }}" data-id="{{ $item['id'] }}" x-if="selectedId === Number($el.dataset.id)">
        <section>
            <header class="relative flex flex-wrap items-end gap-4 md:gap-7 p-4 md:p-7 bg-gradient-to-b from-[var(--md-sys-color-primary-container)] to-[var(--md-sys-color-background)]">
                <x-ui.buttons.form variant="ghost" size="icon" x-on:click="backToList()" class="md:hidden" aria-label="بازگشت به کتابخانه"><span class="material-symbols-rounded">arrow_forward</span></x-ui.buttons.form>
                <div class="w-28 h-28 md:w-[184px] md:h-[184px] rounded-2xl shrink-0 flex items-center justify-center overflow-hidden" data-accent="{{ $item['accent'] }}" :style="accentStyle($el.dataset.accent)">
                    @if($item['cover_url'])
                        <img wire:key="media-hero-cover" src="{{ $item['cover_url'] }}" alt="" loading="lazy" class="w-full h-full object-cover">
                    @else
                        <span wire:key="media-hero-icon" class="material-symbols-rounded text-[52px] md:text-[76px]">{{ $item['icon'] }}</span>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex gap-2 mb-3"><x-ui.badge>{{ $item['kind'] === 'video' ? 'تصویری' : 'صوتی' }}</x-ui.badge><x-ui.badge>{{ $item['type'] === 'series' ? 'سری' : 'تک‌قسمت' }}</x-ui.badge></div>
                    <h2 class="text-2xl md:text-[34px] font-extrabold text-[var(--md-sys-color-on-surface)] mb-2">{{ $item['title'] }}</h2>
                    <div class="text-[13px] leading-6 text-[var(--md-sys-color-on-surface-variant)] mb-3 prose rich-colors max-w-none">{!! $item['description_html'] !!}</div>
                    <p class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-[var(--md-sys-color-on-surface-variant)]"><b>{{ $item['unit'] }}</b><span>{{ $item['department'] }}</span><span>{{ $item['date_label'] }}</span><span x-text="time({{ $item['total_duration'] }})"></span></p>
                </div>
            </header>
            <div class="flex flex-wrap gap-2 items-center px-4 md:px-7 py-4">
                <x-ui.buttons.form variant="primary" x-on:click="heroPlay()" x-bind:disabled="!selected?.episodes.some(ep => ep.file_url)"><span class="material-symbols-rounded" x-text="playing && cur.sid === selectedId ? 'pause' : 'play_arrow'"></span><span x-text="heroLabel()"></span></x-ui.buttons.form>
                <x-ui.buttons.bookmark :id="$item['id']" scope="media" class="w-10 h-10" />
                <x-ui.buttons.form variant="ghost" size="icon" x-on:click="copyMoment()" title="پیوند این لحظه" aria-label="پیوند این لحظه"><span class="material-symbols-rounded">share</span></x-ui.buttons.form>
                <x-ui.buttons.form variant="ghost" size="icon" x-on:click="toggleTheatre()" x-bind:aria-pressed="theatre" title="حالت سینمایی" aria-label="حالت سینمایی"><span class="material-symbols-rounded">theaters</span></x-ui.buttons.form>
                <x-ui.modals.maximize-button size="lg" />
                <div class="hidden md:flex items-center gap-3 ms-auto">
                    <div class="relative inline-flex items-center justify-center shrink-0 w-14 h-14">
                        <svg width="56" height="56" viewBox="0 0 56 56" class="-rotate-90">
                            <circle cx="28" cy="28" r="25" fill="none" stroke="var(--md-sys-color-surface-variant)" stroke-width="6"/>
                            <circle cx="28" cy="28" r="25" fill="none" stroke="var(--md-sys-color-primary)" stroke-width="6" stroke-linecap="round" class="transition-all duration-500"
                                    x-bind:stroke-dasharray="2 * Math.PI * 25" x-bind:stroke-dashoffset="2 * Math.PI * 25 * (1 - percent(progressOf(selectedId)) / 100)"/>
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold tabular-nums text-[var(--md-sys-color-primary)]" x-text="number(percent(progressOf(selectedId))) + '٪'"></span>
                    </div>
                    <div class="text-xs text-[var(--md-sys-color-on-surface-variant)]"><b class="block text-[var(--md-sys-color-on-surface)]" x-text="number(doneCount()) + ' از ' + number(selected?.episodes.length) + ' جلسه'"></b><span x-text="number(percent(progressOf(selectedId))) + '٪ پیشرفت'"></span></div>
                </div>
            </div>
            @if($item['kind'] === 'video')
                <div wire:key="media-video-stage" class="mx-4 md:mx-7 mb-4"><x-dashboard.media.player part="stage" /></div>
            @endif
            <div class="px-4 md:px-7 sticky top-0 z-10 bg-[var(--md-sys-color-background)] pt-2">
                <x-ui.buttons.tab-selector :hasA11y="true" :tabs="[
                    ['id' => 'episodes', 'label' => 'جلسات', 'icon' => 'list', 'alpine' => 'showEpisodes()', 'activeExpr' => 'tab === \'episodes\'', 'countExpr' => 'selected?.episodes.length'],
                    ['id' => 'transcript', 'label' => 'رونوشت', 'icon' => 'subtitles', 'alpine' => 'showTranscript()', 'activeExpr' => 'tab === \'transcript\'', 'condition' => collect($item['episodes'])->contains(fn ($ep) => $ep['transcript_url'] || $ep['chapters_url'])],
                    ['id' => 'notes', 'label' => 'یادداشت‌ها', 'icon' => 'edit_note', 'alpine' => 'showNotes()', 'activeExpr' => 'tab === \'notes\'', 'countExpr' => 'currentNotes.length', 'condition' => collect($item['episodes'])->contains(fn ($ep) => (bool) $ep['file_url'])],
                    ['id' => 'materials', 'label' => 'مواد همراه', 'icon' => 'folder_open', 'alpine' => 'showMaterials()', 'activeExpr' => 'tab === \'materials\'', 'countExpr' => 'selected?.materials.length', 'condition' => count($item['materials']) > 0],
                ]" />
            </div>
            <div class="px-2 md:px-5 pb-7">
                <div wire:key="media-episodes" x-show="tab === 'episodes'">
                    <x-ui.table tableClass="min-w-full w-full border-separate border-spacing-0 text-sm text-right">
                        <x-slot:head><tr><th class="px-3 py-3">جلسه</th><th class="px-3 py-3">عنوان</th><th class="hidden md:table-cell px-3 py-3">مدت</th><th class="hidden md:table-cell px-3 py-3">وضعیت</th><th class="px-3 py-3"><span class="sr-only">دانلود</span></th></tr></x-slot:head>
                        @foreach($item['episodes'] as $index => $episode)
                            <tr wire:key="media-episode-{{ $episode['id'] }}" class="hover:bg-[var(--md-sys-color-surface-variant)]">
                                <td class="px-3 py-3 text-[var(--md-sys-color-on-surface-variant)]">
                                    <span x-show="glyph({{ $item['id'] }}, {{ $index }}) === 'num'">{{ convertToPersian((string) ($index + 1)) }}</span>
                                    <span x-show="glyph({{ $item['id'] }}, {{ $index }}) === 'eq'" x-cloak class="flex gap-0.5 items-end h-4" aria-label="در حال پخش"><i class="animate-eq origin-bottom w-1 h-3 bg-[var(--md-sys-color-primary)]"></i><i class="animate-eq origin-bottom w-1 h-3 bg-[var(--md-sys-color-primary)]"></i><i class="animate-eq origin-bottom w-1 h-3 bg-[var(--md-sys-color-primary)]"></i></span>
                                    <span x-show="glyph({{ $item['id'] }}, {{ $index }}) === 'done'" x-cloak class="material-symbols-rounded text-[20px] font-fill text-[var(--md-sys-color-success)]" aria-label="تمام‌شده">check_circle</span>
                                    <span x-show="glyph({{ $item['id'] }}, {{ $index }}) === 'lock'" x-cloak class="material-symbols-rounded text-[18px]" aria-label="قفل‌شده">lock</span>
                                </td>
                                <td class="px-3 py-3">
                                    <x-ui.buttons.form variant="ghost" data-index="{{ $index }}" x-on:click="startEpisode(Number($el.dataset.index))" x-bind:disabled="!selected?.episodes[{{ $index }}]?.file_url && !isLocked(selected?.episodes[{{ $index }}])" class="!h-auto !min-h-10 !justify-start !px-0 text-right">
                                        <span class="material-symbols-rounded text-[20px]">{{ $episode['icon'] }}</span><span><b class="block text-sm">{{ $episode['title'] }}</b><span class="block text-xs font-normal text-[var(--md-sys-color-on-surface-variant)]">{{ $episode['topic'] }}</span></span>
                                    </x-ui.buttons.form>
                                    @if($episode['unlock_label'])
                                        <p wire:key="media-episode-lock" class="text-[11px] text-[var(--md-sys-color-on-surface-variant)]"><span class="material-symbols-rounded text-[14px]">lock</span>انتشار: {{ $episode['unlock_label'] }}</p>
                                    @endif
                                </td>
                                <td class="hidden md:table-cell px-3 py-3 text-xs" x-text="time({{ $episode['duration'] ?? 0 }})"></td>
                                <td class="hidden md:table-cell px-3 py-3 text-xs"><span x-text="episodeState({{ $item['id'] }}, {{ $index }}) === 'done' ? 'تمام‌شده' : (episodeState({{ $item['id'] }}, {{ $index }}) === 'locked' ? 'قفل‌شده' : (episodeState({{ $item['id'] }}, {{ $index }}) === 'current' ? 'در حال پخش' : 'آمادهٔ پخش'))"></span><div class="mt-2 h-1.5 w-full rounded-full bg-[var(--md-sys-color-surface-variant)] overflow-hidden" role="progressbar" aria-label="پیشرفت" aria-valuemin="0" aria-valuemax="100" x-bind:aria-valuenow="percent(episodeProgress({{ $item['id'] }}, {{ $index }}))"><div class="h-full rounded-full transition-all duration-500 bg-[var(--md-sys-color-primary)]" x-bind:style="{ width: percent(episodeProgress({{ $item['id'] }}, {{ $index }})) + '%' }"></div></div></td>
                                <td class="px-3 py-3">
                                    @if($episode['download_url'])
                                        <div wire:key="media-episode-download"><x-ui.buttons.form href="{{ $episode['download_url'] }}" variant="ghost" size="icon" download aria-label="دانلود جلسه"><span class="material-symbols-rounded">download</span></x-ui.buttons.form></div>
                                    @else
                                        <span wire:key="media-episode-no-download" class="material-symbols-rounded text-[var(--md-sys-color-on-surface-variant)]" aria-label="هنوز منتشر نشده">lock</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                    @if(count($item['episodes']) === 0)
                        <div wire:key="media-episodes-empty" class="contents"><x-ui.empty icon="playlist_play" title="هنوز جلسه‌ای منتشر نشده" /></div>
                    @endif
                </div>
                <div wire:key="media-transcript" x-show="tab === 'transcript'" x-cloak>
                    <div class="relative mb-3"><span class="material-symbols-rounded absolute right-3 top-2.5 text-[20px]">search</span><input type="search" x-model="transcriptQuery" aria-label="جستجوی رونوشت" placeholder="جستجو در رونوشت…" class="w-full h-10 rounded-xl text-sm bg-[var(--md-sys-color-surface-variant)] border border-[var(--md-sys-color-outline-variant)]/50 pr-10 pl-4"></div>
                    <div x-show="tracksBusy"><x-ui.loaders.skeleton.bar /><x-ui.loaders.skeleton.bar /></div>
                    <div x-show="tracksError" role="alert" class="text-sm text-[var(--md-sys-color-error)]" x-text="tracksError"></div>
                    <div class="flex flex-wrap gap-2 mb-3"><template x-for="chapter in chapters" :key="chapter.start"><x-ui.buttons.form variant="tonal" x-on:click="seekContext(chapter.start)"><span x-text="chapter.text"></span></x-ui.buttons.form></template></div>
                    <template x-for="cue in visibleCues" :key="cue.start"><x-ui.buttons.form variant="ghost" x-on:click="seekContext(cue.start)" class="!w-full !h-auto !justify-start !items-start !py-3 text-right" x-bind:aria-current="activeCue(cue)" x-bind:class="activeCue(cue) && 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]'"><span class="text-xs shrink-0" x-text="time(cue.start)"></span><span class="leading-7" x-text="cue.text"></span></x-ui.buttons.form></template>
                    <div wire:key="media-transcript-empty" x-show="!tracksBusy && !tracksError && !visibleCues.length" class="contents"><x-ui.empty icon="subtitles_off" title="رونوشتی برای این جلسه در دسترس نیست" /></div>
                </div>
                <div wire:key="media-notes" x-show="tab === 'notes'" x-cloak class="space-y-3 px-2">
                    <x-ui.forms.textarea label="یادداشت این لحظه" name="media-note" x-model="noteBody" maxlength="500" :rows="3" />
                    <div class="flex flex-wrap gap-2"><x-ui.buttons.form variant="primary" x-on:click="addNote()" x-bind:disabled="noteBusy || !noteBody.trim()"><span class="material-symbols-rounded">bookmark_add</span><span x-text="noteBusy ? 'در حال ذخیره…' : 'افزودن یادداشت'"></span></x-ui.buttons.form><x-ui.buttons.form variant="tonal" x-on:click="copyMoment()">پیوند این لحظه</x-ui.buttons.form></div>
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
