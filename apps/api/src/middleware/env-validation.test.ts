import { describe, expect, it } from "vitest";
import { validateEnv } from "../env";

describe("validateEnv", () => {
  it("accepts a complete development environment", () => {
    expect(
      validateEnv({
        ENVIRONMENT: "development",
        API_VERSION: "0.0.1",
        JWT_SECRET: "test-secret-at-least-32-bytes-long!!",
      }),
    ).toEqual({ ok: true });
  });

  it("requires WEB_ORIGIN in production", () => {
    const result = validateEnv({
      ENVIRONMENT: "production",
      API_VERSION: "0.0.1",
      JWT_SECRET: "test-secret-at-least-32-bytes-long!!",
    });

    expect(result.ok).toBe(false);
    if (!result.ok) {
      expect(result.issues).toContain(
        "WEB_ORIGIN is required in staging and production.",
      );
    }
  });

  it("reports missing or unsafe required bindings", () => {
    const result = validateEnv({
      ENVIRONMENT: "invalid" as "development",
      API_VERSION: "",
      JWT_SECRET: "short",
    });

    expect(result.ok).toBe(false);
    if (!result.ok) {
      expect(result.issues).toEqual(
        expect.arrayContaining([
          "ENVIRONMENT must be development, staging, or production.",
          "API_VERSION is required.",
          "JWT_SECRET must be at least 32 characters.",
        ]),
      );
    }
  });
});
