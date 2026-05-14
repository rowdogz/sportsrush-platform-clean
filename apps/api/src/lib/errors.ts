/**
 * Typed HTTP error hierarchy for the SportsRush API.
 *
 * Usage in route handlers:
 *   throw new NotFoundError('Match not found')
 *   throw new ValidationError('Invalid input', issues)
 *   throw new AuthenticationError()
 *
 * The global error-handler middleware catches all AppError subclasses
 * and serialises them into the standard error response shape.
 * Unknown errors (non-AppError) produce a 500 Internal Server Error.
 */

export class AppError extends Error {
  override readonly name: string;
  readonly statusCode: number;
  readonly code: string;
  readonly details: unknown;

  constructor(
    statusCode: number,
    code: string,
    message: string,
    details?: unknown,
  ) {
    super(message);
    this.name = "AppError";
    this.statusCode = statusCode;
    this.code = code;
    this.details = details ?? null;
  }
}

/** 400 — request body or query param failed schema validation. */
export class ValidationError extends AppError {
  override readonly name = "ValidationError";
  constructor(message = "Validation failed", details?: unknown) {
    super(400, "VALIDATION_ERROR", message, details);
  }
}

/** 401 — no token provided, token expired, or token signature invalid. */
export class AuthenticationError extends AppError {
  override readonly name = "AuthenticationError";
  constructor(message = "Authentication required", code = "UNAUTHENTICATED") {
    super(401, code, message);
  }
}

/** 403 — token is valid but the role is insufficient. */
export class AuthorizationError extends AppError {
  override readonly name = "AuthorizationError";
  constructor(message = "Insufficient permissions") {
    super(403, "FORBIDDEN", message);
  }
}

/** 404 — resource not found. */
export class NotFoundError extends AppError {
  override readonly name = "NotFoundError";
  constructor(message = "Resource not found") {
    super(404, "NOT_FOUND", message);
  }
}

/** 409 — uniqueness conflict (duplicate email, duplicate entry, etc.). */
export class ConflictError extends AppError {
  override readonly name = "ConflictError";
  constructor(message = "Resource already exists") {
    super(409, "CONFLICT", message);
  }
}

/** 422 — request is well-formed but semantically invalid (business rule violation). */
export class UnprocessableError extends AppError {
  override readonly name = "UnprocessableError";
  constructor(message: string, details?: unknown) {
    super(422, "UNPROCESSABLE", message, details);
  }
}

/** 429 — rate limit exceeded. */
export class RateLimitError extends AppError {
  override readonly name = "RateLimitError";
  constructor(message = "Too many requests") {
    super(429, "RATE_LIMITED", message);
  }
}

/** 500 — unexpected internal error (never surfaces implementation details). */
export class InternalError extends AppError {
  override readonly name = "InternalError";
  constructor(message = "An unexpected error occurred") {
    super(500, "INTERNAL_ERROR", message);
  }
}
