/**
 * Top loading bar (YouTube/Nuxt style) covering a whole page change: the Turbo Drive visit AND the
 * API requests the new page makes once mounted. Any code can hold it with begin()/end() (useApi does).
 * The bar lives on <html>, outside the <body> Turbo swaps, and stays visible at least MIN_VISIBLE ms
 * so fast navigations still give feedback.
 */
const MIN_VISIBLE = 300;
const SETTLE = 80; // gap tolerated between the visit end and the page's first request
let pending = 0;
let element = null;
let progress = 0;
let shownAt = 0;
let trickle = null;
let settleTimer = null;

function bar() {
    if (!element) {
        element = document.createElement('div');
        element.className = 'progress-bar';
        element.setAttribute('aria-hidden', 'true');
        document.documentElement.appendChild(element);
    }
    return element;
}

function render() {
    bar().style.transform = `scaleX(${progress})`;
}

function show() {
    if (trickle) return;
    progress = 0.08;
    shownAt = performance.now();
    bar().classList.add('progress-bar--visible');
    render();
    trickle = setInterval(() => {
        progress += (0.9 - progress) * 0.12;
        render();
    }, 200);
}

function finish() {
    if (!trickle) return;
    const wait = Math.max(0, MIN_VISIBLE - (performance.now() - shownAt));
    setTimeout(() => {
        if (pending > 0) return;
        clearInterval(trickle);
        trickle = null;
        progress = 1;
        render();
        setTimeout(() => {
            if (trickle) return;
            bar().classList.remove('progress-bar--visible');
        }, 200);
    }, wait);
}

export function begin() {
    clearTimeout(settleTimer);
    pending += 1;
    show();
}

export function end() {
    pending = Math.max(0, pending - 1);
    if (pending === 0) {
        clearTimeout(settleTimer);
        settleTimer = setTimeout(finish, SETTLE);
    }
}

let visiting = false;
document.addEventListener('turbo:visit', () => {
    if (!visiting) {
        visiting = true;
        begin();
    }
});
document.addEventListener('turbo:load', () => {
    if (visiting) {
        visiting = false;
        end();
    }
});
