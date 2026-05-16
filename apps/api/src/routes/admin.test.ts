import { describe, expect, it } from "vitest";
import initSqlJs from "sql.js";
import { readFileSync } from "node:fs";
import { resolve } from "node:path";
import { createAccessToken } from "@sr/auth";
import { createApp } from "../app";

const migrationPaths = [
  resolve(__dirname, "../../migrations/0001_foundation.sql"),
  resolve(__dirname, "../../migrations/0002_auth_schema.sql"),
  resolve(
    __dirname,
    "../../migrations/0003_competitions_teams_fixtures_results.sql",
  ),
  resolve(__dirname, "../../migrations/0004_admin_audit_events.sql"),
];
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
  migrationPaths.forEach((migrationPath) => {
    db.run(readFileSync(migrationPath, "utf8"));
  });
  db.run(
    `INSERT INTO sports (id, slug, name, created_at, updated_at)
     VALUES ('sport-rugby-league', 'rugby-league', 'Rugby League', ?, ?)`,
    [NOW, NOW],
  );
  db.run(
    `INSERT INTO users
       (id, email, email_normalized, role, is_active, is_legacy_migration, created_at, updated_at)
     VALUES ('admin-user', 'actor-admin@example.test', 'actor-admin@example.test', 'admin', 1, 0, ?, ?)`,
    [NOW, NOW],
  );
  db.run(
    `INSERT INTO user_profiles (user_id, display_name, timezone, created_at, updated_at)
     VALUES ('admin-user', 'Admin User', 'UTC', ?, ?)`,
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

function seedUser(
  sqlDb: SqlJsDatabase,
  {
    id,
    email,
    displayName,
    role = "user",
    isActive = 1,
  }: {
    readonly id: string;
    readonly email: string;
    readonly displayName: string;
    readonly role?: string;
    readonly isActive?: number;
  },
) {
  sqlDb.run(
    `INSERT OR REPLACE INTO users
       (id, email, email_normalized, role, is_active, is_legacy_migration, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, 0, ?, ?)`,
    [id, email, email.toLowerCase(), role, isActive, NOW, NOW],
  );
  sqlDb.run(
    `INSERT OR REPLACE INTO user_profiles (user_id, display_name, timezone, created_at, updated_at)
     VALUES (?, ?, 'UTC', ?, ?)`,
    [id, displayName, NOW, NOW],
  );
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

  it("requires admin auth for audit event export", async () => {
    const { request } = await createTestHarness();
    const response = await request(
      "/v1/admin/audit-events/export",
      { method: "GET" },
      null,
    );
    expect(response.status).toBe(401);
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

describe("admin route slice 1 users", () => {
  it("lists, filters, and paginates audit events", async () => {
    const { request, sqlDb } = await createTestHarness();
    seedUser(sqlDb, {
      id: "user-1",
      email: "alice@example.test",
      displayName: "Alice Example",
    });
    sqlDb.run(
      `INSERT INTO audit_events
         (id, actor_user_id, action, target_type, target_id, before_metadata, after_metadata, created_at)
       VALUES
         ('audit-1', 'user-1', 'team.update', 'team', 'team-1', '{"name":"Old"}', '{"name":"New"}', '2026-05-14T12:00:00.000Z'),
         ('audit-2', 'admin-user', 'user.role.update', 'user', 'user-1', '{"role":"user"}', '{"role":"admin"}', '2026-05-14T13:00:00.000Z')`,
    );

    const listResponse = await request("/v1/admin/audit-events?page=1&limit=1");
    expect(listResponse.status).toBe(200);
    const list = (await listResponse.json()) as any;
    expect(list.data).toHaveLength(1);
    expect(list.data[0]).toMatchObject({
      id: "audit-2",
      actorUserId: "admin-user",
      actorEmail: "actor-admin@example.test",
      actorDisplayName: "Admin User",
      action: "user.role.update",
      entityType: "user",
      entityId: "user-1",
      summary: "user.role.update on user user-1",
      correlationId: null,
    });
    expect(list.data[0].changes.role).toEqual({
      before: "user",
      after: "admin",
    });
    expect(list.meta).toEqual({ page: 1, limit: 1, total: 2, hasMore: true });

    const filteredResponse = await request(
      "/v1/admin/audit-events?actorUserId=user-1&entityType=team&entityId=team-1&action=team.update&dateFrom=2026-05-14T11%3A00%3A00.000Z&dateTo=2026-05-14T12%3A30%3A00.000Z",
    );
    expect(filteredResponse.status).toBe(200);
    const filtered = (await filteredResponse.json()) as any;
    expect(filtered.data).toHaveLength(1);
    expect(filtered.data[0].id).toBe("audit-1");
  });

  it("exports filtered audit events as CSV", async () => {
    const { request, sqlDb } = await createTestHarness();
    seedUser(sqlDb, {
      id: "user-1",
      email: "alice@example.test",
      displayName: 'Alice "Example"',
    });
    sqlDb.run(
      `INSERT INTO audit_events
         (id, actor_user_id, action, target_type, target_id, before_metadata, after_metadata, created_at)
       VALUES
         ('audit-1', 'user-1', 'team.update', 'team', 'team-1', '{"name":"Old, Name"}', '{"name":"New Name"}', '2026-05-14T12:00:00.000Z'),
         ('audit-2', 'admin-user', 'user.role.update', 'user', 'user-1', '{"role":"user"}', '{"role":"admin"}', '2026-05-14T13:00:00.000Z')`,
    );

    const response = await request(
      "/v1/admin/audit-events/export?actorUserId=user-1&entityType=team&entityId=team-1&action=team.update&dateFrom=2026-05-14T11%3A00%3A00.000Z&dateTo=2026-05-14T12%3A30%3A00.000Z",
    );
    expect(response.status).toBe(200);
    expect(response.headers.get("content-type")).toContain("text/csv");
    expect(response.headers.get("content-disposition")).toContain(
      'filename="audit-events-',
    );
    const csv = await response.text();
    expect(csv).toContain(
      "occurredAt,actorUserId,actorEmail,actorDisplayName,action,entityType,entityId,summary,before,after,correlationId",
    );
    expect(csv).toContain('"alice@example.test","Alice ""Example"""');
    expect(csv).toContain('"{""name"":""Old, Name""}"');
    expect(csv).not.toContain("user.role.update");
  });

  it("lists and filters admin users", async () => {
    const { request, sqlDb } = await createTestHarness();
    seedUser(sqlDb, {
      id: "user-1",
      email: "alice@example.test",
      displayName: "Alice Example",
      role: "user",
    });
    seedUser(sqlDb, {
      id: "user-2",
      email: "admin@example.test",
      displayName: "Admin Example",
      role: "admin",
      isActive: 0,
    });

    const listResponse = await request("/v1/admin/users?page=1&limit=10");
    expect(listResponse.status).toBe(200);
    const list = (await listResponse.json()) as any;
    expect(list.data).toHaveLength(3);
    expect(list.data[0].display_name).toBeDefined();
    expect(list.meta).toEqual({ page: 1, limit: 10, total: 3, hasMore: false });

    const filteredResponse = await request(
      "/v1/admin/users?search=admin&role=admin&isActive=false",
    );
    expect(filteredResponse.status).toBe(200);
    const filtered = (await filteredResponse.json()) as any;
    expect(filtered.data).toHaveLength(1);
    expect(filtered.data[0].email).toBe("admin@example.test");
    expect(filtered.data[0].is_active).toBe(0);
  });

  it("updates user role and status", async () => {
    const { request, sqlDb } = await createTestHarness();
    seedUser(sqlDb, {
      id: "user-1",
      email: "alice@example.test",
      displayName: "Alice Example",
      role: "user",
    });

    const roleResponse = await request("/v1/admin/users/user-1/role", {
      method: "PATCH",
      body: JSON.stringify({ role: "admin" }),
    });
    expect(roleResponse.status).toBe(200);
    const roleBody = (await roleResponse.json()) as any;
    expect(roleBody.data.role).toBe("admin");

    const statusResponse = await request("/v1/admin/users/user-1/status", {
      method: "PATCH",
      body: JSON.stringify({ isActive: false }),
    });
    expect(statusResponse.status).toBe(200);
    const statusBody = (await statusResponse.json()) as any;
    expect(statusBody.data.is_active).toBe(0);
  });

  it("suspends and reactivates users", async () => {
    const { request, sqlDb } = await createTestHarness();
    seedUser(sqlDb, {
      id: "user-1",
      email: "alice@example.test",
      displayName: "Alice Example",
    });

    const suspendResponse = await request("/v1/admin/users/user-1/suspend", {
      method: "POST",
    });
    expect(suspendResponse.status).toBe(200);
    const suspended = (await suspendResponse.json()) as any;
    expect(suspended.data.is_active).toBe(0);

    const reactivateResponse = await request(
      "/v1/admin/users/user-1/reactivate",
      {
        method: "POST",
      },
    );
    expect(reactivateResponse.status).toBe(200);
    const reactivated = (await reactivateResponse.json()) as any;
    expect(reactivated.data.is_active).toBe(1);
  });

  it("rejects invalid user role payloads", async () => {
    const { request, sqlDb } = await createTestHarness();
    seedUser(sqlDb, {
      id: "user-1",
      email: "alice@example.test",
      displayName: "Alice Example",
    });

    const response = await request("/v1/admin/users/user-1/role", {
      method: "PATCH",
      body: JSON.stringify({ role: "owner" }),
    });
    expect(response.status).toBe(400);
    const body = (await response.json()) as any;
    expect(body.error.code).toBe("VALIDATION_ERROR");
  });

  it("prevents admins from removing their own access", async () => {
    const { request, sqlDb } = await createTestHarness();
    seedUser(sqlDb, {
      id: "admin-user",
      email: "admin@example.test",
      displayName: "Admin Example",
      role: "admin",
    });

    const roleResponse = await request("/v1/admin/users/admin-user/role", {
      method: "PATCH",
      body: JSON.stringify({ role: "user" }),
    });
    expect(roleResponse.status).toBe(422);
    const roleBody = (await roleResponse.json()) as any;
    expect(roleBody.error.message).toBe(
      "Admins cannot remove their own admin access.",
    );

    const suspendResponse = await request(
      "/v1/admin/users/admin-user/suspend",
      { method: "POST" },
    );
    expect(suspendResponse.status).toBe(422);
    const suspendBody = (await suspendResponse.json()) as any;
    expect(suspendBody.error.message).toBe(
      "Admins cannot deactivate or suspend their own account.",
    );
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
