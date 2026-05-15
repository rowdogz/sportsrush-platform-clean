import { describe, expect, it } from "vitest";
import initSqlJs from "sql.js";
import { readFileSync } from "node:fs";
import { resolve } from "node:path";
import type { DbClient } from "../lib/db";
import {
  createAuditEvent,
  createCompetition,
  createFixture,
  createRound,
  createSeason,
  createTeam,
  createTeamAlias,
  findAliasBySource,
  findAdminUserById,
  findFixtureById,
  insertResultCorrection,
  listAliasesBySource,
  listAuditEvents,
  listCompetitions,
  listFixtures,
  updateAdminUserRole,
  updateAdminUserStatus,
  updateCompetition,
  updateFixture,
  updateRound,
  updateSeason,
  updateTeam,
} from "./repository";

const migrationPaths = [
  resolve(__dirname, "../../migrations/0002_auth_schema.sql"),
  resolve(
    __dirname,
    "../../migrations/0003_competitions_teams_fixtures_results.sql",
  ),
  resolve(__dirname, "../../migrations/0004_admin_audit_events.sql"),
];

const NOW = "2026-05-14T12:00:00.000Z";
const PAGE = { page: 1, limit: 25 };

type SqlJsDatabase = initSqlJs.Database;

async function createSqlDb(): Promise<SqlJsDatabase> {
  const SQL = await initSqlJs();
  const db = new SQL.Database();
  db.run("PRAGMA foreign_keys = ON;");
  migrationPaths.forEach((migrationPath) => {
    db.run(readFileSync(migrationPath, "utf8"));
  });
  return db;
}

function normaliseSqlParams(
  params: readonly unknown[] = [],
): initSqlJs.BindParams {
  return params.map((value) => value ?? null) as initSqlJs.BindParams;
}

function makeDbClient(sqlDb: SqlJsDatabase): DbClient {
  return {
    async queryOne<T extends Record<string, unknown>>(
      sql: string,
      params: unknown[] = [],
    ): Promise<T | null> {
      const stmt = sqlDb.prepare(sql);
      try {
        stmt.bind(normaliseSqlParams(params));
        if (!stmt.step()) return null;
        return stmt.getAsObject() as T;
      } finally {
        stmt.free();
      }
    },
    async queryAll<T extends Record<string, unknown>>(
      sql: string,
      params: unknown[] = [],
    ): Promise<T[]> {
      const stmt = sqlDb.prepare(sql);
      const rows: T[] = [];
      try {
        stmt.bind(normaliseSqlParams(params));
        while (stmt.step()) rows.push(stmt.getAsObject() as T);
        return rows;
      } finally {
        stmt.free();
      }
    },
    async execute<T extends Record<string, unknown> = Record<string, unknown>>(
      sql: string,
      params: unknown[] = [],
    ): Promise<D1Result<T>> {
      sqlDb.run(sql, normaliseSqlParams(params));
      return {
        success: true,
        results: [],
        meta: {
          duration: 0,
          size_after: 0,
          rows_read: 0,
          rows_written: 0,
          last_row_id: 0,
          changed_db: false,
          changes: 0,
        },
      } as D1Result<T>;
    },
    async batch<T extends Record<string, unknown> = Record<string, unknown>>() {
      return [] as D1Result<T>[];
    },
    prepare(sql: string) {
      return {
        bind() {
          return this as unknown as D1PreparedStatement;
        },
        first: async () => null,
        run: async () => ({ success: true, results: [], meta: {} }),
        all: async () => ({ success: true, results: [], meta: {} }),
        raw: async () => [],
      } as unknown as D1PreparedStatement;
    },
    async ping() {
      return true;
    },
  };
}

async function createRepositoryDb() {
  const sqlDb = await createSqlDb();
  const db = makeDbClient(sqlDb);
  sqlDb.run(
    `INSERT INTO sports (id, slug, name, created_at, updated_at)
     VALUES ('sport-rugby-league', 'rugby-league', 'Rugby League', ?, ?)`,
    [NOW, NOW],
  );
  return { sqlDb, db };
}

