import { describe, expect, it } from "vitest";
import initSqlJs from "sql.js";
import { readFileSync } from "node:fs";
import { resolve } from "node:path";
import { AppError } from "../lib/errors";
import type { DbClient } from "../lib/db";
import {
  createCompetition,
  createFixture,
  createSeason,
  createTeam,
  enterFixtureResult,
  findFixtureById,
  setFixtureStatus,
  updateFixture,
} from "./repository";
type FixtureStatus =
  | "scheduled"
  | "postponed"
  | "completed"
  | "abandoned"
  | "cancelled"
  | "void";
import {
  activateSeasonService,
  AdminDomainError,
  correctFixtureResultService,
  enterFixtureResultService,
  getAllowedFixtureTransitions,
  transitionFixtureService,
  type ServiceContext,
} from "./service";
import { normalizeAlias, normalizeSource } from "./normalization";

const migrationPath = resolve(
  __dirname,
  "../../migrations/0003_competitions_teams_fixtures_results.sql",
);

const NOW = "2026-05-14T12:00:00.000Z";
const NEXT = "2026-05-14T13:00:00.000Z";
const CORRELATION_ID = "test-correlation-id";

type SqlJsDatabase = initSqlJs.Database;

async function createSqlDb(): Promise<SqlJsDatabase> {
  const SQL = await initSqlJs();
  const db = new SQL.Database();
  db.run("PRAGMA foreign_keys = ON;");
  db.run(readFileSync(migrationPath, "utf8"));
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
    prepare() {
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

async function createContext() {
  const sqlDb = await createSqlDb();
  const db = makeDbClient(sqlDb);
  const context: ServiceContext = {
    db,
    now: NOW,
    correlationId: CORRELATION_ID,
    actorUserId: "admin-user-1",
    actorDisplayName: "Admin User",
  };

  sqlDb.run(
    `INSERT INTO sports (id, slug, name, created_at, updated_at)
     VALUES ('sport-rugby-league', 'rugby-league', 'Rugby League', ?, ?)`,
    [NOW, NOW],
  );

  await createCompetition(
    db,
    "competition-super-league",
    {
      sportId: "sport-rugby-league",
      slug: "super-league",
      name: "Super League",
      shortName: "SL",
      countryCode: "GB",
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
    },
    NOW,
  );

  return { sqlDb, db, context };
}

async function seedFixture(status: FixtureStatus = "scheduled") {
  const state = await createContext();
  await createFixture(
    state.db,
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
      status,
      homeScore: status === "completed" ? 20 : undefined,
      awayScore: status === "completed" ? 18 : undefined,
    },
    NOW,
  );
  return state;
}

async function expectDomainFailure(promise: Promise<unknown>) {
  await expect(promise).rejects.toBeInstanceOf(AdminDomainError);
  await expect(promise).rejects.toBeInstanceOf(AppError);
}

describe("admin service fixture transitions", () => {
  it("returns the canonical transition matrix", () => {
    expect(getAllowedFixtureTransitions("scheduled")).toEqual([
      "postponed",
      "cancelled",
      "completed",
      "abandoned",
      "void",
    ]);
    expect(getAllowedFixtureTransitions("postponed")).toEqual([
      "scheduled",
      "cancelled",
      "void",
    ]);
    expect(getAllowedFixtureTransitions("abandoned")).toEqual([
      "completed",
      "void",
    ]);
    expect(getAllowedFixtureTransitions("completed")).toEqual([]);
    expect(getAllowedFixtureTransitions("cancelled")).toEqual([]);
    expect(getAllowedFixtureTransitions("void")).toEqual([]);
  });

  it.each([
    ["scheduled", "postponed"],
    ["scheduled", "cancelled"],
    ["scheduled", "abandoned"],
    ["scheduled", "void"],
    ["postponed", "scheduled"],
    ["postponed", "cancelled"],
    ["postponed", "void"],
    ["abandoned", "void"],
  ] as Array<[FixtureStatus, FixtureStatus]>)(
    "allows transition %s -> %s",
    async (from, to) => {
      const { context } = await seedFixture(from);
      const updated = await transitionFixtureService(context, "fixture-1", to);
      expect(updated.status).toBe(to);
    },
  );

  it("allows scheduled -> completed through result entry only", async () => {
    const { context } = await seedFixture("scheduled");
    const updated = await enterFixtureResultService(
      context,
      "fixture-1",
      20,
      18,
      "manual",
    );
    expect(updated.status).toBe("completed");
    expect(updated.home_score).toBe(20);
    expect(updated.away_score).toBe(18);
  });

  it("allows abandoned -> completed through result entry only", async () => {
    const { context } = await seedFixture("abandoned");
    const updated = await enterFixtureResultService(
      context,
      "fixture-1",
      20,
      18,
      "manual",
    );
    expect(updated.status).toBe("completed");
  });

  it.each([
    ["scheduled", "scheduled"],
    ["postponed", "completed"],
    ["postponed", "abandoned"],
    ["abandoned", "scheduled"],
    ["completed", "scheduled"],
    ["completed", "postponed"],
    ["cancelled", "scheduled"],
    ["void", "scheduled"],
  ] as Array<[FixtureStatus, FixtureStatus]>)(
    "rejects invalid transition %s -> %s",
    async (from, to) => {
      const { context } = await seedFixture(from);
      await expectDomainFailure(
        transitionFixtureService(context, "fixture-1", to),
      );
    },
  );

  it("prevents scheduled/postponed from jumping through invalid states", async () => {
    const scheduled = await seedFixture("scheduled");
    await expectDomainFailure(
      transitionFixtureService(scheduled.context, "fixture-1", "scheduled"),
    );

    const postponed = await seedFixture("postponed");
    await expectDomainFailure(
      transitionFixtureService(postponed.context, "fixture-1", "completed"),
    );
  });

  it("clears scores when fixtures are cancelled or void", async () => {
    const cancelled = await seedFixture("scheduled");
    await updateFixture(
      cancelled.db,
      "fixture-1",
      { homeScore: 12, awayScore: 10 },
      NEXT,
    );
    const cancelledFixture = await transitionFixtureService(
      cancelled.context,
      "fixture-1",
      "cancelled",
    );
    expect(cancelledFixture.home_score).toBeNull();
    expect(cancelledFixture.away_score).toBeNull();

    const voided = await seedFixture("scheduled");
    await updateFixture(
      voided.db,
      "fixture-1",
      { homeScore: 12, awayScore: 10 },
      NEXT,
    );
    const voidFixture = await transitionFixtureService(
      voided.context,
      "fixture-1",
      "void",
    );
    expect(voidFixture.home_score).toBeNull();
    expect(voidFixture.away_score).toBeNull();
  });

  it("allows abandoned fixtures to retain explicit partial scores", async () => {
    const { context } = await seedFixture("scheduled");
    const updated = await transitionFixtureService(
      context,
      "fixture-1",
      "abandoned",
      {
        partialHomeScore: 12,
        partialAwayScore: 6,
      },
    );
    expect(updated.status).toBe("abandoned");
    expect(updated.home_score).toBe(12);
    expect(updated.away_score).toBe(6);
  });

  it("rejects partial scores for non-abandoned fixtures", async () => {
    const { context } = await seedFixture("scheduled");
    await expectDomainFailure(
      transitionFixtureService(context, "fixture-1", "postponed", {
        partialHomeScore: 12,
        partialAwayScore: 6,
      }),
    );
  });
});

describe("admin service result entry and corrections", () => {
  it("prevents completed fixtures from being directly overwritten", async () => {
    const { context } = await seedFixture("completed");
    await expectDomainFailure(
      enterFixtureResultService(context, "fixture-1", 22, 18),
    );
  });

  it("treats identical completed result submission as idempotent", async () => {
    const { context } = await seedFixture("completed");
    const updated = await enterFixtureResultService(
      context,
      "fixture-1",
      20,
      18,
    );
    expect(updated.status).toBe("completed");
    expect(updated.home_score).toBe(20);
    expect(updated.away_score).toBe(18);
  });

  it("requires correction flow for different completed result submission", async () => {
    const { context } = await seedFixture("completed");
    await expectDomainFailure(
      enterFixtureResultService(context, "fixture-1", 22, 18),
    );
  });

  it("creates immutable result correction history", async () => {
    const { db, context } = await seedFixture("completed");
    const updated = await correctFixtureResultService(
      context,
      "fixture-1",
      22,
      18,
      "Official correction",
    );

    expect(updated.home_score).toBe(22);

    const correction = await db.queryOne<{
      previous_home_score: number;
      previous_away_score: number;
      corrected_home_score: number;
      corrected_away_score: number;
      corrected_by_user_id: string;
      reason: string;
    }>("SELECT * FROM result_corrections WHERE fixture_id = ?", ["fixture-1"]);

    expect(correction?.previous_home_score).toBe(20);
    expect(correction?.previous_away_score).toBe(18);
    expect(correction?.corrected_home_score).toBe(22);
    expect(correction?.corrected_away_score).toBe(18);
    expect(correction?.corrected_by_user_id).toBe("admin-user-1");
    expect(correction?.reason).toBe("Official correction");
  });

  it("ignores duplicate identical corrections idempotently", async () => {
    const { db, context } = await seedFixture("completed");

    await correctFixtureResultService(
      context,
      "fixture-1",
      22,
      18,
      "Official correction",
    );
    await correctFixtureResultService(
      context,
      "fixture-1",
      22,
      18,
      "Official correction",
    );

    const count = await db.queryOne<{ count: number }>(
      "SELECT COUNT(*) AS count FROM result_corrections WHERE fixture_id = ?",
      ["fixture-1"],
    );

    expect(count?.count).toBe(1);
  });
});

describe("admin service normalization and season orchestration", () => {
  it("normalizes aliases consistently", () => {
    expect(normalizeAlias("  WIGAN   Warriors  ")).toBe("wigan warriors");
    expect(normalizeAlias("St. Helens!!!")).toBe("st helens");
    expect(normalizeAlias("Hull & KR")).toBe("hull and kr");
    expect(normalizeSource("  BBC Sport  ")).toBe("bbc sport");
    expect(normalizeSource(undefined)).toBe("manual");
  });

  it("enforces a single active season per competition", async () => {
    const { db, context } = await createContext();

    await createSeason(
      db,
      "season-2027",
      {
        competitionId: "competition-super-league",
        slug: "2027",
        name: "2027 Season",
        isActive: false,
      },
      NOW,
    );

    await activateSeasonService(
      context,
      "season-2027",
      "competition-super-league",
    );

    const previous = await db.queryOne<{ is_active: number }>(
      "SELECT is_active FROM seasons WHERE id = ?",
      ["season-2026"],
    );
    const active = await db.queryOne<{ is_active: number }>(
      "SELECT is_active FROM seasons WHERE id = ?",
      ["season-2027"],
    );

    expect(previous?.is_active).toBe(0);
    expect(active?.is_active).toBe(1);
  });
});

describe("repository/service separation", () => {
  it("keeps transition rules out of repository persistence primitives", async () => {
    const { db, context } = await seedFixture("completed");

    await setFixtureStatus(db, "fixture-1", "scheduled", NEXT, false);
    const repositoryMutated = await findFixtureById(db, "fixture-1");
    expect(repositoryMutated?.status).toBe("scheduled");

    await enterFixtureResult(
      db,
      "fixture-1",
      20,
      18,
      "manual",
      "admin-user-1",
      NEXT,
    );
    const completedAgain = await findFixtureById(db, "fixture-1");
    expect(completedAgain?.status).toBe("completed");

    await expectDomainFailure(
      transitionFixtureService(context, "fixture-1", "scheduled"),
    );
  });
});
