const THROTTLE_MS = 1500;
const ICON_NAME = /^[a-z0-9_]+$/;
const RAW_RATIO = 2.2;
const FULL_CLASS = 'ms-full';

const idle = (fn) => (window.requestIdleCallback
    ? window.requestIdleCallback(fn, {timeout: 3000})
    : setTimeout(fn, 200));

const useFull = (el) => el.classList.add(FULL_CLASS);

const sendReport = (name) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!token) return;
    fetch('/icon-miss', {
        method: 'POST',
        keepalive: true,
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token},
        body: JSON.stringify({name}),
    }).catch(() => {});
};

const looksRaw = (el) => {
    const range = document.createRange();
    range.selectNodeContents(el);
    const fontSize = parseFloat(getComputedStyle(el).fontSize) || 24;
    return range.getBoundingClientRect().width > RAW_RATIO * fontSize;
};

export default function watchIcons({mark = useFull, report = sendReport} = {}) {
    const reported = new Set();
    let pending = false;

    const scan = () => {
        pending = false;
        for (const el of document.querySelectorAll(`.material-symbols-rounded:not(.${FULL_CLASS})`)) {
            const name = el.textContent.trim();
            if (!ICON_NAME.test(name) || !looksRaw(el)) continue;
            if (import.meta.env?.DEV) console.warn(`[icon-guard] "${name}" renders as raw text; run the build or add it to resources/icons/safelist.json`);
            mark(el);
            if (!reported.has(name)) {
                reported.add(name);
                report(name);
            }
        }
    };

    const settle = () => idle(() => Promise.resolve(document.fonts?.ready).then(scan));

    function request() {
        if (pending) return;
        pending = true;
        setTimeout(settle, THROTTLE_MS);
    }

    document.addEventListener('click', request, {capture: true, passive: true});
    document.addEventListener('livewire:navigated', request);

    if (document.readyState === 'complete') settle();
    else window.addEventListener('load', settle, {once: true});

    return () => {
        document.removeEventListener('click', request, true);
        document.removeEventListener('livewire:navigated', request);
    };
}
