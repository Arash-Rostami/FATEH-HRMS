<div wire:key="profile-sign-code" x-data="signCodeCard()" class="rounded-2xl border border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-low)] shadow-sm w-full overflow-hidden">
    <button type="button" x-on:click="toggle()" x-bind:aria-expanded="open"
            class="group w-full flex items-center justify-between gap-2 px-4 py-3 text-right hover:bg-[var(--md-sys-color-surface-variant)]/30 transition-colors">
        <span class="flex items-center gap-2 text-sm font-bold text-[var(--md-sys-color-on-surface)]">
            <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-primary)]">key_vertical</span>
            {{ __('resources/correspondence/strings.profile.sign_code_section') }}
            <x-ui.badge :variant="$this->hasSignCode ? 'info' : 'neutral'">
                {{ $this->hasSignCode ? __('resources/correspondence/strings.profile.sign_code_set') : __('resources/correspondence/strings.profile.sign_code_unset') }}
            </x-ui.badge>
        </span>
        <span class="material-symbols-rounded text-[18px] text-[var(--md-sys-color-on-surface-variant)] transition-transform duration-200 group-aria-expanded:rotate-180">expand_more</span>
    </button>

    <div x-show="open" x-collapse x-cloak class="px-4 pb-4 pt-1 border-t border-[var(--md-sys-color-outline-variant)]/60">
        <p class="text-xs leading-relaxed text-[var(--md-sys-color-on-surface-variant)] mb-4">
            {{ __('resources/correspondence/strings.profile.sign_code_hint', ['min' => convertToPersian(config('ai.correspondence.sign_code_min_length', 6))]) }}
        </p>

        <form wire:submit.prevent="saveSignCode" class="space-y-4">
            <x-ui.forms.input
                type="password"
                wire:model="signCodeForm.current_password"
                name="signCodeForm.current_password"
                autocomplete="current-password"
                label="{{ __('resources/correspondence/strings.profile.sign_code_password_label') }}"
                icon="lock"
            />

            <x-ui.forms.input
                type="password"
                wire:model="signCodeForm.code"
                name="signCodeForm.code"
                autocomplete="new-password"
                label="{{ __('resources/correspondence/strings.profile.sign_code_label') }}"
                icon="key_vertical"
            />

            <div class="flex items-center justify-between gap-2 pt-2">
                @if($this->hasSignCode)
                    <div wire:key="profile-sign-code-clear">
                        <x-ui.buttons.form
                            type="button"
                            variant="danger"
                            icon="delete"
                            loading="clearSignCode"
                            wire:click="clearSignCode"
                            wire:confirm="{{ __('resources/correspondence/strings.profile.sign_code_clear_confirm') }}"
                            class="rounded-xl text-sm"
                        >
                            {{ __('resources/correspondence/strings.profile.sign_code_clear') }}
                        </x-ui.buttons.form>
                    </div>
                @endif

                <div wire:key="profile-sign-code-save" class="ms-auto">
                    <x-ui.buttons.form
                        type="submit"
                        loading="saveSignCode"
                        loading-text="در حال ذخیره..."
                        icon="save"
                        class="px-5 py-2 rounded-xl text-sm font-bold hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:shadow-md duration-300"
                    >
                        {{ __('resources/correspondence/strings.profile.sign_code_save') }}
                    </x-ui.buttons.form>
                </div>
            </div>
        </form>
    </div>
</div>
