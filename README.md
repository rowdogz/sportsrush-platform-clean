# SportsRush 2.0

Sports prediction platform — monorepo.

## Structure

```
apps/
  api/        Hono API on Cloudflare Workers + D1
  web/        Next.js public web app
  admin/      Next.js admin portal (separate subdomain)
  mobile/     Expo React Native (iOS + Android)

packages/
  types/      Canonical TypeScript domain types (shared by all apps)
  scoring/    Canonical scoring engine — pure function, fully tested
  ui/         Shared React component library (web + admin only)
  api-client/ Typed HTTP client (web, admin, mobile)
  auth/       JWT helpers, role checking, password hashing
  validation/ Zod schemas for all request inputs
  events/     Cloudflare Queue payload type definitions

tooling/
  typescript-config/  Shared tsconfig bases (base, workers, nextjs, react-native)
  eslint-config/      Shared ESLint ruleset
  prettier-config/    Shared Prettier config

infrastructure/
  migrations/         D1 SQL migration files (numbered, sequential)
  scripts/migration/  WordPress → SR2.0 one-time ETL scripts

testing/
  e2e/        Playwright end-to-end tests (run against staging)
  fixtures/   Shared test data factories
  helpers/    Shared test utilities and Miniflare harness

docs/
  architecture/  All planning and architecture documents
  runbooks/      Operational procedures
  decisions/     Architecture Decision Records (ADRs)
  ai/            AI prompt templates for common development tasks
```

## Quick Start

```bash
# Install dependencies
pnpm install

# Copy the local API secrets template
cp apps/api/.dev.vars.example apps/api/.dev.vars

# Reset, migrate, and seed the local D1 database
pnpm db:reset:local

# Run the full platform locally
pnpm dev
```

See [docs/runbooks/local-development.md](docs/runbooks/local-development.md) for the
full onboarding guide.

For local API database setup and realistic admin seed data, see
[`apps/api/DEV_SEEDING.md`](apps/api/DEV_SEEDING.md).

## Commands

| Command                  | Description                              |
| ------------------------ | ---------------------------------------- |
| `pnpm dev`               | Start API, web, admin, and Expo together |
| `pnpm dev:core`          | Start API, web, and admin only           |
| `pnpm dev:mobile`        | Start Expo only                          |
| `pnpm build`             | Build all apps and packages              |
| `pnpm test`              | Run all unit tests                       |
| `pnpm lint`              | Lint all packages                        |
| `pnpm typecheck`         | Type-check all packages                  |
| `pnpm db:migrate:local`  | Apply local D1 migrations                |
| `pnpm db:seed:dev`       | Seed the local D1 demo dataset           |
| `pnpm db:reset:local`    | Recreate and reseed local D1             |
| `pnpm healthcheck:local` | Verify local API/web/admin               |
| `pnpm format`            | Format all files with Prettier           |
| `pnpm format:check`      | Check formatting without writing         |
| `pnpm clean`             | Remove all build artefacts               |

## Key Constraints

- **No `any` types.** ESLint is set to `error` on `@typescript-eslint/no-explicit-any`.
- **One concern per file.** Files over 200 lines should be split.
- **No secrets in code.** All secrets are managed via Wrangler secrets and `.dev.vars`
  (gitignored). See [docs/runbooks/secrets.md](docs/runbooks/secrets.md).
- **Canonical scoring engine first.** `packages/scoring` is the single source of truth
  for all point calculations. No scoring logic exists anywhere else.

## Stack

| Layer    | Technology                                              |
| -------- | ------------------------------------------------------- |
| API      | Hono on Cloudflare Workers                              |
| Database | Cloudflare D1 (SQLite at the edge)                      |
| Cache    | Cloudflare KV                                           |
| Queues   | Cloudflare Queues                                       |
| Storage  | Cloudflare R2                                           |
| Web      | Next.js (App Router)                                    |
| Mobile   | Expo React Native                                       |
| Language | TypeScript (strict mode throughout)                     |
| Tests    | Vitest + Miniflare (unit/integration), Playwright (E2E) |
| CI/CD    | GitHub Actions → Cloudflare                             |

## Documentation

Architecture documents live in `docs/architecture/`. Start with:

- `SPORTSRUSH_CANONICAL_RULES.md` — business rules contract (resolve Owner Decisions
  before starting Phase 1)
- `SPORTSRUSH_2_DOMAIN_MODEL.md` — bounded contexts and data ownership
- `SPORTSRUSH_2_REPOSITORY_STRUCTURE.md` — full file-by-file structure reference
- `PHASE_1_FOUNDATION_IMPLEMENTATION_PLAN.md` — current implementation plan
