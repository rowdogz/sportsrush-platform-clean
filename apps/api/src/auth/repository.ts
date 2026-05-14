/**
 * Auth repository — thin typed wrappers around D1 SQL queries.
 *
 * Rules:
 * - NO business logic here. Every function is a generic data-access primitive.
 * - All functions accept a DbClient (from lib/db.ts) and return typed results.
 * - Row types use snake_case column names (matching the D1/SQLite schema).
 * - Domain type mappers (toUser, toProfile) live here so routes/services never
 *   hand-write type casts against raw DB columns.
 * - Batch operations (createUserWithProfile, rotateSession) group atomic writes
 *   using db.batch() — D1 batch is all-or-nothing.
 */

import type { DbClient } from "../lib/db";
import type { User, UserProfile, UUID, Timestamp, Role } from "@sr/types";

// ── DB row types ──────────────────────────────────────────────────────────────

export type UserRow = {
  readonly id: string;
  readonly email: string;
  readonly email_normalized: string;
  readonly email_verified_at: string | null;
  readonly password_hash: string | null;
  readonly role: string;
  readonly is_active: number;
  readonly is_legacy_migration: number;
  readonly legacy_wp_user_id: number | null;
  readonly legacy_migration_completed_at: string | null;
  readonly created_at: string;
  readonly updated_at: string;
};

export type UserProfileRow = {
  readonly user_id: string;
  readonly display_name: string;
  readonly avatar_url: string | null;
  readonly timezone: string;
  readonly created_at: string;
  readonly updated_at: string;
};

export type SessionRow = {
  readonly id: string;
  readonly user_id: string;
  readonly refresh_token_hash: string;
  readonly user_agent: string | null;
  readonly ip_address: string | null;
  readonly created_at: string;
  readonly last_used_at: string;
  readonly expires_at: string;
  readonly revoked_at: string | null;
};

export type MagicLinkRow = {
  readonly id: string;
  readonly user_id: string;
  readonly token_hash: string;
  readonly email_normalized: string;
  readonly created_at: string;
  readonly expires_at: string;
  readonly used_at: string | null;
};

export type PasswordResetRow = {
  readonly id: string;
  readonly user_id: string;
  readonly token_hash: string;
  readonly created_at: string;
  readonly expires_at: string;
  readonly used_at: string | null;
};

// ── Domain type mappers ───────────────────────────────────────────────────────

export function toUser(row: UserRow): User {
  return {
    id: row.id as UUID,
    email: row.email,
    emailVerifiedAt: row.email_verified_at as Timestamp | null,
    role: row.role as Role,
    createdAt: row.created_at as Timestamp,
    legacyWpUserId: row.legacy_wp_user_id,
  };
}

export function toProfile(row: UserProfileRow): UserProfile {
  return {
    userId: row.user_id as UUID,
    displayName: row.display_name,
    avatarUrl: row.avatar_url,
    timezone: row.timezone,
    createdAt: row.created_at as Timestamp,
    updatedAt: row.updated_at as Timestamp,
  };
}

// ── User queries ──────────────────────────────────────────────────────────────

export async function findUserByEmail(
  db: DbClient,
  emailNormalized: string,
): Promise<UserRow | null> {
  return db.queryOne<UserRow>(
    "SELECT * FROM users WHERE email_normalized = ?",
    [emailNormalized],
  );
}

export async function findUserById(
  db: DbClient,
  id: string,
): Promise<UserRow | null> {
  return db.queryOne<UserRow>("SELECT * FROM users WHERE id = ?", [id]);
}

export async function findProfileByUserId(
  db: DbClient,
  userId: string,
): Promise<UserProfileRow | null> {
  return db.queryOne<UserProfileRow>(
    "SELECT * FROM user_profiles WHERE user_id = ?",
    [userId],
  );
}

