let initialized = false;

const FANCYBOX_OPTIONS = {
    animated: true,
    showClass: "f-fadeIn",
    hideClass: "f-fadeOut",
    Image: { zoom: true },
    backdrop: true,
    dragToClose: true,
    infinite: true,
    Carousel: {
        transition: "slide",
        Toolbar: {
            display: {
                left: [],
                middle: [],
                right: ["counter", "zoomIn", "zoomOut", "toggle1to1", "rotateCCW", "rotateCW", "flipX", "flipY", "reset", "autoplay", "fullscreen", "download", "thumbs", "close"],
            },
        },
        formatCaption: (carousel, slide) => {
            if (!slide.caption) return "";
            const el = document.createElement("span");
            el.textContent = slide.caption;
            return el;
        },
    },
};

function closeAll(Fancybox) {
    for (let box = Fancybox.getInstance(); box; box = Fancybox.getInstance()) box.destroy();
}

function bind(Fancybox, options) {
    Reflect.deleteProperty(Fancybox.Plugins, "Hash");
    Fancybox.bind(document.documentElement, "[data-fancybox]", options);
    document.addEventListener("livewire:navigating", () => closeAll(Fancybox));
}

export default function fancyboxMixin() {
    return {
        initFancybox(options = FANCYBOX_OPTIONS) {
            if (initialized) return;
            initialized = true;
            import("@fancyapps/ui")
                .then(({ Fancybox }) => bind(Fancybox, options))
                .catch(() => { initialized = false; });
        },
    };
}
