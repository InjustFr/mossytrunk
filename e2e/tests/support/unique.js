/** The e2e database is shared across tests: suffix names to keep tests independent. */
export const unique = (label) => `${label} ${Date.now().toString(36)}${Math.floor(Math.random() * 1000)}`;

/**
 * A random future day (YYYY-MM-DD), far from other tests' events since events may not overlap.
 * Every call uses a distinct 10-day slot.
 */
let slot = 0;
export function uniqueDay(offsetDays = 0) {
    if (offsetDays === 0) {
        slot = Math.floor(Math.random() * 2_000) * 10;
    }
    const day = new Date(Date.UTC(2040, 0, 1) + (slot + offsetDays) * 86_400_000);
    return day.toISOString().slice(0, 10);
}
