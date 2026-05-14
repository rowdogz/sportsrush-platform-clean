/**
 * Migration validation tests.
 *
 * Applies migrations to an in-memory SQLite database using sql.js (pure WASM,
 * no native bindings required) and asserts the resulting schema is correct.
 *
 * These tests catch:
 *   - Syntax errors in SQL migration files
 *   - Missing tables or indexes
 *   - Broken foreign key constraints (ON DELETE CASCADE / SET NULL)
 *   - Violated CHECK constraints (role values)
 *   - Violated UNIQUE constraints (email_normalized)
 *   - Single-use token field defaults
 *   - updated_at triggers firing (or correctly not firing) on UPDATE
 *
 * The test database applies all migrations in order, matching production.
 * Do not skip 0001 — it sets PRAGMA foreign_keys = ON for the session.
 */

import { describe, it, expect, beforeAll, afterAll } from "vitest";
import initSqlJs from "sql.js";
import type { Database, SqlJsStatic } from "sql.js";
import { readFileSync } from "node:fs";
import { resolve, dirname } from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";
import { createRequire } from "node:module";

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const MIGRATIONS_DIR = resolve(__dirname, "../../migrations");

const _require = createRequire(import.meta.url);

function readMigration(filename: string): string {
  return readFileSync(resolve(MIGRATIONS_DIR, filename), "utf-8");
}

/**
 * Initialise sql.js with an explicit WASM path so it resolves correctly
 * inside pnpm's virtual store, regardless of __dirname in the sql.js module.
 */
async function initSql(): Promise<SqlJsStatic> {
  const sqlJsEntry = _require.resolve("sql.js");
  const sqlJsDir = dirname(sqlJsEntry);

  return initSqlJs({
    locateFile: (file: string) => resolve(sqlJsDir, file),
  });
}

// ── shared database instance ──────────────────────────────────────────────────

let SQL: SqlJsStatic;
let db: Database;

beforeAll(async () => {
  SQL = await initSql();
  db = new SQL.Database();

  // Apply all migrations in order — same sequence as production wrangler apply.
  db.run(readMigration("0001_foundation.sql"));
  db.run(readMigration("0002_auth_schema.sql"));
});

afterAll(() => {
  db.close();
});

// ── helper: query sqlite_master ───────────────────────────────────────────────

function tableExists(name: string): boolean {
  const result = db.exec(
    `SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?`,
    [name],
  );
  return (
    result.length > 0 && result[0] !== undefined && result[0].values.length > 0
  );
}

function indexExists(name: string): boolean {
  const result = db.exec(
    `SELECT name FROM sqlite_master WHERE type = 'index' AND name = ?`,
    [name],
  );
  return (
    result.length > 0 && result[0] !== undefined && result[0].values.length > 0
  );
}

function columnNames(table: string): string[] {
  const result = db.exec(`PRAGMA table_info(${table})`);
  if (result.length === 0 || result[0] === undefined) return [];
  // PRAGMA table_info columns: cid | name | type | notnull | dflt_value | pk
  return result[0].values.map((row) => String(row[1]));
}

// ── table existence ───────────────────────────────────────────────────────────

describe("0002_auth_schema — tables", () => {
  const expectedTables = [
    "users",
    "user_profiles",
    "user_sessions",
    "magic_links",
    "oauth_accounts",
    "password_reset_tokens",
    "auth_audit_log",
  ];

  it.each(expectedTables)("creates table: %s", (table) => {
    expect(tableExists(table)).toBe(true);
  });
});

// ── column presence ───────────────────────────────────────────────────────────

