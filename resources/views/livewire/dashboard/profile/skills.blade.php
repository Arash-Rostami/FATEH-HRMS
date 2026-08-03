<div class="space-y-6" dir="rtl">
    <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-2xl p-6 border border-[var(--md-sys-color-outline-variant)] shadow-sm">
        <h3 class="text-[var(--md-sys-color-on-surface-variant)] mb-6 leading-relaxed">
            مهارت‌های خود را از فهرست شرکت انتخاب کنید یا در صورت نبود، مهارت جدیدی پیشنهاد دهید. پس از تأیید مدیریت، مهارت شما در دایرکتوری همکاران قابل مشاهده خواهد بود.
        </h3>

        <form wire:submit.prevent="requestSkill" class="grid grid-cols-1 md:grid-cols-2 gap-5" dir="rtl">
            <x-ui.forms.select
                label="انتخاب از فهرست مهارت‌ها"
                name="form.skillId"
                wire:model="form.skillId"
                icon="workspace_premium"
                :searchable="true"
                :options="$this->catalog"
            />

            <x-ui.forms.input
                wire:model="form.proposedName"
                name="form.proposedName"
                label="یا پیشنهاد مهارت جدید"
                placeholder="نام مهارتی که در فهرست نیست..."
                icon="add_circle"
            />

            <div class="md:col-span-2 flex justify-end">
                <x-ui.buttons.form type="submit" loading="requestSkill" loading-text="در حال ثبت..." icon="send"
                    class="px-6 py-2.5 rounded-xl font-bold hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] hover:shadow-md duration-300">
                    ثبت درخواست
                </x-ui.buttons.form>
            </div>
        </form>
    </div>

    <div class="bg-[var(--md-sys-color-surface-container-low)] rounded-2xl p-6 border border-[var(--md-sys-color-outline-variant)] shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-[var(--md-sys-color-on-surface)]">مهارت‌های من</h3>
            <button
                type="button"
                @click="$dispatch('open-modal', { name: 'profile-skill-legend' })"
                title="راهنمای نشان‌های سطح مهارت"
                class="flex items-center justify-center w-8 h-8 rounded-lg text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] transition-colors"
            >
                <span class="material-symbols-rounded text-lg">help</span>
            </button>
        </div>

        <x-ui.modals.dialog name="profile-skill-legend" title="راهنمای نشان‌های سطح مهارت">
            @include('livewire.dashboard.tab.status.legend', ['showFilterHint' => false])
        </x-ui.modals.dialog>

        @forelse($this->ownSkills as $skillUser)
            @if($skillUser->status->value === 'approved')
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0 grow">
                        @include('livewire.dashboard.profile.skill-row', [
                            'skillUser' => $skillUser,
                            'owner' => Auth::user(),
                            'viewer' => Auth::user(),
                            'skillUserPresenter' => $skillUserPresenter,
                            'skillPresenter' => $skillPresenter,
                        ])
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] whitespace-nowrap">{{ $skillUserPresenter->privacyLabel($skillUser) }}</span>
                        <button type="button" wire:click="openMarkUsed({{ $skillUser->id }})"
                                class="p-2 rounded-lg hover:bg-[var(--md-sys-color-secondary-container)] transition"
                                title="استفاده اخیر">
                            <span class="material-symbols-rounded text-[18px]">update</span>
                        </button>
                        <button type="button" wire:click="togglePrivacy({{ $skillUser->id }})"
                                class="p-2 rounded-lg hover:bg-[var(--md-sys-color-secondary-container)] transition"
                                title="نمایش عمومی/خصوصی">
                            <span class="material-symbols-rounded text-[18px]">{{ $skillUser->is_private ? 'visibility_off' : 'visibility' }}</span>
                        </button>
                        <button type="button" wire:click="toggleMentoring({{ $skillUser->id }})"
                                class="p-2 rounded-lg hover:bg-[var(--md-sys-color-secondary-container)] transition {{ $skillUser->is_mentoring ? 'text-[var(--md-sys-color-primary)]' : '' }}"
                                title="آمادگی راهنمایی">
                            <span class="material-symbols-rounded text-[18px]">school</span>
                        </button>
                    </div>
                </div>
            @else
                <div class="py-3 border-b border-[var(--md-sys-color-outline-variant)] last:border-0">
                    <p class="font-bold text-[var(--md-sys-color-on-surface)] truncate">
                        {{ $skillPresenter->displayLabel($skillUser->skill) }}
                    </p>
                    <p class="text-xs text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-1.5 flex-wrap">
                        @if($skillUser->status->value === 'pending')
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-bold tracking-wide bg-amber-500/10 text-amber-600 ring-1 ring-amber-500/20">
                                <span class="material-symbols-rounded text-[13px]">hourglass_top</span>
                                {{ $skillUser->status->label() }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-bold tracking-wide bg-rose-500/10 text-rose-600 ring-1 ring-rose-500/20">
                                <span class="material-symbols-rounded text-[13px]">cancel</span>
                                {{ $skillUser->status->label() }}
                            </span>
                            @if($skillUser->rejected_reason) <span>· {{ $skillUser->rejected_reason }}</span> @endif
                            <span>·</span>
                            <button type="button" wire:click="reRequest({{ $skillUser->id }})" class="text-[var(--md-sys-color-primary)] underline">درخواست مجدد</button>
                        @endif
                    </p>
                </div>
            @endif
        @empty
            <x-ui.empty icon="workspace_premium" title="هنوز مهارتی ثبت نکرده‌اید" variant="list" />
        @endforelse
    </div>

    <x-ui.modals.dialog name="mark-skill-used-modal" title="ثبت استفاده اخیر از مهارت">
        <form wire:submit.prevent="markUsed" class="space-y-4">
            <x-ui.forms.textarea
                wire:model="markUsedContext"
                name="markUsedContext"
                label="در چه زمینه‌ای استفاده کردید؟ (اختیاری)"
                rows="3"
                icon="edit_note"
            />
            <div class="flex justify-end">
                <x-ui.buttons.form type="submit" loading="markUsed" loading-text="در حال ثبت..." icon="check"
                    class="px-6 py-2.5 rounded-xl font-bold hover:bg-[var(--md-sys-color-primary-container)] hover:text-[var(--md-sys-color-on-primary-container)] duration-300">
                    ثبت
                </x-ui.buttons.form>
            </div>
        </form>
    </x-ui.modals.dialog>
</div>
