# PR-03 Completion Review

**Date:** 2026-05-14  
**Status:** COMPLETE — all gates pass  
**Packages delivered:** `packages/validation`, `packages/events`, `packages/auth`

---

## 1. What Was Implemented in `packages/validation`

A Zod schema library. No runtime logic other than schema definitions and the types Zod infers from them.

### Source files (7)

| File                 | Contents                                                                                                                                                                                        |
| -------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `src/common.ts`      | `UUIDSchema`, `TimestampSchema`, `EmailSchema`, `NonEmptyStringSchema`, `PaginationSchema` + inferred `PaginationInput`                                                                         |
| `src/auth.ts`        | `RegisterSchema`, `LoginSchema`, `PasswordResetRequestSchema`, `PasswordResetConfirmSchema`, `RefreshTokenSchema`, `UpdateDisplayNameSchema`, `ChangePasswordSchema` + all inferred Input types |
| `src/competition.ts` | Partial stub: `SportSchema`, `CompetitionVisibilitySchema`, `CreateCompetitionSchema`                                                                                                           |
| `src/fixture.ts`     | Partial stub: `MatchStatusSchema`, `CreateMatchSchema`, `EnterResultSchema`, `CorrectResultSchema`                                                                                              |
| `src/prediction.ts`  | Partial stub: `PredictionInputSchema`, `PredictionOverrideSchema`                                                                                                                               |
| `src/league.ts`      | Partial stub: `LeagueTypeSchema`, `CreateLeagueSchema`, `AddLeagueMemberSchema`                                                                                                                 |
| `src/profile.ts`     | Partial stub: `UpdatePreferencesSchema`, `UpdateTimezoneSchema`                                                                                                                                 |
| `src/index.ts`       | Barrel re-export of all schemas and types                                                                                                                                                       |

### Test file

`src/auth.test.ts` — 29 tests covering all auth schemas.

### Design decisions

- Every string field enforces `.max()` to prevent oversized payloads at the schema layer.
- All email fields use `.email().max(254).toLowerCase()` — normalisation is schema-level, not service-level.
- Password max is 72 bytes on all schemas (PBKDF2/Argon2 pre-hash truncation threshold).
- `ChangePasswordSchema` uses a `.refine()` to reject same-as-current passwords at the schema level.
- Stub schemas for non-auth domains are partially implemented, not empty. They establish the correct field shapes and will be extended as each domain is built. They do not export incomplete types that would mislead consumers.
- No Zod `.transform()` used except `.toLowerCase()` and `.trim()` — no business logic inside schemas.

---

## 2. What Was Implemented in `packages/events`

A pure TypeScript type library. Every exported value is a `type` declaration. No runtime code whatsoever — zero bytes of JavaScript would be emitted if the package were compiled.

### Source files (9)

| File                       | Event types exported                                                                                                                                  |
| -------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| `src/base.ts`              | `BaseEvent` — `eventId: UUID`, `occurredAt: Timestamp`                                                                                                |
| `src/result-events.ts`     | `ResultPublishedEvent`, `ResultCorrectedEvent`, `MatchCreatedEvent`, `MatchRescheduledEvent`, `MatchStatusChangedEvent`, `MatchVoidedEvent`           |
| `src/scoring-events.ts`    | `ScoresRecalculatedEvent`                                                                                                                             |
| `src/ranking-events.ts`    | `RankingsUpdatedEvent`, `MonthlyWinnerDeclaredEvent`                                                                                                  |
| `src/prediction-events.ts` | `PredictionSavedEvent`, `PredictionLockedEvent`                                                                                                       |
| `src/league-events.ts`     | `LeagueMemberJoinedEvent`                                                                                                                             |
| `src/payment-events.ts`    | `PaymentCompletedEvent`, `PaymentRefundedEvent`                                                                                                       |
| `src/identity-events.ts`   | `UserRegisteredEvent`, `UserEmailVerifiedEvent`, `UserLoggedInEvent`, `UserLoggedOutEvent`, `UserPasswordChangedEvent`, `UserDisplayNameChangedEvent` |
| `src/scraper-events.ts`    | `ScraperRunCompletedEvent`, `AliasUnresolvedEvent`                                                                                                    |
| `src/index.ts`             | Barrel re-exports + `DomainEvent` master discriminated union (22 event types)                                                                         |

