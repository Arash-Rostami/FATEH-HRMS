const FULLSCREEN_EVENTS = [
    'fullscreenchange',
    'webkitfullscreenchange',
    'mozfullscreenchange',
    'MSFullscreenChange'
];

export default function fullscreen() {
    return {
        isFullscreen: false,
        _syncState: null,

        init() {
            this._syncState = () => {
                this.isFullscreen = !!(
                    document.fullscreenElement ||
                    document.webkitFullscreenElement ||
                    document.mozFullScreenElement ||
                    document.msFullscreenElement
                );
            };

            FULLSCREEN_EVENTS.forEach(event => {
                document.addEventListener(event, this._syncState);
            });

            this._syncState();
        },

        destroy() {
            if (this._syncState) {
                FULLSCREEN_EVENTS.forEach(event => {
                    document.removeEventListener(event, this._syncState);
                });
                this._syncState = null;
            }
        },

        toggle() {
            const element = document.documentElement;
            const active = document.fullscreenElement ||
                document.webkitFullscreenElement ||
                document.mozFullScreenElement ||
                document.msFullscreenElement;

            if (!active) {
                const request = element.requestFullscreen ||
                    element.webkitRequestFullscreen ||
                    element.mozRequestFullScreen ||
                    element.msRequestFullscreen;

                if (request) {
                    const result = request.call(element);
                    if (result && typeof result.catch === 'function') {
                        result.catch(err => {
                            console.error(`Error attempting to enable fullscreen: ${err.message}`);
                        });
                    }
                }
            } else {
                const exit = document.exitFullscreen ||
                    document.webkitExitFullscreen ||
                    document.mozCancelFullScreen ||
                    document.msExitFullscreen;

                if (exit) {
                    const result = exit.call(document);
                    if (result && typeof result.catch === 'function') {
                        result.catch(err => {
                            console.error(`Error attempting to disable fullscreen: ${err.message}`);
                        });
                    }
                }
            }
        }
    };
}
