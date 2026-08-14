import clipboardMixin from "../mixins/clipboard.js";
import persistentStateMixin from "../mixins/persistentState.js";

const API_SERVERS = [
    'https://de2.api.radio-browser.info/json',
    'https://de1.api.radio-browser.info/json',
    'https://nl1.api.radio-browser.info/json',
    'https://fr1.api.radio-browser.info/json',
];

const FALLBACK_STATIONS = {
    jazz:       [{ name: 'Jazz24',       url_resolved: 'https://jazz24.org/streams/high.mp3',           stationuuid: 'fb-jazz' }],
    classical:  [{ name: 'WCRB',         url_resolved: 'https://streams.wgbh.org/wcrb.mp3',             stationuuid: 'fb-classical' }],
    pop:        [{ name: 'Capital UK',   url_resolved: 'https://media-ssl.musicradio.com/CapitalUK',     stationuuid: 'fb-pop' }],
    electronic: [{ name: 'DI.FM House',  url_resolved: 'https://listen.di.fm/public/3/house.mp3',       stationuuid: 'fb-electronic' }],
    lofi:       [{ name: 'Lofi Girl',    url_resolved: 'https://stream.lofi.co/lofi',                   stationuuid: 'fb-lofi' }],
};

const STATUS_CLASSES = {
    'در حال پخش':       'bg-green-100 text-green-800',
    'در حال بافر...':   'bg-yellow-100 text-yellow-800',
    'در حال تنظیم...':  'bg-blue-100 text-blue-800',
    'متوقف':            'bg-gray-100 text-gray-800',
    'آماده':            'bg-gray-100 text-gray-800',
    'خطا':              'bg-red-100 text-red-800',
    'آفلاین':           'bg-red-100 text-red-800',
};

const GENRES = [
    { value: 'jazz',        label: 'جاز' },
    { value: 'classical',   label: 'کلاسیک' },
    { value: 'pop',         label: 'پاپ' },
    { value: 'electronic',  label: 'الکترونیک' },
    { value: 'lofi',        label: 'لو-فای' },
];

