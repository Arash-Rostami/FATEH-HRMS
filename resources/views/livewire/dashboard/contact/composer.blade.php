<footer
    class="flex-shrink-0 border-t bg-[color-mix(in_srgb,var(--md-sys-color-surface)_90%,transparent)] border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)]">
    @error('composer.body')
    <div class="flex items-center gap-2 px-5 pt-3 text-[11px] text-[var(--md-sys-color-error)]" role="alert">
        <span class="material-symbols-rounded text-sm"
              aria-hidden="true">error_outline</span><span>{{ $message }}</span>
    </div>
    @enderror

    <div class="relative">

        @if($replyingToId && $replyingTo)
            <div
                class="mx-4 mt-3 reply-bar-enter flex items-center gap-3 px-4 py-2.5 rounded-t-2xl rounded-b-xl bg-[var(--md-sys-color-surface-variant)] border-r-[3px] border-[var(--md-sys-color-primary)]">
                <span class="material-symbols-rounded text-sm flex-shrink-0 text-[var(--md-sys-color-primary)]"
                      aria-hidden="true">reply</span>
                <div class="flex-1 min-w-0">
                    <p class="text-[10px] font-bold text-[var(--md-sys-color-primary)]">{{ $replyingTo['sender']['name'] ?? 'ناشناس' }}</p>
                    <p class="text-[11px] truncate text-[var(--md-sys-color-on-surface-variant)]">{{ Str::limit($replyingTo['body'], 60) }}</p>
                </div>
                <button wire:click="cancelReply" aria-label="لغو پاسخ"
                        class="w-6 h-6 rounded-lg flex items-center justify-center transition-all hover:brightness-90 active:scale-90 text-[var(--md-sys-color-on-surface-variant)]">
                    <span class="material-symbols-rounded text-base" aria-hidden="true">close</span>
                </button>
            </div>
        @endif

        @if(count($composer->attachments))
            <div class="flex flex-wrap items-center gap-2 mx-4 mt-3 px-3 py-2.5 rounded-xl bg-[var(--md-sys-color-surface-variant)]/50 border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
                @foreach($composer->attachments as $i => $file)
                    <div wire:key="staged-attachment-{{ $i }}"
                         class="flex items-center gap-1.5 pl-1 pr-2.5 py-1 rounded-lg bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] max-w-[180px]">
                        @if(str_starts_with($file->getMimeType() ?? '', 'image/'))
                            <img src="{{ $file->temporaryUrl() }}" class="w-5 h-5 rounded-md object-cover flex-shrink-0" alt="">
                        @else
                            <span class="material-symbols-rounded text-[14px] flex-shrink-0 text-[var(--md-sys-color-on-surface-variant)]">description</span>
                        @endif
                        <span class="text-[10px] font-medium truncate text-[var(--md-sys-color-on-surface)]">{{ $file->getClientOriginalName() }}</span>
                        <button type="button" wire:click="removeAttachment({{ $i }})"
                                class="flex-shrink-0 w-4 h-4 rounded-full flex items-center justify-center hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-surface-variant)] transition-colors"
                                aria-label="حذف فایل">
                            <span class="material-symbols-rounded text-[12px]">close</span>
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
        @error('composer.attachments') <p class="text-[10px] text-[var(--md-sys-color-error)] mx-4 mt-2">{{ $message }}</p> @enderror
        @error('composer.attachments.*') <p class="text-[10px] text-[var(--md-sys-color-error)] mx-4 mt-2">{{ $message }}</p> @enderror

        <div x-show="emojiOpen"
             x-on:click.outside="emojiOpen = false"
             class="absolute bottom-full right-4 mb-3 p-4 rounded-xl shadow-2xl z-40 min-w-[280px] bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] shadow-[0_12px_48px_color-mix(in_srgb,var(--md-sys-color-shadow)_20%,transparent)]"
             role="dialog" aria-label="ایموجی">
            <div
                class="flex items-center gap-1 mb-3 pb-2 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_20%,transparent)]">
                <template x-for="(cat, idx) in emojis" :key="idx">
                    <button x-on:click="activeCat=idx" x-text="cat.cat"
                            class="w-8 h-8 rounded-xl flex items-center justify-center text-base transition-all duration-150"
                            :class="activeCat===idx?'bg-[var(--md-sys-color-primary-container)] scale-110':'bg-transparent opacity-60'"></button>
                </template>
            </div>
            <div class="grid grid-cols-8 gap-1">
                <template x-for="(e, i) in emojis[activeCat].items" :key="i">
                    <button x-on:click="insertEmoji(e)" x-text="e"
                            class="emoji-btn w-8 h-8 rounded-lg flex items-center justify-center text-lg"></button>
                </template>
            </div>
        </div>

        <div class="flex items-center gap-2 px-4 py-3">
            <button x-on:click="emojiOpen=!emojiOpen"
                    type="button"
                    class="flex-shrink-0 w-9 h-9 rounded-xl flex items-center justify-center transition-all hover:brightness-95 active:scale-[0.92] bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]"
                    aria-label="ایموجی">
                    <span class="material-symbols-rounded text-xl"
                          :class="emojiOpen?'text-[22px]':''"
                          x-text="emojiOpen?'sentiment_satisfied_alt':'sentiment_satisfied'"
                          aria-hidden="true"></span>
            </button>

            <label for="composer-attachments"
                   class="flex-shrink-0 w-9 h-9 rounded-xl flex items-center justify-center transition-all hover:brightness-95 active:scale-[0.92] cursor-pointer bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]"
                   aria-label="افزودن فایل ضمیمه" title="افزودن فایل">
                <span class="material-symbols-rounded text-xl" aria-hidden="true">attach_file</span>
            </label>
            <input id="composer-attachments" type="file" multiple wire:model="composer.attachments" class="hidden"
                   accept="image/jpeg,image/png,image/gif,image/webp,application/pdf,.doc,.docx,.xls,.xlsx,.zip,.rar"/>

            <div
                class="flex-1 relative rounded-2xl overflow-hidden transition-all duration-200 bg-[var(--md-sys-color-surface-variant)] border-[1.5px] {{ $replyingToId ? 'rounded-t-none' : '' }}"
                :class="($wire.composer.body && $wire.composer.body.length > 0) || $wire.composer.attachments.length > 0 ? 'border-[var(--md-sys-color-primary)]' : 'border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]'">
                    <textarea x-ref="msgTa" id="msg-ta" wire:model.defer="composer.body"
                              wire:loading.attr="disabled"
                              wire:target="send"
                              x-on:keydown.ctrl.enter.prevent="sendMessage()"
                              x-on:keydown.enter="if(!event.shiftKey){event.preventDefault();sendMessage();}"
                              x-on:input="$el.style.height='auto';$el.style.height=Math.min($el.scrollHeight,160)+'px'"
                              rows="1"
                              placeholder="پیام خود را بنویسید..." aria-label="متن پیام"
                              class="w-full bg-transparent px-4 py-3 text-sm resize-none focus:outline-none leading-relaxed min-h-[44px] max-h-[160px] text-[var(--md-sys-color-on-surface)] field-sizing-content disabled:opacity-60"></textarea>
                <span x-show="$refs.msgTa && $refs.msgTa.value.length > 1800"
                      x-text="$refs.msgTa ? $refs.msgTa.value.length + ' / 2000' : ''"
                      class="absolute bottom-1.5 right-3 text-[10px] font-medium pointer-events-none"
                      :class="$refs.msgTa && $refs.msgTa.value.length > 1950 ? 'text-[var(--md-sys-color-error)]' : 'text-[var(--md-sys-color-on-surface-variant)]'"
                      aria-live="polite"></span>
            </div>

            <button x-on:click.prevent="sendMessage"
                    wire:loading.attr="disabled"
                    wire:target="send"
                    class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center transition-all hover:brightness-[1.1] hover:-translate-y-0.5 active:scale-[0.92] disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:translate-y-0"
                    :class="($wire.composer.body && $wire.composer.body.length > 0) || $wire.composer.attachments.length > 0 ? 'bg-[linear-gradient(135deg,var(--md-sys-color-primary),var(--md-sys-color-secondary))] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)] text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]'"
                    aria-label="ارسال پیام">
                <span wire:loading.remove
                      wire:target="send"
                      class="material-symbols-rounded text-[18px] font-fill rotate-180" aria-hidden="true">send</span>
                <span wire:loading
                      wire:target="send"
                      class="material-symbols-rounded text-[18px] animate-spin"
                      aria-hidden="true">progress_activity</span>
            </button>
        </div>
        <p class="text-[10px] text-center pb-2.5 text-[var(--md-sys-color-on-surface-variant)] opacity-50"
           aria-hidden="true"> خط جدید Shift+Enter
        </p>
        <p class="text-[10px] text-center pb-2.5 text-[var(--md-sys-color-on-surface-variant)] opacity-50"
           aria-hidden="true"> ارسال Enter
        </p>
    </div>
</footer>