function seedUser(sqlDb: SqlJsDatabase) {
  sqlDb.run(
    `INSERT INTO users
       (id, email, email_normalized, role, is_active, is_legacy_migration, created_at, updated_at)
     VALUES ('user-1', 'alice@example.test', 'alice@example.test', 'user', 1, 0, ?, ?)`,
    [NOW, NOW],
  );
  sqlDb.run(
    `INSERT INTO user_profiles (user_id, display_name, timezone, created_at, updated_at)
     VALUES ('user-1', 'Alice Example', 'UTC', ?, ?)`,
    [NOW, NOW],
  );
}

async function seedCompetitionSeasonTeams() {
  const context = await createRepositoryDb();
  const { db } = context;

  await createCompetition(
    db,
    "competition-super-league",
    {
      sportId: "sport-rugby-league",
      slug: "super-league",
      name: "Super League",
      shortName: "SL",
      countryCode: "GB",
      legacyId: "wp_comp_100",
    },
    NOW,
  );

  await createSeason(
    db,
    "season-2026",
    {
      competitionId: "competition-super-league",
      slug: "2026",
      name: "2026 Season",
      isActive: true,
      legacyId: "wp_season_2026",
    },
    NOW,
  );

  await createTeam(
    db,
    "team-wigan",
    {
      sportId: "sport-rugby-league",
      slug: "wigan-warriors",
      name: "Wigan Warriors",
      shortName: "Wigan",
      legacyId: "wp_team_1",
    },
    NOW,
  );

  await createTeam(
    db,
    "team-saints",
    {
      sportId: "sport-rugby-league",
      slug: "st-helens",
      name: "St Helens",
      shortName: "Saints",
      legacyId: "wp_team_2",
    },
    NOW,
  );

  return context;
}

