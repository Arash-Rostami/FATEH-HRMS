import { feedReactions } from "../stores/emoji.js";

export default (feedId, selected = null) => ({
    feedId,
    per: 7,
    page: 0,
    selected,
    emojis: (selected && feedReactions.includes(selected))
        ? [selected, ...feedReactions.filter((e) => e !== selected)]
        : [...feedReactions],

    next() {
        this.page = (this.page + 1) % Math.ceil(this.emojis.length / this.per);
    },
});
