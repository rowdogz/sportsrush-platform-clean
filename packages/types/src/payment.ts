import type { UUID, Timestamp } from "./common";

/**
 * Lifecycle states of a payment through the Stripe webhook pipeline.
 */
export type PaymentStatus =
  | "pending" // payment intent created, not yet confirmed
  | "processing" // payment_intent.processing event received
  | "completed" // payment_intent.succeeded — access granted
  | "refunded" // charge.refunded — access should be revoked
  | "failed" // payment_intent.payment_failed
  | "disputed"; // charge.dispute.created — under review

/**
 * An immutable record of a Stripe payment event.
 *
 * Written when Stripe posts a webhook (payment_intent.succeeded, charge.refunded, etc.).
 * stripe_event_id ensures idempotency: if Stripe retries a webhook delivery,
 * the duplicate is detected via this field and ignored.
 *
 * The payment → entitlement grant is synchronous (within the webhook handler).
 * A paid user who cannot access their league is a support and revenue incident.
 *
 * Owned by the Payments domain. Admin-only read access.
 * See SPORTSRUSH_2_DOMAIN_MODEL.md — Domain 8 (Payments)
 * See PRIVATE_LEAGUES_AND_PAYMENTS.md — Payment Flow
 */
export type PaymentEvent = {
  readonly id: UUID;
  readonly userId: UUID;
  readonly leagueId: UUID;
  readonly stripeEventId: string; // Stripe event ID — idempotency key
  readonly stripePaymentIntentId: string | null;
  readonly amountPence: number; // amount in smallest currency unit (pence for GBP)
  readonly currency: string; // ISO 4217 currency code, e.g. 'gbp'
  readonly status: PaymentStatus;
  readonly createdAt: Timestamp;
};
