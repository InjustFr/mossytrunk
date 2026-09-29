# Accounts & workspaces (Comptes et espaces de travail)

A **workspace** is one business: its catalogue, events, orders, discounts and settings. A **user** is a person who
signs in; each user belongs to exactly **one** workspace, and several users can share the same workspace.

| Entity | Fields |
|---|---|
| `Workspace` | `name` (unique) |
| `User` | `email` (login, unique, lowercase), `passwordHash` (empty until chosen), `workspace` |
| `PasswordToken` | `selector`, SHA-256 of the `verifier`, `purpose` (invitation / reset), `expiresAt`, `usedAt` |

Model: `src/Domain/Identity/`. Use cases: `src/Application/Identity/`. Sign-in: `config/packages/security.yaml`,
`src/Infrastructure/Security/`, `src/Presentation/Web/SecurityController.php`.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| A1 | No self-registration: users are created from the command line (`app:user:create <email> --workspace=<name>`); the workspace is created when it does not exist yet | `CreateUserHandler`, `CreateUserCommand` | `AccountUseCasesTest` |
| A2 | Email is valid, trimmed, lowercased and unique | `User::normalizeEmail()`, `CreateUserHandler` | `UserTest`, `AccountUseCasesTest` |
| A3 | A new user receives an **invitation** email to choose their password; they cannot sign in before | `CreateUserHandler`, `SymfonyAccountMailer` | `AccountUseCasesTest`, `AuthenticationTest`, e2e `auth.setup.js` |
| A4 | Password links are single-use; an invitation is valid **7 days**, a reset link **1 hour**; issuing a link revokes the previous ones | `PasswordToken`, `PasswordTokenPurpose`, `PasswordTokenIssuer` | `PasswordTokenTest`, `AccountUseCasesTest` |
| A5 | Only a hash of the link's secret is stored; the link token is moved from the URL to the session before the form is shown | `PasswordToken::issue()`, `SecurityController::passwordLink()` | `PasswordTokenTest`, `AuthenticationTest` |
| A6 | Passwords have at least **12 characters** and must be typed twice | `SetPasswordHandler::MIN_LENGTH`, `SecurityController::setPassword()` | `AccountUseCasesTest` |
| A7 | "Mot de passe oublié ?" always shows the same confirmation, whether the account exists or not; at most 3 requests per email and IP every 15 minutes | `RequestPasswordResetHandler`, `forgot_password` rate limiter | `AccountUseCasesTest`, `AuthenticationTest` |
| A8 | Sign-in: 5 failed attempts per 15 minutes are throttled; "Se souvenir de moi" keeps the session 30 days; a password change signs out the other sessions | `security.yaml` (`login_throttling`, `remember_me`), `SecurityUser::__serialize()` | e2e `auth.spec.js` |
| A10 | All business data is scoped to the user's workspace: lists only show it, an id from another workspace answers « introuvable » (404) | `WorkspaceContext` used by every Doctrine repository; aggregates are created with the current workspace (orders take their event's) | `WorkspaceIsolationTest`, `ProductApiTest` |
| A11 | Workspace secrets (access keys and tokens of the connected services) are stored **encrypted** (libsodium secretbox, key `APP_ENCRYPTION_KEY`), one per name and workspace; the API only returns whether a key is set and its last 4 characters; saving an empty key keeps the current one | `WorkspaceSecret`, `SecretName`, `WorkspaceSecrets`, `SodiumSecretCipher`, `ConnectionCredentials` | `SodiumSecretCipherTest`, `SecretNameTest`, `ServiceConnectionUseCasesTest`, `ServicesApiTest` |
| A9 | Every page requires signing in; the API answers 401 to anonymous calls and 403 to cross-site writes | `security.yaml` `access_control`, `AuthenticationEntryPoint`, `SameOriginGuard` | `AuthenticationTest` |

## Pages

| Page | URL |
|---|---|
| Connexion | `/connexion` |
| Mot de passe oublié | `/mot-de-passe/oublie` |
| Choix du mot de passe (invitation or reset link) | `/mot-de-passe/definir/{token}` → `/mot-de-passe/definir` |
| Déconnexion (POST) | `/deconnexion` |
| Paramètres | `/parametres` — API `GET /api/workspace/settings`; services connectés: `/api/services` (see [imports.md](imports.md)) |
