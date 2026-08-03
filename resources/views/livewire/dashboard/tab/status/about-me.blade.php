<div x-data="{
        user: null,
        aboutMe: {},
        resetData() { setTimeout(() => this.user = null, 300) },
        get extraKeys() {
                const core = ['bio','movies','music','hobbies','food','sports'];
                return Object.keys(this.aboutMe).filter(k => !core.includes(k) && this.aboutMe[k]);
            }
     }"
     @open-about-me.window="user = $event.detail.user; aboutMe = $event.detail.aboutMe || {}; $wire.showAboutModal = true;">

    <x-ui.modals.base
        wire:model="showAboutModal"
        contentClass="!w-full md:!w-7xl bg-[var(--md-sys-color-surface)] rounded-3xl shadow-2xl overflow-x-hidden flex flex-col max-h-[90vh] !p-0 border border-[var(--md-sys-color-outline-variant)]/30 relative">

        <div class="absolute inset-0 pointer-events-none z-0 opacity-[0.03] dark:opacity-[0.05]"
             style="background-image: radial-gradient(circle, var(--md-sys-color-on-surface) 1px, transparent 1px); background-size: 28px 28px;">
        </div>
        <div class="absolute -top-40 -right-40 w-[28rem] h-[28rem] rounded-full bg-[var(--md-sys-color-primary)] opacity-[0.06] pointer-events-none z-0"></div>

        <div dir="rtl" class="flex flex-col flex-1 overflow-hidden relative z-10"
             x-data="{ ready: false }"
             x-effect="if (show && !ready) { setTimeout(() => { if (show) ready = true }, 1000) } else if (!show) { ready = false }"
             x-show="ready">

            <div class="px-6 py-6 flex items-center justify-between border-b border-[var(--md-sys-color-outline-variant)]/40 bg-[var(--md-sys-color-surface)]/80 sticky top-0 z-20">
                <div class="flex items-center gap-5">
                    <div class="relative group shrink-0">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden bg-[var(--md-sys-color-surface-variant)] shadow-[0_8px_20px_color-mix(in_srgb,var(--md-sys-color-primary)_15%,transparent)] border-2 border-[var(--md-sys-color-surface)] transition-transform duration-500 group-hover:scale-105 relative">
                            <template x-if="user?.image">
                                <img :src="user.image" :alt="user?.name" class="w-full h-full object-cover" />
                            </template>
                        </div>
                        <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-emerald-500 border-4 border-[var(--md-sys-color-surface)] shadow-sm"></div>
                    </div>

                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <h3 class="text-xl font-bold text-[var(--md-sys-color-on-surface)] tracking-tight leading-none" x-text="user?.name || 'کاربر سیستم'"></h3>
                            <span class="px-2.5 py-1 rounded-lg bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] text-[10px] font-bold uppercase tracking-widest">همکار</span>
                        </div>
                        <p class="text-sm font-semibold text-[var(--md-sys-color-primary)]" x-text="user?.position || 'موقعیت شغلی'"></p>
                        <p class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] flex items-center gap-1.5">
                            <span class="material-symbols-rounded text-[14px]">location_on</span>
                            تهران، ستاد مرکزی
                        </p>
                    </div>
                </div>
            </div>

            <div class="p-6 sm:p-8 overflow-y-auto custom-scrollbar flex-1">
                <div class="max-w-5xl mx-auto w-full space-y-8">

                    <div x-show="user?.department || user?.division || user?.section || (user?.favoriteColors && user.favoriteColors.length)"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-4"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="flex flex-wrap items-center gap-2">
                        <template x-if="user?.department">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[var(--md-sys-color-surface-variant)]/40 text-[var(--md-sys-color-on-surface-variant)] text-xs font-medium">
                                <span class="material-symbols-rounded text-[14px] opacity-70">domain</span>
                                <span x-text="user.department"></span>
                            </span>
                        </template>
                        <template x-if="user?.division">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[var(--md-sys-color-surface-variant)]/40 text-[var(--md-sys-color-on-surface-variant)] text-xs font-medium">
                                <span class="material-symbols-rounded text-[14px] opacity-70">account_tree</span>
                                <span x-text="user.division"></span>
                            </span>
                        </template>
                        <template x-if="user?.section">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[var(--md-sys-color-surface-variant)]/40 text-[var(--md-sys-color-on-surface-variant)] text-xs font-medium">
                                <span class="material-symbols-rounded text-[14px] opacity-70">view_module</span>
                                <span x-text="user.section"></span>
                            </span>
                        </template>
                        <template x-if="user?.favoriteColors && user.favoriteColors.length">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[var(--md-sys-color-surface-variant)]/40 text-[var(--md-sys-color-on-surface-variant)] text-xs font-medium">
                                <span class="material-symbols-rounded text-[14px] opacity-70">palette</span>
                                <template x-for="c in user.favoriteColors" :key="c">
                                    <span class="w-3.5 h-3.5 rounded-full border border-[var(--md-sys-color-outline-variant)]/60" :style="`background:${c}`" :title="c"></span>
                                </template>
                            </span>
                        </template>
                    </div>

                    @if($this->aboutMeSkills->isNotEmpty())
                        <div class="space-y-2">
                            <h4 class="text-xs font-bold text-[var(--md-sys-color-primary)] uppercase tracking-widest">مهارت‌ها</h4>
                            <div class="bg-[var(--md-sys-color-surface)] rounded-2xl border border-[var(--md-sys-color-outline-variant)]/50 divide-y divide-[var(--md-sys-color-outline-variant)]/30 px-5">
                                @foreach($this->aboutMeSkills as $skillUser)
                                    @include('livewire.dashboard.profile.skill-row', [
                                        'skillUser' => $skillUser,
                                        'owner' => $skillUser->user,
                                        'viewer' => Auth::user(),
                                        'skillUserPresenter' => $skillUserPresenter,
                                        'skillPresenter' => $skillPresenter,
                                    ])
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div x-show="aboutMe.bio"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-4"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="relative bg-gradient-to-br from-[var(--md-sys-color-primary-container)]/20 to-transparent rounded-3xl p-6 sm:p-8 border border-[var(--md-sys-color-primary)]/10 shadow-sm group overflow-hidden">

                        <div class="absolute top-0 inset-x-0 h-[3px] bg-[var(--md-sys-color-primary)] shadow-[0_0_15px_color-mix(in_srgb,var(--md-sys-color-primary)_50%,transparent)]"></div>

                        <span class="material-symbols-rounded absolute -bottom-6 -left-6 text-[160px] text-[var(--md-sys-color-primary)] opacity-[0.04] transform -rotate-12 pointer-events-none font-fill">format_quote</span>

                        <div class="relative z-10">
                            <div class="flex items-center gap-3 mb-5">
                                <div class="w-8 h-8 rounded-xl bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] flex items-center justify-center shadow-sm">
                                    <span class="material-symbols-rounded text-sm font-fill">auto_stories</span>
                                </div>
                                <h4 class="text-xs font-bold text-[var(--md-sys-color-primary)] uppercase tracking-widest">من همانم که...</h4>
                                <div class="flex-1 h-px bg-[var(--md-sys-color-outline-variant)]/40"></div>
                            </div>
                            <p class="text-[15px] leading-[2.2] text-justify text-[var(--md-sys-color-on-surface)] font-medium" x-text="aboutMe.bio"></p>
                        </div>
                    </div>

                    <div x-show="aboutMe.movies || aboutMe.music || aboutMe.hobbies || aboutMe.food || aboutMe.sports"
                         class="grid grid-cols-1 md:grid-cols-2 gap-5">

                        <template x-if="aboutMe.movies">
                            <div class="bg-[var(--md-sys-color-surface)] rounded-2xl p-5 border border-[var(--md-sys-color-outline-variant)]/50 shadow-sm hover:shadow-[0_8px_25px_color-mix(in_srgb,var(--md-sys-color-tertiary)_15%,transparent)] transition-all duration-300 relative overflow-hidden group">
                                <div class="absolute top-0 inset-x-0 h-[3px] bg-[var(--md-sys-color-tertiary)] opacity-0 group-hover:opacity-100 transition-all duration-300 shadow-[0_0_12px_var(--md-sys-color-tertiary)]"></div>
                                <div class="flex items-start gap-4">
                                    <div class="w-11 h-11 rounded-xl bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] flex items-center justify-center shrink-0 shadow-sm transition-transform group-hover:scale-110">
                                        <span class="material-symbols-rounded text-xl font-fill">movie</span>
                                    </div>
                                    <div>
                                        <h5 class="text-[10px] font-bold text-[var(--md-sys-color-tertiary)] uppercase tracking-widest mb-1.5">فیلم و سریال</h5>
                                        <p class="text-sm text-[var(--md-sys-color-on-surface)] leading-relaxed font-medium" x-text="aboutMe.movies"></p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="aboutMe.music">
                            <div class="bg-[var(--md-sys-color-surface)] rounded-2xl p-5 border border-[var(--md-sys-color-outline-variant)]/50 shadow-sm hover:shadow-[0_8px_25px_color-mix(in_srgb,var(--md-sys-color-secondary)_15%,transparent)] transition-all duration-300 relative overflow-hidden group">
                                <div class="absolute top-0 inset-x-0 h-[3px] bg-[var(--md-sys-color-secondary)] opacity-0 group-hover:opacity-100 transition-all duration-300 shadow-[0_0_12px_var(--md-sys-color-secondary)]"></div>
                                <div class="flex items-start gap-4">
                                    <div class="w-11 h-11 rounded-xl bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] flex items-center justify-center shrink-0 shadow-sm transition-transform group-hover:scale-110">
                                        <span class="material-symbols-rounded text-xl font-fill">headphones</span>
                                    </div>
                                    <div>
                                        <h5 class="text-[10px] font-bold text-[var(--md-sys-color-secondary)] uppercase tracking-widest mb-1.5">موسیقی و پادکست</h5>
                                        <p class="text-sm text-[var(--md-sys-color-on-surface)] leading-relaxed font-medium" x-text="aboutMe.music"></p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="aboutMe.hobbies">
                            <div class="bg-[var(--md-sys-color-surface)] rounded-2xl p-6 border border-[var(--md-sys-color-outline-variant)]/50 shadow-sm hover:shadow-[0_8px_25px_color-mix(in_srgb,var(--md-sys-color-primary)_15%,transparent)] transition-all duration-300 relative overflow-hidden group md:col-span-2">
                                <div class="absolute top-0 inset-x-0 h-[3px] bg-[var(--md-sys-color-primary)] opacity-0 group-hover:opacity-100 transition-all duration-300 shadow-[0_0_12px_var(--md-sys-color-primary)]"></div>
                                <div class="flex items-start gap-4">
                                    <div class="w-11 h-11 rounded-xl bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center shrink-0 shadow-sm transition-transform group-hover:scale-110">
                                        <span class="material-symbols-rounded text-xl font-fill">palette</span>
                                    </div>
                                    <div class="flex-1">
                                        <h5 class="text-[10px] font-bold text-[var(--md-sys-color-primary)] uppercase tracking-widest mb-1.5">با اینا خستگیمو در می‌کنم</h5>
                                        <p class="text-sm text-[var(--md-sys-color-on-surface)] leading-relaxed font-medium" x-text="aboutMe.hobbies"></p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="aboutMe.food">
                            <div class="bg-[var(--md-sys-color-surface)] rounded-2xl p-5 border border-[var(--md-sys-color-outline-variant)]/50 shadow-sm hover:shadow-[0_8px_25px_rgba(230,81,0,0.15)] transition-all duration-300 relative overflow-hidden group">
                                <div class="absolute top-0 inset-x-0 h-[3px] bg-[#E65100] opacity-0 group-hover:opacity-100 transition-all duration-300 shadow-[0_0_12px_#E65100]"></div>
                                <div class="flex items-start gap-4">
                                    <div class="w-11 h-11 rounded-xl bg-[#FFF3E0] dark:bg-[#E65100]/20 text-[#E65100] flex items-center justify-center shrink-0 shadow-sm transition-transform group-hover:scale-110">
                                        <span class="material-symbols-rounded text-xl font-fill">restaurant</span>
                                    </div>
                                    <div>
                                        <h5 class="text-[10px] font-bold text-[#E65100] uppercase tracking-widest mb-1.5">غذا و خوراکی</h5>
                                        <p class="text-sm text-[var(--md-sys-color-on-surface)] leading-relaxed font-medium" x-text="aboutMe.food"></p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="aboutMe.sports">
                            <div class="bg-[var(--md-sys-color-surface)] rounded-2xl p-5 border border-[var(--md-sys-color-outline-variant)]/50 shadow-sm hover:shadow-[0_8px_25px_rgba(46,125,50,0.15)] transition-all duration-300 relative overflow-hidden group">
                                <div class="absolute top-0 inset-x-0 h-[3px] bg-[#2E7D32] opacity-0 group-hover:opacity-100 transition-all duration-300 shadow-[0_0_12px_#2E7D32]"></div>
                                <div class="flex items-start gap-4">
                                    <div class="w-11 h-11 rounded-xl bg-[#E8F5E9] dark:bg-[#2E7D32]/20 text-[#2E7D32] flex items-center justify-center shrink-0 shadow-sm transition-transform group-hover:scale-110">
                                        <span class="material-symbols-rounded text-xl font-fill">directions_bike</span>
                                    </div>
                                    <div>
                                        <h5 class="text-[10px] font-bold text-[#2E7D32] uppercase tracking-widest mb-1.5">ورزش و تفریح</h5>
                                        <p class="text-sm text-[var(--md-sys-color-on-surface)] leading-relaxed font-medium" x-text="aboutMe.sports"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div x-show="extraKeys.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <template x-for="key in extraKeys" :key="key">
                            <div class="bg-[var(--md-sys-color-surface)] rounded-2xl p-5 border border-[var(--md-sys-color-outline-variant)]/50 shadow-sm hover:shadow-[0_8px_25px_color-mix(in_srgb,var(--md-sys-color-secondary)_15%,transparent)] transition-all duration-300 relative overflow-hidden group">
                                <div class="absolute top-0 inset-x-0 h-[3px] bg-[var(--md-sys-color-outline-variant)] opacity-0 group-hover:opacity-60 transition-all duration-300"></div>
                                <div class="flex items-start gap-4">
                                    <div class="w-11 h-11 rounded-xl bg-[var(--md-sys-color-surface-variant)] text-[var(--md-sys-color-on-surface-variant)] flex items-center justify-center shrink-0 shadow-sm transition-transform group-hover:scale-110">
                                        <span class="material-symbols-rounded text-xl font-fill">edit_note</span>
                                    </div>
                                    <div>
                                        <h5 class="text-[10px] font-bold text-[var(--md-sys-color-on-surface-variant)] uppercase tracking-widest mb-1.5" x-text="key.replace(/_/g, ' ')"></h5>
                                        <p class="text-sm text-[var(--md-sys-color-on-surface)] leading-relaxed font-medium" x-text="aboutMe[key]"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div x-show="!aboutMe.bio && !aboutMe.movies && !aboutMe.music && !aboutMe.hobbies && !aboutMe.food && !aboutMe.sports && !extraKeys.length"
                         class="flex flex-col items-center justify-center py-20 px-4 text-center">
                        <div class="w-24 h-24 rounded-3xl bg-[var(--md-sys-color-surface-variant)]/30 flex items-center justify-center mb-6 relative overflow-hidden">
                            <span class="material-symbols-rounded text-5xl text-[var(--md-sys-color-on-surface-variant)]/40 font-fill">contact_page</span>
                            <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-[var(--md-sys-color-primary)]/5 to-transparent"></div>
                        </div>
                        <h4 class="text-lg font-bold text-[var(--md-sys-color-on-surface)] mb-2">هنوز داستانی برای تعریف نیست</h4>
                        <p class="text-sm text-[var(--md-sys-color-on-surface-variant)] max-w-xs leading-relaxed">
                            این همکار هنوز بخش "درباره من" پروفایل خود را تکمیل نکرده است.
                        </p>
                    </div>

                </div>
            </div>

            <div class="h-8 bg-gradient-to-t from-[var(--md-sys-color-surface)] to-transparent absolute bottom-0 left-0 right-0 pointer-events-none z-20"></div>

        </div>
    </x-ui.modals.base>
</div>
