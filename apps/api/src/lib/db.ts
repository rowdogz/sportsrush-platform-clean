import type { Context } from "hono";
import type { HonoEnv } from "../env";
import { InternalError } from "./errors";

/**
 * Database client factory for Cloudflare D1.
 *
 * This is a thin, typed wrapper around the raw D1 API. It adds:
 *   - Per-connection foreign key enforcement (PRAGMA foreign_keys = ON)
 *   - Consistent parameter binding (avoids forgetting .bind() on parameterised queries)
 *   - Typed return values for SELECT queries
 *   - A safe ping() for health checks
 *
 * ## Foreign key enforcement
 *
 * SQLite (and D1) disables foreign key constraint checking by default.
 * The 0001_foundation migration runs `PRAGMA foreign_keys = ON` but that
 * only affects the migration session — PRAGMA settings are NOT persisted.
 *
 * `createDbClient` therefore re-applies the PRAGMA on every call so that
 * every request handler that uses this client enforces FK constraints.
 * If the PRAGMA fails for any reason (unsupported environment, read-only
 * replica) the client is still returned and usable — FK enforcement is
 * best-effort at this layer; domain migrations use explicit REFERENCES
 * constraints as the source of truth.
 *
 * ## Usage
 *
 *   // In a route handler that requires a database:
 *   const db = await requireDb(c)
 *   const row = await db.queryOne<{ id: string }>('SELECT id FROM users WHERE email = ?', [email])
 *
 *   // In a context where the binding may be absent (e.g. /ready):
 *   if (c.env.DB) {
 *     const db = await createDbClient(c.env.DB)
 *     const alive = await db.ping()
 *   }
 *
 * ## Design constraints
 *   - NO domain logic. Every method is a generic data access primitive.
 *   - NO connection pooling (D1 manages connections per request).
 *   - NO error wrapping at this layer — callers catch D1 errors and convert
 *     them to the appropriate AppError subclass.
 *
 * D1 types (D1Database, D1PreparedStatement, D1Result) are globally available
 * from @cloudflare/workers-types — no import needed.
 */

export async function createDbClient(database: D1Database) {
  // Enable foreign key enforcement for this connection.
  // Must be re-applied per-connection because PRAGMA settings are session-scoped
  // in SQLite and do not persist across D1 requests.
  // Failure is intentionally swallowed — see module JSDoc for rationale.
  try {
    await database.prepare("PRAGMA foreign_keys = ON").run();
  } catch {
    // PRAGMA may be unsupported in some D1 environments or during testing.
    // The client is still returned; domain-level REFERENCES constraints remain
    // the authoritative FK enforcement mechanism in schema definitions.
  }

  return {
    /**
     * Execute a query and return the first matching row, or null.
     * Use for SELECT … WHERE … LIMIT 1 patterns.
     *
     * @param sql    - Parameterised SQL with ? placeholders
     * @param params - Positional parameter values (must match ? count)
     */
    async queryOne<T extends Record<string, unknown>>(
      sql: string,
      params: unknown[] = [],
    ): Promise<T | null> {
      const stmt =
        params.length > 0
          ? database.prepare(sql).bind(...params)
          : database.prepare(sql);
      return stmt.first<T>();
    },

    /**
     * Execute a query and return all matching rows.
     * Returns an empty array (never null) when no rows match.
     *
     * @param sql    - Parameterised SQL with ? placeholders
     * @param params - Positional parameter values (must match ? count)
     */
    async queryAll<T extends Record<string, unknown>>(
      sql: string,
      params: unknown[] = [],
    ): Promise<T[]> {
      const stmt =
        params.length > 0
          ? database.prepare(sql).bind(...params)
          : database.prepare(sql);
      const result = await stmt.all<T>();
      return result.results;
    },

    /**
     * Execute an INSERT, UPDATE, or DELETE statement.
     * Returns the D1Result with rows_written, last_row_id, and duration.
     *
     * @param sql    - Parameterised SQL with ? placeholders
     * @param params - Positional parameter values (must match ? count)
     */
    async execute<T extends Record<string, unknown> = Record<string, unknown>>(
      sql: string,
      params: unknown[] = [],
    ): Promise<D1Result<T>> {
      const stmt =
        params.length > 0
          ? database.prepare(sql).bind(...params)
          : database.prepare(sql);
      return stmt.run<T>();
    },

    /**
     * Execute multiple prepared statements atomically in a single D1 batch.
     * D1 batch is all-or-nothing: all statements succeed or all fail.
     *
     * Build statements with prepare(), then pass them to batch().
     *
     * @example
     *   const db = await requireDb(c)
     *   await db.batch([
     *     db.prepare('INSERT INTO users (id) VALUES (?)').bind(userId),
     *     db.prepare('INSERT INTO profiles (user_id) VALUES (?)').bind(userId),
     *   ])
     */
    async batch<T extends Record<string, unknown> = Record<string, unknown>>(
      statements: D1PreparedStatement[],
    ): Promise<D1Result<T>[]> {
      return database.batch<T>(statements);
    },

    /**
     * Prepare a statement for use in batch().
     * The returned D1PreparedStatement can be bound with .bind(...params).
     */
    prepare(sql: string): D1PreparedStatement {
      return database.prepare(sql);
    },

    /**
     * Verify the D1 database connection is alive.
     * Returns true on success, false on any D1 error.
     *
     * Safe to call without try/catch — all errors are caught internally.
     * Used by GET /ready for the readiness probe.
     */
    async ping(): Promise<boolean> {
      try {
        await database.prepare("SELECT 1").first();
        return true;
      } catch {
        return false;
      }
    },
  };
}

/**
 * The type of the resolved value returned by createDbClient().
 * Use this as a parameter type when passing a DB client between functions.
 *
 * @example
 *   async function findUser(db: DbClient, id: string): Promise<User | null> {
 *     return db.queryOne('SELECT * FROM users WHERE id = ?', [id])
 *   }
 */
export type DbClient = Awaited<ReturnType<typeof createDbClient>>;

/**
 * Assert that the D1 binding is configured and return an initialised DbClient.
 *
 * Use this in route handlers that require the database to function.
 * It throws `InternalError` (→ 500) if the DB binding is absent, which
 * indicates a deployment misconfiguration rather than a user error.
 *
 * For routes that handle an absent DB gracefully (e.g. /ready), use
 * `createDbClient(c.env.DB)` directly with an explicit `if (c.env.DB)` guard.
 *
 * @example
 *   app.get('/v1/users/:id', async (c) => {
 *     const db = await requireDb(c)
 *     const user = await db.queryOne<User>('SELECT * FROM users WHERE id = ?', [c.req.param('id')])
 *     if (!user) throw new NotFoundError('User not found')
 *     return ok(c, user)
 *   })
 */
export async function requireDb(c: Context<HonoEnv>): Promise<DbClient> {
  if (!c.env.DB) {
    throw new InternalError(
      "Database binding is not configured. " +
        "Ensure the D1 database is wired in wrangler.toml and the Worker has been redeployed. " +
        "See apps/api/CLOUDFLARE_SETUP.md.",
    );
  }
  return createDbClient(c.env.DB);
}
