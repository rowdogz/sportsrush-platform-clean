import { Hono } from "hono";
import type { HonoEnv } from "../env";
import { healthRoutes } from "./health";
import { authRoutes } from "./auth";
import { adminRoutes } from "./admin";
import { publicRoutes } from "./public";
import { privateLeagueRoutes } from "./private-leagues";
import { memberPrivateLeagueRoutes } from "./member-private-leagues";
import { predictionRoutes } from "./predictions";

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
 *   PR-11: /v1/admin/*  (admin competitions, seasons, teams slice)
 *   PR-39: /v1/public/* (public read API foundation)
 *   PR-41: /v1/admin/private-leagues, /v1/predictions
 *   PR-47: /v1/private-leagues (authenticated member league UX)
 */
export function registerRoutes(app: Hono<HonoEnv>): void {
  // Unversioned system routes
  app.route("/", healthRoutes);

  // Versioned API routes
  const v1 = new Hono<HonoEnv>();
  v1.route("/auth", authRoutes);
  v1.route("/admin", adminRoutes);
  v1.route("/admin/private-leagues", privateLeagueRoutes);
  v1.route("/private-leagues", memberPrivateLeagueRoutes);
  v1.route("/predictions", predictionRoutes);
  v1.route("/public", publicRoutes);
  app.route("/v1", v1);
}
