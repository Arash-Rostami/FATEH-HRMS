const MAX_LABELS = 10;

let checklistUid = 0;

function equalWeights(n) {
    if (n <= 0) return [];
    const base = (100 / n) | 0;
    const remainder = 100 - base * n;
    const out = new Array(n);
    for (let i = 0; i < n; i++) {
        out[i] = base + (i < remainder ? 1 : 0);
    }
    return out;
}

function cloneChecklistPayload(list) {
    const len = list.length;
    const payload = new Array(len);
    for (let i = 0; i < len; i++) {
        const item = list[i];
        payload[i] = {
            text: item.text,
            done: item.done === true,
            weight: Number(item.weight) || 0
        };
    }
    return payload;
}

export default function taskFormMixin() {
    return {
        formReady: false,
        formTab: "content",
        foldLabels: true,
        foldChecklist: true,
        newLabel: "",
        newChecklistItem: "",
        labels: [],
        checklist: [],
        existingAttachments: [],
        checklistDragIndex: null,
        editingChecklistIndex: null,

        _labelSet: null,

        syncFormArrays() {
            const wire = this.$wire?.form || {};
            this.labels = Array.isArray(wire.labels) ? wire.labels.slice() : [];
            this._labelSet = new Set(this.labels);

            const src = Array.isArray(wire.checklist) ? wire.checklist : [];
            const len = src.length;
            const list = new Array(len);
            let weightSum = 0;

            for (let i = 0; i < len; i++) {
                const row = src[i];
                const weight = Number(row.weight);
                const w = Number.isFinite(weight) ? Math.max(0, Math.min(100, Math.round(weight))) : 0;
                weightSum += w;
                list[i] = {
                    _uid: ++checklistUid,
                    text: String(row.text || ""),
                    done: Boolean(row.done),
                    weight: w
                };
            }

            if (len > 0 && weightSum !== 100) {
                const shares = equalWeights(len);
                for (let i = 0; i < len; i++) {
                    list[i].weight = shares[i];
                }
            }

            this.checklist = list;
            this.existingAttachments = Array.isArray(wire.existingAttachments) ? wire.existingAttachments.slice() : [];
        },

        get labelsCount() {
            return this.labels.length;
        },

        get checklistTotal() {
            return this.checklist.length;
        },

        get checklistCompleted() {
            const list = this.checklist;
            let count = 0;
            for (let i = 0, len = list.length; i < len; i++) {
                if (list[i].done) count++;
            }
            return count;
        },

        get checklistTotalWeight() {
            const list = this.checklist;
            let sum = 0;
            for (let i = 0, len = list.length; i < len; i++) {
                sum += Number(list[i].weight) || 0;
            }
            return sum;
        },

        get checklistCompletedWeight() {
            const list = this.checklist;
            let sum = 0;
            for (let i = 0, len = list.length; i < len; i++) {
                if (list[i].done) {
                    sum += Number(list[i].weight) || 0;
                }
            }
            return sum;
        },

        get checklistProgress() {
            const total = this.checklistTotalWeight;
            if (total <= 0) return 0;
            return Math.min(100, Math.max(0, Math.round((this.checklistCompletedWeight / total) * 100)));
        },

        get actionBadge() {
            const uploads = this.$wire?.form?.attachments;
            return this.existingAttachments.length + (Array.isArray(uploads) ? uploads.length : 0);
        },

        get followupBadge() {
            const collaborators = this.$wire?.form?.collaborators;
            return this.checklistTotal + this.actionBadge + (Array.isArray(collaborators) ? collaborators.length : 0);
        },

        get infoBadge() {
            return this.labelsCount;
        },

        addLabelItem() {
            const val = this.newLabel.trim();
            this.newLabel = "";
            if (!val || this.labels.length >= MAX_LABELS || this._labelSet.has(val)) return;

            this.labels.push(val);
            this._labelSet.add(val);
            this.$wire?.set("form.labels", this.labels.slice(), false);
        },

        removeLabel(index) {
            const val = this.labels[index];
            if (val === undefined) return;

            this.labels.splice(index, 1);
            this._labelSet.delete(val);
            this.$wire?.set("form.labels", this.labels.slice(), false);
        },

        addChecklist() {
            const val = this.newChecklistItem.trim();
            if (!val) return;

            this.checklist.push({
                _uid: ++checklistUid,
                text: val,
                done: false,
                weight: 0
            });
            this._redistributeChecklistWeightsEqually();
            this.newChecklistItem = "";
            this._pushChecklist();
        },

        removeChecklistItem(index) {
            if (index < 0 || index >= this.checklist.length) return;

            this.checklist.splice(index, 1);
            this._redistributeChecklistWeightsEqually();
            this._pushChecklist();
        },

        toggleChecklistItem(index) {
            const item = this.checklist[index];
            if (!item) return;

            item.done = !item.done;
            this._pushChecklist();
        },

        _redistributeChecklistWeightsEqually() {
            const list = this.checklist;
            const len = list.length;
            if (len === 0) return;

            const shares = equalWeights(len);
            for (let i = 0; i < len; i++) {
                list[i].weight = shares[i];
            }
        },

        _pushChecklist() {
            this.$wire?.set("form.checklist", cloneChecklistPayload(this.checklist), false);
        },

        setChecklistWeight(index, rawValue, persist = true) {
            const list = this.checklist;
            const item = list[index];
            if (!item) return;

            let value = Math.round(Number(rawValue));
            if (!Number.isFinite(value)) value = 0;
            value = Math.max(0, Math.min(100, value));

            const len = list.length;
            if (len <= 1) {
                item.weight = 100;
                if (persist) this._pushChecklist();
                return;
            }

            if (item.weight === value && persist) {
                this._pushChecklist();
                return;
            }

            let othersTotal = 0;
            for (let i = 0; i < len; i++) {
                if (i !== index) othersTotal += Number(list[i].weight) || 0;
            }

            const remaining = 100 - value;

            if (othersTotal <= 0) {
                const shares = equalWeights(len - 1);
                let k = 0;
                for (let i = 0; i < len; i++) {
                    if (i !== index) {
                        list[i].weight = Math.round((shares[k++] / 100) * remaining);
                    }
                }
            } else {
                let assigned = 0;
                let seen = 0;
                const totalOthers = len - 1;

                for (let i = 0; i < len; i++) {
                    if (i === index) continue;
                    seen++;
                    if (seen === totalOthers) {
                        list[i].weight = Math.max(0, remaining - assigned);
                    } else {
                        const share = Math.max(0, Math.round(((Number(list[i].weight) || 0) / othersTotal) * remaining));
                        list[i].weight = share;
                        assigned += share;
                    }
                }
            }

            item.weight = value;
            if (persist) this._pushChecklist();
        },

        startChecklistWeightDrag(event, index) {
            const targetEl = event.target;
            if (targetEl.closest("input, button") && !targetEl.closest('[role="slider"]')) return;
            if (!this.checklist[index]) return;

            const target = event.currentTarget;
            const rowEl = target.classList.contains("group") ? target : target.closest(".group");
            if (!rowEl) return;

            const rect = rowEl.getBoundingClientRect();
            if (rect.width <= 0) return;

            event.preventDefault();
            this.checklistDragIndex = index;

            const left = rect.left;
            const width = rect.width;
            let raf = 0;
            let lastX = event.clientX;

            const apply = (clientX) => {
                const raw = ((clientX - left) / width) * 100;
                const clamped = Math.max(0, Math.min(100, Math.round(raw)));
                if (this.checklist[index].weight !== clamped) {
                    this.setChecklistWeight(index, clamped, false);
                }
            };

            const onMove = (e) => {
                lastX = e.clientX;
                if (raf) return;
                raf = requestAnimationFrame(() => {
                    raf = 0;
                    apply(lastX);
                });
            };

            const onUp = (e) => {
                if (raf) cancelAnimationFrame(raf);
                apply(e.clientX);
                this.checklistDragIndex = null;
                this._pushChecklist();
                window.removeEventListener("pointermove", onMove);
                window.removeEventListener("pointerup", onUp);
                window.removeEventListener("pointercancel", onUp);
            };

            window.addEventListener("pointermove", onMove, { passive: true });
            window.addEventListener("pointerup", onUp, { passive: true });
            window.addEventListener("pointercancel", onUp, { passive: true });
        },

        startEditingChecklistItem(index) {
            if (!this.checklist[index]) return;
            this.editingChecklistIndex = index;
        },

        saveChecklistItemText(index) {
            const item = this.checklist[index];
            if (!item) return;

            const val = (item.text || "").trim();
            if (val) {
                item.text = val;
                this._pushChecklist();
            }
            this.editingChecklistIndex = null;
        },

        removeExistingAttachment(index) {
            if (index < 0 || index >= this.existingAttachments.length) return;

            this.existingAttachments.splice(index, 1);
            this.$wire?.set("form.existingAttachments", this.existingAttachments.slice(), false);
        }
    };
}
