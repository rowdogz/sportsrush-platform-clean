import { afterEach, describe, expect, it, vi } from "vitest";
import {
  setAccessTokenProvider,
  setUnauthorizedHandler,
} from "../../lib/apiClient";
import { loginAdmin } from "./api";

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}

describe("loginAdmin", () => {
  afterEach(() => {
    vi.restoreAllMocks();
    setAccessTokenProvider(() => null);
    setUnauthorizedHandler(null);
  });

  it("normalizes wrapped auth responses", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(
        jsonResponse({
          data: {
            accessToken: "wrapped-access",
            refreshToken: "wrapped-refresh",
            user: {
              id: "admin-user",
              email: "admin@sportsrush.test",
              role: "superadmin",
            },
            profile: { displayName: "Admin User" },
            session: { id: "session-1" },
          },
        }),
      ),
    );

    await expect(
      loginAdmin({ email: "admin@sportsrush.test", password: "password-123" }),
    ).resolves.toMatchObject({
      accessToken: "wrapped-access",
      refreshToken: "wrapped-refresh",
      user: { id: "admin-user", role: "superadmin" },
      profile: { displayName: "Admin User" },
      session: { id: "session-1" },
    });
  });
});
