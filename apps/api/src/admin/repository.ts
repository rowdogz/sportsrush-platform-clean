import type { DbClient } from "../lib/db";
import type {
  CreateCompetitionInput,
  CreateFixtureInput,
  CreateRoundInput,
  CreateSeasonInput,
  CreateTeamAliasInput,
  CreateTeamInput,
  FixtureListQuery,
  FixtureStatus,
  UpdateCompetitionInput,
  UpdateFixtureInput,
  UpdateRoundInput,
  UpdateSeasonInput,
  UpdateTeamAliasInput,
  UpdateTeamInput,
} from "./schemas";

export type Pagination = {
  readonly page: number;
  readonly limit: number;
};

export type ListResult<T> = {
  readonly rows: readonly T[];
  readonly total: number;
};

export type SeasonListFilters = {
  readonly competitionId?: string | undefined;
  readonly search?: string | undefined;
};

export type UserListFilters = {
  readonly search?: string | undefined;
  readonly role?: string | undefined;
  readonly isActive?: boolean | undefined;
};

export type AdminUserRole = "user" | "admin" | "superadmin";

export type CompetitionRow = {
  readonly id: string;
  readonly sport_id: string;
  readonly slug: string;
  readonly name: string;
  readonly short_name: string | null;
  readonly country_code: string | null;
  readonly is_active: number;
  readonly created_at: string;
  readonly updated_at: string;
  readonly legacy_id: string | null;
};

export type SeasonRow = {
  readonly id: string;
  readonly competition_id: string;
  readonly slug: string;
  readonly name: string;
  readonly starts_on: string | null;
  readonly ends_on: string | null;
  readonly is_active: number;
  readonly created_at: string;
  readonly updated_at: string;
  readonly legacy_id: string | null;
};

export type TeamRow = {
  readonly id: string;
  readonly sport_id: string;
  readonly slug: string;
  readonly name: string;
  readonly short_name: string | null;
  readonly display_name: string | null;
  readonly country_code: string | null;
  readonly is_active: number;
  readonly created_at: string;
  readonly updated_at: string;
  readonly legacy_id: string | null;
};

export type TeamAliasRow = {
  readonly id: string;
  readonly team_id: string;
  readonly sport_id: string;
  readonly alias: string;
  readonly normalized_alias: string;
  readonly source: string;
  readonly priority: number;
  readonly is_active: number;
  readonly created_at: string;
  readonly updated_at: string;
  readonly legacy_id: string | null;
};

export type RoundRow = {
  readonly id: string;
  readonly season_id: string;
  readonly round: string;
  readonly round_name: string;
  readonly display_order: number;
  readonly starts_at: string | null;
  readonly ends_at: string | null;
  readonly created_at: string;
  readonly updated_at: string;
  readonly legacy_id: string | null;
};

export type FixtureRow = {
  readonly id: string;
  readonly sport_id: string;
  readonly competition_id: string;
  readonly season_id: string;
  readonly round_id: string | null;
  readonly round: string;
  readonly round_name: string;
  readonly round_order: number | null;
  readonly home_team_id: string;
  readonly away_team_id: string;
  readonly scheduled_at: string;
  readonly original_scheduled_at: string | null;
  readonly venue_name: string | null;
  readonly status: FixtureStatus;
  readonly home_score: number | null;
  readonly away_score: number | null;
  readonly result_source: string | null;
  readonly result_entered_at: string | null;
  readonly result_entered_by: string | null;
  readonly created_at: string;
  readonly updated_at: string;
  readonly legacy_match_id: number | null;
  readonly legacy_fixture_id: string | null;
  readonly external_source: string | null;
  readonly external_id: string | null;
};

export type ResultCorrectionRow = {
  readonly id: string;
  readonly fixture_id: string;
  readonly previous_status: FixtureStatus;
  readonly previous_home_score: number | null;
  readonly previous_away_score: number | null;
  readonly corrected_status: FixtureStatus;
  readonly corrected_home_score: number | null;
  readonly corrected_away_score: number | null;
  readonly reason: string;
  readonly corrected_by_user_id: string | null;
  readonly corrected_by_display_name: string | null;
  readonly created_at: string;
};

