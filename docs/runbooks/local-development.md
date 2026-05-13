# Local Development — Onboarding Guide

Get a SportsRush 2.0 development environment running in under 10 minutes.

---

## Prerequisites

| Tool         | Version | Install                                       |
| ------------ | ------- | --------------------------------------------- |
| Node.js      | 20+     | [nodejs.org](https://nodejs.org) or `nvm use` |
| pnpm         | 9+      | `npm install -g pnpm@9`                       |
| Wrangler CLI | 3+      | `pnpm add -g wrangler`                        |
| Git          | Any     | [git-scm.com](https://git-scm.com)            |

Check your versions:

```bash
node --version   # expect v20.x.x
pnpm --version   # expect 9.x.x
wrangler --version
```

---

## First-Time Setup

### 1. Clone and install

```bash
git clone https://github.com/your-org/sportsrush.git
cd sportsrush
pnpm install
```

### 2. Configure environment secrets

```bash
cp .env.example .env
```

Open `.env` and fill in values. See `docs/runbooks/secrets.md` for where to obtain
each one.

For the Cloudflare Workers API specifically, Wrangler uses a `.dev.vars` file (not
`.env`). This file is created in PR-04:

```bash
# Created in PR-04 — not needed until the API app exists
cp apps/api/.dev.vars.example apps/api/.dev.vars
```

### 3. Authenticate with Cloudflare (needed for D1, KV, Queues)

```bash
wrangler login
```

This opens a browser. Authenticate with the Cloudflare account that owns the D1
databases.

### 4. Apply database migrations (added in PR-05)

```bash
pnpm --filter @sr/api migrate:local
```

---

## Running the Development Servers

```bash
pnpm dev
```

Turborepo starts all apps in parallel:

| App          | URL                   | Notes                          |
| ------------ | --------------------- | ------------------------------ |
| `apps/api`   | http://localhost:8787 | Hono on Wrangler (added PR-04) |
| `apps/web`   | http://localhost:3000 | Next.js (added PR-06)          |
| `apps/admin` | http://localhost:3001 | Next.js (added PR-06)          |

---

## Running Tests

```bash
# Unit tests across all packages
pnpm test

# Integration tests (Miniflare + D1) — added in PR-07
pnpm --filter @sr/api test:integration

# End-to-end tests (Playwright) — requires dev servers running
pnpm test:e2e
```

---

## Working with Database Migrations

```bash
# Apply all pending migrations locally
pnpm --filter @sr/api migrate:local

# Create a new migration file
echo "-- Your SQL here" > infrastructure/migrations/0003_your_change.sql

# Check which migrations have been applied
wrangler d1 migrations list sportsrush --local

# Run a raw SQL query against the local D1
wrangler d1 execute sportsrush --local --command "SELECT name FROM sqlite_master WHERE type='table'"
```

---

## Linting & Formatting

```bash
# Check for lint errors
pnpm lint

# Check formatting (does not fix)
pnpm format:check

# Fix formatting
pnpm format
```

---

## Adding a New Package

1. Create the directory: `packages/my-package/`
2. Add `package.json` with name `@sr/my-package`
3. Add `tsconfig.json` extending `@sr/typescript-config/base.json`
4. Add `eslint.config.js` or `.eslintrc.js` extending `@sr/eslint-config`
5. Add scripts: `lint`, `typecheck`, `test`
6. Run `pnpm install` from the root to link the workspace

---

## Useful Commands

```bash
# Clean all build artefacts and node_modules
pnpm clean

# Type-check all packages
pnpm typecheck

# Run a command in a specific package only
pnpm --filter @sr/api typecheck

# List all workspace packages
pnpm -r list --depth 0

# Check for outdated dependencies
pnpm outdated -r
```

---

## Troubleshooting

### `pnpm install` fails with lockfile error

The lockfile is out of date. Run `pnpm install` without `--frozen-lockfile` locally,
commit the updated `pnpm-lock.yaml`, then re-run.

### Wrangler `binding not found` error

The `apps/api/.dev.vars` file is missing or incomplete. Check it has all keys from
`apps/api/.dev.vars.example`.

### TypeScript errors in Workers code

Check that `apps/api/tsconfig.json` extends `@sr/typescript-config/workers.json`
(not `nextjs.json`). Workers have no DOM — using the Next.js config will include DOM
types that do not exist at runtime.

### D1 foreign key constraint errors

The D1 client wrapper must execute `PRAGMA foreign_keys = ON` before any query. See
`apps/api/src/lib/d1.ts` (created in PR-04).
