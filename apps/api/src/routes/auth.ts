/**
 * Auth routes — 9 HTTP endpoints for the Identity & Auth domain.
 *
 * All routes are mounted under /v1/auth by routes/index.ts.
 *
 * Rate limiting:
 *   checkRateLimit() stubs are present on every mutation endpoint.
 *   They log only and do not block requests. A KV-backed implementation
 *   will replace these stubs in a future PR.
 *
 * Dev-only token exposure:
 *   Password reset and magic link tokens are normally delivered out-of-band
 *   (email / push). In ENVIRONMENT !== 'production', the raw token is also
 *   included in the JSON response under `data.devToken` for local testing.
 */

import { Hono } from "hono";
import type { HonoEnv } from "../env";
import { requireAuth } from "../middleware/auth";
import { requireDb } from "../lib/db";
import { ok, created, noContent } from "../lib/response";
import {
  AuthenticationError,
  ValidationError,
  InternalError,
} from "../lib/errors";
import {
  RegisterSchema,
  LoginSchema,
  RefreshTokenSchema,
  PasswordResetRequestSchema,
  PasswordResetConfirmSchema,
  MagicLinkRequestSchema,
  MagicLinkConsumeSchema,
} from "@sr/validation";
import {
  register,
  login,
  logout,
  refresh,
  requestPasswordReset,
  confirmPasswordReset,
  requestMagicLink,
  consumeMagicLink,
  getMe,
  type RequestContext,
} from "../auth/service";

// ── Helpers ───────────────────────────────────────────────────────────────────

function extractContext(c: Parameters<typeof ok>[0]): RequestContext {
  return {
    ipAddress:
      c.req.header("CF-Connecting-IP") ??
      c.req.header("X-Forwarded-For")?.split(",")[0]?.trim() ??
      null,
    userAgent: c.req.header("User-Agent") ?? null,
  };
}

function requireJwtSecret(c: Parameters<typeof ok>[0]): string {
  const secret = c.env.JWT_SECRET;
  if (!secret) throw new InternalError("JWT_SECRET is not configured");
  return secret;
}

// TODO(rate-limiting): Replace checkRateLimit() with a real KV-backed
// sliding-window implementation before this service handles production traffic.
// The current stub is a PLACEHOLDER ONLY — it provides NO protection against
// brute-force attacks, credential stuffing, or denial-of-service.
// Relevant endpoints: /register, /login, /request-password-reset,
// /request-magic-link, /consume-magic-link.
//
// Suggested implementation: Cloudflare Workers KV + sliding-window counter
// keyed by (action, ip) with per-action limits and exponential back-off.
function checkRateLimit(action: string, _key: string): void {
  // STUB: no-op. Does NOT block, throttle, count, or record any request.
  // The `_key` parameter is retained in the signature so that callers are
  // already passing the correct key type — switching to a real implementation
  // requires only the body of this function, not its call sites.
  console.debug(`[rate-limit stub] action=${action}`);
}

// ── Router ────────────────────────────────────────────────────────────────────

export const authRoutes = new Hono<HonoEnv>();

// POST /v1/auth/register ───────────────────────────────────────────────────────

authRoutes.post("/register", async (c) => {
  const raw = await c.req.json();
  const parsed = RegisterSchema.safeParse(raw);
  if (!parsed.success) {
    throw new ValidationError(
      "Invalid registration data",
      parsed.error.flatten().fieldErrors,
    );
  }

  checkRateLimit("register", parsed.data.email);

  const db = await requireDb(c);
  const jwtSecret = requireJwtSecret(c);
  const result = await register(db, jwtSecret, parsed.data, extractContext(c));

  return created(c, {
    user: result.user,
    profile: result.profile,
    accessToken: result.accessToken,
    refreshToken: result.refreshToken,
    session: result.session,
  });
});

// POST /v1/auth/login ──────────────────────────────────────────────────────────

authRoutes.post("/login", async (c) => {
  const raw = await c.req.json();
  const parsed = LoginSchema.safeParse(raw);
  if (!parsed.success) {
    throw new ValidationError(
      "Invalid login data",
      parsed.error.flatten().fieldErrors,
    );
  }

  checkRateLimit("login", parsed.data.email);

  const db = await requireDb(c);
  const jwtSecret = requireJwtSecret(c);
  const result = await login(db, jwtSecret, parsed.data, extractContext(c));

  return ok(c, {
    user: result.user,
    profile: result.profile,
    accessToken: result.accessToken,
    refreshToken: result.refreshToken,
    session: result.session,
  });
});

// POST /v1/auth/logout ─────────────────────────────────────────────────────────

