import type { UUID, Timestamp } from "./common";

/**
 * Sports supported by the platform.
 * Extend this union when new sports are added — do not use a plain string.
 */
export type Sport =
  | "rugby_league"
  | "football"
  | "rugby_union"
  | "cricket"
  | "other";

/**
 * Controls whether a competition appears in public filters and dropdowns.
 *
 * - public   — visible to all users; appears in all competition filters
 * - unlisted — accessible by direct link only; not shown in dropdowns
 * - archived — read-only; hidden from all active views; historical data preserved
 */
export type CompetitionVisibility = "public" | "unlisted" | "archived";

/**
 * A competition (called a "matchtype" in legacy WordPress).
 * The top-level grouping for matches, predictions, and rankings.
 * Owned by the Competitions domain.
 *
 * Examples: "NRL 2025", "Premier League 2024/25", "State of Origin 2025"
 *
 * See SPORTSRUSH_2_DOMAIN_MODEL.md — Domain 3 (Competitions)
 * See SPORTSRUSH_CANONICAL_RULES.md §2.4 (Competition Filter)
 */
export type Competition = {
  readonly id: UUID;
  readonly name: string;
  readonly sport: Sport;
  readonly description: string | null;
  readonly logoUrl: string | null;
  readonly visibility: CompetitionVisibility;
  /**
   * Controls the order competitions appear in dropdowns and filter lists.
   * Lower numbers appear first.
   */
  readonly displayOrder: number;
  readonly createdAt: Timestamp;
  readonly updatedAt: Timestamp;
};

/**
 * A named time period within a competition.
 * Used for grouping rounds and scoping monthly winner calculations.
 *
 * In legacy WordPress this mapped to pool_wpkl_seasons.
 * Example: "NRL 2025 Season", "State of Origin 2025"
 */
export type Season = {
  readonly id: UUID;
  readonly competitionId: UUID;
  readonly name: string; // e.g. "2025 Season"
  readonly startDate: Timestamp; // UTC
  readonly endDate: Timestamp; // UTC
  readonly active: boolean;
  readonly createdAt: Timestamp;
};
