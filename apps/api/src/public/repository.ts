import type { DbClient } from "../lib/db";
import type {
  PublicFixtureListQuery,
  PublicPaginationQuery,
  PublicRoundListQuery,
  PublicSeasonListQuery,
} from "./schemas";

export type ListResult<T> = {
  readonly rows: readonly T[];
  readonly total: number;
};

export type PublicCompetitionRow = {
  readonly id: string;
  readonly sport_id: string;
  readonly slug: string;
  readonly name: string;
  readonly short_name: string | null;
  readonly country_code: string | null;
};

export type PublicSeasonRow = {
  readonly id: string;
  readonly competition_id: string;
  readonly slug: string;
  readonly name: string;
  readonly starts_on: string | null;
  readonly ends_on: string | null;
  readonly competition_slug: string;
  readonly competition_name: string;
  readonly competition_short_name: string | null;
};

export type PublicRoundRow = {
  readonly id: string;
  readonly season_id: string;
  readonly round: string;
  readonly round_name: string;
  readonly display_order: number;
  readonly starts_at: string | null;
  readonly ends_at: string | null;
  readonly season_slug: string;
  readonly season_name: string;
  readonly competition_id: string;
  readonly competition_slug: string;
  readonly competition_name: string;
  readonly competition_short_name: string | null;
};

export type PublicFixtureRow = {
  readonly id: string;
  readonly scheduled_at: string;
  readonly venue_name: string | null;
  readonly status: string;
  readonly home_score: number | null;
  readonly away_score: number | null;
  readonly home_team_id: string;
  readonly home_team_name: string;
  readonly home_team_short_name: string | null;
  readonly home_team_display_name: string | null;
  readonly away_team_id: string;
  readonly away_team_name: string;
  readonly away_team_short_name: string | null;
  readonly away_team_display_name: string | null;
  readonly round_id: string | null;
  readonly round: string;
  readonly round_name: string;
  readonly round_order: number | null;
  readonly season_id: string;
  readonly season_slug: string;
  readonly season_name: string;
  readonly competition_id: string;
  readonly competition_slug: string;
  readonly competition_name: string;
  readonly competition_short_name: string | null;
};

function offset(pagination: PublicPaginationQuery): number {
  return (pagination.page - 1) * pagination.limit;
}

export async function listPublicCompetitions(
  db: DbClient,
  pagination: PublicPaginationQuery,
): Promise<ListResult<PublicCompetitionRow>> {
  const rows = await db.queryAll<PublicCompetitionRow>(
    `SELECT id, sport_id, slug, name, short_name, country_code
       FROM competitions
      WHERE is_active = 1
      ORDER BY name ASC
      LIMIT ? OFFSET ?`,
    [pagination.limit, offset(pagination)],
  );
  const total = await db.queryOne<{ count: number }>(
    "SELECT COUNT(*) AS count FROM competitions WHERE is_active = 1",
  );
  return { rows, total: total?.count ?? 0 };
}

export async function listPublicSeasons(
  db: DbClient,
  pagination: PublicPaginationQuery,
  filters: PublicSeasonListQuery,
): Promise<ListResult<PublicSeasonRow>> {
  const clauses = ["s.is_active = 1", "c.is_active = 1"];
  const params: unknown[] = [];
  if (filters.competitionId) {
    clauses.push("s.competition_id = ?");
    params.push(filters.competitionId);
  }
  const where = `WHERE ${clauses.join(" AND ")}`;
  const rows = await db.queryAll<PublicSeasonRow>(
    `SELECT s.id, s.competition_id, s.slug, s.name, s.starts_on, s.ends_on,
            c.slug AS competition_slug, c.name AS competition_name, c.short_name AS competition_short_name
       FROM seasons s
       JOIN competitions c ON c.id = s.competition_id
      ${where}
      ORDER BY c.name ASC, s.starts_on DESC, s.name ASC
      LIMIT ? OFFSET ?`,
    [...params, pagination.limit, offset(pagination)],
  );
  const total = await db.queryOne<{ count: number }>(
    `SELECT COUNT(*) AS count FROM seasons s JOIN competitions c ON c.id = s.competition_id ${where}`,
    params,
  );
  return { rows, total: total?.count ?? 0 };
}

