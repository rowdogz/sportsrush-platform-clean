import "@testing-library/jest-dom/vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { setAccessTokenProvider } from "../lib/apiClient";
import { FixturesPage } from "./FixturesPage";

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}

function competitionListResponse(): Response {
  return jsonResponse({
    data: [
      {
        id: "competition-1",
        sport_id: "sport-rugby-league",
        slug: "super-league",
        name: "Super League",
        is_active: 1,
      },
    ],
    meta: { page: 1, limit: 50, total: 1, hasMore: false },
  });
}

function teamListResponse(): Response {
  return jsonResponse({
    data: [
      {
        id: "team-home",
        sport_id: "sport-rugby-league",
        slug: "wigan-warriors",
        name: "Wigan Warriors",
        is_active: 1,
      },
      {
        id: "team-away",
        sport_id: "sport-rugby-league",
        slug: "st-helens",
        name: "St Helens",
        is_active: 1,
      },
    ],
    meta: { page: 1, limit: 50, total: 2, hasMore: false },
  });
}

function fixtureListResponse(
  data: readonly Record<string, unknown>[],
): Response {
  return jsonResponse({
    data,
    meta: { page: 1, limit: 50, total: data.length, hasMore: false },
  });
}

function fixture(overrides: Record<string, unknown> = {}) {
  return {
    id: "fixture-1",
    sport_id: "sport-rugby-league",
    competition_id: "competition-1",
    season_id: "season-2026",
    round_id: null,
    round: "1",
    round_name: "Round 1",
    round_order: 1,
    home_team_id: "team-home",
    away_team_id: "team-away",
    scheduled_at: "2026-02-01T20:00:00.000Z",
    original_scheduled_at: null,
    venue_name: "DW Stadium",
    status: "scheduled",
    home_score: null,
    away_score: null,
    result_source: null,
    result_entered_at: null,
    result_entered_by: null,
    legacy_match_id: null,
    legacy_fixture_id: null,
    external_source: null,
    external_id: null,
    ...overrides,
  };
}

