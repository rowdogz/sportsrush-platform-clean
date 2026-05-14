import { describe, it, expect } from "vitest";
import { createApp } from "../app";

/**
 * Test helpers.
 *
 * We pass a mock Env as the third argument to app.request().
 * Hono forwards this to c.env inside handlers.
 * Only the fields used by the tested routes need to be populated.
 *
 * mockEnv intentionally omits DB to test the "no binding configured" path.
 * Use withDb() / withFailingDb() helpers for D1-specific readiness tests.
 */
const mockEnv = {
  ENVIRONMENT: "development" as const,
  API_VERSION: "0.0.1",
  JWT_SECRET: "test-secret-at-least-32-bytes-long!!",
  WEB_ORIGIN: undefined,
};

const app = createApp();

async function get(path: string) {
  return app.request(path, { method: "GET" }, mockEnv);
}

/**
 * Minimal D1Database mock for readiness tests.
 *
 * Implements only the surface used by createDbClient().ping():
 *   database.prepare('SELECT 1').first()
 *
 * `healthy = true`  → first() resolves  → ping() returns true  → checks.db = 'ok'
 * `healthy = false` → first() rejects   → ping() returns false → checks.db = 'error'
 */
function makeD1Mock(healthy: boolean): D1Database {
  return {
    prepare: (_query: string) => ({
      bind(..._values: unknown[]) {
        return this as unknown as D1PreparedStatement;
      },
      first: async () => {
        if (!healthy)
          throw new Error("D1: SQLITE_ERROR: unable to open database file");
        return { result: 1 };
      },
      run: async () => ({ success: true, results: [], meta: {} }),
      all: async () => ({ success: true, results: [], meta: {} }),
      raw: async () => [],
    }),
    batch: async () => [],
    dump: async () => new ArrayBuffer(0),
    exec: async () => ({ count: 0, duration: 0 }),
  } as unknown as D1Database;
}

// ── GET /health ───────────────────────────────────────────────────────────────

describe("GET /health", () => {
  it("returns 200 OK", async () => {
    const res = await get("/health");
    expect(res.status).toBe(200);
  });

  it("returns the correct JSON shape", async () => {
    const res = await get("/health");
    const body = (await res.json()) as { data: Record<string, unknown> };
    expect(body.data).toMatchObject({
      status: "ok",
      service: "sportsrush-api",
      version: "0.0.1",
      environment: "development",
    });
  });

  it("sets the X-Correlation-ID response header", async () => {
    const res = await get("/health");
    expect(res.headers.get("X-Correlation-ID")).toBeTruthy();
  });

  it("propagates the X-Request-ID header as the correlation ID", async () => {
    const res = await app.request(
      "/health",
      { method: "GET", headers: { "X-Request-ID": "my-trace-id-1234" } },
      mockEnv,
    );
    expect(res.headers.get("X-Correlation-ID")).toBe("my-trace-id-1234");
  });
});

// ── GET /version ──────────────────────────────────────────────────────────────

describe("GET /version", () => {
  it("returns 200 OK", async () => {
    const res = await get("/version");
    expect(res.status).toBe(200);
  });

  it("returns version and environment", async () => {
    const res = await get("/version");
    const body = (await res.json()) as { data: Record<string, unknown> };
    expect(body.data).toMatchObject({
      version: "0.0.1",
      environment: "development",
    });
  });
});

// ── GET /ready ────────────────────────────────────────────────────────────────

describe("GET /ready — no DB binding (test / pre-setup mode)", () => {
  it("returns 200 OK", async () => {
    const res = await get("/ready");
    expect(res.status).toBe(200);
  });

  it('status field is "ready"', async () => {
    const res = await get("/ready");
    const body = (await res.json()) as { data: Record<string, unknown> };
    expect(body.data.status).toBe("ready");
  });

  it("db appears in pendingChecks (binding not configured)", async () => {
    const res = await get("/ready");
    const body = (await res.json()) as { data: { pendingChecks: string[] } };
    expect(body.data.pendingChecks).toContain("db");
  });

  it("checks object is empty (no checks were executed)", async () => {
    const res = await get("/ready");
    const body = (await res.json()) as {
      data: { checks: Record<string, unknown> };
    };
    expect(body.data.checks).toEqual({});
  });
});

describe("GET /ready — DB binding present and healthy", () => {
  async function getReady() {
    return app.request(
      "/ready",
      { method: "GET" },
      { ...mockEnv, DB: makeD1Mock(true) },
    );
  }

  it("returns 200 OK", async () => {
    const res = await getReady();
    expect(res.status).toBe(200);
  });

  it('checks.db is "ok"', async () => {
    const res = await getReady();
    const body = (await res.json()) as {
      data: { checks: Record<string, string> };
    };
    expect(body.data.checks.db).toBe("ok");
  });

  it("pendingChecks is empty", async () => {
    const res = await getReady();
    const body = (await res.json()) as { data: { pendingChecks: string[] } };
    expect(body.data.pendingChecks).toEqual([]);
  });

  it('status field is "ready"', async () => {
    const res = await getReady();
    const body = (await res.json()) as { data: Record<string, unknown> };
    expect(body.data.status).toBe("ready");
  });
});

describe("GET /ready — DB binding present but unreachable", () => {
  async function getReady() {
    return app.request(
      "/ready",
      { method: "GET" },
      { ...mockEnv, DB: makeD1Mock(false) },
    );
  }

  it("returns 503 Service Unavailable", async () => {
    const res = await getReady();
    expect(res.status).toBe(503);
  });

  it('checks.db is "error"', async () => {
    const res = await getReady();
    const body = (await res.json()) as {
      error: { details: Record<string, string> };
    };
    expect(body.error.details.db).toBe("error");
  });

  it("error code is SERVICE_UNAVAILABLE", async () => {
    const res = await getReady();
    const body = (await res.json()) as { error: Record<string, unknown> };
    expect(body.error.code).toBe("SERVICE_UNAVAILABLE");
  });

  it("correlationId is present on the error", async () => {
    const res = await getReady();
    const body = (await res.json()) as { error: Record<string, unknown> };
    expect(typeof body.error.correlationId).toBe("string");
  });
});

// ── GET /openapi.json ─────────────────────────────────────────────────────────

describe("GET /openapi.json", () => {
  it("returns 200 OK", async () => {
    const res = await get("/openapi.json");
    expect(res.status).toBe(200);
  });

  it("returns a valid OpenAPI 3.0 document", async () => {
    const res = await get("/openapi.json");
    const spec = (await res.json()) as Record<string, unknown>;
    expect(spec.openapi).toBe("3.0.3");
    expect(spec.info).toBeDefined();
    expect(spec.paths).toBeDefined();
  });

  it("includes /health in the paths", async () => {
    const res = await get("/openapi.json");
    const spec = (await res.json()) as { paths: Record<string, unknown> };
    expect(spec.paths["/health"]).toBeDefined();
  });
});

// ── 404 handling ──────────────────────────────────────────────────────────────

describe("404 not found", () => {
  it("returns 404 for an unknown route", async () => {
    const res = await get("/this-route-does-not-exist");
    expect(res.status).toBe(404);
  });

  it("returns a structured error body", async () => {
    const res = await get("/this-route-does-not-exist");
    const body = (await res.json()) as { error: Record<string, unknown> };
    expect(body.error.code).toBe("NOT_FOUND");
    expect(typeof body.error.message).toBe("string");
  });
});
