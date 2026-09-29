// Avatar borders and queue backgrounds (.px-fr, .px-qb). Their stylesheet is over 1 MB, so it loads
// only once a page shows a look. Each distinct animated SVG look costs main-thread time every frame
// (copies of the same look are nearly free), so .px-still (the static version) goes on every look off
// screen, and on SVG looks beyond the first CAP distinct ones in view.
// Inside [data-looks-all] (the look picker) every look in view animates.
const LOOK = '.px-fr, .px-qb';
const SVG_LOOK = /\bpx-(?:fr|qb)-(?:dev\b|[a-z]{2}-[a-z]+)/;
// ponytail: fixed cap in DOM order; make it device-aware (e.g. navigator.hardwareConcurrency) if phones still lag.
const CAP = 4;
const visible = new Set();
const moving = new WeakSet();
let loaded = false;
let queued = false;

const apply = () => {
    queued = false;
    const allowed = new Set();
    [...visible]
        .sort((a, b) => (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1))
        .forEach((el) => {
            const look = el.className.match(SVG_LOOK)?.[0];
            const on = ! look || el.closest('[data-looks-all]') || allowed.has(look) || (allowed.size < CAP && allowed.add(look));
            on ? moving.add(el) : moving.delete(el);
            el.classList.toggle('px-still', ! on);
        });
};

const schedule = () => queued || (queued = true, requestAnimationFrame(apply));

const io = new IntersectionObserver((entries) => {
    for (const { target, isIntersecting } of entries) {
        if (isIntersecting) {
            visible.add(target);
        } else {
            visible.delete(target);
            moving.delete(target);
            target.classList.add('px-still');
        }
    }
    schedule();
}, { rootMargin: '120px' });

const observe = (el) => {
    if (! loaded) {
        loaded = true;
        import('../css/looks.css');
    }
    io.observe(el);
};

const watch = (root) => {
    root.matches?.(LOOK) && observe(root);
    root.querySelectorAll?.(LOOK).forEach(observe);
};

const forget = (el) => {
    io.unobserve(el);
    visible.delete(el);
};

const hooks = () => {
    // Morphs reset the class attribute to the server's (no .px-still), and may swap which look an element wears.
    Livewire.hook('morph.updated', ({ el }) => {
        if (! el.matches?.(LOOK)) return;
        observe(el);
        moving.has(el) || el.classList.add('px-still');
        schedule();
    });
    Livewire.hook('morph.added', ({ el }) => watch(el));
    Livewire.hook('morph.removed', ({ el }) => el.nodeType === 1 && [el, ...el.querySelectorAll(LOOK)].forEach(forget));
};

window.Livewire ? hooks() : document.addEventListener('livewire:init', hooks);

document.addEventListener('livewire:navigated', () => {
    io.disconnect();
    visible.clear();
    watch(document.body);
});
watch(document.body);
