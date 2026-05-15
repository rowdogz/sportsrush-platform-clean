import "@testing-library/jest-dom/vitest";
import { render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { setAccessTokenProvider } from "../lib/apiClient";
import { CompetitionsPage } from "./CompetitionsPage";

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}

describe("CompetitionsPage", () => {
  afterEach(() => {
    vi.restoreAllMocks();
    setAccessTokenProvider(() => null);
    window.localStorage.clear();
  });

  it("shows loading and then renders competitions", async () => {
    const fetchMock = vi.fn().mockResolvedValue(
      jsonResponse({
        data: [
          {
            id: "competition-1",
            name: "Super League",
            slug: "super-league",
            sport_id: "sport-rugby-league",
            country_code: "GB",
            is_active: 1,
          },
        ],
        meta: { page: 1, limit: 50, total: 1, hasMore: false },
      }),
    );
    vi.stubGlobal("fetch", fetchMock);

    render(<CompetitionsPage />);

    expect(screen.getByRole("status")).toHaveTextContent(
      "Loading competitions…",
    );

    expect(
      await screen.findByRole("cell", { name: "Super League" }),
    ).toBeTruthy();
    expect(screen.getByRole("cell", { name: "super-league" })).toBeTruthy();
    expect(
      screen.getByRole("cell", { name: "sport-rugby-league" }),
    ).toBeTruthy();
    expect(screen.getByRole("cell", { name: "GB" })).toBeTruthy();
    expect(screen.getByText("Active")).toBeTruthy();
    expect(fetchMock).toHaveBeenCalledWith(
      "/v1/admin/competitions?page=1&limit=50",
      expect.objectContaining({
        headers: expect.any(Headers),
      }),
    );
  });

  it("renders an empty state", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
        }),
      ),
    );

    render(<CompetitionsPage />);

    expect(await screen.findByText("No competitions found")).toBeTruthy();
    expect(
      screen.getByText("Competitions will appear here after they are added."),
    ).toBeTruthy();
  });

  it("renders an error state", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(
        jsonResponse(
          {
            error: {
              code: "forbidden",
              message: "Admin access is required.",
            },
          },
          403,
        ),
      ),
    );

    render(<CompetitionsPage />);

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Unable to load competitions",
    );
    expect(screen.getByText("Admin access is required.")).toBeTruthy();
  });

  it("sends the configured bearer token when present", async () => {
    setAccessTokenProvider(() => "token-123");
    const fetchMock = vi.fn().mockResolvedValue(
      jsonResponse({
        data: [],
        meta: { page: 1, limit: 50, total: 0, hasMore: false },
      }),
    );
    vi.stubGlobal("fetch", fetchMock);

    render(<CompetitionsPage />);

    await waitFor(() => expect(fetchMock).toHaveBeenCalled());
    const [, init] = fetchMock.mock.calls[0] as [string, RequestInit];
    expect(init.headers).toBeInstanceOf(Headers);
    expect((init.headers as Headers).get("Authorization")).toBe(
      "Bearer token-123",
    );
  });
});
