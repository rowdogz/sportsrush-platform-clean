import { readFileSync } from "node:fs";
import { resolve } from "node:path";
import { describe, expect, it } from "vitest";
import initSqlJs from "sql.js";
import { createApp } from "../app";

type SqlJsDatabase = initSqlJs.Database;

type TestEnv = {
  readonly ENVIRONMENT: "development";
  readonly API_VERSION: string;
  readonly JWT_SECRET: string;
  readonly DB: D1Database;
};

const migrationPaths = [
  resolve(__dirname, "../../migrations/0001_foundation.sql"),
  resolve(__dirname, "../../migrations/0002_auth_schema.sql"),
  resolve(
    __dirname,
    "../../migrations/0003_competitions_teams_fixtures_results.sql",
  ),
  resolve(__dirname, "../../migrations/0004_admin_audit_events.sql"),
];

const NOW = "2026-05-14T12:00:00.000Z";

function normalizeParams(
  params: readonly unknown[] = [],
): initSqlJs.BindParams {
  return params.map((value) => value ?? null) as initSqlJs.BindParams;
}

function makeD1Result<T extends Record<string, unknown>>(
  results: T[] = [],
): D1Result<T> {
  return {
    success: true,
    results,
    meta: {
      duration: 0,
      size_after: 0,
      rows_read: results.length,
      rows_written: 0,
      last_row_id: 0,
      changed_db: false,
      changes: 0,
    },
  } as D1Result<T>;
}

function makePreparedStatement(
  sqlDb: SqlJsDatabase,
  sql: string,
): D1PreparedStatement {
  let boundParams: readonly unknown[] = [];
  const statement = {
    bind(...params: unknown[]) {
      boundParams = params;
      return statement as unknown as D1PreparedStatement;
    },
    async first<T extends Record<string, unknown>>() {
      const stmt = sqlDb.prepare(sql);
      try {
        stmt.bind(normalizeParams(boundParams));
        if (!stmt.step()) return null;
        return stmt.getAsObject() as T;
      } finally {
        stmt.free();
      }
    },
    async all<T extends Record<string, unknown>>() {
      const stmt = sqlDb.prepare(sql);
      const results: T[] = [];
      try {
        stmt.bind(normalizeParams(boundParams));
        while (stmt.step()) results.push(stmt.getAsObject() as T);
        return makeD1Result(results);
      } finally {
        stmt.free();
      }
    },
    async run<T extends Record<string, unknown>>() {
      sqlDb.run(sql, normalizeParams(boundParams));
      return makeD1Result<T>();
    },
    async raw() {
      return [];
    },
  };
  return statement as unknown as D1PreparedStatement;
}

function makeD1Database(sqlDb: SqlJsDatabase): D1Database {
  return {
    prepare(sql: string) {
      return makePreparedStatement(sqlDb, sql);
    },
    async batch<T extends Record<string, unknown>>(
      statements: D1PreparedStatement[],
    ) {
      const results: D1Result<T>[] = [];
      for (const statement of statements)
        results.push(await statement.run<T>());
      return results;
    },
    async exec(sql: string) {
      sqlDb.run(sql);
      return { count: 0, duration: 0 };
    },
    dump: async () => new ArrayBuffer(0),
  } as unknown as D1Database;
}

async function createSqlDb(): Promise<SqlJsDatabase> {
  const SQL = await initSqlJs();
  const db = new SQL.Database();
  db.run("PRAGMA foreign_keys = ON;");
  migrationPaths.forEach((migrationPath) =>
    db.run(readFileSync(migrationPath, "utf8")),
  );
  seedPublicData(db);
  return db;
}

