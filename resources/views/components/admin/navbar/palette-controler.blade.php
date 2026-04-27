<div class="relative" id="admin-palette-container">
    <button id="admin-palette-toggle"
            type="button"
            class="group relative w-10 h-10 rounded-full hover:bg-[var(--md-sys-color-on-primary)]/10 active:scale-95 transition-all duration-200 flex items-center justify-center">
        <span class="material-symbols-rounded text-[22px]">palette</span>
        <x-ui.modals.tooltip text="شخصی‌سازی ظاهر" position="bottom"/>
    </button>

    <div id="admin-palette-menu"
         class="absolute left-0 mt-3 p-3 rounded-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] shadow-2xl z-50 flex flex-col gap-2 min-w-[72px] animate-slide-down"
         style="display: none;">

        <button id="admin-toggle-mode"
                type="button"
                class="group relative w-10 h-10 rounded-full flex items-center justify-center bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)] hover:brightness-95 transition-all mx-auto">
            <span id="admin-mode-icon" class="material-symbols-rounded text-[22px]">light_mode</span>
            <x-ui.modals.tooltip text="تغییر حالت شب/روز" position="right"/>
        </button>

        <div class="h-px bg-[var(--md-sys-color-outline-variant)] opacity-50"></div>

        <button id="admin-palette-up"
                type="button"
                title="گروه قبلی"
                class="w-6 h-6 rounded-full flex items-center justify-center mx-auto text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] transition-all active:scale-90">
            <span class="material-symbols-rounded text-[16px]">keyboard_arrow_up</span>
        </button>

        <span id="admin-palette-title"
              class="text-[10px] font-medium text-center text-[var(--md-sys-color-on-surface-variant)] tracking-widest leading-none mx-auto opacity-60">تیره</span>

        <div class="h-px bg-[var(--md-sys-color-outline-variant)] opacity-50"></div>

        <div id="admin-palette-colors" class="flex flex-col gap-2"></div>

        <button id="admin-palette-down"
                type="button"
                title="گروه بعدی"
                class="w-6 h-6 rounded-full flex items-center justify-center mx-auto text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] transition-all active:scale-90">
            <span class="material-symbols-rounded text-[16px]">keyboard_arrow_down</span>
        </button>
    </div>
</div>

<script>
    (() => {
        const STATE_KEY = '__AdminPaletteControllerState';

        if (!window[STATE_KEY]) {
            window[STATE_KEY] = {
                page: 0,
                perPage: 5,
                groupTitles: ['تیره', 'میانه', 'روشن'],
                elements: null,
                documentClickBound: false,
            };
        }

        const state = window[STATE_KEY];

        const getElements = () => {
            const container = document.getElementById('admin-palette-container');
            if (!container) {
                return null;
            }

            return {
                container,
                toggleBtn: document.getElementById('admin-palette-toggle'),
                menu: document.getElementById('admin-palette-menu'),
                colorsContainer: document.getElementById('admin-palette-colors'),
                upBtn: document.getElementById('admin-palette-up'),
                downBtn: document.getElementById('admin-palette-down'),
                titleSpan: document.getElementById('admin-palette-title'),
                modeBtn: document.getElementById('admin-toggle-mode'),
            };
        };

        const hasAllElements = (elements) => {
            return !!(
                elements &&
                elements.container &&
                elements.toggleBtn &&
                elements.menu &&
                elements.colorsContainer &&
                elements.upBtn &&
                elements.downBtn &&
                elements.titleSpan &&
                elements.modeBtn
            );
        };

        const closeMenu = () => {
            if (!state.elements) {
                return;
            }

            state.elements.menu.style.display = 'none';
            state.elements.toggleBtn.classList.remove('bg-[var(--md-sys-color-on-primary)]/10');
        };

        const openMenu = () => {
            if (!state.elements) {
                return;
            }

            state.elements.menu.style.display = 'flex';
            state.elements.toggleBtn.classList.add('bg-[var(--md-sys-color-on-primary)]/10');
        };

        const renderColors = () => {
            if (!state.elements || !window.AdminThemeManager) {
                return;
            }

            state.elements.titleSpan.textContent = state.groupTitles[state.page];
            state.elements.colorsContainer.innerHTML = '';

            const start = state.page * state.perPage;
            const end = start + state.perPage;

            window.AdminThemeManager.colors.slice(start, end).forEach((color) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'admin-theme-btn relative w-8 h-8 rounded-full mx-auto shadow-md transition-all duration-200 hover:scale-110 active:scale-95 flex items-center justify-center ring-1 ring-black/10';
                btn.style.background = color.color;
                btn.title = color.title;
                btn.dataset.theme = color.name;

                btn.addEventListener('click', () => {
                    window.AdminThemeManager.setTheme(color.name);
                    closeMenu();
                });

                const icon = document.createElement('span');
                icon.className = 'admin-theme-check material-symbols-rounded text-[var(--header-border-color)] !text-sm drop-shadow';
                icon.textContent = 'check';
                icon.style.display = 'none';

                btn.appendChild(icon);
                state.elements.colorsContainer.appendChild(btn);
            });

            window.AdminThemeManager.updateIcons();
        };

        const renderWhenThemeManagerReady = (retry = 0) => {
            if (window.AdminThemeManager) {
                renderColors();
                return;
            }

            if (retry >= 20) {
                return;
            }

            setTimeout(() => renderWhenThemeManagerReady(retry + 1), 50);
        };

        const bindEvents = () => {
            if (!state.elements) {
                return;
            }

            state.elements.toggleBtn.onclick = (event) => {
                event.stopPropagation();
                if (state.elements.menu.style.display === 'none') {
                    openMenu();
                    return;
                }

                closeMenu();
            };

            state.elements.modeBtn.onclick = () => {
                if (!window.AdminThemeManager) {
                    return;
                }

                window.AdminThemeManager.toggleMode();
            };

            state.elements.upBtn.onclick = () => {
                state.page = (state.page - 1 + state.groupTitles.length) % state.groupTitles.length;
                renderColors();
            };

            state.elements.downBtn.onclick = () => {
                state.page = (state.page + 1) % state.groupTitles.length;
                renderColors();
            };

            if (!state.documentClickBound) {
                document.addEventListener('click', (event) => {
                    if (!state.elements) {
                        return;
                    }

                    const { menu, toggleBtn } = state.elements;
                    if (!menu.contains(event.target) && !toggleBtn.contains(event.target)) {
                        closeMenu();
                    }
                });

                state.documentClickBound = true;
            }
        };

        const initAdminPalette = () => {
            const elements = getElements();
            if (!hasAllElements(elements)) {
                return;
            }

            state.elements = elements;
            bindEvents();
            renderWhenThemeManagerReady();
        };

        document.addEventListener('DOMContentLoaded', initAdminPalette);
        document.addEventListener('livewire:navigated', initAdminPalette);
    })();
</script>
