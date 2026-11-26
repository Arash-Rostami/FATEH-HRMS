import maximizeMixin from "../mixins/maximize.js";
import lastOpenMixin from "../mixins/lastOpen.js";
import sidebarRailMixin from "../mixins/sidebarRail.js";
import chatScaleMixin from "../mixins/chatScale.js";
import clipboardMixin from "../mixins/clipboard.js";
import pasteImageMixin from "../mixins/pasteImage.js";
import chatBase from "../mixins/chatBase.js";
import fancyboxMixin from "../mixins/fancybox.js";
import aiEditMixin from "../mixins/aiEdit.js";

const SCOPE = 'channel';
const OPEN_POLL_CEILING_MS = 20000;
const IDLE_POLL_CEILING_MS = 30000;
const MOBILE_BREAKPOINT = 768;
const MAX_BODY_LENGTH = 4000;
const UNDO_TOAST_MS = 4000;
const SEND_LOCK_RESET_MS = 500;
const CHIPS_FADE_MS = 3000;
const NEAR_BOTTOM_PX = 120;
const LOAD_OLDER_SCROLL_THRESHOLD = 80;
const SCROLL_FAB_DISTANCE_THRESHOLD = 200;
const MSG_VIEWPORT_ID = 'msg-viewport';
const MSG_TEXTAREA_ID = 'msg-ta';
const TOTAL_UNREAD_ATTR = 'data-total-unread';
const DATA_SENDER_NAME_ATTR = 'data-sender-name';
const DATA_RF_MESSAGE_PREFIX = 'channel-message';
const POLL_FALLBACK_EVERY = 10;
const MENTION_LIMIT = 8;
const TOO_LONG_MSG = 'متن پیام نباید بیشتر از ۴۰۰۰ کاراکتر باشد.';
const AI_FAILED_MSG = 'درخواست انجام نشد. کمی بعد دوباره امتحان کنید.';

const RF_ID_OFFSET = DATA_RF_MESSAGE_PREFIX.length + 1;
const ROW_SEL = `[data-rf^="${DATA_RF_MESSAGE_PREFIX}-"]`;
const UNREAD_SEL = `[${TOTAL_UNREAD_ATTR}]`;
const TYPING_SEL = '[data-typing]';
const CHANNEL_COUNT_SEL = '[data-channel-count]';
const FLASH_CLASS = 'record-focus-flash';
const EDITABLE_SEL = 'input, textarea, [contenteditable]';
const FIELD_SEL = 'input, textarea, select, [contenteditable]';
const WS = /\s/;

const ta = () => document.getElementById(MSG_TEXTAREA_ID);
const rowId = (row) => parseInt(row.getAttribute('data-rf').substring(RF_ID_OFFSET), 10) || 0;
const lastAt = (v, pos) => pos ? v.lastIndexOf('@', pos - 1) : -1;

