<template x-teleport="body">
    <div x-show="menuOpen" x-cloak>
        <div @keydown.window.escape="closeMenu"
             class="transition-all duration-1000 fixed inset-0 z-[100] flex items-start justify-center pt-2 px-2 pb-0 sm:items-center sm:p-6 animate-slide-down bg-[var(--md-sys-color-primary)]/60"
             role="dialog" aria-modal="true">
            <div class="w-full h-full sm:h-auto sm:w-[920px] sm:max-w-[95%] bg-[var(--md-sys-color-surface)] rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col">
                <div dir="rtl" class="flex flex-col h-full">

                    <div class="relative px-5 py-5 bg-[var(--md-sys-color-primary)] border-b border-white/5 shrink-0">
                        <button @click="closeMenu"
                                class="absolute left-4 top-4 w-10 h-10 flex items-center justify-center rounded-xl bg-white/95 hover:bg-white active:scale-95 shadow-lg hover:shadow-xl transition-all duration-200"
                                aria-label="بستن منو">
                            <span class="material-symbols-rounded text-[20px] text-[var(--md-sys-color-on-surface)]">close</span>
                        </button>
                        <div class="flex items-center gap-3">
                            <div class="w-14 h-14 rounded-2xl bg-[var(--md-sys-color-primary-container)] flex items-center justify-center shadow-md overflow-hidden shrink-0">
                                <x-ui.avatar :image="null" :existingImage="auth()->user()?->getProfileImageUrl() ?? auth()->user()?->getInitialsAvatarUrl()" class="rounded-lg" />

                            </div>
                            <div class="flex flex-col">
                                <div class="text-lg font-bold text-white leading-tight">{{ auth()->user()?->name ?? 'کاربر سیستم' }}</div>
                                <div class="text-sm text-white/80">{{ auth()->user()?->email ?? 'user@hrms.com' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar">
                        <div class="p-4 sm:p-6 pb-24 sm:pb-6 flex flex-col justify-center min-h-full">
                            <div class="w-full max-w-4xl mx-auto">

                                <div dir="ltr" class="overflow-hidden rounded-xl">
                                    <div class="flex transition-transform duration-300 ease-out"
                                         :style="`width: ${paginatedData.length * 100}%; transform: translateX(-${current * (100 / paginatedData.length)}%)`">
                                        <template x-for="(pageItems, pIndex) in paginatedData" :key="pIndex">
                                            <div class="flex-shrink-0 px-0.5" :style="`width: ${100 / paginatedData.length}%`">
                                                {{-- restore RTL for the grid content --}}
                                                <div dir="rtl" class="grid grid-cols-3 sm:grid-cols-4 gap-2 sm:gap-3">
                                                    <template x-for="item in pageItems" :key="item.id">
                                                        <a :href="item.disabled ? '#' : (item.href === '-' ? '#' : item.href)"
                                                           :data-module="item.module"
                                                           :target="item.disabled || item.href === '-' ? '_self' : '_blank'"
                                                           rel="noopener"
                                                           :aria-disabled="item.disabled ? 'true' : 'false'"
                                                           :title="item.disabled ? 'این گزینه در حال حاضر توسط مدیریت غیرفعال شده است' : ''"
                                                           @click="handleItemClick(item, $event)"
                                                           class="group flex flex-col items-center gap-2 p-3 rounded-2xl bg-[var(--md-sys-color-surface-container-low)] active:scale-[0.96] transition-all duration-200 h-[110px] sm:h-[130px] justify-center border border-transparent"
                                                           :class="item.disabled
                                                                ? 'opacity-40 grayscale-[35%] cursor-not-allowed hover:bg-[var(--md-sys-color-surface-container-low)]'
                                                                : 'hover:bg-[var(--md-sys-color-surface-container)] hover:border-[var(--md-sys-color-outline-variant)]/20 cursor-pointer'">
                                                            <div class="relative w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center bg-gradient-to-tr from-[var(--md-sys-color-primary)]/10 to-[var(--md-sys-color-primary)]/5 border border-[var(--md-sys-color-primary)]/10 shadow-sm transition-all duration-300"
                                                                 :class="item.disabled ? '' : 'group-hover:shadow-md group-hover:scale-110'">
                                                                <span class="material-symbols-rounded text-[22px] sm:text-[24px] text-[var(--md-sys-color-primary)]" x-text="item.icon"></span>
                                                                <template x-if="@js($menuState)[item.id]">
                                                                    <x-ui.notification-badge />
                                                                </template>
                                                                <template x-if="item.disabled">
                                                                    <span class="absolute -bottom-1 -left-1 w-5 h-5 rounded-full bg-[var(--md-sys-color-surface-container-highest)] border border-[var(--md-sys-color-outline-variant)]/30 flex items-center justify-center shadow-sm">
                                                                        <span class="material-symbols-rounded text-[12px] text-[var(--md-sys-color-on-surface-variant)]">lock</span>
                                                                    </span>
                                                                </template>
                                                            </div>
                                                            <div class="text-center w-full" :id="item.id">
                                                                <div class="text-[11px] sm:text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-0.5 truncate px-1" x-text="item.title"></div>
                                                                <div class="text-[10px] text-[var(--md-sys-color-on-surface-variant)] hidden sm:block leading-tight truncate px-1 opacity-80" x-text="item.sub"></div>
                                                            </div>
                                                        </a>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <div class="flex items-center justify-center gap-3 sm:gap-4 mt-5 sm:mt-6"
                                     x-show="paginatedData.length > 1">
                                    <button @click="next" :disabled="current >= paginatedData.length - 1"
                                            class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center bg-[var(--md-sys-color-surface-container)] hover:bg-[var(--md-sys-color-surface-container-high)] active:scale-95 disabled:opacity-30 disabled:cursor-not-allowed transition-all duration-200 shadow-sm hover:shadow-md border border-[var(--md-sys-color-outline-variant)]/20">
                                        <span class="material-symbols-rounded text-xl sm:text-2xl text-[var(--md-sys-color-on-surface)] rtl:-scale-x-100">chevron_left</span>
                                    </button>

                                    <div class="flex gap-2 sm:gap-2.5">
                                        <template x-for="(page, index) in paginatedData" :key="index">
                                            <button @click="current = index"
                                                    class="rounded-full transition-all duration-300"
                                                    :class="current === index
                                                        ? 'w-7 sm:w-8 h-2 sm:h-2.5 bg-[var(--md-sys-color-primary)] shadow-md'
                                                        : 'w-2 sm:w-2.5 h-2 sm:h-2.5 bg-[var(--md-sys-color-outline-variant)]/40 hover:bg-[var(--md-sys-color-outline-variant)]/70 hover:w-4 sm:hover:w-5'">
                                            </button>
                                        </template>
                                    </div>

                                    <button @click="prev" :disabled="current === 0"
                                            class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center bg-[var(--md-sys-color-surface-container)] hover:bg-[var(--md-sys-color-surface-container-high)] active:scale-95 disabled:opacity-30 disabled:cursor-not-allowed transition-all duration-200 shadow-sm hover:shadow-md border border-[var(--md-sys-color-outline-variant)]/20">
                                        <span class="material-symbols-rounded text-xl sm:text-2xl text-[var(--md-sys-color-on-surface)] rtl:-scale-x-100">chevron_right</span>
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="p-3 border-t border-[var(--md-sys-color-outline-variant)]/20 bg-[var(--md-sys-color-surface-container-low)]">
                        <div class="flex justify-between items-center gap-2.5 p-2.5 rounded-lg bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)]/10 shadow-sm">
                            <div class="text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] shrink-0 px-2 py-1 rounded-md bg-[var(--md-sys-color-surface-variant)]/50">
                                v:{{ config('app.version') }}
                            </div>
                            <div class="shrink-0"><livewire:auth.logout-button/></div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>
