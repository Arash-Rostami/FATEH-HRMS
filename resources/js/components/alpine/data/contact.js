import settings from "./settings.js";

export default function contact() {
    return {
        isTyping: false,
        typingTimeout: null,
        showScrollFab: false,
        showInfo: false,
        showUndo: false,
        undoTimeout: null,
        sending: false,

        // UI States for Message Contexts
        replyingTo: null,
        editingMsg: null,
        deletingId: null,

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
            this.$wire.set('composer.body', val.slice(0, s) + e + val.slice(s));
            this.emojiOpen = false;
            this.$nextTick(() => { ta.focus(); ta.selectionStart = ta.selectionEnd = s + e.length; });
        },

        init() {
            this.initPattern();

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
            this.$wire.on('chat-ready',        () => this.$nextTick(() => { this.scrollToBottom(false); this.resetUI(); }));
            this.$wire.on('message-sent',      () => this.$nextTick(() => { this.scrollToBottom(true); this.sending = false; }));
            this.$wire.on('message-error',     () => this.$nextTick(() => { this.sending = false; }));
            this.$wire.on('show-toast',        (e) => this.toast(e.message, e.type ?? 'info'));
            this.$wire.on('show-undo-toast',   (e) => this.toast(e.message, 'warning'));
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
            this.cancelEdit();
            this.cancelDelete();
        },

        resetUI() {
            this.replyingTo = null;
            this.editingMsg = null;
            this.deletingId = null;
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

        // --- Reply Flow ---
        startReply(id, senderName, body) {
            this.editingMsg = null;
            this.deletingId = null;
            this.replyingTo = { id, sender: { name: senderName }, body };
            this.$nextTick(() => document.getElementById('msg-ta')?.focus());
        },
        cancelReply() {
            this.replyingTo = null;
        },

        // --- Edit Flow ---
        startEdit(id, body) {
            this.replyingTo = null;
            this.deletingId = null;
            this.editingMsg = { id, body };
            this.$wire.set('edit.editingBody', body);
            this.$nextTick(() => {
                const ta = document.querySelector('textarea[wire\\:model\\.live="edit.editingBody"]');
                if (ta) { ta.focus(); ta.selectionStart = ta.selectionEnd = ta.value.length; }
            });
        },
        cancelEdit() {
            this.editingMsg = null;
        },
        saveEdit() {
            if (!this.editingMsg) return;
            this.$wire.saveEdit(this.editingMsg.id);
            this.editingMsg = null;
        },

        // --- Delete Flow ---
        confirmDelete(id) {
            this.replyingTo = null;
            this.editingMsg = null;
            this.deletingId = id;
        },
        cancelDelete() {
            this.deletingId = null;
        },
        deleteMessage() {
            if (!this.deletingId) return;
            this.$wire.deleteMessage(this.deletingId);
            this.deletingId = null;
        },

        async sendMessage() {
            if (this.sending) return;

            const ta = document.getElementById('msg-ta');
            const body = ta ? ta.value.trim() : '';
            const attachments = this.$wire.composer?.attachments || [];

            if (body.length === 0 && attachments.length === 0) {
                this.toast('پیام نمی‌تواند خالی باشد.', 'warning');
                return;
            }

            if (body.length > 2000) {
                this.toast('متن پیام نباید بیشتر از ۲۰۰۰ کاراکتر باشد.', 'warning');
                return;
            }

            this.sending = true;
            try {
                await this.$wire.send(this.replyingTo ? this.replyingTo.id : null);
                this.replyingTo = null;
                setTimeout(() => { this.sending = false; }, 5000);
            } catch(e) {
                this.sending = false;
            }
        },
    };
}
