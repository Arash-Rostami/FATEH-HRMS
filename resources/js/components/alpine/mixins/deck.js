const SELECTOR = '[data-resource-id]';
const ATTR_RESOURCE_ID = 'data-resource-id';

export default function deckMixin() {
    return {
        deckOrder: [],

        deckSync(ids) {
            const incArray = ids || [];
            const incLen = incArray.length;
            const currentOrder = this.deckOrder;
            const curLen = currentOrder.length;

            if (incLen === 0) {
                if (curLen !== 0) this.deckOrder = [];
                return;
            }

            const incomingSet = new Set();
            for (let i = 0; i < incLen; i++) {
                incomingSet.add(Number(incArray[i]));
            }

            const nextOrder = [];
            const knownSet = new Set();

            for (let i = 0; i < curLen; i++) {
                const id = currentOrder[i];
                if (incomingSet.has(id)) {
                    nextOrder.push(id);
                    knownSet.add(id);
                }
            }

            for (let i = 0; i < incLen; i++) {
                const id = Number(incArray[i]);
                if (!knownSet.has(id)) {
                    nextOrder.push(id);
                }
            }

            const nextLen = nextOrder.length;
            let changed = nextLen !== curLen;

            if (!changed) {
                for (let i = 0; i < nextLen; i++) {
                    if (nextOrder[i] !== currentOrder[i]) {
                        changed = true;
                        break;
                    }
                }
            }

            if (changed) {
                this.deckOrder = nextOrder;
            }
        },

        deckSyncFromDom(root) {
            const scope = root || document;
            const nodes = scope.querySelectorAll(SELECTOR);
            const len = nodes.length;
            const parsedIds = new Array(len);

            for (let i = 0; i < len; i++) {
                parsedIds[i] = Number(nodes[i].getAttribute(ATTR_RESOURCE_ID));
            }

            this.deckSync(parsedIds);
        },

        deckDepth(id) {
            const i = this.deckOrder.indexOf(Number(id));
            return i < 0 ? this.deckOrder.length : i;
        },

        deckBringToFront(id) {
            const num = Number(id);
            const current = this.deckOrder;
            const idx = current.indexOf(num);

            if (idx === 0) return;

            const next = current.slice();
            if (idx !== -1) {
                next.splice(idx, 1);
            }
            next.unshift(num);
            this.deckOrder = next;
        },

        deckCycle(dir) {
            const current = this.deckOrder;
            if (current.length < 2) return null;

            const rotated = current.slice();
            if (dir > 0) {
                rotated.push(rotated.shift());
            } else {
                rotated.unshift(rotated.pop());
            }

            this.deckOrder = rotated;
            return rotated[0];
        },

        deckOpen(id) {
            this.deckBringToFront(id);
            this.$wire.focusDeck(Number(id));
        },

        deckCycleAndOpen(dir) {
            const id = this.deckCycle(dir);
            if (id !== null) {
                this.$wire.focusDeck(id);
            }
        }
    };
}
