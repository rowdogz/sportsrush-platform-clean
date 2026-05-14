/**
 * Auth route integration tests.
 *
 * Exercises all 9 /v1/auth/* endpoints using a real in-memory SQLite database
 * (sql.js) wrapped in the D1 mock, with both migrations applied.
 *
 * PBKDF2 is replaced with an instant mock hash so that tests are fast:
 *   hashPassword(pw)           → "$mock-hash$<pw>"
 *   verifyPassword(pw, stored) → stored === "$mock-hash$<pw>"
 *   needsRehash()              → false
 * Real cryptographic functions (hashToken, generateSecureToken, JWT signing)
 * are NOT mocked and run with the Web Crypto API.
 *
 * All tests share one sql.js database created in beforeAll. Each test uses
 * unique email addresses (via counter) to avoid state conflicts.
 */

import { describe, it, expect, beforeAll, afterAll, vi } from "vitest";
import initSqlJs from "sql.js";
import type { Database, SqlJsStatic } from "sql.js";
import { readFileSync } from "node:fs";
import { resolve, dirname } from "node:path";
import { fileURLToPath } from "node:url";
import { createRequire } from "node:module";
import { createApp } from "../app";
import { createMockD1 } from "../lib/d1-mock";

// ── PBKDF2 mock ───────────────────────────────────────────────────────────────
// Must be declared at module level; vitest hoists vi.mock() before imports.

vi.mock("@sr/auth", async (importOriginal) => {
  const actual = await importOriginal<typeof import("@sr/auth")>();
  return {
    ...actual,
    hashPassword: vi.fn(async (pw: string) => `$mock-hash$${pw}`),
    verifyPassword: vi.fn(
      async (pw: string, stored: string) => stored === `$mock-hash$${pw}`,
    ),
    needsRehash: vi.fn(() => false),
  };
});

// ── sql.js setup helpers ──────────────────────────────────────────────────────

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const MIGRATIONS_DIR = resolve(__dirname, "../../migrations");
const _require = createRequire(import.meta.url);

function readMigration(name: string): string {
  return readFileSync(resolve(MIGRATIONS_DIR, name), "utf-8");
}

async function initSql(): Promise<SqlJsStatic> {
  const sqlJsEntry = _require.resolve("sql.js");
  const sqlJsDir = dirname(sqlJsEntry);
  return initSqlJs({ locateFile: (f: string) => resolve(sqlJsDir, f) });
}

// ── Shared state ──────────────────────────────────────────────────────────────

const TEST_JWT_SECRET = "test-secret-at-least-32-bytes-long!!";

let SQL: SqlJsStatic;
let sqlDb: Database;
let mockD1: D1Database;
const app = createApp();

let _counter = 0;
function uniqEmail(prefix = "user"): string {
  return `${prefix}-${++_counter}@test.sportsrush.com`;
}

// Test fixtures set up in beforeAll
let legacyEmail: string;
let inactiveEmail: string;

