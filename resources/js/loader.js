// Full-screen loading screen for logging in, logging out and creating an account (x-loader in the layout).
// It covers the page switch: a start time kept in sessionStorage lets the next page carry on mid-animation.
// Start it with $dispatch('px-loading', { kind, first }) on a submit that can't fail, or flash
// session('loader') on success for one that can (so a wrong password never shows it).
const KINDS = {
    login: { ms: 1600, title: 'Logging you in', sub: (f) => (f ? `Welcome back, ${f}.` : 'Welcome back.'), steps: ['Checking your details', 'Loading your open plays', 'Getting your queue spot ready'], kicker: 'Open play, organized' },
    logout: { ms: 1200, title: 'Logging you out', sub: (f) => (f ? `See you on the court, ${f}.` : 'See you on the court.'), steps: ['Saving your changes', 'Signing you out safely'], kicker: 'You can log back in any time' },
    signup: { ms: 2000, title: 'Creating your account', sub: (f) => (f ? `Hi ${f}, setting things up.` : 'Setting things up.'), steps: ['Creating your player profile', 'Finding open plays near you', 'Getting your rankings ready'], kicker: 'Free for players, No DUPR' },
    'signup-org': { ms: 2000, title: 'Creating your account', sub: (f) => (f ? `Hi ${f}, one moment.` : 'One moment.'), steps: ['Creating your account', 'Sending your club to the PickleX admin'], kicker: 'You can play while you wait for approval' },
};
const KEY = 'px-loader';
const read = () => { try { return JSON.parse(sessionStorage.getItem(KEY)); } catch { return null; } };
const write = (v) => { try { v ? sessionStorage.setItem(KEY, JSON.stringify(v)) : sessionStorage.removeItem(KEY); } catch {} };

document.addEventListener('alpine:init', () => {
    window.Alpine.data('pxLoader', (flash, me) => ({
        k: null, first: '', e: 0, out: false, timer: null,

        init() {
            const s = read();
            if (s && KINDS[s.kind] && Date.now() - s.t0 < KINDS[s.kind].ms) return this.play(s);
            write(null);
            if (flash && KINDS[flash.kind]) this.start(flash.kind, flash.first);
        },

        start(kind, first) {
            if (! KINDS[kind]) return;
            const s = { kind, first: first || '', t0: Date.now() };
            write(s);
            this.play(s);
        },

        play(s) {
            const K = KINDS[s.kind];
            clearTimeout(this.timer);
            this.k = K; this.out = false;
            this.first = s.first || me || '';
            this.e = Math.min(K.ms, Date.now() - s.t0) / 1000;
            this.timer = setTimeout(() => {
                this.out = true;
                this.timer = setTimeout(() => { this.k = null; write(null); }, 300);
            }, K.ms - this.e * 1000);
        },

        // Each step fades in, then its spinner turns into a check. Negative delays resume mid-way on a new page.
        step() { return this.k.ms / 1000 / (this.k.steps.length + 0.3); },
        inAt(i) { return `animation-delay: ${(i * this.step() - this.e).toFixed(2)}s`; },
        doneAt(i) { return `animation-delay: ${((i + 0.9) * this.step() - this.e).toFixed(2)}s`; },
        bar() { return `animation-duration: ${this.k.ms / 1000}s; animation-delay: ${-this.e}s`; },
    }));
});
