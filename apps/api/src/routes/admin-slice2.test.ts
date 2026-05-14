import { describe, expect, it } from "vitest";
import initSqlJs from "sql.js";
import { readFileSync } from "node:fs";
import { resolve } from "node:path";
import { createAccessToken } from "@sr/auth";
import { createApp } from "../app";

const migrationPath = resolve(
  __dirname,
  "../../migrations/0003_competitions_teams_fixtures_results.sql",
);
const adminRoutePath = resolve(__dirname, "./admin.ts");

const JWT_SECRET = "test-secret-that-is-at-least-32-bytes-long";
const NOW = "2026-05-14T12:00:00.000Z";

type SqlJsDatabase = initSqlJs.Database;

type TestEnv = {
  readonly ENVIRONMENT: "development";
  readonly API_VERSION: string;
  readonly JWT_SECRET: string;
  readonly DB: D1Database;
};

async function createSqlDb(): Promise<SqlJsDatabase> {
  const SQL = await initSqlJs();
  const db = new SQL.Database();
  db.run("PRAGMA foreign_keys = ON;");
  db.run(readFileSync(migrationPath, "utf8"));
  db.run(
    `INSERT INTO sports (id, slug, name, created_at, updated_at)
     VALUES ('sport-rugby-league', 'rugby-league', 'Rugby League', ?, ?)`,
    [NOW, NOW],
  );
  return db;
}

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
      for (const statement of statements) {
        results.push(await statement.run<T>());
      }
      return results;
    },
    async exec(sql: string) {
      sqlDb.run(sql);
      return { count: 0, duration: 0 };
    },
    dump: async () => new ArrayBuffer(0),
  } as unknown as D1Database;
}

async function createTestHarness() {
  const sqlDb = await createSqlDb();
  const env: TestEnv = {
    ENVIRONMENT: "development",
    API_VERSION: "test",
    JWT_SECRET,
    DB: makeD1Database(sqlDb),
  };
  const app = createApp();
  const adminToken = await createAccessToken(
    { userId: "admin-user", role: "admin", sessionId: "session-admin" },
    JWT_SECRET,
  );
  const userToken = await createAccessToken(
    { userId: "normal-user", role: "user", sessionId: "session-user" },
    JWT_SECRET,
  );

  async function request(
    path: string,
    init: RequestInit = {},
    token: string | null = adminToken,
  ) {
    const headers = new Headers(init.headers);
    if (token !== null) headers.set("authorization", `Bearer ${token}`);
    if (init.body !== undefined && !headers.has("content-type")) {
      headers.set("content-type", "application/json");
    }
    return app.request(path, { ...init, headers }, env);
  }

  return { request, userToken, sqlDb };
}

async function createCompetition(
  request: Awaited<ReturnType<typeof createTestHarness>>["request"],
) {
  const response = await request("/v1/admin/competitions", {
    method: "POST",
    body: JSON.stringify({
      sportId: "sport-rugby-league",
      slug: "super-league",
      name: "Super League",
    }),
  });
  const body = (await response.json()) as any;
  return (body as any).data as { id: string };
}

async function createSeason(
  request: Awaited<ReturnType<typeof createTestHarness>>["request"],
  competitionId: string,
) {
  const response = await request("/v1/admin/seasons", {
    method: "POST",
    body: JSON.stringify({
      competitionId,
      slug: "2026",
      name: "2026 Season",
      isActive: true,
    }),
  });
  const body = (await response.json()) as any;
  return (body as any).data as { id: string };
}

async function createTeam(
  request: Awaited<ReturnType<typeof createTestHarness>>["request"],
  slug: string,
  name: string,
) {
  const response = await request("/v1/admin/teams", {
    method: "POST",
    body: JSON.stringify({
      sportId: "sport-rugby-league",
      slug,
      name,
    }),
  });
  const body = (await response.json()) as any;
  return (body as any).data as { id: string };
}

async function seedCore(
  request: Awaited<ReturnType<typeof createTestHarness>>["request"],
) {
  const competition = await createCompetition(request);
  const season = await createSeason(request, competition.id);
  const homeTeam = await createTeam(
    request,
    "wigan-warriors",
    "Wigan Warriors",
  );
  const awayTeam = await createTeam(request, "st-helens", "St Helens");
  return { competition, season, homeTeam, awayTeam };
}

