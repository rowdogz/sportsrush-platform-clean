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

  return { app, env, request, adminToken, userToken, sqlDb };
}

async function createCompetition(
  request: Awaited<ReturnType<typeof createTestHarness>>["request"],
) {
  return request("/v1/admin/competitions", {
    method: "POST",
    body: JSON.stringify({
      sportId: "sport-rugby-league",
      slug: "super-league",
      name: "Super League",
      shortName: "SL",
      countryCode: "GB",
    }),
  });
}

async function createSeason(
  request: Awaited<ReturnType<typeof createTestHarness>>["request"],
  competitionId: string,
  idSuffix = "2026",
) {
  return request("/v1/admin/seasons", {
    method: "POST",
    body: JSON.stringify({
      competitionId,
      slug: idSuffix,
      name: `${idSuffix} Season`,
      isActive: idSuffix === "2026",
    }),
  });
}

async function createTeam(
  request: Awaited<ReturnType<typeof createTestHarness>>["request"],
  slug = "wigan-warriors",
  name = "Wigan Warriors",
) {
  return request("/v1/admin/teams", {
    method: "POST",
    body: JSON.stringify({
      sportId: "sport-rugby-league",
      slug,
      name,
      shortName: name.split(" ")[0],
    }),
  });
}

describe("admin route slice 1 auth", () => {
  it("requires admin auth for admin routes", async () => {
    const { request } = await createTestHarness();
    const response = await request(
      "/v1/admin/competitions",
      { method: "GET" },
      null,
    );
    expect(response.status).toBe(401);
    const body = (await response.json()) as any;
    expect(body.error.correlationId).toBeDefined();
  });

  it("forbids non-admin users", async () => {
    const { request, userToken } = await createTestHarness();
    const response = await request(
      "/v1/admin/competitions",
      { method: "GET" },
      userToken,
    );
    expect(response.status).toBe(403);
  });
});

describe("admin route slice 1 competitions", () => {
  it("creates, lists, updates, and archives a competition", async () => {
    const { request } = await createTestHarness();

    const createResponse = await createCompetition(request);
    expect(createResponse.status).toBe(201);
    const created = (await createResponse.json()) as any;
    expect(created.data.name).toBe("Super League");
    expect(created.data.slug).toBe("super-league");

    const listResponse = await request(
      "/v1/admin/competitions?page=1&limit=10",
    );
    expect(listResponse.status).toBe(200);
    const list = (await listResponse.json()) as any;
    expect(list.data).toHaveLength(1);
    expect(list.meta).toEqual({ page: 1, limit: 10, total: 1, hasMore: false });

    const updateResponse = await request(
      `/v1/admin/competitions/${created.data.id}`,
      {
        method: "PATCH",
        body: JSON.stringify({ name: "Betfred Super League" }),
      },
    );
    expect(updateResponse.status).toBe(200);
    const updated = (await updateResponse.json()) as any;
    expect(updated.data.name).toBe("Betfred Super League");

    const archiveResponse = await request(
      `/v1/admin/competitions/${created.data.id}/archive`,
      {
        method: "POST",
      },
    );
    expect(archiveResponse.status).toBe(200);
    const archived = (await archiveResponse.json()) as any;
    expect(archived.data.is_active).toBe(0);
  });

  it("returns validation errors for invalid competition create payloads", async () => {
    const { request } = await createTestHarness();
    const response = await request("/v1/admin/competitions", {
      method: "POST",
      body: JSON.stringify({ name: "Missing sport and slug" }),
    });
    expect(response.status).toBe(400);
    const body = (await response.json()) as any;
    expect(body.error.code).toBe("VALIDATION_ERROR");
  });
});

describe("admin route slice 1 seasons", () => {
  it("creates, updates, and activates a season", async () => {
    const { request } = await createTestHarness();
    const competitionResponse = await createCompetition(request);
    const competition = (await competitionResponse.json()) as any;

    const seasonResponse = await createSeason(
      request,
      competition.data.id,
      "2026",
    );
    expect(seasonResponse.status).toBe(201);
    const season = (await seasonResponse.json()) as any;
    expect(season.data.name).toBe("2026 Season");

    const updateResponse = await request(
      `/v1/admin/seasons/${season.data.id}`,
      {
        method: "PATCH",
        body: JSON.stringify({ name: "2026 Regular Season" }),
      },
    );
    expect(updateResponse.status).toBe(200);
    const updated = (await updateResponse.json()) as any;
    expect(updated.data.name).toBe("2026 Regular Season");

    const season2027Response = await createSeason(
      request,
      competition.data.id,
      "2027",
    );
    const season2027 = (await season2027Response.json()) as any;

    const activateResponse = await request(
      `/v1/admin/seasons/${season2027.data.id}/activate`,
      {
        method: "POST",
        body: JSON.stringify({ competitionId: competition.data.id }),
      },
    );
    expect(activateResponse.status).toBe(200);
    const activated = (await activateResponse.json()) as any;
    expect(activated.data.is_active).toBe(1);

    const listResponse = await request(
      `/v1/admin/seasons?competitionId=${competition.data.id}&search=2027`,
    );
    expect(listResponse.status).toBe(200);
    const list = (await listResponse.json()) as any;
    expect(list.data).toHaveLength(1);
    expect(list.data[0].id).toBe(season2027.data.id);
  });

  it("returns validation errors for invalid activation payloads", async () => {
    const { request } = await createTestHarness();
    const response = await request("/v1/admin/seasons/missing/activate", {
      method: "POST",
      body: JSON.stringify({}),
    });
    expect(response.status).toBe(400);
  });
});

describe("admin route slice 1 teams", () => {
  it("creates, lists, updates, and archives a team", async () => {
    const { request } = await createTestHarness();

    const createResponse = await createTeam(request);
    expect(createResponse.status).toBe(201);
    const created = (await createResponse.json()) as any;
    expect(created.data.name).toBe("Wigan Warriors");

    await createTeam(request, "st-helens", "St Helens");

    const listResponse = await request("/v1/admin/teams?page=1&limit=1");
    expect(listResponse.status).toBe(200);
    const list = (await listResponse.json()) as any;
    expect(list.data).toHaveLength(1);
    expect(list.meta).toEqual({ page: 1, limit: 1, total: 2, hasMore: true });

    const updateResponse = await request(`/v1/admin/teams/${created.data.id}`, {
      method: "PATCH",
      body: JSON.stringify({ displayName: "Wigan Warriors RLFC" }),
    });
    expect(updateResponse.status).toBe(200);
    const updated = (await updateResponse.json()) as any;
    expect(updated.data.display_name).toBe("Wigan Warriors RLFC");

    const archiveResponse = await request(
      `/v1/admin/teams/${created.data.id}/archive`,
      {
        method: "POST",
      },
    );
    expect(archiveResponse.status).toBe(200);
    const archived = (await archiveResponse.json()) as any;
    expect(archived.data.is_active).toBe(0);
  });

  it("returns validation errors for invalid team payloads", async () => {
    const { request } = await createTestHarness();
    const response = await request("/v1/admin/teams", {
      method: "POST",
      body: JSON.stringify({ name: "Missing sport and slug" }),
    });
    expect(response.status).toBe(400);
  });
});

describe("admin route/service separation", () => {
  it("routes import services and do not import repositories directly", () => {
    const routeSource = readFileSync(adminRoutePath, "utf8");
    expect(routeSource).toContain("../admin/service");
    expect(routeSource).not.toContain("../admin/repository");
  });
});
