import { z } from "zod";

/**
 * Validates a UUID v4 string.
 * Use this for any ID field in request bodies (path params are typically validated by the router).
 */
export const UUIDSchema = z
  .string()
  .uuid({ message: "Must be a valid UUID v4" });

/**
 * Validates an ISO 8601 UTC timestamp string.
 * Rejects local times (offsets other than Z / +00:00).
 */
export const TimestampSchema = z
  .string()
  .datetime({ offset: true, message: "Must be an ISO 8601 UTC timestamp" });

/**
 * A reusable email schema with consistent normalisation.
 * Always lowercased; conforms to RFC 5321 max length.
 */
export const EmailSchema = z
  .string()
  .email({ message: "Must be a valid email address" })
  .max(254, { message: "Email must be 254 characters or fewer" })
  .toLowerCase();

/**
 * A non-empty string with leading/trailing whitespace stripped.
 */
export const NonEmptyStringSchema = z.string().min(1).trim();

/**
 * Standard pagination query parameters.
 * Uses z.coerce so query string integers ("1") are accepted.
 */
export const PaginationSchema = z.object({
  page: z.coerce
    .number({ invalid_type_error: "page must be a number" })
    .int()
    .min(1)
    .default(1),
  limit: z.coerce
    .number({ invalid_type_error: "limit must be a number" })
    .int()
    .min(1)
    .max(100)
    .default(20),
});

export type PaginationInput = z.infer<typeof PaginationSchema>;
