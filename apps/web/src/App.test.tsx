import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { App } from "./App";

const fixture = {
  id: "fixture-1",
  kickoffTime: "2099-01-01T15:00:00.000Z",
  venue: "SportsRush Park",
  status: "scheduled",
  homeScore: null,
  awayScore: null,
  homeTeam: {
    id: "team-home",
    name: "Home FC",
    shortName: "HOM",
    displayName: "Home FC",
    logoUrl: null,
    badgeUrl: null,
  },
  awayTeam: {
    id: "team-away",
    name: "Away FC",
    shortName: "AWY",
    displayName: "Away FC",
    logoUrl: null,
    badgeUrl: null,
  },
  round: { id: "round-1", round: "1", name: "Round 1", displayOrder: 1 },
  season: { id: "season-1", slug: "2026", name: "2026" },
  competition: {
    id: "competition-1",
    slug: "super-league",
    name: "Super League",
    shortName: "SL",
  },
};

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}

function paginated<T>(data: readonly T[]) {
  return {
    data,
    meta: { page: 1, limit: 50, total: data.length, hasMore: false },
  };
}

function stubFetch(): ReturnType<typeof vi.fn> {
  const fetchMock = vi.fn((input: RequestInfo | URL, init?: RequestInit) => {
    const url = String(input);
    if (url.includes("/v1/public/competitions")) {
      return Promise.resolve(
        jsonResponse(
          paginated([
            {
              id: "competition-1",
              sportId: "football",
              slug: "super-league",
              name: "Super League",
              shortName: "SL",
              countryCode: "GB",
            },
          ]),
        ),
      );
    }
    if (url.includes("/v1/public/leaderboards")) {
      return Promise.resolve(
        jsonResponse(
          paginated([
            {
              rank: 1,
              movement: null,
              userId: "user-1",
              email: "fan@sportsrush.test",
              displayName: "Fan One",
              totalPoints: 11,
              exactScores: 1,
              correctResults: 1,
              predictionsScored: 1,
              lastScoredAt: "2026-01-01T00:00:00.000Z",
            },
          ]),
        ),
      );
    }
    if (url.includes("/v1/public/fixtures")) {
      return Promise.resolve(jsonResponse(paginated([fixture])));
    }
    if (url.includes("/v1/auth/login")) {
      return Promise.resolve(
        jsonResponse({
          data: {
            user: { id: "user-1", email: "fan@sportsrush.test", role: "user" },
            accessToken: "access-token",
            refreshToken: "refresh-token",
            session: { id: "session-1" },
          },
        }),
      );
    }
    if (url.includes("/v1/predictions/me")) {
      return Promise.resolve(jsonResponse(paginated([])));
    }
    if (url.includes("/v1/predictions")) {
      return Promise.resolve(
        jsonResponse({
          data: {
            id: "prediction-1",
            userId: "user-1",
            fixtureId: "fixture-1",
            homeScore: 2,
            awayScore: 1,
            createdAt: "2026-01-01T00:00:00.000Z",
            updatedAt: "2026-01-01T00:00:00.000Z",
          },
        }),
      );
    }
    if (url.includes("/v1/auth/request-password-reset")) {
      return Promise.resolve(
        jsonResponse({
          data: {
            message:
              "If an account with that email exists, a password reset link has been sent.",
          },
        }),
      );
    }
    return Promise.resolve(jsonResponse(paginated([])));
  });
  vi.stubGlobal("fetch", fetchMock);
  return fetchMock;
}

beforeEach(() => {
  window.localStorage.clear();
});

afterEach(() => {
  vi.unstubAllGlobals();
});

describe("SportsRush web app", () => {
  it("renders homepage and navigates to public competitions", async () => {
    stubFetch();
    render(<App />);

    expect(
      screen.getByRole("heading", { name: /predict every score/i }),
    ).toBeTruthy();

    fireEvent.click(screen.getByRole("button", { name: "Competitions" }));

    expect(
      await screen.findByRole("heading", { name: "Competitions" }),
    ).toBeTruthy();
    expect(await screen.findByText("Super League")).toBeTruthy();
  });

  it("renders fixtures and saves an authenticated prediction", async () => {
    const fetchMock = stubFetch();
    render(<App />);

    fireEvent.click(screen.getAllByRole("button", { name: "Login" }).at(-1)!);
    fireEvent.change(screen.getByLabelText("Email"), {
      target: { value: "fan@sportsrush.test" },
    });
    fireEvent.change(screen.getByLabelText("Password"), {
      target: { value: "Password123!" },
    });
    fireEvent.click(screen.getAllByRole("button", { name: "Login" }).at(-1)!);

    await screen.findByRole("heading", { name: "Fixtures" });
    expect(await screen.findByText("Home FC")).toBeTruthy();

    fireEvent.change(screen.getByLabelText("Home prediction for Home FC"), {
      target: { value: "2" },
    });
    fireEvent.change(screen.getByLabelText("Away prediction for Away FC"), {
      target: { value: "1" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Save" }));

    await screen.findByText("Prediction saved.");
    expect(
      fetchMock.mock.calls.some(
        ([url, init]) =>
          String(url).includes("/v1/predictions") &&
          (init as RequestInit | undefined)?.method === "POST",
      ),
    ).toBe(true);
  });

  it("renders rankings and password reset flow", async () => {
    stubFetch();
    render(<App />);

    fireEvent.click(screen.getByRole("button", { name: "Rankings" }));
    expect(await screen.findByText("Fan One")).toBeTruthy();

    fireEvent.click(screen.getByRole("button", { name: "Login" }));
    fireEvent.click(
      screen.getByRole("button", { name: "Forgotten password?" }),
    );
    fireEvent.change(screen.getByLabelText("Email"), {
      target: { value: "fan@sportsrush.test" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Send reset link" }));

    await waitFor(() => {
      expect(
        screen.getByText(/password reset link has been sent/i),
      ).toBeTruthy();
    });
  });
});
