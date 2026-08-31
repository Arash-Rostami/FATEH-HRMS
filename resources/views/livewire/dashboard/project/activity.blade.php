@php($meAvatar = auth()->user()?->getProfileImageUrl())

<div class="mt-4 flex flex-col gap-4" wire:key="activity-{{ $activeProjectId }}" x-on:project-activity-refresh.window="$wire.refreshActivity()">
    <form wire:submit.prevent="postComment"
          class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm p-4 flex flex-col gap-3"
          x-data="projectComposer(@entangle('activityComposer.body').live, @js($this->mentionCandidates->pluck('name')))">
        <div class="flex items-center gap-2.5">
            @auth
                <div class="shrink-0 w-9 h-9 rounded-full overflow-hidden ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center text-sm font-bold">
                    @if($meAvatar)
                        <img src="{{ $meAvatar }}" class="w-full h-full object-cover" alt="" dir="auto">
                    @else
                        <span dir="auto">{{ mb_substr(auth()->user()->name ?? '?', 0, 1) }}</span>
                    @endif
                </div>
            @endauth

            <div class="relative flex-1 min-w-0">
                <textarea x-ref="composer" rows="2" @input="onInput($event)" :value="value" @keydown.escape.window="open = false"
                          placeholder="تصمیم، تأیید یا دلیل تغییر را ثبت کنید… (@ برای اشاره)"
                          class="w-full rounded-xl border border-[var(--md-sys-color-outline-variant)]/60 bg-[var(--md-sys-color-surface-container-lowest)] px-3 py-2 text-sm leading-6 text-[var(--md-sys-color-on-surface)] placeholder:text-[var(--md-sys-color-on-surface-variant)]/60 focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]/30 focus:border-[var(--md-sys-color-primary)] transition-colors"></textarea>

                <x-ui.forms.maximize-trigger class="top-2 left-2"/>
                <x-ui.forms.maximize-overlay icon="chat" title="نظر جدید"/>

                <div x-show="open" x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1 scale-[0.97]"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="absolute top-full mt-1.5 right-0 w-60 max-h-56 overflow-y-auto custom-scrollbar rounded-2xl bg-[var(--tool-amethyst-bg)] border border-[var(--tool-amethyst-color)]/30 shadow-[0_12px_32px_color-mix(in_srgb,var(--md-sys-color-scrim)_22%,transparent)] z-50">
                    <div class="flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold text-[var(--tool-amethyst-color)] border-b border-[var(--tool-amethyst-color)]/25">
                        <span class="material-symbols-rounded text-[15px] text-[var(--tool-amethyst-color)]">alternate_email</span>
                        ذکر نام
                    </div>
                    <template x-for="name in filtered" :key="name">
                        <button type="button" @click="pick(name)"
                                class="flex items-center gap-2 w-full text-right px-3 py-2 text-sm text-[var(--tool-amethyst-text)] hover:bg-[var(--tool-amethyst-color)]/15 transition-colors">
                            <span class="material-symbols-rounded text-[16px] text-[var(--tool-amethyst-color)]">person</span>
                            <span x-text="name"></span>
                        </button>
                    </template>
                </div>
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="postComment"
                    class="shrink-0 flex items-center justify-center w-10 h-10 rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] hover:brightness-110 active:scale-95 disabled:opacity-50 transition shadow-sm">
                <span class="material-symbols-rounded text-lg rotate-180">send</span>
            </button>
        </div>

        <div class="flex items-center justify-between gap-2 pt-1">
            <label class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/50 cursor-pointer transition-colors">
                <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-primary)]">attach_file</span>
                <span>پیوست فایل</span>
                <input type="file" multiple wire:model="activityComposer.files" class="hidden"
                       accept=".jpeg,.jpg,.png,.gif,.bmp,.webp,.svg,.pdf,.doc,.docx,.xls,.xlsx,.odt,.ods"/>
            </label>

            <div class="flex items-center gap-2">
                <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-1" x-show="willNotify.length > 0" x-cloak>
                    <span class="material-symbols-rounded text-[13px] text-[var(--tool-amethyst-color)]">notifications</span>
                    <span x-text="willNotify.join('، ')"></span>
                </p>
                <span class="text-[11px] tabular-nums" :style="{ color: counterTone }"
                      x-text="(value || '').length + ' / ' + maxLength"></span>
            </div>
        </div>

        @if(count($activityComposer->files))
            <div class="flex flex-wrap items-center gap-1.5 -mt-1">
                @foreach($activityComposer->files as $i => $file)
                    <div wire:key="staged-activity-file-{{ $i }}"
                         class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg max-w-[180px] bg-[var(--tool-gold-bg)] text-[var(--tool-gold-text)] border border-[color-mix(in_srgb,var(--tool-gold-color)_25%,transparent)]">
                        @if(str_starts_with($file->getMimeType() ?? '', 'image/'))
                            <img src="{{ $file->temporaryUrl() }}" class="w-4 h-4 rounded object-cover flex-shrink-0" alt="">
                        @else
                            <span class="material-symbols-rounded text-[12px] flex-shrink-0">attach_file</span>
                        @endif
                        <span class="text-[10px] font-bold truncate">{{ $file->getClientOriginalName() }}</span>
                        <button type="button" wire:click="removeActivityAttachment({{ $i }})" aria-label="حذف فایل"
                                class="flex-shrink-0 w-4 h-4 rounded-full flex items-center justify-center hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-surface-variant)] transition-colors">
                            <span class="material-symbols-rounded text-[11px]">close</span>
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
        @error('activityComposer.files') <p class="text-xs text-[var(--md-sys-color-error)] -mt-1">{{ $message }}</p> @enderror
        @error('activityComposer.body')  <p class="text-xs text-[var(--md-sys-color-error)] -mt-1">{{ $message }}</p> @enderror
    </form>

    @php($allEntryPairs = $presenter->activityPairs($this->activityFeed['rows']))

    @if(count($this->activityFeed['rows']))
        <div class="flex flex-wrap items-center gap-2">
            <div class="relative flex-1 min-w-[200px]">
                <span class="material-symbols-rounded text-[16px] text-[var(--md-sys-color-on-surface-variant)] absolute top-1/2 -translate-y-1/2 right-3 pointer-events-none">search</span>
                <input type="text" x-model="activitySearch" placeholder="جستجو در فعالیت‌ها…"
                       :class="activitySearch !== '' ? 'border-[var(--md-sys-color-primary)]' : 'border-[var(--md-sys-color-outline-variant)]'"
                       class="w-full h-9 pr-9 pl-9 rounded-xl text-xs bg-[var(--md-sys-color-surface-container-highest)] border outline-none transition-colors text-[var(--md-sys-color-on-surface)] placeholder:text-[var(--md-sys-color-on-surface-variant)]/60"/>
                <button type="button" x-show="activitySearch !== ''" x-cloak @click="activitySearch = ''"
                        class="absolute top-1/2 -translate-y-1/2 left-2 text-[var(--md-sys-color-on-surface-variant)] hover:text-[var(--md-sys-color-on-surface)] transition-colors">
                    <span class="material-symbols-rounded text-[16px]">close</span>
                </button>
            </div>
            <button type="button" @click="activityPinnedOnly = !activityPinnedOnly"
                    :class="activityPinnedOnly
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent'
                        : 'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)]'"
                    class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl text-xs font-medium border transition-colors">
                <span class="material-symbols-rounded text-[14px]" :class="activityPinnedOnly ? 'font-fill' : ''">bookmark</span>
                <span>نشان‌شده‌ها</span>
            </button>
            <div class="inline-flex items-center gap-1">
                @foreach([
                    'comment' => ['نظر', 'chat_bubble'],
                    'status_change' => ['تغییر وضعیت', 'sync_alt'],
                    'assignment' => ['ارجاع', 'person_add'],
                    'archive' => ['آرشیو', 'archive'],
                    'attachment' => ['پیوست', 'attach_file'],
                ] as $type => [$label, $icon])
                    <button type="button" title="{{ $label }}"
                            @click="activityTypeFilter = activityTypeFilter === '{{ $type }}' ? '' : '{{ $type }}'"
                            :class="activityTypeFilter === '{{ $type }}'
                                ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] border-transparent'
                                : 'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] border-[var(--md-sys-color-outline-variant)]'"
                            class="inline-flex items-center justify-center w-9 h-9 rounded-xl border transition-colors">
                        <span class="material-symbols-rounded text-[14px]">{{ $icon }}</span>
                    </button>
                @endforeach
            </div>
            <button type="button" @click="exportActivity(@js($allEntryPairs))"
                    class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl text-xs font-medium bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] border border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-surface-variant)] transition-colors">
                <span class="material-symbols-rounded text-[14px]">download</span>
                <span>خروجی متنی</span>
            </button>
        </div>
    @endif

    <div class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface)] shadow-sm overflow-hidden">
        <div id="activity-viewport" role="log" aria-live="polite" class="relative flex flex-col gap-4 max-h-[28rem] overflow-y-auto custom-scrollbar p-4">
            @if(count($this->activityFeed['rows']))
                <div class="absolute top-4 bottom-4 right-[13px] w-px bg-[var(--md-sys-color-outline-variant)] opacity-30"></div>
            @endif

            @forelse($this->groupedActivityFeed as $group)
                @php($groupPairs = $presenter->activityPairs($group['entries']))
                <div class="flex items-center gap-3 py-1.5" x-show="anyActivityVisible(@js($groupPairs))" wire:key="activity-date-{{ $group['date'] }}">
                    <div class="flex-1 h-px bg-[linear-gradient(to_left,transparent,color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent))]"></div>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold tracking-wider bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)]">
                        {{ $group['label'] }}
                    </span>
                    <div class="flex-1 h-px bg-[linear-gradient(to_right,transparent,color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent))]"></div>
                </div>

                @foreach($group['entries'] as $entry)
                    <div class="relative animate-bubble-in" data-rf="activity-{{ $entry['id'] }}" wire:key="activity-entry-{{ $entry['id'] }}"
                         x-show="matchesActivityFilter(@js($entry['id']), @js(strip_tags($entry['body_html'] ?? $entry['body'] ?? '')), @js($entry['type']))">
                        <span @class([
                            'absolute top-3 right-[13px] w-[11px] h-[11px] rounded-full border-2 border-[var(--md-sys-color-surface)] z-10',
                            'bg-[var(--md-sys-color-primary)]'                  => $entry['type'] === 'comment' && !($entry['mentions_you'] ?? false),
                            'bg-[var(--md-sys-color-error)] animate-pulse-ring'  => $entry['type'] === 'comment' &&  ($entry['mentions_you'] ?? false),
                            'bg-[var(--md-sys-color-outline-variant)]'          => $entry['type'] !== 'comment',
                        ])></span>

                        @if($entry['type'] === 'comment')
                            <div @class([
                                'relative mr-7 px-3.5 py-3 rounded-2xl border bg-[var(--md-sys-color-surface)] transition-colors group',
                                'border-[var(--md-sys-color-outline-variant)]/50 hover:border-[var(--md-sys-color-outline-variant)]/80' => !($entry['mentions_you'] ?? false),
                                'border-[var(--md-sys-color-error)]/40 bg-[var(--md-sys-color-error-container)]/15 ring-1 ring-inset ring-[var(--md-sys-color-error)]/25' => $entry['mentions_you'] ?? false,
                            ])>
                                <div class="flex items-start gap-3">
                                    <div class="shrink-0 w-9 h-9 rounded-full overflow-hidden ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center text-sm font-bold">
                                        @if(!empty($entry['avatar_url']))
                                            <img src="{{ $entry['avatar_url'] }}" class="w-full h-full object-cover" alt="" dir="auto">
                                        @else
                                            <span dir="auto">{{ superClean(mb_substr($entry['user_name'] ?? '?', 0, 1)) }}</span>
                                        @endif
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <header class="flex items-center flex-wrap gap-x-2 gap-y-1 mb-1">
                                            <span class="text-sm font-bold text-[var(--md-sys-color-on-surface)]" dir="auto">{{ $entry['user_name'] }}</span>
                                            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-70">·</span>
                                            <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-70" title="{{ toJalali($entry['created_at']) }}">{{ toJalaliRelative($entry['created_at']) }}</span>

                                            @if($entry['mentions_you'] ?? false)
                                                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)] border border-[color-mix(in_srgb,var(--md-sys-color-error)_25%,transparent)]">
                                                    <span class="material-symbols-rounded text-[11px]">alternate_email</span>
                                                    شما را خطاب قرار داد
                                                </span>
                                            @endif

                                            @if($entry['is_edited'] ?? false)
                                                <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-50">(ویرایش‌شده)</span>
                                            @endif
                                        </header>

                                        @if($editingReplyId === $entry['id'])
                                            <div class="space-y-1.5 mt-1">
                                                <textarea wire:model.defer="editingReplyBody" rows="2"
                                                          class="w-full rounded-xl border border-[var(--md-sys-color-outline-variant)]/50 bg-transparent px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--md-sys-color-primary)]"></textarea>
                                                @error('editingReplyBody') <p class="text-[10px] text-[var(--md-sys-color-error)]">{{ $message }}</p> @enderror
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <button type="button" wire:click="cancelEditComment"
                                                            class="px-3.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all duration-150 hover:brightness-90 active:scale-95 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_25%,transparent)] text-[var(--md-sys-color-on-surface-variant)]">
                                                        انصراف
                                                    </button>
                                                    <button type="button" wire:click="saveEditedComment" wire:loading.attr="disabled" wire:target="saveEditedComment"
                                                            class="px-3.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all duration-150 hover:shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)] hover:brightness-110 active:scale-95 disabled:opacity-40 bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]">
                                                        ذخیره
                                                    </button>
                                                </div>
                                            </div>
                                        @else
                                            <p class="text-sm leading-6 break-words whitespace-pre-wrap text-[var(--md-sys-color-on-surface)]" dir="auto">{!! $entry['body_html'] !!}</p>

                                            @if(!empty($entry['files']))
                                                <div class="flex flex-wrap gap-1.5 mt-2">
                                                    @foreach($entry['files'] as $file)
                                                        @if($file['is_image'] ?? false)
                                                            <a href="{{ $file['url'] }}" data-fancybox="activity-{{ $entry['id'] }}" data-caption="{{ $file['name'] }}"
                                                               class="block w-24 h-24 rounded-lg overflow-hidden border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
                                                                <img src="{{ $file['url'] }}" alt="{{ $file['name'] }}" loading="lazy"
                                                                     class="w-full h-full object-cover">
                                                            </a>
                                                        @else
                                                            <a href="{{ $file['url'] }}" target="_blank" rel="noopener noreferrer"
                                                               class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[10px] font-bold border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] bg-[var(--tool-gold-bg)] text-[var(--tool-gold-text)] hover:brightness-110 active:scale-95 transition">
                                                                <span class="material-symbols-rounded text-[12px]">attach_file</span>
                                                                <span dir="auto">{{ $file['name'] ?? 'فایل' }}</span>
                                                                <span class="opacity-70">({{ $file['size_label'] }})</span>
                                                            </a>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif

                                            @php($grouped = $presenter->reactionGroups($entry['reactions'] ?? null))
                                            <div class="flex flex-wrap items-center gap-1.5 mt-2.5">
                                                @foreach($grouped as $emoji => $reactors)
                                                    <button type="button" wire:click="toggleReaction({{ $entry['id'] }}, '{{ $emoji }}')"
                                                            title="{{ $reactors->pluck('user_name')->implode('، ') }}"
                                                            @class([
                                                                'inline-flex items-center gap-1 px-1.5 py-0.5 rounded-lg text-[11px] font-bold border transition-all active:scale-95',
                                                                'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] border-[color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)] hover:brightness-110' => $reactors->contains(fn($r) => (int) $r['user_id'] === auth()->id()),
                                                                'bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] hover:bg-[var(--md-sys-color-surface-variant)]/60' => !$reactors->contains(fn($r) => (int) $r['user_id'] === auth()->id()),
                                                            ])>
                                                        <span class="text-[13px] leading-none">{{ $emoji }}</span>
                                                        <span class="tabular-nums">{{ convertToPersian($reactors->count()) }}</span>
                                                    </button>
                                                @endforeach

                                                <button type="button"
                                                        @click="$store.activityReactionPicker.open({{ $entry['id'] }})"
                                                        class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60 hover:text-[var(--md-sys-color-primary)] active:scale-95 transition"
                                                        title="افزودن واکنش">
                                                    <span class="material-symbols-rounded text-[15px]">add_reaction</span>
                                                </button>
                                            </div>

                                            <div x-show="$store.activityReactionPicker.is({{ $entry['id'] }})" x-cloak
                                                 x-transition:enter="transition ease-out duration-150"
                                                 x-transition:enter-start="opacity-0 -translate-y-1 scale-[0.97]"
                                                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                                 x-transition:leave="transition ease-in duration-100"
                                                 x-transition:leave-start="opacity-100"
                                                 x-transition:leave-end="opacity-0"
                                                 @click.outside="$store.activityReactionPicker.close()"
                                                 class="absolute top-full mt-1.5 right-0 z-30 p-2 rounded-2xl bg-[var(--md-sys-color-surface-container-high)] border border-[var(--md-sys-color-outline-variant)]/50 shadow-[0_12px_32px_color-mix(in_srgb,var(--md-sys-color-scrim)_22%,transparent)]">
                                                <div class="grid grid-cols-7 gap-0.5 max-w-[260px]">
                                                    <template x-for="emoji in $root.activityReactions" :key="emoji">
                                                        <button type="button"
                                                                @click="$root.toggleReactionAndClose({{ $entry['id'] }}, emoji)"
                                                                class="flex items-center justify-center w-8 h-8 rounded-lg hover:bg-[var(--md-sys-color-primary-container)]/40 hover:scale-110 active:scale-90 transition text-base">
                                                            <span x-text="emoji"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-0.5 mt-1.5 -mb-1 opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity duration-150">
                                                <button type="button"
                                                        @click="$store.pinned.togglePin(@js($entry['id']), @js('activity'))"
                                                        :class="$store.pinned.isPinned(@js($entry['id']), @js('activity'))
                                                            ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)]'
                                                            : 'text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60 hover:text-[var(--md-sys-color-on-surface)]'"
                                                        class="flex items-center justify-center w-7 h-7 rounded-lg active:scale-90 transition"
                                                        :aria-pressed="$store.pinned.isPinned(@js($entry['id']), @js('activity')) ? 'true' : 'false'"
                                                        title="نشان‌کردن">
                                                    <span class="material-symbols-rounded text-[15px]"
                                                          :class="$store.pinned.isPinned(@js($entry['id']), @js('activity')) ? 'font-fill' : ''">bookmark</span>
                                                </button>

                                                <button type="button"
                                                        @click="copyText(@js(strip_tags($entry['body_html'] ?? '')), @js('متن نظر کپی شد.'))"
                                                        class="flex items-center justify-center w-7 h-7 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60 hover:text-[var(--md-sys-color-on-surface)] active:scale-90 transition"
                                                        title="کپی متن">
                                                    <span class="material-symbols-rounded text-[15px]">content_copy</span>
                                                </button>

                                                <button type="button"
                                                        @click="copyText(@js(route('projects', ['open' => $activeProjectId, 'tab' => 'activity', 'focus_entry' => $entry['id']])), @js('لینک نظر کپی شد.'))"
                                                        class="flex items-center justify-center w-7 h-7 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60 hover:text-[var(--md-sys-color-on-surface)] active:scale-90 transition"
                                                        title="کپی لینک">
                                                    <span class="material-symbols-rounded text-[15px]">link</span>
                                                </button>

                                                @if($entry['can_modify'])
                                                    <button type="button"
                                                            wire:click="startEditComment({{ $entry['id'] }}, @js($entry['body']))"
                                                            class="flex items-center justify-center w-7 h-7 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-variant)]/60 hover:text-[var(--md-sys-color-on-surface)] active:scale-90 transition"
                                                            title="ویرایش">
                                                        <span class="material-symbols-rounded text-[15px]">edit</span>
                                                    </button>
                                                    <button type="button"
                                                            @click="confirmDeleteComment({{ $entry['id'] }})"
                                                            class="flex items-center justify-center w-7 h-7 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-error)] active:scale-90 transition"
                                                            title="حذف">
                                                        <span class="material-symbols-rounded text-[15px]">delete</span>
                                                    </button>
                                                @endif
                                            </div>

                                            @if($entry['can_modify'])
                                                <div x-show="activityDeletingId === {{ $entry['id'] }}" x-cloak
                                                     class="flex items-center gap-2 mt-2 px-3.5 py-2.5 rounded-lg bg-[var(--md-sys-color-error-container)] text-[var(--md-sys-color-on-error-container)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-error)_12%,transparent)] ring-1 ring-[color-mix(in_srgb,var(--md-sys-color-error)_15%,transparent)]">
                                                    <span class="material-symbols-rounded text-[15px] opacity-80">delete_forever</span>
                                                    <span class="text-xs font-semibold flex-1">حذف این نظر؟</span>
                                                    <button type="button" @click="cancelDeleteComment()"
                                                            class="px-3.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all duration-150 hover:brightness-90 active:scale-95 bg-[color-mix(in_srgb,var(--md-sys-color-on-error-container)_10%,transparent)]">
                                                        انصراف
                                                    </button>
                                                    <button type="button" wire:click="deleteComment({{ $entry['id'] }})" wire:loading.attr="disabled" wire:target="deleteComment"
                                                            class="px-3.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all duration-150 hover:brightness-110 hover:shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-error)_35%,transparent)] active:scale-95 bg-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-error)]">
                                                        حذف
                                                    </button>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="flex flex-col items-center gap-1">
                                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-[11px] font-bold border bg-[var(--md-sys-color-surface-container-highest)] text-[var(--md-sys-color-on-surface-variant)] border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] hover:brightness-110 transition">
                                    <span class="material-symbols-rounded text-[13px]">{{ $entry['icon'] }}</span>
                                    <span dir="auto">{{ $entry['body'] }}</span>
                                    @if($entry['task_progress'] !== null)
                                        <span class="mr-1 pr-1.5 border-r border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_50%,transparent)] tabular-nums" title="پیشرفت چک‌لیست وظیفه">{{ convertToPersian($entry['task_progress']) }}٪</span>
                                    @endif
                                </span>
                                <span class="block text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-60" title="{{ toJalali($entry['created_at']) }}">{{ toJalaliRelative($entry['created_at']) }}</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            @empty
                <x-ui.empty icon="timeline" title="هنوز فعالیتی ثبت نشده" variant="list"/>
            @endforelse

            @if(count($this->activityFeed['rows']))
                <div x-show="!anyActivityVisible(@js($allEntryPairs))" x-cloak>
                    <x-ui.empty icon="search_off" title="نتیجه‌ای یافت نشد" variant="list"/>
                </div>
            @endif
        </div>
    </div>

    @if($this->activityFeed['hasMore'])
        <div class="flex justify-center pb-2 w-full">
            <button type="button" x-on:click="loadOlderActivity()" :disabled="_loadingOlderActivity"
                    class="group flex items-center justify-center gap-2 transition-all outline-none font-medium text-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-surface)] px-5 py-2.5 rounded-xl border border-[var(--md-sys-color-outline-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:border-[var(--md-sys-color-primary)] shadow-sm hover:shadow-md disabled:opacity-50">
                <span x-show="!_loadingOlderActivity">نمایش فعالیت‌های قدیمی‌تر</span>
                <span x-show="_loadingOlderActivity" x-cloak>در حال بارگذاری…</span>
            </button>
        </div>
    @endif
</div>