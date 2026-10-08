# Accounts & workspaces (Comptes et espaces de travail)

A **workspace** is one business: its catalogue, events, orders, discounts and settings. A **user** is a person who
signs in; each user belongs to exactly **one** workspace, and several users can share the same workspace.

| Entity | Fields |
|---|---|
| `Workspace` | `name` (unique) |
| `User` | `email` (unique, lowercase), `accountId` (the mossyleaf account's OIDC `sub`, unique, empty until the first sign-in for users from before mossyleaf accounts), `workspace`, `language` (`fr`/`en`, empty until chosen), `themeBackground` + `themeAccent` (hex colours, empty until chosen) |

Model: `src/Domain/Identity/`. Use cases: `src/Application/Identity/`. Sign-in: `config/packages/security.yaml`,
`src/Infrastructure/Security/`, `src/Presentation/Web/Security/`.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| A1 | People sign in with their **mossyleaf account** (`accounts.mossyleaf.studio`, shared by every mossyleaf app). There is no sign-up, password or invitation in MossyTrunk: accounts are invited, reset their password and manage passkeys in mossyleaf accounts, and only members of its `mossytrunk` group may open MossyTrunk (others see "Your mossyleaf account does not have access to MossyTrunk yet.") | `OidcSingleSignOn`, `AccountsAuthenticator` | `AuthenticationTest`, e2e `auth.spec.js` |
| A2 | Email is valid, trimmed, lowercased and unique | `User::normalizeEmail()`, `SignInHandler` | `UserTest`, `AccountUseCasesTest` |
| A3 | On sign-in the account (OIDC `sub`) is matched to its user; a user from before mossyleaf accounts (no account yet) is linked once by the **same email** and keeps their workspace and all its data; otherwise the first sign-in creates the user in the workspace their invitation names (claim `mossytrunk_workspace`, user attribute set by `make invite … WORKSPACE=…` in mossyleaf accounts), opened (main channel, reference formats) when no workspace has that exact name yet; invited without one, they get a new workspace of their own named after them (claim `name`, else their email; « (2) », « (3) »… when the name is taken). A later email change in the account follows on the next sign-in. An email already used by another linked user is refused, and so is an account without email | `SignInHandler`, `WorkspaceOpening` | `AccountUseCasesTest`, `AuthenticationTest` |
| A4 | Signing out of MossyTrunk also ends the mossyleaf accounts session: with the sign-in's id token (kept in the session) mossyleaf accounts sends the browser back to MossyTrunk; a session restored by the remember-me cookie has none, so mossyleaf accounts ends its session and shows its own page. A sign-in link (`state` + PKCE verifier) works once, for the browser that started it | `SignOutOfAccounts`, `AccountsSession`, `SignInAttempts` | `AuthenticationTest`, e2e `auth.spec.js` |
| A8 | Sign-in keeps the session 30 days (remember-me cookie); changing the account's email signs out the other sessions | `security.yaml` (`remember_me`) | `AuthenticationTest` |
| A10 | All business data is scoped to the user's workspace: lists only show it, an id from another workspace answers « introuvable » (404) | `WorkspaceContext` used by every Doctrine repository; aggregates are created with the current workspace (orders take their event's) | `WorkspaceIsolationTest`, `ProductApiTest` |
| A11 | Workspace secrets (access keys and tokens of the connected services) are stored **encrypted** (libsodium secretbox, key `APP_ENCRYPTION_KEY`), one per name and workspace; the API only returns whether a key is set and its last 4 characters; saving an empty key keeps the current one | `WorkspaceSecret`, `SecretName`, `WorkspaceSecrets`, `SodiumSecretCipher`, `ConnectionCredentials` | `SodiumSecretCipherTest`, `SecretNameTest`, `ServiceConnectionUseCasesTest`, `ServicesApiTest` |
| A12 | The app is in **French or English**. Language = the signed-in user's chosen language, else the `locale` cookie (choice made while signed out), else the browser's preferred language (`Accept-Language`) among fr/en, else English. The « Langue / Language » selector (sidebar, sign-in pages) saves the choice (cookie + user account, `PUT /api/me/language` `{language}`). Dates and amounts are formatted for the language (fr-FR / en-GB), amounts stay in euros | `LocaleListener`, `User::speak()`, `ChooseLanguageHandler`, `assets/vue/i18n/` | `LanguageTest`, e2e `language.spec.js` |
| A13 | Each user may choose the app's **background** and **accent** colours (Paramètres › Apparence): one of five themes (Mousse — the default —, Terre cuite, Fjord, Prune, Nuit) or any `#rrggbb` pair, saved on their account (`PUT /api/me/theme` `{background, accent}`) and applied to every page they open. Every other colour derives from these two (`assets/styles/tokens.css`): text, surfaces and borders from the background (dark text on a light background, light text on a dark one), the information colours (danger red, success green, warning amber, chart blue) from the background's lightness blended with the accent, and the text on accent buttons from the accent | `Theme`, `User::wear()`, `ChooseThemeHandler`, `base.html.twig`, `AppearanceSettings.vue`, `useTheme.js` | `ThemeTest` (unit + functional), e2e `theme.spec.js` |
| A9 | Every page requires signing in; the API answers 401 to anonymous calls and 403 to cross-site writes | `security.yaml` `access_control`, `AuthenticationEntryPoint`, `SameOriginGuard` | `AuthenticationTest` |

## Pages

| Page | URL |
|---|---|
| Connexion | `/login` → mossyleaf accounts → `/login/check`; `/login` shows the reason and a « Se connecter avec mossyleaf » button when a sign-in failed |
| Déconnexion (POST) | `/logout` |
| Paramètres | `/settings` — workspace name from the session, « Gérer mon compte mossyleaf » link; services connectés: `/api/services` (see [imports.md](imports.md)) |
