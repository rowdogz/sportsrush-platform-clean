import type { Context } from "hono";
import type { HonoEnv } from "../env";

/**
 * Standard API response envelope types.
 *
 * Every API response is one of:
 *   { data: T }                     — success
 *   { data: T, meta: M }            — success with pagination/cursor metadata
 *   { error: ErrorBody }            — failure
 *
 * Clients MUST check for the presence of `error` before accessing `data`.
 * Both keys are never present simultaneously.
 */

export type SuccessResponse<T> = {
  readonly data: T;
};

export type PaginatedResponse<T> = {
  readonly data: readonly T[];
  readonly meta: {
    readonly page: number;
    readonly limit: number;
    readonly total: number;
    readonly hasMore: boolean;
  };
};

export type ErrorResponse = {
  readonly error: {
    readonly code: string;
    readonly message: string;
    readonly details?: unknown;
    readonly correlationId?: string;
  };
};

// ── Response helpers ──────────────────────────────────────────────────────────

/**
 * Return a 200 OK JSON success response.
 */
export function ok<T>(c: Context<HonoEnv>, data: T, status: 200 | 201 = 200) {
  return c.json<SuccessResponse<T>>({ data }, status);
}

/**
 * Return a 201 Created JSON success response.
 */
export function created<T>(c: Context<HonoEnv>, data: T) {
  return ok(c, data, 201);
}

/**
 * Return a 204 No Content response.
 */
export function noContent(c: Context<HonoEnv>) {
  return c.body(null, 204);
}

/**
 * Return a paginated JSON success response.
 */
export function paginated<T>(
  c: Context<HonoEnv>,
  data: readonly T[],
  meta: PaginatedResponse<T>["meta"],
) {
  return c.json<PaginatedResponse<T>>({ data, meta });
}

/**
 * Serialise an error into the standard error response body.
 * Used by the error-handler middleware — not called directly in route handlers.
 */
export function errorBody(
  code: string,
  message: string,
  correlationId?: string,
  details?: unknown,
): ErrorResponse {
  return {
    error: {
      code,
      message,
      ...(details !== null && details !== undefined ? { details } : {}),
      ...(correlationId ? { correlationId } : {}),
    },
  };
}