describe("0002_auth_schema — column presence", () => {
  it("users has all required identity columns", () => {
    const cols = columnNames("users");
    expect(cols).toContain("id");
    expect(cols).toContain("email");
    expect(cols).toContain("email_normalized");
    expect(cols).toContain("email_verified_at");
    expect(cols).toContain("password_hash");
    expect(cols).toContain("role");
    expect(cols).toContain("is_active");
    expect(cols).toContain("created_at");
    expect(cols).toContain("updated_at");
  });

  it("users has all legacy migration columns", () => {
    const cols = columnNames("users");
    expect(cols).toContain("is_legacy_migration");
    expect(cols).toContain("legacy_wp_user_id");
    expect(cols).toContain("legacy_migration_completed_at");
  });

  it("user_profiles has required columns", () => {
    const cols = columnNames("user_profiles");
    expect(cols).toContain("user_id");
    expect(cols).toContain("display_name");
    expect(cols).toContain("avatar_url");
    expect(cols).toContain("timezone");
    expect(cols).toContain("created_at");
    expect(cols).toContain("updated_at");
  });

  it("user_sessions has token and metadata columns", () => {
    const cols = columnNames("user_sessions");
    expect(cols).toContain("id");
    expect(cols).toContain("user_id");
    expect(cols).toContain("refresh_token_hash");
    expect(cols).toContain("user_agent");
    expect(cols).toContain("ip_address");
    expect(cols).toContain("last_used_at");
    expect(cols).toContain("expires_at");
    expect(cols).toContain("revoked_at");
  });

  it("magic_links has all token lifecycle columns", () => {
    const cols = columnNames("magic_links");
    expect(cols).toContain("token_hash");
    expect(cols).toContain("email_normalized");
    expect(cols).toContain("expires_at");
    expect(cols).toContain("used_at");
  });

  it("auth_audit_log has no updated_at (append-only)", () => {
    const cols = columnNames("auth_audit_log");
    expect(cols).not.toContain("updated_at");
    expect(cols).toContain("created_at");
    expect(cols).toContain("event_type");
    expect(cols).toContain("metadata");
  });
});

// ── indexes ───────────────────────────────────────────────────────────────────

describe("0002_auth_schema — indexes", () => {
  const expectedIndexes = [
    "idx_users_role",
    "idx_users_legacy_wp_user_id",
    "idx_user_sessions_user_id",
    "idx_user_sessions_expires_at",
    "idx_magic_links_user_id",
    "idx_magic_links_expires_at",
    "idx_oauth_accounts_user_id",
    "idx_password_reset_tokens_user_id",
    "idx_auth_audit_log_user_id",
    "idx_auth_audit_log_event_type",
    "idx_auth_audit_log_created_at",
  ];

  it.each(expectedIndexes)("creates index: %s", (idx) => {
    expect(indexExists(idx)).toBe(true);
  });
});

// ── constraints ───────────────────────────────────────────────────────────────

describe("0002_auth_schema — constraints", () => {
  it("enforces unique email_normalized", () => {
    db.run(
      `INSERT INTO users (id, email, email_normalized) VALUES ('c-u1', 'Alice@example.com', 'alice@example.com')`,
    );

    expect(() => {
      db.run(
        `INSERT INTO users (id, email, email_normalized) VALUES ('c-u2', 'ALICE@example.com', 'alice@example.com')`,
      );
    }).toThrow();

    db.run(`DELETE FROM users WHERE id = 'c-u1'`);
  });

  it("rejects invalid role values via CHECK constraint", () => {
    expect(() => {
      db.run(
        `INSERT INTO users (id, email, email_normalized, role) VALUES ('c-u3', 'b@b.com', 'b@b.com', 'superuser')`,
      );
    }).toThrow();
  });

  it("accepts all valid role values", () => {
    const roles = ["user", "admin", "superadmin"];
    roles.forEach((role, i) => {
      expect(() => {
        db.run(
          `INSERT INTO users (id, email, email_normalized, role) VALUES (?, ?, ?, ?)`,
          [`c-role-${i}`, `${role}@test.com`, `${role}@test.com`, role],
        );
        db.run(`DELETE FROM users WHERE id = ?`, [`c-role-${i}`]);
      }).not.toThrow();
    });
  });

  it("enforces unique (provider, provider_user_id) on oauth_accounts", () => {
    db.run(
      `INSERT INTO users (id, email, email_normalized) VALUES ('c-oauth-u', 'oauth@test.com', 'oauth@test.com')`,
    );
    db.run(
      `INSERT INTO oauth_accounts (id, user_id, provider, provider_user_id) VALUES ('oa1', 'c-oauth-u', 'google', 'g-123')`,
    );

    expect(() => {
      db.run(
        `INSERT INTO oauth_accounts (id, user_id, provider, provider_user_id) VALUES ('oa2', 'c-oauth-u', 'google', 'g-123')`,
      );
    }).toThrow();

    db.run(`DELETE FROM users WHERE id = 'c-oauth-u'`);
  });
});

// ── foreign key behaviour ─────────────────────────────────────────────────────

