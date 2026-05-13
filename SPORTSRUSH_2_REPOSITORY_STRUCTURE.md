# SPORTSRUSH_2_REPOSITORY_STRUCTURE.md
## SportsRush 2.0 — Repository & Project Structure

**Sources:** All previous planning documents  
**Stack:** Hono · Cloudflare Workers · D1 · Cloudflare Queues · R2 · Next.js · Expo React Native · TypeScript · GitHub Actions  
**Approach:** Monorepo. One repository. One language. One type system. Shared where it reduces duplication; separate where it reduces coupling.

---

## STACK RECONCILIATION NOTE

The implementation stack differs from earlier architecture documents in four areas. The domain model, ownership boundaries, and event contracts in `SPORTSRUSH_2_DOMAIN_MODEL.md` remain unchanged — only the infrastructure layer changes.

| Previous doc | This document | Impact |
|---|---|---|
| Fastify (Node.js server) | Hono (Cloudflare Workers) | Stateless edge execution; no long-lived connections |
| PostgreSQL | D1 (SQLite at the edge) | Same SQL semantics; DENSE_RANK available in SQLite 3.25+ |
| BullMQ + Redis | Cloudflare Queues | Push-based queue delivery; no polling workers |
| Redis (caching) | Cloudflare KV + Cache API | Different TTL model; KV is eventually consistent |
| WebSocket server | Durable Objects | Stateful WebSocket connections managed at the edge |

All domain logic, scoring rules, and event contracts are stack-agnostic and transfer without change.

---

## SECTION 1 — MONOREPO STRUCTURE

### Tooling Choice
**pnpm workspaces** with a flat `pnpm-workspace.yaml`. Reasons: fast installs, strict dependency isolation between packages, compatible with Cloudflare Workers and Next.js build pipelines, supported by Turborepo for task caching.

**Turborepo** for build orchestration: caches build outputs per package, runs only what changed, makes CI fast.

### Top-Level Layout

```
sportsrush/
│
├── apps/                          # Deployable applications
│   ├── api/                       # Hono API on Cloudflare Workers
│   ├── web/                       # Next.js public web app
│   ├── admin/                     # Next.js admin portal
│   └── mobile/                    # Expo React Native app
│
├── packages/                      # Shared internal packages
│   ├── types/                     # Canonical TypeScript types (domain entities)
│   ├── scoring/                   # Canonical scoring engine (pure function)
│   ├── ui/                        # Shared React component library (web + admin)
│   ├── api-client/                # Typed HTTP client (web, admin, mobile)
│   ├── auth/                      # Auth helpers (JWT parsing, role checking)
│   ├── validation/                # Zod schemas for all domain entities
│   └── events/                    # Event type definitions (Cloudflare Queues payloads)
│
├── infrastructure/                # Cloudflare configuration and database
│   ├── migrations/                # D1 SQL migration files (numbered, sequential)
│   ├── wrangler/                  # Shared Wrangler environment configs
│   └── r2/                        # R2 bucket configuration scripts
│
├── tooling/                       # Shared developer tooling configs
│   ├── eslint-config/             # Shared ESLint ruleset
│   ├── typescript-config/         # Shared tsconfig bases
│   └── prettier-config/           # Shared Prettier config
│
├── testing/                       # Cross-app test infrastructure
│   ├── e2e/                       # Playwright end-to-end tests
│   ├── fixtures/                  # Shared test data factories
│   └── helpers/                   # Shared test utilities and mocks
│
├── docs/                          # Project documentation
│   ├── architecture/              # All SPORTSRUSH_2_*.md files live here
│   ├── runbooks/                  # Operational runbooks (cutover, rollback, etc.)
│   ├── decisions/                 # ADR (Architecture Decision Records)
│   └── owner-decisions/           # Resolved OD-01 through OD-15
│
├── .github/                       # GitHub Actions workflows
│   └── workflows/
│       ├── ci.yml                 # PR validation
│       ├── deploy-dev.yml         # Deploy to dev on merge to main
│       ├── deploy-staging.yml     # Manual promotion to staging
│       └── deploy-production.yml  # Manual promotion with approval gate
│
├── turbo.json                     # Turborepo task pipeline
├── pnpm-workspace.yaml            # pnpm workspace config
├── package.json                   # Root package (scripts only, no dependencies)
└── .env.example                   # All required environment keys, no values
```

### Root `package.json` Scripts
```json
{
  "scripts": {
    "dev": "turbo dev",
    "build": "turbo build",
    "test": "turbo test",
    "lint": "turbo lint",
    "typecheck": "turbo typecheck",
    "migrate:dev": "pnpm --filter @sr/api migrate:dev",
    "migrate:staging": "pnpm --filter @sr/api migrate:staging"
  }
}
```

### Package Naming Convention
All internal packages are scoped under `@sr/`:
- `@sr/types`, `@sr/scoring`, `@sr/ui`, `@sr/api-client`, `@sr/auth`, `@sr/validation`, `@sr/events`
- `@sr/api`, `@sr/web`, `@sr/admin`, `@sr/mobile`
- `@sr/eslint-config`, `@sr/typescript-config`

---

## SECTION 2 — SHARED PACKAGES

### `packages/types` — Canonical Domain Types

The single source of truth for all domain entity types. Every other package imports from here. No package may define a domain entity type locally.

```
packages/types/
├── src/
│   ├── index.ts                   # Re-exports all types
│   ├── auth.ts                    # User, Session, Role, OAuthAccount
│   ├── competition.ts             # Competition, Sport
│   ├── fixture.ts                 # Match, Team, TeamAlias, MatchStatus
│   ├── prediction.ts              # Prediction, PredictionInput
│   ├── scoring.ts                 # MatchScore, ScoringConfig, ScoringConfigVersion
│   ├── ranking.ts                 # RankingRow, RankingSnapshot, MonthlyWinner
│   ├── league.ts                  # League, LeagueMember, LeagueInvite, LeagueStatus
│   ├── payment.ts                 # PaymentEvent, PaymentStatus
│   ├── notification.ts            # NotificationLog, NotificationType, Channel
│   ├── scraper.ts                 # ScraperResult, ScraperRun, UnresolvedAlias
│   └── common.ts                  # UUID, Timestamp, PaginatedResponse, ApiError
├── package.json
└── tsconfig.json
```

