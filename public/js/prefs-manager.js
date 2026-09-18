(() => {
    try {
        const ls = localStorage;
        document.documentElement.classList.toggle('app-density-compact', ls.getItem('app-density') === 'compact');

        const keys = ['dms-col-hidden', 'ths-col-hidden', 'reservation-col-hidden'];
        const tables = ['.dms-doc-table', '.ths-ticket-table', '.reservation-command-table'];
        let css = '';

        for (let i = 0; i < 3; i++) {
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
