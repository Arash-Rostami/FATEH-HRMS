import { Fancybox } from "@fancyapps/ui";

export default function report() {
    return {
        init() {
            this.$nextTick(() => {
                this.initFancybox();
            });

            Livewire.hook('morph.updated', ({ component, el }) => {
                if (component.id === this.$wire.__instance.id) {
                    this.$nextTick(() => {
                        this.initFancybox();
                    });
                }
            });
        },

        initFancybox() {
            Fancybox.bind(this.$el, '[data-fancybox="report"]', {
                groupAll: true,
                Toolbar: {
                    display: {
                        left: ["infobar"],
                        middle: [
                            "zoomIn",
                            "zoomOut",
                            "toggle1to1",
                            "rotateCCW",
                            "rotateCW",
                        ],
                        right: ["slideshow", "fullscreen", "download", "thumbs", "close"],
                    },
                },
                Pdf: {
                    minScale: 1,
                    maxScale: 3,
                },
                animated: true,
                showClass: "f-fadeIn",
                hideClass: "f-fadeOut",
                dragToClose: false,
                keyboard: true,
            });
        }
    }
}
