/**
 * @sr/events — Typed Cloudflare Queue event payloads for the SportsRush platform.
 *
 * Usage:
 *   import type { DomainEvent, ResultPublishedEvent } from '@sr/events'
 *
 * Every event type:
 *   - Extends BaseEvent (eventId for idempotency, occurredAt timestamp)
 *   - Has a string literal eventType field for discriminated union matching
 *   - Has all fields readonly
 *   - Has no optional fields (absent data means the event didn't happen)
 *
 * The DomainEvent union is the type used by queue consumers:
 *   function handleEvent(event: DomainEvent) {
 *     switch (event.eventType) {
 *       case 'result.published': // event is ResultPublishedEvent here
 *     }
 *   }
 *
 * Event type strings match SystemEventType in @sr/types.
 */

export type * from "./base";
export type * from "./result-events";
export type * from "./scoring-events";
export type * from "./ranking-events";
export type * from "./prediction-events";
export type * from "./league-events";
export type * from "./payment-events";
export type * from "./identity-events";
export type * from "./scraper-events";

import type {
  ResultPublishedEvent,
  ResultCorrectedEvent,
} from "./result-events";
import type {
  MatchCreatedEvent,
  MatchRescheduledEvent,
  MatchStatusChangedEvent,
  MatchVoidedEvent,
} from "./result-events";
import type { ScoresRecalculatedEvent } from "./scoring-events";
import type {
  RankingsUpdatedEvent,
  MonthlyWinnerDeclaredEvent,
} from "./ranking-events";
import type {
  PredictionSavedEvent,
  PredictionLockedEvent,
} from "./prediction-events";
import type { LeagueMemberJoinedEvent } from "./league-events";
import type {
  PaymentCompletedEvent,
  PaymentRefundedEvent,
} from "./payment-events";
import type {
  UserRegisteredEvent,
  UserEmailVerifiedEvent,
  UserLoggedInEvent,
  UserLoggedOutEvent,
  UserPasswordChangedEvent,
  UserDisplayNameChangedEvent,
} from "./identity-events";
import type {
  ScraperRunCompletedEvent,
  AliasUnresolvedEvent,
} from "./scraper-events";

/**
 * Master discriminated union of all domain events.
 * Use this as the parameter type in queue consumer handlers.
 * TypeScript narrows the type automatically via the eventType field.
 */
export type DomainEvent =
  | ResultPublishedEvent
  | ResultCorrectedEvent
  | MatchCreatedEvent
  | MatchRescheduledEvent
  | MatchStatusChangedEvent
  | MatchVoidedEvent
  | ScoresRecalculatedEvent
  | RankingsUpdatedEvent
  | MonthlyWinnerDeclaredEvent
  | PredictionSavedEvent
  | PredictionLockedEvent
  | LeagueMemberJoinedEvent
  | PaymentCompletedEvent
  | PaymentRefundedEvent
  | UserRegisteredEvent
  | UserEmailVerifiedEvent
  | UserLoggedInEvent
  | UserLoggedOutEvent
  | UserPasswordChangedEvent
  | UserDisplayNameChangedEvent
  | ScraperRunCompletedEvent
  | AliasUnresolvedEvent;
