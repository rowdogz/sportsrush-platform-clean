import type { UUID, Timestamp } from "./common";

/**
 * A user's prediction for a single match.
 * Owned by the Predictions domain.
 *
 * Lock rule (SPORTSRUSH_CANONICAL_RULES.md §3.1):
 *   A prediction is accepted only if the server receives it before
 *   match.play_date − lock_minutes (default: 30). The check is
 *   server-side and transactional — the client's clock is never trusted.
 *
 * home_score / away_score are nullable to allow partial predictions if
 * the owner enables them (NEEDS OWNER DECISION OD-07).
 *
 * In legacy WordPress: pool_wpkl_predictions
 */
export type Prediction = {
  readonly id: UUID;
  readonly userId: UUID;
  readonly matchId: UUID;
  readonly homeScore: number | null;
  readonly awayScore: number | null;
  /**
   * Joker flag. When true, the canonical scoring engine applies the
   * joker multiplier to this match's total points.
   *
   * Only valid when ScoringConfig.jokerEnabled = true.
   * Limit: one joker per round (configurable).
   *
   * NEEDS OWNER DECISION OD-01 (SPORTSRUSH_CANONICAL_RULES.md §1.6)
   */
  readonly joker: boolean;
  readonly createdAt: Timestamp;
  readonly updatedAt: Timestamp;
  /**
   * Timestamp at which this prediction was locked by the server.
   * Set when the lock window closes (play_date − lock_minutes).
   * Null if the match has not yet locked.
   */
  readonly lockedAt: Timestamp | null;
};

/**
 * An admin override that re-opens or force-locks a match's predictions
 * outside the normal lock window.
 *
 * All overrides are audit-logged. Reason is mandatory.
 * See SPORTSRUSH_CANONICAL_RULES.md §3.5
 */
export type PredictionOverrideType = "open" | "lock";

export type PredictionOverride = {
  readonly id: UUID;
  readonly matchId: UUID;
  readonly overrideType: PredictionOverrideType;
  readonly setBy: UUID; // admin user_id
  readonly reason: string; // mandatory; non-empty
  readonly createdAt: Timestamp;
};

/**
 * Input type for submitting or updating a prediction via the API.
 * `joker` is optional — omitting it preserves the existing value on update.
 */
export type PredictionInput = {
  readonly homeScore: number;
  readonly awayScore: number;
  readonly joker?: boolean;
};
