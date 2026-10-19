// Profile picture dialog (Account settings). Presets, a cropped photo upload, or initials.
// The photo is cropped to a 256px JPEG here and sent to Settings::usePhoto(); nothing else leaves the browser.

const CROP = 240; // crop window, px
const OUT = 256; // saved image size, px
const TYPES = /^image\/(png|jpe?g|webp|gif)$/i;

document.addEventListener('alpine:init', () => {
    window.Alpine.data('avatarPicker', (config) => ({
        open: false,
        tab: 'presets',
        current: config.current, // { avatar, url, color }
        sel: null,
        cat: 'pb', // avatar group in view (category bar)
        jumpUntil: 0,
        color: config.current.color,
        crop: null, // { src, w, h, zoom, x, y, name } x/y run -1..1 (0 = centred)
        img: null,
        drag: null,
        over: false,
        busy: false,
        saving: false,
        error: '',

        show(tab) {
            const [kind, value] = (this.current.avatar || '').split(':');
            this.sel = kind === 'preset' ? value : null;
            this.cat = (this.sel && config.groupOf[this.sel]) || 'pb';
            this.color = this.current.color;
            this.tab = tab || (kind === 'photo' ? 'upload' : 'presets');
            this.error = '';
            this.crop = null;
            this.open = true;
            // Open scrolled to the group of your current avatar.
            this.$nextTick(() => this.cat !== 'pb' && this.jump(this.cat, false));
        },

        // ------------------------------------------------------------ avatar groups
        jump(cat, smooth = true) {
            this.cat = cat;
            this.jumpUntil = Date.now() + 700;
            const grid = this.$refs.grid;
            const sec = grid?.querySelector(`#av-sec-${cat}`);
            if (sec) grid.scrollTo({ top: Math.max(0, sec.offsetTop - 2), behavior: smooth ? 'smooth' : 'auto' });
        },

        scrolled() {
            if (Date.now() < this.jumpUntil) return;
            const grid = this.$refs.grid;
            const secs = [...grid.querySelectorAll('section[data-group]')];
            if (!secs.length) return;
            let cur = secs[0];
            for (const s of secs) if (s.offsetTop - grid.scrollTop <= 48) cur = s;
            if (grid.scrollTop + grid.clientHeight >= grid.scrollHeight - 2) cur = secs[secs.length - 1];
            this.cat = cur.dataset.group;
        },

        close() {
            this.open = false;
            this.drag = null;
        },

        // ------------------------------------------------------------ upload
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
            this.tab = 'upload';
            this.open = true;
            this.error = '';
            if (!TYPES.test(file.type || '')) return (this.error = "That file isn't a supported image. Use a JPG, PNG, WebP or GIF.");
            if (file.size > 5 * 1024 * 1024) return (this.error = 'That photo is over 5 MB. Choose a smaller one.');

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
                if (Math.min(w, h) < 128) return (this.error = 'That photo is too small. Use one at least 128 × 128 pixels.');
                this.img = img;
                this.crop = { src: url, w, h, zoom: 1, x: 0, y: 0, name: file.name || 'Photo' };
            };
            img.src = url;
        },

        geo(c = this.crop) {
            const base = CROP / Math.max(1, Math.min(c.w, c.h));
            const dw = c.w * base * c.zoom;
            const dh = c.h * base * c.zoom;
            const ox = (dw - CROP) / 2;
            const oy = (dh - CROP) / 2;
            return { base, dw, dh, ox, oy, left: -ox * (1 - c.x), top: -oy * (1 - c.y) };
        },

        // Image position inside a circle of `size` px (crop window, previews).
        imgStyle(size) {
            if (!this.crop) return '';
            const g = this.geo();
            const k = size / CROP;
            return `left:${g.left * k}px;top:${g.top * k}px;width:${g.dw * k}px;height:${g.dh * k}px`;
        },

        set(changes) {
            const c = { ...this.crop, ...changes };
            c.zoom = Math.min(3, Math.max(1, c.zoom));
            c.x = Math.min(1, Math.max(-1, c.x));
            c.y = Math.min(1, Math.max(-1, c.y));
            const g = this.geo(c);
            if (g.ox <= 0.5) c.x = 0;
            if (g.oy <= 0.5) c.y = 0;
            this.crop = c;
        },

        down(e) {
            this.drag = { px: e.clientX, py: e.clientY, x: this.crop.x, y: this.crop.y };
            e.currentTarget.setPointerCapture?.(e.pointerId);
        },

        move(e) {
            if (!this.drag) return;
            const g = this.geo();
            this.set({
                x: g.ox > 0 ? this.drag.x + (e.clientX - this.drag.px) / g.ox : 0,
                y: g.oy > 0 ? this.drag.y + (e.clientY - this.drag.py) / g.oy : 0,
            });
        },

        key(e) {
            const g = this.geo();
            const step = (o) => (o > 0 ? Math.min(1, 16 / o) : 0);
            const c = this.crop;
            const moves = {
                ArrowLeft: { x: c.x - step(g.ox) },
                ArrowRight: { x: c.x + step(g.ox) },
                ArrowUp: { y: c.y - step(g.oy) },
                ArrowDown: { y: c.y + step(g.oy) },
                '+': { zoom: c.zoom + 0.1 },
                '=': { zoom: c.zoom + 0.1 },
                '-': { zoom: c.zoom - 0.1 },
                _: { zoom: c.zoom - 0.1 },
            };
            if (!moves[e.key]) return;
            e.preventDefault();
            this.set(moves[e.key]);
        },

        render() {
            const g = this.geo();
            const scale = g.base * this.crop.zoom;
            const canvas = document.createElement('canvas');
            canvas.width = canvas.height = OUT;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, OUT, OUT);
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(this.img, -g.left / scale, -g.top / scale, CROP / scale, CROP / scale, 0, 0, OUT, OUT);
            return canvas.toDataURL('image/jpeg', 0.88);
        },

        // ------------------------------------------------------------ preview + save
        get preview() {
            const presets = config.presets;
            if (this.tab === 'upload' && this.crop) return { photo: true, label: 'New photo, not saved yet' };
            if (this.tab === 'presets' && this.sel) return { url: presets[this.sel].url, color: this.current.color, label: 'PickleX avatar, ' + presets[this.sel].label };
            if (this.tab === 'initials') return { url: null, color: this.color, label: 'Initials on ' + config.colors[this.color].toLowerCase() };
            return { url: this.current.url, color: this.current.color, label: this.currentLabel };
        },

        get currentLabel() {
            const [kind, value] = (this.current.avatar || '').split(':');
            if (kind === 'preset') return 'PickleX avatar, ' + config.presets[value].label;
            return kind === 'photo' ? 'Your photo' : 'Initials';
        },

        get dirty() {
            if (this.tab === 'presets') return !!this.sel && this.current.avatar !== 'preset:' + this.sel;
            if (this.tab === 'upload') return !!this.crop;
            return !!this.current.avatar || this.color !== this.current.color;
        },

        get saveLabel() {
            return { presets: 'Use this avatar', upload: 'Use this photo', initials: 'Use my initials' }[this.tab];
        },

        async remove() {
            await this.$wire.removePicture();
            this.current = { avatar: null, url: null, color: this.current.color };
        },

        async save() {
            if (!this.dirty || this.saving) return;
            this.saving = true;
            this.error = '';
            try {
                const result =
                    this.tab === 'presets' ? await this.$wire.usePreset(this.sel)
                    : this.tab === 'initials' ? await this.$wire.useInitials(this.color)
                    : await this.$wire.usePhoto(this.render());
                if (!result.ok) return (this.error = result.error);
                this.current = { avatar: result.avatar, url: result.url, color: result.color };
                this.close();
            } catch {
                this.error = "We couldn't save that. Check your connection and try again.";
            } finally {
                this.saving = false;
            }
        },
    }));
});