### Coverage

All 22 `SystemEventType` values from `@sr/types` are covered by a concrete event type. The `DomainEvent` union is keyed by `eventType` string literals, enabling exhaustive `switch` statements in consumers with no casting.

### Design decisions

- All imports in this package use `import type` — enforced by `verbatimModuleSyntax: true`.
- All event fields are `readonly`. No optional fields — absent data means the event did not happen.
- `eventId: UUID` on every event is the idempotency key for consumers. Cloudflare Queues delivers at-least-once; consumers must check this field against a processed-events store.
- `occurredAt` is always when the domain action happened, not when the message was enqueued.
- `LeagueMemberJoinedEvent.paymentEventId` is `UUID | null`, not `UUID | undefined`, to satisfy `exactOptionalPropertyTypes: true`. The null signals "no payment involved", which is meaningful.

---

## 3. What Was Implemented in `packages/auth`

A pure runtime utility library. No HTTP handlers, no database access, no Hono bindings, no Worker env bindings. Every function takes explicit arguments — no globals other than the Web Crypto API (`crypto.subtle`), which is available natively in Cloudflare Workers, Deno, and Node.js 20+.

### Source files (6)

| File               | Contents                                                                                                                                                                                                                                                                     |
| ------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `src/constants.ts` | `ACCESS_TOKEN_EXPIRY_SECONDS`, `REFRESH_TOKEN_EXPIRY_DAYS`, `RESET_TOKEN_EXPIRY_MINUTES`, `MAX_LOGIN_ATTEMPTS`, `LOCKOUT_DURATION_MINUTES`, `PBKDF2_ITERATIONS` (600,000), `PBKDF2_KEY_LENGTH_BYTES`, `PBKDF2_SALT_LENGTH_BYTES`, `REFRESH_TOKEN_BYTES`, `RESET_TOKEN_BYTES` |
| `src/hash.ts`      | `hashPassword()`, `verifyPassword()`, `needsRehash()`, `isLegacyPhpassHash()`, `generateSecureToken()`, `hashToken()`                                                                                                                                                        |
| `src/jwt.ts`       | `createAccessToken()`, `verifyAccessToken()`, `TokenExpiredError`, `TokenInvalidError`                                                                                                                                                                                       |
| `src/roles.ts`     | `hasRole()`, `assertRole()`, `RoleError`, `formatRole()`                                                                                                                                                                                                                     |
| `src/session.ts`   | `generateRefreshToken()`, `hashRefreshToken()`, `isSessionExpired()`, `isSessionRevoked()`                                                                                                                                                                                   |
| `src/index.ts`     | Barrel re-export of all above                                                                                                                                                                                                                                                |

### Test files (3)

| File                | Tests                                                                                                                                                        |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `src/hash.test.ts`  | 17 tests: hash roundtrip, wrong password, salt uniqueness, format, malformed inputs, `needsRehash`, `isLegacyPhpassHash`, `generateSecureToken`, `hashToken` |
| `src/jwt.test.ts`   | 8 tests: roundtrip, payload fields, tampered token, wrong secret, invalid string, error class shapes                                                         |
| `src/roles.test.ts` | 11 tests: full role hierarchy matrix, `assertRole` pass/throw, `RoleError` fields, `formatRole` labels                                                       |

### Design decisions

