import type { UUID, Timestamp } from "@sr/types";

/**
 * Every domain event on the Cloudflare Queue extends this type.
 *
 * eventId  — UUID used for idempotency. Consumers MUST check this against a
 *             processed-events store before acting. Cloudflare Queues delivers
 *             at-least-once; duplicates will occur on retries.
 *
 * occurredAt — when the event happened (not when it was enqueued).
 *              Always UTC ISO 8601.
 */
export type BaseEvent = {
  readonly eventId: UUID;
  readonly occurredAt: Timestamp;
};
