import * as Turbo from '@hotwired/turbo';

/** Navigate like a link click: through Turbo Drive (no full reload, progress bar). */
export function visit(url) {
    Turbo.visit(url);
}
