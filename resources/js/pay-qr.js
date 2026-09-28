// Payment QR in Create open play. The image stays here until publish, then goes to
// OpenPlays::publish() as a data URL ("saved" reuses the QR from your last open play).

const TYPES = /^image\/(png|jpe?g|webp)$/i;
const MAX_SIDE = 900;

document.addEventListener('alpine:init', () => {
    window.Alpine.data('payQr', (savedUrl) => ({
        savedUrl,
        qr: savedUrl ? 'saved' : null, // null | 'saved' | data URL
        name: savedUrl ? 'Saved QR' : '',
        busy: false,
        over: false,
        check: '', // '' | 'ok' | 'none'
        error: '',

        get src() { return this.qr === 'saved' ? this.savedUrl : this.qr; },

        pick(event) {
            const file = event.target.files && event.target.files[0];
            event.target.value = '';
            if (file) this.load(file);
        },

        dropped(event) {
            this.over = false;
            const file = event.dataTransfer && event.dataTransfer.files[0];
            if (file) this.load(file);
        },

        load(file) {
            this.error = '';
            if (!TYPES.test(file.type || '')) return (this.error = "That file isn't an image we can use. Upload a PNG, JPG or WebP of your QR.");
            if (file.size > 5 * 1024 * 1024) return (this.error = 'That image is over 5 MB. A screenshot of the QR works best.');
            this.busy = true;
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onerror = () => {
                this.busy = false;
                this.error = "We couldn't read that image. Try a different file.";
            };
            img.onload = () => {
                this.busy = false;
                const w = img.naturalWidth;
                const h = img.naturalHeight;
                if (Math.min(w, h) < 120) return (this.error = 'That image is too small to scan. Use a clearer screenshot of your QR.');
                // Downscale big screenshots; always re-draw to PNG so the upload is a known format.
                const k = Math.min(1, MAX_SIDE / Math.max(w, h));
                const canvas = document.createElement('canvas');
                canvas.width = Math.round(w * k);
                canvas.height = Math.round(h * k);
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                this.qr = canvas.toDataURL('image/png');
                this.name = file.name || 'payment-qr.png';
                this.check = '';
                URL.revokeObjectURL(url);
                this.detect(this.qr);
            };
            img.src = url;
        },

        // Soft check where the browser supports it: warn if no QR code is visible in the image.
        async detect(src) {
            try {
                if (!window.BarcodeDetector) return;
                const formats = await window.BarcodeDetector.getSupportedFormats?.() ?? ['qr_code'];
                if (!formats.includes('qr_code')) return;
                const img = new Image();
                img.src = src;
                await img.decode();
                const codes = await new window.BarcodeDetector({ formats: ['qr_code'] }).detect(img);
                if (this.qr === src) this.check = codes.length ? 'ok' : 'none';
            } catch {
                // Detector unavailable: skip the check.
            }
        },

        remove() {
            this.qr = null;
            this.name = '';
            this.check = '';
            this.error = '';
        },

        publish() {
            this.$wire.publish(this.qr);
        },

        // After publishing, the QR just used becomes "saved from your last open play".
        reset(url) {
            this.savedUrl = url || this.savedUrl;
            this.qr = this.savedUrl ? 'saved' : null;
            this.name = this.savedUrl ? 'Saved QR' : '';
            this.check = '';
            this.error = '';
        },
    }));
});