export async function listPublicRounds(
  db: DbClient,
  pagination: PublicPaginationQuery,
  filters: PublicRoundListQuery,
): Promise<ListResult<PublicRoundRow>> {
  const clauses = ["s.is_active = 1", "c.is_active = 1"];
  const params: unknown[] = [];
  if (filters.competitionId) {
    clauses.push("c.id = ?");
    params.push(filters.competitionId);
  }
  if (filters.seasonId) {
    clauses.push("r.season_id = ?");
    params.push(filters.seasonId);
  }
  const where = `WHERE ${clauses.join(" AND ")}`;
  const rows = await db.queryAll<PublicRoundRow>(
    `SELECT r.id, r.season_id, r.round, r.round_name, r.display_order, r.starts_at, r.ends_at,
            s.slug AS season_slug, s.name AS season_name,
            c.id AS competition_id, c.slug AS competition_slug, c.name AS competition_name, c.short_name AS competition_short_name
       FROM rounds r
       JOIN seasons s ON s.id = r.season_id
       JOIN competitions c ON c.id = s.competition_id
      ${where}
      ORDER BY c.name ASC, s.starts_on DESC, r.display_order ASC
      LIMIT ? OFFSET ?`,
    [...params, pagination.limit, offset(pagination)],
  );
  const total = await db.queryOne<{ count: number }>(
    `SELECT COUNT(*) AS count FROM rounds r JOIN seasons s ON s.id = r.season_id JOIN competitions c ON c.id = s.competition_id ${where}`,
    params,
  );
  return { rows, total: total?.count ?? 0 };
}
function toDbStatuses(status: string | undefined): readonly string[] {
  if (status === undefined) return [];
  if (status === "cancelled") return ["cancelled", "void"];
  if (status === "live") return ["__public_live__"];
  return [status];
}

function buildPublicFixtureQuery(filters: PublicFixtureListQuery): {
  readonly fromSql: string;
  readonly params: readonly unknown[];
} {
  const clauses = [
    "c.is_active = 1",
    "s.is_active = 1",
    "home.is_active = 1",
    "away.is_active = 1",
  ];
  const params: unknown[] = [];
  if (filters.competitionId) {
    clauses.push("f.competition_id = ?");
    params.push(filters.competitionId);
  }
  if (filters.seasonId) {
    clauses.push("f.season_id = ?");
    params.push(filters.seasonId);
  }
  if (filters.roundId) {
    clauses.push("f.round_id = ?");
    params.push(filters.roundId);
  }
  if (filters.status) {
    const statuses = toDbStatuses(filters.status);
    clauses.push(`f.status IN (${statuses.map(() => "?").join(", ")})`);
    params.push(...statuses);
  }
  if (filters.fromDate) {
    clauses.push("f.scheduled_at >= ?");
    params.push(filters.fromDate);
  }
  if (filters.toDate) {
    clauses.push("f.scheduled_at <= ?");
    params.push(filters.toDate);
  }

  return {
    fromSql: `FROM fixtures f
      JOIN competitions c ON c.id = f.competition_id
      JOIN seasons s ON s.id = f.season_id
      JOIN teams home ON home.id = f.home_team_id
      JOIN teams away ON away.id = f.away_team_id
      WHERE ${clauses.join(" AND ")}`,
    params,
  };
}

const publicFixtureSelect = `SELECT f.id,
            f.scheduled_at,
            f.venue_name,
            f.status,
            f.home_score,
            f.away_score,
            home.id AS home_team_id,
            home.name AS home_team_name,
            home.short_name AS home_team_short_name,
            home.display_name AS home_team_display_name,
            away.id AS away_team_id,
            away.name AS away_team_name,
            away.short_name AS away_team_short_name,
            away.display_name AS away_team_display_name,
            f.round_id,
            f.round,
            f.round_name,
            f.round_order,
            s.id AS season_id,
            s.slug AS season_slug,
            s.name AS season_name,
            c.id AS competition_id,
            c.slug AS competition_slug,
            c.name AS competition_name,
            c.short_name AS competition_short_name`;

export async function listPublicFixtures(
  db: DbClient,
  filters: PublicFixtureListQuery,
): Promise<ListResult<PublicFixtureRow>> {
  const { fromSql, params } = buildPublicFixtureQuery(filters);
  const rows = await db.queryAll<PublicFixtureRow>(
    `${publicFixtureSelect} ${fromSql} ORDER BY f.scheduled_at ASC, f.id ASC LIMIT ? OFFSET ?`,
    [...params, filters.limit, offset(filters)],
  );
  const total = await db.queryOne<{ count: number }>(
    `SELECT COUNT(*) AS count ${fromSql}`,
    [...params],
  );
  return { rows, total: total?.count ?? 0 };
}

export async function findPublicFixtureById(
  db: DbClient,
  id: string,
): Promise<PublicFixtureRow | null> {
  return db.queryOne<PublicFixtureRow>(
    `${publicFixtureSelect}
       FROM fixtures f
       JOIN competitions c ON c.id = f.competition_id
       JOIN seasons s ON s.id = f.season_id
       JOIN teams home ON home.id = f.home_team_id
       JOIN teams away ON away.id = f.away_team_id
      WHERE f.id = ?
        AND c.is_active = 1
        AND s.is_active = 1
        AND home.is_active = 1
        AND away.is_active = 1`,
    [id],
  );
}
