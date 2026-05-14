/**
 * D1Database mock backed by a sql.js in-memory database.
 *
 * Implements the D1Database + D1PreparedStatement interfaces so that real SQL
 * (including schema constraints, AFTER UPDATE triggers, and FK rules) is
 * exercised in Vitest without a Cloudflare Workers runtime.
 *
 * Behaviour notes:
 * - batch() wraps all statements in a SQLite BEGIN/COMMIT/ROLLBACK transaction,
 *   matching D1's own all-or-nothing batch guarantee: if any statement throws,
 *   the entire batch is rolled back and the error is re-thrown. This allows
 *   tests to verify that atomic write patterns (e.g. rotateSession) correctly
 *   handle partial failures.
 * - first() executes via exec() and returns the first row as an object, or a
 *   single column value when colName is supplied.
 * - run() executes via run() and returns a D1Result whose meta.rows_written is
 *   populated from getRowsModified().
 * - all() executes via exec() and returns every row as a D1Result.
 * - dump() is a no-op stub (returns empty ArrayBuffer).
 *
 * Only the interface surface actually used by createDbClient() and the auth
 * route handlers is implemented — raw() is intentionally omitted.
 */

import type { Database } from "sql.js";

// ── Internal helpers ──────────────────────────────────────────────────────────

type SqlValue = string | number | null;
type SqlRow = Record<string, SqlValue>;

function execSelect(db: Database, sql: string, params: SqlValue[]): SqlRow[] {
  const results = db.exec(sql, params);
  if (results.length === 0 || results[0] === undefined) return [];
  const { columns, values } = results[0];
  return values.map((row) =>
    Object.fromEntries(
      columns.map((col, i) => [col, (row[i] as SqlValue) ?? null]),
    ),
  );
}

function makeMeta(rowsWritten = 0) {
  return {
    changed_db: rowsWritten > 0,
    changes: rowsWritten,
    duration: 0,
    last_row_id: 0,
    rows_read: 0,
    rows_written: rowsWritten,
    size_after: 0,
  };
}

// ── MockStatement ─────────────────────────────────────────────────────────────

class MockStatement {
  private params: SqlValue[] = [];

  constructor(
    private readonly db: Database,
    private readonly sql: string,
  ) {}

  bind(...values: unknown[]): this {
    this.params = values as SqlValue[];
    return this;
  }

  async first<T = unknown>(colName?: string): Promise<T | null> {
    const rows = execSelect(this.db, this.sql, this.params);
    if (rows.length === 0) return null;
    const row = rows[0] as SqlRow;
    if (colName !== undefined) {
      return (row[colName] ?? null) as T;
    }
    return row as T;
  }

  async all<T = unknown>(): Promise<D1Result<T>> {
    const rows = execSelect(this.db, this.sql, this.params) as T[];
    return { results: rows, success: true, meta: makeMeta() };
  }

  async run<T = unknown>(): Promise<D1Result<T>> {
    this.db.run(this.sql, this.params);
    const rowsWritten = this.db.getRowsModified();
    return { results: [], success: true, meta: makeMeta(rowsWritten) };
  }
}

// ── createMockD1 ──────────────────────────────────────────────────────────────

/**
 * Wrap a sql.js Database in a D1Database-compatible interface for Vitest.
 *
 * @param db - An initialised sql.js Database with migrations already applied.
 * @returns   A D1Database-shaped object suitable for injection into HonoEnv.
 */
export function createMockD1(db: Database): D1Database {
  return {
    prepare(sql: string): D1PreparedStatement {
      return new MockStatement(db, sql) as unknown as D1PreparedStatement;
    },

    async batch<T = unknown>(
      statements: D1PreparedStatement[],
    ): Promise<D1Result<T>[]> {
      db.run("BEGIN");
      try {
        const results: D1Result<T>[] = [];
        for (const stmt of statements) {
          results.push(await (stmt as unknown as MockStatement).run<T>());
        }
        db.run("COMMIT");
        return results;
      } catch (err) {
        db.run("ROLLBACK");
        throw err;
      }
    },

    async dump(): Promise<ArrayBuffer> {
      return new ArrayBuffer(0);
    },

    async exec(query: string): Promise<D1ExecResult> {
      db.run(query);
      return { count: 1, duration: 0 };
    },
  } as unknown as D1Database;
}
