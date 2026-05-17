import { afterEach, describe, expect, it, vi } from "vitest";
import {
  ApiError,
  apiRequest,
  setAccessTokenProvider,
  setUnauthorizedHandler,
} from "./apiClient";

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}

describe("apiClient auth failure handling", () => {
  afterEach(() => {
    vi.restoreAllMocks();
    setAccessTokenProvider(() => null);
    setUnauthorizedHandler(null);
  });

  it("notifies the session layer on authenticated 401 responses", async () => {
    const onUnauthorized = vi.fn();
    setAccessTokenProvider(() => "access-token");
    setUnauthorizedHandler(onUnauthorized);
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockResolvedValue(
          jsonResponse(
            { error: { code: "UNAUTHENTICATED", message: "Token expired" } },
            401,
          ),
        ),
    );

    await expect(apiRequest("/v1/admin/competitions")).rejects.toMatchObject({
      status: 401,
      code: "UNAUTHENTICATED",
    } satisfies Partial<ApiError>);
    expect(onUnauthorized).toHaveBeenCalledTimes(1);
  });

  it("does not clear the session for login failures", async () => {
    const onUnauthorized = vi.fn();
    setUnauthorizedHandler(onUnauthorized);
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(
        jsonResponse(
          {
            error: {
              code: "INVALID_CREDENTIALS",
              message: "Invalid email or password.",
            },
          },
          401,
        ),
      ),
    );

    await expect(
      apiRequest("/v1/auth/login", {
        method: "POST",
        body: { email: "admin@sportsrush.test", password: "wrong" },
      }),
    ).rejects.toMatchObject({ status: 401 });
    expect(onUnauthorized).not.toHaveBeenCalled();
  });
});
