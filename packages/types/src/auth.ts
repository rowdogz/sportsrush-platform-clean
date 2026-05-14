import type { UUID, Timestamp } from "./common";

/**
 * All roles in the platform.
 * Hierarchy (highest to lowest): superadmin > admin > user
 */
export type Role = "user" | "admin" | "superadmin";

/**
 * Canonical User entity. Owned by the Identity & Auth domain.
 *
 * Password hashes are NEVER included in this type — they exist only in
 * the database layer and are never exposed through any API or service boundary.
 *
 * See SPORTSRUSH_2_DOMAIN_MODEL.md — Domain 1 (Identity & Auth)
 */
export type User = {
  readonly id: UUID;
  readonly email: string;
  readonly emailVerifiedAt: Timestamp | null;
  readonly role: Role;
  readonly createdAt: Timestamp;
  /**
   * Populated only for accounts migrated from WordPress.
   * Null for all natively-registered SR platform accounts.
   * Read-only after migration — never updated.
   */
  readonly legacyWpUserId: number | null;
};

/**
 * An authenticated session. Refresh tokens are stored hashed;
 * the raw token value is only ever held client-side (HTTP-only cookie on web,
 * SecureStore on mobile).
 */
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

/**
 * A password reset request. Single-use and short-lived (15 minutes).
 */
export type PasswordReset = {
  readonly id: UUID;
  readonly userId: UUID;
  readonly tokenHash: string;
  readonly expiresAt: Timestamp;
  readonly usedAt: Timestamp | null;
};

/**
 * An OAuth provider account linked to a user.
 */
export type OAuthAccount = {
  readonly id: UUID;
  readonly userId: UUID;
  readonly provider: string; // e.g. 'google', 'facebook'
  readonly providerUserId: string;
  readonly createdAt: Timestamp;
};

/**
 * JWT access token payload.
 * Kept minimal — no PII beyond user_id and role.
 * The full User record must be fetched from the DB when needed.
 *
 * exp and iat are standard JWT claim names (snake_case per RFC 7519).
 */
export type TokenPayload = {
  readonly userId: UUID;
  readonly role: Role;
  readonly sessionId: UUID;
  readonly exp: number; // Unix timestamp in seconds
  readonly iat: number; // Issued-at, Unix timestamp in seconds
};
