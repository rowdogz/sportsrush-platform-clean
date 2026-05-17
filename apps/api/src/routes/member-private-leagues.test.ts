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
  resolve(
    __dirname,
    "../../migrations/0005_private_leagues_predictions_rankings.sql",
  ),
];

const JWT_SECRET = "test-secret-that-is-at-least-32-bytes-long";
const NOW = "2026-05-14T12:00:00.000Z";

type SqlJsDatabase = initSqlJs.Database;

type TestEnv = {
  readonly ENVIRONMENT: "development";
  readonly API_VERSION: string;
  readonly JWT_SECRET: string;
  readonly DB: D1Database;
};

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
    `INSERT INTO competitions
       (id, sport_id, slug, name, short_name, country_code, is_active, created_at, updated_at)
     VALUES ('comp-sl', 'sport-rugby-league', 'super-league', 'Super League', 'SL', 'GB', 1, ?, ?)`,
    [NOW, NOW],
  );
  seedUser(db, "member-user", "member@example.test", "Member User");
  seedUser(db, "other-user", "other@example.test", "Other User");
  db.run(
    `INSERT INTO private_leagues
       (id, slug, name, description, logo_url, banner_url, invite_code, owner_user_id, is_archived, created_at, updated_at, archived_at)
     VALUES
       ('league-1', 'test-league', 'Test League', 'Member test league', 'https://cdn.example.test/logo.svg',
        'https://cdn.example.test/banner.jpg', 'LEAGUE2026', 'member-user', 0, ?, ?, NULL)`,
    [NOW, NOW],
  );
  db.run(
    `INSERT INTO private_league_members
       (id, private_league_id, user_id, role, is_active, joined_at, updated_at)
     VALUES ('league-member-1', 'league-1', 'member-user', 'owner', 1, ?, ?)`,
    [NOW, NOW],
  );
  db.run(
    `INSERT INTO private_league_competitions
       (id, private_league_id, competition_id, created_at)
     VALUES ('league-competition-1', 'league-1', 'comp-sl', ?)`,
    [NOW],
  );
  return db;
}

function seedUser(
  db: SqlJsDatabase,
  id: string,
  email: string,
  displayName: string,
) {
  db.run(
    `INSERT INTO users
       (id, email, email_normalized, role, is_active, is_legacy_migration, created_at, updated_at)
     VALUES (?, ?, ?, 'user', 1, 0, ?, ?)`,
    [id, email, email.toLowerCase(), NOW, NOW],
  );
  db.run(
    `INSERT INTO user_profiles (user_id, display_name, timezone, created_at, updated_at)
     VALUES (?, ?, 'UTC', ?, ?)`,
    [id, displayName, NOW, NOW],
  );
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
  const memberToken = await createAccessToken(
    { userId: "member-user", role: "user", sessionId: "session-member" },
    JWT_SECRET,
  );
  const otherToken = await createAccessToken(
    { userId: "other-user", role: "user", sessionId: "session-other" },
    JWT_SECRET,
  );

  async function request(
    path: string,
    init: RequestInit = {},
    token: string | null = memberToken,
  ) {
    const headers = new Headers(init.headers);
    if (token !== null) headers.set("authorization", `Bearer ${token}`);
    if (init.body !== undefined && !headers.has("content-type")) {
      headers.set("content-type", "application/json");
    }
    return app.request(path, { ...init, headers }, env);
  }

  return { request, memberToken, otherToken, sqlDb };
}

describe("member private league routes", () => {
  it("requires authentication", async () => {
    const { request } = await createTestHarness();
    const response = await request("/v1/private-leagues", {}, null);
    expect(response.status).toBe(401);
  });

  it("lists the authenticated user's private leagues", async () => {
    const { request } = await createTestHarness();
    const response = await request("/v1/private-leagues");
    const body = (await response.json()) as any;

    expect(response.status).toBe(200);
    expect(body.data).toHaveLength(1);
    expect(body.data[0]).toMatchObject({
      id: "league-1",
      name: "Test League",
      viewerRole: "owner",
      competitionCount: 1,
    });
    expect(body.data[0]).not.toHaveProperty("inviteCode");
  });

  it("joins a private league by invite code and returns member-safe detail", async () => {
    const { request, otherToken } = await createTestHarness();
    const response = await request(
      "/v1/private-leagues/join",
      {
        method: "POST",
        body: JSON.stringify({ inviteCode: "league2026" }),
      },
      otherToken,
    );
    const body = (await response.json()) as any;

    expect(response.status).toBe(201);
    expect(body.data.name).toBe("Test League");
    expect(body.data.viewerRole).toBe("member");
    expect(
      body.data.members.some((member: any) => member.userId === "other-user"),
    ).toBe(true);
    expect(body.data.competitions[0]).toMatchObject({
      competitionId: "comp-sl",
      competitionName: "Super League",
    });
    expect(body.data).not.toHaveProperty("inviteCode");
  });

  it("forbids viewing league detail without membership", async () => {
    const { request, otherToken } = await createTestHarness();
    const response = await request(
      "/v1/private-leagues/league-1",
      {},
      otherToken,
    );
    expect(response.status).toBe(403);
  });
});
