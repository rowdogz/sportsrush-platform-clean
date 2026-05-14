import type { UUID } from "@sr/types";
import type { BaseEvent } from "./base";

/**
 * Emitted after a prediction is successfully saved (create or update).
 * Used by the Analytics domain to log prediction_submitted events.
 *
 * Scores are not included — predictions are not visible until after kick-off.
 */
export type PredictionSavedEvent = BaseEvent & {
  readonly eventType: "prediction.saved";
  readonly predictionId: UUID;
  readonly userId: UUID;
  readonly matchId: UUID;
  readonly competitionId: UUID;
  readonly joker: boolean;
  readonly isUpdate: boolean; // false = first save, true = overwrite
};

/**
 * Emitted when the prediction lock fires for a match
 * (play_date − lock_minutes, default 30 min before kick-off).
 * All predictions for this match are now immutable.
 */
export type PredictionLockedEvent = BaseEvent & {
  readonly eventType: "prediction.locked";
  readonly matchId: UUID;
  readonly competitionId: UUID;
  readonly lockedAt: string; // ISO 8601 UTC — the exact lock timestamp
  readonly predictionCount: number; // how many predictions were locked
};
