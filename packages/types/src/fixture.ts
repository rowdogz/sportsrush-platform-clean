import type { UUID, Timestamp } from "./common";

/**
 * Lifecycle status of a match.
 *
 * Scoring rules per status (SPORTSRUSH_CANONICAL_RULES.md §1.7):
 * - scheduled  — not yet played; predictions open (subject to lock window)
 * - completed  — result entered; scores normal
 * - postponed  — rescheduled; 0 points until completed
 * - abandoned  — NEEDS OWNER DECISION: award points or void? (OD-08)
 * - void       — 0 points; excluded from all calculations
 */
export type MatchStatus =
  | "scheduled"
  | "completed"
  | "postponed"
  | "abandoned"
  | "void";

/**
 * A team that participates in matches.
 * Owned by the Fixtures & Results domain.
 *
 * In legacy WordPress: pool_wpkl_teams
 */
export type Team = {
  readonly id: UUID;
  readonly name: string;
  readonly shortName: string | null;
  readonly logoUrl: string | null;
  readonly createdAt: Timestamp;
};

/**
 * An alias mapping an external team name (from scrapers/data feeds)
 * to a canonical team_id.
 *
 * Required because BBC Sport and other data sources use different name formats
 * (e.g. "Leeds Rhinos" vs "Leeds"). Unmatched names are logged to
 * scraper_unresolved_aliases and trigger an admin notification.
 *
 * See SPORTSRUSH_CANONICAL_RULES.md §4.3 (Team Aliases)
 */
export type TeamAlias = {
  readonly id: UUID;
  readonly teamId: UUID;
  readonly externalName: string; // normalised: lowercase, trimmed, collapsed whitespace
  readonly source: string; // e.g. 'bbc', 'rlcom', 'manual'
  readonly createdAt: Timestamp;
};

/**
 * A scheduled or completed match.
 * The authoritative source of kick-off times and results.
 * Owned by the Fixtures & Results domain.
 *
 * Constraints (SPORTSRUSH_CANONICAL_RULES.md §4.1):
 * - round is mandatory (integer); fixtures without a round are rejected
 * - play_date is always UTC
 * - home_score / away_score are null until the match is completed
 * - Duplicate matches (same home+away+competition+date) are rejected
 *
 * In legacy WordPress: pool_wpkl_matches
 */
export type Match = {
  readonly id: UUID;
  readonly competitionId: UUID;
  readonly homeTeamId: UUID;
  readonly awayTeamId: UUID;
  readonly playDate: Timestamp; // kick-off time, UTC (ISO 8601)
  readonly round: number; // required; integer round number within competition
  readonly roundName: string | null; // optional display label, e.g. "Grand Final"
  readonly status: MatchStatus;
  readonly homeScore: number | null; // null until status = 'completed'
  readonly awayScore: number | null; // null until status = 'completed'
  readonly createdAt: Timestamp;
  readonly updatedAt: Timestamp;
  readonly createdBy: UUID; // admin user_id who created the fixture
};

/**
 * An audit record of a result correction.
 * Written whenever home_score or away_score is overwritten after initial entry.
 *
 * `reason` is mandatory — silent corrections are not permitted.
 * All corrections are logged for full audit trail.
 *
 * See SPORTSRUSH_CANONICAL_RULES.md §1.8 (Results Edited After Points Are Calculated)
 */
export type ResultCorrection = {
  readonly id: UUID;
  readonly matchId: UUID;
  readonly previousHome: number;
  readonly previousAway: number;
  readonly correctedHome: number;
  readonly correctedAway: number;
  readonly correctedBy: UUID;
  readonly correctedAt: Timestamp;
  readonly reason: string; // non-empty; mandatory
};

/**
 * Read-model: a match with teams denormalised for API responses.
 * Not stored — assembled at read time to avoid N+1 lookups.
 */
export type MatchWithTeams = Match & {
  readonly homeTeam: Team;
  readonly awayTeam: Team;
};
