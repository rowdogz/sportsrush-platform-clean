# PHASE_1_FOUNDATION_IMPLEMENTATION_PLAN.md

## SportsRush 2.0 — Phase 1: Foundation Implementation Plan

**Scope:** Monorepo, tooling, environments, CI/CD, shared TypeScript packages, API shell, auth shell, database migration framework, logging/monitoring, test framework, local development workflow  
**Out of scope:** Predictions, rankings, fixtures, scoring logic, mobile apps, frontend pages, payments  
**Stack:** pnpm + Turborepo · Hono · Cloudflare Workers · D1 · Next.js · TypeScript · Vitest · Miniflare · GitHub Actions  
**References:** `SPORTSRUSH_2_REPOSITORY_STRUCTURE.md`, `SPORTSRUSH_2_IMPLEMENTATION_SEQUENCE.md`

---

## READING THIS DOCUMENT

Each task has:

- **Deliverable** — the exact file(s) or configuration produced
- **Location** — path in the repository
- **Dependencies** — tasks that must be complete first
- **Acceptance criteria** — concrete pass/fail checks
- **Tests required** — what automated tests must exist

Tasks are grouped into pull requests. PRs are ordered — each PR can be opened once the previous merges. Within a PR, tasks can be implemented in parallel by an AI assistant.

---

## PHASE 1 TASK MAP

```
PR-01  Monorepo skeleton
PR-02  Shared tooling packages
PR-03  Core shared packages (types, validation, events)
PR-04  API application shell
PR-05  Database migration framework
PR-06  Auth module
PR-07  Test framework and integration test harness
PR-08  CI/CD pipelines
PR-09  Local development workflow
```

Dependency chain:

```
PR-01 → PR-02 → PR-03 → PR-04 → PR-05 → PR-06 → PR-07
                                                        ↓
                                            PR-08 (uses all of above)
PR-09 (can proceed after PR-01, documents all of above)
```

---

## PR-01 — Monorepo Skeleton

### Objective

Create the repository structure, package manager configuration, and build orchestration. Everything in subsequent PRs installs into this skeleton.

---

### TASK 1.1 — Repository initialisation

**Deliverable:**

```
sportsrush/
├── .gitignore
├── .nvmrc                        # Node version pin
├── package.json                  # Root package (scripts only)
├── pnpm-workspace.yaml           # Workspace glob patterns
├── turbo.json                    # Turborepo pipeline
└── README.md                     # Single paragraph — what this repo is
```

**Location:** Repository root

**Dependencies:** None

**`pnpm-workspace.yaml`:**

```yaml
packages:
  - "apps/*"
  - "packages/*"
  - "tooling/*"
  - "testing/*"
```

**`turbo.json`:**

```json
{
  "$schema": "https://turborepo.org/schema.json",
  "pipeline": {
    "build": {
      "dependsOn": ["^build"],
      "outputs": [".next/**", "dist/**", ".wrangler/**"]
    },
    "dev": {
      "cache": false,
      "persistent": true
    },
    "test": {
      "dependsOn": ["^build"],
      "outputs": ["coverage/**"]
    },
    "lint": {
      "outputs": []
    },
    "typecheck": {
      "dependsOn": ["^build"],
      "outputs": []
    }
  }
}
```

**Root `package.json`:**

```json
{
  "name": "sportsrush",
  "private": true,
  "scripts": {
    "dev": "turbo dev",
    "build": "turbo build",
    "test": "turbo test",
    "test:e2e": "pnpm --filter @sr/e2e test",
    "lint": "turbo lint",
    "typecheck": "turbo typecheck",
    "format": "prettier --write \"**/*.{ts,tsx,json,md}\"",
    "format:check": "prettier --check \"**/*.{ts,tsx,json,md}\""
  },
  "devDependencies": {
    "turbo": "^2.0.0",
    "prettier": "^3.0.0"
  },
  "engines": {
    "node": ">=20.0.0",
    "pnpm": ">=9.0.0"
  },
  "packageManager": "pnpm@9.0.0"
}
```

**`.nvmrc`:** `20`

**`.gitignore` must include:**

```
node_modules/
.wrangler/
dist/
.next/
.turbo/
*.env
*.dev.vars
coverage/
.DS_Store
```

**Acceptance criteria:**

- [ ] `pnpm install` succeeds from the root with no errors
- [ ] `turbo build` exits with "no packages to build" (no apps yet) — no errors
- [ ] `.nvmrc` pins Node 20
- [ ] No `node_modules` is committed
- [ ] `*.dev.vars` and `*.env` are gitignored

**Tests required:** None (structural)

---

### TASK 1.2 — Directory scaffolding

**Deliverable:**

```
apps/
├── api/.gitkeep
├── web/.gitkeep
├── admin/.gitkeep
└── mobile/.gitkeep
packages/
├── types/.gitkeep
├── scoring/.gitkeep
├── ui/.gitkeep
├── api-client/.gitkeep
├── auth/.gitkeep
├── validation/.gitkeep
└── events/.gitkeep
tooling/
├── eslint-config/.gitkeep
├── typescript-config/.gitkeep
└── prettier-config/.gitkeep
testing/
├── e2e/.gitkeep
├── fixtures/.gitkeep
└── helpers/.gitkeep
infrastructure/
├── migrations/.gitkeep
└── scripts/
    └── migration/.gitkeep
docs/
├── architecture/.gitkeep
├── runbooks/.gitkeep
├── decisions/.gitkeep
└── ai/.gitkeep
.github/
└── workflows/.gitkeep
```

**Location:** Repository root

**Dependencies:** Task 1.1

**Acceptance criteria:**

- [ ] All directories exist and are tracked by git via `.gitkeep` files
- [ ] No application code exists yet — directories only

**Tests required:** None

---

### TASK 1.3 — Move architecture documents

**Deliverable:** All existing `SPORTSRUSH_2_*.md` and related planning documents moved to `docs/architecture/`

**Location:** `docs/architecture/`

**Files to move:**

```
SPORTSRUSH_2_REPOSITORY_STRUCTURE.md
SPORTSRUSH_2_DOMAIN_MODEL.md
SPORTSRUSH_2_TARGET_ARCHITECTURE.md
SPORTSRUSH_2_IMPLEMENTATION_SEQUENCE.md
SPORTSRUSH_2_CANONICAL_RULES.md
PHASE_1_FOUNDATION_IMPLEMENTATION_PLAN.md (this file)
VERIFIED_FINDINGS.md
HIGH_RISK_AREAS.md
CURRENT_SYSTEM_OVERVIEW.md
DATABASE_AND_DATA_FLOW.md
SCORING_AND_RANKINGS_ENGINE.md
FIXTURE_AND_RESULTS_PIPELINE.md
PRIVATE_LEAGUES_AND_PAYMENTS.md
TECHNICAL_DEBT_AND_LIMITATIONS.md
```

**Dependencies:** Task 1.2

**Acceptance criteria:**

- [ ] All planning documents live under `docs/architecture/`
- [ ] Repository root contains only `README.md`, config files, and workspace directories

**Tests required:** None

---

## PR-02 — Shared Tooling Packages

### Objective

Create the three tooling packages that all other packages inherit configuration from. These are not application code — they are configuration packages that ensure consistent linting, formatting, and TypeScript settings across the entire monorepo.

---

### TASK 2.1 — `tooling/typescript-config`

**Deliverable:**

```
tooling/typescript-config/
├── package.json
├── base.json          # Strict base config for all packages
├── nextjs.json        # Extends base, adds Next.js specifics
└── workers.json       # Extends base, adds Workers specifics (no DOM)
```

**Location:** `tooling/typescript-config/`

**Package name:** `@sr/typescript-config`

**`package.json`:**

```json
{
  "name": "@sr/typescript-config",
  "version": "0.0.1",
  "private": true,
  "files": ["*.json"],
  "license": "UNLICENSED"
}
```

**`base.json`:**

```json
{
  "$schema": "https://json.schemastore.org/tsconfig",
  "compilerOptions": {
    "strict": true,
    "exactOptionalPropertyTypes": true,
    "noUncheckedIndexedAccess": true,
    "noImplicitReturns": true,
    "noFallthroughCasesInSwitch": true,
    "moduleResolution": "bundler",
    "module": "ESNext",
    "target": "ES2022",
    "skipLibCheck": true,
    "isolatedModules": true,
    "esModuleInterop": true,
    "resolveJsonModule": true
  }
}
```

**`workers.json`:**

```json
{
  "$schema": "https://json.schemastore.org/tsconfig",
  "extends": "./base.json",
  "compilerOptions": {
    "lib": ["ES2022"],
    "types": ["@cloudflare/workers-types"]
  }
}
```

**`nextjs.json`:**

```json
{
  "$schema": "https://json.schemastore.org/tsconfig",
  "extends": "./base.json",
  "compilerOptions": {
    "lib": ["ES2022", "DOM", "DOM.Iterable"],
    "jsx": "preserve",
    "plugins": [{ "name": "next" }]
  }
}
```

**Dependencies:** Task 1.1

**Acceptance criteria:**

- [ ] Package installs as a workspace dependency: `pnpm add @sr/typescript-config --filter @sr/api -D`
- [ ] `base.json` has `"strict": true` and `"exactOptionalPropertyTypes": true`
- [ ] `workers.json` does not include `"DOM"` in `lib` (Workers have no DOM)