export type AdminUserRow = {
  readonly id: string;
  readonly email: string;
  readonly display_name: string | null;
  readonly role: string;
  readonly is_active: number;
  readonly email_verified_at: string | null;
  readonly created_at: string;
  readonly updated_at: string;
  readonly profile_updated_at: string | null;
  readonly legacy_wp_user_id: number | null;
};

function offset(pagination: Pagination): number {
  return (pagination.page - 1) * pagination.limit;
}

function boolToInt(value: boolean | undefined): number | undefined {
  return value === undefined ? undefined : value ? 1 : 0;
}

function buildUpdate(
  table: string,
  id: string,
  values: Record<string, unknown>,
): { readonly sql: string; readonly params: readonly unknown[] } | null {
  const entries = Object.entries(values).filter(
    ([, value]) => value !== undefined,
  );
  if (entries.length === 0) return null;
  const sets = entries.map(([key]) => `${key} = ?`).join(", ");
  const params = entries.map(([, value]) => value);
  return {
    sql: `UPDATE ${table} SET ${sets} WHERE id = ?`,
    params: [...params, id],
  };
}

export async function createCompetition(
  db: DbClient,
  id: string,
  input: CreateCompetitionInput,
  now: string,
): Promise<CompetitionRow> {
  await db.execute(
    `INSERT INTO competitions
       (id, sport_id, slug, name, short_name, country_code, created_at, updated_at, legacy_id)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      id,
      input.sportId,
      input.slug,
      input.name,
      input.shortName ?? null,
      input.countryCode ?? null,
      now,
      now,
      input.legacyId ?? null,
    ],
  );
  return findCompetitionById(db, id) as Promise<CompetitionRow>;
}

export async function findCompetitionById(
  db: DbClient,
  id: string,
): Promise<CompetitionRow | null> {
  return db.queryOne<CompetitionRow>(
    "SELECT * FROM competitions WHERE id = ?",
    [id],
  );
}

export async function updateCompetition(
  db: DbClient,
  id: string,
  input: UpdateCompetitionInput,
  now: string,
): Promise<CompetitionRow | null> {
  const update = buildUpdate("competitions", id, {
    sport_id: input.sportId,
    slug: input.slug,
    name: input.name,
    short_name: input.shortName,
    country_code: input.countryCode,
    is_active: boolToInt(input.isActive),
    legacy_id: input.legacyId,
    updated_at: now,
  });
  if (update) await db.execute(update.sql, [...update.params]);
  return findCompetitionById(db, id);
}

export async function listCompetitions(
  db: DbClient,
  pagination: Pagination,
): Promise<ListResult<CompetitionRow>> {
  const rows = await db.queryAll<CompetitionRow>(
    "SELECT * FROM competitions ORDER BY name LIMIT ? OFFSET ?",
    [pagination.limit, offset(pagination)],
  );
  const total = await db.queryOne<{ count: number }>(
    "SELECT COUNT(*) AS count FROM competitions",
  );
  return { rows, total: total?.count ?? 0 };
}

export async function listAdminUsers(
  db: DbClient,
  pagination: Pagination,
  filters: UserListFilters = {},
): Promise<ListResult<AdminUserRow>> {
  const where: string[] = [];
  const params: unknown[] = [];

  if (filters.search) {
    where.push("(u.email LIKE ? OR p.display_name LIKE ?)");
    params.push(`%${filters.search}%`, `%${filters.search}%`);
  }

  if (filters.role) {
    where.push("u.role = ?");
    params.push(filters.role);
  }

  if (filters.isActive !== undefined) {
    where.push("u.is_active = ?");
    params.push(filters.isActive ? 1 : 0);
  }

  const whereSql = where.length > 0 ? ` WHERE ${where.join(" AND ")}` : "";
  const fromSql = `FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id${whereSql}`;
  const rows = await db.queryAll<AdminUserRow>(
    `SELECT u.id,
            u.email,
            p.display_name,
            u.role,
            u.is_active,
            u.email_verified_at,
            u.created_at,
            u.updated_at,
            p.updated_at AS profile_updated_at,
            u.legacy_wp_user_id
       ${fromSql}
      ORDER BY u.created_at DESC, u.email
      LIMIT ? OFFSET ?`,
    [...params, pagination.limit, offset(pagination)],
  );
  const total = await db.queryOne<{ count: number }>(
    `SELECT COUNT(*) AS count ${fromSql}`,
    params,
  );
  return { rows, total: total?.count ?? 0 };
}

export async function findAdminUserById(
  db: DbClient,
  id: string,
): Promise<AdminUserRow | null> {
  return db.queryOne<AdminUserRow>(
    `SELECT u.id,
            u.email,
            p.display_name,
            u.role,
            u.is_active,
            u.email_verified_at,
            u.created_at,
            u.updated_at,
            p.updated_at AS profile_updated_at,
            u.legacy_wp_user_id
       FROM users u
       LEFT JOIN user_profiles p ON p.user_id = u.id
      WHERE u.id = ?`,
    [id],
  );
}

export async function updateAdminUserRole(
  db: DbClient,
  id: string,
  role: AdminUserRole,
  now: string,
): Promise<AdminUserRow | null> {
  await db.execute("UPDATE users SET role = ?, updated_at = ? WHERE id = ?", [
    role,
    now,
    id,
  ]);
  return findAdminUserById(db, id);
}

export async function updateAdminUserStatus(
  db: DbClient,
  id: string,
  isActive: boolean,
  now: string,
): Promise<AdminUserRow | null> {
  await db.execute(
    "UPDATE users SET is_active = ?, updated_at = ? WHERE id = ?",
    [isActive ? 1 : 0, now, id],
  );
  return findAdminUserById(db, id);
}

export async function createSeason(
  db: DbClient,
  id: string,
  input: CreateSeasonInput,
  now: string,
): Promise<SeasonRow> {
  await db.execute(
    `INSERT INTO seasons
       (id, competition_id, slug, name, starts_on, ends_on, is_active, created_at, updated_at, legacy_id)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      id,
      input.competitionId,
      input.slug,
      input.name,
      input.startsOn ?? null,
      input.endsOn ?? null,
      input.isActive === undefined ? 1 : boolToInt(input.isActive),
      now,
      now,
      input.legacyId ?? null,
    ],
  );
  return findSeasonById(db, id) as Promise<SeasonRow>;
}

