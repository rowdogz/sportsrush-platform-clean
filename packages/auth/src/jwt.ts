/**
 * JWT utilities using the `jose` library.
 * jose is pure ESM and fully compatible with Cloudflare Workers, Node.js 20+,
 * and all modern runtimes. It uses the Web Crypto API internally.
 *
 * Algorithm: HS256 (HMAC-SHA256)
 * Secret: a raw string encoded as UTF-8 bytes (stored in an env secret / KV)
 *
 * The JWT secret must be at least 32 bytes (256 bits) of random data.
 * Generate with: openssl rand -base64 32
 */

import { SignJWT, jwtVerify, errors as joseErrors } from "jose";
import type { TokenPayload, Role } from "@sr/types";
import { ACCESS_TOKEN_EXPIRY_SECONDS } from "./constants";

// ── Secret encoding ────────────────────────────────────────────────────────────

function encodeSecret(secret: string): Uint8Array {
  return new TextEncoder().encode(secret);
}

// ── Token creation ─────────────────────────────────────────────────────────────

/**
 * Create a signed HS256 access token.
 *
 * @param claims  - userId, role, sessionId (exp/iat are set automatically)
 * @param secret  - JWT signing secret from environment (min 32 bytes as string)
 * @returns signed JWT string
 */
export async function createAccessToken(
  claims: {
    readonly userId: string;
    readonly role: Role;
    readonly sessionId: string;
  },
  secret: string,
): Promise<string> {
  return new SignJWT({
    userId: claims.userId,
    role: claims.role,
    sessionId: claims.sessionId,
  })
    .setProtectedHeader({ alg: "HS256", typ: "JWT" })
    .setIssuedAt()
    .setExpirationTime(`${ACCESS_TOKEN_EXPIRY_SECONDS}s`)
    .sign(encodeSecret(secret));
}

// ── Token verification ────────────────────────────────────────────────────────

export class TokenExpiredError extends Error {
  override readonly name = "TokenExpiredError";
  constructor() {
    super("Access token has expired");
  }
}

export class TokenInvalidError extends Error {
  override readonly name = "TokenInvalidError";
  constructor(detail?: string) {
    super(detail ? `Invalid access token: ${detail}` : "Invalid access token");
  }
}

/**
 * Verify a signed HS256 access token.
 *
 * Throws TokenExpiredError if the token has expired.
 * Throws TokenInvalidError if the token is malformed, has a bad signature,
 * or is missing required claims.
 *
 * On success, returns the validated TokenPayload.
 */
export async function verifyAccessToken(
  token: string,
  secret: string,
): Promise<TokenPayload> {
  try {
    const { payload } = await jwtVerify(token, encodeSecret(secret), {
      algorithms: ["HS256"],
    });

    // Validate required custom claims
    const { userId, role, sessionId, exp, iat } = payload;

    if (
      typeof userId !== "string" ||
      typeof role !== "string" ||
      typeof sessionId !== "string" ||
      typeof exp !== "number" ||
      typeof iat !== "number"
    ) {
      throw new TokenInvalidError("Missing required claims");
    }

    return {
      userId: userId as TokenPayload["userId"],
      role: role as Role,
      sessionId: sessionId as TokenPayload["sessionId"],
      exp,
      iat,
    };
  } catch (err) {
    if (err instanceof TokenExpiredError || err instanceof TokenInvalidError) {
      throw err;
    }
    if (err instanceof joseErrors.JWTExpired) {
      throw new TokenExpiredError();
    }
    if (
      err instanceof joseErrors.JWSInvalid ||
      err instanceof joseErrors.JWTInvalid
    ) {
      throw new TokenInvalidError();
    }
    throw new TokenInvalidError("Verification failed");
  }
}