**Tests required:** None (configuration only)

---

### TASK 2.2 — `tooling/eslint-config`

**Deliverable:**

```
tooling/eslint-config/
├── package.json
├── index.js           # Base config (all packages)
└── next.js            # Extends base, adds Next.js rules
```

**Location:** `tooling/eslint-config/`

**Package name:** `@sr/eslint-config`

**`package.json`:**

```json
{
  "name": "@sr/eslint-config",
  "version": "0.0.1",
  "private": true,
  "main": "index.js",
  "dependencies": {
    "@typescript-eslint/eslint-plugin": "^7.0.0",
    "@typescript-eslint/parser": "^7.0.0",
    "eslint-plugin-import": "^2.29.0"
  }
}
```

**`index.js` key rules:**

- `@typescript-eslint/no-explicit-any: error` — no `any` types
- `@typescript-eslint/no-unused-vars: error` — no unused variables
- `import/no-cycle: error` — no circular dependencies between packages
- `no-console: warn` — console.log is allowed in Workers (it is the logging mechanism) but warned elsewhere

**Dependencies:** Task 1.1

**Acceptance criteria:**

- [ ] `no-explicit-any` is set to `error`
- [ ] `no-cycle` is enabled (prevents accidental circular package dependencies)
- [ ] Running `eslint .` on an empty package produces no errors

**Tests required:** None

---

### TASK 2.3 — `tooling/prettier-config`

**Deliverable:**

```
tooling/prettier-config/
├── package.json
└── index.json
```

**`index.json`:**

```json
{
  "semi": false,
  "singleQuote": true,
  "tabWidth": 2,
  "trailingComma": "all",
  "printWidth": 100,
  "bracketSpacing": true
}
```

Add to root `package.json`:

```json
{
  "prettier": "@sr/prettier-config"
}
```

**Acceptance criteria:**

- [ ] Running `pnpm format:check` on a correctly-formatted file passes
- [ ] `semi: false` is set (no semicolons — consistent with modern TypeScript style)

**Tests required:** None

---

## PR-03 — Core Shared Packages

### Objective

Create the three packages that form the type system and contracts for the entire platform. These must be finalised (or at least stable in shape) before any application code is written.

---

### TASK 3.1 — `packages/types`

**Deliverable:**

```
packages/types/
├── package.json
├── tsconfig.json
└── src/
    ├── index.ts          # Re-exports everything
    ├── common.ts         # UUID, Timestamp, PaginatedResponse, ApiError
    ├── auth.ts           # User, Session, Role, TokenPayload
    ├── competition.ts    # Competition, Sport (enum)
    ├── fixture.ts        # Match, Team, TeamAlias, MatchStatus (enum)
    ├── prediction.ts     # Prediction, PredictionInput
    ├── scoring.ts        # MatchScore, ScoringConfig, ScoringConfigVersion
    ├── ranking.ts        # RankingRow, RankingSnapshot, MonthlyWinner
    ├── league.ts         # League, LeagueMember, LeagueInvite, LeagueStatus
    ├── payment.ts        # PaymentEvent, PaymentStatus
    ├── notification.ts   # NotificationLog, NotificationType, Channel
    └── scraper.ts        # ScraperResult, ScraperRun, UnresolvedAlias
```

**Location:** `packages/types/`

**Package name:** `@sr/types`

**`package.json`:**

```json
{
  "name": "@sr/types",
  "version": "0.0.1",
  "private": true,
  "main": "./src/index.ts",
  "types": "./src/index.ts",
  "scripts": {
    "typecheck": "tsc --noEmit",
    "lint": "eslint src"
  },
  "devDependencies": {
    "@sr/eslint-config": "workspace:*",
    "@sr/typescript-config": "workspace:*",
    "typescript": "^5.4.0"
  }
}
```

**`tsconfig.json`:**

```json
{
  "extends": "@sr/typescript-config/base.json",
  "include": ["src"],
  "compilerOptions": {
    "noEmit": true
  }
}
```

**Key types to define in Phase 1** (others can be stubs):

```typescript
// src/common.ts
export type UUID = string & { readonly _brand: "UUID" };
export type Timestamp = string & { readonly _brand: "Timestamp" }; // ISO 8601 UTC

export type PaginatedResponse<T> = {
  readonly data: readonly T[];
  readonly pagination: {
    readonly page: number;
    readonly limit: number;
    readonly total: number;
    readonly totalPages: number;
  };
};

export type ApiError = {
  readonly code: string; // machine-readable, e.g. 'PREDICTION_LOCKED'
  readonly message: string; // human-readable
  readonly statusCode: number;
  readonly details?: unknown;
};
```

```typescript
// src/auth.ts
export type Role = "user" | "admin" | "superadmin";

export type User = {
  readonly id: UUID;
  readonly email: string;
  readonly emailVerifiedAt: Timestamp | null;
  readonly role: Role;
  readonly createdAt: Timestamp;
  readonly legacyWpUserId: number | null; // nullable; set only for migrated accounts
};

export type Session = {
  readonly id: UUID;
  readonly userId: UUID;
  readonly refreshTokenHash: string;
  readonly createdAt: Timestamp;
  readonly expiresAt: Timestamp;
  readonly revokedAt: Timestamp | null;
  readonly userAgent: string | null;
  readonly ipAddress: string | null;
};

export type TokenPayload = {
  readonly userId: UUID;
  readonly role: Role;
  readonly sessionId: UUID;
  readonly exp: number; // Unix timestamp
};
```

```typescript
// src/scoring.ts
export type ScoringConfig = {
  readonly id: UUID;
  readonly version: number;
  readonly exactPoints: number;
  readonly totoPoints: number;
  readonly homeBonusPoints: number;
  readonly awayBonusPoints: number;
  readonly diffBonusPoints: number;
  readonly diffBonusMode: "toto_only" | "always" | "never";
  readonly jokerEnabled: boolean;
  readonly jokerMultiplier: number;
  readonly predictionLockMinutes: number;
  readonly validFrom: Timestamp;
  readonly createdBy: UUID;
};

export type MatchScore = {
  readonly userId: UUID;
  readonly matchId: UUID;
  readonly pointsExact: number;
  readonly pointsToto: number;
  readonly pointsHomeBonus: number;
  readonly pointsAwayBonus: number;
  readonly pointsDiffBonus: number;
  readonly pointsJokerMultiplier: number;
  readonly total: number;
  readonly scoringConfigVersion: number;
};
```

**Acceptance criteria:**

- [ ] `pnpm typecheck` passes with zero errors
- [ ] All types use `readonly` on every property
- [ ] No `any` type exists in any file
- [ ] `UUID` and `Timestamp` are branded types (cannot be accidentally swapped)
- [ ] `src/index.ts` exports everything from all domain files
- [ ] Package has zero runtime code — `tsc` output would be empty

**Tests required:**

```
typecheck passes with strict mode (CI gate)
```

No unit tests needed — types have no runtime behaviour.

---

### TASK 3.2 — `packages/validation`

**Deliverable:**

```
packages/validation/
├── package.json
├── tsconfig.json
└── src/
    ├── index.ts
    ├── common.ts         # UUIDSchema, PaginationSchema, TimestampSchema
    ├── auth.ts           # RegisterSchema, LoginSchema, PasswordResetRequestSchema,
    │                     # PasswordResetConfirmSchema, RefreshTokenSchema
    └── (other schemas)   # Stubs for future domains — empty files with TODO comments
```

**Location:** `packages/validation/`

**Package name:** `@sr/validation`

**Key schemas for Phase 1:**

```typescript
// src/auth.ts
import { z } from "zod";

export const RegisterSchema = z.object({
  email: z.string().email().max(254),
  password: z.string().min(8).max(72), // 72 is bcrypt/Argon2 practical limit
  displayName: z.string().min(2).max(50).trim(),
});

export const LoginSchema = z.object({
  email: z.string().email(),
  password: z.string().min(1),
});

export const PasswordResetRequestSchema = z.object({
  email: z.string().email(),
});

export const PasswordResetConfirmSchema = z.object({
  token: z.string().min(1),
  newPassword: z.string().min(8).max(72),
});
```

**Dependencies:** Task 3.1 (uses `@sr/types` for inferred types via `z.infer`)

**Acceptance criteria:**

- [ ] `pnpm typecheck` passes
- [ ] `RegisterSchema.parse({ email: 'bad', password: '123', displayName: 'a' })` throws a ZodError
- [ ] `RegisterSchema.parse({ email: 'test@example.com', password: 'password123', displayName: 'TestUser' })` succeeds
- [ ] All schemas have `.max()` limits on all string fields (prevents oversized payloads)
- [ ] Email fields use `.email()` validation

**Tests required:**

```
test: RegisterSchema rejects email without @
test: RegisterSchema rejects password shorter than 8 chars
test: RegisterSchema rejects displayName longer than 50 chars
test: LoginSchema accepts valid credentials shape
test: PasswordResetConfirmSchema requires token field
```

---

### TASK 3.3 — `packages/events`

**Deliverable:**