beforeAll(async () => {
  SQL = await initSql();
  sqlDb = new SQL.Database();

  // Enable FK enforcement for this test session (matches production behaviour)
  sqlDb.run("PRAGMA foreign_keys = ON");
  sqlDb.run(readMigration("0001_foundation.sql"));
  sqlDb.run(readMigration("0002_auth_schema.sql"));

  mockD1 = createMockD1(sqlDb);

  // ── Fixture: legacy (WordPress-migrated) user ─────────────────────────────
  // is_legacy_migration = 1, no legacy_migration_completed_at
  // password_hash is a $P$ phpass stub — must never be verified
  legacyEmail = uniqEmail("legacy");
  const now = new Date().toISOString();
  sqlDb.run(
    `INSERT INTO users
       (id, email, email_normalized, password_hash, role,
        is_active, is_legacy_migration, created_at, updated_at)
     VALUES (?, ?, ?, '$P$BwordpressHash', 'user', 1, 1, ?, ?)`,
    ["test-legacy-uid", legacyEmail, legacyEmail, now, now],
  );
  sqlDb.run(
    `INSERT INTO user_profiles (user_id, display_name, timezone, created_at, updated_at)
     VALUES ('test-legacy-uid', 'Legacy User', 'UTC', ?, ?)`,
    [now, now],
  );

  // ── Fixture: suspended user ───────────────────────────────────────────────
  inactiveEmail = uniqEmail("inactive");
  sqlDb.run(
    `INSERT INTO users
       (id, email, email_normalized, password_hash, role,
        is_active, is_legacy_migration, created_at, updated_at)
     VALUES (?, ?, ?, '$mock-hash$Password123!', 'user', 0, 0, ?, ?)`,
    ["test-inactive-uid", inactiveEmail, inactiveEmail, now, now],
  );
  sqlDb.run(
    `INSERT INTO user_profiles (user_id, display_name, timezone, created_at, updated_at)
     VALUES ('test-inactive-uid', 'Inactive User', 'UTC', ?, ?)`,
    [now, now],
  );
});

afterAll(() => {
  sqlDb.close();
});

// ── Request helper ────────────────────────────────────────────────────────────

type EnvOverride = { ENVIRONMENT?: "development" | "staging" | "production" };

async function req(
  method: string,
  path: string,
  body?: unknown,
  token?: string,
  envOverride?: EnvOverride,
) {
  const headers: Record<string, string> = {};
  if (body !== undefined) headers["Content-Type"] = "application/json";
  if (token !== undefined) headers["Authorization"] = `Bearer ${token}`;

  const env = {
    ENVIRONMENT: envOverride?.ENVIRONMENT ?? ("development" as const),
    API_VERSION: "0.0.1",
    JWT_SECRET: TEST_JWT_SECRET,
    DB: mockD1,
  };

  return app.request(
    `/v1/auth${path}`,
    {
      method,
      headers,
      ...(body !== undefined ? { body: JSON.stringify(body) } : {}),
    },
    env,
  );
}

/** Register a fresh user and return their tokens + metadata. */
async function createUser(prefix = "user") {
  const userEmail = uniqEmail(prefix);
  const res = await req("POST", "/register", {
    email: userEmail,
    password: "Password123!",
    displayName: "Test User",
  });
  expect(res.status).toBe(201);
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const body = (await res.json()) as any;
  return {
    email: userEmail,
    password: "Password123!",
    accessToken: body.data.accessToken as string,
    refreshToken: body.data.refreshToken as string,
    userId: body.data.user.id as string,
    sessionId: body.data.session.id as string,
  };
}

// ── Tests ─────────────────────────────────────────────────────────────────────

describe("POST /v1/auth/register", () => {
  it("creates a user and returns 201 with tokens and profile", async () => {
    const email = uniqEmail();
    const res = await req("POST", "/register", {
      email,
      password: "Password123!",
      displayName: "Alice",
    });
    expect(res.status).toBe(201);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { data } = (await res.json()) as any;
    expect(data.user.email).toBe(email);
    expect(data.user.role).toBe("user");
    expect(data.profile.displayName).toBe("Alice");
    expect(data.profile.timezone).toBe("UTC");
    expect(typeof data.accessToken).toBe("string");
    expect(typeof data.refreshToken).toBe("string");
    expect(typeof data.session.id).toBe("string");
    expect(typeof data.session.expiresAt).toBe("string");
  });

  it("returns 409 CONFLICT for a duplicate email", async () => {
    const email = uniqEmail();
    await req("POST", "/register", {
      email,
      password: "Password123!",
      displayName: "First",
    });
    const res = await req("POST", "/register", {
      email,
      password: "Password999!",
      displayName: "Second",
    });
    expect(res.status).toBe(409);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { error } = (await res.json()) as any;
    expect(error.code).toBe("CONFLICT");
  });

  it("returns 400 VALIDATION_ERROR when required fields are missing", async () => {
    const res = await req("POST", "/register", { email: "not-an-email" });
    expect(res.status).toBe(400);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { error } = (await res.json()) as any;
    expect(error.code).toBe("VALIDATION_ERROR");
  });

  it("returns 400 VALIDATION_ERROR for a password that is too short", async () => {
    const res = await req("POST", "/register", {
      email: uniqEmail(),
      password: "short",
      displayName: "Bob",
    });
    expect(res.status).toBe(400);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { error } = (await res.json()) as any;
    expect(error.code).toBe("VALIDATION_ERROR");
  });
});