type CreateUserParams = {
  readonly user: {
    readonly id: string;
    readonly email: string;
    readonly emailNormalized: string;
    readonly passwordHash: string | null;
    readonly role: string;
  };
  readonly profile: {
    readonly displayName: string;
  };
  readonly audit: {
    readonly id: string;
    readonly ipAddress: string | null;
    readonly userAgent: string | null;
    readonly metadata: string | null;
  };
  readonly now: string;
};

/**
 * Atomically create a user, their profile, and the registration audit event.
 * Uses db.batch() so all three INSERTs succeed or all fail together.
 */
export async function createUserWithProfile(
  db: DbClient,
  p: CreateUserParams,
): Promise<void> {
  await db.batch([
    db
      .prepare(
        `INSERT INTO users
           (id, email, email_normalized, password_hash, role,
            is_active, is_legacy_migration, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, 1, 0, ?, ?)`,
      )
      .bind(
        p.user.id,
        p.user.email,
        p.user.emailNormalized,
        p.user.passwordHash,
        p.user.role,
        p.now,
        p.now,
      ),
    db
      .prepare(
        `INSERT INTO user_profiles
           (user_id, display_name, timezone, created_at, updated_at)
         VALUES (?, ?, 'UTC', ?, ?)`,
      )
      .bind(p.user.id, p.profile.displayName, p.now, p.now),
    db
      .prepare(
        `INSERT INTO auth_audit_log
           (id, user_id, event_type, ip_address, user_agent, metadata, created_at)
         VALUES (?, ?, 'user.registered', ?, ?, ?, ?)`,
      )
      .bind(
        p.audit.id,
        p.user.id,
        p.audit.ipAddress,
        p.audit.userAgent,
        p.audit.metadata,
        p.now,
      ),
  ]);
}

export async function setPasswordHash(
  db: DbClient,
  userId: string,
  hash: string,
  now: string,
): Promise<void> {
  await db.execute(
    "UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?",
    [hash, now, userId],
  );
}

export async function setEmailVerified(
  db: DbClient,
  userId: string,
  now: string,
): Promise<void> {
  await db.execute(
    `UPDATE users
     SET email_verified_at = ?, updated_at = ?
     WHERE id = ? AND email_verified_at IS NULL`,
    [now, now, userId],
  );
}

export async function setLegacyMigrationCompleted(
  db: DbClient,
  userId: string,
  now: string,
): Promise<void> {
  await db.execute(
    `UPDATE users
     SET legacy_migration_completed_at = ?, updated_at = ?
     WHERE id = ?`,
    [now, now, userId],
  );
}

// ── Session queries ───────────────────────────────────────────────────────────

export type SessionInsertParams = {
  readonly id: string;
  readonly userId: string;
  readonly refreshTokenHash: string;
  readonly userAgent: string | null;
  readonly ipAddress: string | null;
  readonly now: string;
  readonly expiresAt: string;
};

export async function insertSession(
  db: DbClient,
  p: SessionInsertParams,
): Promise<void> {
  await db.execute(
    `INSERT INTO user_sessions
       (id, user_id, refresh_token_hash, user_agent, ip_address,
        created_at, last_used_at, expires_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      p.id,
      p.userId,
      p.refreshTokenHash,
      p.userAgent,
      p.ipAddress,
      p.now,
      p.now,
      p.expiresAt,
    ],
  );
}

export async function findSessionByHash(
  db: DbClient,
  hash: string,
): Promise<SessionRow | null> {
  return db.queryOne<SessionRow>(
    "SELECT * FROM user_sessions WHERE refresh_token_hash = ?",
    [hash],
  );
}

export async function revokeSession(
  db: DbClient,
  sessionId: string,
  now: string,
): Promise<void> {
  await db.execute("UPDATE user_sessions SET revoked_at = ? WHERE id = ?", [
    now,
    sessionId,
  ]);
}

export async function revokeAllUserSessions(
  db: DbClient,
  userId: string,
  now: string,
): Promise<void> {
  await db.execute(
    `UPDATE user_sessions
     SET revoked_at = ?
     WHERE user_id = ? AND revoked_at IS NULL`,
    [now, userId],
  );
}

/**
 * Refresh token rotation: atomically revoke the old session and create the new
 * one so there is never a gap where neither session exists.
 */
export async function rotateSession(
  db: DbClient,
  oldSessionId: string,
  newSession: SessionInsertParams,
): Promise<void> {
  await db.batch([
    db
      .prepare("UPDATE user_sessions SET revoked_at = ? WHERE id = ?")
      .bind(newSession.now, oldSessionId),
    db
      .prepare(
        `INSERT INTO user_sessions
           (id, user_id, refresh_token_hash, user_agent, ip_address,
            created_at, last_used_at, expires_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
      )
      .bind(
        newSession.id,
        newSession.userId,
        newSession.refreshTokenHash,
        newSession.userAgent,
        newSession.ipAddress,
        newSession.now,
        newSession.now,
        newSession.expiresAt,
      ),
  ]);
}

