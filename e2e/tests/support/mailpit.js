import { expect } from '@playwright/test';

const mailpitUrl = process.env.MAILPIT_URL ?? 'http://localhost:8025';

export async function latestEmailTo(request, email) {
    let message;
    await expect(async () => {
        const search = await request.get(`${mailpitUrl}/api/v1/search?query=${encodeURIComponent(`to:${email}`)}`);
        message = (await search.json()).messages?.[0];
        expect(message).toBeTruthy();
    }).toPass();

    return (await request.get(`${mailpitUrl}/api/v1/message/${message.ID}`)).json();
}

export async function passwordLinkFrom(message) {
    return new URL(message.HTML.match(/https?:\/\/[^"'\s<]+\/mot-de-passe\/definir\/[0-9a-f]+/)[0]).pathname;
}

export async function clearEmailsTo(request, email) {
    await request.delete(`${mailpitUrl}/api/v1/search?query=${encodeURIComponent(`to:${email}`)}`);
}
