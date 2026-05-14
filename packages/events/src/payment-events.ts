import type { UUID } from "@sr/types";
import type { BaseEvent } from "./base";

/**
 * Emitted after a Stripe payment_intent.succeeded webhook is processed and
 * the league membership has been granted (synchronously, within the webhook handler).
 * Used by the Analytics domain.
 */
export type PaymentCompletedEvent = BaseEvent & {
  readonly eventType: "payment.completed";
  readonly paymentEventId: UUID;
  readonly userId: UUID;
  readonly leagueId: UUID;
  readonly amountPence: number;
  readonly currency: string; // ISO 4217, e.g. 'gbp'
  readonly stripePaymentIntentId: string;
};

/**
 * Emitted after a Stripe charge.refunded webhook is processed.
 * The Payments domain will check whether to revoke the entitlement.
 */
export type PaymentRefundedEvent = BaseEvent & {
  readonly eventType: "payment.refunded";
  readonly paymentEventId: UUID;
  readonly userId: UUID;
  readonly leagueId: UUID;
  readonly amountPence: number;
  readonly currency: string;
  readonly stripeEventId: string;
};