export async function listSeasons(
  db: DbClient,
  pagination: Pagination,
  filters: SeasonListFilters = {},
): Promise<ListResult<SeasonRow>> {
  const where: string[] = [];
  const params: unknown[] = [];

  if (filters.competitionId) {
    where.push("competition_id = ?");
    params.push(filters.competitionId);
  }

  if (filters.search) {
    where.push("(name LIKE ? OR slug LIKE ?)");
    params.push(`%${filters.search}%`, `%${filters.search}%`);
  }

  const whereSql = where.length > 0 ? ` WHERE ${where.join(" AND ")}` : "";
  const rows = await db.queryAll<SeasonRow>(
    `SELECT * FROM seasons${whereSql} ORDER BY competition_id, starts_on DESC, name LIMIT ? OFFSET ?`,
    [...params, pagination.limit, offset(pagination)],
  );
  const total = await db.queryOne<{ count: number }>(
    `SELECT COUNT(*) AS count FROM seasons${whereSql}`,
    params,
  );
  return { rows, total: total?.count ?? 0 };
}

export async function findSeasonById(
  db: DbClient,
  id: string,
): Promise<SeasonRow | null> {
  return db.queryOne<SeasonRow>("SELECT * FROM seasons WHERE id = ?", [id]);
}

export async function updateSeason(
  db: DbClient,
  id: string,
  input: UpdateSeasonInput,
  now: string,
): Promise<SeasonRow | null> {
  const update = buildUpdate("seasons", id, {
    competition_id: input.competitionId,
    slug: input.slug,
    name: input.name,
    starts_on: input.startsOn,
    ends_on: input.endsOn,
    is_active: boolToInt(input.isActive),
    legacy_id: input.legacyId,
    updated_at: now,
  });
  if (update) await db.execute(update.sql, [...update.params]);
  return findSeasonById(db, id);
}

