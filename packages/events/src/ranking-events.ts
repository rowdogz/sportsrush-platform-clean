import type { UUID } from "@sr/types";
import type { BaseEvent } from "./base";

/**
 * Emitted by the Rankings domain after a new RankingSnapshot has been written.
 * Triggers: WebSocket broadcast → connected clients.
 * Maximum acceptable lag from scores.recalculated: 30 seconds.
 */
export type RankingsUpdatedEvent = BaseEvent & {
  readonly eventType: "rankings.updated";
  readonly competitionId: UUID;
  readonly snapshotId: UUID;
  readonly matchCount: number;
  readonly userCount: number;
};

/**
 * Emitted by the Rankings domain after the monthly winner is calculated.
 * Runs on the 1st of each month at 00:05 UTC.
 *
 * isTied = true if multiple users share the top monthly score.
 * Tie handling: NEEDS OWNER DECISION OD-06.
 *
 * Triggers: NotificationLog push + email to winner
 */
export type MonthlyWinnerDeclaredEvent = BaseEvent & {
  readonly eventType: "monthly_winner.declared";
  readonly competitionId: UUID;
  readonly year: number;
  readonly month: number; // 1–12
  readonly winnerId: UUID;
  readonly monthPoints: number;
  readonly isTied: boolean;
};
