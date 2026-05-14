import type { UUID, Timestamp } from "./common";

/**
 * A single row in a rankings table.
 *
 * Rank uses DENSE_RANK semantics (SPORTSRUSH_CANONICAL_RULES.md §2.2–2.3):
 *   - Tied players share the same rank number
 *   - The next rank after a tie group is consecutive: 1, 2, 2, 3 — never 1, 2, 2, 4
 *
 * Tiebreaker order beyond total_points:
 *   NEEDS OWNER DECISION OD-04 (SPORTSRUSH_CANONICAL_RULES.md §2.2)
 *   Recommended: (1) exactScoreCount DESC, (2) correctOutcomeCount DESC,
 *               (3) displayName ASC
 */
export type RankingRow = {
  readonly userId: UUID;
  readonly displayName: string;
  readonly avatarUrl: string | null;
  readonly rank: number;
  readonly totalPoints: number;
  readonly currentMonthPoints: number;
  readonly exactScoreCount: number;
  readonly correctOutcomeCount: number;
  readonly predictionCount: number;
};

/**
 * Scope parameters for a rankings query.
 * All fields beyond competitionId are optional filters.
 */
export type RankingFilter = {
  readonly competitionId: UUID;
  readonly seasonId?: UUID;
  readonly round?: number;
  readonly monthYear?: {
    readonly year: number;
    readonly month: number; // 1–12
  };
};

/**
 * A point-in-time snapshot of a full competition ranking.
 * Written by the Rankings domain after scores.recalculated is processed.
 * Serves rankings without recalculating on every request (eventual consistency).
 * Maximum acceptable lag from scores.recalculated: 30 seconds.
 */
export type RankingSnapshot = {
  readonly id: UUID;
  readonly competitionId: UUID;
  readonly rows: readonly RankingRow[];
  readonly generatedAt: Timestamp;
  readonly matchCount: number; // number of completed matches included
  readonly userCount: number;
};

/**
 * The winner of a calendar month within a competition.
 * Calculated automatically on the 1st of each month at 00:05 UTC.
 *
 * Monthly winner = player with highest points from completed, non-void matches
 * whose play_date falls within the full previous calendar month
 * (midnight on the 1st to 23:59:59 on the last day, UTC).
 *
 * isTied = true when multiple users share the top spot.
 * Tie handling: NEEDS OWNER DECISION OD-06 (SPORTSRUSH_CANONICAL_RULES.md §2.7)
 *
 * Competition exclusions from monthly winner display must be admin-configurable
 * (not hard-coded as in the legacy system).
 */
export type MonthlyWinner = {
  readonly id: UUID;
  readonly competitionId: UUID;
  readonly year: number;
  readonly month: number; // 1–12
  readonly userId: UUID;
  readonly displayName: string;
  readonly monthPoints: number;
  readonly calculatedAt: Timestamp;
  readonly isTied: boolean; // true if multiple users share the top monthly score
};
