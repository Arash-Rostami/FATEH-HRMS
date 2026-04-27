<div class="relative" id="admin-palette-container">
    <button id="admin-palette-toggle"
            class="group relative w-10 h-10 rounded-full hover:bg-[var(--md-sys-color-on-primary)]/10 active:scale-95 transition-all duration-200 flex items-center justify-center">
        <span class="material-symbols-rounded text-[22px]">palette</span>
        <x-ui.modals.tooltip text="شخصی‌سازی ظاهر" position="bottom"/>
    </button>

    <div id="admin-palette-menu"
         class="absolute left-0 mt-3 p-3 rounded-2xl bg-[var(--md-sys-color-surface)] border border-[var(--md-sys-color-outline-variant)] shadow-2xl z-50 flex flex-col gap-2 min-w-[72px] animate-slide-down"
         style="display: none;">

        <button onclick="window.AdminThemeManager.toggleMode()"
                class="group relative w-10 h-10 rounded-full flex items-center justify-center bg-[var(--md-sys-color-surface-container-high)] text-[var(--md-sys-color-on-surface)] hover:brightness-95 transition-all mx-auto">
            <span id="admin-mode-icon" class="material-symbols-rounded text-[22px]">light_mode</span>
            <x-ui.modals.tooltip text="تغییر حالت شب/روز" position="right"/>
        </button>

        <div class="h-px bg-[var(--md-sys-color-outline-variant)] opacity-50"></div>

        <button id="admin-palette-up" title="گروه قبلی"
                class="w-6 h-6 rounded-full flex items-center justify-center mx-auto text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] transition-all active:scale-90">
            <span class="material-symbols-rounded text-[16px]">keyboard_arrow_up</span>
        </button>

        <span id="admin-palette-title"
              class="text-[10px] font-medium text-center text-[var(--md-sys-color-on-surface-variant)] tracking-widest leading-none mx-auto opacity-60">تیره</span>

        <div class="h-px bg-[var(--md-sys-color-outline-variant)] opacity-50"></div>

        <div id="admin-palette-colors" class="flex flex-col gap-2"></div>

        <button id="admin-palette-down" title="گروه بعدی"
                class="w-6 h-6 rounded-full flex items-center justify-center mx-auto text-[var(--md-sys-color-on-surface-variant)] hover:bg-[var(--md-sys-color-surface-container-high)] transition-all active:scale-90">
            <span class="material-symbols-rounded text-[16px]">keyboard_arrow_down</span>
        </button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggleBtn = document.getElementById('admin-palette-toggle');
        const menu = document.getElementById('admin-palette-menu');
        const colorsContainer = document.getElementById('admin-palette-colors');
        const upBtn = document.getElementById('admin-palette-up');
        const downBtn = document.getElementById('admin-palette-down');
        const titleSpan = document.getElementById('admin-palette-title');

        let page = 0;
        const perPage = 5;
        const groupTitles = ['تیره', 'میانه', 'روشن'];

        // Toggle Menu
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (menu.style.display === 'none') {
                menu.style.display = 'flex';
                toggleBtn.classList.add('bg-[var(--md-sys-color-on-primary)]/10');
            } else {
                menu.style.display = 'none';
                toggleBtn.classList.remove('bg-[var(--md-sys-color-on-primary)]/10');
            }
        });

        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (!menu.contains(e.target) && !toggleBtn.contains(e.target)) {
                menu.style.display = 'none';
                toggleBtn.classList.remove('bg-[var(--md-sys-color-on-primary)]/10');
            }
        });

        const renderColors = () => {
            if (!window.AdminThemeManager) return;

            titleSpan.textContent = groupTitles[page];
            colorsContainer.innerHTML = '';

            const colors = window.AdminThemeManager.colors.slice(page * perPage, page * perPage + perPage);

            colors.forEach(color => {
                const btn = document.createElement('button');
                btn.className = 'admin-theme-btn relative w-8 h-8 rounded-full mx-auto shadow-md transition-all duration-200 hover:scale-110 active:scale-95 flex items-center justify-center ring-1 ring-black/10';
                btn.style.background = color.color;
                btn.title = color.title;
                btn.dataset.theme = color.name;

                btn.onclick = () => {
                    window.AdminThemeManager.setTheme(color.name);
                    menu.style.display = 'none';
                    toggleBtn.classList.remove('bg-[var(--md-sys-color-on-primary)]/10');
                };

                const span = document.createElement('span');
                span.className = 'admin-theme-check material-symbols-rounded text-[var(--header-border-color)] !text-sm drop-shadow';
                span.textContent = 'check';
                span.style.display = 'none';

                btn.appendChild(span);
                colorsContainer.appendChild(btn);
            });

            window.AdminThemeManager.updateIcons();
        };

        upBtn.onclick = () => {
            page = (page - 1 + groupTitles.length) % groupTitles.length;
            renderColors();
        };

        downBtn.onclick = () => {
            page = (page + 1) % groupTitles.length;
            renderColors();
        };

        // Delay render slightly to ensure AdminThemeManager is attached
        setTimeout(renderColors, 50);
    });
</script>
