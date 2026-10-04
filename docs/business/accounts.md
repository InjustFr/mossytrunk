# Accounts & workspaces (Comptes et espaces de travail)

A **workspace** is one business: its catalogue, events, orders, discounts and settings. A **user** is a person who
signs in; each user belongs to exactly **one** workspace, and several users can share the same workspace.

| Entity | Fields |
|---|---|
| `Workspace` | `name` (unique) |
| `User` | `email` (login, unique, lowercase), `passwordHash` (empty until chosen), `workspace`, `language` (`fr`/`en`, empty until chosen), `themeBackground` + `themeAccent` (hex colours, empty until chosen) |
| `PasswordToken` | `selector`, SHA-256 of the `verifier`, `purpose` (invitation / reset), `expiresAt`, `usedAt` |

Model: `src/Domain/Identity/`. Use cases: `src/Application/Identity/`. Sign-in: `config/packages/security.yaml`,
`src/Infrastructure/Security/`, `src/Presentation/Web/Security/`.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| A1 | No self-registration: users are created from the command line (`app:user:create <email> --workspace=<name>`); the workspace is created when it does not exist yet | `CreateUserHandler`, `CreateUserCommand` | `AccountUseCasesTest` |
| A2 | Email is valid, trimmed, lowercased and unique | `User::normalizeEmail()`, `CreateUserHandler` | `UserTest`, `AccountUseCasesTest` |
| A3 | A new user receives an **invitation** email to choose their password; they cannot sign in before | `CreateUserHandler`, `SymfonyAccountMailer` | `AccountUseCasesTest`, `AuthenticationTest`, e2e `auth.setup.js` |
| A4 | Password links are single-use; an invitation is valid **7 days**, a reset link **1 hour**; issuing a link revokes the previous ones | `PasswordToken`, `PasswordTokenPurpose`, `PasswordTokenIssuer` | `PasswordTokenTest`, `AccountUseCasesTest` |
| A5 | Only a hash of the link's secret is stored; the link token is moved from the URL to the session before the form is shown | `PasswordToken::issue()`, `PasswordLinkController` | `PasswordTokenTest`, `AuthenticationTest` |
| A6 | Passwords have at least **12 characters** and must be typed twice | `SetPasswordHandler::MIN_LENGTH`, `SetPasswordController` | `AccountUseCasesTest` |
| A7 | "Mot de passe oublié ?" always shows the same confirmation, whether the account exists or not; at most 3 requests per email and IP every 15 minutes | `RequestPasswordResetHandler`, `forgot_password` rate limiter | `AccountUseCasesTest`, `AuthenticationTest` |
| A8 | Sign-in: 5 failed attempts per 15 minutes are throttled; "Se souvenir de moi" keeps the session 30 days; a password change signs out the other sessions | `security.yaml` (`login_throttling`, `remember_me`), `SecurityUser::__serialize()` | e2e `auth.spec.js` |
| A10 | All business data is scoped to the user's workspace: lists only show it, an id from another workspace answers « introuvable » (404) | `WorkspaceContext` used by every Doctrine repository; aggregates are created with the current workspace (orders take their event's) | `WorkspaceIsolationTest`, `ProductApiTest` |
| A11 | Workspace secrets (access keys and tokens of the connected services) are stored **encrypted** (libsodium secretbox, key `APP_ENCRYPTION_KEY`), one per name and workspace; the API only returns whether a key is set and its last 4 characters; saving an empty key keeps the current one | `WorkspaceSecret`, `SecretName`, `WorkspaceSecrets`, `SodiumSecretCipher`, `ConnectionCredentials` | `SodiumSecretCipherTest`, `SecretNameTest`, `ServiceConnectionUseCasesTest`, `ServicesApiTest` |
| A12 | The app is in **French or English**. Language = the signed-in user's chosen language, else the `locale` cookie (choice made while signed out), else the browser's preferred language (`Accept-Language`) among fr/en, else English. The « Langue / Language » selector (sidebar, sign-in pages) saves the choice (cookie + user account, `PUT /api/me/language` `{language}`). Emails are sent in the recipient's language (else the current one). Dates and amounts are formatted for the language (fr-FR / en-GB), amounts stay in euros | `LocaleListener`, `User::speak()`, `ChooseLanguageHandler`, `SymfonyAccountMailer`, `assets/vue/i18n/` | `LanguageTest`, e2e `language.spec.js` |
| A13 | Each user may choose the app's **background** and **accent** colours (Paramètres › Apparence): one of five themes (Mousse — the default —, Terre cuite, Fjord, Prune, Nuit) or any `#rrggbb` pair, saved on their account (`PUT /api/me/theme` `{background, accent}`) and applied to every page they open. Every other colour derives from these two (`assets/styles/tokens.css`): text, surfaces and borders from the background (dark text on a light background, light text on a dark one), the information colours (danger red, success green, warning amber, chart blue) from the background's lightness blended with the accent, and the text on accent buttons from the accent | `Theme`, `User::wear()`, `ChooseThemeHandler`, `base.html.twig`, `AppearanceSettings.vue`, `useTheme.js` | `ThemeTest` (unit + functional), e2e `theme.spec.js` |
| A9 | Every page requires signing in; the API answers 401 to anonymous calls and 403 to cross-site writes | `security.yaml` `access_control`, `AuthenticationEntryPoint`, `SameOriginGuard` | `AuthenticationTest` |

## Pages

| Page | URL |
|---|---|
| Connexion | `/login` |
| Mot de passe oublié | `/password/forgot` |
| Choix du mot de passe (invitation or reset link) | `/password/set/{token}` → `/password/set` |
| Déconnexion (POST) | `/logout` |
| Paramètres | `/settings` — workspace name from the session; services connectés: `/api/services` (see [imports.md](imports.md)) |