export async function markActiveSeason(
  db: DbClient,
  seasonId: string,
  competitionId: string,
  now: string,
): Promise<SeasonRow | null> {
  await db.execute(
    "UPDATE seasons SET is_active = 0, updated_at = ? WHERE competition_id = ?",
    [now, competitionId],
  );
  await db.execute(
    "UPDATE seasons SET is_active = 1, updated_at = ? WHERE id = ?",
    [now, seasonId],
  );
  return findSeasonById(db, seasonId);
}

export async function createTeam(
  db: DbClient,
  id: string,
  input: CreateTeamInput,
  now: string,
): Promise<TeamRow> {
  await db.execute(
    `INSERT INTO teams
       (id, sport_id, slug, name, short_name, display_name, country_code, created_at, updated_at, legacy_id)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      id,
      input.sportId,
      input.slug,
      input.name,
      input.shortName ?? null,
      input.displayName ?? null,
      input.countryCode ?? null,
      now,
      now,
      input.legacyId ?? null,
    ],
  );
  return findTeamById(db, id) as Promise<TeamRow>;
}

export async function findTeamById(
  db: DbClient,
  id: string,
): Promise<TeamRow | null> {
  return db.queryOne<TeamRow>("SELECT * FROM teams WHERE id = ?", [id]);
}

export async function updateTeam(
  db: DbClient,
  id: string,
  input: UpdateTeamInput,
  now: string,
): Promise<TeamRow | null> {
  const update = buildUpdate("teams", id, {
    sport_id: input.sportId,
    slug: input.slug,
    name: input.name,
    short_name: input.shortName,
    display_name: input.displayName,
    country_code: input.countryCode,
    is_active: boolToInt(input.isActive),
    legacy_id: input.legacyId,
    updated_at: now,
  });
  if (update) await db.execute(update.sql, [...update.params]);
  return findTeamById(db, id);
}

export async function createTeamAlias(
  db: DbClient,
  id: string,
  input: CreateTeamAliasInput,
  normalizedAlias: string,
  source: string,
  now: string,
): Promise<TeamAliasRow> {
  await db.execute(
    `INSERT INTO team_aliases
       (id, team_id, sport_id, alias, normalized_alias, source, priority, is_active, created_at, updated_at, legacy_id)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      id,
      input.teamId,
      input.sportId,
      input.alias,
      normalizedAlias,
      source,
      input.priority,
      input.isActive === undefined ? 1 : boolToInt(input.isActive),
      now,
      now,
      input.legacyId ?? null,
    ],
  );
  return findTeamAliasById(db, id) as Promise<TeamAliasRow>;
}

export async function findTeamAliasById(
  db: DbClient,
  id: string,
): Promise<TeamAliasRow | null> {
  return db.queryOne<TeamAliasRow>("SELECT * FROM team_aliases WHERE id = ?", [
    id,
  ]);
}

export async function findAliasBySource(
  db: DbClient,
  sportId: string,
  source: string,
  normalizedAlias: string,
): Promise<TeamAliasRow | null> {
  return db.queryOne<TeamAliasRow>(
    `SELECT * FROM team_aliases
     WHERE sport_id = ?
       AND lower(source) = lower(?)
       AND normalized_alias = ?
       AND is_active = 1
     ORDER BY priority ASC
     LIMIT 1`,
    [sportId, source, normalizedAlias],
  );
}

export async function listAliasesBySource(
  db: DbClient,
  sportId: string,
  source: string | undefined,
  normalizedAlias: string | undefined,
): Promise<readonly TeamAliasRow[]> {
  const clauses = ["sport_id = ?", "is_active = 1"];
  const params: unknown[] = [sportId];
  if (source !== undefined) {
    clauses.push("lower(source) = lower(?)");
    params.push(source);
  }
  if (normalizedAlias !== undefined) {
    clauses.push("normalized_alias = ?");
    params.push(normalizedAlias);
  }
  return db.queryAll<TeamAliasRow>(
    `SELECT * FROM team_aliases WHERE ${clauses.join(" AND ")} ORDER BY source, priority, alias`,
    params,
  );
}