**Rules:**
- All types are `readonly` where possible (domain entities are not mutated in place)
- ID fields are typed as `Brand<string, 'UserId'>` (branded types) — a `UserId` cannot be accidentally passed where a `MatchId` is expected
- Nullable fields use `T | null`, never `T | undefined` (explicit nullability)
- No classes — only interfaces and type aliases
- No runtime code in this package — types only, zero bytes in the bundle

### `packages/scoring` — Canonical Scoring Engine

The most critical shared package. Pure functions, no side effects, no I/O.

```
packages/scoring/
├── src/
│   ├── index.ts                   # Public API: exports calculateMatchScore, calculateRoundScore
│   ├── calculate-match-score.ts   # Core scoring function
│   ├── calculate-round-score.ts   # Aggregates match scores for a round
│   ├── config.ts                  # ScoringConfig type and default values
│   ├── rules/
│   │   ├── exact-score.ts         # Exact score rule
│   │   ├── toto.ts                # Correct outcome rule
│   │   ├── home-bonus.ts          # Home goals bonus rule
│   │   ├── away-bonus.ts          # Away goals bonus rule
│   │   ├── diff-bonus.ts          # Goal difference bonus rule
│   │   └── joker.ts               # Joker multiplier rule
│   └── verify/
│       └── verify-against-snapshot.ts  # CLI tool: verify output matches WP snapshot
├── __tests__/
│   ├── calculate-match-score.test.ts   # All scenarios from CANONICAL_RULES §1.1–1.6
│   ├── joker.test.ts
│   ├── diff-bonus.test.ts
│   └── fuzz.test.ts               # 1000 random inputs — determinism and type safety
├── package.json
└── tsconfig.json
```

**Rules:**
- `calculateMatchScore` is a pure function: `(result, prediction, config) => MatchScore`
- No database access, no network calls, no side effects
- All rules are separate files — each rule is independently testable
- `verify/` contains the CLI verification script used in Phase 1 acceptance criteria

### `packages/events` — Event Type Definitions

Defines the payload shape for every event published to Cloudflare Queues. The queue producer and consumer must agree on the schema — this package is the contract.

```
packages/events/
├── src/
│   ├── index.ts
│   ├── result-events.ts           # ResultPublishedEvent, ResultCorrectedEvent
│   ├── scoring-events.ts          # ScoresRecalculatedEvent
│   ├── ranking-events.ts          # RankingsUpdatedEvent, MonthlyWinnerDeclaredEvent
│   ├── prediction-events.ts       # PredictionSavedEvent, PredictionLockedEvent
│   ├── league-events.ts           # LeagueMemberJoinedEvent, LeagueMemberRemovedEvent
│   ├── payment-events.ts          # PaymentCompletedEvent, PaymentRefundedEvent
│   ├── scraper-events.ts          # ScraperRunCompletedEvent, AliasUnresolvedEvent
│   └── notification-events.ts     # (consumed by notification workers)
├── package.json
└── tsconfig.json
```

Each event type includes:
- `eventType: string` — namespaced string literal (`'result.published'`)
- `eventId: UUID` — unique event ID for idempotency
- `occurredAt: string` — ISO 8601 timestamp
- The event payload fields

### `packages/validation` — Zod Schemas

Zod schemas for all request bodies, query parameters, and domain entity validation. Used by:
- `apps/api` — request body parsing and validation (Hono uses Zod via `@hono/zod-validator`)
- `apps/web` and `apps/admin` — form validation (same schema, no duplication)
- `apps/mobile` — form validation

```
packages/validation/
├── src/
│   ├── index.ts
│   ├── auth.ts                    # RegisterSchema, LoginSchema, PasswordResetSchema
│   ├── competition.ts             # CreateCompetitionSchema, UpdateCompetitionSchema
│   ├── fixture.ts                 # CreateMatchSchema, UpdateMatchSchema, EnterResultSchema
│   ├── prediction.ts              # SavePredictionSchema (home_score, away_score, joker)
│   ├── league.ts                  # CreateLeagueSchema, UpdateLeagueSchema
│   └── common.ts                  # UUIDSchema, PaginationSchema, DateRangeSchema
├── package.json
└── tsconfig.json
```

**Rule:** Zod schemas are the input layer. The `@sr/types` interfaces are the domain layer. They are separate — a schema parses and validates untrusted input; a type describes a validated domain entity.

### `packages/api-client` — Typed HTTP Client

A typed fetch wrapper that mirrors the API's route structure. Used by web, admin, and mobile. Generated from the API's Zod schemas to ensure the client and server always agree.

```
packages/api-client/
├── src/
│   ├── index.ts                   # createApiClient(baseUrl, getToken) → ApiClient
│   ├── client.ts                  # Base fetch wrapper (auth headers, error handling, retries)
│   ├── modules/
│   │   ├── auth.ts                # client.auth.login(), client.auth.refresh(), etc.
│   │   ├── competitions.ts
│   │   ├── fixtures.ts
│   │   ├── predictions.ts
│   │   ├── rankings.ts
│   │   ├── leagues.ts
│   │   └── admin.ts
│   └── types.ts                   # ApiClientConfig, ApiResponse<T>
├── package.json
└── tsconfig.json
```

The client is environment-agnostic — it uses the native `fetch` API, which is available in Cloudflare Workers, Next.js (server and client), and React Native.

### `packages/auth` — Auth Helpers

Shared JWT validation and role-checking logic, usable in both the API (Worker) and the web/admin (Next.js middleware).

```
packages/auth/
├── src/
│   ├── index.ts
│   ├── jwt.ts                     # verifyAccessToken(token, secret) → TokenPayload | null
│   ├── roles.ts                   # hasRole(payload, role), assertRole(payload, role)
│   ├── session.ts                 # TokenPayload type (user_id, role, session_id, exp)
│   └── cookies.ts                 # Cookie name constants, cookie options per environment
├── package.json
└── tsconfig.json
```

### `packages/ui` — Shared React Component Library

Used by `apps/web` and `apps/admin`. Not used by `apps/mobile` (React Native has its own component layer).

