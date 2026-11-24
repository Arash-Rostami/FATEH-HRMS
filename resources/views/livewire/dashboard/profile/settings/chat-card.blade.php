<div wire:key="profile-settings-chat-card" x-init="refreshChatState()" class="p-4 rounded-2xl border border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface-container)]/40 space-y-3">
    <div class="space-y-1">
        <p class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]">گفتگو <span class="opacity-60">(این مرورگر)</span></p>
        <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] opacity-70">اندازهٔ دکمه‌ها همان لحظه تغییر می‌کند. برای دیدن سایر تغییرها، گفتگو را دوباره باز کنید.</p>
    </div>

    <div class="p-3 rounded-xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/30 flex items-center justify-between gap-2">
        <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">اندازهٔ دکمه‌ها و آیکون‌ها در گفتگو</p>
        <x-ui.buttons.chat-scale/>
    </div>

    <div class="p-3 rounded-xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/30 space-y-2">
        <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">فونت پیام‌ها</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach(['contact' => 'پیام‌رسان', 'channel' => 'گروه‌ها'] as $scope => $name)
                <x-ui.forms.select label="{{ $name }}" name="chat-font-{{ $scope }}" x-model="chat.{{ $scope }}.messageFont" x-on:change="saveChatSetting('{{ $scope }}', 'messageFont', $event.target.value)">
                    <option value="default">فونت پیش‌فرض برنامه</option>
                    @foreach($chatFonts as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </x-ui.forms.select>
            @endforeach
        </div>
    </div>

    <div class="p-3 rounded-xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/30 space-y-2">
        <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">نمایش گفتگو</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <template x-for="mod in chatModules" :key="mod.id">
                <div class="space-y-1">
                    <p class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]" x-text="mod.label"></p>
                    <x-dashboard.settings.toggle-row icon="format_ink_highlighter" label="برجسته‌سازی و طرح پس‌زمینه" color="violet" active="chat[mod.id].isHighlighted" action="toggleChatLook(mod.id)"/>
                    <div x-show="chat[mod.id].isHighlighted" style="display:none" class="flex flex-wrap gap-2 px-3 pb-1">
                        @foreach($chatPatterns as $id => $label)
                            <button type="button" @click="saveChatSetting(mod.id, 'backgroundPatternType', '{{ $id }}')"
                                    :aria-pressed="chat[mod.id].backgroundPatternType === '{{ $id }}'"
                                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors"
                                    :class="chat[mod.id].backgroundPatternType === '{{ $id }}' ? 'bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)]' : 'bg-[var(--md-sys-color-surface-variant)]/60 text-[var(--md-sys-color-on-surface-variant)]'">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="p-3 rounded-xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/30 space-y-2">
        <p class="text-xs font-bold text-[var(--md-sys-color-on-surface)]">بستن خودکار نوار کناری</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <template x-for="mod in sidebarModules" :key="mod.id">
                <div class="space-y-1">
                    <p class="text-xs font-bold text-[var(--md-sys-color-on-surface-variant)]" x-text="mod.label"></p>
                    <x-dashboard.settings.toggle-row icon="view_sidebar" label="نوار کناری در شروع بسته باشد" color="indigo" active="sidebar[mod.id]" action="toggleSidebarDefault(mod.id)"/>
                </div>
            </template>
        </div>
    </div>

    <x-ui.buttons.form @click="resetChat()" variant="tonal" icon="restart_alt">
        بازنشانی گفتگو
    </x-ui.buttons.form>
</div>