authRoutes.post("/logout", requireAuth(), async (c) => {
  const user = c.var.user;
  if (user === undefined) throw new AuthenticationError();
  const { sessionId, userId } = user;
  const db = await requireDb(c);
  await logout(db, sessionId, userId);
  return noContent(c);
});

// POST /v1/auth/refresh ────────────────────────────────────────────────────────

authRoutes.post("/refresh", async (c) => {
  const raw = await c.req.json();
  const parsed = RefreshTokenSchema.safeParse(raw);
  if (!parsed.success) {
    throw new ValidationError(
      "Invalid request",
      parsed.error.flatten().fieldErrors,
    );
  }

  const db = await requireDb(c);
  const jwtSecret = requireJwtSecret(c);
  const result = await refresh(
    db,
    jwtSecret,
    parsed.data.refreshToken,
    extractContext(c),
  );

  return ok(c, {
    accessToken: result.accessToken,
    refreshToken: result.refreshToken,
    session: result.session,
  });
});

// POST /v1/auth/request-password-reset ────────────────────────────────────────

authRoutes.post("/request-password-reset", async (c) => {
  const raw = await c.req.json();
  const parsed = PasswordResetRequestSchema.safeParse(raw);
  if (!parsed.success) {
    throw new ValidationError(
      "Invalid request",
      parsed.error.flatten().fieldErrors,
    );
  }

  checkRateLimit("password-reset-request", parsed.data.email);

  const db = await requireDb(c);
  const result = await requestPasswordReset(db, parsed.data.email);
  const isDevMode = c.env.ENVIRONMENT !== "production";

  const message =
    "If an account with that email exists, a password reset link has been sent.";

  if (isDevMode && result.devToken !== null) {
    return ok(c, { message, devToken: result.devToken });
  }
  return ok(c, { message });
});

// POST /v1/auth/confirm-password-reset ────────────────────────────────────────

authRoutes.post("/confirm-password-reset", async (c) => {
  const raw = await c.req.json();
  const parsed = PasswordResetConfirmSchema.safeParse(raw);
  if (!parsed.success) {
    throw new ValidationError(
      "Invalid request",
      parsed.error.flatten().fieldErrors,
    );
  }

  const db = await requireDb(c);
  const jwtSecret = requireJwtSecret(c);
  const result = await confirmPasswordReset(
    db,
    jwtSecret,
    parsed.data.token,
    parsed.data.newPassword,
    extractContext(c),
  );

  return ok(c, {
    user: result.user,
    profile: result.profile,
    accessToken: result.accessToken,
    refreshToken: result.refreshToken,
    session: result.session,
  });
});

// POST /v1/auth/request-magic-link ────────────────────────────────────────────

authRoutes.post("/request-magic-link", async (c) => {
  const raw = await c.req.json();
  const parsed = MagicLinkRequestSchema.safeParse(raw);
  if (!parsed.success) {
    throw new ValidationError(
      "Invalid request",
      parsed.error.flatten().fieldErrors,
    );
  }

  checkRateLimit("magic-link-request", parsed.data.email);

  const db = await requireDb(c);
  const result = await requestMagicLink(db, parsed.data.email);
  const isDevMode = c.env.ENVIRONMENT !== "production";

  const message =
    "If an account with that email exists, a magic link has been sent.";

  if (isDevMode && result.devToken !== null) {
    return ok(c, { message, devToken: result.devToken });
  }
  return ok(c, { message });
});

// POST /v1/auth/consume-magic-link ────────────────────────────────────────────

authRoutes.post("/consume-magic-link", async (c) => {
  const raw = await c.req.json();
  const parsed = MagicLinkConsumeSchema.safeParse(raw);
  if (!parsed.success) {
    throw new ValidationError(
      "Invalid request",
      parsed.error.flatten().fieldErrors,
    );
  }

  checkRateLimit(
    "magic-link-consume",
    c.req.header("CF-Connecting-IP") ?? "unknown",
  );

  const db = await requireDb(c);
  const jwtSecret = requireJwtSecret(c);
  const result = await consumeMagicLink(
    db,
    jwtSecret,
    parsed.data.token,
    extractContext(c),
  );

  return ok(c, {
    user: result.user,
    profile: result.profile,
    accessToken: result.accessToken,
    refreshToken: result.refreshToken,
    session: result.session,
  });
});

// GET /v1/auth/me ─────────────────────────────────────────────────────────────

authRoutes.get("/me", requireAuth(), async (c) => {
  const user = c.var.user;
  if (user === undefined) throw new AuthenticationError();
  const { userId } = user;
  const db = await requireDb(c);
  const result = await getMe(db, userId);
  return ok(c, result);
});
