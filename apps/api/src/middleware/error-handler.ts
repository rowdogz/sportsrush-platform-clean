import type { ErrorHandler } from "hono";
import { HTTPException } from "hono/http-exception";
import type { HonoEnv } from "../env";
import { AppError } from "../lib/errors";
import { errorBody } from "../lib/response";

/**
 * Global error handler for the Hono application.
 *
 * Registered via app.onError(makeErrorHandler()).
 *
 * Error classification:
 *   AppError subclass       → use statusCode and code from the error
 *   Hono HTTPException      → use the exception's status
 *   Unknown / unexpected    → 500, code INTERNAL_ERROR
 *
 * In non-development environments, the raw error message is suppressed for
 * 500 errors to avoid leaking implementation details.
 *
 * The correlation ID is included in the error response body so clients can
 * provide it when reporting bugs.
 */
export function makeErrorHandler(): ErrorHandler<HonoEnv> {
  return (err, c) => {
    const correlationId = c.var.correlationId ?? "unknown";
    const environment = c.env?.ENVIRONMENT ?? "development";

    // ── Known application errors ──────────────────────────────────────────────
    if (err instanceof AppError) {
      console.warn(
        JSON.stringify({
          level: "warn",
          correlationId,
          errorCode: err.code,
          statusCode: err.statusCode,
          message: err.message,
        }),
      );
      return c.json(
        errorBody(err.code, err.message, correlationId, err.details),
        err.statusCode as Parameters<typeof c.json>[1],
      );
    }

    // ── Hono built-in HTTP exceptions (thrown by framework internals) ─────────
    if (err instanceof HTTPException) {
      const message = err.message || "HTTP error";
      console.warn(
        JSON.stringify({
          level: "warn",
          correlationId,
          statusCode: err.status,
          message,
        }),
      );
      return c.json(
        errorBody("HTTP_ERROR", message, correlationId),
        err.status,
      );
    }

    // ── Unexpected errors ─────────────────────────────────────────────────────
    // Always log the full error internally.
    console.error(
      JSON.stringify({
        level: "error",
        correlationId,
        message: err instanceof Error ? err.message : String(err),
        stack: err instanceof Error ? err.stack : undefined,
      }),
    );

    // Never expose internal error details outside development.
    const publicMessage =
      environment === "development" && err instanceof Error
        ? err.message
        : "An unexpected error occurred";

    return c.json(
      errorBody("INTERNAL_ERROR", publicMessage, correlationId),
      500,
    );
  };
}

/**
 * 404 handler — registered via app.notFound().
 * Fires when no route matches the incoming request.
 */
export function makeNotFoundHandler(): Parameters<
  typeof import("hono").Hono.prototype.notFound
>[0] {
  return (c) => {
    const correlationId = c.var.correlationId ?? "unknown";
    return c.json(
      errorBody(
        "NOT_FOUND",
        `Route ${c.req.method} ${c.req.path} not found`,
        correlationId,
      ),
      404,
    );
  };
}
