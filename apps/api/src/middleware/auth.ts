import type { MiddlewareHandler } from "hono";
import {
  verifyAccessToken,
  TokenExpiredError,
  TokenInvalidError,
} from "@sr/auth";
import type { HonoEnv } from "../env";
import { AuthenticationError, AuthorizationError } from "../lib/errors";
import type { Role } from "@sr/types";
import { hasRole } from "@sr/auth";

/**
 * Auth middleware shell for the SportsRush API.
 *
 * requireAuth() — verifies the Bearer token and sets c.var.user.
 *   Usage:  app.use('/v1/protected/*', requireAuth())
 *   Or:     route.get('/me', requireAuth(), handler)
 *
 * requireRole(role) — verifies the token AND asserts a minimum role level.
 *   Usage:  app.use('/v1/admin/*', requireRole('admin'))
 *
 * The JWT secret is read from c.env.JWT_SECRET (Cloudflare secret binding).
 *
 * Token format: Authorization: Bearer <hs256-jwt>
 *
 * Error codes:
 *   MISSING_TOKEN   — Authorization header absent or malformed
 *   TOKEN_EXPIRED   — Token signature valid but exp is in the past
 *   TOKEN_INVALID   — Signature invalid, claims missing, or format wrong
 *   FORBIDDEN       — Token valid but role is insufficient (requireRole only)
 */
export function requireAuth(): MiddlewareHandler<HonoEnv> {
  return async (c, next) => {
    const authHeader = c.req.header("authorization");

    if (!authHeader || !authHeader.startsWith("Bearer ")) {
      throw new AuthenticationError(
        "Authorization header is missing or malformed",
        "MISSING_TOKEN",
      );
    }

    const token = authHeader.slice(7); // strip "Bearer "

    const secret = c.env.JWT_SECRET;
    if (!secret) {
      // JWT_SECRET binding is missing — this is a misconfiguration, not a client error.
      // Fail with 500 to avoid accepting unauthenticated requests silently.
      throw new Error("JWT_SECRET environment binding is not configured");
    }

    try {
      const payload = await verifyAccessToken(token, secret);
      c.set("user", payload);
    } catch (err) {
      if (err instanceof TokenExpiredError) {
        throw new AuthenticationError(
          "Access token has expired",
          "TOKEN_EXPIRED",
        );
      }
      if (err instanceof TokenInvalidError) {
        throw new AuthenticationError(
          "Access token is invalid",
          "TOKEN_INVALID",
        );
      }
      throw err; // unexpected error — re-throw to the global handler
    }

    await next();
  };
}

/**
 * Verifies the Bearer token AND asserts that the user's role meets the minimum
 * required level. Composes requireAuth internally.
 *
 * @param minimumRole - The lowest role that is allowed to proceed.
 */
export function requireRole(minimumRole: Role): MiddlewareHandler<HonoEnv> {
  return async (c, next) => {
    // Re-use the requireAuth logic by calling it inline.
    await requireAuth()(c, async () => {
      const user = c.var.user;
      if (!user) {
        // Should never reach here if requireAuth() ran, but satisfies strict checks.
        throw new AuthenticationError();
      }
      if (!hasRole(user, minimumRole)) {
        throw new AuthorizationError(
          `This action requires the '${minimumRole}' role or higher`,
        );
      }
      await next();
    });
  };
}
