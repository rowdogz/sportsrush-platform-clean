# @sr/types

Canonical TypeScript domain types for the SportsRush platform.

This is a **types-only package** — it contains zero runtime code. Every export is a
TypeScript type or type alias. The compiled output of this package is empty.

---

## Usage

```typescript
import type { UUID, Match, MatchScore, PrivateLeague } from "@sr/types";
```

This is the **only** import path. Do not import from individual files within the package
(e.g. `@sr/types/src/fixture`) — the public API is `src/index.ts` only.

---

## Package Structure

```
src/
├── index.ts         Re-exports everything — the only public entry point
├── common.ts        UUID, Timestamp (branded), PaginatedResponse, ApiError
├── auth.ts          User, Session, Role, TokenPayload, PasswordReset, OAuthAccount
├── profile.ts       UserProfile, UserPreferences, PushToken, PushPlatform
├── competition.ts   Competition, Season, Sport, CompetitionVisibility
├── fixture.ts       Match, Team, TeamAlias, MatchStatus, ResultCorrection, MatchWithTeams
├── prediction.ts    Prediction, PredictionOverride, PredictionInput
├── scoring.ts       ScoringConfig, MatchScore, ScoreBreakdown, DiffBonusMode
├── ranking.ts       RankingRow, RankingFilter, RankingSnapshot, MonthlyWinner
├── league.ts        PrivateLeague, LeagueMembership, PaymentEntitlement, LeagueType
├── payment.ts       PaymentEvent, PaymentStatus
├── audit.ts         AuditEvent, AuditAction, AuditEntityType
├── events.ts        SystemEvent, SystemEventType
├── scraper.ts       ScraperResult, ScraperRun, UnresolvedAlias
└── notification.ts  NotificationLog, NotificationType, NotificationChannel
```

---

## Invariants

These rules apply to every type in this package and must never be violated:

### 1. All properties are `readonly`

Types represent data records. Mutations are expressed through explicit update types
or function parameters — not by modifying the base record type.

```typescript
// correct
export type Match = {
  readonly id: UUID;
  readonly homeScore: number | null;
};

// wrong — do not omit readonly
export type Match = {
  id: UUID;
  homeScore: number | null;
};
```

### 2. UUID and Timestamp are branded types

`UUID` and `Timestamp` cannot be accidentally swapped with plain `string` values.
The compiler will reject an assignment of `string` to `UUID`.

```typescript
import type { UUID, Timestamp } from '@sr/types'

// Correct — construct branded types explicitly in implementation code:
const id = '550e8400-e29b-41d4-a716-446655440000' as UUID
const ts = new Date().toISOString() as Timestamp

// Wrong — TypeScript will reject this:
const match: Match = { id: 'plain-string', ... } // error: string is not UUID
```

### 3. No `any` types

Use `unknown` for values that are genuinely untyped at this layer (e.g. JSON snapshots
in `AuditEvent.beforeValue`). Full types for those values live in the domain that owns
them.

### 4. Nullability is explicit

A property that may be absent uses `Type | null` — not `Type | undefined` and not
optional (`?:`). The only exception is input types (e.g. `PredictionInput`) where
optional fields represent "omit to preserve existing value" semantics.

### 5. No circular dependencies

Files within this package import only from `./common`. No file imports from another
sibling file.

---

## Owner Decisions embedded in types

Several types include comments marking unresolved Owner Decisions (ODs). These affect
how certain fields behave at runtime but the type shape is defined conservatively so
they do not need to change when the OD is resolved:

| Type            | Field                             | OD Reference                   |
| --------------- | --------------------------------- | ------------------------------ |
| `Prediction`    | `joker`                           | OD-01 (joker enabled/disabled) |
| `ScoringConfig` | `jokerEnabled`, `jokerMultiplier` | OD-01                          |
| `ScoringConfig` | `diffBonusMode`                   | OD-02 (diff bonus draw rule)   |
| `RankingRow`    | rank tiebreaker ordering          | OD-04                          |
| `MonthlyWinner` | `isTied`                          | OD-06 (tie handling)           |

---

## Domain ownership mapping

| Types                                                     | Owning Domain                  |
| --------------------------------------------------------- | ------------------------------ |
| `User`, `Session`, `TokenPayload`                         | Identity & Auth                |
| `UserProfile`, `UserPreferences`, `PushToken`             | Users & Profiles               |
| `Competition`, `Season`                                   | Competitions                   |
| `Match`, `Team`, `TeamAlias`, `ResultCorrection`          | Fixtures & Results             |
| `Prediction`, `PredictionOverride`                        | Predictions                    |
| `ScoringConfig`, `MatchScore`, `ScoreBreakdown`           | Scoring Engine                 |
| `RankingRow`, `RankingSnapshot`, `MonthlyWinner`          | Rankings                       |
| `PrivateLeague`, `LeagueMembership`, `PaymentEntitlement` | Private Leagues                |
| `PaymentEvent`                                            | Payments                       |
| `AuditEvent`                                              | Admin & Moderation             |
| `ScraperResult`, `ScraperRun`, `UnresolvedAlias`          | External Integrations          |
| `NotificationLog`                                         | Notifications                  |
| `SystemEvent`, `SystemEventType`                          | Cross-cutting (queue envelope) |

---

## Running type checks

```bash
# From the repo root
pnpm --filter @sr/types typecheck

# Or as part of the full monorepo check
pnpm typecheck
```