describe("admin repository layer", () => {
  it("creates and lists admin audit events", async () => {
    const { db, sqlDb } = await createRepositoryDb();
    seedUser(sqlDb);

    const event = await createAuditEvent(
      db,
      "audit-1",
      {
        actorUserId: "user-1",
        action: "user.role.update",
        targetType: "user",
        targetId: "user-1",
        before: { role: "user" },
        after: { role: "admin" },
      },
      NOW,
    );

    expect(event.action).toBe("user.role.update");
    expect(event.actor_user_id).toBe("user-1");
    expect(JSON.parse(event.before_metadata ?? "{}")).toEqual({
      role: "user",
    });

    const events = await listAuditEvents(db);
    expect(events).toHaveLength(1);
    expect(events[0]?.id).toBe("audit-1");
  });

  it("finds and updates admin user role and status", async () => {
    const { db, sqlDb } = await createRepositoryDb();
    seedUser(sqlDb);

    const existing = await findAdminUserById(db, "user-1");
    expect(existing?.display_name).toBe("Alice Example");
    expect(existing?.role).toBe("user");

    const roleUpdated = await updateAdminUserRole(db, "user-1", "admin", NOW);
    expect(roleUpdated?.role).toBe("admin");

    const statusUpdated = await updateAdminUserStatus(db, "user-1", false, NOW);
    expect(statusUpdated?.is_active).toBe(0);
  });

  it("creates, lists, and updates competitions", async () => {
    const { db } = await createRepositoryDb();

    const created = await createCompetition(
      db,
      "competition-1",
      {
        sportId: "sport-rugby-league",
        slug: "super-league",
        name: "Super League",
        shortName: "SL",
        countryCode: "GB",
        legacyId: "wp_1",
      },
      NOW,
    );

    expect(created.name).toBe("Super League");
    expect(created.legacy_id).toBe("wp_1");

    const updated = await updateCompetition(
      db,
      "competition-1",
      { name: "Betfred Super League", isActive: false },
      "2026-05-14T13:00:00.000Z",
    );

    expect(updated?.name).toBe("Betfred Super League");
    expect(updated?.is_active).toBe(0);

    const list = await listCompetitions(db, PAGE);
    expect(list.total).toBe(1);
    expect(list.rows[0]?.id).toBe("competition-1");
  });

  it("creates and updates seasons", async () => {
    const { db } = await seedCompetitionSeasonTeams();

    const updated = await updateSeason(
      db,
      "season-2026",
      { name: "2026 Regular Season", isActive: false },
      "2026-05-14T13:00:00.000Z",
    );

    expect(updated?.name).toBe("2026 Regular Season");
    expect(updated?.is_active).toBe(0);
  });

  it("creates and updates teams", async () => {
    const { db } = await seedCompetitionSeasonTeams();

    const updated = await updateTeam(
      db,
      "team-wigan",
      { displayName: "Wigan Warriors RLFC", isActive: false },
      "2026-05-14T13:00:00.000Z",
    );

    expect(updated?.display_name).toBe("Wigan Warriors RLFC");
    expect(updated?.is_active).toBe(0);
  });

  it("creates aliases and supports source lookups", async () => {
    const { db } = await seedCompetitionSeasonTeams();

    await createTeamAlias(
      db,
      "alias-wigan-bbc",
      {
        teamId: "team-wigan",
        sportId: "sport-rugby-league",
        alias: "Wigan",
        source: "BBC",
        priority: 10,
      },
      "wigan",
      "bbc",
      NOW,
    );

    const found = await findAliasBySource(
      db,
      "sport-rugby-league",
      "BBC",
      "wigan",
    );
    expect(found?.team_id).toBe("team-wigan");

    const rows = await listAliasesBySource(
      db,
      "sport-rugby-league",
      "bbc",
      "wigan",
    );
    expect(rows).toHaveLength(1);
  });

  it("rejects duplicate normalized aliases", async () => {
    const { db } = await seedCompetitionSeasonTeams();

    await createTeamAlias(
      db,
      "alias-wigan-bbc",
      {
        teamId: "team-wigan",
        sportId: "sport-rugby-league",
        alias: "Wigan",
        source: "bbc",
        priority: 10,
      },
      "wigan",
      "bbc",
      NOW,
    );

    await expect(
      createTeamAlias(
        db,
        "alias-saints-sportradar",
        {
          teamId: "team-saints",
          sportId: "sport-rugby-league",
          alias: "Wigan Warriors",
          source: "sportradar",
          priority: 10,
        },
        "wigan",
        "sportradar",
        NOW,
      ),
    ).rejects.toThrow();
  });

  it("creates and updates rounds with ordering", async () => {
    const { db } = await seedCompetitionSeasonTeams();

    const roundOne = await createRound(
      db,
      "round-1",
      {
        seasonId: "season-2026",
        round: "1",
        roundName: "Round 1",
        displayOrder: 1,
      },
      NOW,
    );

    expect(roundOne.round_name).toBe("Round 1");
    expect(roundOne.display_order).toBe(1);

    const updated = await updateRound(
      db,
      "round-1",
      { roundName: "Quarter Final", displayOrder: 10 },
      "2026-05-14T13:00:00.000Z",
    );

    expect(updated?.round_name).toBe("Quarter Final");
    expect(updated?.display_order).toBe(10);
  });

  it("creates fixtures and filters them", async () => {
    const { db } = await seedCompetitionSeasonTeams();

    await createFixture(
      db,
      "fixture-1",
      {
        sportId: "sport-rugby-league",
        competitionId: "competition-super-league",
        seasonId: "season-2026",
        round: "1",
        roundName: "Round 1",
        roundOrder: 1,
        homeTeamId: "team-wigan",
        awayTeamId: "team-saints",
        scheduledAt: "2026-02-01T20:00:00.000Z",
        status: "scheduled",
        legacyMatchId: 101,
        legacyFixtureId: "legacy-fixture-101",
      },
      NOW,
    );

    const byCompetition = await listFixtures(
      db,
      { competitionId: "competition-super-league" },
      PAGE,
    );
    expect(byCompetition.total).toBe(1);

    const byRound = await listFixtures(db, { round: "1" }, PAGE);
    expect(byRound.rows[0]?.round_name).toBe("Round 1");

    const byDateRange = await listFixtures(
      db,
      {
        dateFrom: "2026-02-01T00:00:00.000Z",
        dateTo: "2026-02-02T00:00:00.000Z",
      },
      PAGE,
    );
    expect(byDateRange.total).toBe(1);
  });

  it("enforces duplicate fixture constraints", async () => {
    const { db } = await seedCompetitionSeasonTeams();
    const fixture = {
      sportId: "sport-rugby-league",
      competitionId: "competition-super-league",
      seasonId: "season-2026",
      round: "1",
      roundName: "Round 1",
      homeTeamId: "team-wigan",
      awayTeamId: "team-saints",
      scheduledAt: "2026-02-01T20:00:00.000Z",
      status: "scheduled" as const,
    };

    await createFixture(db, "fixture-1", fixture, NOW);
    await expect(
      createFixture(db, "fixture-2", fixture, NOW),
    ).rejects.toThrow();
  });

  it("persists result corrections with previous values", async () => {
    const { db } = await seedCompetitionSeasonTeams();

    await createFixture(
      db,
      "fixture-1",
      {
        sportId: "sport-rugby-league",
        competitionId: "competition-super-league",
        seasonId: "season-2026",
        round: "1",
        roundName: "Round 1",
        homeTeamId: "team-wigan",
        awayTeamId: "team-saints",
        scheduledAt: "2026-02-01T20:00:00.000Z",
        status: "completed",
        homeScore: 20,
        awayScore: 18,
      },
      NOW,
    );

    const fixture = await findFixtureById(db, "fixture-1");
    expect(fixture).not.toBeNull();

    const correction = await insertResultCorrection(
      db,
      "correction-1",
      fixture!,
      22,
      18,
      "Official correction",
      "admin-user-1",
      "Admin User",
      "2026-05-14T13:00:00.000Z",
    );

    expect(correction.previous_home_score).toBe(20);
    expect(correction.corrected_home_score).toBe(22);
    expect(correction.reason).toBe("Official correction");
    expect(correction.corrected_by_user_id).toBe("admin-user-1");
  });

  it("keeps correction audit immutable when fixtures change later", async () => {
    const { db } = await seedCompetitionSeasonTeams();

    await createFixture(
      db,
      "fixture-1",
      {
        sportId: "sport-rugby-league",
        competitionId: "competition-super-league",
        seasonId: "season-2026",
        round: "1",
        roundName: "Round 1",
        homeTeamId: "team-wigan",
        awayTeamId: "team-saints",
        scheduledAt: "2026-02-01T20:00:00.000Z",
        status: "completed",
        homeScore: 20,
        awayScore: 18,
      },
      NOW,
    );

    const originalFixture = await findFixtureById(db, "fixture-1");
    expect(originalFixture).not.toBeNull();

    await insertResultCorrection(
      db,
      "correction-1",
      originalFixture!,
      22,
      18,
      "Official correction",
      "admin-user-1",
      "Admin User",
      "2026-05-14T13:00:00.000Z",
    );

    await updateFixture(
      db,
      "fixture-1",
      { homeScore: 30, awayScore: 12 },
      "2026-05-14T14:00:00.000Z",
    );

    const row = await db.queryOne<{
      previous_home_score: number;
      corrected_home_score: number;
    }>(
      "SELECT previous_home_score, corrected_home_score FROM result_corrections WHERE id = ?",
      ["correction-1"],
    );

    expect(row?.previous_home_score).toBe(20);
    expect(row?.corrected_home_score).toBe(22);
  });
});