```
packages/ui/
├── src/
│   ├── index.ts                   # All public component exports
│   ├── components/
│   │   ├── Button/
│   │   │   ├── Button.tsx
│   │   │   └── Button.test.tsx
│   │   ├── Card/
│   │   ├── Table/
│   │   ├── Modal/
│   │   ├── Badge/
│   │   ├── Input/
│   │   ├── Select/
│   │   └── Pagination/
│   ├── domain/                    # Domain-aware display components (no data fetching)
│   │   ├── RankingRow/            # Renders a single leaderboard row
│   │   ├── MatchCard/             # Renders a fixture/result card
│   │   ├── PredictionInput/       # Individual score input field
│   │   └── ScoreBadge/            # Points badge with exact/toto/joker visual variants
│   └── tokens/
│       ├── colours.ts             # Brand colour tokens
│       ├── typography.ts
│       └── spacing.ts
├── package.json
└── tsconfig.json
```

**Rule:** Components in `packages/ui` have no data fetching, no API calls, no router dependencies. They receive data as props and emit events via callbacks. They are pure display components.

---

## SECTION 3 — BACKEND STRUCTURE (`apps/api`)

### Overview
A single Hono application deployed as a Cloudflare Worker. Stateless by design — no in-memory state, no long-lived connections. State lives in D1, KV, Durable Objects, and Queues.

### Directory Structure

```
apps/api/
│
├── src/
│   ├── index.ts                   # Worker entry point. Exports default Hono app + queue handlers + cron handlers.
│   ├── app.ts                     # Hono app creation, middleware registration, route mounting
│   │
│   ├── modules/                   # One directory per bounded context (matches DOMAIN_MODEL.md)
│   │   │
│   │   ├── auth/
│   │   │   ├── routes.ts          # Hono route handlers: POST /auth/login, etc.
│   │   │   ├── service.ts         # Business logic: login(), register(), refreshToken()
│   │   │   ├── repository.ts      # D1 queries: findUserByEmail(), createSession(), etc.
│   │   │   ├── middleware.ts       # requireAuth() middleware (used across all modules)
│   │   │   └── types.ts           # Module-local types (extends @sr/types)
│   │   │
│   │   ├── competitions/
│   │   │   ├── routes.ts
│   │   │   ├── service.ts
│   │   │   └── repository.ts
│   │   │
│   │   ├── fixtures/
│   │   │   ├── routes.ts
│   │   │   ├── service.ts         # applyResult() — the event-emitting function
│   │   │   ├── repository.ts
│   │   │   └── ingestion.ts       # Anti-corruption layer: ScraperResult → Match write
│   │   │
│   │   ├── predictions/
│   │   │   ├── routes.ts
│   │   │   ├── service.ts         # savePrediction() — contains lock check transaction
│   │   │   └── repository.ts
│   │   │
│   │   ├── scoring/
│   │   │   ├── service.ts         # recalculateMatch() — calls @sr/scoring, writes match_scores
│   │   │   ├── repository.ts      # writeMatchScores(), getMatchPredictions()
│   │   │   └── config.ts          # getScoringConfig() — reads from D1, cached in KV
│   │   │
│   │   ├── rankings/
│   │   │   ├── routes.ts
│   │   │   ├── service.ts         # getRankings(), getMonthlyWinner(), writeSnapshot()
│   │   │   └── repository.ts
│   │   │
│   │   ├── leagues/
│   │   │   ├── routes.ts
│   │   │   ├── service.ts         # grantMembership(), revokeMembership() (called by payments)
│   │   │   └── repository.ts
│   │   │
│   │   ├── payments/
│   │   │   ├── routes.ts          # POST /webhooks/stripe, POST /leagues/:id/checkout
│   │   │   ├── service.ts         # processStripeWebhook(), createCheckoutSession()
│   │   │   └── repository.ts      # writePaymentEvent(), findEventByStripeId()
│   │   │
│   │   ├── notifications/
│   │   │   ├── service.ts         # dispatch() — called by queue consumers
│   │   │   └── templates.ts       # Notification payload builders per event type
│   │   │
│   │   ├── scraper/
│   │   │   ├── service.ts         # runScraper(), resolveAliases()
│   │   │   ├── repository.ts      # writeScraperRun(), writeUnresolvedAlias()
│   │   │   └── adapters/
│   │   │       ├── bbc.ts         # BBC Sport adapter → ScraperResult[]
│   │   │       ├── rlcom.ts       # RL.com adapter → ScraperResult[]
│   │   │       └── base.ts        # ScraperAdapter interface
│   │   │
│   │   └── admin/
│   │       ├── routes.ts          # Admin-only routes (audit log, user management, etc.)
│   │       ├── service.ts
│   │       └── repository.ts
│   │
│   ├── queue-consumers/            # Cloudflare Queues message handlers
│   │   ├── index.ts               # Exports queue() handler for wrangler.toml binding
│   │   ├── router.ts              # Routes queue messages to the right handler by eventType
│   │   ├── score-recalculation.ts # Handles ScoresRecalculatedEvent
│   │   ├── ranking-update.ts      # Handles ScoresRecalculatedEvent (downstream)
│   │   ├── notification-dispatch.ts # Handles all notification trigger events
│   │   └── scraper-ingest.ts      # Handles scraped data ingestion
│   │
│   ├── scheduled/                  # Cloudflare Cron Triggers
│   │   ├── index.ts               # Exports scheduled() handler for wrangler.toml
│   │   ├── hourly-results.ts      # Trigger results scraper every hour during season
│   │   ├── weekly-fixtures.ts     # Trigger fixture fetch every Monday 06:00 UTC
│   │   ├── monthly-winner.ts      # Calculate monthly winner on 1st of month 00:05 UTC
│   │   └── cleanup.ts             # Purge expired sessions and stale push tokens
│   │
│   ├── middleware/
│   │   ├── auth.ts                # requireAuth() — extracts and verifies JWT
│   │   ├── require-admin.ts       # requireAdmin() — asserts role === 'admin'
│   │   ├── require-superadmin.ts  # requireSuperAdmin() — asserts role === 'superadmin'
│   │   ├── cors.ts                # CORS config per environment
│   │   ├── rate-limit.ts          # Route-level rate limiting via KV counters
│   │   └── error-handler.ts       # Catches all errors → consistent JSON error response
│   │
│   └── lib/
│       ├── d1.ts                  # D1 query helpers (typed query builder wrapper)
│       ├── kv.ts                  # KV get/set/delete with TTL helpers
│       ├── queues.ts              # Queue producer: enqueue(queueName, event) helper
│       ├── r2.ts                  # R2 upload/delete/signed-URL helpers
│       ├── stripe.ts              # Stripe client initialisation
│       ├── logger.ts              # Structured JSON logging (console.log in Workers)
│       └── env.ts                 # Typed environment binding interface (Hono Env type)
│
├── wrangler.toml                  # Worker configuration (see Environment Strategy)
├── package.json
└── tsconfig.json
```