describe("0002_auth_schema — foreign key cascades", () => {
  it("deleting a user cascades to user_sessions", () => {
    db.run(
      `INSERT INTO users (id, email, email_normalized) VALUES ('fk-u1', 'fk1@test.com', 'fk1@test.com')`,
    );
    db.run(
      `INSERT INTO user_sessions (id, user_id, refresh_token_hash, expires_at) VALUES ('fk-s1', 'fk-u1', 'rth-fk1', '2099-01-01T00:00:00.000Z')`,
    );

    db.run(`DELETE FROM users WHERE id = 'fk-u1'`);

    const result = db.exec(`SELECT id FROM user_sessions WHERE id = 'fk-s1'`);
    expect(result.length).toBe(0);
  });

  it("deleting a user cascades to magic_links", () => {
    db.run(
      `INSERT INTO users (id, email, email_normalized) VALUES ('fk-u2', 'fk2@test.com', 'fk2@test.com')`,
    );
    db.run(
      `INSERT INTO magic_links (id, user_id, token_hash, email_normalized, expires_at) VALUES ('fk-ml1', 'fk-u2', 'th-fk1', 'fk2@test.com', '2099-01-01T00:00:00.000Z')`,
    );

    db.run(`DELETE FROM users WHERE id = 'fk-u2'`);

    const result = db.exec(`SELECT id FROM magic_links WHERE id = 'fk-ml1'`);
    expect(result.length).toBe(0);
  });

  it("deleting a user cascades to password_reset_tokens", () => {
    db.run(
      `INSERT INTO users (id, email, email_normalized) VALUES ('fk-u3', 'fk3@test.com', 'fk3@test.com')`,
    );
    db.run(
      `INSERT INTO password_reset_tokens (id, user_id, token_hash, expires_at) VALUES ('fk-prt1', 'fk-u3', 'th-prt1', '2099-01-01T00:00:00.000Z')`,
    );

    db.run(`DELETE FROM users WHERE id = 'fk-u3'`);

    const result = db.exec(
      `SELECT id FROM password_reset_tokens WHERE id = 'fk-prt1'`,
    );
    expect(result.length).toBe(0);
  });

  it("deleting a user sets auth_audit_log.user_id to NULL (SET NULL)", () => {
    db.run(
      `INSERT INTO users (id, email, email_normalized) VALUES ('fk-u4', 'fk4@test.com', 'fk4@test.com')`,
    );
    db.run(
      `INSERT INTO auth_audit_log (id, user_id, event_type) VALUES ('fk-aal1', 'fk-u4', 'user.login_success')`,
    );

    db.run(`DELETE FROM users WHERE id = 'fk-u4'`);

    // Audit record is preserved, user_id is nulled
    const result = db.exec(
      `SELECT id, user_id FROM auth_audit_log WHERE id = 'fk-aal1'`,
    );
    expect(result[0]?.values[0]?.[0]).toBe("fk-aal1");
    expect(result[0]?.values[0]?.[1]).toBeNull();
  });
});

// ── single-use token defaults ─────────────────────────────────────────────────