export default function channel() {
    let ac, vpAc, vpRo, scrollRaf, selRaf, unreadObs, fadeTimer, digest, ticks = 0, selectSeq = 0, aiSeq = 0;

    return {
        ...maximizeMixin('channel'),
        ...lastOpenMixin('channel'),
        ...sidebarRailMixin('channel'),
        ...chatScaleMixin(),
        ...clipboardMixin(),
        ...pasteImageMixin(),
        ...chatBase(SCOPE),
        ...fancyboxMixin(),
        ...aiEditMixin(),
        channelCount: 0,
        editingOriginal: '',
        isEditing: false,
        editingMsgId: null,
        editingBody: '',
        quoteChip: { visible: false, x: 0, y: 0, id: 0, sender: '', snippet: '' },
        keyOpen: false,
        channelKey: '',
        aiOpen: false,
        aiBusy: false,
        aiQuestion: '',
        aiAnswer: '',
        aiNote: '',
        aiMessage: '',
        aiSoft: false,
        aiMeter: '',
        activeSender: null,
        chipsVisible: true,
        mentionOpen: false,
        mentionQuery: '',
        mentionActiveIndex: 0,

        init() {
            ac = new AbortController();
            const { signal } = ac;
            this.initChatScale(signal);
            this.initFancybox();
            this._loadChatSettings();
            this.headerDeclutterMenuBridge(signal);
            this.$watch('showInfo', (open) => {
                if (!open) this.aiOpen = false;
                else if (this.filesOpen && !this._filesCache) this.retryFiles();
            });

            this.syncChannelCount();
            this.syncPushNotify();

            unreadObs = new MutationObserver(() => this.syncPushNotify());
            unreadObs.observe(document.body, { subtree: true, attributes: true, attributeFilter: [TOTAL_UNREAD_ATTR] });

            this.startPolling();

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    this._flushPendingRing();
                    this._repollNow();
                    if (this.aiOpen && !this.aiBusy) this.aiLoadMeter();
                } else if (!this.$store.push.pollsHidden(SCOPE)) this.stopPolling();
            }, { signal });

            this._bindViewport();
            this.initSwipeReply();

            document.addEventListener('selectionchange', () => {
                if (selRaf) return;
                if (!this.quoteChip.visible && getSelection()?.isCollapsed) return;
                selRaf = requestAnimationFrame(() => {
                    selRaf = null;
                    const chip = this._selectionChip();
                    if (chip) this.quoteChip = chip;
                    else this.quoteChip.visible = false;
                });
            }, { signal });

            document.addEventListener('keydown', (e) => {
                if (e.key !== '/' || e.isComposing || !this.$root.contains(e.target)) return;
                if (document.activeElement?.closest?.(FIELD_SEL)) return;
                if (this.searchMessages) return;
                e.preventDefault();
                this.openMessageSearch();
            }, { signal });

            this._draftInit(signal);

            this.$wire.on('message-sent', (e) => {
                this.sending = false;
                this.replyingTo = null;
                this._draftSent(e);
                this.$store.sound?.playOutgoing(this.$wire.activeChannelId, SCOPE);
            });

            this.$wire.on('show-toast', (e) => this.toast(e.message, e.type ?? 'info'));
            this.$wire.on('show-undo-toast', (e) => this.toast(e.message, 'warning'));
            this.$wire.on('attachments-updated', () => {
                this._isl().syncAttachments().catch(() => {});
            });

            this.$wire.on('channel-members-updated', () => {
                this._isl().refreshActive().catch(() => {});
                this._sidebar().catch(() => {});
            });

            this.$watch('$wire.lastDeleted', (val) => {
                clearTimeout(this.undoTimeout);
                this.showUndo = !!val;
                if (val) this.undoTimeout = setTimeout(() => { this.showUndo = false; }, UNDO_TOAST_MS);
            });

            this.$watch('$wire.editingMsg', (val, old) => {
                if (val || (old?.id && this.editingMsgId && old.id !== this.editingMsgId)) return;
                this._clearEdit();
            });

            const lastId = this.$wire.activeChannelId ? null : this._readLastOpenId();
            if (lastId) this.selectChannel(lastId).finally(() => this.initMaximize());
            else this.initMaximize();
        },

        destroy() {
            this._ghostHide();
            this._destroyed = true;
            this.stopPolling();
            ac?.abort();
            clearTimeout(this._declutterTimer);
            this._swipeAc?.abort();
            vpAc?.abort();
            vpRo?.disconnect();
            clearTimeout(this._scrollSettleTimer);
            unreadObs?.disconnect();
            cancelAnimationFrame(scrollRaf);
            cancelAnimationFrame(selRaf);
            clearTimeout(fadeTimer);
        },

        // #msg-viewport lives inside the messages island's pane-active branch, destroyed and
        // re-mounted every time a channel opens/closes — this component's own init() runs once,
        // so a reference captured there goes stale (null forever if mounted with no channel open,
        // detached after the first close-then-reopen). Re-binding here, called from both init()
        // and every selectChannel() resolution, keeps scroll-to-load-older, the scroll FAB, and
        // the quote-chip feature working across channel switches.
        _bindViewport() {
            const vp = document.getElementById(MSG_VIEWPORT_ID);
            this._msgViewportEl = vp;
            if (!vp) return;

            vpAc?.abort();
            vpRo?.disconnect();
            vpAc = new AbortController();
            const { signal } = vpAc;
            let lastSt = vp.scrollTop;

            vp.addEventListener('programmatic-scroll', () => this.markProgrammaticScroll(), { signal });

            vp.addEventListener('scroll', () => {
                if (this._programmaticScroll) this._armScrollSettle();
                if (scrollRaf) return;
                scrollRaf = requestAnimationFrame(() => {
                    scrollRaf = null;
                    const programmatic = this._programmaticScroll;
                    const { scrollTop: st, scrollHeight: sh, clientHeight: ch } = vp;
                    this.showScrollFab = sh - st - ch > SCROLL_FAB_DISTANCE_THRESHOLD;

                    if (!this.showScrollFab && this.newBelow > 0) {
                        this.newBelow = 0;
                        if (this.$wire.focusAnchorId == null) this.$wire.markRead(this.$wire.activeChannelId);
                    }

                    const up = st < lastSt;
                    lastSt = st;
                    if (programmatic) return;

                    this.quoteChip.visible = false;
                    this.openActionsId = null;

                    if (up && st < LOAD_OLDER_SCROLL_THRESHOLD && this.$wire.hasOlder) {
                        this.loadOlderKeepingPlace(() => this._isl().loadOlder());
                    }

                    if (!up && sh - st - ch < LOAD_OLDER_SCROLL_THRESHOLD && this.$wire.hasNewer && !this._loadingNewer) {
                        this.loadNewer(() => this._isl().loadNewer());
                    }
                });
            }, { passive: true, signal });

            let prevCh = vp.clientHeight;
            vpRo = new ResizeObserver(() => {
                const ch = vp.clientHeight;
                if (ch < prevCh && vp.scrollHeight - vp.scrollTop - prevCh < NEAR_BOTTOM_PX) this.scrollToBottom(false);
                prevCh = ch;
            });
            vpRo.observe(vp);
        },

        // Every transition that swaps #msg-viewport's content (join/create/leave/delete/browse
        // toggle/create-pane toggle) must re-bind it the same way selectChannel() does, or
        // scroll-to-load-older/the scroll FAB/the quote-chip go dead until the next channel pick.
        _afterPaneSwap() {
            this.showScrollFab = false;
            this.newBelow = 0;
            this.$nextTick(() => {
                this._bindViewport();
                if (this._msgViewportEl) this.scrollToBottom();
            });
        },

        _isl() { return this.$wire.$island('messages'); },
        _sidebar() { return this.$wire.$island('sidebar').refreshUnread(); },

        _editDirty() { return this.isEditing && this.editingBody !== this.editingOriginal; },

        aiEditCurrentId() { return this.isEditing ? this.editingMsgId : null; },
        aiEditBox(id) { return document.querySelector(`[data-edit-box="${id}"] textarea`); },
        aiEditApply(text) { this.editingBody = text; },

        _clearEdit() {
            this.resetAiEdit();
            this.isEditing = false;
            this.editingMsgId = null;
            this.editingBody = '';
            this.editingOriginal = '';
        },

        _overLimit(body) {
            if (this.chars(body) <= MAX_BODY_LENGTH) return false;
            this.toast(TOO_LONG_MSG, 'warning');
            return true;
        },

        _selectionChip() {
            const sel = getSelection();
            if (!sel || sel.isCollapsed || !sel.rangeCount) return;

            const node = sel.anchorNode;
            if (!node || !this._msgViewportEl?.contains(node)) return;

            const el = node.nodeType === 1 ? node : node.parentElement;
            if (!el || el.closest(EDITABLE_SEL)) return;

            const row = el.closest(ROW_SEL);
            if (!row || !row.querySelector('button[data-body]')) return;

            const text = sel.toString().trim();
            if (!text) return;

            const { left: x, top: y } = sel.getRangeAt(0).getBoundingClientRect();
            return {
                visible: true, x, y,
                id: rowId(row),
                sender: row.querySelector('[data-sender]')?.getAttribute('data-sender') ?? '',
                snippet: text.substring(0, 120),
            };
        },

        async _pollTick(force = false) {
            const fullSidebar = force || ++ticks % POLL_FALLBACK_EVERY === 0;
            const open = !!this.$wire.activeChannelId && !document.hidden;
            const lastBefore = open ? this._readLastMessageMarker() : null;
            const typingBefore = open ? this._readTypingMarker() : null;
            let changed = false;
            let unreadRose = false;

            try {
                const d = await this.$wire.pollDigest();
                if (fullSidebar || d !== digest) {
                    this._riseOnlyMuted();
                    const unreadBefore = this._readUnread();
                    await this._sidebar();
                    this.syncChannelCount();
                    digest = d;
                    const unreadAfter = this._readUnread();
                    changed = unreadAfter !== unreadBefore;
                    unreadRose = unreadAfter > unreadBefore;
                    if (unreadRose && document.hidden) this._ringIncoming(null);
                }

                if (open) {
                    const vp = this._msgViewportEl;
                    const nearBottom = !!vp && vp.scrollHeight - vp.scrollTop - vp.clientHeight < NEAR_BOTTOM_PX;
                    await this._isl().refreshActive();
                    this.autoDirection(ta());
                    const lastAfter = this._readLastMessageMarker();
                    if (lastAfter !== lastBefore) this._dropFiles();
                    const threadChanged = lastAfter !== lastBefore && lastBefore !== null && lastAfter !== null && lastAfter !== this._selfMarker && this._markerAdvanced(lastAfter, lastBefore);
                    const incoming = threadChanged && !this._lastMessageIsMine();
                    if (threadChanged) {
                        if (nearBottom) this.$nextTick(() => this.scrollToBottom(true));
                        else this.newBelow += this._rowsNewerThan(lastBefore);
                    }
                    if (incoming && nearBottom && !document.hidden && this.$wire.focusAnchorId == null) this.$wire.markRead(this.$wire.activeChannelId);
                    if (incoming) this._ringIncoming(this.$wire.activeChannelId);
                    else if (unreadRose && !threadChanged) this._ringIncoming(null);
                    changed ||= lastAfter !== lastBefore || this._readTypingMarker() !== typingBefore;
                }
            } catch {}

            this._applyPollResult(changed, open ? OPEN_POLL_CEILING_MS : IDLE_POLL_CEILING_MS);
        },

        _sendTypingPing() { return this._isl().pingTyping(); },

        _readUnread() {
            return parseInt(document.querySelector(UNREAD_SEL)?.getAttribute(TOTAL_UNREAD_ATTR), 10) || 0;
        },

        _readLastMessageMarker() {
            const rows = this._msgViewportEl?.querySelectorAll(ROW_SEL);
            return rows?.length ? rows[rows.length - 1].getAttribute('data-rf') : null;
        },

        _readTypingMarker() {
            return document.querySelector(TYPING_SEL)?.getAttribute('data-typing') ?? null;
        },

        syncChannelCount() {
            const el = document.querySelector(CHANNEL_COUNT_SEL);
            if (el) this.channelCount = parseInt(el.getAttribute('data-channel-count'), 10) || 0;
        },

        syncPushNotify() {
            this._riseOnlyMuted();
            const now = this._readUnread();
            const message = this._pushMessage(now, 'یک گروه پیام جدید دارد');
            const last = this._lastUnread;
            if (last !== undefined && now > last) {
                this.$store.push.notify(...message, SCOPE);
                if (!this.$wire.activeChannelId) this._ringIncoming(null);
            }
            this._lastUnread = now;
        },

        _lastMessageIsMine() {
            const rows = this._msgViewportEl?.querySelectorAll(ROW_SEL);
            const last = rows?.length ? rows[rows.length - 1] : null;
            return !!last?.classList.contains('justify-start');
        },

        get mentionMatches() {
            if (!this.mentionOpen) return [];

            const list = this.$wire.mentionMemberNames || [];
            const q = (this.mentionQuery || '').toLowerCase();
            if (!q) return list.slice(0, MENTION_LIMIT);

            const out = [];
            for (let i = 0; i < list.length && out.length < MENTION_LIMIT; i++) {
                if ((list[i] || '').toLowerCase().includes(q)) out.push(list[i]);
            }
            return out;
        },

        openMentionPicker() {
            const el = ta();
            if (!el || el.readOnly) return;

            const v = el.value;
            const pos = el.selectionStart ?? v.length;
            const sp = pos > 0 && !WS.test(v[pos - 1]) ? ' ' : '';
            const next = v.substring(0, pos) + sp + '@' + v.substring(pos);

            el.value = next;
            el.focus();
            el.selectionStart = el.selectionEnd = pos + sp.length + 1;

            this._draftSave(this._draftRoom(), next);
            this.$wire.set('composer.body', next, false);
            this.$wire.loadMentionMemberNames().catch(() => {});
            this.mentionQuery = '';
            this.mentionActiveIndex = 0;
            this.mentionOpen = true;
        },

        detectMention(e) {
            this.resetPollInterval();
            this.pingTyping();

            const { value: v, selectionStart: pos } = e.target;
            const at = lastAt(v, pos);
            const segment = at < 0 ? '' : v.substring(at + 1, pos);

            if (at < 0 || WS.test(segment) || (at > 0 && !WS.test(v[at - 1]))) {
                this.mentionOpen = false;
                return;
            }

            if (!this.mentionOpen) this.$wire.loadMentionMemberNames().catch(() => {});
            this.mentionQuery = segment;
            this.mentionActiveIndex = 0;
            this.mentionOpen = true;
        },

        onComposerKeydown(e) {
            if (e.isComposing) return;
            const { key } = e;
            if (this.mentionOpen) {
                const len = this.mentionMatches.length;
                if (len) {
                    if (key === 'ArrowDown' || key === 'ArrowUp') {
                        e.preventDefault();
                        this.mentionActiveIndex = (this.mentionActiveIndex + (key === 'ArrowDown' ? 1 : len - 1)) % len;
                        return;
                    }
                    if (key === 'Enter' || key === 'Tab') { e.preventDefault(); this.pickMention(this.mentionActiveIndex); return; }
                    if (key === 'Escape') { e.preventDefault(); this.mentionOpen = false; return; }
                }
            }
            if (key === 'Enter' && !e.shiftKey) { e.preventDefault(); this.sendMessage(); }
        },

        pickMention(i) {
            const name = this.mentionMatches[i];
            const el = ta();
            const at = name && el ? lastAt(el.value, el.selectionStart) : -1;

            if (at < 0 || el.readOnly) { this.mentionOpen = false; return; }

            const insert = name + ' ';
            const next = el.value.substring(0, at + 1) + insert + el.value.substring(el.selectionStart);

            this.mentionOpen = false;
            this.mentionQuery = '';
            el.value = next;
            this._draftSave(this._draftRoom(), next);
            this.$wire.set('composer.body', next, false);
            this.$nextTick(() => { el.focus(); el.selectionStart = el.selectionEnd = at + 1 + insert.length; });
        },

        toggleActions(id) {
            this.openActionsId = this.openActionsId === id ? null : id;
        },

        openManageMembers(id) {
            if (!id) return;
            this.$wire.openManageMembers(id).catch(() => {});
        },

        openManageAdmins(id) {
            if (!id) return;
            this.$wire.openManageAdmins(id).catch(() => {});
        },

        closeOverlays() {
            const wasOpen = this.showInfo || this.searchMessages || this.bookmarksOpen || this.daysOpen || this.emojiOpen || this.$wire.createMode || this.$wire.browseMode || this.replyingTo || this.deletingId || this.openActionsId || this.quoteChip.visible || this.activeSender || this.isEditing;
            this.showInfo = false;
            this.searchMessages = false;
            this.bookmarksOpen = false;
            this.daysOpen = false;
            this.replyingTo = null;
            this.deletingId = null;
            this.openActionsId = null;
            this.emojiOpen = false;
            this.quoteChip.visible = false;
            this.activeSender = null;
            this.closeChannelKey();
            if (this.$wire.createMode) this.closeCreate();
            if (this.$wire.browseMode) this.toggleBrowse();
            this.$wire.cancelReply();
            this.$wire.cancelEdit();
            return Boolean(wasOpen);
        },

        scrollToMessage(id) {
            if (!id) return;
            const el = document.querySelector(`[data-rf="${DATA_RF_MESSAGE_PREFIX}-${id}"]`);
            if (!el) { this.typeFilter = ''; this._isl().focusMessage(id).catch(() => {}); return; }
            if (this.typeFilter && el.offsetParent === null) {
                this.typeFilter = '';
                this.$nextTick(() => this.scrollToMessage(id));
                return;
            }

            document.querySelectorAll(`.${FLASH_CLASS}`).forEach(n => n.classList.remove(FLASH_CLASS));
            el.style.animation = 'none';
            this.programmaticScroll(() => el.scrollIntoView({ behavior: 'smooth', block: 'center' }));
            el.classList.add(FLASH_CLASS);
        },

        filterSender(name) {
            this.activeSender = name;
            let latest = 0;
            for (const r of document.querySelectorAll(ROW_SEL)) {
                if (r.getAttribute(DATA_SENDER_NAME_ATTR) === name) latest = Math.max(latest, rowId(r));
            }

            if (latest) this.scrollToMessage(latest);
            else this._isl().focusSender(name).catch(() => {});
        },

        clearSenderFilter() {
            if (this.activeSender === null) return;
            this.activeSender = null;
            this._isl().clearFocus()
                .then(() => this.$nextTick(() => this.scrollToBottom(true)))
                .catch(() => {});
        },

        revealChips() {
            clearTimeout(fadeTimer);
            this.chipsVisible = true;
        },

        scheduleChipsFade() {
            clearTimeout(fadeTimer);
            fadeTimer = setTimeout(() => { this.chipsVisible = false; }, CHIPS_FADE_MS);
        },

        selectChannel(id) {
            if (!id) return;
            const wire = this.$wire;
            const same = id === wire.activeChannelId;
            if (same && wire.mobileShowChat && !wire.browseMode && !wire.createMode) return;
            if (!same && this.holdDraft(id, ta())) return;
            const seq = ++selectSeq;
            this._persistLastOpen(id);
            this._repollNow(false);

            const el = ta();
            this.resetAutoResize(el);
            if (el) el.dir = 'rtl';

            this.replyingTo = null;
            this.activeSender = null;
            this.deletingId = null;
            this.openActionsId = null;
            this.searchMessages = false;
            this.bookmarksOpen = false;
            this.daysOpen = false;
            this.closeChannelKey();
            this.aiReset();
            this._clearEdit();
            this.resetRoomScoped();

            this.$wire.cancelReply();
            return this._isl().selectChannel(id)
                .then(() => {
                    if (seq !== selectSeq) return;
                    this.showScrollFab = false;
                    this.newBelow = 0;
                    this.$nextTick(() => { this._bindViewport(); this.scrollToBottom(false); this._draftRestore(); });
                    return this._sidebar();
                })
                .then(() => {
                    if (seq !== selectSeq) return;
                    this.chipsVisible = true;
                    this.scheduleChipsFade();
                })
                .then(() => {
                    if (seq !== selectSeq) return;
                    if (innerWidth < MOBILE_BREAKPOINT && el) this.$nextTick(() => el.focus());
                });
        },

        openAiPanel() {
            if (this.filesOpen) this.toggleFiles();
            this.aiOpen = true;
            this.showInfo = true;
            this.aiLoadMeter();
        },

        aiLoadMeter() {
            return this.$wire.aiQuota().then((html) => { this.aiMeter = html; }).catch(() => { this.aiMeter = ''; });
        },

        aiReset() {
            aiSeq++;
            this.aiBusy = false;
            this.aiQuestion = '';
            this.aiAnswer = '';
            this.aiNote = '';
            this.aiMessage = '';
            this.aiSoft = false;
        },

        askAi(mode) {
            if (this.aiBusy) return;
            const seq = ++aiSeq;
            this.aiBusy = true;
            this.aiMessage = '';

            return this.$wire.askAi(mode, this.aiQuestion)
                .then((r) => {
                    if (seq !== aiSeq) return;
                    this.aiMeter = r.meter;
                    this.aiMessage = r.message || '';
                    this.aiSoft = r.soft;
                    if (r.error === 'busy') return;
                    this.aiAnswer = r.answer;
                    this.aiNote = r.note || '';
                })
                .catch(() => {
                    if (seq !== aiSeq) return;
                    this.aiMessage = AI_FAILED_MSG;
                    this.aiSoft = false;
                })
                .finally(() => {
                    if (seq === aiSeq) this.aiBusy = false;
                });
        },

        toggleBrowse() {
            const wire = this.$wire;
            return wire.$island('messages').toggleBrowse()
                .then(() => { this._afterPaneSwap(); return wire.$island('sidebar').refreshUnread(); });
        },
        openCreate() {
            const wire = this.$wire;
            if (this.holdDraft('create', ta(), wire)) return Promise.resolve();
            return wire.$island('messages').openCreate()
                .then(() => { this._afterPaneSwap(); return wire.$island('sidebar').refreshUnread(); });
        },

        closeCreate() {
            const wire = this.$wire;
            return wire.$island('messages').closeCreate()
                .then(() => { this._afterPaneSwap(); return wire.$island('sidebar').refreshUnread(); });
        },

        backToList() {
            const wire = this.$wire;
            if (this.holdDraft('back', ta(), wire)) return;
            this.searchMessages = false;
            this.bookmarksOpen = false;
            this.daysOpen = false;
            this._clearLastOpen();
            this.activeSender = null;
            this.openActionsId = null;
            this._msgViewportEl = null;
            this.showScrollFab = false;
            this.newBelow = 0;
            wire.set('mobileShowChat', false, false);
            wire.$island('messages').backToList().then(() => wire.$island('sidebar').refreshUnread());
        },

        leaveChannel(id) {
            if (!id) return;
            this.searchMessages = false;
            const wire = this.$wire;
            wire.$island('messages').leaveChannel(id)
                .then(() => { this._afterPaneSwap(); return wire.$island('sidebar').refreshUnread(); })
                .then(() => this.syncChannelCount())
                .catch(() => {});
        },

        createChannel() {
            const wire = this.$wire;
            wire.$island('messages').createChannel()
                .then(() => { this._afterPaneSwap(); return wire.$island('sidebar').refreshUnread(); })
                .then(() => this.syncChannelCount());
        },

        joinChannel(id) {
            if (!id) return;
            const wire = this.$wire;
            wire.$island('messages').joinChannel(id)
                .then(() => { this._afterPaneSwap(); return wire.$island('sidebar').refreshUnread(); })
                .then(() => this.syncChannelCount())
                .catch(() => {});
        },

        startReply(id, senderName, body) {
            if (!id) return;
            this.replyingTo = { id, sender: { name: senderName || 'Unknown' }, body: body || '' };
            this.quoteChip.visible = false;
            this.deletingId = null;
            this.openActionsId = null;

            this.$wire.replyTo(id);
            this.$wire.cancelEdit();
            this.$nextTick(() => ta()?.focus());
        },

        startEdit(id, body) {
            if (!id) return;
            this.replyingTo = null;
            this.deletingId = null;
            this.editingMsgId = id;
            this.editingOriginal = this.editingBody = body || '';
            this.isEditing = true;
            this.resetAiEdit();

            this.$wire.cancelReply();
            return this.$wire.editMessage(id);
        },

        cancelEdit() {
            this._clearEdit();
            this.$wire.cancelEdit();
        },

        async undoDelete() {
            const wire = this.$wire;
            try {
                await this._isl().undoDelete();
                this._syncSidebarAfterOwnAction(wire);
            } catch {}
        },

        async saveEdit(id) {
            if (!id || id !== this.editingMsgId || this._savingEdit) return;
            const body = this.editingBody || '';
            if (body === this.editingOriginal) {
                this.toast('متن تغییری نکرده است؛ چیزی برای ذخیره نبود.', 'warning');
                return this.cancelEdit();
            }
            if (this._overLimit(body)) return;

            const wire = this.$wire;
            this._savingEdit = true;
            try {
                await this._isl().saveEdit(id, body);
                this._syncSidebarAfterOwnAction(wire);
            } catch {
                this.toast('ذخیرهٔ ویرایش تأیید نشد. پیش از تلاش دوباره، اتصال اینترنت را بررسی کنید.', 'error');
            } finally {
                this._savingEdit = false;
            }
        },

        confirmDelete(id) {
            if (!id) return;
            this.replyingTo = null;
            this._clearEdit();
            this.deletingId = id;
        },

        async doCancelInvite(channelId, userId) {
            const wire = this.$wire;
            await wire.$island('messages').cancelInvite(channelId, userId);
            wire.$island('sidebar').refreshUnread();
        },

        async doChangeType(id, type) {
            if (!id) return;
            const wire = this.$wire;
            try {
                await wire.$island('messages').changeChannelType(id, type);
                wire.$island('sidebar').refreshUnread();
            } catch {
                this.toast('خطا در تغییر نوع گروه.', 'error');
            }
        },

        openChannelKey() {
            this.keyOpen = true;
        },

        closeChannelKey() {
            this.keyOpen = false;
            this.channelKey = '';
        },

        async saveChannelKey(id, clear = false) {
            if (!id) return;
            const code = clear ? '' : this.channelKey;
            try {
                await this._isl().saveChannelKey(id, code);
            } catch {
                this.toast('خطا در ثبت کلید گروه.', 'error');
            } finally {
                this.closeChannelKey();
            }
        },

        async doDeleteChannel(id) {
            if (!id) return;
            const wire = this.$wire;
            try {
                await wire.$island('messages').deleteChannel(id);
                this._afterPaneSwap();
                wire.$island('sidebar').refreshUnread();
                this.syncChannelCount();
            } catch {
                this.toast('خطا در حذف گروه.', 'error');
            }
        },

        async sendMessage() {
            if (this.sending) return;

            const body = ta()?.value?.trim() ?? '';
            if (!body && !(this.$wire.composer?.attachments?.length)) {
                this.toast('پیام نمی‌تواند خالی باشد.', 'warning');
                return;
            }
            if (this._overLimit(body)) return;

            this.sending = true;
            const seq = this._sendSeq = (this._sendSeq || 0) + 1;
            const wire = this.$wire;
            const ghost = !(wire.composer?.attachments?.length) && !this.replyingTo && !wire.hasNewer
                ? this._ghostShow(body)
                : null;
            try {
                if (await this._deliver(body) === null) return;
                this._syncSidebarAfterOwnAction(wire);
                this.$nextTick(() => { this.scrollToBottom(true); ta()?.focus(); this.autoGrow(ta(), 160); this.autoDirection(ta()); });
            } catch {
                this.toast('هنگام پردازش پیام مشکلی پیش آمد. پیش از ارسال دوباره، اتصال اینترنت را بررسی کنید.', 'error');
            } finally {
                this._ghostHide(ghost);
                setTimeout(() => { if (this._sendSeq === seq) this.sending = false; }, SEND_LOCK_RESET_MS);
            }
        },
    };
}