### Module Internal Structure Convention
Every module follows the same three-layer pattern:

```
routes.ts    → Hono route handlers. Validates input (Zod). Calls service. Returns HTTP response.
service.ts   → Business logic. Enforces domain rules. Calls repository. Emits events to queue.
repository.ts → D1 queries only. No business logic. Returns typed domain objects.
```

**Why three layers?**
- `routes.ts` can be tested with `hono/testing` (mock request/response)
- `service.ts` can be tested with mocked repositories (pure business logic)
- `repository.ts` can be tested with a test D1 database (SQL correctness)
- Each layer has one reason to change

### `env.ts` — Typed Cloudflare Bindings

Hono's generic `Env` type captures all Cloudflare bindings. This file is the single definition:

```typescript
// apps/api/src/lib/env.ts
export type Env = {
  Bindings: {
    DB: D1Database
    CACHE: KVNamespace
    QUEUE_SCORING: Queue
    QUEUE_NOTIFICATIONS: Queue
    QUEUE_SCRAPER: Queue
    ASSETS: R2Bucket
    STRIPE_SECRET_KEY: string
    STRIPE_WEBHOOK_SECRET: string
    JWT_SECRET: string
    SPORTS_API_KEY: string
    ENVIRONMENT: 'local' | 'dev' | 'staging' | 'production'
  }
  Variables: {
    userId: string      // set by requireAuth middleware
    userRole: string    // set by requireAuth middleware
    sessionId: string   // set by requireAuth middleware
  }
}
```

---

## SECTION 4 — FRONTEND STRUCTURE

### Public Web App (`apps/web`)

```
apps/web/
│
├── src/
│   ├── app/                       # Next.js App Router
│   │   ├── layout.tsx             # Root layout (font, metadata, providers)
│   │   ├── page.tsx               # Landing page → redirects to /rankings
│   │   ├── rankings/
│   │   │   └── page.tsx           # SSR: competition rankings
│   │   ├── predictions/
│   │   │   └── page.tsx           # CSR: user predictions form (auth required)
│   │   ├── fixtures/
│   │   │   └── page.tsx           # SSR: upcoming fixtures
│   │   ├── results/
│   │   │   └── page.tsx           # SSR: past results
│   │   ├── leagues/
│   │   │   ├── page.tsx           # League catalogue
│   │   │   └── [id]/
│   │   │       ├── page.tsx       # League detail + rankings
│   │   │       └── join/
│   │   │           └── page.tsx   # Join flow
│   │   ├── auth/
│   │   │   ├── login/page.tsx
│   │   │   ├── register/page.tsx
│   │   │   ├── verify-email/page.tsx
│   │   │   └── reset-password/page.tsx
│   │   └── profile/
│   │       └── page.tsx           # User profile + notification preferences
│   │
│   ├── components/                # Web-app-specific components (use @sr/ui for primitives)
│   │   ├── features/
│   │   │   ├── Rankings/
│   │   │   │   ├── RankingsTable.tsx      # Full leaderboard table
│   │   │   │   ├── CompetitionFilter.tsx  # Competition selector
│   │   │   │   └── MonthlyWinnerBanner.tsx
│   │   │   ├── Predictions/
│   │   │   │   ├── PredictionsForm.tsx    # Outer form container
│   │   │   │   ├── RoundSelector.tsx
│   │   │   │   ├── MatchPrediction.tsx    # Single match prediction row
│   │   │   │   └── LockCountdown.tsx      # Countdown to lock time
│   │   │   └── Leagues/
│   │   │       ├── LeagueCatalogue.tsx
│   │   │       └── LeagueCard.tsx
│   │   └── layouts/
│   │       ├── SiteHeader.tsx
│   │       ├── SiteFooter.tsx
│   │       └── CompetitionSidebar.tsx
│   │
│   ├── hooks/                     # React hooks (data fetching, UI state)
│   │   ├── useCurrentUser.ts      # Current authenticated user
│   │   ├── useRankings.ts         # Rankings with TanStack Query
│   │   ├── usePredictions.ts      # User predictions with optimistic updates
│   │   ├── useCompetitions.ts
│   │   └── useLiveRankings.ts     # Durable Object WebSocket subscription
│   │
│   ├── lib/
│   │   ├── api.ts                 # Instantiates @sr/api-client for web
│   │   ├── auth.ts                # Next.js session helpers (cookie reading)
│   │   └── query-client.ts        # TanStack Query client config
│   │
│   └── middleware.ts              # Next.js edge middleware: auth redirects
│
├── next.config.ts
├── tailwind.config.ts
├── package.json
└── tsconfig.json
```

### Admin Portal (`apps/admin`)

Same structure as `apps/web` but scoped to admin routes. Shares `@sr/ui` but has its own layout and admin-specific feature components.

