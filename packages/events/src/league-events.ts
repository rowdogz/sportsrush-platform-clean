import type { UUID } from "@sr/types";
import type { BaseEvent } from "./base";

/**
 * Emitted when a user joins a private league (by admin invite or after payment).
 * Triggers: NotificationLog push: "Welcome to {league}"
 */
export type LeagueMemberJoinedEvent = BaseEvent & {
  readonly eventType: "league.member_joined";
  readonly leagueId: UUID;
  readonly userId: UUID;
  readonly grantedBy: "admin" | "payment" | "migration";
  readonly paymentEventId: UUID | null; // populated when grantedBy = 'payment'
};