describe("POST /v1/auth/login", () => {
  it("returns 200 with tokens for valid credentials", async () => {
    const { email, password } = await createUser();
    const res = await req("POST", "/login", { email, password });
    expect(res.status).toBe(200);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { data } = (await res.json()) as any;
    expect(data.user.email).toBe(email);
    expect(typeof data.accessToken).toBe("string");
    expect(typeof data.refreshToken).toBe("string");
  });

  it("returns 401 INVALID_CREDENTIALS for a wrong password", async () => {
    const { email } = await createUser();
    const res = await req("POST", "/login", { email, password: "WrongPass1!" });
    expect(res.status).toBe(401);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { error } = (await res.json()) as any;
    expect(error.code).toBe("INVALID_CREDENTIALS");
  });

  it("returns 401 INVALID_CREDENTIALS for an unknown email", async () => {
    const res = await req("POST", "/login", {
      email: "ghost@nowhere.com",
      password: "Password123!",
    });
    expect(res.status).toBe(401);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { error } = (await res.json()) as any;
    expect(error.code).toBe("INVALID_CREDENTIALS");
  });

  it("returns 401 MIGRATION_REQUIRED for a legacy user", async () => {
    const res = await req("POST", "/login", {
      email: legacyEmail,
      password: "anything",
    });
    expect(res.status).toBe(401);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { error } = (await res.json()) as any;
    expect(error.code).toBe("MIGRATION_REQUIRED");
  });

  it("returns 401 ACCOUNT_SUSPENDED for an inactive user", async () => {
    const res = await req("POST", "/login", {
      email: inactiveEmail,
      password: "Password123!",
    });
    expect(res.status).toBe(401);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { error } = (await res.json()) as any;
    expect(error.code).toBe("ACCOUNT_SUSPENDED");
  });

  it("returns 401 MIGRATION_REQUIRED when a $P$ phpass hash survives after migration (data-integrity guard)", async () => {
    // Simulates data corruption: legacy_migration_completed_at is set (migration
    // was supposedly finished) but the password_hash still starts with $P$.
    // The isLegacyPhpassHash() guard in the service must fire and block login
    // rather than letting verifyPassword silently return false (INVALID_CREDENTIALS).
    const corruptedEmail = uniqEmail("phpass-guard");
    const now = new Date().toISOString();
    sqlDb.run(
      `INSERT INTO users
         (id, email, email_normalized, password_hash, role,
          is_active, is_legacy_migration,
          legacy_migration_completed_at, created_at, updated_at)
       VALUES (?, ?, ?, '$P$BcorruptedPhpassHash', 'user', 1, 1, ?, ?, ?)`,
      ["test-phpass-guard-uid", corruptedEmail, corruptedEmail, now, now, now],
    );
    sqlDb.run(
      `INSERT INTO user_profiles
         (user_id, display_name, timezone, created_at, updated_at)
       VALUES ('test-phpass-guard-uid', 'Corrupted User', 'UTC', ?, ?)`,
      [now, now],
    );

    const res = await req("POST", "/login", {
      email: corruptedEmail,
      password: "anything",
    });
    expect(res.status).toBe(401);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { error } = (await res.json()) as any;
    expect(error.code).toBe("MIGRATION_REQUIRED");
  });
});