```
packages/events/
├── package.json
├── tsconfig.json
└── src/
    ├── index.ts
    ├── base.ts           # BaseEvent type with eventType, eventId, occurredAt
    ├── result-events.ts  # ResultPublishedEvent, ResultCorrectedEvent
    ├── scoring-events.ts # ScoresRecalculatedEvent
    ├── ranking-events.ts # RankingsUpdatedEvent, MonthlyWinnerDeclaredEvent
    ├── prediction-events.ts  # PredictionSavedEvent
    ├── league-events.ts      # LeagueMemberJoinedEvent
    ├── payment-events.ts     # PaymentCompletedEvent, PaymentRefundedEvent
    └── scraper-events.ts     # ScraperRunCompletedEvent, AliasUnresolvedEvent
```

**`src/base.ts`:**

```typescript
import type { UUID, Timestamp } from "@sr/types";

export type BaseEvent = {
  readonly eventId: UUID; // unique per event instance; used for idempotency
  readonly occurredAt: Timestamp;
};
```

**`src/result-events.ts`:**

```typescript
import type { UUID, Timestamp } from "@sr/types";
import type { BaseEvent } from "./base";

export type ResultPublishedEvent = BaseEvent & {
  readonly eventType: "result.published";
  readonly matchId: UUID;
  readonly competitionId: UUID;
  readonly homeScore: number;
  readonly awayScore: number;
  readonly round: number;
};

export type ResultCorrectedEvent = BaseEvent & {
  readonly eventType: "result.corrected";
  readonly matchId: UUID;
  readonly competitionId: UUID;
  readonly previousHome: number;
  readonly previousAway: number;
  readonly correctedHome: number;
  readonly correctedAway: number;
  readonly correctedBy: UUID;
};
```

**Acceptance criteria:**

- [ ] Every event type extends `BaseEvent` (has `eventId` and `occurredAt`)
- [ ] Every event type has a string literal `eventType` field
- [ ] `pnpm typecheck` passes
- [ ] No event type contains optional fields that should be required (all fields are `readonly` and non-optional)

**Tests required:** Typecheck only.

---

### TASK 3.4 — `packages/auth`

**Deliverable:**

```
packages/auth/
├── package.json
├── tsconfig.json
└── src/
    ├── index.ts
    ├── jwt.ts            # verifyAccessToken(), createAccessToken()
    ├── roles.ts          # hasRole(), assertRole()
    ├── session.ts        # TokenPayload type re-export
    ├── hash.ts           # hashPassword(), verifyPassword() (Argon2id via crypto)
    └── constants.ts      # ACCESS_TOKEN_EXPIRY_SECONDS, REFRESH_TOKEN_EXPIRY_DAYS, etc.
```

**Location:** `packages/auth/`

**Important:** Cloudflare Workers has no Node.js `crypto` module. Use the **Web Crypto API** (`crypto.subtle`) which is available in all Workers. Do NOT use `bcrypt` or `argon2` npm packages — they require Node.js native modules which are not available in Workers.

Password hashing strategy for Workers:

```typescript
// Use PBKDF2 via Web Crypto API (available in all Workers environments)
// Or use a pure-JS Argon2 WASM implementation that runs in Workers
// Recommendation: start with PBKDF2 (Web Crypto, no deps), migrate to Argon2 WASM later
```

**`src/constants.ts`:**

```typescript
export const ACCESS_TOKEN_EXPIRY_SECONDS = 15 * 60; // 15 minutes
export const REFRESH_TOKEN_EXPIRY_DAYS = 30;
export const RESET_TOKEN_EXPIRY_MINUTES = 15;
export const MAX_LOGIN_ATTEMPTS = 5;
export const LOCKOUT_DURATION_MINUTES = 15;
```

**`src/jwt.ts`:**

```typescript
// Uses crypto.subtle.sign / verify (Web Crypto API, available in Workers)
// HS256 JWT — implemented without external library to avoid Node.js dependency
// OR use 'jose' package (pure ESM, Workers-compatible)
```

Recommended: use the `jose` library (pure ESM, Cloudflare Workers compatible, no native deps).

**`src/roles.ts`:**

```typescript
import type { Role, TokenPayload } from "@sr/types";

export function hasRole(payload: TokenPayload, role: Role): boolean {
  if (role === "superadmin") return payload.role === "superadmin";
  if (role === "admin")
    return payload.role === "admin" || payload.role === "superadmin";
  return true;
}

export class InsufficientRoleError extends Error {
  readonly statusCode = 403;
  constructor(required: Role, actual: Role) {
    super(`Required role '${required}', got '${actual}'`);
  }
}

export function assertRole(payload: TokenPayload, required: Role): void {
  if (!hasRole(payload, required)) {
    throw new InsufficientRoleError(required, payload.role);
  }
}
```

**Dependencies:** Task 3.1

**Acceptance criteria:**

- [ ] `createAccessToken()` produces a verifiable JWT
- [ ] `verifyAccessToken()` returns `null` for expired tokens, `null` for tampered tokens
- [ ] `assertRole(adminPayload, 'superadmin')` throws `InsufficientRoleError`
- [ ] `assertRole(superadminPayload, 'admin')` does NOT throw (superadmin satisfies admin check)
- [ ] `hashPassword()` and `verifyPassword()` are Web Crypto API compatible (no Node.js native modules)
- [ ] Package builds without errors in a Workers-compatible environment

**Tests required:**

```
test: createAccessToken returns a three-part JWT string
test: verifyAccessToken returns TokenPayload for valid token
test: verifyAccessToken returns null for expired token
test: verifyAccessToken returns null for token with tampered payload
test: verifyAccessToken returns null for token with wrong secret
test: hasRole superadmin satisfies admin check
test: hasRole admin does not satisfy superadmin check
test: assertRole throws InsufficientRoleError for insufficient role
test: hashPassword produces different hashes for same input (salt randomness)
test: verifyPassword returns true for correct password
test: verifyPassword returns false for wrong password
test: verifyPassword is constant-time (timing-safe comparison)
```

---

## PR-04 — API Application Shell

### Objective

Create the Hono application structure, middleware stack, and environment bindings. No business logic yet — just the skeleton that all future routes plug into.

---

### TASK 4.1 — API package initialisation

**Deliverable:**

```
apps/api/
├── package.json
├── tsconfig.json
├── wrangler.toml
├── vitest.config.ts
└── src/
    ├── index.ts          # Worker entry point
    ├── app.ts            # Hono app factory
    └── lib/
        └── env.ts        # Cloudflare bindings type definition
```

**Location:** `apps/api/`

**Package name:** `@sr/api`

**`package.json`:**

```json
{
  "name": "@sr/api",
  "version": "0.0.1",
  "private": true,
  "scripts": {
    "dev": "wrangler dev --local",
    "build": "wrangler deploy --dry-run",
    "deploy:dev": "wrangler deploy --env dev",
    "deploy:staging": "wrangler deploy --env staging",
    "deploy:production": "wrangler deploy --env production",
    "test": "vitest run",
    "test:watch": "vitest",
    "test:integration": "vitest run --config vitest.integration.config.ts",
    "typecheck": "tsc --noEmit",
    "lint": "eslint src",
    "migrate:local": "wrangler d1 migrations apply sportsrush --local",
    "migrate:dev": "wrangler d1 migrations apply sportsrush-dev --env dev",
    "migrate:staging": "wrangler d1 migrations apply sportsrush-staging --env staging"
  },
  "dependencies": {
    "hono": "^4.0.0",
    "jose": "^5.0.0",
    "@sr/types": "workspace:*",
    "@sr/auth": "workspace:*",
    "@sr/validation": "workspace:*",
    "@sr/events": "workspace:*"
  },
  "devDependencies": {
    "@cloudflare/workers-types": "^4.0.0",
    "@sr/eslint-config": "workspace:*",
    "@sr/typescript-config": "workspace:*",
    "wrangler": "^3.0.0",
    "vitest": "^1.0.0",
    "@cloudflare/vitest-pool-workers": "^0.1.0",
    "typescript": "^5.4.0"
  }
}
```

---

### TASK 4.2 — Cloudflare bindings type

**Deliverable:** `apps/api/src/lib/env.ts`

```typescript
// apps/api/src/lib/env.ts
// The single source of truth for all Cloudflare Worker bindings.
// Update this file whenever a new binding is added in wrangler.toml.

export type Env = {
  Bindings: {
    // Database
    DB: D1Database;

    // Cache
    CACHE: KVNamespace;

    // Queues (producers)
    QUEUE_SCORING: Queue;
    QUEUE_NOTIFICATIONS: Queue;
    QUEUE_SCRAPER: Queue;

    // Object storage
    ASSETS: R2Bucket;

    // Secrets (set via `wrangler secret put`)
    JWT_SECRET: string;
    STRIPE_SECRET_KEY: string;
    STRIPE_WEBHOOK_SECRET: string;
    SPORTS_API_KEY: string;

    // Environment identifier
    ENVIRONMENT: "local" | "dev" | "staging" | "production";
  };
  Variables: {
    // Set by requireAuth middleware on authenticated routes
    userId: string;
    userRole: "user" | "admin" | "superadmin";
    sessionId: string;
  };
};
```

**Acceptance criteria:**

- [ ] `Env` type compiles without error when `@cloudflare/workers-types` is installed
- [ ] `Bindings` and `Variables` are separate (Hono convention)
- [ ] All secrets are listed as `string` (not `string | undefined`) — they are required

---

### TASK 4.3 — Hono app factory

**Deliverable:** `apps/api/src/app.ts`

