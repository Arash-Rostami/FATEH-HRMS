<form wire:key="ths-reply-composer"
      wire:submit.prevent="postReply"
      x-data="{
          len: 0,
          grow(el) { el.style.height = 'auto'; el.style.height = Math.min(160, el.scrollHeight) + 'px'; },
          insertQuick(text) {
              const ta = this.$refs.ta;
              ta.value = (ta.value.trim() ? ta.value.replace(/\s*$/, '') + '\n' : '') + text;
              ta.dispatchEvent(new Event('input', { bubbles: true }));
              ta.focus();
          }
      }"
      x-effect="len = ($wire.replyForm.body || '').length"
      class="max-h-[40vh] flex flex-col gap-2 px-4 sm:px-6 pt-2.5 pb-[calc(0.625rem+env(safe-area-inset-bottom))] border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] backdrop-blur-sm bg-[color-mix(in_srgb,var(--md-sys-color-surface)_90%,transparent)]">

    @error('replyForm.body')
        <p role="alert" class="shrink-0 flex items-center gap-1.5 text-[11px] text-[var(--md-sys-color-error)]">
            <span class="material-symbols-rounded text-[13px]">error_outline</span>
            {{ $message }}
        </p>
    @enderror
    @error('replyForm.files')
        <p role="alert" class="shrink-0 flex items-center gap-1.5 text-[11px] text-[var(--md-sys-color-error)]">
            <span class="material-symbols-rounded text-[13px]">error_outline</span>
            {{ $message }}
        </p>
    @enderror
    @error('replyForm.files.*')
        <p role="alert" class="shrink-0 flex items-center gap-1.5 text-[11px] text-[var(--md-sys-color-error)]">
            <span class="material-symbols-rounded text-[13px]">error_outline</span>
            {{ $message }}
        </p>
    @enderror

    @if($this->canReroute || $this->canRelease)
        <div wire:key="ths-quick-replies" class="shrink-0 flex gap-1.5 overflow-x-auto scrollbar-hide pb-0.5" role="group" aria-label="پاسخ سریع">
            @foreach($presenter->quickReplies() as $quick)
                <button type="button" data-q="{{ $quick }}" x-on:click="insertQuick($el.dataset.q)"
                        class="shrink-0 inline-flex items-center gap-1 min-h-[32px] px-2.5 rounded-full text-[11px] font-semibold bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] border border-transparent hover:border-[var(--md-sys-color-primary)] hover:text-[var(--md-sys-color-primary)] transition-colors">
                    <span class="material-symbols-rounded text-[14px]">bolt</span>
                    {{ $quick }}
                </button>
            @endforeach
        </div>
    @endif

    @if(count($replyForm->files))
        <div wire:key="ths-reply-staged"
             aria-label="فایل‌های آماده برای ارسال ({{ convertToPersian(count($replyForm->files)) }})"
             class="shrink min-h-[44px] max-h-[76px] overflow-y-auto overscroll-contain custom-scrollbar flex flex-wrap content-start items-center gap-1.5 px-3 py-2 rounded-xl bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_50%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">
            @foreach($replyForm->files as $i => $file)
                <div wire:key="staged-ths-reply-file-{{ $i }}"
                     class="inline-flex items-center gap-1.5 ps-2 pe-1 py-1 rounded-lg max-w-[180px] bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] animate-bubble-in">
                    @if(str_starts_with($file->getMimeType() ?? '', 'image/'))
                        <img src="{{ $file->temporaryUrl() }}" class="w-5 h-5 rounded-md object-cover flex-shrink-0" alt="">
                    @else
                        <span class="material-symbols-rounded text-[13px] flex-shrink-0 text-[var(--md-sys-color-on-surface-variant)]">description</span>
                    @endif
                    <span class="text-[10px] font-semibold truncate text-[var(--md-sys-color-on-surface)]">{{ $file->getClientOriginalName() }}</span>
                    <button type="button" wire:click="removeReplyAttachment({{ $i }})" aria-label="حذف فایل"
                            class="flex-shrink-0 w-[22px] h-[22px] rounded-full flex items-center justify-center hover:bg-[var(--md-sys-color-error-container)] hover:text-[var(--md-sys-color-error)] text-[var(--md-sys-color-on-surface-variant)] transition-colors">
                        <span class="material-symbols-rounded text-[12px]">close</span>
                    </button>
                </div>
            @endforeach
        </div>
    @endif

    <div class="shrink-0 flex items-end gap-1.5">
        <div class="flex-1 min-w-0 relative rounded-xl bg-[var(--md-sys-color-surface-variant)] border transition-colors duration-200"
             :class="($wire.replyForm.body && $wire.replyForm.body.length > 0) || $wire.replyForm.files.length > 0
                 ? 'border-[var(--md-sys-color-primary)]'
                 : 'border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_35%,transparent)]'">
            <textarea x-ref="ta" data-ths-composer dir="rtl"
                      wire:model.defer="replyForm.body"
                      wire:loading.attr="disabled" wire:target="postReply"
                      x-init="$nextTick(() => { grow($el); if (window.matchMedia('(min-width:640px)').matches) $el.focus() })"
                      x-on:input="len = $el.value.length; grow($el)"
                      x-on:keydown.ctrl.enter.prevent="if ($el.value.trim() || $wire.replyForm.files.length) $el.form.requestSubmit()"
                      x-on:keydown.meta.enter.prevent="if ($el.value.trim() || $wire.replyForm.files.length) $el.form.requestSubmit()"
                      rows="1" maxlength="4000" placeholder="پاسخ خود را بنویسید..." aria-label="متن پاسخ"
                      class="block w-full bg-transparent px-3 pt-2.5 pb-5 text-[13px] leading-relaxed resize-none focus:outline-none min-h-[44px] max-h-[160px] text-[var(--md-sys-color-on-surface)] disabled:opacity-60 placeholder:text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_50%,transparent)]"></textarea>
            <span x-text="len.toLocaleString('fa-IR') + ' / ' + (4000).toLocaleString('fa-IR')"
                  :class="len > 3900 ? 'text-[var(--md-sys-color-error)]' : 'text-[color-mix(in_srgb,var(--md-sys-color-on-surface-variant)_60%,transparent)]'"
                  class="absolute bottom-1 start-3 text-[10px] font-medium tabular-nums pointer-events-none" dir="ltr" aria-live="polite"></span>
        </div>

        <label aria-label="افزودن فایل ضمیمه" title="پیوست"
               class="flex-shrink-0 w-11 h-11 sm:w-9 sm:h-9 rounded-lg flex items-center justify-center cursor-pointer transition-all hover:brightness-95 active:scale-90 bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]">
            <span class="material-symbols-rounded text-[18px]">attach_file</span>
            <input type="file" multiple wire:model="replyForm.files" class="hidden"/>
        </label>

        <x-ui.buttons.form type="submit" loading="postReply" aria-label="ارسال پاسخ" title="ارسال پاسخ"
                x-bind:disabled="!$wire.replyForm.body.trim() && !$wire.replyForm.files.length"
                class="flex-shrink-0 w-11 h-11 sm:w-10 sm:h-10 p-0 rounded-xl hover:brightness-110 hover:-translate-y-0.5 disabled:hover:translate-y-0"
                x-bind:class="($wire.replyForm.body && $wire.replyForm.body.trim().length > 0) || $wire.replyForm.files.length > 0
                    ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] shadow-[0_4px_16px_color-mix(in_srgb,var(--md-sys-color-primary)_30%,transparent)]'
                    : 'bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)]'">
            <span wire:loading.remove wire:target="postReply" class="material-symbols-rounded text-[18px] font-fill rotate-180">send</span>
        </x-ui.buttons.form>
    </div>

    @if($this->canReroute || $this->canRelease)
        <p wire:key="ths-reply-note-visible" class="shrink-0 flex items-center gap-1.5 text-[11px] text-[var(--md-sys-color-on-surface-variant)]">
            <span class="material-symbols-rounded text-[14px] text-[var(--md-sys-color-warning)]">visibility</span>
            این توضیح برای درخواست‌دهنده نیز قابل مشاهده است.
        </p>
    @endif

    <p class="shrink-0 hidden sm:flex items-center justify-between gap-2 text-[10px] text-[var(--md-sys-color-on-surface-variant)] opacity-60" aria-hidden="true">
        <span x-text="(4000 - len).toLocaleString('fa-IR') + ' کاراکتر باقی‌مانده'"></span>
        <span class="inline-flex items-center gap-1.5">
            <kbd class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-surface-variant)] font-sans text-[9px] font-bold">Ctrl+Enter</kbd> ارسال
            <span class="opacity-40">•</span>
            <kbd class="px-1.5 py-0.5 rounded-md bg-[var(--md-sys-color-surface-variant)] font-sans text-[9px] font-bold">Shift+Enter</kbd> خط جدید
            <span class="opacity-40">•</span>
            فایل را اینجا بکشید
        </span>
    </p>
</form>
