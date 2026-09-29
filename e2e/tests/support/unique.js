/** The e2e database is shared across tests: suffix names to keep tests independent. */
export const unique = (label) => `${label} ${Date.now().toString(36)}${Math.floor(Math.random() * 1000)}`;

const base = Math.floor(Math.random() * 50) * 5_000;
let next = 0;
let slot = 0;
export function uniqueDay(offsetDays = 0) {
    if (offsetDays === 0) {
        next += 1;
        slot = base + next * 10;
    }
    const day = new Date(Date.UTC(2040, 0, 1) + (slot + offsetDays) * 86_400_000);
    return day.toISOString().slice(0, 10);
}
