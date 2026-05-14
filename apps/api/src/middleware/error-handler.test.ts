import { describe, it, expect } from "vitest";
import { Hono } from "hono";
import type { HonoEnv } from "../env";
import { makeErrorHandler, makeNotFoundHandler } from "./error-handler";
import {
  AppError,
  ValidationError,
  NotFoundError,
  AuthenticationError,
  InternalError,
} from "../lib/errors";

const mockEnv = {
  ENVIRONMENT: "development" as const,
  API_VERSION: "0.0.1",
  JWT_SECRET: "test-secret-at-least-32-bytes-long!!",
  WEB_ORIGIN: undefined,
};

function makeTestApp() {
  const app = new Hono<HonoEnv>();
  app.onError(makeErrorHandler());
  app.notFound(makeNotFoundHandler());
  return app;
}

// ── AppError subclasses ───────────────────────────────────────────────────────

describe("error handler — AppError subclasses", () => {
  it("serialises ValidationError as 400", async () => {
    const app = makeTestApp();
    app.get("/test", () => {
      throw new ValidationError("Invalid input", [{ field: "email" }]);
    });
    const res = await app.request("/test", {}, mockEnv);
    expect(res.status).toBe(400);
    const body = (await res.json()) as { error: Record<string, unknown> };
    expect(body.error.code).toBe("VALIDATION_ERROR");
    expect(body.error.message).toBe("Invalid input");
  });

  it("serialises NotFoundError as 404", async () => {
    const app = makeTestApp();
    app.get("/test", () => {
      throw new NotFoundError("Match not found");
    });
    const res = await app.request("/test", {}, mockEnv);
    expect(res.status).toBe(404);
    const body = (await res.json()) as { error: Record<string, unknown> };
    expect(body.error.code).toBe("NOT_FOUND");
  });

  it("serialises AuthenticationError as 401", async () => {
    const app = makeTestApp();
    app.get("/test", () => {
      throw new AuthenticationError();
    });
    const res = await app.request("/test", {}, mockEnv);
    expect(res.status).toBe(401);
    const body = (await res.json()) as { error: Record<string, unknown> };
    expect(body.error.code).toBe("UNAUTHENTICATED");
  });

  it("serialises InternalError as 500", async () => {
    const app = makeTestApp();
    app.get("/test", () => {
      throw new InternalError();
    });
    const res = await app.request("/test", {}, mockEnv);
    expect(res.status).toBe(500);
    const body = (await res.json()) as { error: Record<string, unknown> };
    expect(body.error.code).toBe("INTERNAL_ERROR");
  });

  it("includes correlationId in the error response body", async () => {
    const app = makeTestApp();
    // Manually set a correlationId via middleware to simulate the logger
    app.use("*", async (c, next) => {
      c.set("correlationId", "test-correlation-id");
      await next();
    });
    app.get("/test", () => {
      throw new NotFoundError();
    });
    const res = await app.request("/test", {}, mockEnv);
    const body = (await res.json()) as { error: Record<string, unknown> };
    expect(body.error.correlationId).toBe("test-correlation-id");
  });

  it("does not include details in the response when details is null", async () => {
    const app = makeTestApp();
    app.get("/test", () => {
      throw new NotFoundError("Simple error");
    });
    const res = await app.request("/test", {}, mockEnv);
    const body = (await res.json()) as { error: Record<string, unknown> };
    // details should not be present when not provided
    expect("details" in body.error).toBe(false);
  });
});

// ── Unknown errors ────────────────────────────────────────────────────────────

describe("error handler — unknown errors", () => {
  it("returns 500 for a thrown plain Error", async () => {
    const app = makeTestApp();
    app.get("/test", () => {
      throw new Error("Something exploded internally");
    });
    const res = await app.request("/test", {}, mockEnv);
    expect(res.status).toBe(500);
    const body = (await res.json()) as { error: Record<string, unknown> };
    expect(body.error.code).toBe("INTERNAL_ERROR");
  });

  it("exposes the error message in development environment", async () => {
    const app = makeTestApp();
    app.get("/test", () => {
      throw new Error("Detailed dev error message");
    });
    const res = await app.request("/test", {}, mockEnv);
    const body = (await res.json()) as { error: Record<string, unknown> };
    expect(body.error.message).toBe("Detailed dev error message");
  });
});

// ── Custom AppError (base class directly) ────────────────────────────────────

describe("error handler — base AppError", () => {
  it("uses the statusCode and code from the error", async () => {
    const app = makeTestApp();
    app.get("/test", () => {
      throw new AppError(418, "IM_A_TEAPOT", "I'm a teapot");
    });
    const res = await app.request("/test", {}, mockEnv);
    expect(res.status).toBe(418);
    const body = (await res.json()) as { error: Record<string, unknown> };
    expect(body.error.code).toBe("IM_A_TEAPOT");
  });
});
