import type { DbClient } from "../lib/db";
import type {
  PrivateLeagueListQuery,
  PrivateLeagueUpdateInput,
  PrivateLeagueWriteInput,
} from "./schemas";

export type PrivateLeagueRow = {
  readonly id: string;
  readonly slug: string;
  readonly name: string;
  readonly description: string | null;
  readonly logo_url: string | null;
  readonly banner_url: string | null;
  readonly invite_code: string;
  readonly owner_user_id: string | null;
  readonly is_archived: number;
  readonly created_at: string;
  readonly updated_at: string;
  readonly archived_at: string | null;
};

export type PrivateLeagueMemberRow = {
  readonly id: string;
  readonly private_league_id: string;
  readonly user_id: string;
  readonly email: string | null;
  readonly display_name: string | null;
  readonly role: "owner" | "admin" | "member";
  readonly is_active: number;
  readonly joined_at: string;
  readonly updated_at: string;
};

export type PrivateLeagueCompetitionRow = {
  readonly id: string;
  readonly private_league_id: string;
  readonly competition_id: string;
  readonly competition_name: string | null;
  readonly created_at: string;
};

export type PrivateLeagueDetailRow = PrivateLeagueRow & {
  readonly member_count: number;
  readonly competition_count: number;
};

export type ListResult<T> = {
  readonly rows: readonly T[];
  readonly total: number;
};

function offset(query: PrivateLeagueListQuery): number {
  return ((query.page ?? 1) - 1) * (query.limit ?? 25);
}

function nullable(value: string | null | undefined): string | null {
  return value?.trim() ? value.trim() : null;
}

function boolToInt(value: boolean | undefined): number | undefined {
  return value === undefined ? undefined : value ? 1 : 0;
}

function buildListWhere(query: PrivateLeagueListQuery) {
  const clauses: string[] = [];
  const params: unknown[] = [];
  if (query.includeArchived !== "true") {
    clauses.push("pl.is_archived = 0");
  }
  if (query.search?.trim()) {
    clauses.push("(pl.name LIKE ? OR pl.slug LIKE ? OR pl.invite_code LIKE ?)");
    const search = `%${query.search.trim()}%`;
    params.push(search, search, search);
  }
  return {
    where: clauses.length > 0 ? `WHERE ${clauses.join(" AND ")}` : "",
    params,
  };
}

async function leagueById(
  db: DbClient,
  id: string,
): Promise<PrivateLeagueDetailRow | null> {
  return db.queryOne<PrivateLeagueDetailRow>(
    `SELECT pl.*,
            COUNT(DISTINCT plm.id) AS member_count,
            COUNT(DISTINCT plc.id) AS competition_count
       FROM private_leagues pl
       LEFT JOIN private_league_members plm ON plm.private_league_id = pl.id AND plm.is_active = 1
       LEFT JOIN private_league_competitions plc ON plc.private_league_id = pl.id
      WHERE pl.id = ?
      GROUP BY pl.id`,
    [id],
  );
}

export async function listPrivateLeagues(
  db: DbClient,
  query: PrivateLeagueListQuery,
): Promise<ListResult<PrivateLeagueDetailRow>> {
  const { where, params } = buildListWhere(query);
  const rows = await db.queryAll<PrivateLeagueDetailRow>(
    `SELECT pl.*,
            COUNT(DISTINCT plm.id) AS member_count,
            COUNT(DISTINCT plc.id) AS competition_count
       FROM private_leagues pl
       LEFT JOIN private_league_members plm ON plm.private_league_id = pl.id AND plm.is_active = 1
       LEFT JOIN private_league_competitions plc ON plc.private_league_id = pl.id
       ${where}
      GROUP BY pl.id
      ORDER BY pl.created_at DESC
      LIMIT ? OFFSET ?`,
    [...params, query.limit ?? 25, offset(query)],
  );
  const total = await db.queryOne<{ count: number }>(
    `SELECT COUNT(*) AS count FROM private_leagues pl ${where}`,
    params,
  );
  return { rows, total: total?.count ?? 0 };
}

export async function findPrivateLeagueById(
  db: DbClient,
  id: string,
): Promise<PrivateLeagueDetailRow | null> {
  return leagueById(db, id);
}

