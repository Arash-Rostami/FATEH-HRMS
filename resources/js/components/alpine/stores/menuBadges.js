export default function menuBadgesStore(Alpine) {
    Alpine.store('menuBadges', {
        s: {},

        set(key, active) {
            this.s[key] = !!active;
        },

        on(key, fallback) {
            return key in this.s ? this.s[key] : !!fallback;
        }
    });
}
