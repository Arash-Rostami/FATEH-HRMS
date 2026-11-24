export default function aiEditMixin() {
    return {
        aiEditBusy: false,
        aiEditId: null,
        aiEditNote: '',
        aiEditSoft: true,
        aiEditOk: false,
        aiEditMeter: '',

        resetAiEdit() {
            this.aiEditNote = '';
            this.aiEditOk = false;
        },

        async enhanceFromMenu(id, body) {
            if (!id || this.aiEditBusy) return;
            const opened = this.startEdit(id, body || '');
            const ta = this.aiEditBox(id);
            if (ta && body) {
                ta.value = body;
                this.autoDirection(ta);
                this.fitEdit(ta);
            }
            try { await opened; } catch { return; }
            return this.enhanceEdit(id);
        },

        async enhanceEdit(id, btn) {
            if (!id || id !== this.aiEditCurrentId() || this.aiEditBusy) return;

            const ta = btn?.closest('[data-edit-box]')?.querySelector('textarea') ?? this.aiEditBox(id);
            const text = (ta?.value ?? '').trim();
            if (!text) return this.toast('متن پیام خالی است.', 'warning');
            if (this._overLimit(text)) return;

            let refocus = false;
            this.aiEditBusy = true;
            this.aiEditId = id;
            this.resetAiEdit();

            try {
                const r = await this._isl().enhanceEdit(id, text);
                if (r.meter) this.aiEditMeter = r.meter;
                if (id !== this.aiEditCurrentId()) return;

                this.aiEditNote = r.message || r.note || '';
                this.aiEditSoft = r.soft !== false;

                if (!r.answer || !ta) return;

                if ((ta.value ?? '').trim() !== text) {
                    this.aiEditNote = 'متن تغییر کرد. برای متن تازه، دوباره پیشنهاد بگیرید.';
                    this.aiEditSoft = true;
                    return;
                }

                ta.value = r.answer;
                this.autoDirection(ta);
                const active = document.activeElement;
                if (active === ta || active === document.body || ta.closest('[data-edit-box]')?.contains(active)) {
                    this.fitEdit(ta);
                    refocus = true;
                } else {
                    ta.style.height = 'auto';
                    ta.style.height = Math.min(ta.scrollHeight, 320) + 'px';
                }
                this.aiEditApply(r.answer);
                this.aiEditOk = true;
                if (r.note) this.aiEditNote = r.note;
            } catch (e) {
                console.error('smart-edit', e);
                if (id === this.aiEditCurrentId()) this.toast('ارتباط با سرویس هوش مصنوعی برقرار نشد. کمی بعد دوباره تلاش کنید.', 'error');
            } finally {
                this.aiEditBusy = false;
                this.aiEditId = null;
            }

            if (!refocus) return;
            const focus = () => {
                if (id !== this.aiEditCurrentId()) return;
                ta.focus({ preventScroll: true });
                ta.selectionStart = ta.selectionEnd = ta.value.length;
            };
            this.$nextTick ? this.$nextTick(focus) : focus();
        },
    };
}