```typescript
// apps/api/src/app.ts
// Creates and configures the Hono application.
// Routes are registered here. Middleware is applied here.
// This file is imported by both the Worker entry point (index.ts)
// and by integration tests (which create the app with a test DB).

import { Hono } from "hono";
import { cors } from "hono/cors";
import type { Env } from "./lib/env";
import { errorHandler } from "./middleware/error-handler";
import { requestLogger } from "./middleware/request-logger";

// Route modules will be imported and registered here as they are built.
// import { authRoutes } from './modules/auth/routes'

export function createApp(): Hono<Env> {
  const app = new Hono<Env>();

  // --- Global middleware (order matters) ---
  app.use("*", requestLogger());
  app.use(
    "*",
    cors({
      origin: (_origin, c) => {
        const env = c.env.ENVIRONMENT;
        if (env === "production") return "https://sportsrush.co.uk";
        if (env === "staging") return "https://staging.sportsrush.co.uk";
        return "*"; // local and dev: allow all origins
      },
      allowHeaders: ["Authorization", "Content-Type", "X-CSRF-Token"],
      allowMethods: ["GET", "POST", "PATCH", "DELETE", "OPTIONS"],
      credentials: true,
    }),
  );

  // --- Health check (unauthenticated) ---
  app.get("/health", (c) =>
    c.json({ status: "ok", environment: c.env.ENVIRONMENT }),
  );

  // --- Route modules ---
  // app.route('/auth', authRoutes)
  // app.route('/competitions', competitionRoutes)
  // ... added in future PRs

  // --- Error handler (must be last) ---
  app.onError(errorHandler);
  app.notFound((c) =>
    c.json(
      { code: "NOT_FOUND", message: "Route not found", statusCode: 404 },
      404,
    ),
  );

  return app;
}
```

---

### TASK 4.4 — Worker entry point

**Deliverable:** `apps/api/src/index.ts`

```typescript
// apps/api/src/index.ts
// Cloudflare Worker entry point.
// Exports: default (fetch handler), queue (queue consumer), scheduled (cron handler).
// Business logic lives in modules — not in this file.

import { createApp } from "./app";

const app = createApp();

export default {
  /**
   * Handles HTTP requests.
   */
  fetch: app.fetch,

  /**
   * Handles Cloudflare Queues messages.
   * Messages are routed to the appropriate handler by event type.
   */
  async queue(
    batch: MessageBatch<unknown>,
    env: Env["Bindings"],
  ): Promise<void> {
    // Queue consumer router will be wired here in Phase 3 (Scoring Engine)
    console.log(
      `Queue batch received: ${batch.queue}, ${batch.messages.length} messages`,
    );
  },

  /**
   * Handles Cloudflare Cron Triggers.
   * The cron schedule is configured in wrangler.toml.
   */
  async scheduled(
    event: ScheduledEvent,
    env: Env["Bindings"],
    ctx: ExecutionContext,
  ): Promise<void> {
    // Scheduled job router will be wired here in Phase 5 (Admin & Integrations)
    console.log(`Cron triggered: ${event.cron}`);
  },
};
```

---

### TASK 4.5 — Core middleware

**Deliverable:**

```
apps/api/src/middleware/
├── error-handler.ts      # Catches all errors, returns consistent JSON
├── request-logger.ts     # Structured JSON request/response logging
├── require-auth.ts       # JWT verification, sets c.var.userId etc.
├── require-admin.ts      # Asserts admin role (uses require-auth output)
└── require-superadmin.ts # Asserts superadmin role
```

**`src/middleware/error-handler.ts`:**

```typescript
import type { ErrorHandler } from "hono";
import type { Env } from "../lib/env";
import type { ApiError } from "@sr/types";

/**
 * Global error handler. Catches all unhandled errors and returns a
 * consistent JSON error response.
 *
 * Domain-specific errors (e.g. PredictionLockedError) should have a
 * `statusCode` and `code` property so they are formatted correctly here.
 */
export const errorHandler: ErrorHandler<Env> = (err, c) => {
  // Known domain errors with a statusCode property
  if ("statusCode" in err && typeof err.statusCode === "number") {
    const error: ApiError = {
      code: "code" in err ? String(err.code) : "DOMAIN_ERROR",
      message: err.message,
      statusCode: err.statusCode,
    };
    return c.json(
      error,
      err.statusCode as 400 | 401 | 403 | 404 | 409 | 422 | 423 | 500,
    );
  }

  // Unknown errors — log and return 500
  console.error("[ERROR]", { message: err.message, stack: err.stack });
  const error: ApiError = {
    code: "INTERNAL_ERROR",
    message:
      c.env.ENVIRONMENT === "production"
        ? "Internal server error"
        : err.message,
    statusCode: 500,
  };
  return c.json(error, 500);
};
```

**`src/middleware/require-auth.ts`:**

```typescript
import { createMiddleware } from "hono/factory";
import { verifyAccessToken } from "@sr/auth";
import type { Env } from "../lib/env";

/**
 * Middleware that requires a valid access token.
 * Extracts the token from the Authorization header (Bearer scheme).
 * Sets c.var.userId, c.var.userRole, c.var.sessionId on success.
 * Returns 401 if the token is missing, invalid, or expired.
 *
 * Side effects: none (read-only)
 */
export const requireAuth = createMiddleware<Env>(async (c, next) => {
  const authHeader = c.req.header("Authorization");
  if (!authHeader?.startsWith("Bearer ")) {
    return c.json(
      {
        code: "UNAUTHORIZED",
        message: "Missing or invalid Authorization header",
        statusCode: 401,
      },
      401,
    );
  }

  const token = authHeader.slice(7);
  const payload = await verifyAccessToken(token, c.env.JWT_SECRET);

  if (!payload) {
    return c.json(
      {
        code: "UNAUTHORIZED",
        message: "Invalid or expired access token",
        statusCode: 401,
      },
      401,
    );
  }

  c.set("userId", payload.userId);
  c.set("userRole", payload.role);
  c.set("sessionId", payload.sessionId);

  await next();
});
```

**Acceptance criteria:**

- [ ] `GET /health` returns `{ status: 'ok', environment: 'local' }` with status 200 when running locally
- [ ] `GET /nonexistent` returns a JSON `{ code: 'NOT_FOUND', ... }` response with status 404
- [ ] An unhandled thrown error returns a JSON `{ code: 'INTERNAL_ERROR', ... }` with status 500
- [ ] In production mode, unhandled error messages are masked (not leaked to client)
- [ ] `requireAuth` returns 401 for a request with no Authorization header
- [ ] `requireAuth` returns 401 for a request with an expired token
- [ ] `requireAuth` sets `c.var.userId` correctly for a valid token

**Tests required:**

```
test: GET /health returns 200 with status:ok
test: GET /unknown-route returns 404 JSON
test: error handler masks error message in production
test: error handler exposes error message in dev
test: requireAuth rejects missing Authorization header
test: requireAuth rejects invalid JWT format
test: requireAuth rejects expired token
test: requireAuth sets context variables for valid token
test: requireAdmin rejects user-role token
test: requireAdmin accepts admin-role token
test: requireSuperAdmin rejects admin-role token
test: requireSuperAdmin accepts superadmin-role token
```

---

### TASK 4.6 — `wrangler.toml`

**Deliverable:** `apps/api/wrangler.toml`

```toml
name = "sportsrush-api"
main = "src/index.ts"
compatibility_date = "2024-11-01"
compatibility_flags = ["nodejs_compat"]

# Production bindings (default environment)
[[d1_databases]]
binding = "DB"
database_name = "sportsrush-prod"
database_id = "REPLACE_AFTER_D1_CREATED"

[[kv_namespaces]]
binding = "CACHE"
id = "REPLACE_AFTER_KV_CREATED"

[[queues.producers]]
binding = "QUEUE_SCORING"
queue = "sr-scoring-prod"

[[queues.producers]]
binding = "QUEUE_NOTIFICATIONS"
queue = "sr-notifications-prod"

[[queues.producers]]
binding = "QUEUE_SCRAPER"
queue = "sr-scraper-prod"

[[queues.consumers]]
queue = "sr-scoring-prod"
max_batch_size = 10
max_batch_timeout = 5

[[r2_buckets]]
binding = "ASSETS"
bucket_name = "sportsrush-assets-prod"

[vars]
ENVIRONMENT = "production"

# Cron triggers (scheduled jobs)
[[triggers.crons]]
crons = ["0 * * * *"]   # hourly: results scraper

# --- Dev environment ---
[env.dev]
vars = { ENVIRONMENT = "dev" }

[[env.dev.d1_databases]]
binding = "DB"
database_name = "sportsrush-dev"
database_id = "REPLACE_AFTER_D1_CREATED"

[[env.dev.kv_namespaces]]
binding = "CACHE"
id = "REPLACE_AFTER_KV_CREATED"

[[env.dev.queues.producers]]
binding = "QUEUE_SCORING"
queue = "sr-scoring-dev"

[[env.dev.r2_buckets]]
binding = "ASSETS"
bucket_name = "sportsrush-assets-dev"

# --- Staging environment ---
[env.staging]
vars = { ENVIRONMENT = "staging" }

[[env.staging.d1_databases]]
binding = "DB"
database_name = "sportsrush-staging"
database_id = "REPLACE_AFTER_D1_CREATED"

[[env.staging.kv_namespaces]]
binding = "CACHE"
id = "REPLACE_AFTER_KV_CREATED"
```

