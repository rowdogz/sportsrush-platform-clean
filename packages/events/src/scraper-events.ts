import type { UUID } from "@sr/types";
import type { BaseEvent } from "./base";

/**
 * Emitted after a scraper execution finishes (regardless of outcome).
 * status reflects whether all results were applied, or some were skipped.
 */
export type ScraperRunCompletedEvent = BaseEvent & {
  readonly eventType: "scraper.run_completed";
  readonly scraperRunId: UUID;
  readonly source: string; // e.g. 'bbc', 'rlcom'
  readonly status: "completed" | "partial" | "failed";
  readonly resultsFound: number;
  readonly resultsApplied: number;
  readonly unresolvedAliasCount: number;
};

/**
 * Emitted for each team name the scraper encounters that cannot be matched
 * to a canonical team_id via the alias table.
 *
 * Triggers: admin notification (so the alias can be added without a code deploy).
 * Never silently discarded.
 */
export type AliasUnresolvedEvent = BaseEvent & {
  readonly eventType: "scraper.alias_unresolved";
  readonly scraperRunId: UUID;
  readonly externalName: string;
  readonly source: string;
};
