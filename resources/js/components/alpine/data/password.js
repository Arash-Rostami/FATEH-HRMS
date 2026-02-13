export default function password() {
    return {
        showPassword: false,
        strength: 0,
        hasNumber: false,
        hasLetter: false,
        hasSpecial: false,
        letterCount: 0,

        checkStrength(val) {
            this.value = val;
            this.numberCount = (val.match(/\d/g) || []).length;
            this.hasNumber = this.numberCount > 7; // More than 7 numbers (8+)
            this.letterCount = (val.match(/[a-zA-Z]/g) || []).length;
            this.hasLetter = this.letterCount > 7; // More than 7 letters (8+)
            this.hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(val);

            let score = 0;
            if (val.length >= 6) score++;
            if (val.length >= 10) score++;
            // All three green only when: 8+ letters AND 8+ numbers AND special
            if (this.hasLetter && this.hasNumber && this.hasSpecial) score = 3;
            this.strength = score;
        },

        get bar1Color() {
            if (this.strength < 1) return '';
            // Green only if all character conditions met (8+ letters, 8+ numbers, special)
            const allGreen = this.hasLetter && this.hasNumber && this.hasSpecial;
            return allGreen ? 'bg-green-500' : 'bg-[var(--md-sys-color-error)]';
        },

        get bar2Color() {
            if (this.strength < 2) return '';
            const allGreen = this.hasLetter && this.hasNumber && this.hasSpecial;
            return allGreen ? 'bg-green-500' : 'bg-yellow-500';
        },

        get bar3Color() {
            if (this.strength < 3) return '';
            return 'bg-green-500';
        },

        get showIndicator() {
            return this.value.length > 0;
        }
    };
}
