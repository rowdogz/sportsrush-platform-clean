import type { UUID, Timestamp } from "./common";

/**
 * Controls which predictions qualify for the goal-difference bonus.
 *
 * - 'toto_only'       — only non-draw correct outcomes qualify (legacy SR behaviour)
 * - 'toto_and_draws'  — draws also qualify for the diff bonus
 * - 'never'           — goal-difference bonus is disabled entirely
 *
 * NEEDS OWNER DECISION OD-02 (SPORTSRUSH_CANONICAL_RULES.md §1.5)
 */
export type DiffBonusMode = "toto_only" | "toto_and_draws" | "never";

/**
 * A versioned scoring configuration.
 *
 * Versioned so historical recalculations use the config that was active
 * when points were originally earned. Config changes are never retroactive
 * unless an explicit full recalculation is triggered by an admin.
 *
 * Canonical default values (verified from legacy system):
 *   exactPoints = 50, totoPoints = 20, homeBonusPoints = 10,
 *   awayBonusPoints = 10, diffBonusPoints = 20
 *
 * See SPORTSRUSH_CANONICAL_RULES.md §1.1–1.6
 */
export type ScoringConfig = {
  readonly id: UUID;
  readonly version: number;
  readonly exactPoints: number; // canonical default: 50
  readonly totoPoints: number; // canonical default: 20
  readonly homeBonusPoints: number; // canonical default: 10
  readonly awayBonusPoints: number; // canonical default: 10
  readonly diffBonusPoints: number; // canonical default: 20
  readonly diffBonusMode: DiffBonusMode; // NEEDS OWNER DECISION OD-02
  readonly jokerEnabled: boolean; // NEEDS OWNER DECISION OD-01
  readonly jokerMultiplier: number; // applies when jokerEnabled=true; default: 2
  readonly predictionLockMinutes: number; // default: 30
  readonly validFrom: Timestamp; // UTC; config is active from this timestamp onwards
  readonly createdBy: UUID;
};

/**
 * Computed score for a single user's prediction on a single match.
 * Written atomically by the Scoring Engine after a result is published or corrected.
 * Owned by the Scoring Engine domain.
 *
 * Each scoring component is stored separately to enable auditing and debugging.
 *
 * Maximum per match without joker (exact score):
 *   exactPoints(50) + homeBonusPoints(10) + awayBonusPoints(10) = 70
 *
 * In legacy WordPress: calculated live in SQL; no persistent history in custom rankings.
 */
export type MatchScore = {
  readonly id: UUID;
  readonly userId: UUID;
  readonly matchId: UUID;
  readonly competitionId: UUID;
  readonly pointsExact: number; // 50 if exact score, else 0
  readonly pointsToto: number; // 20 if correct outcome (not exact), else 0
  readonly pointsHomeBonus: number; // 10 if home score matches, else 0
  readonly pointsAwayBonus: number; // 10 if away score matches, else 0
  readonly pointsDiffBonus: number; // 20 if goal diff matches (per DiffBonusMode), else 0
  readonly pointsJokerMultiplier: number; // additional points from joker; 0 if no joker
  readonly totalPoints: number; // (sum of above components) × joker multiplier
  readonly scoringConfigVersion: number; // version of ScoringConfig used
  readonly calculatedAt: Timestamp;
};

/**
 * Human-readable breakdown of a MatchScore for display to the user.
 * Not stored in the database — derived from MatchScore + Prediction at read time.
 */
export type ScoreBreakdown = {
  readonly matchId: UUID;
  readonly userId: UUID;
  readonly predictedHome: number | null;
  readonly predictedAway: number | null;
  readonly actualHome: number;
  readonly actualAway: number;
  readonly pointsExact: number;
  readonly pointsToto: number;
  readonly pointsHomeBonus: number;
  readonly pointsAwayBonus: number;
  readonly pointsDiffBonus: number;
  readonly pointsJokerMultiplier: number;
  readonly totalPoints: number;
  readonly jokerApplied: boolean;
};
