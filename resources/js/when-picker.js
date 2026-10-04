// Date + start/end time pickers for Create open play. Reads and writes the Livewire
// properties date (Y-m-d), starts and ends (H:i); the server re-validates on publish.

const DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

const pad = (n) => String(n).padStart(2, '0');
const parts = (iso) => iso.split('-').map(Number);
const toIso = (t) => {
    const d = new Date(t);
    return `${d.getUTCFullYear()}-${pad(d.getUTCMonth() + 1)}-${pad(d.getUTCDate())}`;
};
const addDays = (iso, n) => {
    const [y, m, d] = parts(iso);
    return toIso(Date.UTC(y, m - 1, d + n));
};
const addMonths = (iso, n) => {
    const [y, m, d] = parts(iso);
    const last = new Date(Date.UTC(y, m - 1 + n + 1, 0)).getUTCDate();
    return toIso(Date.UTC(y, m - 1 + n, Math.min(d, last)));
};
const dow = (iso) => {
    const [y, m, d] = parts(iso);
    return new Date(Date.UTC(y, m - 1, d)).getUTCDay();
};
const toMin = (t) => {
    const [h, m] = (t || '0:0').split(':').map(Number);
    return ((h || 0) * 60 + (m || 0)) % 1440;
};
const toTime = (n) => {
    n = ((n % 1440) + 1440) % 1440;
    return `${pad(Math.floor(n / 60))}:${pad(n % 60)}`;
};
const fmtTime = (t) => {
    const n = toMin(t);
    const h = Math.floor(n / 60);
    return `${((h + 11) % 12) + 1}:${pad(n % 60)} ${h >= 12 ? 'PM' : 'AM'}`;
};
const duration = (s, e) => (toMin(e) - toMin(s) + 1440) % 1440;
const durLabel = (n) => {
    const h = Math.floor(n / 60);
    const m = n % 60;
    return [h ? `${h} ${h === 1 ? 'hr' : 'hrs'}` : '', m ? `${m} min` : ''].filter(Boolean).join(' ') || '0 min';
};

// Accepts 6:10 PM, 6:10pm, 6.10 pm, 610p, 6pm, 18:10 or 1810. Without AM/PM, 1-12 keeps the current half of the day.
export const parseTime = (str, currentPm) => {
    const s = String(str || '').trim().toLowerCase().replace(/\s+/g, '').replace(/\./g, ':').replace(/m$/, '');
    const m = s.match(/^(\d{1,2}):?(\d{2})?([ap])?$/);
    if (!m) return null;
    let h = Number(m[1]);
    const min = m[2] ? Number(m[2]) : 0;
    if (min > 59) return null;
    if (m[3]) {
        if (h < 1 || h > 12) return null;
        h = (h % 12) + (m[3] === 'p' ? 12 : 0);
    } else if (h >= 1 && h <= 12) {
        h = (h % 12) + (currentPm ? 12 : 0);
    } else if (h > 23) {
        return null;
    }
    return toTime(h * 60 + min);
};