export async function createPrivateLeague(
  db: DbClient,
  id: string,
  inviteCode: string,
  input: PrivateLeagueWriteInput,
  now: string,
): Promise<PrivateLeagueDetailRow> {
  await db.execute(
    `INSERT INTO private_leagues
       (id, slug, name, description, logo_url, banner_url, invite_code, owner_user_id, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      id,
      input.slug,
      input.name,
      nullable(input.description),
      nullable(input.logoUrl),
      nullable(input.bannerUrl),
      inviteCode,
      nullable(input.ownerUserId),
      now,
      now,
    ],
  );
  const row = await leagueById(db, id);
  if (row === null) throw new Error("Private league insert failed");
  return row;
}

export async function updatePrivateLeague(
  db: DbClient,
  id: string,
  input: PrivateLeagueUpdateInput & { readonly isArchived?: boolean },
  now: string,
): Promise<PrivateLeagueDetailRow | null> {
  const values: Record<string, unknown> = {
    ...(input.slug !== undefined ? { slug: input.slug } : {}),
    ...(input.name !== undefined ? { name: input.name } : {}),
    ...(input.description !== undefined
      ? { description: nullable(input.description) }
      : {}),
    ...(input.logoUrl !== undefined
      ? { logo_url: nullable(input.logoUrl) }
      : {}),
    ...(input.bannerUrl !== undefined
      ? { banner_url: nullable(input.bannerUrl) }
      : {}),
    ...(input.ownerUserId !== undefined
      ? { owner_user_id: nullable(input.ownerUserId) }
      : {}),
    ...(input.isArchived !== undefined
      ? {
          is_archived: boolToInt(input.isArchived),
          archived_at: input.isArchived ? now : null,
        }
      : {}),
    updated_at: now,
  };
  const entries = Object.entries(values);
  await db.execute(
    `UPDATE private_leagues SET ${entries.map(([key]) => `${key} = ?`).join(", ")} WHERE id = ?`,
    [...entries.map(([, value]) => value), id],
  );
  return leagueById(db, id);
}

export async function replaceLeagueCompetitions(
  db: DbClient,
  leagueId: string,
  relationIds: readonly string[],
  competitionIds: readonly string[],
  now: string,
) {
  await db.execute(
    "DELETE FROM private_league_competitions WHERE private_league_id = ?",
    [leagueId],
  );
  for (let index = 0; index < competitionIds.length; index += 1) {
    await db.execute(
      `INSERT INTO private_league_competitions
         (id, private_league_id, competition_id, created_at)
       VALUES (?, ?, ?, ?)`,
      [relationIds[index], leagueId, competitionIds[index], now],
    );
  }
}

export async function listLeagueCompetitions(
  db: DbClient,
  leagueId: string,
): Promise<readonly PrivateLeagueCompetitionRow[]> {
  return db.queryAll<PrivateLeagueCompetitionRow>(
    `SELECT plc.*, c.name AS competition_name
       FROM private_league_competitions plc
       LEFT JOIN competitions c ON c.id = plc.competition_id
      WHERE plc.private_league_id = ?
      ORDER BY c.name`,
    [leagueId],
  );
}

export async function listLeagueMembers(
  db: DbClient,
  leagueId: string,
): Promise<readonly PrivateLeagueMemberRow[]> {
  return db.queryAll<PrivateLeagueMemberRow>(
    `SELECT plm.*, u.email, up.display_name
       FROM private_league_members plm
       LEFT JOIN users u ON u.id = plm.user_id
       LEFT JOIN user_profiles up ON up.user_id = plm.user_id
      WHERE plm.private_league_id = ?
      ORDER BY plm.joined_at DESC`,
    [leagueId],
  );
}

export async function upsertLeagueMember(
  db: DbClient,
  id: string,
  leagueId: string,
  userId: string,
  role: "owner" | "admin" | "member",
  now: string,
) {
  await db.execute(
    `INSERT INTO private_league_members
       (id, private_league_id, user_id, role, is_active, joined_at, updated_at)
     VALUES (?, ?, ?, ?, 1, ?, ?)
     ON CONFLICT(private_league_id, user_id)
     DO UPDATE SET role = excluded.role, is_active = 1, updated_at = excluded.updated_at`,
    [id, leagueId, userId, role, now, now],
  );
  return listLeagueMembers(db, leagueId);
}

export async function removeLeagueMember(
  db: DbClient,
  leagueId: string,
  userId: string,
  now: string,
) {
  await db.execute(
    `UPDATE private_league_members
        SET is_active = 0, updated_at = ?
      WHERE private_league_id = ? AND user_id = ?`,
    [now, leagueId, userId],
  );
  return listLeagueMembers(db, leagueId);
}