export async function updateTeamAlias(
  db: DbClient,
  id: string,
  input: UpdateTeamAliasInput,
  normalizedAlias: string | undefined,
  source: string | undefined,
  now: string,
): Promise<TeamAliasRow | null> {
  const update = buildUpdate("team_aliases", id, {
    team_id: input.teamId,
    sport_id: input.sportId,
    alias: input.alias,
    normalized_alias: normalizedAlias,
    source,
    priority: input.priority,
    is_active: boolToInt(input.isActive),
    legacy_id: input.legacyId,
    updated_at: now,
  });
  if (update) await db.execute(update.sql, [...update.params]);
  return findTeamAliasById(db, id);
}

export async function deleteTeamAlias(db: DbClient, id: string): Promise<void> {
  await db.execute("DELETE FROM team_aliases WHERE id = ?", [id]);
}

export async function createRound(
  db: DbClient,
  id: string,
  input: CreateRoundInput,
  now: string,
): Promise<RoundRow> {
  await db.execute(
    `INSERT INTO rounds
       (id, season_id, round, round_name, display_order, starts_at, ends_at, created_at, updated_at, legacy_id)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      id,
      input.seasonId,
      input.round,
      input.roundName,
      input.displayOrder,
      input.startsAt ?? null,
      input.endsAt ?? null,
      now,
      now,
      input.legacyId ?? null,
    ],
  );
  return findRoundById(db, id) as Promise<RoundRow>;
}

export async function findRoundById(
  db: DbClient,
  id: string,
): Promise<RoundRow | null> {
  return db.queryOne<RoundRow>("SELECT * FROM rounds WHERE id = ?", [id]);
}

export async function updateRound(
  db: DbClient,
  id: string,
  input: UpdateRoundInput,
  now: string,
): Promise<RoundRow | null> {
  const update = buildUpdate("rounds", id, {
    season_id: input.seasonId,
    round: input.round,
    round_name: input.roundName,
    display_order: input.displayOrder,
    starts_at: input.startsAt,
    ends_at: input.endsAt,
    legacy_id: input.legacyId,
    updated_at: now,
  });
  if (update) await db.execute(update.sql, [...update.params]);
  return findRoundById(db, id);
}

export async function createFixture(
  db: DbClient,
  id: string,
  input: CreateFixtureInput,
  now: string,
): Promise<FixtureRow> {
  await db.execute(
    `INSERT INTO fixtures
       (id, sport_id, competition_id, season_id, round_id, round, round_name, round_order,
        home_team_id, away_team_id, scheduled_at, original_scheduled_at, venue_name, status,
        home_score, away_score, created_at, updated_at, legacy_match_id, legacy_fixture_id,
        external_source, external_id)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      id,
      input.sportId,
      input.competitionId,
      input.seasonId,
      input.roundId ?? null,
      input.round,
      input.roundName,
      input.roundOrder ?? null,
      input.homeTeamId,
      input.awayTeamId,
      input.scheduledAt,
      input.originalScheduledAt ?? null,
      input.venueName ?? null,
      input.status,
      input.homeScore ?? null,
      input.awayScore ?? null,
      now,
      now,
      input.legacyMatchId ?? null,
      input.legacyFixtureId ?? null,
      input.externalSource ?? null,
      input.externalId ?? null,
    ],
  );
  return findFixtureById(db, id) as Promise<FixtureRow>;
}

export async function findFixtureById(
  db: DbClient,
  id: string,
): Promise<FixtureRow | null> {
  return db.queryOne<FixtureRow>("SELECT * FROM fixtures WHERE id = ?", [id]);
}

export async function updateFixture(
  db: DbClient,
  id: string,
  input: UpdateFixtureInput,
  now: string,
): Promise<FixtureRow | null> {
  const update = buildUpdate("fixtures", id, {
    sport_id: input.sportId,
    competition_id: input.competitionId,
    season_id: input.seasonId,
    round_id: input.roundId,
    round: input.round,
    round_name: input.roundName,
    round_order: input.roundOrder,
    home_team_id: input.homeTeamId,
    away_team_id: input.awayTeamId,
    scheduled_at: input.scheduledAt,
    original_scheduled_at: input.originalScheduledAt,
    venue_name: input.venueName,
    status: input.status,
    home_score: input.homeScore,
    away_score: input.awayScore,
    legacy_match_id: input.legacyMatchId,
    legacy_fixture_id: input.legacyFixtureId,
    external_source: input.externalSource,
    external_id: input.externalId,
    updated_at: now,
  });
  if (update) await db.execute(update.sql, [...update.params]);
  return findFixtureById(db, id);
}

