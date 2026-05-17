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

const lockedFixture = {
  id: "fixture-2",
  kickoffTime: "2000-01-01T15:00:00.000Z",
  venue: "Legacy Ground",
  status: "scheduled",
  homeScore: null,
  awayScore: null,
  homeTeam: {
    id: "team-locked-home",
    name: "Locked Home FC",
    shortName: "LHF",
    displayName: "Locked Home FC",
    logoUrl: null,
    badgeUrl: null,
  },
  awayTeam: {
    id: "team-locked-away",
    name: "Locked Away FC",
    shortName: "LAF",
    displayName: "Locked Away FC",
    logoUrl: null,
    badgeUrl: null,
  },
  round: { id: "round-2", round: "2", name: "Round 2", displayOrder: 2 },
  season: { id: "season-1", slug: "2026", name: "2026" },
  competition: {
    id: "competition-1",
    slug: "super-league",
    name: "Super League",
    shortName: "SL",
  },
};

const completedFixture = {
  id: "fixture-3",
  kickoffTime: "2026-01-10T15:00:00.000Z",
  venue: "Results Arena",
  status: "completed",
  homeScore: 2,
  awayScore: 1,
  homeTeam: {
    id: "team-results-home",
    name: "Results Home FC",
    shortName: "RHF",
    displayName: "Results Home FC",
    logoUrl: null,
    badgeUrl: null,
  },
  awayTeam: {
    id: "team-results-away",
    name: "Results Away FC",
    shortName: "RAF",
    displayName: "Results Away FC",
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

function stubFetch({
  role = "user",
  predictionSaveStatus = 200,
}: {
  readonly role?: "user" | "admin" | "superadmin";
  readonly predictionSaveStatus?: number;
} = {}): ReturnType<typeof vi.fn> {
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
    if (url.includes("/v1/public/seasons")) {
      return Promise.resolve(
        jsonResponse(
          paginated([
            {
              id: "season-1",
              competitionId: "competition-1",
              slug: "2026",
              name: "2026",
              startsOn: "2026-01-01",
              endsOn: "2026-12-31",
              competition: {
                id: "competition-1",
                slug: "super-league",
                name: "Super League",
                shortName: "SL",
              },
            },
          ]),
        ),
      );
    }
    if (url.includes("/v1/public/rounds")) {
      return Promise.resolve(
        jsonResponse(
          paginated([
            {
              id: "round-1",
              seasonId: "season-1",
              round: "1",
              name: "Round 1",
              displayOrder: 1,
              startsAt: "2026-01-01T00:00:00.000Z",
              endsAt: "2026-01-07T23:59:59.000Z",
              season: { id: "season-1", slug: "2026", name: "2026" },
              competition: {
                id: "competition-1",
                slug: "super-league",
                name: "Super League",
                shortName: "SL",
              },
            },
            {
              id: "round-2",
              seasonId: "season-1",
              round: "2",
              name: "Round 2",
              displayOrder: 2,
              startsAt: "2026-01-08T00:00:00.000Z",
              endsAt: "2026-01-14T23:59:59.000Z",
              season: { id: "season-1", slug: "2026", name: "2026" },
              competition: {
                id: "competition-1",
                slug: "super-league",
                name: "Super League",
                shortName: "SL",
              },
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
              movement: 2,
              userId: "user-1",
              email: "fan@sportsrush.test",
              displayName: "Fan One",
              totalPoints: 11,
              exactScores: 1,
              correctResults: 1,
              predictionsScored: 1,
              lastScoredAt: "2026-01-01T00:00:00.000Z",
            },
            {
              rank: 2,
              movement: -1,
              userId: "user-2",
              email: "other@sportsrush.test",
              displayName: "Other Player",
              totalPoints: 9,
              exactScores: 0,
              correctResults: 2,
              predictionsScored: 2,
              lastScoredAt: "2026-01-01T00:00:00.000Z",
            },
          ]),
        ),
      );
    }
    if (url.includes("/v1/public/fixtures")) {
      if (url.includes("status=completed")) {
        return Promise.resolve(jsonResponse(paginated([completedFixture])));
      }
      if (url.includes("roundId=round-2")) {
        return Promise.resolve(jsonResponse(paginated([lockedFixture])));
      }
      return Promise.resolve(jsonResponse(paginated([fixture, lockedFixture])));
    }
    if (url.includes("/v1/auth/login")) {
      return Promise.resolve(
        jsonResponse({
          data: {
            user: { id: "user-1", email: "fan@sportsrush.test", role },
            accessToken: "access-token",
            refreshToken: "refresh-token",
            session: { id: "session-1" },
          },
        }),
      );
    }
    if (url.includes("/v1/predictions/me")) {
      return Promise.resolve(
        jsonResponse(
          paginated([
            {
              id: "prediction-locked",
              userId: "user-1",
              fixtureId: "fixture-2",
              homeScore: 1,
              awayScore: 0,
              createdAt: "2026-01-01T00:00:00.000Z",
              updatedAt: "2026-01-01T00:00:00.000Z",
            },
            {
              id: "prediction-results",
              userId: "user-1",
              fixtureId: "fixture-3",
              homeScore: 2,
              awayScore: 1,
              createdAt: "2026-01-10T00:00:00.000Z",
              updatedAt: "2026-01-10T16:00:00.000Z",
            },
          ]),
        ),
      );
    }
    if (url.includes("/v1/predictions")) {
      if (predictionSaveStatus !== 200) {
        return Promise.resolve(
          jsonResponse(
            {
              error: {
                code: "prediction_invalid",
                message: "Prediction window has closed.",
              },
            },
            predictionSaveStatus,
          ),
        );
      }
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
  document.documentElement.removeAttribute("data-theme");
});

afterEach(() => {
  vi.unstubAllGlobals();
});

describe("SportsRush web app", () => {
  it("renders homepage and navigates to public competitions", async () => {
    stubFetch();
    render(<App />);

    expect(document.documentElement.dataset.theme).toBe("light");
    expect(
      screen.getByRole("heading", { name: /predict every score/i }),
    ).toBeTruthy();

    fireEvent.click(
      screen.getAllByRole("button", { name: "Competitions" })[0]!,
    );

    expect(
      await screen.findByRole("heading", { name: "Competitions" }),
    ).toBeTruthy();
    expect(await screen.findByText("Super League")).toBeTruthy();
  });

  it("persists theme changes and shows admin entry only for authorized users", async () => {
    stubFetch({ role: "admin" });
    render(<App />);

    expect(screen.queryByRole("link", { name: "Admin" })).toBeNull();

    fireEvent.click(screen.getByRole("button", { name: "Login" }));
    fireEvent.change(screen.getByLabelText("Email"), {
      target: { value: "admin@sportsrush.test" },
    });
    fireEvent.change(screen.getByLabelText("Password"), {
      target: { value: "Password123!" },
    });
    fireEvent.click(screen.getAllByRole("button", { name: "Login" }).at(-1)!);

    expect(
      (await screen.findAllByRole("link", { name: "Admin" }))[0],
    ).toHaveAttribute("href", "/admin");

    fireEvent.click(
      screen.getByRole("button", { name: "Switch to dark mode" }),
    );
    expect(document.documentElement.dataset.theme).toBe("dark");
    expect(window.localStorage.getItem("sr_theme_mode")).toBe("dark");
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

  it("renders redesigned predictions with selectors and locked read-only fixtures", async () => {
    stubFetch();
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

    fireEvent.click(
      screen.getAllByRole("button", { name: "Predictions" }).at(-1)!,
    );

    expect(
      await screen.findByRole("heading", { name: "Predictions" }),
    ).toBeTruthy();
    expect(screen.getByLabelText("Competition")).toBeTruthy();
    expect(screen.getByLabelText("Season")).toBeTruthy();
    expect(screen.getByLabelText("Round")).toBeTruthy();

    expect(await screen.findByText("Home FC")).toBeTruthy();
    expect(await screen.findByText("Locked Home FC")).toBeTruthy();

    fireEvent.change(screen.getByLabelText("Round"), {
      target: { value: "round-2" },
    });

    expect(await screen.findByText("Locked prediction: 1 - 0")).toBeTruthy();
    expect(
      screen.queryByLabelText("Home prediction for Locked Home FC"),
    ).toBeNull();
  });

  it("shows prediction save errors in the redesigned predictions flow", async () => {
    stubFetch({ predictionSaveStatus: 409 });
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

    fireEvent.click(
      screen.getAllByRole("button", { name: "Predictions" }).at(-1)!,
    );

    await screen.findByRole("heading", { name: "Predictions" });
    await screen.findByText("Home FC");
    const homePredictionInput = await screen.findByLabelText(
      "Home prediction for Home FC",
    );
    const awayPredictionInput = await screen.findByLabelText(
      "Away prediction for Away FC",
    );

    fireEvent.change(homePredictionInput, {
      target: { value: "4" },
    });
    fireEvent.change(awayPredictionInput, {
      target: { value: "2" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Save prediction" }));

    expect(
      await screen.findByText("Prediction window has closed."),
    ).toBeTruthy();
  });

  it("renders redesigned rankings with current-user highlighting and local search", async () => {
    stubFetch();
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

    fireEvent.click(
      screen.getAllByRole("button", { name: "Rankings" }).at(-1)!,
    );

    expect(
      await screen.findByRole("heading", { name: "Rankings" }),
    ).toBeTruthy();
    expect(screen.getByLabelText("Leaderboard competition")).toBeTruthy();
    expect(screen.getByLabelText("Leaderboard search")).toBeTruthy();
    expect(await screen.findByText("Your standing")).toBeTruthy();
    expect(screen.getByText("#1 · 11 pts · Fan One")).toBeTruthy();
    expect(screen.getAllByText("Fan One").length).toBeGreaterThan(0);
    expect(screen.getAllByText("Other Player").length).toBeGreaterThan(0);
    expect(screen.getAllByText("↑ 2").length).toBeGreaterThan(0);
    expect(screen.getAllByText("↓ 1").length).toBeGreaterThan(0);

    fireEvent.change(screen.getByLabelText("Leaderboard search"), {
      target: { value: "other" },
    });

    await waitFor(() => {
      expect(screen.queryByText("Your standing")).toBeNull();
      expect(screen.queryByText("#1 · 11 pts · Fan One")).toBeNull();
      expect(screen.queryByText("Fan One")).toBeNull();
      expect(screen.getAllByText("Other Player").length).toBeGreaterThan(0);
    });
  });

  it("renders password reset flow", async () => {
    stubFetch();
    render(<App />);

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

  it("renders redesigned results with prediction comparison and points deferral", async () => {
    stubFetch();
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

    fireEvent.click(screen.getAllByRole("button", { name: "Results" }).at(-1)!);

    expect(
      await screen.findByRole("heading", { name: "Results" }),
    ).toBeTruthy();
    expect(screen.getByLabelText("Results competition")).toBeTruthy();
    expect(screen.getByLabelText("Results season")).toBeTruthy();
    expect(screen.getByLabelText("Results round")).toBeTruthy();
    expect(await screen.findByText("Results Home FC")).toBeTruthy();
    expect(await screen.findByText("Your prediction: 2 - 1")).toBeTruthy();
    expect(await screen.findByText("Exact score")).toBeTruthy();
    expect(await screen.findByText("Points unavailable")).toBeTruthy();
    expect(
      screen.getByText(
        /scoring points are not yet exposed by the current api/i,
      ),
    ).toBeTruthy();
  });
});
