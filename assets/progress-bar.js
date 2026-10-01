const SHOW_DELAY = 150;
const MIN_VISIBLE = 300;
const SETTLE = 80;
const CRAWL = 'transform 8s cubic-bezier(0.1, 0.7, 0.3, 1), opacity 150ms ease';
const COMPLETE = 'transform 200ms ease-out, opacity 250ms ease 200ms';
const COMPLETED_AFTER = 450;

let pending = 0;
let element = null;
let visible = false;
let shownAt = 0;
let showTimer = null;
let settleTimer = null;
let hideTimer = null;

function bar() {
    if (!element) {
        element = document.createElement('div');
        element.className = 'progress-bar';
        element.setAttribute('aria-hidden', 'true');
        document.documentElement.appendChild(element);
    }
    return element;
}

function currentScale() {
    const transform = getComputedStyle(bar()).transform;
    return transform === 'none' ? 0 : new DOMMatrix(transform).a;
}

function crawlFrom(scale) {
    const progress = bar();
    progress.style.transition = 'none';
    progress.style.transform = `scaleX(${scale})`;
    progress.getBoundingClientRect();
    progress.style.transition = CRAWL;
    progress.style.transform = 'scaleX(0.9)';
}

function show() {
    if (visible) return;
    const resuming = hideTimer !== null;
    clearTimeout(hideTimer);
    hideTimer = null;
    visible = true;
    shownAt = performance.now();
    bar().classList.add('progress-bar--visible');
    crawlFrom(resuming ? currentScale() : 0.05);
}

function complete() {
    clearTimeout(showTimer);
    showTimer = null;
    if (!visible) return;
    const wait = Math.max(0, MIN_VISIBLE - (performance.now() - shownAt));
    setTimeout(() => {
        if (pending > 0 || !visible) return;
        visible = false;
        const progress = bar();
        progress.style.transition = COMPLETE;
        progress.style.transform = 'scaleX(1)';
        progress.classList.remove('progress-bar--visible');
        hideTimer = setTimeout(() => {
            hideTimer = null;
            progress.style.transition = 'none';
            progress.style.transform = 'scaleX(0)';
        }, COMPLETED_AFTER);
    }, wait);
}

export function begin() {
    clearTimeout(settleTimer);
    pending += 1;
    if (visible || showTimer) return;
    if (hideTimer) {
        show();
        return;
    }
    showTimer = setTimeout(() => {
        showTimer = null;
        if (pending > 0) show();
    }, SHOW_DELAY);
}

export function end() {
    pending = Math.max(0, pending - 1);
    if (pending === 0) {
        clearTimeout(settleTimer);
        settleTimer = setTimeout(complete, SETTLE);
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
