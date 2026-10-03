import { ref } from 'vue';
import { useApi } from './useApi.js';

const MAX_SIDE = 2400;
const QUALITY = 0.9;

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

    async function recognize(file) {
        const form = new FormData();
        form.append('photo', await downscaled(file), 'page.jpg');
        return (await api.query('/api/notebook/recognitions', form)).text;
    }

    return {
        load: (target) => api.load(url, target),
        scan: (pages) => api.post(url, { pages }),
        recognize,
    };
}

export function useNotebookPages(recognize) {
    const pages = ref([]);
    let nextId = 0;
    let queue = Promise.resolve();

    const update = (id, changes) => {
        pages.value = pages.value.map((page) => (page.id === id ? { ...page, ...changes } : page));
    };

    function addPhotos(files) {
        files.forEach((file) => {
            const id = ++nextId;
            pages.value = [...pages.value, { id, preview: URL.createObjectURL(file), text: '', reading: true, error: null }];
            queue = queue.then(async () => {
                if (!pages.value.some((page) => page.id === id)) return;
                try {
                    update(id, { text: await recognize(file), reading: false });
                } catch (exception) {
                    update(id, { reading: false, error: exception.message });
                }
            });
        });
    }

    function addTyped() {
        pages.value = [...pages.value, { id: ++nextId, preview: null, text: '', reading: false, error: null }];
    }

    function remove(id) {
        const page = pages.value.find((candidate) => candidate.id === id);
        if (page?.preview) URL.revokeObjectURL(page.preview);
        pages.value = pages.value.filter((candidate) => candidate.id !== id);
    }

    function clear() {
        pages.value.forEach((page) => page.preview && URL.revokeObjectURL(page.preview));
        pages.value = [];
    }

    const write = (id, text) => update(id, { text });

    return { pages, addPhotos, addTyped, remove, clear, write };
}
