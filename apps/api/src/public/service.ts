import { NotFoundError } from "../lib/errors";
import type { DbClient } from "../lib/db";
import {
  findPublicFixtureById,
  listPublicCompetitions,
  listPublicFixtures,
  listPublicRounds,
  listPublicSeasons,
  type ListResult,
  type PublicCompetitionRow,
  type PublicFixtureRow,
  type PublicRoundRow,
  type PublicSeasonRow,
} from "./repository";
import type {
  PublicFixtureListQuery,
  PublicPaginationQuery,
  PublicRoundListQuery,
  PublicSeasonListQuery,
} from "./schemas";
import type {
  PublicCompetition,
  PublicFixture,
  PublicFixtureStatus,
  PublicRound,
  PublicSeason,
} from "./types";

export type PublicServiceContext = { readonly db: DbClient };

function toPublicStatus(status: string): PublicFixtureStatus {
  return status === "void" ? "cancelled" : (status as PublicFixtureStatus);
}

function toPublicCompetition(row: PublicCompetitionRow): PublicCompetition {
  return {
    id: row.id,
    sportId: row.sport_id,
    slug: row.slug,
    name: row.name,
    shortName: row.short_name,
    countryCode: row.country_code,
  };
}

function toPublicSeason(row: PublicSeasonRow): PublicSeason {
  return {
    id: row.id,
    competitionId: row.competition_id,
    slug: row.slug,
    name: row.name,
    startsOn: row.starts_on,
    endsOn: row.ends_on,
    competition: {
      id: row.competition_id,
      slug: row.competition_slug,
      name: row.competition_name,
      shortName: row.competition_short_name,
    },
  };
}

function toPublicRound(row: PublicRoundRow): PublicRound {
  return {
    id: row.id,
    seasonId: row.season_id,
    round: row.round,
    name: row.round_name,
    displayOrder: row.display_order,
    startsAt: row.starts_at,
    endsAt: row.ends_at,
    season: { id: row.season_id, slug: row.season_slug, name: row.season_name },
    competition: {
      id: row.competition_id,
      slug: row.competition_slug,
      name: row.competition_name,
      shortName: row.competition_short_name,
    },
  };
}

function toPublicFixture(row: PublicFixtureRow): PublicFixture {
  return {
    id: row.id,
    kickoffTime: row.scheduled_at,
    venue: row.venue_name,
    status: toPublicStatus(row.status),
    homeScore: row.home_score,
    awayScore: row.away_score,
    homeTeam: {
      id: row.home_team_id,
      name: row.home_team_name,
      shortName: row.home_team_short_name,
      displayName: row.home_team_display_name,
      logoUrl: null,
      badgeUrl: null,
    },
    awayTeam: {
      id: row.away_team_id,
      name: row.away_team_name,
      shortName: row.away_team_short_name,
      displayName: row.away_team_display_name,
      logoUrl: null,
      badgeUrl: null,
    },
    round: {
      id: row.round_id,
      round: row.round,
      name: row.round_name,
      displayOrder: row.round_order,
    },
    season: { id: row.season_id, slug: row.season_slug, name: row.season_name },
    competition: {
      id: row.competition_id,
      slug: row.competition_slug,
      name: row.competition_name,
      shortName: row.competition_short_name,
    },
  };
}

function mapList<TInput, TOutput>(
  result: ListResult<TInput>,
  mapper: (row: TInput) => TOutput,
): ListResult<TOutput> {
  return { rows: result.rows.map(mapper), total: result.total };
}

export async function listPublicCompetitionsService(
  context: PublicServiceContext,
  pagination: PublicPaginationQuery,
): Promise<ListResult<PublicCompetition>> {
  return mapList(
    await listPublicCompetitions(context.db, pagination),
    toPublicCompetition,
  );
}

export async function listPublicSeasonsService(
  context: PublicServiceContext,
  pagination: PublicPaginationQuery,
  filters: PublicSeasonListQuery,
): Promise<ListResult<PublicSeason>> {
  return mapList(
    await listPublicSeasons(context.db, pagination, filters),
    toPublicSeason,
  );
}

export async function listPublicRoundsService(
  context: PublicServiceContext,
  pagination: PublicPaginationQuery,
  filters: PublicRoundListQuery,
): Promise<ListResult<PublicRound>> {
  return mapList(
    await listPublicRounds(context.db, pagination, filters),
    toPublicRound,
  );
}

export async function listPublicFixturesService(
  context: PublicServiceContext,
  filters: PublicFixtureListQuery,
): Promise<ListResult<PublicFixture>> {
  return mapList(
    await listPublicFixtures(context.db, filters),
    toPublicFixture,
  );
}

export async function getPublicFixtureService(
  context: PublicServiceContext,
  id: string,
): Promise<PublicFixture> {
  const row = await findPublicFixtureById(context.db, id);
  if (row === null) throw new NotFoundError("Fixture not found");
  return toPublicFixture(row);
}
