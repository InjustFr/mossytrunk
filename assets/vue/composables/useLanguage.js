import { useApi } from './useApi.js';
import { visit } from './useNavigation.js';
import { useSession } from './useSession.js';

const ONE_YEAR = 60 * 60 * 24 * 365;

export function useLanguage() {
    const api = useApi();
    const session = useSession();

    async function choose(language) {
        document.cookie = `locale=${language}; path=/; max-age=${ONE_YEAR}; SameSite=Lax`;
        if (session) {
            await api.put('/api/me/language', { language });
        }
        visit(`${window.location.pathname}${window.location.search}`);
    }

    return { choose };
}
