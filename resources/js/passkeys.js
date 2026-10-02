// Passkeys: "Log in with a passkey" (login page) and Account settings › Passkeys.
// The device's own fingerprint / face / screen-lock prompt does the real work (WebAuthn via @simplewebauthn/browser);
// the server side is spatie/laravel-passkeys (routes/web.php passkeys.*, Settings::storePasskey()).
import { browserSupportsWebAuthn, startAuthentication, startRegistration } from '@simplewebauthn/browser';

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content
    || decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');

// Which passkeys were created on this browser, per account, so Settings can mark "This device".
const HERE = (userId) => `px-passkeys-here:${userId}`;
const readHere = (userId) => { try { return JSON.parse(localStorage.getItem(HERE(userId))) || []; } catch { return []; } };
const writeHere = (userId, ids) => { try { localStorage.setItem(HERE(userId), JSON.stringify(ids)); } catch {} };

const cancelled = (e) => e?.name === 'NotAllowedError' || e?.name === 'AbortError';

document.addEventListener('alpine:init', () => {
    // Login page: the dialog stays up while the device asks; cancel just closes it.
    window.Alpine.data('passkeyLogin', () => ({
        open: false,
        error: '',
        supported: browserSupportsWebAuthn(),

        async start() {
            this.error = '';
            this.open = true;
            if (! this.supported) {
                this.error = "This browser can't use passkeys. Log in with your password instead.";

                return;
            }
            try {
                const options = await fetch(this.$root.dataset.options, { headers: { Accept: 'application/json' }, credentials: 'same-origin' }).then((r) => r.json());
                const response = await startAuthentication({ optionsJSON: options });
                const res = await fetch(this.$root.dataset.login, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-XSRF-TOKEN': csrf() },
                    body: JSON.stringify({ response: JSON.stringify(response) }),
                });
                const data = await res.json().catch(() => ({}));
                if (! res.ok) throw Object.assign(new Error(data.message), { shown: true });
                window.location.href = data.redirect;
            } catch (e) {
                if (cancelled(e)) {
                    this.open = false;

                    return;
                }
                this.error = e.shown ? e.message : "That passkey didn't work. Try again, or log in with your password.";
            }
        },
    }));

    // Account settings › Passkeys: create on this device, then name it (Settings::storePasskey / namePasskey).
    window.Alpine.data('passkeySettings', (userId, ids) => ({
        here: readHere(userId).filter((id) => ids.includes(id)),
        supported: browserSupportsWebAuthn(),
        step: null, // null | wait (device prompt) | name
        error: '',

        isHere(id) { return this.here.includes(id); },

        async add() {
            this.error = '';
            if (! this.supported) {
                this.$dispatch('toast', { message: "This browser can't create passkeys" });

                return;
            }
            this.step = 'wait';
            try {
                const options = JSON.parse(await this.$wire.passkeyOptions());
                const attestation = await startRegistration({ optionsJSON: options });
                const id = await this.$wire.storePasskey(JSON.stringify(attestation), this.here[0] ?? null);
                if (! id) throw new Error('not saved');
                this.here = [id];
                writeHere(userId, this.here);
                this.step = null; // the server shows the name step (Settings::$naming)
            } catch (e) {
                this.step = null;
                if (! cancelled(e)) this.$dispatch('toast', { message: "Couldn't save that passkey. Try again." });
            }
        },
    }));
});
