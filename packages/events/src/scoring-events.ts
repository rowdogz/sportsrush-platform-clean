import type { UUID } from "@sr/types";
import type { BaseEvent } from "./base";

/**
 * Emitted by the Scoring Engine after all match_scores rows for a match
 * have been written (or rewritten, in the case of a correction).
 *
 * Triggers: RankingsUpdatedEvent (Rankings domain, async)
 *           Achievement checks (Gamification domain, future)
 *
 * userCount reflects how many user scores were calculated in this batch.
 * scoringConfigVersion is the version of ScoringConfig used.
 */
export type ScoresRecalculatedEvent = BaseEvent & {
  readonly eventType: "scores.recalculated";
  readonly matchId: UUID;
  readonly competitionId: UUID;
  readonly round: number;
  readonly userCount: number;
  readonly scoringConfigVersion: number;
  readonly triggeredBy: "result.published" | "result.corrected";
};