document.addEventListener('alpine:init', () => {
    window.Alpine.data('whenPicker', (today) => ({
        today,
        max: addDays(today, 365),
        pick: null, // 'date' | 'start' | 'end'
        calM: null, // 'YYYY-MM' shown in the calendar
        typed: null,
        typedErr: '',

        init() {
            this.calM = (this.date || this.today).slice(0, 7);
        },

        get date() { return this.$wire.date; },
        get starts() { return this.$wire.starts; },
        get ends() { return this.$wire.ends; },

        toggle(which) {
            this.pick = this.pick === which ? null : which;
            this.calM = this.date.slice(0, 7);
            this.typed = null;
            this.typedErr = '';
            // On a phone the picker opens below the fold: scroll the form (only the form) so it shows, keeping the Date / Starts / Ends row in sight.
            if (this.pick) {
                this.$nextTick(() => {
                    const el = document.getElementById(which === 'date' ? 'c-cal' : 'c-time');
                    const box = el?.closest('.overflow-y-auto');
                    if (box && el.getBoundingClientRect().bottom > box.getBoundingClientRect().bottom) {
                        box.scrollBy({ top: el.getBoundingClientRect().top - box.getBoundingClientRect().top - 112, behavior: 'smooth' });
                    }
                });
            }
        },

        // ------------------------------------------------------------ labels
        dateLabel(iso, long = false) {
            const [y, m, d] = parts(iso);
            const name = DAY_NAMES[dow(iso)];
            const year = y !== parts(this.today)[0] ? `, ${y}` : '';
            return `${long ? name : name.slice(0, 3)}, ${MONTHS[m - 1].slice(0, 3)} ${d}${year}`;
        },
        fmtTime,
        get overnight() { return duration(this.starts, this.ends) > 0 && toMin(this.ends) <= toMin(this.starts); },
        get problem() {
            const dur = duration(this.starts, this.ends);
            if (!dur) return 'Ends when it starts. Pick a later end time.';
            if (dur > 720) return 'That is longer than 12 hours. Check the end time.';
            if (this.date < this.today) return 'That date has passed. Pick today or later.';
            return '';
        },
        get summary() {
            return `${this.dateLabel(this.date, true)} · ${fmtTime(this.starts)} - ${fmtTime(this.ends)}${this.overnight ? ' next day' : ''} · ${durLabel(duration(this.starts, this.ends))}`;
        },

        // ------------------------------------------------------------ calendar
        get monthLabel() {
            const [y, m] = parts(this.calM + '-01');
            return `${MONTHS[m - 1]} ${y}`;
        },
        get canPrev() { return this.calM > this.today.slice(0, 7); },
        get canNext() { return this.calM < this.max.slice(0, 7); },
        month(n) {
            const next = addMonths(this.calM + '-01', n).slice(0, 7);
            if (next >= this.today.slice(0, 7) && next <= this.max.slice(0, 7)) this.calM = next;
        },
        get weeks() {
            const first = this.calM + '-01';
            const start = addDays(first, -dow(first));
            const focus = this.date.slice(0, 7) === this.calM ? this.date : this.today.slice(0, 7) === this.calM ? this.today : first;
            return Array.from({ length: 6 }, (_, w) => Array.from({ length: 7 }, (_, i) => {
                const iso = addDays(start, w * 7 + i);
                return {
                    iso,
                    n: parts(iso)[2],
                    out: iso.slice(0, 7) !== this.calM,
                    off: iso < this.today || iso > this.max,
                    sel: iso === this.date,
                    isToday: iso === this.today,
                    focus: iso === focus,
                    label: this.dateLabel(iso, true) + (iso === this.today ? ', today' : ''),
                };
            }));
        },
        get quick() {
            const next = (d) => addDays(this.today, (d - dow(this.today) + 7) % 7);
            const picks = [['Today', this.today], ['Tomorrow', addDays(this.today, 1)], ['This Sat', next(6)], ['This Sun', next(0)]];
            return picks.filter((p, i) => picks.findIndex((q) => q[1] === p[1]) === i)
                .map(([label, iso]) => ({ label, iso, sub: this.dateLabel(iso).split(', ')[1] }));
        },
        setDate(iso, keepOpen = false) {
            if (iso < this.today || iso > this.max) return;
            this.$wire.date = iso;
            this.calM = iso.slice(0, 7);
            if (!keepOpen) this.pick = null;
        },
        calKey(e) {
            const d = this.date;
            const moves = {
                ArrowLeft: addDays(d, -1), ArrowRight: addDays(d, 1), ArrowUp: addDays(d, -7), ArrowDown: addDays(d, 7),
                PageUp: addMonths(d, -1), PageDown: addMonths(d, 1), Home: addDays(d, -dow(d)), End: addDays(d, 6 - dow(d)),
            };
            let n = moves[e.key];
            if (!n) return;
            e.preventDefault();
            n = n < this.today ? this.today : n > this.max ? this.max : n;
            this.setDate(n, true);
            this.$nextTick(() => this.$refs.cal?.querySelector(`[data-iso="${n}"]`)?.focus());
        },

        // ------------------------------------------------------------ time
        get cur() { return this.pick === 'end' ? this.ends : this.starts; },
        get curMin() { return toMin(this.cur); },
        get h12() { return ((Math.floor(this.curMin / 60) + 11) % 12) + 1; },
        get min() { return this.curMin % 60; },
        get pm() { return this.curMin >= 720; },
        get duration() { return duration(this.starts, this.ends); },
        at(h12, min, pm) { return toTime(((h12 % 12) + (pm ? 12 : 0)) * 60 + min); },

        // Moving the start keeps the session length.
        setTime(which, t) {
            if (which === 'start') {
                const dur = duration(this.starts, this.ends) || 180;
                this.$wire.starts = t;
                this.$wire.ends = toTime(toMin(t) + dur);
            } else {
                this.$wire.ends = t;
            }
            this.typed = null;
            this.typedErr = '';
        },
        nudge(n) { this.setTime(this.pick, toTime(this.curMin + n)); },
        commit() {
            if (this.typed == null) return true;
            const t = parseTime(this.typed, this.pm);
            if (!t) {
                this.typedErr = "That doesn't look like a time. Try 6:10 PM or 18:10.";
                return false;
            }
            this.setTime(this.pick, t);
            return true;
        },
        done() {
            if (this.pick !== 'date' && !this.commit()) return;
            this.pick = null;
        },
        get startPresets() { return [['6:00 AM', '06:00'], ['8:00 AM', '08:00'], ['4:00 PM', '16:00'], ['6:00 PM', '18:00'], ['7:00 PM', '19:00']]; },
        get lengthPresets() { return [[90, '1.5 hrs'], [120, '2 hrs'], [180, '3 hrs'], [240, '4 hrs']]; },
        lengthEnd(n) { return toTime(toMin(this.starts) + n); },
    }));
});
