let booted = false;

export default function initTooltip() {
    if (booted) return;
    booted = true;

    const tip = document.createElement('div');
    tip.className = 'fixed z-[9999] pointer-events-none whitespace-nowrap max-w-[85vw] overflow-hidden text-ellipsis px-3 py-1.5 rounded-lg bg-[var(--md-sys-color-primary)] text-[var(--md-sys-color-on-primary)] text-[12px] font-medium tracking-wide border border-[var(--md-sys-color-outline)]/10 shadow-[0_4px_6px_-1px_rgba(0,0,0,0.1),0_2px_4px_-1px_rgba(0,0,0,0.06)] opacity-0 transition-[opacity,transform] duration-300 ease-out';
    tip.setAttribute('role', 'tooltip');

    const GAP = 12;
    const PAD = 8;
    const NUDGE = { left: [8, 0], right: [-8, 0], top: [0, 8], bottom: [0, -8] };
    const groupTips = new WeakMap();
    let active = null;
    let raf1 = null;
    let raf2 = null;

    const killFrames = () => {
        if (raf1) { cancelAnimationFrame(raf1); raf1 = null; }
        if (raf2) { cancelAnimationFrame(raf2); raf2 = null; }
    };

    const hide = () => {
        active = null;
        tip.style.opacity = '0';
        killFrames();
    };

    const resolve = (target) => {
        let el = target;
        while (el && el !== document.body) {
            let holder = el.querySelector(':scope > [data-tip]');
            if (holder) return { holder, anchor: holder.parentElement, pos: holder.dataset.tipPos || 'bottom' };
            if (el.dataset && el.dataset.title !== undefined) {
                const t = el.getAttribute('title');
                if (t && t.trim()) {
                    el.dataset.title = t.trim();
                    el.removeAttribute('title');
                }
                return { holder: el, anchor: el, pos: 'top' };
            }
            if (el.classList.contains('group')) {
                if (!groupTips.has(el)) groupTips.set(el, el.querySelector('[data-tip]'));
                holder = groupTips.get(el);
                if (holder && !holder.isConnected) {
                    holder = el.querySelector('[data-tip]');
                    groupTips.set(el, holder);
                }
                if (holder) return { holder, anchor: holder.parentElement, pos: holder.dataset.tipPos || 'bottom' };
            }
            if (el.tagName === 'IFRAME') return null;
            const title = el.getAttribute?.('title');
            if (title && title.trim()) {
                el.dataset.title = title.trim();
                el.removeAttribute('title');
                if (!el.hasAttribute('aria-label')) el.setAttribute('aria-label', title.trim());
                return { holder: el, anchor: el, pos: 'top' };
            }
            el = el.parentElement;
        }
        return null;
    };

    const readText = (res) => {
        const h = res.holder;
        return (h.dataset.tip !== undefined ? (h.dataset.tip || h.textContent || '') : (h.dataset.title || '')).trim();
    };

    const show = (res) => {
        const text = readText(res);
        if (!text) return hide();
        active = res;
        if (!tip.isConnected) document.body.appendChild(tip);
        tip.textContent = text;
        tip.style.opacity = '0';
        tip.style.left = '0px';
        tip.style.top = '0px';
        tip.style.transform = 'none';

        const r = res.anchor.getBoundingClientRect();
        const w = tip.offsetWidth;
        const h = tip.offsetHeight;
        const pos = res.pos;
        let x, y;
        if (pos === 'left') { x = r.left - GAP - w; y = r.top + r.height / 2 - h / 2; }
        else if (pos === 'right') { x = r.right + GAP; y = r.top + r.height / 2 - h / 2; }
        else if (pos === 'top') { x = r.left + r.width / 2 - w / 2; y = r.top - GAP - h; }
        else { x = r.left + r.width / 2 - w / 2; y = r.bottom + GAP; }
        let flipped = false;
        if (pos === 'top' && y < PAD) { y = r.bottom + GAP; flipped = true; }
        x = Math.max(PAD, Math.min(x, innerWidth - w - PAD));
        y = Math.max(PAD, Math.min(y, innerHeight - h - PAD));
        tip.style.left = x + 'px';
        tip.style.top = y + 'px';
        const [dx, dy] = flipped ? NUDGE.bottom : (NUDGE[pos] ?? NUDGE.bottom);
        tip.style.transform = `translate(${dx}px, ${dy}px)`;

        killFrames();
        raf1 = requestAnimationFrame(() => {
            raf2 = requestAnimationFrame(() => {
                if (active === res) {
                    const fresh = readText(res);
                    if (fresh) tip.textContent = fresh;
                    tip.style.opacity = '1';
                    tip.style.transform = 'none';
                }
            });
        });
    };

    document.addEventListener('mouseover', (e) => {
        if (active && !active.holder.isConnected) hide();
        const res = resolve(e.target);
        if (!res) return hide();
        if (active && res.holder === active.holder) return;
        show(res);
    }, true);

    window.addEventListener('scroll', hide, { capture: true, passive: true });
    window.addEventListener('resize', hide, { passive: true });
}

initTooltip();