describe("0002_auth_schema — token field defaults", () => {
  it("magic_links.used_at defaults to NULL", () => {
    db.run(
      `INSERT INTO users (id, email, email_normalized) VALUES ('tok-u1', 'tok1@test.com', 'tok1@test.com')`,
    );
    db.run(
      `INSERT INTO magic_links (id, user_id, token_hash, email_normalized, expires_at) VALUES ('tok-ml1', 'tok-u1', 'th-tok1', 'tok1@test.com', '2099-01-01T00:00:00.000Z')`,
    );

    const result = db.exec(
      `SELECT used_at FROM magic_links WHERE id = 'tok-ml1'`,
    );
    expect(result[0]?.values[0]?.[0]).toBeNull();

    db.run(`DELETE FROM users WHERE id = 'tok-u1'`);
  });

  it("password_reset_tokens.used_at defaults to NULL", () => {
    db.run(
      `INSERT INTO users (id, email, email_normalized) VALUES ('tok-u2', 'tok2@test.com', 'tok2@test.com')`,
    );
    db.run(
      `INSERT INTO password_reset_tokens (id, user_id, token_hash, expires_at) VALUES ('tok-prt1', 'tok-u2', 'th-tok2', '2099-01-01T00:00:00.000Z')`,
    );

    const result = db.exec(
      `SELECT used_at FROM password_reset_tokens WHERE id = 'tok-prt1'`,
    );
    expect(result[0]?.values[0]?.[0]).toBeNull();

    db.run(`DELETE FROM users WHERE id = 'tok-u2'`);
  });

  it("user_sessions.revoked_at defaults to NULL", () => {
    db.run(
      `INSERT INTO users (id, email, email_normalized) VALUES ('tok-u3', 'tok3@test.com', 'tok3@test.com')`,
    );
    db.run(
      `INSERT INTO user_sessions (id, user_id, refresh_token_hash, expires_at) VALUES ('tok-s1', 'tok-u3', 'rth-tok1', '2099-01-01T00:00:00.000Z')`,
    );

    const result = db.exec(
      `SELECT revoked_at FROM user_sessions WHERE id = 'tok-s1'`,
    );
    expect(result[0]?.values[0]?.[0]).toBeNull();

    db.run(`DELETE FROM users WHERE id = 'tok-u3'`);
  });

  it("legacy_wp_user_id defaults to NULL for native accounts", () => {
    db.run(
      `INSERT INTO users (id, email, email_normalized) VALUES ('tok-u4', 'tok4@test.com', 'tok4@test.com')`,
    );

    const result = db.exec(
      `SELECT legacy_wp_user_id, is_legacy_migration FROM users WHERE id = 'tok-u4'`,
    );
    expect(result[0]?.values[0]?.[0]).toBeNull();
    expect(result[0]?.values[0]?.[1]).toBe(0);

    db.run(`DELETE FROM users WHERE id = 'tok-u4'`);
  });
});

// ── updated_at triggers ───────────────────────────────────────────────────────
//
// Each test follows the same deterministic pattern:
//   1. INSERT a row with a known past timestamp as updated_at.
//   2. UPDATE the row WITHOUT explicitly setting updated_at.
//   3. Assert updated_at has changed (trigger fired and set it to now).
//
// The "no-override" tests verify the WHEN guard: when the application supplies
// an explicit updated_at in the UPDATE, the trigger must not overwrite it.

