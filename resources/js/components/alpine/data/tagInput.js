export default function tagInput() {
    return {
        value: '',

        get blank() {
            return !this.value.trim();
        },

        add() {
            const tag = this.value.trim();
            this.value = '';
            if (!tag) return;
            this.$wire?.[this.$root.dataset.add]?.(tag);
        },
    };
}
