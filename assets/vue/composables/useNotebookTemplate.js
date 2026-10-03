import { useApi } from './useApi.js';

export const SEPARATIONS = ['numbered', 'line', 'gap'];

export function useNotebookTemplate() {
    const api = useApi();

    return {
        load: (target) => api.load('/api/notebook-template', target),
        save: (template) => api.put('/api/notebook-template', template),
    };
}
