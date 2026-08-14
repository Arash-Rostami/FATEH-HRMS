import { Fancybox } from "@fancyapps/ui";

let initialized = false;

const FANCYBOX_OPTIONS = {
    Toolbar: {
        display: {
            left: ["infobar"],
            middle: ["zoomIn", "zoomOut", "toggle1to1", "rotateCCW", "rotateCW", "flipX", "flipY"],
            right: ["slideshow", "fullscreen", "download", "thumbs", "close"],
        },
    },
    animated: true,
    showClass: "f-fadeIn",
    hideClass: "f-fadeOut",
    Image: { zoom: true },
    backdrop: true,
    keyboard: true,
    dragToClose: true,
    infinite: true,
    Carousel: { transition: "slide" },
    formatCaption: (fancybox, slide) => {
        if (!slide.caption) return "";
        const el = document.createElement("span");
        el.textContent = slide.caption;
        return el;
    },
};

export default function fancyboxMixin() {
    return {
        initFancybox(options = FANCYBOX_OPTIONS) {
            if (initialized) return;
            initialized = true;
            Fancybox.bind("[data-fancybox]", options);
        },
    };
}
