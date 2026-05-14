import { describe, it, expect } from "vitest";
import { createDbClient, requireDb } from "./db";
import { InternalError } from "./errors";
import type { Context } from "hono";
import type { HonoEnv } from "../env";

// ── D1 mock helpers ───────────────────────────────────────────────────────────

/**
 * Tracked D1 mock that records every SQL statement that is prepared and run.
 * Use `runSqls` to assert which statements were executed during the test.
 */
function makeTrackedD1Mock(opts: { pingHealthy?: boolean } = {}) {
  const { pingHealthy = true } = opts;
  const runSqls: string[] = [];

  const database = {
    prepare: (sql: string) => ({
      bind(..._values: unknown[]) {
        return this as unknown as D1PreparedStatement;
      },
      run: async () => {
        runSqls.push(sql);
        return { success: true, results: [], meta: {} };
      },
      first: async () => {
        if (!pingHealthy)
          throw new Error("D1: SQLITE_ERROR: unable to open database file");
        return { result: 1 };
      },
      all: async () => ({ success: true, results: [], meta: {} }),
      raw: async () => [],
    }),
    batch: async () => [],
    dump: async () => new ArrayBuffer(0),
    exec: async () => ({ count: 0, duration: 0 }),
  } as unknown as D1Database;

  return { database, runSqls };
}

/**
 * D1 mock where PRAGMA foreign_keys = ON throws.
 * Used to verify the client is still usable when the PRAGMA is unsupported.
 */
function makePragmaFailingD1Mock() {
  return {
    prepare: (sql: string) => ({
      bind(..._values: unknown[]) {
        return this as unknown as D1PreparedStatement;
      },
      run: async () => {
        if (sql === "PRAGMA foreign_keys = ON") {
          throw new Error("D1: PRAGMA not supported in this context");
        }
        return { success: true, results: [], meta: {} };
      },
      first: async () => ({ result: 1 }),
      all: async () => ({ success: true, results: [], meta: {} }),
      raw: async () => [],
    }),
    batch: async () => [],
    dump: async () => new ArrayBuffer(0),
    exec: async () => ({ count: 0, duration: 0 }),
  } as unknown as D1Database;
}

// ── createDbClient — foreign key enforcement ──────────────────────────────────

describe("createDbClient — foreign key enforcement", () => {
  it("runs PRAGMA foreign_keys = ON via prepare().run() at construction", async () => {
    const { database, runSqls } = makeTrackedD1Mock();

    await createDbClient(database);

    expect(runSqls).toContain("PRAGMA foreign_keys = ON");
  });

  it("enables FK enforcement before any query methods are available", async () => {
    // The PRAGMA must be the first run() call — before any user-initiated queries.
    const { database, runSqls } = makeTrackedD1Mock();

    const db = await createDbClient(database);

    // PRAGMA was run before the returned client is used
    const pragmaIndex = runSqls.indexOf("PRAGMA foreign_keys = ON");
    expect(pragmaIndex).toBe(0);

    // A subsequent execute() adds another entry
    await db.execute("INSERT INTO test (id) VALUES (?)", [1]);
    expect(runSqls.at(1)).toBe("INSERT INTO test (id) VALUES (?)");
  });

  it("returns a usable client even when PRAGMA foreign_keys = ON fails", async () => {
    // Some D1 environments or read-only replicas may reject the PRAGMA.
    // The client must still be returned and functional.
    const db = await createDbClient(makePragmaFailingD1Mock());

    expect(db).toBeDefined();
    await expect(db.ping()).resolves.toBe(true);
  });

  it("ping() returns true for a reachable database", async () => {
    const { database } = makeTrackedD1Mock({ pingHealthy: true });
    const db = await createDbClient(database);
    await expect(db.ping()).resolves.toBe(true);
  });

  it("ping() returns false (does not throw) for an unreachable database", async () => {
    const { database } = makeTrackedD1Mock({ pingHealthy: false });
    const db = await createDbClient(database);
    await expect(db.ping()).resolves.toBe(false);
  });
});

// ── createDbClient — query primitives ─────────────────────────────────────────

describe("createDbClient — query primitives", () => {
  it("queryOne() passes parameters to .bind() and calls .first()", async () => {
    const boundParams: unknown[][] = [];
    const database = {
      prepare: (_sql: string) => ({
        bind(...values: unknown[]) {
          boundParams.push(values);
          return this as unknown as D1PreparedStatement;
        },
        first: async () => ({ id: "abc" }),
        run: async () => ({ success: true, results: [], meta: {} }),
        all: async () => ({ success: true, results: [], meta: {} }),
        raw: async () => [],
      }),
      batch: async () => [],
      dump: async () => new ArrayBuffer(0),
      exec: async () => ({ count: 0, duration: 0 }),
    } as unknown as D1Database;

    const db = await createDbClient(database);
    const row = await db.queryOne<{ id: string }>(
      "SELECT id FROM users WHERE id = ?",
      ["abc"],
    );

    expect(row).toEqual({ id: "abc" });
    // boundParams[0] is from the PRAGMA (no params), boundParams[1] from queryOne
    expect(boundParams).toContainEqual(["abc"]);
  });

  it("queryAll() returns the results array from D1", async () => {
    const database = {
      prepare: (_sql: string) => ({
        bind(..._: unknown[]) {
          return this as unknown as D1PreparedStatement;
        },
        first: async () => null,
        run: async () => ({ success: true, results: [], meta: {} }),
        all: async () => ({
          success: true,
          results: [{ id: "1" }, { id: "2" }],
          meta: {},
        }),
        raw: async () => [],
      }),
      batch: async () => [],
      dump: async () => new ArrayBuffer(0),
      exec: async () => ({ count: 0, duration: 0 }),
    } as unknown as D1Database;

    const db = await createDbClient(database);
    const rows = await db.queryAll<{ id: string }>("SELECT id FROM users");

    expect(rows).toEqual([{ id: "1" }, { id: "2" }]);
  });
});

// ── requireDb ─────────────────────────────────────────────────────────────────

describe("requireDb", () => {
  it("throws InternalError when the DB binding is absent", async () => {
    const ctx = { env: {} } as unknown as Context<HonoEnv>;

    await expect(requireDb(ctx)).rejects.toThrow(InternalError);
  });

  it("throws InternalError (500) — not a generic Error", async () => {
    const ctx = { env: {} } as unknown as Context<HonoEnv>;

    try {
      await requireDb(ctx);
      expect.fail("should have thrown");
    } catch (err) {
      expect(err).toBeInstanceOf(InternalError);
      expect((err as InternalError).statusCode).toBe(500);
      expect((err as InternalError).code).toBe("INTERNAL_ERROR");
    }
  });

  it("returns a DbClient when the DB binding is present", async () => {
    const { database } = makeTrackedD1Mock();
    const ctx = { env: { DB: database } } as unknown as Context<HonoEnv>;

    const db = await requireDb(ctx);

    expect(db).toBeDefined();
    expect(typeof db.queryOne).toBe("function");
    expect(typeof db.queryAll).toBe("function");
    expect(typeof db.execute).toBe("function");
    expect(typeof db.ping).toBe("function");
  });

  it("also enables FK enforcement when called via requireDb", async () => {
    const { database, runSqls } = makeTrackedD1Mock();
    const ctx = { env: { DB: database } } as unknown as Context<HonoEnv>;

    await requireDb(ctx);

    expect(runSqls).toContain("PRAGMA foreign_keys = ON");
  });
});
