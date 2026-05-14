/**
 * Password hashing using PBKDF2-SHA256 via the Web Crypto API.
 *
 * Compatible with: Cloudflare Workers, Deno, Node.js 20+, modern browsers.
 * No npm dependencies — uses the globally available `crypto.subtle`.
 *
 * Hash format (dollar-delimited, similar to PHC string format):
 *   $pbkdf2-sha256$<iterations>$<salt_base64url>$<hash_base64url>
 *
 * This format is self-describing: the iteration count is embedded so that
 * future increases can be detected and hashes re-upgraded on next login.
 */

import {
  PBKDF2_ITERATIONS,
  PBKDF2_KEY_LENGTH_BYTES,
  PBKDF2_SALT_LENGTH_BYTES,
} from "./constants";

// ── Base64url helpers ──────────────────────────────────────────────────────────

function toBase64url(bytes: Uint8Array): string {
  return btoa(String.fromCharCode(...bytes))
    .replace(/\+/g, "-")
    .replace(/\//g, "_")
    .replace(/=+$/, "");
}

function fromBase64url(str: string): Uint8Array {
  const padded = str
    .replace(/-/g, "+")
    .replace(/_/g, "/")
    .padEnd(str.length + ((4 - (str.length % 4)) % 4), "=");
  return Uint8Array.from(atob(padded), (c) => c.charCodeAt(0));
}

// ── PBKDF2 key derivation ─────────────────────────────────────────────────────

async function deriveKey(
  password: string,
  salt: Uint8Array,
  iterations: number,
): Promise<Uint8Array> {
  const keyMaterial = await crypto.subtle.importKey(
    "raw",
    new TextEncoder().encode(password),
    "PBKDF2",
    false,
    ["deriveBits"],
  );
  // Wrap salt in a fresh Uint8Array to guarantee ArrayBuffer backing (not SharedArrayBuffer).
  const safeSalt = new Uint8Array(salt);
  const bits = await crypto.subtle.deriveBits(
    { name: "PBKDF2", salt: safeSalt, iterations, hash: "SHA-256" },
    keyMaterial,
    PBKDF2_KEY_LENGTH_BYTES * 8,
  );
  return new Uint8Array(bits);
}

// ── Constant-time comparison ──────────────────────────────────────────────────

function constantTimeEqual(a: Uint8Array, b: Uint8Array): boolean {
  if (a.length !== b.length) return false;
  let diff = 0;
  for (let i = 0; i < a.length; i++) {
    diff |= (a[i] ?? 0) ^ (b[i] ?? 0);
  }
  return diff === 0;
}

// ── Public API ────────────────────────────────────────────────────────────────

/**
 * Hash a plaintext password. Returns a self-describing hash string.
 * This function is intentionally slow — do not call it in hot paths.
 */
export async function hashPassword(password: string): Promise<string> {
  const salt = crypto.getRandomValues(new Uint8Array(PBKDF2_SALT_LENGTH_BYTES));
  const derived = await deriveKey(password, salt, PBKDF2_ITERATIONS);
  // salt is already Uint8Array; derived is Uint8Array from deriveKey
  return `$pbkdf2-sha256$${PBKDF2_ITERATIONS}$${toBase64url(new Uint8Array(salt))}$${toBase64url(derived)}`;
}

/**
 * Verify a plaintext password against a stored PBKDF2 hash string.
 * Uses constant-time comparison to prevent timing attacks.
 * Returns false (not throws) on malformed hash strings.
 */
export async function verifyPassword(
  password: string,
  storedHash: string,
): Promise<boolean> {
  // Format: $pbkdf2-sha256$<iterations>$<salt_b64url>$<hash_b64url>
  const parts = storedHash.split("$");
  // After splitting '$pbkdf2-sha256$600000$salt$hash', parts is:
  // ['', 'pbkdf2-sha256', '600000', 'salt', 'hash']
  if (parts.length !== 5 || parts[1] !== "pbkdf2-sha256") {
    return false;
  }

  const iterations = parseInt(parts[2] ?? "0", 10);
  const saltStr = parts[3];
  const expectedStr = parts[4];

  if (
    !Number.isFinite(iterations) ||
    iterations < 1 ||
    !saltStr ||
    !expectedStr
  ) {
    return false;
  }

  const salt = fromBase64url(saltStr);
  const expected = fromBase64url(expectedStr);

  const derived = await deriveKey(password, salt, iterations);
  return constantTimeEqual(derived, expected);
}

/**
 * Returns true if the stored hash uses an older iteration count.
 * Call this after a successful verifyPassword() — if true, rehash immediately
 * and overwrite the stored value.
 */
export function needsRehash(storedHash: string): boolean {
  const parts = storedHash.split("$");
  if (parts.length !== 5 || parts[1] !== "pbkdf2-sha256") return true;
  const iterations = parseInt(parts[2] ?? "0", 10);
  return iterations < PBKDF2_ITERATIONS;
}

/**
 * Returns true if the stored hash is a legacy WordPress phpass hash ($P$).
 * These hashes cannot be verified in JavaScript without a WASM phpass port.
 * The auth service must handle these by delegating to a legacy verification
 * endpoint or a WASM module — see ACL-1 in SPORTSRUSH_2_DOMAIN_MODEL.md.
 */
export function isLegacyPhpassHash(storedHash: string): boolean {
  return storedHash.startsWith("$P$");
}

/**
 * Generate a cryptographically random token (hex-encoded).
 * Used for refresh tokens and password reset tokens.
 */
export function generateSecureToken(byteLength: number): string {
  const bytes = crypto.getRandomValues(new Uint8Array(byteLength));
  return Array.from(bytes)
    .map((b) => b.toString(16).padStart(2, "0"))
    .join("");
}

/**
 * Hash a token for storage (SHA-256, hex-encoded).
 * The raw token is sent to the client; only the hash is stored.
 */
export async function hashToken(token: string): Promise<string> {
  const digest = await crypto.subtle.digest(
    "SHA-256",
    new TextEncoder().encode(token),
  );
  return Array.from(new Uint8Array(digest))
    .map((b) => b.toString(16).padStart(2, "0"))
    .join("");
}