describe("0002_auth_schema — updated_at triggers", () => {
  // ── trigger existence ───────────────────────────────────────────────────────

  it.each([
    "trg_users_updated_at",
    "trg_user_profiles_updated_at",
    "trg_oauth_accounts_updated_at",
  ])("trigger exists in sqlite_master: %s", (triggerName) => {
    const result = db.exec(
      `SELECT name FROM sqlite_master WHERE type = 'trigger' AND name = ?`,
      [triggerName],
    );
    expect(result[0]?.values[0]?.[0]).toBe(triggerName);
  });

  // ── users ───────────────────────────────────────────────────────────────────

  it("trg_users_updated_at fires when UPDATE omits updated_at", () => {
    const oldTs = "2020-01-01T00:00:00.000Z";
    db.run(
      `INSERT INTO users (id, email, email_normalized, created_at, updated_at)
       VALUES ('trg-u1', 'trg1@test.com', 'trg1@test.com', ?, ?)`,
      [oldTs, oldTs],
    );

    db.run(`UPDATE users SET role = 'admin' WHERE id = 'trg-u1'`);

    const result = db.exec(`SELECT updated_at FROM users WHERE id = 'trg-u1'`);
    const updatedAt = result[0]?.values[0]?.[0] as string;

    expect(updatedAt).not.toBe(oldTs);
    expect(updatedAt).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/);

    db.run(`DELETE FROM users WHERE id = 'trg-u1'`);
  });

  it("trg_users_updated_at does NOT override an explicit updated_at", () => {
    const oldTs = "2020-01-01T00:00:00.000Z";
    const explicitTs = "2025-06-15T10:00:00.000Z";
    db.run(
      `INSERT INTO users (id, email, email_normalized, created_at, updated_at)
       VALUES ('trg-u2', 'trg2@test.com', 'trg2@test.com', ?, ?)`,
      [oldTs, oldTs],
    );

    db.run(
      `UPDATE users SET role = 'admin', updated_at = ? WHERE id = 'trg-u2'`,
      [explicitTs],
    );

    const result = db.exec(`SELECT updated_at FROM users WHERE id = 'trg-u2'`);
    expect(result[0]?.values[0]?.[0]).toBe(explicitTs);

    db.run(`DELETE FROM users WHERE id = 'trg-u2'`);
  });

  // ── user_profiles ───────────────────────────────────────────────────────────

  it("trg_user_profiles_updated_at fires when UPDATE omits updated_at", () => {
    const oldTs = "2020-01-01T00:00:00.000Z";
    db.run(
      `INSERT INTO users (id, email, email_normalized) VALUES ('trg-pu1', 'trgp1@test.com', 'trgp1@test.com')`,
    );
    db.run(
      `INSERT INTO user_profiles (user_id, display_name, created_at, updated_at)
       VALUES ('trg-pu1', 'Before', ?, ?)`,
      [oldTs, oldTs],
    );

    db.run(
      `UPDATE user_profiles SET display_name = 'After' WHERE user_id = 'trg-pu1'`,
    );

    const result = db.exec(
      `SELECT updated_at FROM user_profiles WHERE user_id = 'trg-pu1'`,
    );
    const updatedAt = result[0]?.values[0]?.[0] as string;

    expect(updatedAt).not.toBe(oldTs);
    expect(updatedAt).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/);

    db.run(`DELETE FROM users WHERE id = 'trg-pu1'`);
  });

  it("trg_user_profiles_updated_at does NOT override an explicit updated_at", () => {
    const oldTs = "2020-01-01T00:00:00.000Z";
    const explicitTs = "2025-06-15T10:00:00.000Z";
    db.run(
      `INSERT INTO users (id, email, email_normalized) VALUES ('trg-pu2', 'trgp2@test.com', 'trgp2@test.com')`,
    );
    db.run(
      `INSERT INTO user_profiles (user_id, display_name, created_at, updated_at)
       VALUES ('trg-pu2', 'Before', ?, ?)`,
      [oldTs, oldTs],
    );

    db.run(
      `UPDATE user_profiles SET display_name = 'After', updated_at = ? WHERE user_id = 'trg-pu2'`,
      [explicitTs],
    );

    const result = db.exec(
      `SELECT updated_at FROM user_profiles WHERE user_id = 'trg-pu2'`,
    );
    expect(result[0]?.values[0]?.[0]).toBe(explicitTs);

    db.run(`DELETE FROM users WHERE id = 'trg-pu2'`);
  });

  // ── oauth_accounts ──────────────────────────────────────────────────────────

  it("trg_oauth_accounts_updated_at fires when UPDATE omits updated_at", () => {
    const oldTs = "2020-01-01T00:00:00.000Z";
    db.run(
      `INSERT INTO users (id, email, email_normalized) VALUES ('trg-ou1', 'trgo1@test.com', 'trgo1@test.com')`,
    );
    db.run(
      `INSERT INTO oauth_accounts (id, user_id, provider, provider_user_id, created_at, updated_at)
       VALUES ('trg-oa1', 'trg-ou1', 'google', 'g-trg-1', ?, ?)`,
      [oldTs, oldTs],
    );

    db.run(
      `UPDATE oauth_accounts SET email = 'new@gmail.com' WHERE id = 'trg-oa1'`,
    );

    const result = db.exec(
      `SELECT updated_at FROM oauth_accounts WHERE id = 'trg-oa1'`,
    );
    const updatedAt = result[0]?.values[0]?.[0] as string;

    expect(updatedAt).not.toBe(oldTs);
    expect(updatedAt).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/);

    db.run(`DELETE FROM users WHERE id = 'trg-ou1'`);
  });

  it("trg_oauth_accounts_updated_at does NOT override an explicit updated_at", () => {
    const oldTs = "2020-01-01T00:00:00.000Z";
    const explicitTs = "2025-06-15T10:00:00.000Z";
    db.run(
      `INSERT INTO users (id, email, email_normalized) VALUES ('trg-ou2', 'trgo2@test.com', 'trgo2@test.com')`,
    );
    db.run(
      `INSERT INTO oauth_accounts (id, user_id, provider, provider_user_id, created_at, updated_at)
       VALUES ('trg-oa2', 'trg-ou2', 'google', 'g-trg-2', ?, ?)`,
      [oldTs, oldTs],
    );

    db.run(
      `UPDATE oauth_accounts SET email = 'new@gmail.com', updated_at = ? WHERE id = 'trg-oa2'`,
      [explicitTs],
    );

    const result = db.exec(
      `SELECT updated_at FROM oauth_accounts WHERE id = 'trg-oa2'`,
    );
    expect(result[0]?.values[0]?.[0]).toBe(explicitTs);

    db.run(`DELETE FROM users WHERE id = 'trg-ou2'`);
  });
});
