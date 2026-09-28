# MossyTrunk

Small-business management app — module 1: **Order Management** (products, events & expenses, orders, bundle discounts, SumUp import, event profitability).

Symfony 8.1 · PHP 8.5 (FrankenPHP) · PostgreSQL 18 · Vue 3 via Symfony UX · PHPUnit · Playwright — all in Docker.

## Getting started

```bash
make build up        # http://localhost:8080 (HTTP_PORT to change)
make install         # composer + npm (first run)
make fixtures        # database + mock data
```

SumUp import: put `SUMUP_API_KEY` and `SUMUP_MERCHANT_CODE` in `.env.local`.

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
