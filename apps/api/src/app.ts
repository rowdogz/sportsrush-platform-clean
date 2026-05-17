import { Hono } from "hono";
import type { HonoEnv } from "./env";
import { makeCorsMiddleware } from "./middleware/cors";
import { makeEnvValidationMiddleware } from "./middleware/env-validation";
import { makeLoggerMiddleware } from "./middleware/logger";
import {
  makeErrorHandler,
  makeNotFoundHandler,
} from "./middleware/error-handler";
import { registerRoutes } from "./routes/index";

/**
 * Hono app factory for the SportsRush API.
 *
 * Separated from src/index.ts so that tests can call createApp() and invoke
 * app.request() without needing the Cloudflare Workers runtime.
 *
 * Middleware registration order matters:
 *   1. Logger — must be first so every request gets a correlation ID,
 *      including requests that 404 or throw before reaching a handler.
 *   2. Environment validation — fail clearly when required bindings are unsafe.
 *   3. CORS — must precede route handlers so OPTIONS preflights are handled.
 *   4. Routes — all application routes.
 *   5. Error handler (onError) — catches thrown errors from any handler.
 *   6. Not-found handler (notFound) — fires when no route matched.
 */
export function createApp(): Hono<HonoEnv> {
  const app = new Hono<HonoEnv>();

  // ── Global middleware ───────────────────────────────────────────────────────
  app.use("*", makeLoggerMiddleware());
  app.use("*", makeEnvValidationMiddleware());
  app.use("*", makeCorsMiddleware());

  // ── Routes ──────────────────────────────────────────────────────────────────
  registerRoutes(app);

  // ── Error handling ──────────────────────────────────────────────────────────
  app.onError(makeErrorHandler());
  app.notFound(makeNotFoundHandler());

  return app;
}
