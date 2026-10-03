import { useApi } from './useApi.js';

const MAX_SIDE = 2000;
const QUALITY = 0.85;

async function downscaled(file) {
    const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
    const scale = Math.min(1, MAX_SIDE / Math.max(bitmap.width, bitmap.height));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(bitmap.width * scale);
    canvas.height = Math.round(bitmap.height * scale);
    canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
    bitmap.close();
    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', QUALITY));
    return blob ?? file;
}

export function useNotebook(eventId) {
    const api = useApi();
    const url = `/api/events/${eventId}/notebook`;

    async function scan(files) {
        const form = new FormData();
        const pages = await Promise.all(files.map(downscaled));
        pages.forEach((page, index) => form.append('pages[]', page, `page-${index + 1}.jpg`));
        return api.post(url, form);
    }

    return {
        load: (target) => api.load(url, target),
        scan,
    };
}