**`apps/api/.dev.vars.example`** (committed; `.dev.vars` is gitignored):

```
JWT_SECRET=local-dev-secret-min-32-chars-required
STRIPE_SECRET_KEY=sk_test_placeholder
STRIPE_WEBHOOK_SECRET=whsec_placeholder
SPORTS_API_KEY=placeholder
ENVIRONMENT=local
```

**Acceptance criteria:**

- [ ] `wrangler dev --local` starts without errors (after `migrate:local` is run)
- [ ] `wrangler deploy --dry-run` validates the config without errors
- [ ] `REPLACE_AFTER_D1_CREATED` comments are clearly visible — IDs must be filled before first deploy
- [ ] `.dev.vars` is gitignored; `.dev.vars.example` is committed

---

## PR-05 — Database Migration Framework

### Objective

Establish D1 as the database, run the first migration, and ensure migrations can be applied to local, dev, staging, and production environments reproducibly.

---

### TASK 5.1 — Create D1 databases

**Deliverable:** Three D1 database instances created in the Cloudflare dashboard (or via Wrangler CLI). IDs recorded in `wrangler.toml`.

**Commands:**

```bash
wrangler d1 create sportsrush-dev
wrangler d1 create sportsrush-staging
wrangler d1 create sportsrush-prod
```

Record the returned `database_id` for each in `wrangler.toml`.

**Acceptance criteria:**

- [ ] Three D1 databases exist in the Cloudflare account
- [ ] All three `database_id` values are filled in `wrangler.toml`
- [ ] `wrangler d1 info sportsrush-dev` returns the database metadata

---

### TASK 5.2 — Initial schema migration

**Deliverable:** `infrastructure/migrations/0001_initial_schema.sql`

This migration creates all tables from `SPORTSRUSH_2_DOMAIN_MODEL.md`. Key D1/SQLite considerations applied:

- All PKs are `TEXT` (UUID stored as text)
- Datetimes are `TEXT` in ISO 8601 format (UTC)
- JSON payloads are `TEXT` (SQLite has no JSON column type; use `json()` functions in queries)
- `PRAGMA foreign_keys = ON` is executed per connection in `lib/d1.ts` (not in migration)

**Migration structure:**

```sql
-- infrastructure/migrations/0001_initial_schema.sql
-- SR2.0 Initial Schema
-- Based on live production database export (NOT the committed backup)
-- See docs/architecture/SPORTSRUSH_2_DOMAIN_MODEL.md for ownership of each table

-- ============================================================
-- IDENTITY & AUTH
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
  id TEXT PRIMARY KEY,
  email TEXT NOT NULL UNIQUE,
  email_verified_at TEXT,
  password_hash TEXT,
  role TEXT NOT NULL DEFAULT 'user' CHECK (role IN ('user', 'admin', 'superadmin')),
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  legacy_wp_user_id INTEGER UNIQUE
);

CREATE TABLE IF NOT EXISTS sessions (
  id TEXT PRIMARY KEY,
  user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  refresh_token_hash TEXT NOT NULL UNIQUE,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  expires_at TEXT NOT NULL,
  revoked_at TEXT,
  user_agent TEXT,
  ip_address TEXT
);

CREATE TABLE IF NOT EXISTS password_resets (
  id TEXT PRIMARY KEY,
  user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  token_hash TEXT NOT NULL UNIQUE,
  expires_at TEXT NOT NULL,
  used_at TEXT
);

CREATE TABLE IF NOT EXISTS oauth_accounts (
  id TEXT PRIMARY KEY,
  user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  provider TEXT NOT NULL,
  provider_user_id TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  UNIQUE(provider, provider_user_id)
);

-- ============================================================
-- USERS & PROFILES
-- ============================================================

CREATE TABLE IF NOT EXISTS user_profiles (
  user_id TEXT PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
  display_name TEXT NOT NULL,
  avatar_url TEXT,
  timezone TEXT NOT NULL DEFAULT 'UTC',
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS user_preferences (
  user_id TEXT PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
  default_competition_id TEXT,
  notification_results INTEGER NOT NULL DEFAULT 1,   -- SQLite boolean
  notification_round_open INTEGER NOT NULL DEFAULT 1,
  notification_monthly_winner INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS push_tokens (
  id TEXT PRIMARY KEY,
  user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  token TEXT NOT NULL UNIQUE,
  platform TEXT NOT NULL CHECK (platform IN ('ios', 'android', 'web')),
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  last_seen_at TEXT NOT NULL DEFAULT (datetime('now')),
  active INTEGER NOT NULL DEFAULT 1
);

-- ============================================================
-- COMPETITIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS competitions (
  id TEXT PRIMARY KEY,
  name TEXT NOT NULL,
  sport TEXT NOT NULL CHECK (sport IN ('rugby_league', 'football', 'other')),
  description TEXT,
  logo_url TEXT,
  visibility TEXT NOT NULL DEFAULT 'public' CHECK (visibility IN ('public', 'private', 'archived')),
  display_order INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- ============================================================
-- FIXTURES & RESULTS
-- ============================================================

CREATE TABLE IF NOT EXISTS teams (
  id TEXT PRIMARY KEY,
  name TEXT NOT NULL,
  short_name TEXT,
  logo_url TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS matches (
  id TEXT PRIMARY KEY,
  competition_id TEXT NOT NULL REFERENCES competitions(id),
  home_team_id TEXT NOT NULL REFERENCES teams(id),
  away_team_id TEXT NOT NULL REFERENCES teams(id),
  play_date TEXT NOT NULL,          -- ISO 8601 UTC
  round INTEGER NOT NULL,
  round_name TEXT,                  -- nullable: descriptive label e.g. 'Final'
  status TEXT NOT NULL DEFAULT 'scheduled'
    CHECK (status IN ('scheduled', 'completed', 'postponed', 'abandoned', 'void')),
  home_score INTEGER,               -- null until result entered
  away_score INTEGER,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now')),
  created_by TEXT REFERENCES users(id),
  UNIQUE(competition_id, home_team_id, away_team_id, play_date)
);

CREATE TABLE IF NOT EXISTS team_aliases (
  id TEXT PRIMARY KEY,
  team_id TEXT NOT NULL REFERENCES teams(id),
  external_name TEXT NOT NULL,
  source TEXT NOT NULL,             -- 'bbc', 'rlcom', 'sportradar', etc.
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  created_by TEXT REFERENCES users(id),
  UNIQUE(external_name, source)
);

CREATE TABLE IF NOT EXISTS result_corrections (
  id TEXT PRIMARY KEY,
  match_id TEXT NOT NULL REFERENCES matches(id),
  previous_home INTEGER NOT NULL,
  previous_away INTEGER NOT NULL,
  corrected_home INTEGER NOT NULL,
  corrected_away INTEGER NOT NULL,
  corrected_by TEXT NOT NULL REFERENCES users(id),
  corrected_at TEXT NOT NULL DEFAULT (datetime('now')),
  reason TEXT NOT NULL              -- mandatory; empty string rejected at application layer
);

-- ============================================================
-- PREDICTIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS predictions (
  id TEXT PRIMARY KEY,
  user_id TEXT NOT NULL REFERENCES users(id),
  match_id TEXT NOT NULL REFERENCES matches(id),
  home_score INTEGER,
  away_score INTEGER,
  joker INTEGER NOT NULL DEFAULT 0, -- SQLite boolean
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now')),
  locked_at TEXT,
  UNIQUE(user_id, match_id)
);

CREATE TABLE IF NOT EXISTS prediction_overrides (
  id TEXT PRIMARY KEY,
  match_id TEXT NOT NULL REFERENCES matches(id),
  override_type TEXT NOT NULL CHECK (override_type IN ('open', 'lock')),
  set_by TEXT NOT NULL REFERENCES users(id),
  reason TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- ============================================================
-- SCORING
-- ============================================================

CREATE TABLE IF NOT EXISTS scoring_config (
  id TEXT PRIMARY KEY,
  version INTEGER NOT NULL UNIQUE,
  exact_points INTEGER NOT NULL,
  toto_points INTEGER NOT NULL,
  home_bonus_points INTEGER NOT NULL,
  away_bonus_points INTEGER NOT NULL,
  diff_bonus_points INTEGER NOT NULL,
  diff_bonus_mode TEXT NOT NULL CHECK (diff_bonus_mode IN ('toto_only', 'always', 'never')),
  joker_enabled INTEGER NOT NULL DEFAULT 0,
  joker_multiplier REAL NOT NULL DEFAULT 1.0,
  prediction_lock_minutes INTEGER NOT NULL DEFAULT 30,
  valid_from TEXT NOT NULL,
  created_by TEXT REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS match_scores (
  id TEXT PRIMARY KEY,
  user_id TEXT NOT NULL REFERENCES users(id),
  match_id TEXT NOT NULL REFERENCES matches(id),
  competition_id TEXT NOT NULL REFERENCES competitions(id),
  points_exact INTEGER NOT NULL DEFAULT 0,
  points_toto INTEGER NOT NULL DEFAULT 0,
  points_home_bonus INTEGER NOT NULL DEFAULT 0,
  points_away_bonus INTEGER NOT NULL DEFAULT 0,
  points_diff_bonus INTEGER NOT NULL DEFAULT 0,
  points_joker_multiplier INTEGER NOT NULL DEFAULT 0,
  total_points INTEGER NOT NULL DEFAULT 0,
  scoring_config_version INTEGER NOT NULL,
  calculated_at TEXT NOT NULL DEFAULT (datetime('now')),
  UNIQUE(user_id, match_id)
);

-- ============================================================
-- RANKINGS
-- ============================================================

CREATE TABLE IF NOT EXISTS ranking_snapshots (
  id TEXT PRIMARY KEY,
  competition_id TEXT NOT NULL REFERENCES competitions(id),
  league_id TEXT,                   -- null for competition-wide rankings
  snapshot_at TEXT NOT NULL DEFAULT (datetime('now')),
  user_id TEXT NOT NULL REFERENCES users(id),
  rank INTEGER NOT NULL,
  total_points INTEGER NOT NULL,
  month_points INTEGER NOT NULL,
  correct_scores INTEGER NOT NULL DEFAULT 0,
  toto_count INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS monthly_winners (
  id TEXT PRIMARY KEY,
  competition_id TEXT NOT NULL REFERENCES competitions(id),
  year INTEGER NOT NULL,
  month INTEGER NOT NULL,
  user_id TEXT NOT NULL REFERENCES users(id),
  total_points INTEGER NOT NULL,
  confirmed_at TEXT NOT NULL DEFAULT (datetime('now')),
  UNIQUE(competition_id, year, month)
);

-- ============================================================
-- PRIVATE LEAGUES
-- ============================================================

CREATE TABLE IF NOT EXISTS leagues (
  id TEXT PRIMARY KEY,
  name TEXT NOT NULL,
  competition_id TEXT NOT NULL REFERENCES competitions(id),
  owner_id TEXT NOT NULL REFERENCES users(id),
  is_paid INTEGER NOT NULL DEFAULT 0,
  price_gbp REAL,
  stripe_product_id TEXT,
  prize_gbp REAL,
  logo_url TEXT,
  banner_url TEXT,
  description TEXT,
  visibility TEXT NOT NULL DEFAULT 'public' CHECK (visibility IN ('public', 'unlisted')),
  status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'closed', 'archived')),
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS league_members (
  id TEXT PRIMARY KEY,
  league_id TEXT NOT NULL REFERENCES leagues(id),
  user_id TEXT NOT NULL REFERENCES users(id),
  joined_at TEXT NOT NULL DEFAULT (datetime('now')),
  access_granted_by TEXT NOT NULL CHECK (access_granted_by IN ('admin', 'payment', 'invite')),
  payment_intent_id TEXT,
  revoked_at TEXT,
  UNIQUE(league_id, user_id)
);

CREATE TABLE IF NOT EXISTS league_invites (
  id TEXT PRIMARY KEY,
  league_id TEXT NOT NULL REFERENCES leagues(id),
  invite_code TEXT NOT NULL UNIQUE,
  created_by TEXT NOT NULL REFERENCES users(id),
  expires_at TEXT,
  max_uses INTEGER,
  use_count INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- ============================================================
-- PAYMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS payment_events (
  id TEXT PRIMARY KEY,
  stripe_event_id TEXT NOT NULL UNIQUE,
  event_type TEXT NOT NULL,
  user_id TEXT REFERENCES users(id),
  league_id TEXT REFERENCES leagues(id),
  amount_pence INTEGER,
  currency TEXT NOT NULL DEFAULT 'gbp',
  stripe_payment_intent_id TEXT,
  status TEXT NOT NULL DEFAULT 'received' CHECK (status IN ('received', 'processed', 'failed')),
  processed_at TEXT,
  raw_payload TEXT NOT NULL,        -- JSON serialised
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- ============================================================
-- NOTIFICATIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS notification_log (
  id TEXT PRIMARY KEY,
  user_id TEXT NOT NULL REFERENCES users(id),
  type TEXT NOT NULL,
  channel TEXT NOT NULL CHECK (channel IN ('push', 'email', 'in_app')),
  payload TEXT NOT NULL,            -- JSON serialised
  status TEXT NOT NULL CHECK (status IN ('sent', 'failed', 'opted_out')),
  sent_at TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- ============================================================
-- EXTERNAL INTEGRATIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS scraper_runs (
  id TEXT PRIMARY KEY,
  source TEXT NOT NULL,
  target_date TEXT NOT NULL,
  status TEXT NOT NULL CHECK (status IN ('running', 'completed', 'failed')),
  matches_found INTEGER NOT NULL DEFAULT 0,
  matches_updated INTEGER NOT NULL DEFAULT 0,
  errors TEXT,                      -- JSON serialised error array
  duration_ms INTEGER,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS scraper_unresolved_aliases (
  id TEXT PRIMARY KEY,
  scraper_run_id TEXT NOT NULL REFERENCES scraper_runs(id),
  external_name TEXT NOT NULL,
  source TEXT NOT NULL,
  raw_context TEXT,                 -- JSON serialised surrounding data
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS scraper_competition_config (
  id TEXT PRIMARY KEY,
  competition_id TEXT NOT NULL REFERENCES competitions(id),
  source TEXT NOT NULL,
  external_name TEXT NOT NULL,
  active INTEGER NOT NULL DEFAULT 1,
  start_date TEXT,
  end_date TEXT,
  UNIQUE(competition_id, source)
);

-- ============================================================
-- ADMIN & MODERATION
-- ============================================================

CREATE TABLE IF NOT EXISTS audit_log (
  id TEXT PRIMARY KEY,
  user_id TEXT REFERENCES users(id),
  action TEXT NOT NULL,
  entity_type TEXT NOT NULL,
  entity_id TEXT NOT NULL,
  before_state TEXT,                -- JSON serialised
  after_state TEXT,                 -- JSON serialised
  ip_address TEXT,
  user_agent TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- ============================================================
-- INDEXES
-- ============================================================

CREATE INDEX IF NOT EXISTS idx_matches_competition_round ON matches(competition_id, round);
CREATE INDEX IF NOT EXISTS idx_matches_play_date ON matches(play_date);
CREATE INDEX IF NOT EXISTS idx_predictions_match ON predictions(match_id);
CREATE INDEX IF NOT EXISTS idx_predictions_user ON predictions(user_id);
CREATE INDEX IF NOT EXISTS idx_match_scores_match ON match_scores(match_id);
CREATE INDEX IF NOT EXISTS idx_match_scores_user_competition ON match_scores(user_id, competition_id);
CREATE INDEX IF NOT EXISTS idx_ranking_snapshots_competition ON ranking_snapshots(competition_id, snapshot_at);
CREATE INDEX IF NOT EXISTS idx_sessions_user ON sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_sessions_expires ON sessions(expires_at);
CREATE INDEX IF NOT EXISTS idx_league_members_league ON league_members(league_id);
CREATE INDEX IF NOT EXISTS idx_audit_log_entity ON audit_log(entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_audit_log_user ON audit_log(user_id);
```

