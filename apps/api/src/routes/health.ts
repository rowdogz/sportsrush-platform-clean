import { Hono } from "hono";
import type { HonoEnv } from "../env";
import { ok, errorBody } from "../lib/response";
import { buildOpenApiSpec } from "../lib/openapi";
import { createDbClient } from "../lib/db";

/**
 * System / observability routes.
 *
 * GET /health       — Liveness probe. Always 200 if the Worker is running.
 *                     Never checks downstream dependencies.
 *
 * GET /version      — Returns version + environment. Useful for canary deployments.
 *
 * GET /ready        — Readiness probe.
 *                     Checks each bound dependency and returns its status.
 *                     Returns 200 when all bound checks pass.
 *                     Returns 503 when any bound check fails.
 *                     Checks not yet wired appear in pendingChecks (not failures).
 *
 *                     Current checks (PR-05):
 *                       db — D1 database (PRAGMA + SELECT 1 ping via createDbClient)
 *
 *                     Pending checks (future PRs):
 *                       kv — KV namespace (not yet wired)
 *
 * GET /openapi.json — OpenAPI 3.0 specification document.
 *
 * None of these routes require authentication.
 * Note: /ready uses createDbClient() directly (not requireDb()) because
 * an absent DB binding is a valid pre-setup state, not a server error.
 */
const health = new Hono<HonoEnv>();

health.get("/health", (c) => {
  return ok(c, {
    status: "ok" as const,
    service: "sportsrush-api",
    version: c.env.API_VERSION,
    environment: c.env.ENVIRONMENT,
  });
});

health.get("/version", (c) => {
  return ok(c, {
    version: c.env.API_VERSION,
    environment: c.env.ENVIRONMENT,
  });
});

health.get("/ready", async (c) => {
  const checks: Record<string, "ok" | "error"> = {};
  const pendingChecks: string[] = [];

  // ── D1 check ────────────────────────────────────────────────────────────────
  if (c.env.DB) {
    // createDbClient is awaited — it applies PRAGMA foreign_keys = ON first,
    // then ping() issues SELECT 1 to confirm connectivity.
    const db = await createDbClient(c.env.DB);
    const alive = await db.ping();
    checks.db = alive ? "ok" : "error";
  } else {
    // DB binding not yet configured for this environment.
    // This is expected in test environments and during initial local dev setup.
    pendingChecks.push("db");
  }

  // ── KV check — not yet wired (future PR) ────────────────────────────────────
  // if (c.env.KV) {
  //   ... kv ping ...
  // } else {
  //   pendingChecks.push('kv')
  // }

  // ── Result ───────────────────────────────────────────────────────────────────
  const hasFailure = Object.values(checks).some((v) => v === "error");

  if (hasFailure) {
    return c.json(
      errorBody(
        "SERVICE_UNAVAILABLE",
        "One or more dependency checks failed",
        c.var.correlationId,
        checks,
      ),
      503,
    );
  }

  return ok(c, {
    status: "ready" as const,
    checks,
    pendingChecks,
  });
});

health.get("/openapi.json", (c) => {
  const baseUrl = new URL(c.req.url).origin;
  const spec = buildOpenApiSpec(baseUrl);
  return c.json(spec);
});

export { health as healthRoutes };