describe("POST /v1/auth/logout", () => {
  it("returns 204 and revokes the session", async () => {
    const { accessToken } = await createUser();
    const res = await req("POST", "/logout", undefined, accessToken);
    expect(res.status).toBe(204);
  });

  it("returns 401 MISSING_TOKEN without an Authorization header", async () => {
    const res = await req("POST", "/logout");
    expect(res.status).toBe(401);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { error } = (await res.json()) as any;
    expect(error.code).toBe("MISSING_TOKEN");
  });
});

describe("POST /v1/auth/refresh", () => {
  it("returns 200 with new tokens", async () => {
    const { refreshToken } = await createUser();
    const res = await req("POST", "/refresh", { refreshToken });
    expect(res.status).toBe(200);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { data } = (await res.json()) as any;
    expect(typeof data.accessToken).toBe("string");
    expect(typeof data.refreshToken).toBe("string");
    expect(typeof data.session.id).toBe("string");
  });

  it("rejects the old refresh token after rotation (single-use enforcement)", async () => {
    const { refreshToken: token1 } = await createUser();

    // Use token1 → should succeed, producing token2
    const res1 = await req("POST", "/refresh", { refreshToken: token1 });
    expect(res1.status).toBe(200);

    // Try token1 again → must fail (session revoked)
    const res2 = await req("POST", "/refresh", { refreshToken: token1 });
    expect(res2.status).toBe(401);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { error } = (await res2.json()) as any;
    expect(error.code).toBe("INVALID_TOKEN");
  });

  it("returns 401 INVALID_TOKEN for an unknown token", async () => {
    const res = await req("POST", "/refresh", {
      refreshToken: "deadbeef".repeat(8),
    });
    expect(res.status).toBe(401);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { error } = (await res.json()) as any;
    expect(error.code).toBe("INVALID_TOKEN");
  });
});

describe("POST /v1/auth/request-password-reset", () => {
  it("returns 200 with devToken in development mode for a known email", async () => {
    const { email } = await createUser();
    const res = await req("POST", "/request-password-reset", { email });
    expect(res.status).toBe(200);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { data } = (await res.json()) as any;
    expect(typeof data.message).toBe("string");
    expect(typeof data.devToken).toBe("string");
    expect(data.devToken.length).toBeGreaterThan(0);
  });

  it("returns 200 without devToken for an unknown email (anti-enumeration)", async () => {
    const res = await req("POST", "/request-password-reset", {
      email: "nobody@nowhere.com",
    });
    expect(res.status).toBe(200);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { data } = (await res.json()) as any;
    expect(typeof data.message).toBe("string");
    expect(data.devToken).toBeUndefined();
  });

  it("hides devToken in production mode even for a known email", async () => {
    const { email } = await createUser();
    const res = await req(
      "POST",
      "/request-password-reset",
      { email },
      undefined,
      { ENVIRONMENT: "production" },
    );
    expect(res.status).toBe(200);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { data } = (await res.json()) as any;
    expect(data.devToken).toBeUndefined();
  });
});

