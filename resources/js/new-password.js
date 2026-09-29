// Live checklist + strength meter for "Set a new password". The rules mirror
// ForgotPassword::rules(); the server checks them again on save.

export const check = (pass, email) => {
    const p = String(pass || '');
    const low = p.toLowerCase();
    const local = String(email || '').split('@')[0].toLowerCase();
    const personal = local.length >= 3 && low.includes(local);
    const rules = [
        ['At least 8 characters', p.length >= 8],
        ['A letter and a number', /[a-z]/i.test(p) && /\d/.test(p)],
        ["Doesn't include your email or name", p.length > 0 && !personal],
    ];
    let score = 0;
    if (p) {
        score = 1;
        if (rules[0][1] && rules[1][1]) score = 2;
        if (score === 2 && (p.length >= 12 || /[^a-z0-9]/i.test(p) || (/[a-z]/.test(p) && /[A-Z]/.test(p)))) score = 3;
        if (score === 3 && p.length >= 10 && /[^a-z0-9]/i.test(p) && /[a-z]/.test(p) && /[A-Z]/.test(p)) score = 4;
        if (personal) score = Math.min(score, 1);
    }
    return { rules, score };
};

document.addEventListener('alpine:init', () => {
    window.Alpine.data('newPassword', (email) => ({
        show: false,
        get result() { return check(this.$wire.password, email); },
        get label() { return this.$wire.password ? 'Strength: ' + ['', 'Weak', 'Fair', 'Good', 'Strong'][this.result.score] : 'Strength shows as you type'; },
        get labelClass() { return ['text-ink3', 'text-hot', 'text-ink2', 'text-lime-text', 'text-lime-text'][this.result.score]; },
        bar(n) {
            const s = this.result.score;
            if (n > s) return 'bg-line';
            return s <= 1 ? 'bg-hot' : s === 2 ? 'bg-[#E0A526]' : 'bg-lime-text';
        },
        get match() {
            const c = this.$wire.confirm;
            if (!c) return null;
            return c === this.$wire.password;
        },
    }));
});
