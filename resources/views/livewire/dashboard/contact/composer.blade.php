<footer class="flex-shrink-0 border-t backdrop-blur-sm bg-[color-mix(in_srgb,var(--md-sys-color-surface)_90%,transparent)] border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
    @error('composer.body')
    <div class="flex items-center gap-1.5 px-4 pt-2.5 text-[11px] text-[var(--md-sys-color-error)]" role="alert">
        <span class="material-symbols-rounded text-[13px]">error_outline</span>
        <span>{{ $message }}</span>
    </div>
    @enderror

    <div class="relative">
        <div x-show="emojiOpen" x-on:click.outside="emojiOpen = false"
             class="absolute bottom-full end-4 mb-2 p-3 rounded-xl z-40 min-w-[272px] bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)] shadow-[0_12px_48px_color-mix(in_srgb,var(--md-sys-color-shadow)_18%,transparent)]"
             role="dialog" aria-label="ایموجی">
            <div class="flex items-center gap-0.5 mb-2.5 pb-2 border-b border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_20%,transparent)]">
                <template x-for="(cat, idx) in emojis" :key="idx">
                    <button x-on:click="activeCat=idx" x-text="cat.cat"
                            class="w-8 h-8 rounded-lg flex items-center justify-center text-base transition-all duration-150"
                            :class="activeCat===idx ? 'bg-[var(--md-sys-color-primary-container)] scale-110' : 'opacity-50 hover:opacity-80'"></button>
                </template>
            </div>
            <div class="grid grid-cols-8 gap-0.5">
                <template x-for="(e, i) in emojis[activeCat].items" :key="i">
                    <button x-on:click="insertEmoji(e)" x-text="e"
                            class="w-8 h-8 rounded-lg flex items-center justify-center text-lg hover:bg-[var(--md-sys-color-surface-variant)] transition-colors"></button>
                </template>
            </div>
        </div>

        <template x-if="replyingTo">
            <div class="mx-4 mt-3 flex items-center gap-2.5 px-3 py-2 rounded-t-xl rounded-b-md bg-[var(--md-sys-color-surface-variant)] border-r-2 border-[var(--md-sys-color-primary)]">
                <span class="material-symbols-rounded text-[13px] flex-shrink-0 text-[var(--md-sys-color-primary)]">reply</span>
                <div class="flex-1 min-w-0">
                    <p class="text-[10px] font-bold text-[var(--md-sys-color-primary)]" x-text="replyingTo.sender.name"></p>
                    <p class="text-[11px] truncate text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_70%,transparent)]" x-text="replyingTo.body"></p>
                </div>
                <button x-on:click.prevent="cancelReply" aria-label="لغو پاسخ"
                        class="w-6 h-6 rounded-md flex items-center justify-center transition-all hover:brightness-90 active:scale-90 text-[var(--md-sys-color-on-surface-variant)]">
                    <span class="material-symbols-rounded text-[14px]">close</span>
                </button>
            </div>
        </template>

        @if(count($composer->attachments))
            <div class="flex flex-wrap items-center gap-1.5 mx-4 mt-3 px-3 py-2 rounded-xl bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_50%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
                @foreach($composer->attachments as $i => $file)
                    <div wire:key="staged-attachment-{{ $i }}"
                         class="flex items-center gap-1.5 ps-1 pe-2 py-1 rounded-lg max-w-[180px] bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
                        @if(str_starts_with($file->getMimeType() ?? '', 'image/'))
                            <img src="{{ $file->temporaryUrl() }}" class="w-5 h-5 rounded-md object-cover flex-shrink-0" alt="">
                        @else
                            <span class="material-symbols-rounded text-[13px] flex-shrink-0 text-[var(--md-sys-color-on-surface-variant)]">description</span>
                        @endif
                        <span class="text-[10px] font-medium truncate text-[var(--md-sys-color-on-surface)]">{{ $file->getClientOriginalName() }}</span>
                        <button type="button" wire:click="removeAttachment({{ $i }})" aria-label="حذف فایل"
                                class="flex-shrink-0 w-4 h-4 rounded-full flex items-center justify-center transition-colors hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-surface-variant)]">
                            <span class="material-symbols-rounded text-[11px]">close</span>
                        </button>
                    </div>
                @endforeach
            </div>
        @endif

        @error('composer.attachments') <p class="text-[10px] text-[var(--md-sys-color-error)] mx-4 mt-1.5">{{ $message }}</p> @enderror
        @error('composer.attachments.*') <p class="text-[10px] text-[var(--md-sys-color-error)] mx-4 mt-1.5">{{ $message }}</p> @enderror

        <div class="flex flex-wrap items-center gap-1.5 px-4 py-2.5">
            <div class="flex-1 min-w-0 order-1 relative rounded-xl overflow-hidden transition-all duration-200 bg-[var(--md-sys-color-surface-variant)] border"
                 :class="[
                     replyingTo ? 'rounded-t-none' : '',
                     (($wire.composer.body && $wire.composer.body.length > 0) || $wire.composer.attachments.length > 0)
                         ? 'border-[var(--md-sys-color-primary)]'
                         : 'border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)]'
                 ]">
                <textarea x-ref="msgTa" id="msg-ta"
                          wire:model.defer="composer.body"
                          wire:loading.attr="disabled" wire:target="send"
                          x-on:keydown.ctrl.enter.prevent="sendMessage()"
                          x-on:keydown.enter="if(!event.shiftKey){event.preventDefault();sendMessage();}"
                          x-on:input="$el.style.height='auto';$el.style.height=Math.min($el.scrollHeight,160)+'px'"
                          x-on:paste="pasteImage($event, 'composer-attachments')"
                          rows="1" placeholder="پیام خود را بنویسید..." aria-label="متن پیام"
                          class="w-full bg-transparent px-3 py-2.5 text-sm resize-none focus:outline-none leading-relaxed min-h-[40px] max-h-[160px] field-sizing-content text-[var(--md-sys-color-on-surface)] disabled:opacity-60 placeholder:text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_50%,transparent)]"></textarea>
                <span x-show="$refs.msgTa && $refs.msgTa.value.length > 1800"
                      x-text="$refs.msgTa ? $refs.msgTa.value.length + ' / 2000' : ''"
                      class="absolute bottom-1.5 end-3 text-[10px] font-medium pointer-events-none"
                      :class="$refs.msgTa && $refs.msgTa.value.length > 1950 ? 'text-[var(--md-sys-color-error)]' : 'text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_60%,transparent)]'"
                      aria-live="polite"></span>
            </div>

            <button x-on:click.prevent="sendMessage"
                    wire:loading.attr="disabled" wire:target="send"
                    class="flex-shrink-0 w-9 h-9 order-2 md:order-4 rounded-xl flex items-center justify-center transition-all hover:brightness-110 hover:-translate-y-0.5 active:scale-90 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:translate-y-0"
                    :class="($wire.composer.body && $wire.composer.body.length > 0) || $wire.composer.attachments.length > 0
                        ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_30%,transparent)]'
                        : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]'"
                    aria-label="ارسال پیام">
                <span wire:loading.remove wire:target="send"
                      class="material-symbols-rounded text-[18px] font-fill rotate-180">send</span>
                <span wire:loading wire:target="send"
                      class="material-symbols-rounded text-[18px] animate-spin">progress_activity</span>
            </button>

            <div class="basis-full h-0 order-3 md:hidden"></div>

            <button x-on:click="emojiOpen=!emojiOpen" type="button" aria-label="ایموجی"
                    class="flex-shrink-0 w-8 h-8 order-4 md:order-2 ms-auto md:ms-0 rounded-lg flex items-center justify-center transition-all hover:brightness-95 active:scale-90 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">
                <span class="material-symbols-rounded text-[18px]"
                      :class="emojiOpen ? 'text-[var(--md-sys-color-primary)]' : ''"
                      x-text="emojiOpen ? 'sentiment_satisfied_alt' : 'sentiment_satisfied'"></span>
            </button>

            <label for="composer-attachments" aria-label="افزودن فایل ضمیمه"
                   class="flex-shrink-0 w-8 h-8 order-5 md:order-3 rounded-lg flex items-center justify-center transition-all hover:brightness-95 active:scale-90 cursor-pointer bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">
                <span class="material-symbols-rounded text-[18px]">attach_file</span>
            </label>
            <input id="composer-attachments" type="file" multiple wire:model="composer.attachments" class="hidden"
                   accept="image/jpeg,image/png,image/gif,image/webp,application/pdf,.doc,.docx,.xls,.xlsx,.zip,.rar"/>
        </div>

        <p class="hidden md:block text-[10px] text-center pb-2.5 text-[var(--md-sys-color-on-surface-variant)] opacity-50"
           aria-hidden="true"> خط جدید Shift+Enter
        </p>
        <p class="hidden md:block text-[10px] text-center pb-2.5 text-[var(--md-sys-color-on-surface-variant)] opacity-50"
           aria-hidden="true"> ارسال Enter
        </p>
    </div>
</footer>
