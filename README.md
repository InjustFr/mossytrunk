# MossyTrunk

Small-business management app — module 1: **Order Management** (products, events & expenses, orders, bundle discounts, SumUp import, event profitability).

Symfony 8.1 · PHP 8.5 (FrankenPHP) · PostgreSQL 18 · Vue 3 via Symfony UX · PHPUnit · Playwright — all in Docker.

## Getting started

```bash
make build up        # http://localhost:8080 (HTTP_PORT to change)
make install         # composer + npm (first run)
make fixtures        # database + mock data
```

Sign in with `demo@mossytrunk.local` / `mossytrunk`. Other users: `docker compose exec php php bin/console app:user:create <email> --workspace=<name>` sends an invitation (emails: Mailpit on http://localhost:8025).

SumUp import: set the merchant code and API key in « Paramètres » (stored encrypted with `APP_ENCRYPTION_KEY`; generate one with `php bin/console app:encryption:generate-key`).

## Quality

```bash
make test      # PHPUnit unit + functional
make deptrac   # onion layer rules
make e2e       # Playwright (dedicated app container, fake SumUp)
make qa        # all of the above
```

## Documentation

- Business rules: [`docs/business/`](docs/business/README.md)
- Architecture & conventions: [`CLAUDE.md`](CLAUDE.md)