describe("FixturesPage", () => {
  afterEach(() => {
    vi.restoreAllMocks();
    setAccessTokenProvider(() => null);
    window.localStorage.clear();
  });

  it("shows loading and then renders fixtures", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(competitionListResponse())
      .mockResolvedValueOnce(teamListResponse())
      .mockResolvedValueOnce(fixtureListResponse([fixture()]));
    vi.stubGlobal("fetch", fetchMock);

    render(<FixturesPage />);

    expect(screen.getByRole("status")).toHaveTextContent("Loading fixtures…");
    expect(
      await screen.findByRole("cell", {
        name: "Wigan Warriors v St Helens",
      }),
    ).toBeTruthy();
    expect(screen.getByRole("cell", { name: "season-2026" })).toBeTruthy();
    expect(screen.getByRole("cell", { name: "Round 1 (1)" })).toBeTruthy();
    expect(screen.getByRole("cell", { name: "DW Stadium" })).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      3,
      "/v1/admin/fixtures?page=1&limit=50",
      expect.objectContaining({ headers: expect.any(Headers) }),
    );
  });

  it("renders an empty state", async () => {
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockResolvedValueOnce(competitionListResponse())
        .mockResolvedValueOnce(teamListResponse())
        .mockResolvedValueOnce(fixtureListResponse([])),
    );

    render(<FixturesPage />);

    expect(await screen.findByText("No fixtures found")).toBeTruthy();
    expect(
      screen.getByText("Fixtures will appear here after they are added."),
    ).toBeTruthy();
  });

  it("renders an API error state", async () => {
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockResolvedValueOnce(
          jsonResponse(
            {
              error: {
                code: "forbidden",
                message: "Admin access is required.",
              },
            },
            403,
          ),
        )
        .mockResolvedValueOnce(teamListResponse())
        .mockResolvedValueOnce(fixtureListResponse([])),
    );

    render(<FixturesPage />);

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Unable to load fixtures",
    );
    expect(screen.getByText("Admin access is required.")).toBeTruthy();
  });

  it("creates a fixture and refreshes the list", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(competitionListResponse())
      .mockResolvedValueOnce(teamListResponse())
      .mockResolvedValueOnce(fixtureListResponse([]))
      .mockResolvedValueOnce(jsonResponse({ data: [] }))
      .mockResolvedValueOnce(jsonResponse({ data: fixture() }, 201))
      .mockResolvedValueOnce(fixtureListResponse([fixture()]));
    vi.stubGlobal("fetch", fetchMock);

    render(<FixturesPage />);

    await screen.findByText("No fixtures found");
    fireEvent.change(screen.getByLabelText("Competition"), {
      target: { value: "competition-1" },
    });
    fireEvent.change(screen.getByLabelText("Season ID"), {
      target: { value: "season-2026" },
    });
    fireEvent.change(screen.getByLabelText("Round"), {
      target: { value: "1" },
    });
    fireEvent.change(screen.getByLabelText("Round name"), {
      target: { value: "Round 1" },
    });
    fireEvent.change(screen.getByLabelText("Round order"), {
      target: { value: "1" },
    });
    fireEvent.change(screen.getByLabelText("Home team"), {
      target: { value: "team-home" },
    });
    fireEvent.change(screen.getByLabelText("Away team"), {
      target: { value: "team-away" },
    });
    fireEvent.change(screen.getByLabelText("Scheduled at"), {
      target: { value: "2026-02-01T20:00:00.000Z" },
    });
    fireEvent.change(screen.getByLabelText("Venue"), {
      target: { value: "DW Stadium" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Create fixture" }));

    expect(await screen.findByText("Fixture created.")).toBeTruthy();
    expect(
      await screen.findByRole("cell", {
        name: "Wigan Warriors v St Helens",
      }),
    ).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      4,
      "/v1/admin/rounds?seasonId=season-2026",
      expect.any(Object),
    );
    expect(fetchMock).toHaveBeenNthCalledWith(
      5,
      "/v1/admin/fixtures",
      expect.objectContaining({
        method: "POST",
        body: JSON.stringify({
          sportId: "sport-rugby-league",
          competitionId: "competition-1",
          seasonId: "season-2026",
          roundId: null,
          round: "1",
          roundName: "Round 1",
          roundOrder: 1,
          homeTeamId: "team-home",
          awayTeamId: "team-away",
          scheduledAt: "2026-02-01T20:00:00.000Z",
          originalScheduledAt: null,
          venueName: "DW Stadium",
          status: "scheduled",
          homeScore: null,
          awayScore: null,
          legacyMatchId: null,
          legacyFixtureId: null,
          externalSource: null,
          externalId: null,
        }),
      }),
    );
  });

  it("edits a fixture and refreshes the list", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(competitionListResponse())
      .mockResolvedValueOnce(teamListResponse())
      .mockResolvedValueOnce(fixtureListResponse([fixture()]))
      .mockResolvedValueOnce(
        jsonResponse({ data: fixture({ venue_name: "Updated Stadium" }) }),
      )
      .mockResolvedValueOnce(
        fixtureListResponse([fixture({ venue_name: "Updated Stadium" })]),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<FixturesPage />);

    await screen.findByRole("cell", { name: "DW Stadium" });
    fireEvent.click(screen.getByRole("button", { name: "Edit" }));
    fireEvent.change(screen.getByLabelText("Edit venue"), {
      target: { value: "Updated Stadium" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Save" }));

    expect(await screen.findByText("Fixture updated.")).toBeTruthy();
    expect(
      await screen.findByRole("cell", { name: "Updated Stadium" }),
    ).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      4,
      "/v1/admin/fixtures/fixture-1",
      expect.objectContaining({
        method: "PATCH",
        body: expect.stringContaining("Updated Stadium"),
      }),
    );
  });

  it("transitions fixture status", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(competitionListResponse())
      .mockResolvedValueOnce(teamListResponse())
      .mockResolvedValueOnce(fixtureListResponse([fixture()]))
      .mockResolvedValueOnce(
        jsonResponse({ data: fixture({ status: "postponed" }) }),
      )
      .mockResolvedValueOnce(
        fixtureListResponse([fixture({ status: "postponed" })]),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<FixturesPage />);

    await screen.findByRole("cell", { name: "scheduled" });
    fireEvent.change(screen.getByLabelText("Transition status for fixture-1"), {
      target: { value: "postponed" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Update status" }));

    expect(await screen.findByText("Fixture status updated.")).toBeTruthy();
    expect(await screen.findByRole("cell", { name: "postponed" })).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      4,
      "/v1/admin/fixtures/fixture-1/transition",
      expect.objectContaining({
        method: "POST",
        body: JSON.stringify({ status: "postponed" }),
      }),
    );
  });

  it("enters a fixture result", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(competitionListResponse())
      .mockResolvedValueOnce(teamListResponse())
      .mockResolvedValueOnce(fixtureListResponse([fixture()]))
      .mockResolvedValueOnce(
        jsonResponse({
          data: fixture({
            status: "completed",
            home_score: 22,
            away_score: 18,
            result_source: "manual",
          }),
        }),
      )
      .mockResolvedValueOnce(
        fixtureListResponse([
          fixture({ status: "completed", home_score: 22, away_score: 18 }),
        ]),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<FixturesPage />);

    await screen.findByRole("cell", { name: "scheduled" });
    fireEvent.change(screen.getByLabelText("Home score for fixture-1"), {
      target: { value: "22" },
    });
    fireEvent.change(screen.getByLabelText("Away score for fixture-1"), {
      target: { value: "18" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Enter result" }));

    expect(await screen.findByText("Fixture result entered.")).toBeTruthy();
    expect(await screen.findByRole("cell", { name: "22-18" })).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      4,
      "/v1/admin/fixtures/fixture-1/result",
      expect.objectContaining({
        method: "POST",
        body: JSON.stringify({
          homeScore: 22,
          awayScore: 18,
          resultSource: "manual",
        }),
      }),
    );
  });

  it("corrects a fixture result with an audit reason", async () => {
    const completedFixture = fixture({
      status: "completed",
      home_score: 22,
      away_score: 18,
      result_source: "manual",
    });
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(competitionListResponse())
      .mockResolvedValueOnce(teamListResponse())
      .mockResolvedValueOnce(fixtureListResponse([completedFixture]))
      .mockResolvedValueOnce(
        jsonResponse({ data: fixture({ home_score: 24, away_score: 18 }) }),
      )
      .mockResolvedValueOnce(
        fixtureListResponse([
          fixture({ status: "completed", home_score: 24, away_score: 18 }),
        ]),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<FixturesPage />);

    await screen.findByRole("cell", { name: "22-18" });
    fireEvent.change(
      screen.getByLabelText("Corrected home score for fixture-1"),
      {
        target: { value: "24" },
      },
    );
    fireEvent.change(screen.getByLabelText("Correction reason for fixture-1"), {
      target: { value: "Official correction" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Correct result" }));

    expect(await screen.findByText("Fixture result corrected.")).toBeTruthy();
    expect(await screen.findByRole("cell", { name: "24-18" })).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      4,
      "/v1/admin/fixtures/fixture-1/correct-result",
      expect.objectContaining({
        method: "POST",
        body: JSON.stringify({
          homeScore: 24,
          awayScore: 18,
          reason: "Official correction",
        }),
      }),
    );
  });

  it("validates required fixture fields", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(competitionListResponse())
      .mockResolvedValueOnce(teamListResponse())
      .mockResolvedValueOnce(fixtureListResponse([]));
    vi.stubGlobal("fetch", fetchMock);

    render(<FixturesPage />);

    await screen.findByText("No fixtures found");
    fireEvent.click(screen.getByRole("button", { name: "Create fixture" }));

    expect(screen.getByRole("alert")).toHaveTextContent(
      "Sport, competition, season, round, teams, and scheduled time are required.",
    );
    expect(fetchMock).toHaveBeenCalledTimes(3);
  });
});
