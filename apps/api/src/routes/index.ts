import { Hono } from "hono";
import type { HonoEnv } from "../env";
import { healthRoutes } from "./health";
import { authRoutes } from "./auth";

/**
 * Route aggregator — mounts all route groups onto the main Hono app.
 *
 * All API endpoints are versioned under /v1.
 * The system/observability routes (health, version, ready) are unversioned
 * to align with standard infrastructure probing conventions.
 *
 * Route groups added per PR:
 *   PR-04: /health, /version, /ready, /openapi.json
 *   PR-07: /v1/auth/*   (register, login, logout, refresh, password reset, magic link, me)
 *   PR-08+: /v1/competitions/*, /v1/fixtures/*, etc.
 */
export function registerRoutes(app: Hono<HonoEnv>): void {
  // Unversioned system routes
  app.route("/", healthRoutes);

  // Versioned API routes
  const v1 = new Hono<HonoEnv>();
  v1.route("/auth", authRoutes);
  app.route("/v1", v1);
}