async function createFixture(
  request: Awaited<ReturnType<typeof createTestHarness>>["request"],
) {
  const { competition, season, homeTeam, awayTeam } = await seedCore(request);
  const response = await request("/v1/admin/fixtures", {
    method: "POST",
    body: JSON.stringify({
      sportId: "sport-rugby-league",
      competitionId: competition.id,
      seasonId: season.id,
      round: "1",
      roundName: "Round 1",
      roundOrder: 1,
      homeTeamId: homeTeam.id,
      awayTeamId: awayTeam.id,
      scheduledAt: "2026-02-01T20:00:00.000Z",
      status: "scheduled",
    }),
  });
  const body = (await response.json()) as any;
  return {
    fixture: (body as any).data as { id: string },
    competition,
    season,
    homeTeam,
    awayTeam,
  };
}

describe("admin route slice 2 auth", () => {
  it("requires admin auth", async () => {
    const { request } = await createTestHarness();
    const response = await request(
      "/v1/admin/team-aliases?sportId=sport-rugby-league",
      {},
      null,
    );
    expect(response.status).toBe(401);
  });

  it("forbids non-admin users", async () => {
    const { request, userToken } = await createTestHarness();
    const response = await request(
      "/v1/admin/fixtures",
      { method: "GET" },
      userToken,
    );
    expect(response.status).toBe(403);
  });
});

describe("admin route slice 2 aliases", () => {
  it("creates, updates, lists, looks up, and deletes aliases", async () => {
    const { request } = await createTestHarness();
    const team = await createTeam(request, "wigan-warriors", "Wigan Warriors");

    const createResponse = await request("/v1/admin/team-aliases", {
      method: "POST",
      body: JSON.stringify({
        teamId: team.id,
        sportId: "sport-rugby-league",
        alias: "  WIGAN Warriors  ",
        source: "BBC Sport",
      }),
    });
    expect(createResponse.status).toBe(201);
    const created = (await createResponse.json()) as any;
    expect(created.data.normalized_alias).toBe("wigan warriors");
    expect(created.data.source).toBe("bbc sport");

    const lookupResponse = await request(
      "/v1/admin/team-aliases?sportId=sport-rugby-league&source=BBC%20Sport&alias=WIGAN%20Warriors",
    );
    expect(lookupResponse.status).toBe(200);
    const lookup = (await lookupResponse.json()) as any;
    expect(lookup.data.id).toBe(created.data.id);

    const listResponse = await request(
      "/v1/admin/team-aliases?sportId=sport-rugby-league&source=BBC%20Sport",
    );
    expect(listResponse.status).toBe(200);
    const list = (await listResponse.json()) as any;
    expect(list.data).toHaveLength(1);

    const updateResponse = await request(
      `/v1/admin/team-aliases/${created.data.id}`,
      {
        method: "PATCH",
        body: JSON.stringify({ alias: "Wigan RL", source: "Sportradar" }),
      },
    );
    expect(updateResponse.status).toBe(200);
    const updated = (await updateResponse.json()) as any;
    expect(updated.data.normalized_alias).toBe("wigan rl");
    expect(updated.data.source).toBe("sportradar");

    const deleteResponse = await request(
      `/v1/admin/team-aliases/${created.data.id}`,
      {
        method: "DELETE",
      },
    );
    expect(deleteResponse.status).toBe(200);
    const deleted = (await deleteResponse.json()) as any;
    expect(deleted.data.deleted).toBe(true);
  });

  it("returns conflict-style domain error for duplicate normalized aliases", async () => {
    const { request } = await createTestHarness();
    const team = await createTeam(request, "wigan-warriors", "Wigan Warriors");

    const body = {
      teamId: team.id,
      sportId: "sport-rugby-league",
      alias: "Wigan Warriors",
      source: "BBC",
    };

    const first = await request("/v1/admin/team-aliases", {
      method: "POST",
      body: JSON.stringify(body),
    });
    expect(first.status).toBe(201);

    const second = await request("/v1/admin/team-aliases", {
      method: "POST",
      body: JSON.stringify({ ...body, alias: " wigan   warriors " }),
    });
    expect(second.status).toBe(422);
    const error = (await second.json()) as any;
    expect(error.error.code).toBe("ADMIN_DOMAIN_ERROR");
  });

  it("validates alias query requirements", async () => {
    const { request } = await createTestHarness();
    const response = await request("/v1/admin/team-aliases");
    expect(response.status).toBe(400);
  });
});