```
apps/admin/
│
├── src/
│   ├── app/
│   │   ├── layout.tsx             # Admin layout (sidebar nav, auth guard)
│   │   ├── page.tsx               # Dashboard overview
│   │   ├── fixtures/
│   │   │   ├── page.tsx           # Fixture list with edit/create
│   │   │   ├── new/page.tsx       # Create fixture form
│   │   │   └── [id]/page.tsx      # Edit fixture, enter result, view correction history
│   │   ├── results/
│   │   │   └── page.tsx           # Result entry and correction
│   │   ├── users/
│   │   │   ├── page.tsx
│   │   │   └── [id]/page.tsx      # User detail + suspend/unsuspend
│   │   ├── leagues/
│   │   │   ├── page.tsx
│   │   │   └── [id]/page.tsx      # League detail + membership management
│   │   ├── scraper/
│   │   │   ├── page.tsx           # Run history + unresolved aliases
│   │   │   └── aliases/page.tsx   # Alias resolution interface
│   │   ├── scoring/
│   │   │   └── page.tsx           # Scoring config view + change history (superadmin)
│   │   └── audit-log/
│   │       └── page.tsx           # Audit log browser
│   │
│   ├── components/
│   │   ├── features/
│   │   │   ├── FixtureForm.tsx
│   │   │   ├── ResultCorrectionModal.tsx
│   │   │   ├── AliasResolutionForm.tsx
│   │   │   └── AuditLogTable.tsx
│   │   └── layouts/
│   │       └── AdminSidebar.tsx
│   │
│   └── middleware.ts              # Cloudflare Access JWT verification for admin subdomain
│
├── next.config.ts
├── tailwind.config.ts
├── package.json
└── tsconfig.json
```

### Shared UI Strategy

```
Primitive components (Button, Card, Input):  @sr/ui
Domain display components (RankingRow, MatchCard): @sr/ui/domain
Application feature components (PredictionsForm, RankingsTable): apps/web/components
Admin feature components (FixtureForm, AuditLogTable): apps/admin/components
```

The dividing line: if a component could be used in both web and admin with no changes, it belongs in `@sr/ui`. If it is specific to one application's data fetching or routing, it stays in the application.

---

## SECTION 5 — MOBILE STRUCTURE (`apps/mobile`)

```
apps/mobile/
│
├── src/
│   ├── app/                       # Expo Router (file-based routing)
│   │   ├── _layout.tsx            # Root layout (auth provider, query client)
│   │   ├── (auth)/
│   │   │   ├── login.tsx
│   │   │   ├── register.tsx
│   │   │   └── verify-email.tsx
│   │   ├── (tabs)/                # Tab navigation
│   │   │   ├── _layout.tsx        # Tab bar config
│   │   │   ├── rankings.tsx
│   │   │   ├── predictions.tsx
│   │   │   ├── fixtures.tsx
│   │   │   └── profile.tsx
│   │   └── leagues/
│   │       ├── index.tsx
│   │       └── [id].tsx
│   │
│   ├── components/                # Native components (React Native, not web HTML)
│   │   ├── RankingsList.tsx       # FlatList-based leaderboard
│   │   ├── PredictionRow.tsx      # Native prediction input row
│   │   ├── MatchCard.tsx          # Native match card
│   │   ├── ScoreBadge.tsx         # Native points badge
│   │   └── OfflineBanner.tsx      # Shown when NetInfo reports offline
│   │
│   ├── hooks/                     # Shared hooks (use @sr/api-client)
│   │   ├── useCurrentUser.ts      # Same interface as web, different storage
│   │   ├── useRankings.ts         # TanStack Query — shared logic with web
│   │   ├── usePredictions.ts
│   │   └── useNetwork.ts          # NetInfo wrapper
│   │
│   └── lib/
│       ├── api.ts                 # Instantiates @sr/api-client for mobile
│       ├── auth.ts                # Expo SecureStore token management
│       ├── notifications.ts       # Expo push token registration
│       └── query-client.ts        # TanStack Query + AsyncStorage persistence
│
├── app.json                       # Expo config
├── eas.json                       # Expo EAS Build config
├── package.json
└── tsconfig.json
```

### Shared Code Strategy

```
Shared with web:       @sr/types, @sr/scoring, @sr/api-client, @sr/validation, @sr/auth
Shared hooks logic:    useRankings, usePredictions — same TanStack Query logic, different clients
NOT shared:            @sr/ui (Tailwind/HTML is not React Native)
NOT shared:            Native components (platform-specific)
NOT shared:            Expo-specific auth (SecureStore vs cookies)
```

### Offline / Cache Handling

```
apps/mobile/src/lib/query-client.ts
```

Configures TanStack Query with `@tanstack/query-async-storage-persister`. Rules:
- Rankings, fixtures, and the user's own predictions are persisted to AsyncStorage
- Cache is shown stale while revalidating in the background
- Predictions form shows a `OfflineBanner` when offline (NetInfo)
- Prediction submissions require connectivity — queued offline submission is not supported (the server-side lock makes it unsafe)
- Auth refresh failures when offline → the user remains logged in until the token expires (mobile expects intermittent connectivity)

---

## SECTION 6 — DATABASE MIGRATIONS (`infrastructure/migrations`)

### Strategy
Sequential, numbered SQL files. Applied by the Wrangler CLI (`wrangler d1 migrations apply`). Every migration has an `up` direction; destructive migrations include a `down` file.

```
infrastructure/migrations/
├── 0001_initial_schema.sql           # All tables (see DOMAIN_MODEL.md ownership)
├── 0002_seed_scoring_config.sql      # Initial scoring config row (resolved Owner Decisions)
├── 0003_seed_competitions.sql        # Initial competition rows
├── 0004_indexes.sql                  # Performance indexes
├── 0005_add_league_status.sql        # Example: additive migrations
├── 0005_add_league_status.down.sql   # Rollback for 0005
└── ...
```

### Migration Conventions
- Filenames: `{4-digit-number}_{descriptive_name}.sql`
- Additive operations only (new columns with defaults, new tables) — never breaking changes
- Dropping a column requires two migrations: first add a replacement column + migrate data, then drop the old one in a subsequent release
- `0001_initial_schema.sql` is based on the **live production database export** — not the committed backup (see Phase 0 pre-action in `SPORTSRUSH_2_IMPLEMENTATION_SEQUENCE.md`)
- Every migration file is reviewed in pull request before merging

### D1-Specific Notes
- D1 is SQLite — data types differ from PostgreSQL (no `UUID` type natively; store as `TEXT`)
- `DENSE_RANK()` is available in SQLite 3.25+ — D1 supports it
- No `timestamptz` — store all datetimes as `TEXT` in ISO 8601 UTC format, or as `INTEGER` Unix timestamps
- No `JSONB` — use `TEXT` with JSON serialised strings for flexible payload columns
- Foreign keys must be enabled per connection with `PRAGMA foreign_keys = ON` (handled in `lib/d1.ts`)
- Transactions in D1 are synchronous within a Worker invocation

---

## SECTION 7 — TESTING STRATEGY

