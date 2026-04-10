import settings from "./settings.js";

export default function contact() {
    return {
        isTyping: false,
        typingTimeout: null,
        showScrollFab: false,
        showInfo: false,
        showUndo: false,
        undoTimeout: null,

        emojiOpen: false,
        activeCat: 0,
        emojis: [
            {cat:'😊',items:['😀','😂','🥰','😎','🤔','👍','👏','🙏','❤️','🔥','✅','⭐','💯','🎉','😊','😅','🤝','💪','👋','🫡']},
            {cat:'👋',items:['👋','👍','👎','👊','✊','🤛','🤜','🤞','✌️','🤟','🤘','👌','🤌','👈','👉','👆','👇','✋','🤚','🖐','🖖','👏','🙌','👐','🤲','🤝','🙏']},
            {cat:'❤️',items:['❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','💟','♥️']},
            {cat:'🎭',items:['😭','😡','😱','🥳','🤯','🤮','🤢','🥶','🥵','😤','😴','🤤','😷','🤒','🤕','🤑','🤠','😈','👿','👹','👺','🤡','💩','👻','💀','☠️','👽','👾','🤖']},
            {cat:'🐾',items:['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐷','🐸','🐵','🐔','🐧','🐦','🐤','🦆','🦅','🦉','🦇','🐺','🐗','🐴','🦄','🐝','🐛','🦋']},
            {cat:'🍔',items:['🍎','🍐','🍊','🍋','🍌','🍉','🍇','🍓','🫐','🍈','🍒','🍑','🍍','🥝','🥥','🥑','🍆','🥕','🌽','🌶️','🫑','🥒','🥬','🥦','🧄','🧅','🍄','🥜','🌰','🍞']},
            {cat:'⚽',items:['⚽','🏀','🏈','⚾','🥎','🎾','🏐','🏉','🥏','🎱','🪀','🏓','🏸','🏒','🏑','🥍','🏏','🥅','⛳','🏹','🎣','🤿','🥊','🥋','🎽','🛹','🛼','🛷','⛸️','🥌']},
            {cat:'💻',items:['💻','📱','⌨️','🖥️','🖨️','🖱️','🖲️','🕹️','🗜️','💽','💾','💿','📀','📼','📷','📸','📹','🎥','📽️','🎞️','📞','☎️','📟','📠','📺','📻','🎙️','🎚️','🎛️','🧭']},
            {cat:'🚗',items:['🚗','🚕','🚙','🚌','🚎','🏎️','🚓','🚑','🚒','🚐','🛻','🚚','🚛','🚜','🦯','🦽','🦼','🛴','🚲','🛵','🏍️','🛺','🚨','🚔','🚍','🚘','🚖','🚡','🚠','🚟']},
            {cat:'🌟',items:['🌟','✨','💫','⚡','🔥','💥','☄️','☀️','🌤️','⛅','🌥️','☁️','🌦️','🌧️','⛈️','🌩️','🌨️','❄️','🌬️','💨','🌪️','🌫️','🌈','☔','💧','💦','🌊','🌍','🌎','🌏']}
        ],

        insertEmoji(e) {
            const ta = document.getElementById('msg-ta');
            if (!ta) return;
            const s = ta.selectionStart, val = ta.value;
            this.$wire.set('body', val.slice(0, s) + e + val.slice(s));
            this.emojiOpen = false;
            this.$nextTick(() => { ta.focus(); ta.selectionStart = ta.selectionEnd = s + e.length; });
        },

        init() {
            const vp = this.$refs.msgViewport;
            if (vp) {
                let t = false;
                vp.addEventListener('scroll', () => {
                    if (!t) {
                        requestAnimationFrame(() => {
                            this.showScrollFab = (vp.scrollHeight - vp.scrollTop - vp.clientHeight) > 200;
                            t = false;
                        });
                        t = true;
                    }
                });
            }
            window.addEventListener('typing-indicator', () => {
                this.isTyping = true;
                clearTimeout(this.typingTimeout);
                this.typingTimeout = setTimeout(() => { this.isTyping = false; }, 2500);
            });
            this.$wire.on('chat-ready',        () => this.$nextTick(() => this.scrollToBottom(false)));
            this.$wire.on('message-sent',      () => this.$nextTick(() => this.scrollToBottom(true)));
            this.$wire.on('show-toast',        (e) => this.toast(e.message, e.type ?? 'info'));
            this.$wire.on('show-undo-toast',   (e) => this.toast(e.message, 'warning'));
            this.$wire.on('edit-mode-entered', () => {
                this.$nextTick(() => {
                    const ta = document.querySelector('textarea[wire\\:model\\.live="editingBody"]');
                    if (ta) { ta.focus(); ta.selectionStart = ta.selectionEnd = ta.value.length; }
                });
            });
            this.$watch('$wire.lastDeleted', (val) => {
                clearTimeout(this.undoTimeout);
                if (val) {
                    this.showUndo = true;
                    this.undoTimeout = setTimeout(() => { this.showUndo = false; }, 4000);
                } else {
                    this.showUndo = false;
                }
            });
        },
        initPattern() {
            const settingInstance = settings();
            return settingInstance.initPattern();
        },

        toast(message, type = 'info') {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }));
        },

        scrollToBottom(smooth = false) {
            document.getElementById('msg-viewport')
                ?.scrollTo({ top: 999999, behavior: smooth ? 'smooth' : 'instant' });
        },

        focusSearch() {
            this.$refs.searchInput?.focus();
            this.$refs.searchInput?.select();
        },

        closeOverlays() {
            this.showInfo = false;
            this.$wire.cancelEdit();
            this.$wire.cancelDelete();
        },

        copyMessage(text) {
            (navigator.clipboard?.writeText(text) ?? Promise.reject())
                .then(() => this.toast('پیام کپی شد', 'info'))
                .catch(() => {
                    const ta = Object.assign(document.createElement('textarea'),
                        { value: text, style: 'position:fixed;opacity:0' });
                    document.body.append(ta); ta.select();
                    document.execCommand('copy'); ta.remove();
                    this.toast('پیام کپی شد', 'info');
                });
        },
    };
}