describe("admin route slice 2 rounds", () => {
  it("creates, updates, and lists rounds", async () => {
    const { request } = await createTestHarness();
    const competition = await createCompetition(request);
    const season = await createSeason(request, competition.id);

    const createResponse = await request("/v1/admin/rounds", {
      method: "POST",
      body: JSON.stringify({
        seasonId: season.id,
        round: "QF",
        roundName: "Quarter Final",
        displayOrder: 10,
      }),
    });
    expect(createResponse.status).toBe(201);
    const created = (await createResponse.json()) as any;
    expect(created.data.round_name).toBe("Quarter Final");

    const updateResponse = await request(
      `/v1/admin/rounds/${created.data.id}`,
      {
        method: "PATCH",
        body: JSON.stringify({ roundName: "Semi Final", displayOrder: 20 }),
      },
    );
    expect(updateResponse.status).toBe(200);
    const updated = (await updateResponse.json()) as any;
    expect(updated.data.round_name).toBe("Semi Final");

    const listResponse = await request(
      `/v1/admin/rounds?seasonId=${season.id}`,
    );
    expect(listResponse.status).toBe(200);
    const list = (await listResponse.json()) as any;
    expect(list.data).toHaveLength(1);
    expect(list.data[0].round).toBe("QF");
  });
});

describe("admin route slice 2 fixtures", () => {
  it("creates, updates, gets, lists, and filters fixtures", async () => {
    const { request } = await createTestHarness();
    const { fixture, season } = await createFixture(request);

    const getResponse = await request(`/v1/admin/fixtures/${fixture.id}`);
    expect(getResponse.status).toBe(200);
    const got = (await getResponse.json()) as any;
    expect(got.data.id).toBe(fixture.id);

    const updateResponse = await request(`/v1/admin/fixtures/${fixture.id}`, {
      method: "PATCH",
      body: JSON.stringify({ venueName: "DW Stadium" }),
    });
    expect(updateResponse.status).toBe(200);
    const updated = (await updateResponse.json()) as any;
    expect(updated.data.venue_name).toBe("DW Stadium");

    const listResponse = await request(
      `/v1/admin/fixtures?seasonId=${season.id}&round=1&status=scheduled&page=1&limit=10`,
    );
    expect(listResponse.status).toBe(200);
    const list = (await listResponse.json()) as any;
    expect(list.data).toHaveLength(1);
    expect(list.meta).toEqual({ page: 1, limit: 10, total: 1, hasMore: false });
  });

  it("transitions fixture status", async () => {
    const { request } = await createTestHarness();
    const { fixture } = await createFixture(request);

    const response = await request(
      `/v1/admin/fixtures/${fixture.id}/transition`,
      {
        method: "POST",
        body: JSON.stringify({ status: "postponed" }),
      },
    );
    expect(response.status).toBe(200);
    const body = (await response.json()) as any;
    expect(body.data.status).toBe("postponed");
  });

  it("enters fixture results", async () => {
    const { request } = await createTestHarness();
    const { fixture } = await createFixture(request);

    const response = await request(`/v1/admin/fixtures/${fixture.id}/result`, {
      method: "POST",
      body: JSON.stringify({
        homeScore: 22,
        awayScore: 18,
        resultSource: "manual",
      }),
    });
    expect(response.status).toBe(200);
    const body = (await response.json()) as any;
    expect(body.data.status).toBe("completed");
    expect(body.data.home_score).toBe(22);
    expect(body.data.away_score).toBe(18);
  });

  it("corrects fixture results with audit flow", async () => {
    const { request, sqlDb } = await createTestHarness();
    const { fixture } = await createFixture(request);

    await request(`/v1/admin/fixtures/${fixture.id}/result`, {
      method: "POST",
      body: JSON.stringify({
        homeScore: 22,
        awayScore: 18,
        resultSource: "manual",
      }),
    });

    const response = await request(
      `/v1/admin/fixtures/${fixture.id}/correct-result`,
      {
        method: "POST",
        body: JSON.stringify({
          homeScore: 24,
          awayScore: 18,
          reason: "Official correction",
        }),
      },
    );
    expect(response.status).toBe(200);
    const body = (await response.json()) as any;
    expect(body.data.home_score).toBe(24);

    const auditRows = sqlDb.exec(
      "SELECT previous_home_score, corrected_home_score FROM result_corrections WHERE fixture_id = '" +
        fixture.id +
        "'",
    )[0]!.values;
    expect(auditRows[0]).toEqual([22, 24]);
  });

  it("validates fixture payloads and filters", async () => {
    const { request } = await createTestHarness();
    const badCreate = await request("/v1/admin/fixtures", {
      method: "POST",
      body: JSON.stringify({ round: "1" }),
    });
    expect(badCreate.status).toBe(400);

    const badFilter = await request("/v1/admin/fixtures?status=live");
    expect(badFilter.status).toBe(400);
  });
});

describe("admin route slice 2 service separation", () => {
  it("routes use services and do not import repositories directly", () => {
    const routeSource = readFileSync(adminRoutePath, "utf8");
    expect(routeSource).toContain("../admin/service");
    expect(routeSource).not.toContain("../admin/repository");
  });
});
