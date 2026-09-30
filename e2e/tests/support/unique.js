/** The e2e database is shared across tests: suffix names to keep tests independent. */
export const unique = (label) => `${label} ${Date.now().toString(36)}${Math.floor(Math.random() * 1000)}`;

const base = (Math.floor(Math.random() * 10) * 10 + Number(process.env.TEST_PARALLEL_INDEX ?? 0)) * 5_000;
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

export const uniqueTypeCode = () => `T${process.env.TEST_PARALLEL_INDEX ?? 0}${Date.now().toString(36).slice(-4)}${Math.floor(Math.random() * 1_296).toString(36).padStart(2, '0')}`.toUpperCase();