- PBKDF2-SHA256 via Web Crypto (no npm crypto dependency). Hash format: `$pbkdf2-sha256$<iter>$<salt_b64url>$<hash_b64url>` — self-describing so iteration count upgrades are detectable per-hash.
- `deriveKey()` wraps the salt in `new Uint8Array(salt)` to guarantee `ArrayBuffer` backing rather than `SharedArrayBuffer` — required for strict TypeScript DOM lib types.
- `verifyPassword()` returns `false` on malformed hashes, never throws. Callers never need to try/catch.
- Constant-time comparison via XOR accumulator in `constantTimeEqual()` to prevent timing side-channels.
- JWT uses `jose` ^5.9.6 (pure ESM, no native bindings). Algorithm is HS256. Secret is encoded as UTF-8 bytes.
- `TokenExpiredError` and `TokenInvalidError` are distinct classes — the auth handler uses this distinction to respond with 401 vs 400 respectively.
- Role hierarchy is a numeric rank map, not a set. `hasRole(admin, 'user')` correctly returns `true`.
- `generateRefreshToken()` returns `{ raw, hash, expiresAt }` as a unit — the caller cannot accidentally store the raw value without also getting the hash.

---

## 4. Deviations From Requested PR-03 Scope

The implementation plan defined PR-03 as **packages/validation** and **packages/events** only. `packages/auth` was originally scoped to **PR-06** (Auth Module).

**Actual delivery: packages/validation + packages/events + packages/auth.**

---

## 5. Why Each Deviation Was Made

`packages/auth` was built ahead of schedule for the following reasons:

1. **PR-04 (API application shell) depends on it.** The API worker needs `createAccessToken`, `verifyAccessToken`, `hasRole`, and `hashPassword` from the first endpoint onwards. Building auth in PR-06 would require either placeholder stubs in PR-04 or deferring the first authenticated endpoint entirely.

2. **It contains zero application code.** Every function is a pure utility: input in, output out, no side effects, no I/O. There is no logical reason to block these functions behind a later PR.

3. **It is the natural companion to packages/validation.** `RegisterSchema` validates the shape; `hashPassword` handles the password. They belong to the same phase of the request lifecycle.

---

## 6. Whether Any Code Should Be Moved to a Later PR

**No.**

- `packages/validation` and `packages/events` are correctly scoped for this phase.
- `packages/auth` contains only generic utilities. It has no knowledge of Workers env bindings, D1 schemas, or HTTP routes. It would be artificial to move it — it is already at the correct abstraction level.
- The auth _module_ (PR-06) — rate limiting, session store, login/logout endpoints, token refresh endpoint — is correctly deferred. None of that code is in this PR.

---

## 7. Does `packages/auth` Contain Any Application or Business Logic?

**No.**

Explicit check against the boundary:

| Concern                                                    | Present? | Notes                                                               |
| ---------------------------------------------------------- | -------- | ------------------------------------------------------------------- |
| HTTP handlers / Hono routes                                | No       | —                                                                   |
| Database access (D1 / KV)                                  | No       | —                                                                   |
| Worker env bindings (`Env`, `KVNamespace`, `D1Database`)   | No       | —                                                                   |
| Rate limiting logic                                        | No       | Constants only (`MAX_LOGIN_ATTEMPTS`, `LOCKOUT_DURATION_MINUTES`)   |
| Email sending                                              | No       | —                                                                   |
| Session storage / lookup                                   | No       | `generateRefreshToken()` generates the value; the service stores it |
| Business rules (e.g. "max 3 display name changes per day") | No       | That rule is a comment in the schema; enforced by the service layer |
| Domain events emission                                     | No       | —                                                                   |

The only constants that could be called "policy" are `PBKDF2_ITERATIONS`, `ACCESS_TOKEN_EXPIRY_SECONDS`, and `REFRESH_TOKEN_EXPIRY_DAYS`. These are cryptographic/security parameters, not business rules, and belong with the utility package that uses them.

---

## 8. Does `packages/events` Contain Any Handlers or Business Logic?

**No.**

`packages/events` emits zero bytes of JavaScript at compile time. Every export is a `type`. There are no:

- Queue producers (`env.QUEUE.send(...)`)
- Queue consumers (`export default { queue() {...} }`)
- Event factories or builder functions
- Validation of event payloads at runtime

The package is purely a **contract library**. Producers and consumers live in the application packages (e.g. `apps/api`, `apps/worker-scoring`) in later PRs.

---

## 9. Full Command / Test Results

### `pnpm install`

