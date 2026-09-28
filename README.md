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

## Deploy

The `prod` target of the `Dockerfile` builds a self-contained image (vendor without dev deps, production assets, migrations run at boot). It is published on Docker Hub and run on the server with [`deploy/compose.yaml`](deploy/compose.yaml) + PostgreSQL.

```bash
docker login
make push                                   # build linux/amd64 and push IMAGE:<git sha> + :latest
make deploy DEPLOY_HOST=user@server         # push, copy deploy/ to ~/mossytrunk, pull and restart
make image PLATFORM=linux/arm64             # local build only
```

`IMAGE`, `TAG`, `PLATFORM`, `DEPLOY_DIR` are overridable. Server setup, port choice and reverse proxy: [`deploy/README.md`](deploy/README.md).

## Documentation

- Business rules: [`docs/business/`](docs/business/README.md)
- Architecture & conventions: [`CLAUDE.md`](CLAUDE.md)
