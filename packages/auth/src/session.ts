/**
 * Session utilities.
 *
 * Refresh tokens:
 *   - Generated as 32 random bytes, hex-encoded (64 character string)
 *   - Stored as SHA-256 hash (hex) in the DB — the raw value is sent to the client only
 *   - Transmitted via HTTP-only Secure cookie (web) or SecureStore (mobile)
 *
 * Session expiry:
 *   - Access tokens expire after 15 minutes (ACCESS_TOKEN_EXPIRY_SECONDS)
 *   - Refresh tokens expire after 30 days (REFRESH_TOKEN_EXPIRY_DAYS)
 *   - Revoking a session (logout) sets sessions.revoked_at immediately
 */

import { generateSecureToken, hashToken } from "./hash";
import { REFRESH_TOKEN_BYTES, REFRESH_TOKEN_EXPIRY_DAYS } from "./constants";

/**
 * Generate a new refresh token.
 * Returns both the raw token (to send to the client) and the hash (to store in DB).
 *
 * NEVER store the raw token. NEVER send the hash to the client.
 */
export async function generateRefreshToken(): Promise<{
  readonly raw: string;
  readonly hash: string;
  readonly expiresAt: Date;
}> {
  const raw = generateSecureToken(REFRESH_TOKEN_BYTES);
  const hash = await hashToken(raw);
  const expiresAt = new Date();
  expiresAt.setDate(expiresAt.getDate() + REFRESH_TOKEN_EXPIRY_DAYS);
  return { raw, hash, expiresAt };
}

/**
 * Given a raw refresh token from the client, produce the hash to look up in the DB.
 */
export async function hashRefreshToken(rawToken: string): Promise<string> {
  return hashToken(rawToken);
}

/**
 * Returns true if the session expiry timestamp is in the past.
 */
export function isSessionExpired(expiresAt: string | Date): boolean {
  const expiry =
    typeof expiresAt === "string" ? new Date(expiresAt) : expiresAt;
  return expiry.getTime() < Date.now();
}

/**
 * Returns true if the session has been explicitly revoked (logout).
 */
export function isSessionRevoked(revokedAt: string | null): boolean {
  return revokedAt !== null;
}