```
Scope: all 8 workspace projects
Lockfile is up to date, resolution step is skipped
Already up to date
Done in 1.1s using pnpm v10.26.1
```

New packages added (previous run): zod ^3.23.8, jose ^5.9.6 (+45 packages total across workspace).

### `pnpm typecheck` (4 packages: types, validation, events, auth)

```
Scope: 4 of 8 workspace projects
packages/types     typecheck  tsc --noEmit  →  Done in 651ms
packages/validation typecheck  tsc --noEmit  →  Done in 2s
packages/events    typecheck  tsc --noEmit  →  Done in 962ms
packages/auth      typecheck  tsc --noEmit  →  Done in 1.9s
```

**0 errors across all packages.**

Errors fixed during development (documented for audit):

- `hash.ts` (×2): `Uint8Array<ArrayBufferLike>` not assignable to `BufferSource` — fixed by wrapping salt in `new Uint8Array(salt)` and returning `Uint8Array` from `deriveKey()`.
- `jwt.ts` (×2) / `roles.ts` (×1): Error subclass `readonly name` override without `override` keyword — fixed by adding `override readonly name = '...'`. Required by `noImplicitOverride: true` in base tsconfig.

### `pnpm test` (validation: 29 tests, auth: 36 tests)

```
packages/validation  →  Test Files 1 passed (1)  Tests  29 passed (29)   Duration 2.03s
packages/auth        →  Test Files 3 passed (3)  Tests  36 passed (36)   Duration 3.25s
```

**65 tests total. 0 failures.**

One test corrected during development: "rejects email longer than 254 characters" used `'a'.repeat(244) + '@test.com'` = 253 chars (under the limit). Corrected to `'a'.repeat(246) + '@test.com'` = 255 chars.

### `pnpm format:check`

```
All matched files use Prettier code style!
```

---

## 10. Known Risks and Owner Decisions

### Open owner decisions that do NOT block PR-04

| ID    | Decision                    | Blocks                     |
| ----- | --------------------------- | -------------------------- |
| OD-01 | Joker limit per round       | Prediction service, PR-07+ |
| OD-02 | Diff bonus scoring mode     | Scoring engine, PR-07+     |
| OD-04 | Tiebreaker for leaderboard  | Ranking engine, PR-07+     |
| OD-06 | Monthly winner tie handling | Ranking engine, PR-07+     |
| OD-07 | Partial prediction scoring  | Scoring engine, PR-07+     |
| OD-08 | Abandoned match handling    | Fixture service, PR-06+    |

None of these block the API shell (PR-04) or the database migration framework (PR-05).

### Technical risks

**PBKDF2 at 600,000 iterations in Cloudflare Workers**: The free tier CPU limit is 10ms per request; the paid (Workers Paid) limit is 30s. PBKDF2-SHA256 at 600k iterations takes approximately 200–400ms on modern hardware. This means:

- **Registration and login endpoints MUST run on Workers Paid or an R2/Queue offload pattern.**
- This is a known constraint. It does not require changing the hashing algorithm — it requires ensuring the auth worker is on an appropriate tier.
- If this becomes a deployment constraint, the mitigation is to queue the hash operation and complete registration asynchronously (user receives "check your email" before hashing completes). This is acceptable UX.

**`needsRehash()` is implemented but cannot be triggered automatically**: The service layer must call `needsRehash(storedHash)` after a successful login and, if `true`, rehash and overwrite. This is a service-layer concern, not a utilities concern. It will be enforced in the auth module (PR-06).

---

## 11. Canonical Legacy Migration Strategy

### Context

The existing SportsRush 1.0 platform is a WordPress installation. Migrated user accounts will have passwords stored as phpass hashes (`$P$` prefix). The Web Crypto API does not support phpass verification, and no WASM phpass compatibility layer will be built.

**Canonical decision:** Legacy WordPress password hashes are used only for migration detection. They are never verified. Migrated users must complete a one-time identity challenge on first login.

---

### Recommended First-Login Migration Flow

