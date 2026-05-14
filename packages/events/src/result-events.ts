import type { UUID, MatchStatus } from "@sr/types";
import type { BaseEvent } from "./base";

/**
 * Emitted synchronously when an admin enters a result for a match.
 * Triggers: ScoresRecalculatedEvent (async, scoring engine)
 *           NotificationLog push: "Full time results are in" (async)
 */
export type ResultPublishedEvent = BaseEvent & {
  readonly eventType: "result.published";
  readonly matchId: UUID;
  readonly competitionId: UUID;
  readonly homeScore: number;
  readonly awayScore: number;
  readonly round: number;
  readonly publishedBy: UUID; // admin user_id
};

/**
 * Emitted when an admin corrects a previously entered result.
 * Triggers: ScoresRecalculatedEvent for all predictions on this match.
 * Reason is mandatory — see SPORTSRUSH_CANONICAL_RULES.md §1.8.
 */
export type ResultCorrectedEvent = BaseEvent & {
  readonly eventType: "result.corrected";
  readonly matchId: UUID;
  readonly competitionId: UUID;
  readonly previousHome: number;
  readonly previousAway: number;
  readonly correctedHome: number;
  readonly correctedAway: number;
  readonly correctedBy: UUID; // admin user_id
  readonly reason: string;
};

/**
 * Emitted when an admin creates a new fixture.
 */
export type MatchCreatedEvent = BaseEvent & {
  readonly eventType: "match.created";
  readonly matchId: UUID;
  readonly competitionId: UUID;
  readonly homeTeamId: UUID;
  readonly awayTeamId: UUID;
  readonly playDate: string; // ISO 8601 UTC
  readonly round: number;
  readonly createdBy: UUID;
};

/**
 * Emitted when a match's kick-off time is changed.
 * Predictions remain locked/unlocked based on the new play_date.
 */
export type MatchRescheduledEvent = BaseEvent & {
  readonly eventType: "match.rescheduled";
  readonly matchId: UUID;
  readonly competitionId: UUID;
  readonly previousPlayDate: string;
  readonly newPlayDate: string;
  readonly rescheduledBy: UUID;
};

/**
 * Emitted when a match status changes (e.g. scheduled → postponed).
 */
export type MatchStatusChangedEvent = BaseEvent & {
  readonly eventType: "match.status_changed";
  readonly matchId: UUID;
  readonly competitionId: UUID;
  readonly previousStatus: MatchStatus;
  readonly newStatus: MatchStatus;
  readonly changedBy: UUID;
};

/**
 * Emitted when a match is voided. All associated predictions score 0 points.
 */
export type MatchVoidedEvent = BaseEvent & {
  readonly eventType: "match.voided";
  readonly matchId: UUID;
  readonly competitionId: UUID;
  readonly voidedBy: UUID;
  readonly reason: string;
};