// ── Magic link queries ────────────────────────────────────────────────────────

export type MagicLinkInsertParams = {
  readonly id: string;
  readonly userId: string;
  readonly tokenHash: string;
  readonly emailNormalized: string;
  readonly now: string;
  readonly expiresAt: string;
};

export async function insertMagicLink(
  db: DbClient,
  p: MagicLinkInsertParams,
): Promise<void> {
  await db.execute(
    `INSERT INTO magic_links
       (id, user_id, token_hash, email_normalized, created_at, expires_at)
     VALUES (?, ?, ?, ?, ?, ?)`,
    [p.id, p.userId, p.tokenHash, p.emailNormalized, p.now, p.expiresAt],
  );
}

export async function findMagicLinkByHash(
  db: DbClient,
  hash: string,
): Promise<MagicLinkRow | null> {
  return db.queryOne<MagicLinkRow>(
    "SELECT * FROM magic_links WHERE token_hash = ?",
    [hash],
  );
}

export async function consumeMagicLink(
  db: DbClient,
  id: string,
  now: string,
): Promise<void> {
  await db.execute("UPDATE magic_links SET used_at = ? WHERE id = ?", [
    now,
    id,
  ]);
}

// ── Password reset token queries ──────────────────────────────────────────────

export type PasswordResetInsertParams = {
  readonly id: string;
  readonly userId: string;
  readonly tokenHash: string;
  readonly now: string;
  readonly expiresAt: string;
};

export async function insertPasswordResetToken(
  db: DbClient,
  p: PasswordResetInsertParams,
): Promise<void> {
  await db.execute(
    `INSERT INTO password_reset_tokens
       (id, user_id, token_hash, created_at, expires_at)
     VALUES (?, ?, ?, ?, ?)`,
    [p.id, p.userId, p.tokenHash, p.now, p.expiresAt],
  );
}

export async function findPasswordResetByHash(
  db: DbClient,
  hash: string,
): Promise<PasswordResetRow | null> {
  return db.queryOne<PasswordResetRow>(
    "SELECT * FROM password_reset_tokens WHERE token_hash = ?",
    [hash],
  );
}

export async function consumePasswordResetToken(
  db: DbClient,
  id: string,
  now: string,
): Promise<void> {
  await db.execute(
    "UPDATE password_reset_tokens SET used_at = ? WHERE id = ?",
    [now, id],
  );
}

// ── Audit log ─────────────────────────────────────────────────────────────────

export type AuditLogParams = {
  readonly id: string;
  readonly userId: string | null;
  readonly eventType: string;
  readonly ipAddress: string | null;
  readonly userAgent: string | null;
  readonly metadata: string | null;
  readonly createdAt: string;
};

export async function insertAuthAuditLog(
  db: DbClient,
  p: AuditLogParams,
): Promise<void> {
  await db.execute(
    `INSERT INTO auth_audit_log
       (id, user_id, event_type, ip_address, user_agent, metadata, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?)`,
    [
      p.id,
      p.userId,
      p.eventType,
      p.ipAddress,
      p.userAgent,
      p.metadata,
      p.createdAt,
    ],
  );
}