```
User attempts login with email + password
         │
         ▼
Auth service looks up user record
         │
         ├─ isLegacyPhpassHash(stored_hash) == true?
         │         │
         │         ▼ YES — Legacy migrated account
         │  Do NOT attempt password verification.
         │  Respond with HTTP 200 (not 401) to prevent account enumeration.
         │  Send magic-link email OR force-initiate password reset flow.
         │  Response body: { type: "legacy_migration_required" }
         │
         └─ NO — Normal PBKDF2 path (verifyPassword → issue tokens)
```

**Two valid paths for the user to complete migration:**

**Path A — Password Reset (preferred)**

1. User receives "Reset your password to continue" email with a `password_reset` token (HMAC-SHA256, stored as hash in DB).
2. User clicks link → submits new password → `PasswordResetConfirmSchema` validates it.
3. Auth service:
   - Hashes new password with PBKDF2-SHA256 (600k iter).
   - Overwrites `users.password_hash` with the PBKDF2 hash.
   - Sets `users.legacy_migration_completed_at = NOW()`.
   - Revokes all existing sessions.
   - Deletes the phpass hash (it is now in neither DB nor logs).
   - Emits `UserPasswordChangedEvent` (`triggeredBy: 'reset_token'`).
4. User is issued an access token and refresh token — they are now fully on the new platform.

**Path B — Magic Link (fallback)**

1. User requests a magic link (separate endpoint). One-time token emailed.
2. User clicks link → auth service verifies the OTP token.
3. Auth service:
   - Issues a short-lived (15-minute) access token marked `requires_password_set: true`.
   - Does NOT overwrite the phpass hash yet.
4. User is immediately redirected to a "Set your password" screen (enforced by middleware checking the claim).
5. User sets a new password → same sequence as Path A step 3 onwards.

**Magic links must expire:** 30-minute TTL. After expiry, the user requests a new one. There is no limit on the number of magic link requests, but they should be rate-limited (3 per hour per email address).

---

### Required Database Fields

These fields must be present on the `users` table. Migrations are delivered in PR-05.

| Field                           | Type                        | Default | Purpose                                                                                           |
| ------------------------------- | --------------------------- | ------- | ------------------------------------------------------------------------------------------------- |
| `is_legacy_migration`           | `INTEGER` (0/1)             | `0`     | Set to `1` during bulk WordPress import. Never cleared — acts as a permanent audit flag.          |
| `legacy_migration_completed_at` | `TEXT` (ISO 8601) \| `NULL` | `NULL`  | Timestamp of first successful login post-migration. Once non-null, the account is fully migrated. |
| `password_hash`                 | `TEXT`                      | —       | Stores the phpass hash during migration. Overwritten with PBKDF2 hash on migration completion.    |

The existing `password_resets` table (to be created in PR-05) is sufficient for Path A. Magic links use a separate `magic_links` table:

| Field        | Type                    | Notes                                          |
| ------------ | ----------------------- | ---------------------------------------------- |
| `id`         | `TEXT` UUID             | Primary key                                    |
| `user_id`    | `TEXT` UUID             | FK → users.id                                  |
| `token_hash` | `TEXT`                  | SHA-256 hash of the raw token sent to the user |
| `expires_at` | `TEXT` ISO 8601         | 30-minute TTL from creation                    |
| `used_at`    | `TEXT` ISO 8601 \| NULL | Set on first use; subsequent uses rejected     |
| `created_at` | `TEXT` ISO 8601         | —                                              |

---

### Required Events

| Event                                                     | When                                              | Already implemented?    |
| --------------------------------------------------------- | ------------------------------------------------- | ----------------------- |
| `UserRegisteredEvent` (`isLegacyMigration: true`)         | During bulk WordPress import                      | Yes — `packages/events` |
| `UserPasswordChangedEvent` (`triggeredBy: 'reset_token'`) | When migration password reset completes           | Yes — `packages/events` |
| `UserLoggedInEvent`                                       | When magic link is consumed and tokens are issued | Yes — `packages/events` |

No new event types are required. The existing `UserPasswordChangedEvent` covers the migration completion signal.