**`infrastructure/migrations/0002_seed_scoring_config.sql`** (placeholder):

```sql
-- To be populated after Owner Decisions OD-01 through OD-05 are resolved.
-- INSERT INTO scoring_config (...) VALUES (...);
-- See docs/owner-decisions/ for resolved values.
```

**Acceptance criteria:**

- [ ] `pnpm migrate:local` applies both migrations without error
- [ ] `wrangler d1 execute sportsrush --local --command "SELECT name FROM sqlite_master WHERE type='table'"` lists all expected tables
- [ ] `UNIQUE(user_id, match_id)` constraint exists on `predictions`
- [ ] `UNIQUE(stripe_event_id)` constraint exists on `payment_events`
- [ ] `UNIQUE(league_id, user_id)` constraint exists on `league_members`
- [ ] All foreign keys reference valid tables
- [ ] `wrangler d1 migrations list sportsrush --local` shows both migrations as applied

**Tests required:**

```
test: migration applies to fresh D1 without errors
test: all expected tables exist after migration
test: unique constraints reject duplicate (user_id, match_id) prediction
test: unique constraints reject duplicate stripe_event_id
test: foreign keys reject invalid references (when PRAGMA foreign_keys = ON)
```

---

## PR-06 — Auth Module

### Objective

Implement the complete auth module: registration, email verification, login, JWT token issuance, refresh, logout, and password reset. This is the first production-grade domain module.

---

### TASK 6.1 — Auth repository

**Deliverable:** `apps/api/src/modules/auth/repository.ts`

```typescript
// Owns all D1 queries for the auth and sessions tables.
// No business logic. Returns typed domain objects.
// All queries use parameterised statements (D1 prepared statements).

// Functions to implement:
findUserByEmail(db: D1Database, email: string): Promise<User | null>
findUserById(db: D1Database, id: UUID): Promise<User | null>
createUser(db: D1Database, data: CreateUserData): Promise<User>
updateEmailVerified(db: D1Database, userId: UUID): Promise<void>
createSession(db: D1Database, data: CreateSessionData): Promise<Session>
findSessionByRefreshTokenHash(db: D1Database, hash: string): Promise<Session | null>
revokeSession(db: D1Database, sessionId: UUID): Promise<void>
revokeAllUserSessions(db: D1Database, userId: UUID): Promise<void>
createPasswordReset(db: D1Database, data: CreatePasswordResetData): Promise<void>
findValidPasswordReset(db: D1Database, tokenHash: string): Promise<PasswordReset | null>
markPasswordResetUsed(db: D1Database, id: UUID): Promise<void>
updatePasswordHash(db: D1Database, userId: UUID, hash: string): Promise<void>
createAuditLog(db: D1Database, data: AuditLogData): Promise<void>
```

