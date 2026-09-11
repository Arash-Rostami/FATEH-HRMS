<template x-teleport="body">
    <div x-show="menuOpen" x-cloak>
        <div @click.self="closeMenu"
             @keydown.window="handleGlobalKeydown($event)"
             class="transition-all duration-1000 fixed inset-0 z-[100] flex items-start justify-center pt-2 px-2 pb-0 sm:items-center sm:p-6 animate-slide-down bg-[var(--md-sys-color-primary)]/60"
             role="dialog"
             aria-modal="true"
             aria-labelledby="main-menu-title">

            <div class="w-full h-full sm:h-[680px] sm:max-h-[88vh] sm:w-[920px] sm:max-w-[95%] bg-[var(--md-sys-color-surface)] border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_60%,transparent)] rounded-t-3xl sm:rounded-3xl shadow-[0_16px_48px_color-mix(in_srgb,var(--md-sys-color-primary)_25%,transparent)] dark:shadow-[0_12px_40px_rgba(0,0,0,0.6)] overflow-hidden flex flex-col"
                 @click.stop
                 @touchstart="touchStartX = $event.changedTouches[0].screenX"
                 @touchend="touchEndX = $event.changedTouches[0].screenX; handleSwipe()">

                <div dir="rtl" class="flex flex-col h-full">

                    <div class="px-4 sm:px-6 py-2 sm:py-5 bg-gradient-to-l from-[var(--md-sys-color-primary)] to-[color-mix(in_srgb,var(--md-sys-color-primary)_85%,transparent)] text-[var(--md-sys-color-on-primary)] border-b border-[color-mix(in_srgb,var(--md-sys-color-on-primary)_15%,transparent)] shrink-0 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 sm:gap-4 flex-1 min-w-0">
                            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_15%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-on-primary)_25%,transparent)] flex items-center justify-center shadow-md overflow-hidden shrink-0">
                                <x-ui.avatar :image="null" :existingImage="auth()->user()?->getProfileImageUrl() ?? auth()->user()?->getInitialsAvatarUrl()" class="rounded-xl w-full h-full object-cover" />
                            </div>
                            <div class="flex flex-col flex-1 min-w-0">
                                <h2 id="main-menu-title" class="text-[15px] sm:text-lg font-bold text-[var(--md-sys-color-on-primary)] leading-tight truncate">{{ auth()->user()?->name ?? 'کاربر سیستم' }}</h2>
                                <div class="text-[11px] sm:text-sm text-[color-mix(in_srgb,var(--md-sys-color-on-primary)_80%,transparent)] truncate mt-0.5">{{ auth()->user()?->email ?? 'user@hrms.com' }}</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">

                            <x-ui.buttons.view-toggle
                                state="viewMode"
                                action=""
                                responsive
                                :modes="[
                                    ['value' => 'grid', 'icon' => 'grid_view', 'title' => 'نمای شبکه‌ای'],
                                    ['value' => 'list', 'icon' => 'view_list', 'title' => 'نمای لیستی'],
                                    ['value' => 'grouped', 'icon' => 'category', 'title' => 'نمای دسته‌بندی'],
                                ]"
                            />

                            <button @click="typeof toggleSortAlpha === 'function' ? toggleSortAlpha() : null"
                                    class="w-8 h-8 shrink-0 flex items-center justify-center rounded-lg transition-all duration-200 focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-on-primary)] focus-visible:outline-none border"
                                    :class="(typeof sortAlpha !== 'undefined' && sortAlpha)
                                        ? 'bg-[var(--md-sys-color-on-primary)] text-[var(--md-sys-color-primary)] border-transparent shadow-md'
                                        : 'bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_15%,transparent)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_25%,transparent)] text-[var(--md-sys-color-on-primary)] border-[color-mix(in_srgb,var(--md-sys-color-on-primary)_20%,transparent)]'"
                                    title="مرتب‌سازی الفبایی">
                                <span class="material-symbols-rounded text-[16px]">sort_by_alpha</span>
                            </button>

                            <button @click="typeof togglePinEdit === 'function' ? togglePinEdit() : null"
                                    class="w-8 h-8 shrink-0 flex items-center justify-center rounded-lg transition-all duration-200 focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-on-primary)] focus-visible:outline-none border"
                                    :class="pinEditMode
                                        ? 'bg-[var(--md-sys-color-on-primary)] text-[var(--md-sys-color-primary)] border-transparent shadow-md'
                                        : 'bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_15%,transparent)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_25%,transparent)] text-[var(--md-sys-color-on-primary)] border-[color-mix(in_srgb,var(--md-sys-color-on-primary)_20%,transparent)]'"
                                    :aria-pressed="pinEditMode"
                                    :title="pinEditMode ? 'پایان حالت سنجاق' : 'سنجاق‌کردن ماژول‌ها'">
                                <span class="material-symbols-rounded text-[16px] inline-block transition-transform duration-200"
                                      :class="pinEditMode ? 'font-fill rotate-45' : ''">push_pin</span>
                            </button>

                            <button @click="typeof toggleRecentOnly === 'function' ? toggleRecentOnly() : null"
                                    class="w-8 h-8 shrink-0 flex items-center justify-center rounded-lg transition-all duration-200 focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-on-primary)] focus-visible:outline-none border"
                                    :class="(typeof showRecentOnly !== 'undefined' && showRecentOnly)
                                        ? 'bg-[var(--md-sys-color-on-primary)] text-[var(--md-sys-color-primary)] border-transparent shadow-md'
                                        : 'bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_15%,transparent)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_25%,transparent)] text-[var(--md-sys-color-on-primary)] border-[color-mix(in_srgb,var(--md-sys-color-on-primary)_20%,transparent)]'"
                                    title="ماژول‌های اخیر">
                                <span class="material-symbols-rounded text-[16px]">history</span>
                            </button>

                            <div class="relative w-24 sm:w-56 shrink-0 group">
                                <span class="material-symbols-rounded absolute right-2 top-1/2 -translate-y-1/2 text-[13px] text-[color-mix(in_srgb,var(--md-sys-color-on-primary)_70%,transparent)] group-focus-within:text-[var(--md-sys-color-on-primary)] transition-colors pointer-events-none">search</span>
                                <input x-ref="searchInput"
                                       x-model="search"
                                       type="text"
                                       title="جستجوی سریع ماژول‌ها و ابزارها... (کلید /)"
                                       placeholder=" جستجو..."
                                       class="w-full h-8 pr-7 pl-6 rounded-lg bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_10%,transparent)] border border-[color-mix(in_srgb,var(--md-sys-color-on-primary)_20%,transparent)] text-[11px] text-[var(--md-sys-color-on-primary)] placeholder-[color-mix(in_srgb,var(--md-sys-color-on-primary)_60%,transparent)] outline-none focus:ring-2 focus:ring-[color-mix(in_srgb,var(--md-sys-color-on-primary)_55%,transparent)] focus:border-transparent focus:bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_16%,transparent)] transition-all duration-200">
                                <button x-show="search"
                                        @click="search = ''; $refs.searchInput.focus()"
                                        class="absolute left-1 top-1/2 -translate-y-1/2 w-5 h-5 rounded-full flex items-center justify-center text-[color-mix(in_srgb,var(--md-sys-color-on-primary)_75%,transparent)] hover:text-[var(--md-sys-color-on-primary)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_15%,transparent)] transition-all">
                                    <span class="material-symbols-rounded text-[12px]">close</span>
                                </button>
                            </div>

                            <button @click="closeMenu"
                                    class="w-8 h-8 shrink-0 flex items-center justify-center rounded-lg bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_15%,transparent)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-on-primary)_25%,transparent)] text-[var(--md-sys-color-on-primary)] active:scale-95 border border-[color-mix(in_srgb,var(--md-sys-color-on-primary)_20%,transparent)] transition-all duration-200 focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-on-primary)] focus-visible:outline-none"
                                    aria-label="بستن منو">
                                <span class="material-symbols-rounded text-[16px]">close</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar">
                        <div class="p-4 sm:p-6 pb-4 flex flex-col min-h-full">
                            <div class="w-full max-w-4xl mx-auto flex-1 flex flex-col"
                                 :class="(search.trim() !== '' || (typeof showRecentOnly !== 'undefined' && showRecentOnly)) ? 'justify-start' : 'justify-center'">

                                <div x-show="(search.trim() !== '' || (typeof showRecentOnly !== 'undefined' && showRecentOnly)) && allVisibleItems.length === 0" class="py-12 text-center">
                                    <span class="material-symbols-rounded text-5xl text-[var(--md-sys-color-on-surface-variant)]/40 mb-2" x-text="(typeof showRecentOnly !== 'undefined' && showRecentOnly) ? 'history_toggle_off' : 'search_off'"></span>
                                    <p class="text-[13px] sm:text-sm font-medium text-[var(--md-sys-color-on-surface-variant)]" x-text="(typeof showRecentOnly !== 'undefined' && showRecentOnly) ? 'هنوز موردی اخیراً باز نشده است.' : 'نتیجه‌ای برای این جستجو یافت نشد.'"></p>
                                    <button x-show="search.trim() !== ''"
                                            @click="closeMenu(); $dispatch('open-command-palette')"
                                            type="button"
                                            class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12px] font-medium text-[var(--md-sys-color-primary)] bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] hover:bg-[color-mix(in_srgb,var(--md-sys-color-primary)_16%,transparent)] transition-colors">
                                        <span class="material-symbols-rounded text-[15px]">search</span>
                                        امتحان پالت فرمان (Ctrl+K)
                                    </button>
                                </div>

                                <template x-if="viewMode === 'list' && allVisibleItems.length > 0">
                                    <div :class="listCols(allVisibleItems.length) === 3 ? 'grid grid-cols-1 sm:grid-cols-3 gap-2' : (listCols(allVisibleItems.length) === 2 ? 'grid grid-cols-1 sm:grid-cols-2 gap-2' : 'flex flex-col gap-2')">
                                        <template x-for="item in allVisibleItems" :key="item.id">
                                            @include('components.dashboard.modal.menu.item')
                                        </template>
                                    </div>
                                </template>

                                <template x-if="viewMode === 'grouped' && allVisibleItems.length > 0">
                                    <div class="flex flex-col gap-4">
                                        <template x-for="group in groupedItems" :key="group.label">
                                            <div>
                                                <div class="flex items-center gap-2 px-1 mb-1.5">
                                                    <span class="text-[11px] font-bold tracking-wide text-[var(--md-sys-color-primary)]" x-text="group.label"></span>
                                                    <span class="text-[10px] font-medium text-[var(--md-sys-color-on-surface-variant)] opacity-70" x-text="group.items.length"></span>
                                                    <div class="flex-1 h-px bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_25%,transparent)]"></div>
                                                </div>
                                                <div :class="listCols(group.items.length) === 3 ? 'grid grid-cols-1 sm:grid-cols-3 gap-2' : (listCols(group.items.length) === 2 ? 'grid grid-cols-1 sm:grid-cols-2 gap-2' : 'flex flex-col gap-2')">
                                                    <template x-for="item in group.items" :key="item.id">
                                                        @include('components.dashboard.modal.menu.item')
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="viewMode === 'grid'">
                                <div class="contents">
                                <div dir="ltr" class="overflow-hidden rounded-2xl w-full flex-1 flex flex-col">
                                    <div class="flex transition-transform duration-300 ease-out will-change-transform flex-1"
                                         :style="`width: ${activePages.length * 100}%; transform: translateX(-${activeIndex * (100 / activePages.length)}%)`">

                                        <template x-for="(pageItems, pIndex) in activePages" :key="pIndex">
                                            <div class="flex-shrink-0 px-1 h-full" :style="`width: ${100 / activePages.length}%`">
                                                <div dir="rtl" class="grid grid-cols-3 sm:grid-cols-4 gap-2 sm:gap-2.5 h-full auto-rows-fr">
                                                    <template x-for="item in pageItems" :key="item.id">

                                                        <a :href="item.disabled ? '#' : (item.href === '-' ? '#' : item.href)"
                                                           :data-module="item.module"
                                                           :id="item.id"
                                                           :target="item.disabled || item.href === '-' ? '_self' : '_blank'"
                                                           rel="noopener"
                                                           :aria-disabled="item.disabled ? 'true' : 'false'"
                                                           :title="item.disabled ? 'این گزینه در حال حاضر توسط مدیریت غیرفعال شده است' : ''"
                                                           @click="handleItemClick(item, $event)"
                                                           class="group relative flex flex-col items-center gap-2 p-3 rounded-2xl active:scale-[0.96] transition-all duration-200 justify-center focus-visible:outline-none min-h-0"
                                                           :class="[
                                                               item.disabled
                                                                   ? 'bg-[var(--md-sys-color-surface-container-low)] opacity-40 grayscale-[40%] cursor-not-allowed'
                                                                   : 'bg-[var(--md-sys-color-surface-container-low)] hover:bg-[var(--md-sys-color-surface-container)] border border-transparent hover:border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] hover:shadow-[0_8px_20px_color-mix(in_srgb,var(--md-sys-color-primary)_12%,transparent)] hover:-translate-y-0.5 cursor-pointer',
                                                               (typeof focusIndex !== 'undefined' && focusIndex === item.id)
                                                                   ? 'ring-2 ring-inset ring-[var(--md-sys-color-primary)] bg-[var(--md-sys-color-surface-container)]'
                                                                   : ''
                                                           ]">

                                                            <button x-show="pinEditMode || isPinned(item)"
                                                                    @click="togglePin(item, $event)"
                                                                    class="absolute top-1 end-1 z-10 w-6 h-6 rounded-full flex items-center justify-center transition-all duration-200 active:scale-95 focus-visible:outline-none"
                                                                    :class="isPinned(item)
                                                                        ? 'bg-[var(--md-sys-color-primary-container)] text-[var(--md-sys-color-on-primary-container)] shadow-sm'
                                                                        : 'bg-[var(--md-sys-color-surface-container)] text-[var(--md-sys-color-primary)] hover:bg-[var(--md-sys-color-surface-container-high)]'"
                                                                    :aria-pressed="isPinned(item)"
                                                                    :title="isPinned(item) ? 'برداشتن سنجاق از صفحه اول' : 'سنجاق در صفحه اول'"
                                                                    :aria-label="isPinned(item) ? 'برداشتن سنجاق از صفحه اول' : 'سنجاق در صفحه اول'">
                                                                <span class="material-symbols-rounded text-[13px] inline-block transition-transform duration-200"
                                                                      :class="isPinned(item) ? 'font-fill rotate-45' : ''">push_pin</span>
                                                            </button>

                                                            <div class="relative w-11 h-11 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center bg-[color-mix(in_srgb,var(--md-sys-color-primary)_8%,var(--md-sys-color-surface))] border border-[color-mix(in_srgb,var(--md-sys-color-primary)_15%,transparent)] shadow-sm transition-all duration-200"
                                                                 :class="[item.disabled ? '' : 'group-hover:scale-110 group-hover:bg-[var(--md-sys-color-primary)] group-hover:shadow-[0_4px_14px_color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)]', pinEditMode ? 'animate-shiver' : '']">
                                                                <span class="material-symbols-rounded text-[20px] sm:text-[22px] text-[var(--md-sys-color-primary)] transition-colors duration-200"
                                                                      :class="item.disabled ? '' : 'group-hover:text-[var(--md-sys-color-on-primary)]'"
                                                                      x-text="item.icon"></span>
                                                                <template x-if="@js($menuState)[item.id]">
                                                                    <x-ui.notification-badge />
                                                                </template>
                                                                <template x-if="item.disabled">
                                                                    <span class="absolute -bottom-1 -left-1 w-4 h-4 rounded-md bg-[var(--md-sys-color-surface-container-highest)] flex items-center justify-center shadow-sm">
                                                                        <span class="material-symbols-rounded text-[10px] text-[var(--md-sys-color-on-surface-variant)]">lock</span>
                                                                    </span>
                                                                </template>
                                                            </div>
                                                            <div class="text-center w-full">
                                                                <div class="text-[13px] sm:text-sm font-bold text-[var(--md-sys-color-on-surface)] mb-0.5 truncate px-1 transition-colors duration-200"
                                                                     :class="item.disabled ? '' : 'group-hover:text-[var(--md-sys-color-primary)]'"
                                                                     x-html="highlight(item.title, search)"></div>
                                                                <div class="text-[11px] text-[var(--md-sys-color-on-surface-variant)] hidden sm:block leading-tight truncate px-1 opacity-75"
                                                                     x-html="highlight(item.sub, search)"></div>
                                                            </div>
                                                        </a>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <div class="flex items-center justify-center gap-2 sm:gap-3 pt-2 sm:pt-3">
                                    <button @click="next"
                                            :disabled="current >= activePages.length - 1"
                                            aria-label="صفحه بعد"
                                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center bg-[var(--md-sys-color-surface-container)] hover:bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)] active:scale-95 disabled:opacity-30 disabled:pointer-events-none transition-all duration-200 shadow-sm hover:shadow-md border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]">
                                        <span class="material-symbols-rounded text-lg sm:text-xl rtl:-scale-x-100">chevron_left</span>
                                    </button>

                                    <div class="flex items-center gap-2 sm:gap-2.5">
                                        <template x-for="(page, index) in activePages" :key="index">
                                            <button @click="current = index"
                                                    :aria-label="`صفحه ${index + 1}`"
                                                    class="relative rounded-full transition-all duration-250 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]"
                                                    :class="current === index
                                                        ? 'w-6 sm:w-7 h-1.5 sm:h-2 bg-[var(--md-sys-color-primary)] shadow-[0_2px_8px_color-mix(in_srgb,var(--md-sys-color-primary)_35%,transparent)]'
                                                        : 'w-1.5 sm:w-2 h-1.5 sm:h-2 bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] hover:bg-[var(--md-sys-color-outline)] hover:w-3 sm:hover:w-4'">

                                                <template x-if="typeof pageHasNotification === 'function' && pageHasNotification(index)">
                                                    <span class="absolute -top-1 -right-1 w-2 h-2 rounded-full bg-[var(--md-sys-color-error)] border-2 border-[var(--md-sys-color-surface)]"></span>
                                                </template>

                                            </button>
                                        </template>
                                    </div>

                                    <button @click="prev"
                                            :disabled="current === 0"
                                            aria-label="صفحه قبل"
                                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center bg-[var(--md-sys-color-surface-container)] hover:bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)] active:scale-95 disabled:opacity-30 disabled:pointer-events-none transition-all duration-200 shadow-sm hover:shadow-md border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--md-sys-color-primary)]">
                                        <span class="material-symbols-rounded text-lg sm:text-xl rtl:-scale-x-100">chevron_right</span>
                                    </button>
                                </div>
                                </div>
                                </template>

                            </div>
                        </div>
                    </div>

                    <div class="shrink-0 border-t border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_25%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-container-low)_75%,transparent)] p-3 backdrop-blur-md sm:p-4">
                        <div x-data="{ playerOn: localStorage.getItem('audio_player_visible') === '1' }"
                             class="flex w-full items-center justify-between gap-4 rounded-2xl border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_20%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface)_80%,transparent)] px-4 py-3 shadow-sm backdrop-blur-sm transition-all hover:shadow-md">

                            <!-- Left Side: Version, Notifications, Shortcut Hint -->
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 items-center gap-2 rounded-lg border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_40%,transparent)] pl-2 pr-3 text-[11px] font-semibold tracking-wider text-[var(--md-sys-color-on-surface-variant)] transition-colors hover:bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_60%,transparent)]">
                                    <span>v:{{ config('app.version') }}</span>
                                    <div class="h-4 w-[1px] bg-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)]"></div>
                                    <div class="flex h-5 w-5 scale-75 items-center justify-center">
                                        <x-dashboard.modal.release/>
                                    </div>
                                </div>

                                <div x-show="typeof notifiedCount === 'number' && notifiedCount > 0"
                                     x-transition.opacity
                                     class="hidden h-8 items-center gap-1.5 rounded-lg border border-[color-mix(in_srgb,var(--md-sys-color-primary)_20%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-primary)_10%,transparent)] px-3 text-[11px] font-medium text-[var(--md-sys-color-primary)] sm:flex">
                                    <span class="material-symbols-rounded text-[14px]">notifications</span>
                                    <span x-text="notifiedCount + ' اعلان'"></span>
                                </div>

                                <div class="hidden h-8 items-center gap-2 text-[11px] font-medium text-[var(--md-sys-color-on-surface-variant)] sm:flex">
                                    <kbd class="flex h-5 min-w-[26px] items-center justify-center rounded border border-[color-mix(in_srgb,var(--md-sys-color-outline-variant)_40%,transparent)] bg-[color-mix(in_srgb,var(--md-sys-color-surface-variant)_50%,transparent)] px-1.5 font-mono text-[10px] text-[var(--md-sys-color-on-surface)] shadow-[0_1px_0_color-mix(in_srgb,var(--md-sys-color-outline-variant)_30%,transparent)]">Ctrl</kbd>
                                    <span>+ کلیک = باز نگه‌داشتن</span>
                                </div>
                            </div>

                            <!-- Spacer to push right-side content -->
                            <div class="flex-1"></div>

                            <!-- Right Side: Player, Audio Toggle, Divider, Logout -->
                            <div class="flex shrink-0 items-center gap-3 sm:gap-4">
                                <template x-if="playerOn">
                                    <div x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0 scale-95"
                                         x-transition:enter-end="opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-150"
                                         x-transition:leave-start="opacity-100 scale-100"
                                         x-transition:leave-end="opacity-0 scale-95"
                                         class="flex items-center">
                                        <x-dashboard.modal.menu.player/>
                                    </div>
                                </template>

                                <div class="flex items-center">
                                    <x-dashboard.modal.menu.audio-toggle/>
                                </div>

                                <!-- Sleek Modern Divider -->
                                <div class="h-6 w-[1px] rounded-full bg-[var(--md-sys-color-primary-container)]"></div>

                                <div class="flex items-center">
                                    <livewire:auth.logout-button/>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>