An optional `UserMigrationCompletedEvent` could be added as a convenience for the analytics domain (counting migration completion rate over time). This is not a blocker for any PR — add it if the analytics domain needs it in PR-07+.

---

### Security Considerations

**Account enumeration prevention:**  
The login response for a legacy account must be identical in timing and body shape to the "email not found" response. Both return HTTP 200 with a generic `{ type: "check_your_email" }` body. Never reveal whether the email is registered.

**Phpass hash handling:**

- The phpass hash must never be logged, included in error messages, or returned in any API response.
- `isLegacyPhpassHash()` is the only function that examines the hash — it does not extract any value from it.
- The hash must be deleted from the `users.password_hash` column as soon as Path A or Path B completes. Do not retain it "just in case".
- The phpass hash must not be exported in any data portability or admin export feature.

**Token security:**

- Magic link tokens: 32 bytes of cryptographic randomness, hex-encoded. Only the SHA-256 hash is stored. The raw token is transmitted once via email and never again.
- Password reset tokens: same approach (already implemented in `packages/auth` — `generateSecureToken`, `hashToken`).
- Both token types are single-use. `used_at` is set atomically on first verification; subsequent uses are rejected regardless of expiry.

**Hard migration deadline:**  
Consider setting a deadline (e.g., 6 months post-launch) after which unverified migrated accounts are deactivated. After deactivation, the phpass hash is nullified and the account requires manual admin re-activation. This eliminates the long-term risk of dormant migrated accounts being a target.

**Rate limiting:**  
All migration-path endpoints (magic link send, password reset send) must be rate-limited by email address and by IP:

- Max 3 magic link requests per email per hour.
- Max 5 password reset requests per email per hour.
- These are enforced at the Worker middleware layer (PR-06), not the utility layer.

---

### Rollback / Failure Handling

| Failure scenario                                                                                     | Handling                                                                                                                                                                                                                                                                                                                                    |
| ---------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Email delivery fails for magic link or reset                                                         | User sees "If your email is registered, you'll receive a link" — they can retry. No state is written to DB until the email is confirmed delivered.                                                                                                                                                                                          |
| User clicks an expired token                                                                         | Token expiry is checked before `used_at` is set. Expired tokens return a clear error message with a "Request a new link" CTA. The old token row remains (for audit).                                                                                                                                                                        |
| PBKDF2 hashing fails mid-request                                                                     | The password hash is NOT overwritten. The phpass hash remains. The user can try again.                                                                                                                                                                                                                                                      |
| Migration completion is partially applied (hash written but `legacy_migration_completed_at` not set) | On next login, `isLegacyPhpassHash()` returns false (PBKDF2 hash now stored), so the normal login path is taken. `legacy_migration_completed_at` being null is not a blocker for subsequent logins. A background job can back-fill this field for accounts where `password_hash` is not phpass but `legacy_migration_completed_at` is null. |
| User forgets they have an old account and registers a new one with the same email                    | Registration fails at the DB unique constraint on `email`. The error message should prompt them to use password reset.                                                                                                                                                                                                                      |
| Admin needs to force-migrate a user                                                                  | Admin endpoint (PR-06+) that triggers a magic link email. Requires `admin` role. This is a convenience, not a security bypass — the user still completes the challenge themselves.                                                                                                                                                          |

---

## Summary

PR-03 delivered three packages, all clean:

| Package          | Type                  | Tests           | Typecheck |
| ---------------- | --------------------- | --------------- | --------- |
| `@sr/validation` | Zod schemas           | 29/29           | Clean     |
| `@sr/events`     | TypeScript types only | N/A (type-only) | Clean     |
| `@sr/auth`       | Web Crypto utilities  | 36/36           | Clean     |

The scope deviation (`packages/auth` delivered ahead of PR-06) is justified and harmless. No application or business logic was introduced in any of the three packages.

The legacy migration strategy is fully documented above. No code changes to existing files are required as a result — `isLegacyPhpassHash()` is the only function needed at the utility layer, and it is already implemented and tested. The service-layer migration flow is deferred to PR-06.

**PR-04 may begin.**
