/**
 * Branded string type for UUID v4 values.
 * Prevents accidentally passing a plain string where a UUID is expected.
 *
 * In D1 (SQLite), UUIDs are stored as TEXT.
 * Generate with: crypto.randomUUID()
 */
export type UUID = string & { readonly _brand: "UUID" };

/**
 * Branded string type for ISO 8601 UTC timestamps.
 * Always UTC. Never a Date object. Never a local time.
 *
 * Example: "2025-03-15T18:00:00.000Z"
 *
 * In D1 (SQLite), timestamps are stored as ISO 8601 TEXT.
 */
export type Timestamp = string & { readonly _brand: "Timestamp" };

/**
 * Standard paginated API response envelope.
 */
export type PaginatedResponse<T> = {
  readonly data: readonly T[];
  readonly pagination: {
    readonly page: number;
    readonly limit: number;
    readonly total: number;
    readonly totalPages: number;
  };
};

/**
 * Standard API error shape returned by all endpoints.
 * The `code` field is machine-readable and stable across API versions.
 */
export type ApiError = {
  readonly code: string; // e.g. 'PREDICTION_LOCKED', 'MATCH_NOT_FOUND'
  readonly message: string; // human-readable
  readonly statusCode: number;
  readonly details?: unknown;
};

/**
 * Standard single-item API success response envelope.
 */
export type ApiResponse<T> = {
  readonly data: T;
};
