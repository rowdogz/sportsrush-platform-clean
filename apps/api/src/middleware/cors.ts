import { cors } from "hono/cors";
import type { MiddlewareHandler } from "hono";
import type { HonoEnv } from "../env";

/**
 * CORS middleware factory.
 *
 * Development:  allows all origins ('*')
 * Staging/Prod: restricts to the WEB_ORIGIN binding (set via wrangler secret)
 *
 * The OPTIONS preflight is handled automatically by hono/cors.
 *
 * Exposed headers:
 *   X-Correlation-ID — so clients can include it in bug reports
 *   X-API-Version    — so clients can detect API version changes
 */
export function makeCorsMiddleware(): MiddlewareHandler<HonoEnv> {
  return async (c, next) => {
    const environment = c.env.ENVIRONMENT;
    const webOrigin = c.env.WEB_ORIGIN;

    const allowedOrigins: string | string[] =
      environment === "development" || !webOrigin ? "*" : webOrigin;

    const handler = cors({
      origin: allowedOrigins,
      allowMethods: ["GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS"],
      allowHeaders: ["Content-Type", "Authorization", "X-Request-ID"],
      exposeHeaders: ["X-Correlation-ID", "X-API-Version"],
      maxAge: 86400, // 24 hours preflight cache
      credentials: environment !== "development",
    });

    return handler(c, next);
  };
}
