import type { UUID, Timestamp } from "./common";

/**
 * Raw result data from an external scraper or data feed.
 *
 * This type is the membrane of the anti-corruption layer (ACL) between the
 * External Integrations domain and the Fixtures & Results domain.
 *
 * External team names and competition names must be resolved to canonical
 * IDs via the team_aliases table BEFORE the data crosses this boundary.
 * If resolution fails, the data is written to scraper_unresolved_aliases
 * and the Fixtures domain is not touched.
 *
 * See SPORTSRUSH_2_DOMAIN_MODEL.md — Domain 10 (External Integrations)
 * See SPORTSRUSH_CANONICAL_RULES.md §4.3 (Team Aliases)
 */
export type ScraperResult = {
  readonly externalHomeName: string; // as received from the data source
  readonly externalAwayName: string;
  readonly externalCompetitionName: string;
  readonly playDate: Timestamp; // UTC
  readonly homeScore: number;
  readonly awayScore: number;
  readonly source: string; // e.g. 'bbc', 'rlcom', 'manual_csv'
};

/**
 * A team name that could not be resolved to a canonical team_id.
 * Written when the scraper receives a name with no matching alias.
 *
 * Never silently discarded — always logged and surfaced in the admin panel.
 * Triggers an admin notification so the alias can be added without a code deploy.
 *
 * `resolvedAt` is set when an admin creates the alias.
 */
export type UnresolvedAlias = {
  readonly id: UUID;
  readonly externalName: string;
  readonly source: string;
  readonly scraperRunId: UUID;
  readonly encounteredAt: Timestamp;
  readonly resolvedAt: Timestamp | null;
  readonly resolvedBy: UUID | null;
};

/**
 * Status of a single scraper execution.
 *
 * - running  — in progress
 * - completed — all results processed (some may have unresolved aliases)
 * - partial   — completed with errors; some results skipped
 * - failed    — execution aborted; no results written
 */
export type ScraperRunStatus = "running" | "completed" | "partial" | "failed";

/**
 * Metadata about a single scraper execution run.
 */
export type ScraperRun = {
  readonly id: UUID;
  readonly source: string;
  readonly status: ScraperRunStatus;
  readonly resultsFound: number;
  readonly resultsApplied: number;
  readonly unresolvedAliasCount: number;
  readonly startedAt: Timestamp;
  readonly completedAt: Timestamp | null;
  readonly errorMessage: string | null;
};
