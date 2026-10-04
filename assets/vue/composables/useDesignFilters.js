import { computed } from 'vue';
import { queryText } from './useQueryState.js';
import { normalize } from './useSearch.js';

export const DESIGN_STATUSES = { open: 'open', validated: 'validated', all: 'all' };
export const DESIGN_SCOPES = { all: '', standalone: 'none' };

const isValidated = (design) => design.status === 'validated';
const words = (design, collection) => normalize([design.name, collection?.name ?? '', ...design.declinations.map((declination) => declination.gabarit.name)].join(' '));

export function useDesignFilters(board) {
    const search = queryText('q');
    const status = queryText('status', DESIGN_STATUSES.open);
    const scope = queryText('collection', DESIGN_SCOPES.all);

    const needle = computed(() => normalize(search.value.trim()));
    const searching = computed(() => needle.value !== '');
    const found = (design, collection) => !searching.value || words(design, collection).includes(needle.value);
    const hasStatus = (design) => status.value === DESIGN_STATUSES.all || (status.value === DESIGN_STATUSES.validated) === isValidated(design);

    const rank = (collection) => (collection.current ? 0 : collection.designs.some((design) => !isValidated(design)) ? 1 : 2);
    const shelves = computed(() => {
        if (!board.value) return [];
        const collections = [...board.value.collections].sort((a, b) => rank(a) - rank(b) || a.name.localeCompare(b.name));
        return [
            ...collections.map((collection) => ({ key: collection.id, collection, designs: collection.designs })),
            { key: DESIGN_SCOPES.standalone, collection: null, designs: board.value.standalone },
        ].map((shelf) => {
            const matching = shelf.designs.filter((design) => found(design, shelf.collection));
            return { ...shelf, matching, open: shelf.designs.filter((design) => !isValidated(design)).length };
        });
    });

    const activeScope = computed(() => (shelves.value.some((shelf) => shelf.key === scope.value) ? scope.value : DESIGN_SCOPES.all));
    const index = computed(() => shelves.value.filter((shelf) => !searching.value || shelf.matching.length > 0 || shelf.key === activeScope.value));
    const inScope = computed(() => shelves.value.filter((shelf) => activeScope.value === DESIGN_SCOPES.all || shelf.key === activeScope.value));
    const counts = computed(() => {
        const designs = inScope.value.flatMap((shelf) => shelf.matching);
        return {
            [DESIGN_STATUSES.open]: designs.filter((design) => !isValidated(design)).length,
            [DESIGN_STATUSES.validated]: designs.filter(isValidated).length,
            [DESIGN_STATUSES.all]: designs.length,
        };
    });
    const groups = computed(() => inScope.value
        .map((shelf) => ({ ...shelf, shown: shelf.matching.filter(hasStatus) }))
        .filter((shelf) => activeScope.value !== DESIGN_SCOPES.all || shelf.shown.length > 0));
    const selected = computed(() => (activeScope.value === DESIGN_SCOPES.all ? null : groups.value[0] ?? null));

    return { search, status, scope, activeScope, searching, index, counts, groups, selected };
}
