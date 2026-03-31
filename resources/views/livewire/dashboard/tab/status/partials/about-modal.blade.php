<div x-data="{
        show: false,
        user: null,
        aboutMe: {},
        close() { this.show = false; setTimeout(() => this.user = null, 300) }
     }"
     @open-about-modal.window="show = true; user = $event.detail.user; aboutMe = $event.detail.aboutMe || {};">
    <template x-teleport="body">
         x-show="show"
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
         style="display: none;">

        <!-- Backdrop -->
        <div x-show="show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="close()"
             class="fixed inset-0 bg-black/40 backdrop-blur-sm"></div>

        <!-- Modal Content -->
        <div x-show="show"
             x-transition:enter="transition ease-out duration-300 delay-100"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             class="relative w-full max-w-lg bg-[var(--md-sys-color-surface)] rounded-[28px] shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
             dir="rtl"
             @keydown.escape.window="close()">

            <!-- Header -->
            <div class="px-6 py-4 flex items-center justify-between border-b border-[var(--md-sys-color-outline-variant)] bg-[var(--md-sys-color-surface-container-low)]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] flex items-center justify-center shrink-0 shadow-inner">
                        <span class="material-symbols-rounded">badge</span>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-[var(--md-sys-color-on-surface)]" x-text="'درباره ' + (user?.name || '')"></h3>
                        <p class="text-xs text-[var(--md-sys-color-on-surface-variant)]" x-text="user?.position || ''"></p>
                    </div>
                </div>

                <button @click="close()" class="p-2 rounded-full hover:bg-[var(--md-sys-color-on-surface)]/10 text-[var(--md-sys-color-on-surface-variant)] transition-colors">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>

            <!-- Body (Scrollable) -->
            <div class="p-6 overflow-y-auto custom-scrollbar flex-1 bg-[var(--md-sys-color-surface)]">

                <!-- Welcome/Intro Card -->
                <div x-show="aboutMe.bio" class="relative bg-[var(--md-sys-color-surface-container)] rounded-2xl p-5 mb-6 border border-[var(--md-sys-color-outline-variant)]">
                    <div class="absolute -top-3 -right-2 bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] text-xs font-bold px-3 py-1 rounded-full shadow-sm">
                        من همانم که...
                    </div>
                    <p class="text-sm leading-loose text-justify text-[var(--md-sys-color-on-surface-variant)] mt-2" x-text="aboutMe.bio"></p>
                </div>

                <!-- Interests Timeline / List -->
                <div class="relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-[var(--md-sys-color-outline-variant)] before:to-transparent">

                    <template x-if="aboutMe.movies">
                        <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group mb-8">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] border-4 border-[var(--md-sys-color-surface)] shadow-sm shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 z-10 transition-transform hover:scale-110">
                                <span class="material-symbols-rounded text-xl">movie</span>
                            </div>
                            <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] p-4 rounded-xl bg-[var(--md-sys-color-surface-container-lowest)] border border-[var(--md-sys-color-outline-variant)]/50 shadow-sm text-sm text-[var(--md-sys-color-on-surface)]">
                                <span x-text="aboutMe.movies"></span>
                            </div>
                        </div>
                    </template>

                    <template x-if="aboutMe.music">
                        <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group mb-8">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-[var(--md-sys-color-secondary-container)] text-[var(--md-sys-color-on-secondary-container)] border-4 border-[var(--md-sys-color-surface)] shadow-sm shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 z-10 transition-transform hover:scale-110">
                                <span class="material-symbols-rounded text-xl">headphones</span>
                            </div>
                            <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] p-4 rounded-xl bg-[var(--md-sys-color-surface-container-lowest)] border border-[var(--md-sys-color-outline-variant)]/50 shadow-sm text-sm text-[var(--md-sys-color-on-surface)]">
                                <span x-text="aboutMe.music"></span>
                            </div>
                        </div>
                    </template>

                    <template x-if="aboutMe.hobbies">
                        <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group mb-8">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-[var(--md-sys-color-tertiary-container)] text-[var(--md-sys-color-on-tertiary-container)] border-4 border-[var(--md-sys-color-surface)] shadow-sm shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 z-10 transition-transform hover:scale-110">
                                <span class="material-symbols-rounded text-xl">palette</span>
                            </div>
                            <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] p-4 rounded-xl bg-[var(--md-sys-color-surface-container-lowest)] border border-[var(--md-sys-color-outline-variant)]/50 shadow-sm text-sm text-[var(--md-sys-color-on-surface)]">
                                <span x-text="aboutMe.hobbies"></span>
                            </div>
                        </div>
                    </template>

                    <template x-if="aboutMe.food">
                        <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group mb-8">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 border-4 border-[var(--md-sys-color-surface)] shadow-sm shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 z-10 transition-transform hover:scale-110">
                                <span class="material-symbols-rounded text-xl">restaurant</span>
                            </div>
                            <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] p-4 rounded-xl bg-[var(--md-sys-color-surface-container-lowest)] border border-[var(--md-sys-color-outline-variant)]/50 shadow-sm text-sm text-[var(--md-sys-color-on-surface)]">
                                <span x-text="aboutMe.food"></span>
                            </div>
                        </div>
                    </template>

                    <template x-if="aboutMe.sports">
                        <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group mb-8">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border-4 border-[var(--md-sys-color-surface)] shadow-sm shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 z-10 transition-transform hover:scale-110">
                                <span class="material-symbols-rounded text-xl">directions_bike</span>
                            </div>
                            <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] p-4 rounded-xl bg-[var(--md-sys-color-surface-container-lowest)] border border-[var(--md-sys-color-outline-variant)]/50 shadow-sm text-sm text-[var(--md-sys-color-on-surface)]">
                                <span x-text="aboutMe.sports"></span>
                            </div>
                        </div>
                    </template>

                </div>

                <div x-show="!aboutMe.bio && !aboutMe.movies && !aboutMe.music && !aboutMe.hobbies && !aboutMe.food && !aboutMe.sports"
                     class="flex flex-col items-center justify-center py-12 text-[var(--md-sys-color-on-surface-variant)]/50">
                     <span class="material-symbols-rounded text-5xl mb-3">sentiment_dissatisfied</span>
                     <p class="text-sm font-medium">اطلاعاتی ثبت نشده است</p>
                </div>
            </div>

        </div>
    </template>
</div>