**Acceptance criteria:**

- [ ] All functions use D1 prepared statements (no string interpolation of user input)
- [ ] `createUser` creates both a `users` row and a `user_profiles` row and a `user_preferences` row in a single transaction
- [ ] `findUserByEmail` is case-insensitive (use `LOWER(email) = LOWER(?)`)

---

### TASK 6.2 — Auth service

**Deliverable:** `apps/api/src/modules/auth/service.ts`

```typescript
// Business logic layer. Enforces domain rules.
// Calls repository for data access. Emits no events (auth is a foundational domain).

// Functions:
register(db, data: RegisterInput): Promise<{ user: User }>
verifyEmail(db, token: string): Promise<void>
login(db, data: LoginInput, ipAddress: string, userAgent: string):
  Promise<{ accessToken: string, refreshToken: string, user: User }>
refreshToken(db, refreshToken: string): Promise<{ accessToken: string, refreshToken: string }>
logout(db, sessionId: UUID): Promise<void>
requestPasswordReset(db, email: string): Promise<void>  // always succeeds (no user enumeration)
confirmPasswordReset(db, token: string, newPassword: string): Promise<void>
```

**Key rules enforced:**

- Registration: email must be unique (handle DB unique constraint error → friendly error)
- Login: failed attempt tracking (read from KV, increment, lock after MAX_LOGIN_ATTEMPTS)
- Refresh: rotate refresh token (create new session, revoke old one) in a single transaction
- Password reset: token is single-use, expires in 15 minutes
- `requestPasswordReset` returns success even if the email does not exist (prevents user enumeration)

---

### TASK 6.3 — Auth routes

**Deliverable:** `apps/api/src/modules/auth/routes.ts`

```typescript
// Hono route handlers. Validates input with Zod. Calls service. Returns HTTP responses.
// POST /auth/register
// POST /auth/verify-email
// POST /auth/login
// POST /auth/refresh
// POST /auth/logout
// POST /auth/password-reset/request
// POST /auth/password-reset/confirm
```

**Response format examples:**

```json
// POST /auth/login → 200
{
  "accessToken": "eyJ...",
  "user": {
    "id": "...",
    "email": "user@example.com",
    "role": "user",
    "displayName": "Username"
  }
}

// POST /auth/register → 201
{
  "message": "Registration successful. Please check your email to verify your account."
}
```

The refresh token is returned as an HTTP-only, Secure, SameSite=Strict cookie for web clients. For mobile clients (identified by the absence of a cookie-accepting header), it is also included in the response body.

**Register auth routes in `app.ts`:**

```typescript
import { authRoutes } from "./modules/auth/routes";
app.route("/auth", authRoutes);
```

**Acceptance criteria:**

- [ ] `POST /auth/register` with valid data creates a user and returns 201
- [ ] `POST /auth/register` with a duplicate email returns 409 with `code: 'EMAIL_TAKEN'`
- [ ] `POST /auth/login` with correct credentials returns an access token and sets a refresh token cookie
- [ ] `POST /auth/login` with wrong password returns 401 (not 404 — no user enumeration)
- [ ] `POST /auth/refresh` with valid refresh token returns a new access token and rotates the refresh token
- [ ] `POST /auth/refresh` with an already-used refresh token returns 401 (rotation invalidates old token)
- [ ] `POST /auth/logout` invalidates the session
- [ ] `POST /auth/password-reset/request` returns 200 even for an email that does not exist
- [ ] All auth endpoints are rate-limited

**Tests required:**

```
test: register with valid data returns 201
test: register with duplicate email returns 409
test: register with invalid email format returns 422
test: register with short password returns 422
test: login with correct credentials returns access token
test: login with wrong password returns 401
test: login with nonexistent email returns 401 (not 404)
test: refresh with valid token returns new access token
test: refresh with used refresh token returns 401
test: logout invalidates session
test: password reset request returns 200 for unknown email
test: password reset confirm with expired token returns 400
test: password reset confirm with used token returns 400
test: password reset confirm with valid token updates password
```

---

## PR-07 — Test Framework and Integration Test Harness

### Objective

Configure Vitest for unit tests and set up Miniflare-backed integration tests so every subsequent PR can include both.

---

### TASK 7.1 — Vitest configuration

**Deliverable:**

```
apps/api/
├── vitest.config.ts          # Unit test config
└── vitest.integration.config.ts  # Integration test config (uses miniflare pool)
```

**`vitest.config.ts`:**

```typescript
import { defineConfig } from "vitest/config";

export default defineConfig({
  test: {
    name: "unit",
    include: ["src/**/*.test.ts"],
    exclude: ["src/**/*.int.test.ts"],
    coverage: {
      provider: "v8",
      reporter: ["text", "json", "html"],
      include: ["src/modules/**/*.ts", "src/middleware/**/*.ts"],
      exclude: ["src/**/*.test.ts", "src/index.ts"],
      thresholds: {
        branches: 80,
        functions: 80,
        lines: 80,
      },
    },
  },
});
```

**`vitest.integration.config.ts`:**

```typescript
import { defineConfig } from "vitest/config";
import { defineWorkersConfig } from "@cloudflare/vitest-pool-workers/config";

export default defineWorkersConfig({
  test: {
    name: "integration",
    include: ["src/**/*.int.test.ts"],
    poolOptions: {
      workers: {
        wrangler: {
          configPath: "./wrangler.toml",
        },
        miniflare: {
          // D1 bindings are auto-created per test file and seeded fresh
        },
      },
    },
  },
});
```

---

### TASK 7.2 — Integration test helpers

**Deliverable:**

```
testing/helpers/
├── index.ts
├── db.ts          # createTestDb() — applies migrations to a fresh D1 instance
├── app.ts         # createTestApp(db) — creates Hono app with test bindings
├── auth.ts        # createTestUser(), getAuthHeader(), getAdminAuthHeader()
└── factories/
    ├── user.ts    # buildUser(overrides?) → User test object
    └── match.ts   # buildMatch(overrides?) → Match test object
```

**`testing/helpers/db.ts`:**

```typescript
// Creates a fresh D1 database for each test file, applies all migrations.
// Used by integration tests to get a clean database state.
export async function createTestDb(env: Env["Bindings"]): Promise<D1Database>;
```

**`testing/helpers/auth.ts`:**

```typescript
// Creates test users and returns auth headers for use in integration tests.
export async function createTestUser(
  db,
  overrides?,
): Promise<{ user: User; accessToken: string }>;
export async function createTestAdmin(
  db,
): Promise<{ user: User; accessToken: string }>;
export function getAuthHeader(accessToken: string): Record<string, string>;
```

**Integration test example pattern:**

```typescript
// apps/api/src/modules/auth/auth.int.test.ts
import { SELF } from "cloudflare:test";
import { createTestDb } from "@sr/test-helpers";
import { describe, it, expect, beforeEach } from "vitest";

describe("POST /auth/register", () => {
  beforeEach(async () => {
    // Reset DB state — each test gets fresh migrations
  });

  it("returns 201 and creates a user", async () => {
    const response = await SELF.fetch("http://localhost/auth/register", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        email: "test@example.com",
        password: "password123",
        displayName: "Test User",
      }),
    });

    expect(response.status).toBe(201);
    const body = await response.json();
    expect(body).toHaveProperty("message");
  });
});
```

**Acceptance criteria:**

- [ ] `pnpm test` runs unit tests and exits 0
- [ ] `pnpm test:integration` runs integration tests against miniflare and exits 0
- [ ] Unit test coverage report is generated under `coverage/`
- [ ] Integration tests use a fresh D1 database per test file (isolated state)
- [ ] A failing unit test causes the CI pipeline to fail

---

## PR-08 — CI/CD Pipelines

### Objective

Implement GitHub Actions workflows that enforce quality gates on every PR and automate deployment to dev on merge.

---

### TASK 8.1 — CI workflow (`ci.yml`)

**Deliverable:** `.github/workflows/ci.yml`

```yaml
name: CI

on:
  push:
    branches: ["*"]
  pull_request:
    branches: [main]

jobs:
  lint:
    name: Lint
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: pnpm/action-setup@v3
        with:
          version: 9
      - uses: actions/setup-node@v4
        with:
          node-version: 20
          cache: "pnpm"
      - run: pnpm install --frozen-lockfile
      - run: pnpm lint
      - run: pnpm format:check

  typecheck:
    name: Type Check
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: pnpm/action-setup@v3
        with:
          version: 9
      - uses: actions/setup-node@v4
        with:
          node-version: 20
          cache: "pnpm"
      - run: pnpm install --frozen-lockfile
      - run: pnpm typecheck

  unit-tests:
    name: Unit Tests
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: pnpm/action-setup@v3
        with:
          version: 9
      - uses: actions/setup-node@v4
        with:
          node-version: 20
          cache: "pnpm"
      - run: pnpm install --frozen-lockfile
      - run: pnpm --filter @sr/scoring test --coverage # 100% branch coverage gate
      - run: pnpm --filter @sr/auth test
      - run: pnpm --filter @sr/validation test
      - run: pnpm --filter @sr/api test

  integration-tests:
    name: Integration Tests
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: pnpm/action-setup@v3
        with:
          version: 9
      - uses: actions/setup-node@v4
        with:
          node-version: 20
          cache: "pnpm"
      - run: pnpm install --frozen-lockfile
      - run: pnpm --filter @sr/api test:integration

  build-check:
    name: Build Check
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: pnpm/action-setup@v3
        with:
          version: 9
      - uses: actions/setup-node@v4
        with:
          node-version: 20
          cache: "pnpm"
      - run: pnpm install --frozen-lockfile
      - run: pnpm --filter @sr/api build # wrangler deploy --dry-run

  secret-scan:
    name: Secret Scan
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Check for credential patterns
        run: |
          # Reject any file containing known WP credential patterns
          if grep -r "u108848352_KDqxs\|WhuiMoFs0X\|u108848352_Ewka1" . --include="*.ts" --include="*.js" --include="*.toml" --include="*.json"; then
            echo "ERROR: Legacy credential found in codebase"
            exit 1
          fi
          if grep -r "password\s*=\s*['\"][^'\"]*['\"]" . --include="*.ts" --include="*.toml"; then
            echo "WARNING: Possible hardcoded password found"
          fi
```