function seedPublicData(db: SqlJsDatabase): void {
  db.run(
    "INSERT INTO sports (id, slug, name, created_at, updated_at) VALUES (?, ?, ?, ?, ?)",
    ["sport-rugby-league", "rugby-league", "Rugby League", NOW, NOW],
  );
  db.run(
    `INSERT INTO competitions (id, sport_id, slug, name, short_name, country_code, is_active, created_at, updated_at)
     VALUES
       ('comp-sl', 'sport-rugby-league', 'super-league', 'Super League', 'SL', 'GB', 1, ?, ?),
       ('comp-archive', 'sport-rugby-league', 'archived', 'Archived League', NULL, 'GB', 0, ?, ?)`,
    [NOW, NOW, NOW, NOW],
  );
  db.run(
    `INSERT INTO seasons (id, competition_id, slug, name, starts_on, ends_on, is_active, created_at, updated_at)
     VALUES
       ('season-2026', 'comp-sl', '2026', '2026', '2026-02-01', '2026-10-31', 1, ?, ?),
       ('season-archived', 'comp-sl', '2025', '2025', '2025-02-01', '2025-10-31', 0, ?, ?)`,
    [NOW, NOW, NOW, NOW],
  );
  db.run(
    `INSERT INTO teams (id, sport_id, slug, name, short_name, display_name, country_code, is_active, created_at, updated_at)
     VALUES
       ('team-wigan', 'sport-rugby-league', 'wigan-warriors', 'Wigan Warriors', 'Wigan', 'Wigan Warriors', 'GB', 1, ?, ?),
       ('team-saints', 'sport-rugby-league', 'st-helens', 'St Helens', 'Saints', 'St Helens', 'GB', 1, ?, ?),
       ('team-leeds', 'sport-rugby-league', 'leeds-rhinos', 'Leeds Rhinos', 'Leeds', 'Leeds Rhinos', 'GB', 1, ?, ?)`,
    [NOW, NOW, NOW, NOW, NOW, NOW],
  );
  db.run(
    `INSERT INTO rounds (id, season_id, round, round_name, display_order, starts_at, ends_at, created_at, updated_at)
     VALUES
       ('round-1', 'season-2026', '1', 'Round 1', 1, '2026-02-12T00:00:00.000Z', '2026-02-15T23:59:59.000Z', ?, ?),
       ('round-2', 'season-2026', '2', 'Round 2', 2, '2026-02-19T00:00:00.000Z', '2026-02-22T23:59:59.000Z', ?, ?)`,
    [NOW, NOW, NOW, NOW],
  );
  db.run(
    `INSERT INTO fixtures
       (id, sport_id, competition_id, season_id, round_id, round, round_name, round_order,
        home_team_id, away_team_id, scheduled_at, venue_name, status, home_score, away_score, created_at, updated_at)
     VALUES
       ('fixture-2', 'sport-rugby-league', 'comp-sl', 'season-2026', 'round-1', '1', 'Round 1', 1,
        'team-saints', 'team-leeds', '2026-02-12T20:00:00.000Z', 'Totally Wicked Stadium', 'completed', 24, 18, ?, ?),
       ('fixture-1', 'sport-rugby-league', 'comp-sl', 'season-2026', 'round-1', '1', 'Round 1', 1,
        'team-wigan', 'team-saints', '2026-02-13T20:00:00.000Z', 'The Brick Community Stadium', 'scheduled', NULL, NULL, ?, ?),
       ('fixture-3', 'sport-rugby-league', 'comp-sl', 'season-2026', 'round-2', '2', 'Round 2', 2,
        'team-leeds', 'team-wigan', '2026-02-20T20:00:00.000Z', 'Headingley', 'abandoned', 10, 8, ?, ?),
       ('fixture-4', 'sport-rugby-league', 'comp-sl', 'season-2026', 'round-2', '2', 'Round 2', 2,
        'team-wigan', 'team-leeds', '2026-02-21T20:00:00.000Z', 'The Brick Community Stadium', 'void', NULL, NULL, ?, ?)`,
    [NOW, NOW, NOW, NOW, NOW, NOW, NOW, NOW],
  );
}

type JsonObject = Record<string, any>;

async function readJson(response: Response): Promise<JsonObject> {
  return (await response.json()) as JsonObject;
}

async function createTestHarness() {
  const sqlDb = await createSqlDb();
  const env: TestEnv = {
    ENVIRONMENT: "development",
    API_VERSION: "test",
    JWT_SECRET: "test-secret-that-is-at-least-32-bytes-long",
    DB: makeD1Database(sqlDb),
  };
  const app = createApp();
  return {
    sqlDb,
    request: (path: string, init: RequestInit = {}) =>
      app.request(path, init, env),
  };
}