### Philosophy
Tests are documentation. Every test describes a specific behaviour or rule, not an implementation detail. Test names read like sentences.

### Test Locations

```
Test type        Location                           Runner
─────────────    ─────────────────────────────────  ─────────────
Unit             Co-located with source (*.test.ts)  Vitest
Integration      apps/api/src/modules/**/*.int.test.ts  Vitest + miniflare
E2E              testing/e2e/                        Playwright
Scoring verify   packages/scoring/src/verify/       Node CLI script
Migration        infrastructure/migrations/*.test.sql  wrangler d1 execute
```

### Unit Tests (Vitest)

Co-located alongside source files. One `*.test.ts` per `*.ts` file where the file has testable logic.

```
packages/scoring/src/calculate-match-score.ts
packages/scoring/src/calculate-match-score.test.ts   ← co-located

apps/api/src/modules/predictions/service.ts
apps/api/src/modules/predictions/service.test.ts     ← co-located
```

`packages/scoring` has 100% branch coverage requirement enforced by CI.  
All other packages and apps target > 80% branch coverage on service and repository files.

### Integration Tests (Vitest + Miniflare)

`miniflare` emulates the Cloudflare Workers runtime locally, including D1, KV, Queues, and R2 bindings. Integration tests make real HTTP requests to a locally-running Worker using a test D1 database.

```
apps/api/src/modules/auth/auth.int.test.ts
```

Each integration test file:
1. Starts miniflare with a fresh D1 database
2. Applies all migrations to the test database
3. Seeds necessary data
4. Makes HTTP requests using `fetch(worker.fetch, ...)`
5. Asserts response status, headers, and body
6. Tears down after each test

Integration tests are the primary correctness check for API behaviour. They catch D1 query errors, middleware ordering bugs, and route misconfigurations that unit tests cannot.

### Scoring Verification Tests

A special category — these run the canonical scoring engine against real historical WordPress data and assert the output matches the current leaderboard.

```
packages/scoring/src/verify/verify-against-snapshot.ts
```

Run as:
```bash
pnpm --filter @sr/scoring verify -- --snapshot ./data/wp-snapshot-2024-12-01.json
```

Output: per-user, per-competition point totals compared side by side. Any discrepancy halts the CI pipeline for Phase 1 and Phase 8.

### End-to-End Tests (Playwright)

Located in `testing/e2e/`. Runs against the `staging` environment. Tests critical user flows only — not every page.

```
testing/e2e/
├── auth.spec.ts               # Register → verify email → login → logout
├── predictions.spec.ts        # Login → navigate to predictions → submit → see saved state
├── predictions-lock.spec.ts   # Attempt prediction after lock → see locked state
├── rankings.spec.ts           # View rankings → filter by competition → paginate
├── league-join.spec.ts        # Browse catalogue → join free league → see ranking
├── league-payment.spec.ts     # Join paid league → Stripe test checkout → see ranking
└── admin-result-entry.spec.ts # Login as admin → enter result → see rankings update
```

### Contract Tests

The `packages/api-client` has contract tests that verify the client's type assumptions match what the API actually returns. Run against the dev environment after every deploy:

```
testing/contracts/
├── auth-contract.test.ts
├── rankings-contract.test.ts
└── predictions-contract.test.ts
```

---

## SECTION 8 — ENVIRONMENT STRATEGY

### Four Environments

| Environment | Purpose | Workers Route | D1 Database | KV Namespace |
|---|---|---|---|---|
| `local` | Developer machine via miniflare | localhost:8787 | Local D1 file | Local KV emulation |
| `dev` | Shared dev; auto-deployed on merge to `main` | dev-api.sportsrush.co.uk | D1 dev binding | KV dev binding |
| `staging` | Pre-production; manual promotion | staging-api.sportsrush.co.uk | D1 staging binding | KV staging binding |
| `production` | Live site | api.sportsrush.co.uk | D1 production binding | KV production binding |

### `wrangler.toml` Structure

```toml
# apps/api/wrangler.toml

name = "sportsrush-api"
main = "src/index.ts"
compatibility_date = "2024-11-01"

[[d1_databases]]
binding = "DB"
database_name = "sportsrush-prod"
database_id = "..."   # production D1 ID

[[kv_namespaces]]
binding = "CACHE"
id = "..."

[[queues.producers]]
binding = "QUEUE_SCORING"
queue = "sr-scoring-prod"

[env.dev]
[[env.dev.d1_databases]]
binding = "DB"
database_name = "sportsrush-dev"
database_id = "..."

[env.staging]
...
```

Secrets (JWT_SECRET, STRIPE_SECRET_KEY, etc.) are stored as Cloudflare Worker secrets via `wrangler secret put`. They are never in `wrangler.toml`.

### Local Development

```bash
# Start all apps in dev mode
pnpm dev                          # Turborepo: starts api, web, admin concurrently

# API: miniflare with local D1
pnpm --filter @sr/api dev         # wrangler dev --local

# Web: Next.js dev server
pnpm --filter @sr/web dev

# Admin: Next.js dev server  
pnpm --filter @sr/admin dev
```

Local development uses `.dev.vars` (gitignored) for secrets:
```
# apps/api/.dev.vars  (gitignored — never committed)
JWT_SECRET=local-dev-secret-not-real
STRIPE_SECRET_KEY=sk_test_...
```

`.env.example` at the repo root documents every required key with a description and dummy value.

---

## SECTION 9 — MIGRATION TOOLING (`infrastructure/migrations` + scripts)

### Data Migration Scripts

One-time ETL scripts for the WordPress → SR2.0 migration. Located in `infrastructure/scripts/migration/`. Each script is idempotent (safe to re-run), has a dry-run mode, and produces a summary report.