**Acceptance criteria:**

- [ ] CI fails if any job fails
- [ ] CI runs on every push (not just PRs)
- [ ] `pnpm install --frozen-lockfile` is used (not `pnpm install`) to catch lockfile drift
- [ ] The secret scan step rejects known WordPress credentials if accidentally committed

---

### TASK 8.2 — Dev deploy workflow (`deploy-dev.yml`)

**Deliverable:** `.github/workflows/deploy-dev.yml`

```yaml
name: Deploy to Dev

on:
  push:
    branches: [main]

jobs:
  deploy-api-dev:
    name: Deploy API to Dev
    runs-on: ubuntu-latest
    needs: [] # CI must pass (enforced by branch protection)
    environment: dev
    steps:
      - uses: actions/checkout@v4
      - uses: pnpm/action-setup@v3
        with:
          version: 9
      - uses: actions/setup-node@v4
        with:
          node-version: 20
          cache: "pnpm"
      - run: pnpm install --frozen-lockfile
      - name: Apply D1 migrations
        run: pnpm --filter @sr/api migrate:dev
        env:
          CLOUDFLARE_API_TOKEN: ${{ secrets.CLOUDFLARE_API_TOKEN }}
          CLOUDFLARE_ACCOUNT_ID: ${{ secrets.CLOUDFLARE_ACCOUNT_ID }}
      - name: Deploy Worker
        run: pnpm --filter @sr/api deploy:dev
        env:
          CLOUDFLARE_API_TOKEN: ${{ secrets.CLOUDFLARE_API_TOKEN }}
          CLOUDFLARE_ACCOUNT_ID: ${{ secrets.CLOUDFLARE_ACCOUNT_ID }}
      - name: Smoke test
        run: |
          sleep 10
          STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://dev-api.sportsrush.co.uk/health)
          if [ "$STATUS" != "200" ]; then
            echo "Smoke test failed: /health returned $STATUS"
            exit 1
          fi
```

**Secrets required in GitHub repository settings:**

```
CLOUDFLARE_API_TOKEN     (Cloudflare API token with Workers and D1 permissions)
CLOUDFLARE_ACCOUNT_ID    (Cloudflare account ID)
```

**Acceptance criteria:**

- [ ] Workflow triggers only on push to `main`
- [ ] Migrations are applied before the Worker is deployed
- [ ] Smoke test hits `/health` and fails the deployment if it returns non-200

---

### TASK 8.3 — Branch protection rules

**Deliverable:** GitHub repository settings (documented in `docs/runbooks/branch-protection.md`)

Rules for the `main` branch:

- Require pull request reviews (at least 1)
- Require status checks to pass: `lint`, `typecheck`, `unit-tests`, `integration-tests`, `build-check`
- Require branches to be up to date before merging
- No force pushes
- No direct pushes to `main`

---

## PR-09 — Local Development Workflow

### Objective

Ensure a developer (or AI assistant) can clone the repository, follow a single document, and have a running local environment in under 10 minutes.

---

### TASK 9.1 — Local setup documentation

**Deliverable:** `docs/runbooks/local-development.md`

This runbook covers:

1. **Prerequisites**

```
- Node.js 20+ (use .nvmrc: `nvm use`)
- pnpm 9+ (`npm install -g pnpm`)
- Wrangler CLI (`pnpm install -g wrangler`)
- Wrangler authentication (`wrangler login`)
- A Cloudflare account (free tier is sufficient for dev)
```

2. **First-time setup**

```bash
git clone https://github.com/your-org/sportsrush.git
cd sportsrush
pnpm install
cp apps/api/.dev.vars.example apps/api/.dev.vars
# Edit apps/api/.dev.vars and fill in actual values
pnpm --filter @sr/api migrate:local
pnpm dev
```

3. **What `pnpm dev` starts**

- `apps/api`: Hono Worker via `wrangler dev --local` on `localhost:8787`
- `apps/web`: Next.js dev server on `localhost:3000`
- `apps/admin`: Next.js dev server on `localhost:3001`

4. **Running tests locally**

```bash
pnpm test                          # Unit tests across all packages
pnpm --filter @sr/api test:integration  # Integration tests with miniflare
pnpm test:e2e                      # Playwright E2E (requires dev servers running)
```

5. **Adding a new migration**

```bash
# Create the migration file
echo "-- Your SQL here" > infrastructure/migrations/0003_your_change.sql
# Apply locally
pnpm --filter @sr/api migrate:local
# Verify
wrangler d1 execute sportsrush --local --command "PRAGMA table_info(your_table)"
```

6. **Common problems**

- `wrangler dev` fails with binding errors → check `.dev.vars` has all required keys
- D1 foreign key errors → run `PRAGMA foreign_keys = ON` check in d1.ts
- TypeScript errors in Workers → ensure `tsconfig.json` uses `workers.json` base (not `nextjs.json`)

---

### TASK 9.2 — `.env.example` and secrets documentation

**Deliverable:** `docs/runbooks/secrets.md`

Documents every secret key, what it is for, where to obtain it, and which environments it applies to.

| Key                     | Description                        | Required in   | Obtained from                                 |
| ----------------------- | ---------------------------------- | ------------- | --------------------------------------------- |
| `JWT_SECRET`            | Signs access tokens. Min 32 chars. | All           | `openssl rand -hex 32`                        |
| `STRIPE_SECRET_KEY`     | Stripe API key                     | All           | Stripe Dashboard                              |
| `STRIPE_WEBHOOK_SECRET` | Verifies Stripe webhook signatures | All           | Stripe Dashboard (after webhook registration) |
| `SPORTS_API_KEY`        | Commercial sports data API         | staging, prod | Vendor portal                                 |
| `CLOUDFLARE_API_TOKEN`  | GitHub Actions deploy token        | CI only       | Cloudflare Dashboard                          |
| `CLOUDFLARE_ACCOUNT_ID` | Cloudflare account identifier      | CI only       | Cloudflare Dashboard                          |

---

## PHASE 1 COMPLETION CHECKLIST

All items must be checked before Phase 2 begins.

### Infrastructure

- [ ] Repository created in GitHub with `main` branch protection enforced
- [ ] Three D1 databases created (dev, staging, prod) with IDs in `wrangler.toml`
- [ ] Cloudflare KV namespaces created and IDs in `wrangler.toml`
- [ ] Cloudflare Queues created (dev, staging, prod per queue name)
- [ ] R2 buckets created (dev, staging, prod)
- [ ] `CLOUDFLARE_API_TOKEN` and `CLOUDFLARE_ACCOUNT_ID` stored as GitHub secrets
- [ ] Worker secrets set via `wrangler secret put` for dev and staging

### Code

- [ ] All 9 PRs merged to `main`
- [ ] `pnpm install && pnpm build && pnpm test` exits 0 from root
- [ ] `GET /health` returns 200 on dev environment (`https://dev-api.sportsrush.co.uk/health`)
- [ ] Auth register → verify → login → refresh → logout works end-to-end on dev
- [ ] `pnpm --filter @sr/api migrate:dev` shows both migrations as applied

### Tests

- [ ] Unit tests: all passing, > 80% coverage on service and middleware files
- [ ] Auth tests: all 15 auth test cases passing
- [ ] Integration tests: all running against miniflare
- [ ] CI pipeline: all jobs green on `main`

### Documentation

- [ ] `docs/runbooks/local-development.md` complete
- [ ] `docs/runbooks/secrets.md` complete
- [ ] All planning documents in `docs/architecture/`

---

## PHASE 1 → PHASE 2 HANDOFF

When Phase 1 is complete, the following are in place for Phase 2 (Fixtures & Results module):

- A running Hono API with auth, middleware, error handling, and logging
- A D1 database with all tables including `matches`, `teams`, `team_aliases`
- A typed `Env` binding with `DB` for D1 queries
- A `requireAuth` and `requireAdmin` middleware ready to protect new routes
- A `createTestDb` and `createTestApp` test harness for integration tests
- A CI pipeline that enforces quality gates
- Shared packages (`@sr/types`, `@sr/validation`, `@sr/events`, `@sr/auth`) providing typed contracts

Phase 2 adds the Competitions and Fixtures & Results modules following the same pattern established in PR-06 (auth module): repository → service → routes → integration tests.
