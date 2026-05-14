/**
 * Auth timing constants.
 * All durations are in the unit indicated by the suffix.
 * Changing these values requires a corresponding update to the DB schema
 * (sessions.expires_at and password_resets.expires_at are pre-computed).
 */

export const ACCESS_TOKEN_EXPIRY_SECONDS = 15 * 60; // 15 minutes
export const REFRESH_TOKEN_EXPIRY_DAYS = 30; // 30 days
export const RESET_TOKEN_EXPIRY_MINUTES = 15; // 15 minutes

/**
 * Brute-force lockout: after MAX_LOGIN_ATTEMPTS consecutive failures
 * from the same IP/user, the account is locked for LOCKOUT_DURATION_MINUTES.
 */
export const MAX_LOGIN_ATTEMPTS = 5;
export const LOCKOUT_DURATION_MINUTES = 15;

/**
 * PBKDF2-SHA256 parameters.
 * OWASP 2023 recommendation: 600,000 iterations for PBKDF2-SHA256.
 * See: https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html
 *
 * WARNING: Increasing PBKDF2_ITERATIONS invalidates all existing hashes.
 * A migration that re-hashes on next login is required.
 */
export const PBKDF2_ITERATIONS = 600_000;
export const PBKDF2_KEY_LENGTH_BYTES = 32; // 256-bit derived key
export const PBKDF2_SALT_LENGTH_BYTES = 16; // 128-bit random salt

/**
 * Refresh token byte length before base64 encoding.
 * 32 bytes = 256 bits of entropy.
 */
export const REFRESH_TOKEN_BYTES = 32;

/**
 * Password reset token byte length before hex encoding.
 */
export const RESET_TOKEN_BYTES = 32;

/**
 * Magic link token byte length before hex encoding.
 * 32 bytes = 256 bits of entropy, same as reset tokens.
 */
export const MAGIC_LINK_TOKEN_BYTES = 32;