export async function listFixtures(
  db: DbClient,
  filters: FixtureListQuery,
  pagination: Pagination,
): Promise<ListResult<FixtureRow>> {
  const clauses: string[] = [];
  const params: unknown[] = [];
  if (filters.competitionId) {
    clauses.push("competition_id = ?");
    params.push(filters.competitionId);
  }
  if (filters.seasonId) {
    clauses.push("season_id = ?");
    params.push(filters.seasonId);
  }
  if (filters.round) {
    clauses.push("round = ?");
    params.push(filters.round);
  }
  if (filters.status) {
    clauses.push("status = ?");
    params.push(filters.status);
  }
  if (filters.dateFrom) {
    clauses.push("scheduled_at >= ?");
    params.push(filters.dateFrom);
  }
  if (filters.dateTo) {
    clauses.push("scheduled_at <= ?");
    params.push(filters.dateTo);
  }

  const where = clauses.length > 0 ? `WHERE ${clauses.join(" AND ")}` : "";
  const rows = await db.queryAll<FixtureRow>(
    `SELECT * FROM fixtures ${where} ORDER BY scheduled_at LIMIT ? OFFSET ?`,
    [...params, pagination.limit, offset(pagination)],
  );
  const total = await db.queryOne<{ count: number }>(
    `SELECT COUNT(*) AS count FROM fixtures ${where}`,
    params,
  );
  return { rows, total: total?.count ?? 0 };
}

export async function setFixtureStatus(
  db: DbClient,
  id: string,
  status: FixtureStatus,
  now: string,
  clearScores: boolean,
): Promise<FixtureRow | null> {
  if (clearScores) {
    await db.execute(
      `UPDATE fixtures
       SET status = ?, home_score = NULL, away_score = NULL, result_source = NULL,
           result_entered_at = NULL, result_entered_by = NULL, updated_at = ?
       WHERE id = ?`,
      [status, now, id],
    );
  } else {
    await db.execute(
      "UPDATE fixtures SET status = ?, updated_at = ? WHERE id = ?",
      [status, now, id],
    );
  }
  return findFixtureById(db, id);
}

export async function enterFixtureResult(
  db: DbClient,
  id: string,
  homeScore: number,
  awayScore: number,
  resultSource: string | null,
  userId: string,
  now: string,
): Promise<FixtureRow | null> {
  await db.execute(
    `UPDATE fixtures
     SET status = 'completed', home_score = ?, away_score = ?, result_source = ?,
         result_entered_at = ?, result_entered_by = ?, updated_at = ?
     WHERE id = ?`,
    [homeScore, awayScore, resultSource, now, userId, now, id],
  );
  return findFixtureById(db, id);
}

export async function insertResultCorrection(
  db: DbClient,
  id: string,
  fixture: FixtureRow,
  correctedHomeScore: number,
  correctedAwayScore: number,
  reason: string,
  correctedByUserId: string,
  correctedByDisplayName: string | null,
  now: string,
): Promise<ResultCorrectionRow> {
  await db.execute(
    `INSERT INTO result_corrections
       (id, fixture_id, previous_status, previous_home_score, previous_away_score,
        corrected_status, corrected_home_score, corrected_away_score, reason,
        corrected_by_user_id, corrected_by_display_name, created_at)
     VALUES (?, ?, ?, ?, ?, 'completed', ?, ?, ?, ?, ?, ?)`,
    [
      id,
      fixture.id,
      fixture.status,
      fixture.home_score,
      fixture.away_score,
      correctedHomeScore,
      correctedAwayScore,
      reason,
      correctedByUserId,
      correctedByDisplayName,
      now,
    ],
  );
  const row = await db.queryOne<ResultCorrectionRow>(
    "SELECT * FROM result_corrections WHERE id = ?",
    [id],
  );
  if (row === null) throw new Error("Result correction insert failed");
  return row;
}