describe("public read API", () => {
  it("lists public competitions without authentication and hides internal fields", async () => {
    const { request } = await createTestHarness();
    const response = await request("/v1/public/competitions");
    const body = await readJson(response);

    expect(response.status).toBe(200);
    expect(body.data).toHaveLength(1);
    expect(body.data[0]).toMatchObject({
      id: "comp-sl",
      name: "Super League",
      shortName: "SL",
    });
    expect(body.data[0]).not.toHaveProperty("created_at");
    expect(body.data[0]).not.toHaveProperty("legacy_id");
  });

  it("lists seasons and rounds with public competition context", async () => {
    const { request } = await createTestHarness();

    const seasonsResponse = await request(
      "/v1/public/seasons?competitionId=comp-sl",
    );
    const seasonsBody = await readJson(seasonsResponse);
    expect(seasonsBody.data).toHaveLength(1);
    expect(seasonsBody.data[0].competition).toMatchObject({
      id: "comp-sl",
      name: "Super League",
    });

    const roundsResponse = await request(
      "/v1/public/rounds?seasonId=season-2026",
    );
    const roundsBody = await readJson(roundsResponse);
    expect(roundsBody.data.map((round: { id: string }) => round.id)).toEqual([
      "round-1",
      "round-2",
    ]);
  });

  it("lists fixtures sorted by kickoff time with public DTO fields", async () => {
    const { request } = await createTestHarness();
    const response = await request("/v1/public/fixtures?limit=2");
    const body = await readJson(response);

    expect(response.status).toBe(200);
    expect(body.meta).toMatchObject({
      page: 1,
      limit: 2,
      total: 4,
      hasMore: true,
    });
    expect(body.data.map((fixture: { id: string }) => fixture.id)).toEqual([
      "fixture-2",
      "fixture-1",
    ]);
    expect(body.data[0]).toMatchObject({
      id: "fixture-2",
      kickoffTime: "2026-02-12T20:00:00.000Z",
      venue: "Totally Wicked Stadium",
      status: "completed",
      homeScore: 24,
      awayScore: 18,
      homeTeam: {
        name: "St Helens",
        shortName: "Saints",
        logoUrl: null,
        badgeUrl: null,
      },
      awayTeam: { name: "Leeds Rhinos" },
      round: { id: "round-1", name: "Round 1" },
      competition: { id: "comp-sl", name: "Super League" },
    });
    expect(body.data[0]).not.toHaveProperty("resultSource");
    expect(body.data[0]).not.toHaveProperty("created_at");
  });

  it("filters fixtures by competition, season, round, status and date range", async () => {
    const { request } = await createTestHarness();
    const response = await request(
      "/v1/public/fixtures?competitionId=comp-sl&seasonId=season-2026&roundId=round-1&status=scheduled&fromDate=2026-02-13T00%3A00%3A00.000Z&toDate=2026-02-14T00%3A00%3A00.000Z",
    );
    const body = await readJson(response);

    expect(body.data).toHaveLength(1);
    expect(body.data[0].id).toBe("fixture-1");
  });

  it("paginates public fixtures", async () => {
    const { request } = await createTestHarness();
    const response = await request("/v1/public/fixtures?page=2&limit=2");
    const body = await readJson(response);

    expect(body.meta).toMatchObject({
      page: 2,
      limit: 2,
      total: 4,
      hasMore: false,
    });
    expect(body.data.map((fixture: { id: string }) => fixture.id)).toEqual([
      "fixture-3",
      "fixture-4",
    ]);
  });

  it("serializes abandoned partial scores and maps void to cancelled", async () => {
    const { request } = await createTestHarness();

    const abandonedResponse = await request(
      "/v1/public/fixtures?status=abandoned",
    );
    const abandonedBody = await readJson(abandonedResponse);
    expect(abandonedBody.data[0]).toMatchObject({
      status: "abandoned",
      homeScore: 10,
      awayScore: 8,
    });

    const fixtureResponse = await request("/v1/public/fixtures/fixture-4");
    const fixtureBody = await readJson(fixtureResponse);
    expect(fixtureBody.data).toMatchObject({
      id: "fixture-4",
      status: "cancelled",
    });

    const cancelledResponse = await request(
      "/v1/public/fixtures?status=cancelled",
    );
    const cancelledBody = await readJson(cancelledResponse);
    expect(
      cancelledBody.data.map((fixture: { id: string }) => fixture.id),
    ).toEqual(["fixture-4"]);
  });

  it("returns a single fixture by ID", async () => {
    const { request } = await createTestHarness();
    const response = await request("/v1/public/fixtures/fixture-1");
    const body = await readJson(response);

    expect(response.status).toBe(200);
    expect(body.data).toMatchObject({
      id: "fixture-1",
      homeTeam: { name: "Wigan Warriors" },
    });
  });

  it("validates query parameters and route not found cases", async () => {
    const { request } = await createTestHarness();

    const invalidResponse = await request(
      "/v1/public/fixtures?status=invalid&page=0",
    );
    expect(invalidResponse.status).toBe(400);

    const missingResponse = await request(
      "/v1/public/fixtures/missing-fixture",
    );
    expect(missingResponse.status).toBe(404);
  });

  it("documents public endpoints in OpenAPI", async () => {
    const { request } = await createTestHarness();
    const response = await request("/openapi.json");
    const spec = await readJson(response);

    expect(spec.paths["/v1/public/competitions"]).toBeDefined();
    expect(spec.paths["/v1/public/seasons"]).toBeDefined();
    expect(spec.paths["/v1/public/rounds"]).toBeDefined();
    expect(spec.paths["/v1/public/fixtures"]).toBeDefined();
    expect(spec.paths["/v1/public/fixtures/{id}"]).toBeDefined();
  });
});
