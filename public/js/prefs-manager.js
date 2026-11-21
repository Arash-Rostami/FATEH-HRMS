(() => {
    try {
        const ls = localStorage;
        document.documentElement.classList.toggle('app-density-compact', ls.getItem('app-density') === 'compact');

        const tone = ls.getItem('bgTone');
        const hueMatch = /^hue:(\d{1,3})$/.exec(tone || '');
        if (['snow', 'pearl', 'mist', 'ash'].includes(tone)) {
            document.documentElement.dataset.bgTone = tone;
        } else if (hueMatch && Number(hueMatch[1]) <= 359) {
            document.documentElement.dataset.bgTone = 'hue';
            document.documentElement.style.setProperty('--tone-h', hueMatch[1]);
        }

        const applyDeclutter = () => document.documentElement.classList.toggle('declutter', ls.getItem('menu_declutter') === 'true');
        applyDeclutter();
        document.addEventListener('livewire:navigated', applyDeclutter);

        const maxScope = document.documentElement.dataset.maxScope;
        const uid = document.documentElement.dataset.uid;
        if (maxScope && uid && window.self === window.top) {
            document.documentElement.classList.toggle('max-restore', ls.getItem(`max:${maxScope}:${uid}`) !== null);
        }

        const keys = ['dms-col-hidden', 'ths-col-hidden', 'reservation-col-hidden', 'taskboard-col-hidden', 'ai-report-col-hidden', 'correspondence-col-hidden', 'launchpad-col-hidden', 'media-col-hidden'];
        const tables = ['.dms-doc-table', '.ths-ticket-table', '.reservation-command-table', '.taskboard-card', '.ai-report-table', '.correspondence-table', '.launchpad-table', '.media-episode-table'];
        let css = '';

        for (let i = 0; i < keys.length; i++) {
            const val = ls.getItem(keys[i]);

            if (val !== null && val.length > 2) {
                try {
                    const hidden = JSON.parse(val);

                    if (Array.isArray(hidden)) {
                        const len = hidden.length;

                        if (len > 0) {
                            const prefix = tables[i] + ' [data-col="';
                            for (let j = 0; j < len; j++) {
                                const col = hidden[j];
                                if (typeof col === 'string') {
                                    css += prefix + col + '"]{display:none!important}';
                                }
                            }
                        }
                    }
                } catch {}
            }
        }

        if (css !== '') {
            const style = document.createElement('style');
            style.id = 'dms-col-visibility-style';
            style.textContent = css;
            document.head.appendChild(style);
        }
    } catch {}
})();
