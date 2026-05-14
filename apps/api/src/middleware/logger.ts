import type { MiddlewareHandler } from "hono";
import type { HonoEnv } from "../env";

/**
 * Request logging + correlation ID middleware.
 *
 * Sets a correlation ID on every request, then logs the completed request.
 *
 * Correlation ID resolution order:
 *   1. X-Request-ID header (client-provided — allows end-to-end tracing)
 *   2. Generated UUID v4 (fallback when no client header is present)
 *
 * The correlation ID is:
 *   - Stored in c.var.correlationId for downstream middleware/handlers
 *   - Echoed in the X-Correlation-ID response header
 *
 * Structured log fields (written to console as JSON):
 *   correlationId, method, path, status, durationMs, environment
 *
 * Cloudflare Workers captures console output as structured log lines
 * in the Cloudflare Dashboard → Workers → Logs.
 */
export function makeLoggerMiddleware(): MiddlewareHandler<HonoEnv> {
  return async (c, next) => {
    const requestedAt = Date.now();

    // Resolve correlation ID
    const incomingId = c.req.header("x-request-id");
    const correlationId = incomingId ?? crypto.randomUUID();

    c.set("correlationId", correlationId);
    c.set("requestedAt", requestedAt);

    await next();

    const durationMs = Date.now() - requestedAt;
    const status = c.res.status;

    // Propagate correlation ID to the client
    c.header("X-Correlation-ID", correlationId);
    c.header("X-API-Version", c.env.API_VERSION);

    const logEntry = {
      correlationId,
      method: c.req.method,
      path: c.req.path,
      status,
      durationMs,
      environment: c.env.ENVIRONMENT,
    };

    if (status >= 500) {
      console.error(JSON.stringify(logEntry));
    } else if (status >= 400) {
      console.warn(JSON.stringify(logEntry));
    } else {
      console.log(JSON.stringify(logEntry));
    }
  };
}
