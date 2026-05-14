# Auth Security Notes — PR-07

Reviewed and verified during the PR-07 security pass. Covers all nine auth
endpoints (`/register`, `/login`, `/logout`, `/refresh`,
`/request-password-reset`, `/confirm-password-reset`, `/request-magic-link`,
`/consume-magic-link`, `/me`).

---

## 1. PBKDF2 Mock Is Test-Only

**Status: VERIFIED — no production exposure.**

The fast mock hashes (`$mock-hash$<password>`) exist exclusively in
`apps/api/src/routes/auth.test.ts` behind a `vi.mock('@sr/auth', ...)` call
that Vitest hoists before any module is loaded. The mock is never referenced
in `service.ts`, `routes/auth.ts`, or any non-test file.

Production and staging both exercise the real `hashPassword`/`verifyPassword`
from `packages/auth/src/hash.ts` (PBKDF2-SHA256, 600 000 iterations, 32-byte
random salt, Web Crypto API).

**Verification path:**

```
packages/auth/src/hash.ts   ← real PBKDF2 implementation
apps/api/src/auth/service.ts  ← imports from @sr/auth (real)
apps/api/src/routes/auth.test.ts  ← vi.mock('@sr/auth') only here
```

---

## 2. Production Password Hashing

**Status: VERIFIED — real PBKDF2 everywhere outside of Vitest.**

`service.ts` imports `hashPassword` and `verifyPassword` directly from
`@sr/auth`. The hash format is self-describing (`$pbkdf2-sha256$<iter>$<salt>$<hash>`),
which enables automatic rehashing on next login when the iteration count is
increased (via `needsRehash()`).

---

## 3. Refresh Token Rotation Atomicity

**Status: VERIFIED — atomic in both D1 and the sql.js mock.**

`rotateSession()` in `repository.ts` calls `db.batch([revokeOld, insertNew])`.

**D1 guarantee:** Cloudflare D1 executes a `batch()` as a single implicit
transaction. Either all statements succeed or the whole batch is rolled back
(documented in the Cloudflare D1 API reference).

**Mock guarantee:** `createMockD1()` in `lib/d1-mock.ts` wraps every
`batch()` in an explicit `BEGIN … COMMIT`, with `ROLLBACK` on any error.
This is verified by the test:

> _"D1 mock — batch atomicity: rolls back all statements when any statement
> in the batch fails"_ (`routes/auth.test.ts`)

The same atomicity applies to `createUserWithProfile()` (3-statement batch:
users + user_profiles + auth_audit_log).

---

## 4. No Raw Credentials in Logs

**Status: VERIFIED — no secrets, tokens, or hashes in any log output.**

Audit log metadata contains only:
| Event | Metadata fields |
|---|---|
| `user.registered` | `{ email }` — PII but not a secret |
| `user.login_success` | `{ sessionId }` — UUID, not a token |
| `user.login_failure` | `{ reason: "wrong_password" \| "legacy_migration_required" \| "phpass_hash_integrity_violation" }` |
| `user.logout` | `{ sessionId }` |
| `user.token_refreshed` | `{ oldSessionId, newSessionId }` |
| `user.password_reset_requested` | _(none)_ |
| `user.password_changed` | _(none)_ |
| `user.magic_link_requested` | _(none)_ |
| `user.magic_link_used` | _(none)_ |

`console.warn` in the audit helper logs only the `eventType` string, not any
metadata. The rate-limit stub uses `console.debug` (lowest level) with only
the action name — the rate-limit key (which may be a user email address) is
intentionally omitted from the log line to avoid PII leakage in production
Worker logs.

---

## 5. `devToken` Is Never Returned in Production

**Status: VERIFIED — production responses never contain `devToken`.**

Both `/request-password-reset` and `/request-magic-link` gate the field:

```typescript
if (c.env.ENVIRONMENT !== "production" && result.devToken !== null) {
  return ok(c, { message, devToken: result.devToken });
}
return ok(c, { message });
```

With `exactOptionalPropertyTypes: true` the `devToken` key is **structurally
absent** (not `undefined`) in the production branch — it cannot be observed
by clients even via `"devToken" in response`.

`devToken` **is** returned in `development` and `staging`. This is intentional:
staging requires token visibility for end-to-end test pipelines that run before
an email delivery service is wired up.

The service layer (`service.ts`) always computes and returns `devToken`
internally regardless of environment. The suppression lives entirely in the
route handler, which is the only external interface.

---

## 6. Legacy WordPress `$P$` Hash Verification Is Not Implemented

**Status: VERIFIED — two independent guards block `$P$` hashes from reaching
`verifyPassword()`.**

