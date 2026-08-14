(() => {
    const doc = document.documentElement;

    doc.classList.toggle('app-density-compact', localStorage.getItem('app-density') === 'compact');

    try {
        const hidden = JSON.parse(localStorage.getItem('dms-col-hidden'));
        if (Array.isArray(hidden) && hidden.length) {
            const css = hidden
                .filter(col => typeof col === 'string')
                .map(col => `.dms-doc-table [data-col="${col}"]{display:none!important}`)
                .join('');

            const style = document.createElement('style');
            style.id = 'dms-col-visibility-style';
            style.textContent = css;
            document.head.appendChild(style);
        }
    } catch {}
})();