describe("POST /v1/auth/confirm-password-reset", () => {
  it("resets the password and returns an auth result", async () => {
    const { email } = await createUser();

    // Step 1 — request token
    const reqRes = await req("POST", "/request-password-reset", { email });
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { data: resetData } = (await reqRes.json()) as any;
    const token = resetData.devToken as string;

    // Step 2 — confirm with new password
    const confirmRes = await req("POST", "/confirm-password-reset", {
      token,
      newPassword: "NewPassword456!",
    });
    expect(confirmRes.status).toBe(200);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { data } = (await confirmRes.json()) as any;
    expect(data.user.email).toBe(email);
    expect(typeof data.accessToken).toBe("string");
    expect(typeof data.refreshToken).toBe("string");
  });

  it("returns 401 INVALID_TOKEN for a token that does not exist", async () => {
    const res = await req("POST", "/confirm-password-reset", {
      token: "nosuchtoken",
      newPassword: "NewPassword456!",
    });
    expect(res.status).toBe(401);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { error } = (await res.json()) as any;
    expect(error.code).toBe("INVALID_TOKEN");
  });

  it("returns 401 INVALID_TOKEN when the same token is used twice", async () => {
    const { email } = await createUser();
    const reqRes = await req("POST", "/request-password-reset", { email });
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { data: rd } = (await reqRes.json()) as any;
    const token = rd.devToken as string;

    // First use — should succeed
    const first = await req("POST", "/confirm-password-reset", {
      token,
      newPassword: "NewPassword456!",
    });
    expect(first.status).toBe(200);

    // Second use — must fail
    const second = await req("POST", "/confirm-password-reset", {
      token,
      newPassword: "AnotherPass789!",
    });
    expect(second.status).toBe(401);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { error } = (await second.json()) as any;
    expect(error.code).toBe("INVALID_TOKEN");
  });

  it("revokes all previous sessions after a password reset", async () => {
    const { refreshToken: oldRefreshToken } = await createUser();

    // Get the reset token
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { email } = await (async () => {
      // Re-register a fresh user for this scenario
      const e = uniqEmail("revoke-test");
      await req("POST", "/register", {
        email: e,
        password: "Password123!",
        displayName: "Revoke Test",
      });
      return { email: e };
    })();
    const reqRes = await req("POST", "/request-password-reset", { email });
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { data: rd } = (await reqRes.json()) as any;

    await req("POST", "/confirm-password-reset", {
      token: rd.devToken,
      newPassword: "NewPassword456!",
    });

    // The OLD refresh token (from before the reset) must now be rejected.
    // (oldRefreshToken belongs to a different user — we test the same-user flow below)
    // For the same user: request reset → confirm → try old refresh
    const { refreshToken: staleToken } = await createUser("stale");
    const staleEmail = uniqEmail("stale-reset");
    await req("POST", "/register", {
      email: staleEmail,
      password: "Password123!",
      displayName: "Stale",
    });
    const staleLoginRes = await req("POST", "/login", {
      email: staleEmail,
      password: "Password123!",
    });
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { data: staleLogin } = (await staleLoginRes.json()) as any;
    const staleRefreshToken = staleLogin.refreshToken as string;

    // Request + confirm reset for staleEmail
    const staleReqRes = await req("POST", "/request-password-reset", {
      email: staleEmail,
    });
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { data: staleRd } = (await staleReqRes.json()) as any;
    await req("POST", "/confirm-password-reset", {
      token: staleRd.devToken,
      newPassword: "ResetPass789!",
    });

    // The pre-reset refresh token must be rejected
    const reuseRes = await req("POST", "/refresh", {
      refreshToken: staleRefreshToken,
    });
    expect(reuseRes.status).toBe(401);
    void oldRefreshToken; // suppress unused-var warning for the outer fixture
    void staleToken;
  });
});

describe("POST /v1/auth/request-magic-link", () => {
  it("returns 200 with devToken in development mode for a known email", async () => {
    const { email } = await createUser();
    const res = await req("POST", "/request-magic-link", { email });
    expect(res.status).toBe(200);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { data } = (await res.json()) as any;
    expect(typeof data.message).toBe("string");
    expect(typeof data.devToken).toBe("string");
  });

  it("returns 200 without devToken for an unknown email", async () => {
    const res = await req("POST", "/request-magic-link", {
      email: "ghost@example.com",
    });
    expect(res.status).toBe(200);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { data } = (await res.json()) as any;
    expect(data.devToken).toBeUndefined();
  });
});