**Guard 1 (ACL-1, normal path):** Any user with
`is_legacy_migration = 1 AND legacy_migration_completed_at IS NULL` is blocked
before the password check and receives `MIGRATION_REQUIRED (401)`.

**Guard 2 (data-integrity path):** After the null-hash check, `isLegacyPhpassHash()`
is called on every stored hash before `verifyPassword()`. If a `$P$` hash is
present (possible only via data corruption after migration was marked complete),
the service throws `MIGRATION_REQUIRED` rather than `INVALID_CREDENTIALS`, and
logs the event as `phpass_hash_integrity_violation`.

This is verified by the test:

> _"returns 401 MIGRATION_REQUIRED when a \$P\$ phpass hash survives after
> migration (data-integrity guard)"_ (`routes/auth.test.ts`)

`verifyPassword()` itself would return `false` for a `$P$` hash (it only
understands `$pbkdf2-sha256$…`), so even if both guards were removed the
attacker would only receive `INVALID_CREDENTIALS`. The guards exist to
surface the data corruption clearly and direct users to the correct recovery
flow.

---

## 7. Legacy Account Existence Disclosure

**Status: INTENTIONAL TRADE-OFF — documented below.**

When a legacy user (unfinished migration) attempts to log in, the API returns
`401 MIGRATION_REQUIRED`. This response **does** implicitly confirm that the
email address is registered as a legacy account.

**Why this is acceptable:**

- Legacy accounts are a finite, known set imported from WordPress. Their
  existence is not a secret — users know they previously had a SportsRush 1.0
  account.
- Returning a generic `INVALID_CREDENTIALS` would silently fail and block
  legitimate users from discovering the migration flow.
- Enumeration of _non-legacy_ accounts is still prevented: unknown emails
  always receive `INVALID_CREDENTIALS` regardless of whether the email exists
  as a normal account.

**The `/request-password-reset` and `/request-magic-link` flows do not reveal
account existence** — they always return the same message:

> _"If an account with that email exists, a link has been sent."_

---

## 8. Audit Log Writes Do Not Leak Secrets

**Status: VERIFIED — see §4 above.**

Additional notes:

- Audit log writes are **best-effort**: failures are caught and logged with
  `console.warn(eventType)` only. A failed audit write never propagates to the
  caller and never changes the HTTP response code.
- The `auth_audit_log` table stores `ip_address` and `user_agent` as PII.
  This is intentional for fraud detection and is consistent with standard
  auth audit practice. Access to this table should be restricted at the
  database permission level.
- No password hashes, raw tokens, JWT secrets, or refresh tokens appear in
  any audit log metadata field. Grep-verifiable:
  ```
  grep -n "password_hash\|rawToken\|jwtSecret\|refreshToken\|accessToken" \
    apps/api/src/auth/service.ts | grep "auditLog"
  # Expected: no matches
  ```

---

## 9. Rate-Limit Stubs — NOT Real Protection

**Status: CLEARLY MARKED — action required before production traffic.**

`checkRateLimit()` in `routes/auth.ts` is a **no-op stub**. It logs only the
action name at `console.debug` level and does not block, count, or throttle
any request.

The stub is marked with a prominent `// TODO(rate-limiting):` comment and
will remain until a KV-backed sliding-window counter is implemented. Callers
already pass the correct key type so the implementation change is isolated to
the function body.

**Endpoints currently unprotected:**

- `POST /v1/auth/register` (credential stuffing, fake account creation)
- `POST /v1/auth/login` (brute-force, credential stuffing)
- `POST /v1/auth/request-password-reset` (email enumeration at scale)
- `POST /v1/auth/request-magic-link` (token flooding)
- `POST /v1/auth/consume-magic-link` (token guessing — mitigated by 64-char
  hex tokens with 256-bit entropy)

**Recommended implementation:** Cloudflare Workers KV, sliding-window counter
keyed by `(action, CF-Connecting-IP)`, with separate limits per action and
exponential back-off on repeated failures.

---

## Known Limitations / Future Work

| Item                                                | Tracking reference                                                                |
| --------------------------------------------------- | --------------------------------------------------------------------------------- |
| Rate limiting (see §9)                              | `TODO(rate-limiting)` in `routes/auth.ts`                                         |
| Email / push delivery for reset + magic-link tokens | PR-08+                                                                            |
| Access token revocation (short-circuit on logout)   | Currently limited to 15-min expiry; add a KV deny-list in a future PR if needed   |
| Session `last_used_at` is not updated on refresh    | Low priority; add in the refresh handler if session activity tracking is required |
| `WEB_ORIGIN` CORS is enforced at middleware level   | Not auth-specific; tracked in cors middleware                                     |
