import { describe, it, expect } from "vitest";
import { Hono } from "hono";
import { createAccessToken } from "@sr/auth";
import type { HonoEnv } from "../env";
import { requireAuth, requireRole } from "./auth";
import { makeErrorHandler } from "./error-handler";

const SECRET = "test-secret-at-least-32-bytes-long!!";

const mockEnv = {
  ENVIRONMENT: "development" as const,
  API_VERSION: "0.0.1",
  JWT_SECRET: SECRET,
  WEB_ORIGIN: undefined,
};

/**
 * Build a minimal Hono app with the auth middleware, the error handler,
 * and a protected route. The error handler is required so that thrown
 * AppError subclasses (AuthenticationError, AuthorizationError) are
 * serialised into structured JSON rather than falling through to Hono's
 * default 500 handler.
 */
function makeTestApp(middleware = requireAuth()) {
  const app = new Hono<HonoEnv>();
  app.use("/protected", middleware);
  app.get("/protected", (c) => {
    return c.json({ data: { userId: c.var.user?.userId } });
  });
  app.onError(makeErrorHandler());
  return app;
}

async function request(
  app: ReturnType<typeof makeTestApp>,
  headers: Record<string, string> = {},
) {
  return app.request("/protected", { method: "GET", headers }, mockEnv);
}

// ── requireAuth ───────────────────────────────────────────────────────────────

describe("requireAuth()", () => {
  it("returns 401 when the Authorization header is absent", async () => {
    const res = await request(makeTestApp());
    expect(res.status).toBe(401);
    const body = (await res.json()) as { error: { code: string } };
    expect(body.error.code).toBe("MISSING_TOKEN");
  });

  it('returns 401 when the header does not start with "Bearer "', async () => {
    const res = await request(makeTestApp(), { Authorization: "Basic abc123" });
    expect(res.status).toBe(401);
    const body = (await res.json()) as { error: { code: string } };
    expect(body.error.code).toBe("MISSING_TOKEN");
  });

  it("returns 401 TOKEN_INVALID for a tampered token", async () => {
    const res = await request(makeTestApp(), {
      Authorization: "Bearer not.a.real.jwt",
    });
    expect(res.status).toBe(401);
    const body = (await res.json()) as { error: { code: string } };
    expect(body.error.code).toBe("TOKEN_INVALID");
  });

  it("returns 401 TOKEN_INVALID for a token signed with the wrong secret", async () => {
    const token = await createAccessToken(
      { userId: "abc", role: "user", sessionId: "sess" },
      "completely-different-secret-value!!",
    );
    const res = await request(makeTestApp(), {
      Authorization: `Bearer ${token}`,
    });
    expect(res.status).toBe(401);
    const body = (await res.json()) as { error: { code: string } };
    expect(body.error.code).toBe("TOKEN_INVALID");
  });

  it("returns 200 and sets c.var.user for a valid token", async () => {
    const token = await createAccessToken(
      { userId: "user-id-123", role: "user", sessionId: "sess-id-456" },
      SECRET,
    );
    const res = await request(makeTestApp(), {
      Authorization: `Bearer ${token}`,
    });
    expect(res.status).toBe(200);
    const body = (await res.json()) as { data: { userId: string } };
    expect(body.data.userId).toBe("user-id-123");
  });
});

// ── requireRole ───────────────────────────────────────────────────────────────

describe("requireRole()", () => {
  it("returns 403 when the user role is below the required role", async () => {
    const token = await createAccessToken(
      { userId: "u1", role: "user", sessionId: "s1" },
      SECRET,
    );
    const app = makeTestApp(requireRole("admin"));
    const res = await request(app, { Authorization: `Bearer ${token}` });
    expect(res.status).toBe(403);
    const body = (await res.json()) as { error: { code: string } };
    expect(body.error.code).toBe("FORBIDDEN");
  });

  it("returns 200 when the user role meets the minimum", async () => {
    const token = await createAccessToken(
      { userId: "u2", role: "admin", sessionId: "s2" },
      SECRET,
    );
    const app = makeTestApp(requireRole("admin"));
    const res = await request(app, { Authorization: `Bearer ${token}` });
    expect(res.status).toBe(200);
  });

  it("returns 200 when the user role exceeds the minimum", async () => {
    const token = await createAccessToken(
      { userId: "u3", role: "superadmin", sessionId: "s3" },
      SECRET,
    );
    const app = makeTestApp(requireRole("admin"));
    const res = await request(app, { Authorization: `Bearer ${token}` });
    expect(res.status).toBe(200);
  });

  it("returns 401 when no token is provided (role check never reached)", async () => {
    const app = makeTestApp(requireRole("admin"));
    const res = await request(app);
    expect(res.status).toBe(401);
  });
});