describe("POST /v1/auth/consume-magic-link", () => {
  it("consumes the token and returns an auth result", async () => {
    const { email } = await createUser();
    const reqRes = await req("POST", "/request-magic-link", { email });
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { data: ld } = (await reqRes.json()) as any;
    const token = ld.devToken as string;

    const consumeRes = await req("POST", "/consume-magic-link", { token });
    expect(consumeRes.status).toBe(200);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { data } = (await consumeRes.json()) as any;
    expect(data.user.email).toBe(email);
    expect(typeof data.accessToken).toBe("string");
    expect(typeof data.refreshToken).toBe("string");
  });

  it("returns 401 INVALID_TOKEN when the same magic link is used twice", async () => {
    const { email } = await createUser();
    const reqRes = await req("POST", "/request-magic-link", { email });
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { data: ld } = (await reqRes.json()) as any;
    const token = ld.devToken as string;

    const first = await req("POST", "/consume-magic-link", { token });
    expect(first.status).toBe(200);

    const second = await req("POST", "/consume-magic-link", { token });
    expect(second.status).toBe(401);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { error } = (await second.json()) as any;
    expect(error.code).toBe("INVALID_TOKEN");
  });

  it("returns 401 INVALID_TOKEN for a fabricated token", async () => {
    const res = await req("POST", "/consume-magic-link", {
      token: "fakefakefake",
    });
    expect(res.status).toBe(401);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { error } = (await res.json()) as any;
    expect(error.code).toBe("INVALID_TOKEN");
  });
});

describe("GET /v1/auth/me", () => {
  it("returns the authenticated user and their profile", async () => {
    const { email, accessToken } = await createUser();
    const res = await req("GET", "/me", undefined, accessToken);
    expect(res.status).toBe(200);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { data } = (await res.json()) as any;
    expect(data.user.email).toBe(email);
    expect(data.user.role).toBe("user");
    expect(typeof data.profile.displayName).toBe("string");
    expect(typeof data.profile.timezone).toBe("string");
  });

  it("returns 401 MISSING_TOKEN without an Authorization header", async () => {
    const res = await req("GET", "/me");
    expect(res.status).toBe(401);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { error } = (await res.json()) as any;
    expect(error.code).toBe("MISSING_TOKEN");
  });
});

// ── D1 mock batch atomicity ───────────────────────────────────────────────────
//
// D1 guarantees that batch() is all-or-nothing: if any statement throws the
// whole batch is rolled back. This describe block verifies that createMockD1()
// honours the same contract via SQLite BEGIN/COMMIT/ROLLBACK so that atomic
// write patterns (createUserWithProfile, rotateSession, …) are reliable in tests.

describe("D1 mock — batch atomicity", () => {
  it("rolls back all statements when any statement in the batch fails", async () => {
    const now = new Date().toISOString();

    // Snapshot row count before the failing batch.
    const before =
      (sqlDb.exec("SELECT COUNT(*) AS n FROM users")[0]?.values[0]?.[0] as
        | number
        | undefined) ?? 0;

    const id1 = `batch-atom-${Date.now()}`;
    const em1 = `batch-atom-${Date.now()}@test.com`;

    // Batch: statement 1 is a valid INSERT; statement 2 repeats the same
    // primary key, which forces a UNIQUE constraint violation.  Both must
    // be rolled back — the row count must be unchanged after the failure.
    await expect(
      mockD1.batch([
        mockD1
          .prepare(
            `INSERT INTO users
               (id, email, email_normalized, role, is_active,
                is_legacy_migration, created_at, updated_at)
             VALUES (?, ?, ?, 'user', 1, 0, ?, ?)`,
          )
          .bind(id1, em1, em1, now, now),
        // Duplicate primary key → UNIQUE constraint violation → ROLLBACK
        mockD1
          .prepare(
            `INSERT INTO users
               (id, email, email_normalized, role, is_active,
                is_legacy_migration, created_at, updated_at)
             VALUES (?, ?, ?, 'user', 1, 0, ?, ?)`,
          )
          .bind(id1, em1 + "-dup", em1 + "-dup", now, now),
      ]),
    ).rejects.toThrow();

    // Row count must be identical to pre-batch snapshot — rollback worked.
    const after =
      (sqlDb.exec("SELECT COUNT(*) AS n FROM users")[0]?.values[0]?.[0] as
        | number
        | undefined) ?? 0;
    expect(after).toBe(before);
  });
});
