import maximizeMixin from '../mixins/maximize.js';
import clipboardMixin from '../mixins/clipboard.js';
import mentionMixin from '../mixins/mention.js';
import kanbanDragMixin from '../mixins/kanbanDrag.js';
import taskFormMixin from '../mixins/taskForm.js';
import { feedReactions } from '../stores/emoji.js';

const BASE_INTERVAL_MS = 10000;
const MAX_INTERVAL_MS = 60000;
const PULSE_CHANNEL_NAME = 'project-pulse';
const TAB_DOMAIN = {
    activity: 'activity',
    teamChat: 'chat',
    projectCalendar: 'task',
    kanban: 'task',
    report: 'task',
};
const TAB_REFRESH_EVENT = {
    activity: 'project-activity-refresh',
    teamChat: 'project-teamchat-refresh',
    projectCalendar: 'project-calendar-refresh',
    kanban: 'project-kanban-refresh',
    report: 'project-report-refresh',
};
const WARMABLE_TABS = new Set(['report', 'analytics', 'projectCalendar', 'kanban', 'activity']);

export default function project() {
    const workspace = {
        ...maximizeMixin(),
        ...clipboardMixin(),
        ...mentionMixin(),
        ...kanbanDragMixin(),
        _timer: null,
        _intervalMs: BASE_INTERVAL_MS,
        _lastVersion: null,
        _loadingOlderActivity: false,
        _onVisibility: null,
        _searchLowerCache: '',
        _searchLowerValue: '',
        _isDestroyed: false,
        _commitHook: null,
        _warmTimer: null,
        _pulseChannel: null,
        _isLeader: true,
        _leaderProjectId: null,
        _releaseLeadership: null,
        mentionOpen: false,
        mentionQuery: '',
        mentionActiveIndex: 0,
        sending: false,
        showScrollFab: false,
        backgroundPattern: 'off',
        isHighlighted: false,
        activitySearch: '',
        activityPinnedOnly: false,
        activityTypeFilter: '',
        activityDeletingId: null,
        mobileShowChat: false,

        init() {
            this.initKanbanDrag();
            this._isDestroyed = false;

            const chatSettings = localStorage.getItem('chat-settings');
            if (chatSettings) {
                try {
                    const data = JSON.parse(chatSettings);
                    this.isHighlighted = data.isHighlighted ?? false;
                    this.backgroundPattern = data.backgroundPattern ?? 'off';
                } catch {}
            }

            this._setupPulseChannel();

            const wire = this.$wire;
            this._electLeaderFor(wire.activeProjectId);

            const wireId = wire.__instance.id;
            this._commitHook = Livewire.hook('commit', ({ component, succeed }) => {
                if (this._isDestroyed || component.id !== wireId) return;
                succeed(() => { this._syncVersionSilently(); });
            });

            this.startPolling();

            this._onVisibility = () => document.hidden ? this.stopPolling() : this.startPolling();
            document.addEventListener('visibilitychange', this._onVisibility);

            wire.on('show-toast', (e) => this.toast?.(e.message, e.type ?? 'info'));
            this.$el.addEventListener('activity-typed', () => this.resetInterval());

            const focusEntry = new URLSearchParams(window.location.search).get('focus_entry');
            if (focusEntry) {
                this.scrollToActivityEntry(focusEntry);
            }

            const chatVp = document.getElementById('team-chat-viewport');
            if (chatVp) {
                chatVp.style.overflowAnchor = 'none';
                let rafId = null;
                chatVp.addEventListener('scroll', () => {
                    if (rafId) return;
                    rafId = requestAnimationFrame(() => {
                        this.showScrollFab = (chatVp.scrollHeight - chatVp.scrollTop - chatVp.clientHeight) > 200;
                        rafId = null;
                    });
                }, { passive: true });
            }
        },

        destroy() {
            this._isDestroyed = true;
            this.stopPolling();
            this.cancelWarm();
            document.removeEventListener('visibilitychange', this._onVisibility);

            if (typeof this._commitHook === 'function') {
                this._commitHook();
                this._commitHook = null;
            }

            this._releaseLeadership?.();
            this._pulseChannel?.close();
        },

        toast(message, type = 'info') {
            if (!message) return;
            window.dispatchEvent(new CustomEvent('toast', {detail: {message, type}}));
        },

        async loadOlderActivity() {
            if (this._loadingOlderActivity) return;

            const vp = document.getElementById('activity-viewport');
            let prevHeight = 0;

            if (vp) {
                vp.style.overflowAnchor = 'none';
                prevHeight = vp.scrollHeight;
            }

            this._loadingOlderActivity = true;

            try {
                await this.$wire.loadOlderActivity();

                this.$nextTick(() => {
                    if (!vp) return;
                    const delta = vp.scrollHeight - prevHeight;
                    if (delta > 0) vp.scrollTop += delta;
                });
            } catch {}

            this._loadingOlderActivity = false;
        },

        get _searchLower() {
            if (this._searchLowerCache !== this.activitySearch) {
                this._searchLowerCache = this.activitySearch;
                this._searchLowerValue = this.activitySearch.toLowerCase();
            }
            return this._searchLowerValue;
        },

        matchesActivityFilter(id, text, type) {
            if (this.activityTypeFilter && type !== this.activityTypeFilter) return false;
            if (this.activityPinnedOnly && !this.$store.pinned.isPinned(id, 'activity')) return false;
            if (!this.activitySearch) return true;
            if (!text) return false;
            return text.toLowerCase().includes(this._searchLower);
        },

        anyActivityVisible(entries) {
            for (let i = 0, len = entries.length; i < len; i++) {
                if (this.matchesActivityFilter(entries[i][0], entries[i][1], entries[i][4])) return true;
            }
            return false;
        },

        exportActivity(entries) {
            const lines = [];
            for (let i = 0, len = entries.length; i < len; i++) {
                const entry = entries[i];
                if (this.matchesActivityFilter(entry[0], entry[1], entry[4])) {
                    lines.push(`[${entry[3]}] ${entry[2]}: ${entry[1]}`);
                }
            }
            if (lines.length === 0) return;
            const blob = new Blob([lines.join('\n')], { type: 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'activity.txt';
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
        },

        confirmDeleteComment(id) {
            this.activityDeletingId = id;
        },

        get activityReactions() {
            return feedReactions;
        },

        toggleReactionAndClose(id, emoji) {
            this.$store.activityReactionPicker.close();
            this.$wire.toggleReaction(id, emoji).catch(() => {});
        },

        scrollToActivityEntry(id, attempt = 0) {
            const el = document.querySelector(`[data-rf="activity-${id}"]`);

            if (!el) {
                if (attempt < 40) setTimeout(() => this.scrollToActivityEntry(id, attempt + 1), 75);
                return;
            }

            const flashes = document.querySelectorAll('.record-focus-flash');
            for (let i = 0, len = flashes.length; i < len; i++) {
                flashes[i].classList.remove('record-focus-flash');
            }

            el.style.animation = 'none';
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.classList.add('record-focus-flash');
        },

        cancelDeleteComment() {
            this.activityDeletingId = null;
        },

        scrollToBottom(smooth = false) {
            const vp = document.getElementById('team-chat-viewport');
            if (vp) {
                vp.scrollTo({
                    top: 999999,
                    behavior: smooth ? 'smooth' : 'instant',
                });
            }
        },

        toggleHighlight() {
            this.isHighlighted = !this.isHighlighted;
            this.backgroundPattern = this.backgroundPattern === 'on' ? 'off' : 'on';
            try {
                localStorage.setItem('chat-settings', JSON.stringify({
                    isHighlighted: this.isHighlighted,
                    backgroundPattern: this.backgroundPattern
                }));
            } catch {}
        },

        get mentionMatches() {
            if (!this.mentionOpen) return [];
            return this.mentionFilter(this.$wire.mentionMemberNames || [], this.mentionQuery).slice(0, 8);
        },

        openMentionPicker() {
            const ta = document.getElementById('team-chat-ta');
            if (!ta) return;

            const pos = ta.selectionStart ?? ta.value.length;
            const val = ta.value;

            let needsSpace = false;
            if (pos > 0) {
                const code = val.charCodeAt(pos - 1);
                needsSpace = code !== 32 && code !== 10 && code !== 9 && code !== 13;
            }

            const insert = (needsSpace ? ' @' : '@');
            const next = val.slice(0, pos) + insert + val.slice(pos);

            ta.value = next;
            const at = pos + (needsSpace ? 1 : 0);
            ta.focus();
            ta.selectionStart = ta.selectionEnd = at + 1;

            const wire = this.$wire;
            wire.set('chatComposer.body', next);
            wire.loadMentionMemberNames().catch(() => {});
            this.mentionQuery = '';
            this.mentionActiveIndex = 0;
            this.mentionOpen = true;
        },

        detectMention(e) {
            const ta = e.target;
            const t = this.mentionAtTerm(ta.value, ta.selectionStart);
            if (!t) { this.mentionOpen = false; return; }
            if (!this.mentionOpen) this.$wire.loadMentionMemberNames().catch(() => {});
            this.mentionQuery = t.term;
            this.mentionActiveIndex = 0;
            this.mentionOpen = true;
        },

        onComposerKeydown(e) {
            if (this.mentionOpen && this.mentionMatches.length) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    this.mentionActiveIndex = (this.mentionActiveIndex + 1) % this.mentionMatches.length;
                    return;
                }
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    this.mentionActiveIndex = (this.mentionActiveIndex - 1 + this.mentionMatches.length) % this.mentionMatches.length;
                    return;
                }
                if (e.key === 'Enter' || e.key === 'Tab') {
                    e.preventDefault();
                    this.pickMention(this.mentionActiveIndex);
                    return;
                }
                if (e.key === 'Escape') {
                    e.preventDefault();
                    this.mentionOpen = false;
                    return;
                }
            }
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        },

        pickMention(i) {
            const name = this.mentionMatches[i];
            if (!name) { this.mentionOpen = false; return; }

            const ta = document.getElementById('team-chat-ta');
            if (!ta) { this.mentionOpen = false; return; }

            const r = this.mentionBuild(ta.value, ta.selectionStart, name);
            if (!r) { this.mentionOpen = false; return; }

            this.mentionOpen = false;
            this.mentionQuery = '';
            this.$wire.set('chatComposer.body', r.value);

            this.$nextTick(() => {
                ta.focus();
                ta.selectionStart = ta.selectionEnd = r.caret;
            });
        },

        async sendMessage() {
            if (this.sending) return;

            const wire = this.$wire;
            const ta = document.getElementById('team-chat-ta');
            const body = ta?.value ? ta.value.trim() : '';
            const attachments = wire.chatComposer?.attachments || [];

            if (body.length === 0 && attachments.length === 0) {
                this.toast?.('پیام نمی‌تواند خالی باشد.', 'warning');
                return;
            }

            if (body.length > 4000) {
                this.toast?.('متن پیام نباید بیشتر از ۴۰۰۰ کاراکتر باشد.', 'warning');
                return;
            }

            this.sending = true;

            try {
                await wire.sendChatMessage();
                this.$nextTick(() => this.scrollToBottom(true));
            } catch {
                this.toast?.('خطا در ارتباط با سرور.', 'error');
            } finally {
                setTimeout(() => { this.sending = false; }, 500);
            }
        },

        backToList() {
            this.mobileShowChat = false;
        },

        selectProject(id) {
            this.resetInterval();
            this._lastVersion = null;
            this._electLeaderFor(id);
            this.mobileShowChat = true;
            const wire = this.$wire;
            wire.$island('workspace').selectProject(id)
                .then(() => wire.$island('sidebar').refreshSidebar());
        },

        startPolling() {
            if (this._timer) return;
            this._scheduleTick();
        },

        stopPolling() {
            if (!this._timer) return;
            clearTimeout(this._timer);
            this._timer = null;
        },

        resetInterval() {
            this._intervalMs = BASE_INTERVAL_MS;
        },

        _scheduleTick() {
            const jitter = this._intervalMs * (0.9 + Math.random() * 0.2);
            this._timer = setTimeout(() => this._tick(), jitter);
        },

        _pulseUrl(projectId, activeTab) {
            return `/projects/${projectId}/pulse` + (activeTab === 'teamChat' ? '?tab=teamChat' : '');
        },

        async _fetchPulse(projectId, activeTab) {
            const response = await fetch(this._pulseUrl(projectId, activeTab), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            return response.json();
        },

        _setupPulseChannel() {
            if (typeof BroadcastChannel === 'undefined') return;

            this._pulseChannel = new BroadcastChannel(PULSE_CHANNEL_NAME);
            this._pulseChannel.onmessage = (e) => {
                if (this._isDestroyed) return;
                const msg = e.data;
                if (!msg || msg.projectId !== this.$wire.activeProjectId) return;
                this._applyPulse(msg.data);
            };
        },

        _electLeaderFor(projectId) {
            if (!('locks' in navigator) || !this._pulseChannel) {
                this._isLeader = true;
                return;
            }

            if (this._leaderProjectId === projectId) return;

            this._releaseLeadership?.();
            this._releaseLeadership = null;
            this._isLeader = false;
            this._leaderProjectId = projectId;

            if (!projectId) return;

            navigator.locks.request(`project-pulse-leader-${projectId}`, () => new Promise((resolve) => {
                if (this._isDestroyed || this._leaderProjectId !== projectId) {
                    resolve();
                    return;
                }
                this._isLeader = true;
                this._releaseLeadership = resolve;
            })).catch(() => {});
        },

        async _syncVersionSilently() {
            const wire = this.$wire;
            const projectId = wire.activeProjectId;
            if (!projectId) return;

            try {
                const data = await this._fetchPulse(projectId, wire.activeTab);
                if (!data.gone) this._lastVersion = data;
            } catch {}
        },

        async _tick() {
            const wire = this.$wire;
            const projectId = wire.activeProjectId;

            if (projectId) {
                this._electLeaderFor(projectId);
            }

            if (projectId && this._isLeader) {
                try {
                    const activeTab = wire.activeTab;
                    const data = await this._fetchPulse(projectId, activeTab);
                    this._pulseChannel?.postMessage({ projectId, data });
                    await this._applyPulse(data);
                } catch {}
            }

            if (this._timer !== null) {
                this._scheduleTick();
            }
        },

        async _applyPulse(data) {
            if (data.gone) {
                this.stopPolling();
                this.toast?.('شما دیگر عضو این گفتگو نیستید.', 'warning');
                await this.$wire.switchTab('activity');
                return;
            }

            if (this._lastVersion === null) {
                this._lastVersion = data;
                return;
            }

            const prev = this._lastVersion;
            const changedDomains = [];
            for (const domain in data) {
                if (data[domain] !== prev[domain]) changedDomains.push(domain);
            }
            this._lastVersion = data;

            if (!changedDomains.length) {
                this._intervalMs = Math.min(this._intervalMs * 2, MAX_INTERVAL_MS);
                return;
            }

            this.resetInterval();

            const wire = this.$wire;
            const activeTab = wire.activeTab;
            if (changedDomains.includes(TAB_DOMAIN[activeTab])) {
                await this._refreshActiveTab(activeTab);
            }

            wire.$island('workspace').markTabsDirtyExcept(activeTab, changedDomains).catch(() => {});
        },

        async _refreshActiveTab(tab) {
            const event = TAB_REFRESH_EVENT[tab];
            if (event) Livewire.dispatch(event);
        },

        warmTab(tab) {
            const wire = this.$wire;
            if (!WARMABLE_TABS.has(tab) || tab === wire.activeTab) return;

            this.cancelWarm();
            this._warmTimer = setTimeout(() => {
                this._warmTimer = null;
                wire.warm(tab).catch(() => {});
            }, 200);
        },

        cancelWarm() {
            if (!this._warmTimer) return;
            clearTimeout(this._warmTimer);
            this._warmTimer = null;
        },
    };

    return Object.defineProperties(workspace, Object.getOwnPropertyDescriptors(taskFormMixin()));
}
