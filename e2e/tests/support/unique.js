/** The e2e database is shared across tests: suffix names to keep tests independent. */
export const unique = (label) => `${label} ${Date.now().toString(36)}${Math.floor(Math.random() * 1000)}`;
