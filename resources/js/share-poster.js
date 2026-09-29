// Share view mode (court board): draws a 1080 × 1350 PNG poster with the live-view QR, for
// Download QR image and Send to members. `info` comes from CourtBoard::render() ('poster').

const FONTS = ["800 60px 'Bricolage Grotesque'", "500 30px 'Instrument Sans'", "700 58px 'Barlow Condensed'", "600 32px 'Barlow Condensed'", "500 36px 'Barlow Condensed'"];

function draw(info) {
    const W = 1080;
    const H = 1350;
    const cv = document.createElement('canvas');
    cv.width = W;
    cv.height = H;
    const x = cv.getContext('2d');
    const rr = (x0, y0, w, h, r) => {
        x.beginPath();
        x.moveTo(x0 + r, y0);
        x.arcTo(x0 + w, y0, x0 + w, y0 + h, r);
        x.arcTo(x0 + w, y0 + h, x0, y0 + h, r);
        x.arcTo(x0, y0 + h, x0, y0, r);
        x.arcTo(x0, y0, x0 + w, y0, r);
        x.closePath();
    };
    const wrap = (text, maxW, maxLines) => {
        const lines = [];
        let cur = '';
        String(text || '').split(/\s+/).forEach((w) => {
            const t = cur ? `${cur} ${w}` : w;
            if (x.measureText(t).width > maxW && cur) {
                lines.push(cur);
                cur = w;
            } else cur = t;
        });
        if (cur) lines.push(cur);
        if (lines.length > maxLines) {
            lines.length = maxLines;
            lines[maxLines - 1] = lines[maxLines - 1].replace(/\s*\S*$/, '') + '…';
        }
        return lines;
    };
    const spacing = (v) => {
        if ('letterSpacing' in x) x.letterSpacing = v;
    };

    // Card and dark header band
    x.fillStyle = '#F3F2EA';
    x.fillRect(0, 0, W, H);
    x.fillStyle = '#FFFFFF';
    rr(60, 60, W - 120, H - 120, 48);
    x.fill();
    x.strokeStyle = '#DEDCD0';
    x.lineWidth = 3;
    x.stroke();
    x.save();
    rr(60, 60, W - 120, H - 120, 48);
    x.clip();
    x.fillStyle = '#12170F';
    x.fillRect(60, 60, W - 120, 150);
    x.restore();

    x.fillStyle = '#D3F347';
    x.beginPath();
    x.arc(136, 135, 22, 0, Math.PI * 2);
    x.fill();
    x.fillStyle = '#FFFFFF';
    x.textBaseline = 'middle';
    x.textAlign = 'left';
    x.font = "800 52px 'Bricolage Grotesque', system-ui, sans-serif";
    x.fillText('PickleX', 176, 137);
    x.textAlign = 'right';
    x.fillStyle = '#D3F347';
    x.font = "600 32px 'Barlow Condensed', 'Arial Narrow', sans-serif";
    spacing('4px');
    x.fillText('● LIVE VIEW', W - 120, 137);
    spacing('0px');

    // Title, club · venue, when
    x.textAlign = 'center';
    x.textBaseline = 'alphabetic';
    x.fillStyle = '#12170F';
    x.font = "800 60px 'Bricolage Grotesque', system-ui, sans-serif";
    let y = 300;
    wrap(info.title, W - 260, 2).forEach((l) => {
        x.fillText(l, W / 2, y);
        y += 68;
    });
    x.font = "500 30px 'Instrument Sans', system-ui, sans-serif";
    [[info.sub, '#464D42'], [info.when, '#5B6257']].forEach(([text, color]) => {
        if (!text) return;
        x.fillStyle = color;
        x.fillText(wrap(text, W - 260, 1)[0], W / 2, y + 4);
        y += 42;
    });

    // QR with a 4-module quiet zone inside a white tile
    const rows = info.rows;
    const cell = Math.floor(560 / (rows.length + 8));
    const qs = cell * (rows.length + 8);
    const qx = Math.round((W - qs) / 2);
    const qy = Math.max(y + 30, 470);
    x.fillStyle = '#FFFFFF';
    rr(qx - 16, qy - 16, qs + 32, qs + 32, 28);
    x.fill();
    x.strokeStyle = '#DEDCD0';
    x.lineWidth = 3;
    x.stroke();
    x.fillStyle = '#12170F';
    rows.forEach((row, ry) => {
        for (let cx = 0; cx < row.length; cx++) {
            if (row[cx] === '1') x.fillRect(qx + (cx + 4) * cell, qy + (ry + 4) * cell, cell, cell);
        }
    });

    // Code, link, how-to
    let fy = qy + qs + 86;
    x.font = "700 58px 'Barlow Condensed', 'Arial Narrow', sans-serif";
    spacing('8px');
    x.fillText(info.code, W / 2, fy);
    spacing('0px');
    fy += 54;
    x.fillStyle = '#464D42';
    x.font = "500 36px 'Barlow Condensed', 'Arial Narrow', sans-serif";
    x.fillText(info.shortUrl, W / 2, fy);
    fy += 64;
    x.fillStyle = '#5B6257';
    x.font = "500 28px 'Instrument Sans', system-ui, sans-serif";
    wrap("Scan to see the courts, who's up next and the queue. No login needed.", W - 200, 2).forEach((l) => {
        x.fillText(l, W / 2, fy);
        fy += 38;
    });

    return new Promise((resolve) => cv.toBlob(resolve, 'image/png'));
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('sharePoster', (info) => ({
        img: '', // object URL of the poster PNG
        file: null,
        failed: false,

        async prep() {
            if (this.img || this.failed) return;
            try {
                await Promise.all(FONTS.map((f) => document.fonts.load(f))).catch(() => {});
                const blob = await draw(info);
                if (!blob) throw new Error('no blob');
                this.file = new File([blob], info.file, { type: 'image/png' });
                this.img = URL.createObjectURL(blob);
            } catch {
                this.failed = true;
            }
        },

        get canSend() {
            return !!this.file && !!navigator.share;
        },

        saved() {
            this.$dispatch('toast', { message: `Saved ${info.file}. Send it in your members' group chat or print it for the venue.` });
        },

        send() {
            const fail = () => this.$dispatch('toast', { message: "Sharing isn't available here. Use Download QR image instead." });
            const text = `Follow ${info.title} live on PickleX. Scan the QR or open ${info.url}`;
            const data = navigator.canShare?.({ files: [this.file] })
                ? { files: [this.file], title: info.title, text }
                : { title: info.title, text, url: info.url };
            try {
                navigator.share(data).catch((err) => err?.name !== 'AbortError' && fail());
            } catch {
                fail();
            }
        },
    }));
});