```
infrastructure/
├── migrations/                    # D1 schema migrations (versioned SQL)
│   └── 0001_initial_schema.sql
│   └── ...
│
└── scripts/
    ├── migration/                 # WordPress → SR2.0 one-time ETL
    │   ├── README.md              # How to run the full migration sequence
    │   ├── 01-migrate-users.ts    # wp_users + wp_usermeta → users + user_profiles
    │   ├── 02-migrate-competitions.ts
    │   ├── 03-migrate-teams.ts
    │   ├── 04-migrate-matches.ts  # pool_wpkl_matches → matches (including round column)
    │   ├── 05-migrate-aliases.ts  # PHP hardcoded array → team_aliases
    │   ├── 06-migrate-predictions.ts
    │   ├── 07-migrate-leagues.ts  # custom_competitions → leagues
    │   ├── 08-migrate-memberships.ts # custom_competition_users → league_members
    │   ├── 09-recalculate-scores.ts  # Run scoring engine on all historical predictions
    │   └── 10-verify-rankings.ts  # Compare SR2.0 output to WP snapshot
    │
    ├── sync/                      # Live sync during parallel-running phase
    │   ├── sync-predictions.ts    # Pull new WP predictions → SR2.0 (nightly)
    │   └── sync-results.ts        # Pull new WP results → SR2.0 (hourly)
    │
    └── tools/
        ├── wp-export.ts           # Export specific tables from live WP MySQL database
        └── compare-rankings.ts    # Generate side-by-side ranking comparison report
```

### Migration Script Interface

Every ETL script follows this interface:

```typescript
// Standard interface for all migration scripts
interface MigrationScript {
  name: string
  description: string
  run(options: { dryRun: boolean; verbose: boolean }): Promise<MigrationReport>
}

interface MigrationReport {
  scriptName: string
  startedAt: string
  completedAt: string
  processed: number
  skipped: number    // already existed (idempotency)
  failed: number
  errors: Array<{ id: string; reason: string }>
}
```

Run all migration scripts in sequence:
```bash
pnpm migration:run --env staging --dry-run      # Preview what would happen
pnpm migration:run --env staging                # Execute against staging
pnpm migration:verify --env staging             # Run ranking comparison report
```

---

## SECTION 10 — CI/CD STRUCTURE (`.github/workflows`)

### Workflow Overview

```
ci.yml               → Triggered on every push and pull request
deploy-dev.yml       → Triggered on merge to main branch
deploy-staging.yml   → Triggered manually (workflow_dispatch)
deploy-production.yml → Triggered manually with required approvals
```

### `ci.yml` — Pull Request Validation

```yaml
# Runs on: push to any branch, pull_request to main
jobs:
  lint:
    - pnpm install (cached)
    - turbo lint              # ESLint across all packages

  typecheck:
    - turbo typecheck         # tsc --noEmit across all packages

  unit-test:
    - turbo test              # Vitest unit tests across all packages
    - Upload coverage to Codecov

  scoring-verify:
    - pnpm --filter @sr/scoring test        # 100% coverage required
    - pnpm --filter @sr/scoring verify      # Verify against WP snapshot (Phase 1+)

  integration-test:
    - pnpm --filter @sr/api test:integration  # miniflare + test D1

  build:
    - turbo build             # Verify all apps build successfully
```

All jobs must pass before a PR can be merged. `main` branch is protected.

### `deploy-dev.yml` — Automatic Dev Deployment

```yaml
# Runs on: push to main (after PR merge)
jobs:
  deploy-api:
    - wrangler deploy --env dev
    - wrangler d1 migrations apply sportsrush-dev --env dev

  deploy-web:
    - pnpm --filter @sr/web build
    - Deploy to Cloudflare Pages (dev environment)

  deploy-admin:
    - pnpm --filter @sr/admin build
    - Deploy to Cloudflare Pages (dev environment)

  smoke-test:
    - Run contract tests against dev environment
    - Notify team on failure (Slack)
```

### `deploy-staging.yml` — Manual Staging Promotion

```yaml
# Runs on: workflow_dispatch (manual trigger in GitHub UI)
# Requires: no open CI failures on main

jobs:
  deploy:
    - Same as deploy-dev but with --env staging
    - Run full E2E test suite (Playwright against staging)
    - Report results to PR/Slack
    - On E2E failure: notify team, do NOT auto-rollback (staging is not production)
```

### `deploy-production.yml` — Manual Production Deployment

```yaml
# Runs on: workflow_dispatch
# Requires: environment protection rule with 2 required approvers

jobs:
  pre-flight:
    - Assert staging E2E passed in last 24 hours (check workflow run history)
    - Assert no open severity-high issues in the repo

  backup:
    - Export D1 production database via wrangler d1 export
    - Upload backup to R2 with timestamp

  deploy-api:
    - wrangler deploy --env production
    - wrangler d1 migrations apply sportsrush-prod --env production

  deploy-web:
    - Deploy to Cloudflare Pages (production)

  smoke-test:
    - Run production smoke tests (3 critical paths only — fast)
    - On smoke test failure: ROLLBACK immediately (see below)

  notify:
    - Post deployment summary to Slack
```

### Rollback Support

```yaml
rollback-production.yml:
  # Triggered manually; requires 1 approver
  jobs:
    rollback-api:
      - wrangler rollback --env production  # Cloudflare supports instant rollback to previous Worker version
    rollback-web:
      - Redeploy previous Pages deployment via Cloudflare dashboard (API call)
    notify:
      - Post rollback notice to Slack
```

D1 schema rollbacks (if a migration must be undone) are handled by applying the `.down.sql` file manually via `wrangler d1 execute`. This is a documented step in the production runbook.

---

## SECTION 11 — AI WORKFLOW OPTIMISATION

### Rationale
AI-assisted development works best when the codebase is designed to give the AI maximum relevant context and minimum noise. The following conventions are specifically chosen to reduce hallucinations, reduce repetitive context-setting, and maximise the accuracy of AI-generated code.

### File Sizing

**Target: ≤ 200 lines per file.** Files over 300 lines should be split.

Reasons:
- A focused file fits entirely in the AI's context window with room for the prompt and response
- A 500-line file forces the AI to guess about the parts it cannot see
- Smaller files have single responsibilities — the AI knows exactly what to change

**Split triggers:**
- `service.ts` > 200 lines → extract sub-services (`auth/login.service.ts`, `auth/register.service.ts`)
- `routes.ts` > 150 lines → split by route group (`auth/login.routes.ts`, `auth/register.routes.ts`)
- `repository.ts` > 200 lines → split by entity or operation type

### One Concern Per File

Each file does one thing. Name it after what it does, not what module it belongs to.

