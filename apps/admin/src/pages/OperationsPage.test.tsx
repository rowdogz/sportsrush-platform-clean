import "@testing-library/jest-dom/vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { setAccessTokenProvider } from "../lib/apiClient";
import { OperationsPage } from "./OperationsPage";

vi.mock("../contexts/AuthSessionProvider", () => ({
  useAuthSession: () => ({
    userRole: "superadmin",
  }),
}));

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}

function paginatedResponse(data: readonly Record<string, unknown>[]): Response {
  return jsonResponse({
    data,
    meta: { page: 1, limit: 50, total: data.length, hasMore: false },
  });
}

function teamListResponse(): Response {
  return paginatedResponse([
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
  ]);
}

function auditListResponse(data: readonly Record<string, unknown>[]): Response {
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
    status: "completed",
    home_score: 22,
    away_score: 18,
    result_source: "manual",
    result_entered_at: "2026-02-01T22:00:00.000Z",
    result_entered_by: "admin-1",
    legacy_match_id: null,
    legacy_fixture_id: null,
    external_source: null,
    external_id: null,
    ...overrides,
  };
}

describe("OperationsPage", () => {
  afterEach(() => {
    vi.restoreAllMocks();
    setAccessTokenProvider(() => null);
    window.localStorage.clear();
  });

  it("loads operations data and renders summary and scoring tools", async () => {
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockResolvedValueOnce(
          paginatedResponse([
            fixture({
              id: "fixture-scheduled",
              status: "scheduled",
              home_score: null,
              away_score: null,
            }),
          ]),
        )
        .mockResolvedValueOnce(paginatedResponse([]))
        .mockResolvedValueOnce(paginatedResponse([fixture()]))
        .mockResolvedValueOnce(paginatedResponse([]))
        .mockResolvedValueOnce(teamListResponse())
        .mockResolvedValueOnce(
          auditListResponse([
            {
              id: "audit-1",
              action: "fixture.result.correct",
              entity_type: "fixture",
              entity_id: "fixture-1",
              summary: "fixture.result.correct on fixture fixture-1",
              created_at: "2026-02-01T22:30:00.000Z",
              actor_display_name: "Admin User",
            },
          ]),
        ),
    );

    render(<OperationsPage />);

    expect(await screen.findByText("Scored fixtures")).toBeTruthy();
    expect(screen.getByText("Stale kickoff backlog")).toBeTruthy();
    expect(
      screen.getByRole("button", { name: "Recalculate scores" }),
    ).toBeTruthy();
    expect(
      screen.getByText("fixture.result.correct on fixture fixture-1"),
    ).toBeTruthy();
  });

  it("recalculates prediction scores for a scored fixture", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(paginatedResponse([]))
      .mockResolvedValueOnce(paginatedResponse([]))
      .mockResolvedValueOnce(paginatedResponse([fixture()]))
      .mockResolvedValueOnce(paginatedResponse([]))
      .mockResolvedValueOnce(teamListResponse())
      .mockResolvedValueOnce(auditListResponse([]))
      .mockResolvedValueOnce(jsonResponse({ data: { scoredPredictions: 4 } }))
      .mockResolvedValueOnce(paginatedResponse([]))
      .mockResolvedValueOnce(paginatedResponse([]))
      .mockResolvedValueOnce(paginatedResponse([fixture()]))
      .mockResolvedValueOnce(paginatedResponse([]))
      .mockResolvedValueOnce(teamListResponse())
      .mockResolvedValueOnce(auditListResponse([]));
    vi.stubGlobal("fetch", fetchMock);

    render(<OperationsPage />);

    fireEvent.click(
      await screen.findByRole("button", { name: "Recalculate scores" }),
    );

    expect(
      await screen.findByText(
        "Recalculated 4 prediction scores for Wigan Warriors v St Helens.",
      ),
    ).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      7,
      "/v1/predictions/fixtures/fixture-1/recalculate",
      expect.objectContaining({
        method: "POST",
      }),
    );
  });

  it("corrects a fixture result from operations", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(paginatedResponse([]))
      .mockResolvedValueOnce(paginatedResponse([]))
      .mockResolvedValueOnce(paginatedResponse([fixture()]))
      .mockResolvedValueOnce(paginatedResponse([]))
      .mockResolvedValueOnce(teamListResponse())
      .mockResolvedValueOnce(auditListResponse([]))
      .mockResolvedValueOnce(
        jsonResponse({ data: fixture({ home_score: 24, away_score: 18 }) }),
      )
      .mockResolvedValueOnce(paginatedResponse([]))
      .mockResolvedValueOnce(paginatedResponse([]))
      .mockResolvedValueOnce(
        paginatedResponse([fixture({ home_score: 24, away_score: 18 })]),
      )
      .mockResolvedValueOnce(paginatedResponse([]))
      .mockResolvedValueOnce(teamListResponse())
      .mockResolvedValueOnce(auditListResponse([]));
    vi.stubGlobal("fetch", fetchMock);

    render(<OperationsPage />);

    fireEvent.click(
      await screen.findByRole("button", { name: "Correct result" }),
    );
    fireEvent.change(
      screen.getByLabelText("Corrected home score for fixture-1"),
      {
        target: { value: "24" },
      },
    );
    fireEvent.change(screen.getByLabelText("Correction reason for fixture-1"), {
      target: { value: "Official correction" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Save correction" }));

    expect(await screen.findByText("Fixture result corrected.")).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      7,
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

  it("looks up team aliases and shows an empty state when none match", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(paginatedResponse([]))
      .mockResolvedValueOnce(paginatedResponse([]))
      .mockResolvedValueOnce(paginatedResponse([fixture()]))
      .mockResolvedValueOnce(paginatedResponse([]))
      .mockResolvedValueOnce(teamListResponse())
      .mockResolvedValueOnce(auditListResponse([]))
      .mockResolvedValueOnce(jsonResponse({ data: [] }));
    vi.stubGlobal("fetch", fetchMock);

    render(<OperationsPage />);

    await screen.findByText("Scored fixtures");
    fireEvent.change(screen.getByLabelText("Alias"), {
      target: { value: "Wigan RL" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Lookup alias" }));

    expect(await screen.findByText("No alias mapping found")).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      7,
      "/v1/admin/team-aliases?sportId=sport-rugby-league&source=manual&alias=Wigan+RL",
      expect.any(Object),
    );
  });
});