export default function radio(id) {
    const storageKey = `${id}_state`;

    return {
        ...clipboardMixin(),
        ...persistentStateMixin(),
        open: false,
        minimized: false,
        playing: false,
        isLoading: false,
        status: 'آماده',
        audio: null,
        stations: [],
        currentStationUrl: '',
        currentGenre: 'jazz',
        currentServerIndex: 0,
        formattedDisplay: '',
        genres: GENRES,

        _windowRadioListener: null,
        _windowKeydownListener: null,

        get statusClass() {
            return STATUS_CLASSES[this.status] || STATUS_CLASSES['آماده'];
        },

        get currentStationName() {
            if (!this.currentStationUrl) return 'یک ایستگاه انتخاب کنید';
            return this.stations.find(s => s.url_resolved === this.currentStationUrl)?.name || 'ایستگاه ناشناس';
        },

        get currentGenreLabel() {
            return this.genres.find(g => g.value === this.currentGenre)?.label || 'جاز';
        },

        updateDisplay() {
            this.formattedDisplay = this.currentStationUrl ? this.currentStationName : this.status;
        },

        init() {
            const state = this._loadState(storageKey, {});
            this.currentGenre = state.genre || 'jazz';
            if (state.stationUrl) this.currentStationUrl = state.stationUrl;
            if (state.minimized) this.minimized = true;

            this._windowRadioListener = () => {
                this.minimized = false;
                this.open = true;
                if (!this.stations.length) this.fetchStations(false).catch(() => {});
            };

            this._windowKeydownListener = (e) => {
                if (e.key === 'Escape' && this.open) this.minimize();
            };

            window.addEventListener('radio', this._windowRadioListener);
            window.addEventListener('keydown', this._windowKeydownListener);

            this.$watch('currentStationUrl', (url) => { this.handleStationChange(url); this.updateDisplay(); });
            this.$watch('currentGenre', (n, o) => { if (n !== o) this.fetchStations(true); });

            this.updateDisplay();
        },

        destroy() {
            window.removeEventListener('radio', this._windowRadioListener);
            window.removeEventListener('keydown', this._windowKeydownListener);

            if (this.audio) {
                this.audio.pause();
                this.audio.src = '';
                this.audio = null;
            }
        },

        async fetchStations(forceRefetch = false) {
            if (this.stations.length && !forceRefetch) {
                this.updateDisplay();
                return;
            }

            if (forceRefetch) {
                this.stations = [];
                this.currentStationUrl = '';
                if (this.playing && this.audio) this.audio.pause();
            }

            this.isLoading = true;
            this.status = 'در حال تنظیم...';
            this.updateDisplay();

            let fetched = [];

            for (let i = 0; i < API_SERVERS.length; i++) {
                const server = API_SERVERS[this.currentServerIndex];
                const endpoints = [
                    `/stations/bytag/${this.currentGenre}`,
                    `/stations/search?name=${this.currentGenre}&limit=100`,
                ];

                for (const ep of endpoints) {
                    const ctrl = new AbortController();
                    const t = setTimeout(() => ctrl.abort(), 5000);
                    try {
                        const res = await fetch(server + ep, { signal: ctrl.signal });
                        if (!res.ok) continue;
                        const data = await res.json();
                        fetched = data.filter(s =>
                            s.url_resolved &&
                            String(s.codec || '').toLowerCase() === 'mp3' &&
                            s.lastcheckok === 1 &&
                            Number(s.bitrate || 0) > 64
                        );
                        if (fetched.length) break;
                    } catch { } finally { clearTimeout(t); }
                }

                if (fetched.length) break;
                this.currentServerIndex = (this.currentServerIndex + 1) % API_SERVERS.length;
            }

            if (fetched.length) {
                const unique = Array.from(new Map(fetched.map(s => [s.name.trim().toLowerCase(), s])).values());
                this.stations = unique.map(s => ({ ...s, name: s.name.length > 40 ? s.name.slice(0, 40) + '...' : s.name }));
            } else {
                this.stations = FALLBACK_STATIONS[this.currentGenre] || [];
            }

            if (this.stations.length) {
                if (!this.currentStationUrl || forceRefetch) {
                    this.$nextTick(() => {
                        this.currentStationUrl = this.stations[0].url_resolved;
                        this.setupAudio();
                        this.updateDisplay();
                    });
                } else {
                    this.setupAudio();
                    this.updateDisplay();
                }
                this.status = 'آماده';
            } else {
                this.status = 'آفلاین';
                this.updateDisplay();
            }

            this.isLoading = false;
            this.saveState();
        },

        setupAudio() {
            if (this.audio) return;
            this.audio = new Audio();
            this.audio.preload = 'none';

            const onPlay = () => { this.playing = true;  this.status = 'در حال پخش';     this.updateDisplay(); if (this.currentStationUrl) this.saveState(); };
            const onStop = () => { this.playing = false; this.status = 'متوقف';           this.updateDisplay(); if (this.currentStationUrl) this.saveState(); };
            const onBuffer = () => {                      this.status = 'در حال بافر...'; this.updateDisplay(); };
            const onError = () => { this.playing = false; this.status = 'خطا';             this.updateDisplay(); if (this.currentStationUrl) this.saveState(); };

            this.audio.addEventListener('playing', onPlay);
            this.audio.addEventListener('pause', onStop);
            this.audio.addEventListener('ended', onStop);
            this.audio.addEventListener('waiting', onBuffer);
            this.audio.addEventListener('error', onError);
        },

        handleStationChange(newUrl) {
            if (!newUrl) return;
            this.setupAudio();
            if (!this.audio || this.audio.src === newUrl) {
                this.updateDisplay();
                this.saveState();
                return;
            }

            const wasPlaying = this.playing;
            this.status = 'در حال تنظیم...';
            this.updateDisplay();
            if (wasPlaying) this.audio.pause();

            this.audio.src = newUrl;
            this.audio.load();

            if (wasPlaying) {
                this.audio.play().catch(() => {
                    this.status = 'خطا';
                    this.playing = false;
                    this.updateDisplay();
                });
            }
            this.saveState();
        },

        nextStation() {
            if (!this.stations.length) return;
            const i = this.stations.findIndex(s => s.url_resolved === this.currentStationUrl);
            this.currentStationUrl = this.stations[(i === -1 ? 0 : i + 1) % this.stations.length].url_resolved;
        },

        togglePlay() {
            if (!this.audio || !this.currentStationUrl) return;
            if (this.playing) {
                this.audio.pause();
                return;
            }
            if (this.audio.src !== this.currentStationUrl) {
                this.audio.src = this.currentStationUrl;
                this.audio.load();
            }
            this.audio.play().catch(() => {
                this.status = 'خطا';
                this.playing = false;
                this.updateDisplay();
            });
        },

        copyCurrentStation() {
            const text = this.currentStationUrl || this.formattedDisplay;
            if (!text) return;
            this.copyText(text, 'با موفقیت کپی شد.');
        },

        minimize() {
            this.minimized = true;
            this.open = false;
            this.saveState();
        },

        restore()  {
            this.minimized = false;
            this.open = true;
            this.saveState();
        },

        closeModal() {
            if (this.playing && this.audio) this.audio.pause();
            this.open = false;
            this.minimized = false;
            this.currentStationUrl = '';
            this._clearState(storageKey);
        },

        saveState() {
            this._saveState(storageKey, {
                stationUrl: this.currentStationUrl,
                genre: this.currentGenre,
                minimized: this.minimized
            });
        },
    };
}
