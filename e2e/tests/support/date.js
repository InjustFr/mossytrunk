import { expect } from '@playwright/test';

function segmentsOf(iso) {
    const [date, time] = iso.split('T');
    const [year, month, day] = date.split('-');
    return time ? [day, month, year, ...time.split(':')] : [day, month, year];
}

const YEAR_FIRST = [2, 1, 0, 3, 4];

async function typeSegments(group, values, offset = 0) {
    const segments = group.getByRole('spinbutton');
    for (const index of YEAR_FIRST.slice(0, values.length)) {
        const value = values[index];
        const segment = segments.nth(offset + index);
        await segment.click();
        await segment.pressSequentially(value);
        await expect(segment).toHaveText(value);
    }
}

export async function fillDate(group, iso) {
    await typeSegments(group, segmentsOf(iso));
}

export async function fillDateRange(group, start, end) {
    await typeSegments(group, segmentsOf(start));
    await typeSegments(group, segmentsOf(end), 3);
}
