import type { UUID, Timestamp } from "./common";

/**
 * All possible system event type strings used on the Cloudflare Queue.
 *
 * Full typed event payload types live in packages/events (built in a later PR).
 * This union is defined here so all packages can reference event type strings
 * without depending on packages/events.
 */
export type SystemEventType =
  // ── Fixtures & Results ──────────────────────────────────────────────────────
  | "result.published"
  | "result.corrected"
  | "match.created"
  | "match.rescheduled"
  | "match.status_changed"
  | "match.voided"
  // ── Scoring ─────────────────────────────────────────────────────────────────
  | "scores.recalculated"
  // ── Rankings ────────────────────────────────────────────────────────────────
  | "rankings.updated"
  | "monthly_winner.declared"
  // ── Predictions ─────────────────────────────────────────────────────────────
  | "prediction.saved"
  | "prediction.locked"
  // ── Private Leagues ──────────────────────────────────────────────────────────
  | "league.member_joined"
  // ── Payments ────────────────────────────────────────────────────────────────
  | "payment.completed"
  | "payment.refunded"
  // ── Identity ────────────────────────────────────────────────────────────────
  | "user.registered"
  | "user.email_verified"
  | "user.logged_in"
  | "user.logged_out"
  | "user.password_changed"
  | "user.display_name_changed"
  // ── External Integrations ───────────────────────────────────────────────────
  | "scraper.run_completed"
  | "scraper.alias_unresolved";

/**
 * Minimal envelope for any system event placed on the Cloudflare Queue.
 *
 * Every event has:
 *   - eventId     — UUID used for idempotency checking by consumers
 *   - eventType   — discriminant for routing to the correct handler
 *   - occurredAt  — UTC timestamp of when the event occurred (not when queued)
 *   - payload     — typed per eventType in packages/events
 *
 * Consumers must check eventId against a processed-events table before
 * acting, to handle at-least-once delivery from Cloudflare Queues.
 */
export type SystemEvent = {
  readonly eventId: UUID;
  readonly eventType: SystemEventType;
  readonly occurredAt: Timestamp;
  readonly payload: unknown; // fully typed in packages/events via discriminated union
};