```
Good:  calculate-match-score.ts   (does one thing)
Bad:   utils.ts                   (does many things)

Good:  save-prediction.service.ts (one operation)
Bad:   prediction-service.ts      (many operations, ambiguous to AI)
```

### Naming Conventions

All names are descriptive and explicit. No abbreviations.

```
Files:      kebab-case.ts           (calculate-match-score.ts)
Types:      PascalCase              (MatchScore, ScoringConfig)
Functions:  camelCase, verb-first   (calculateMatchScore, savePrediction, getRankings)
Constants:  UPPER_SNAKE_CASE        (DEFAULT_LOCK_MINUTES, JOKER_MULTIPLIER)
Variables:  camelCase, descriptive  (predictionLockedAt, not lockedAt or t)
DB columns: snake_case              (play_date, home_score, competition_id)
Queue names: kebab-case             (sr-scoring-prod, sr-notifications-dev)
```

### JSDoc on All Exported Functions

Every exported function has a JSDoc comment describing:
1. What it does (one sentence)
2. Its preconditions (what must be true before calling it)
3. What it returns
4. Side effects (events emitted, D1 writes)

```typescript
/**
 * Calculates the points a user earns for a single match prediction.
 *
 * Preconditions: result.home and result.away must not be null.
 * Returns: A MatchScore object with the breakdown of points earned.
 * Side effects: None. Pure function.
 */
export function calculateMatchScore(
  result: MatchResult,
  prediction: PredictionInput,
  config: ScoringConfig
): MatchScore { ... }
```

The JSDoc becomes the AI's primary context when it cannot see the function's implementation. Accurate JSDoc = accurate AI completions.

### Module READMEs

Every module under `apps/api/src/modules/` has a `README.md` with:
- What this module does (2–3 sentences)
- The domain rules it enforces
- External dependencies (which other modules it calls)
- Events it emits
- Things it explicitly does NOT do (prevents scope creep in AI suggestions)

```
apps/api/src/modules/predictions/README.md
```

```markdown
## Predictions Module

Accepts and stores user predictions for matches. Enforces the server-side prediction lock rule.

**Domain rules enforced:**
- A prediction is rejected if submitted within `scoring_config.prediction_lock_minutes` of kick-off.
- The user_id is always taken from the authenticated JWT, never from the request body.
- Predictions for completed, void, or abandoned matches are rejected.

**Calls:** fixtures module (to read match.play_date), scoring module (emits event, does not call directly)
**Emits:** prediction.saved (to QUEUE_SCORING)
**Does NOT:** calculate points (that is the Scoring module), display rankings (that is the Rankings module)
```

### Domain Boundaries Reduce Hallucinations

The most common AI error in a multi-domain codebase is crossing a domain boundary incorrectly — e.g. generating code that calls `getRankings()` from inside the `predictions` service, or writing to `match_scores` from the `rankings` service.

Defences:
1. Module READMEs document what each module does NOT do
2. TypeScript module boundaries (repositories return types; only the owning service writes to those types)
3. No barrel `index.ts` files — the AI must explicitly import from the file it needs, making cross-domain imports visible
4. Repository functions are named after the table they write to: `match_scores.repository.ts` — it is immediately obvious to the AI that writing to `match_scores` is this file's job only

### AI Prompt Templates (in `docs/ai/`)

Standardised prompts for common development tasks. These are project-level templates that give the AI the right context immediately.

```
docs/ai/
├── new-route.md          # Prompt for adding a new API route to an existing module
├── new-module.md         # Prompt for adding a new bounded context module
├── new-migration.md      # Prompt for writing a new D1 migration
├── new-component.md      # Prompt for adding a new shared UI component
├── fix-scoring-bug.md    # Prompt for investigating and fixing a scoring discrepancy
└── add-test.md           # Prompt for adding unit or integration tests
```

Each template includes:
- The files the AI should read first (context)
- The constraints it must respect (domain rules, naming conventions)
- The files it should produce or edit
- The tests it must write

### Type-Driven Development

Types come first, implementation second. When adding a new feature:
1. Add the type to `@sr/types` first
2. Add the Zod schema to `@sr/validation`
3. Add the event type to `@sr/events` (if the feature emits events)
4. Then implement routes, service, repository

This order means the AI always has a typed contract to code against, rather than inferring types from implementation.

### Avoid These Patterns (AI Confusion Triggers)

| Pattern | Problem | Alternative |
|---|---|---|
| `any` type | AI cannot infer intent | Always use explicit types |
| `utils.ts` with mixed functions | AI cannot determine scope | One file per utility |
| Barrel `index.ts` re-exports | Hides import origin | Import directly from source file |
| Magic numbers inline | AI cannot determine meaning | Named constants in `constants.ts` |
| Long promise chains without types | AI loses type context | Extract to typed intermediate variables |
| Implicit `undefined` checks | Ambiguous nullability | Explicit `null` with `T \| null` |
| Mixed business logic and DB queries | AI cannot separate concerns | Strict service/repository split |

---

## QUICK REFERENCE — KEY FILE LOCATIONS

| What you're looking for | Where to find it |
|---|---|
| Canonical scoring formula | `packages/scoring/src/calculate-match-score.ts` |
| All domain entity types | `packages/types/src/*.ts` |
| Zod validation schemas | `packages/validation/src/*.ts` |
| Queue event payloads | `packages/events/src/*.ts` |
| API route handlers | `apps/api/src/modules/{module}/routes.ts` |
| Business rules enforcement | `apps/api/src/modules/{module}/service.ts` |
| D1 queries | `apps/api/src/modules/{module}/repository.ts` |
| Queue consumers | `apps/api/src/queue-consumers/` |
| Scheduled jobs | `apps/api/src/scheduled/` |
| Cloudflare bindings type | `apps/api/src/lib/env.ts` |
| Database migrations | `infrastructure/migrations/` |
| Migration ETL scripts | `infrastructure/scripts/migration/` |
| E2E tests | `testing/e2e/` |
| Scoring unit tests | `packages/scoring/src/__tests__/` |
| Architecture documents | `docs/architecture/` |
| Owner Decisions resolved | `docs/owner-decisions/` |
| Operational runbooks | `docs/runbooks/` |

---

*Repository structure version 1.0. Update the Quick Reference table when adding new top-level directories or packages. Module READMEs are required before a module is considered complete.*
