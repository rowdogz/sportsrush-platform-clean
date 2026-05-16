import "@testing-library/jest-dom/vitest";
import { render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { AuthSessionProvider } from "../contexts/AuthSessionProvider";
import { setAccessTokenProvider } from "../lib/apiClient";
import type { UserRole } from "../features/users/types";
import { DashboardPage } from "./DashboardPage";

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}

function accessTokenForRole(role: UserRole): string {
  return `header.${window.btoa(JSON.stringify({ role }))}.signature`;
}

function paginatedResponse(total: number, data: readonly unknown[] = []) {
  return jsonResponse({
    data,
    meta: { page: 1, limit: 50, total, hasMore: false },
  });
}

function auditEvent() {
  return {
    id: "audit-1",
    actorUserId: "admin-user",
    actorEmail: "admin@example.test",
    actorDisplayName: "Admin User",
    action: "fixture.result.enter",
    entityType: "fixture",
    entityId: "fixture-1",
    summary: "fixture.result.enter on fixture fixture-1",
    beforeMetadata: null,
    afterMetadata: { homeScore: 18, awayScore: 12 },
    changes: {},
    createdAt: "2026-05-14T12:00:00.000Z",
    correlationId: null,
  };
}

function completedFixture() {
  return {
    id: "fixture-1",
    sport_id: "sport-rugby-league",
    competition_id: "competition-1",
    season_id: "season-1",
    round_id: "round-1",
    round: "1",
    round_name: "Round 1",
    round_order: 1,
    home_team_id: "team-wigan",
    away_team_id: "team-saints",
    scheduled_at: "2026-05-14T18:00:00.000Z",
    original_scheduled_at: null,
    venue_name: "DW Stadium",
    status: "completed",
    home_score: 18,
    away_score: 12,
    result_source: "manual",
    result_entered_at: "2026-05-14T20:00:00.000Z",
    result_entered_by: "admin-user",
    legacy_match_id: null,
    legacy_fixture_id: null,
    external_source: null,
    external_id: null,
  };
}

function stubDashboardFetch({
  audit = true,
  empty = false,
}: {
  readonly audit?: boolean;
  readonly empty?: boolean;
} = {}) {
  const fetchMock = vi.fn((input: RequestInfo | URL) => {
    const url = String(input);
    if (url.startsWith("/v1/admin/competitions")) {
      return Promise.resolve(paginatedResponse(empty ? 0 : 3));
    }
    if (url.startsWith("/v1/admin/seasons")) {
      return Promise.resolve(paginatedResponse(empty ? 0 : 4));
    }
    if (url.startsWith("/v1/admin/teams")) {
      return Promise.resolve(paginatedResponse(empty ? 0 : 12));
    }
    if (url.startsWith("/v1/admin/users")) {
      return Promise.resolve(paginatedResponse(empty ? 0 : 8));
    }
    if (url.startsWith("/v1/admin/audit-events")) {
      return Promise.resolve(
        paginatedResponse(empty ? 0 : 1, empty || !audit ? [] : [auditEvent()]),
      );
    }
    if (url.startsWith("/v1/admin/fixtures")) {
      if (url.includes("status=completed")) {
        return Promise.resolve(
          paginatedResponse(empty ? 0 : 1, empty ? [] : [completedFixture()]),
        );
      }
      return Promise.resolve(paginatedResponse(empty ? 0 : 25));
    }
    return Promise.resolve(paginatedResponse(0));
  });
  vi.stubGlobal("fetch", fetchMock);
  return fetchMock;
}

function renderDashboard(role: UserRole = "superadmin") {
  window.localStorage.setItem(
    "sr_admin_access_token",
    accessTokenForRole(role),
  );
  render(
    <AuthSessionProvider>
      <DashboardPage />
    </AuthSessionProvider>,
  );
}

describe("DashboardPage", () => {
  afterEach(() => {
    vi.restoreAllMocks();
    setAccessTokenProvider(() => null);
    window.localStorage.clear();
  });

  it("renders summary cards, recent activity, and fixture changes for superadmins", async () => {
    stubDashboardFetch();

    renderDashboard("superadmin");

    expect(screen.getByRole("status")).toHaveTextContent("Loading dashboard…");
    expect(await screen.findByText("Competitions")).toBeTruthy();
    expect(screen.getByText("3")).toBeTruthy();
    expect(screen.getByText("Audit Events")).toBeTruthy();
    expect(screen.getByText("fixture.result.enter")).toBeTruthy();
    expect(screen.getByText("team-wigan vs team-saints")).toBeTruthy();
    expect(screen.getByText("18-12")).toBeTruthy();
  });

  it("hides audit sections and does not call audit APIs for non-superadmin admins", async () => {
    const fetchMock = stubDashboardFetch({ audit: false });

    renderDashboard("admin");

    expect(await screen.findByText("Competitions")).toBeTruthy();
    expect(screen.queryByText("Audit Events")).not.toBeInTheDocument();
    expect(screen.queryByText("Recent Activity")).not.toBeInTheDocument();
    expect(
      fetchMock.mock.calls.some(([url]) =>
        String(url).includes("audit-events"),
      ),
    ).toBe(false);
  });

  it("renders empty states when no overview data exists", async () => {
    stubDashboardFetch({ empty: true });

    renderDashboard("superadmin");

    expect(await screen.findByText("No admin data yet")).toBeTruthy();
    expect(screen.getByText("No recent activity")).toBeTruthy();
    expect(screen.getByText("No recent fixture result changes")).toBeTruthy();
  });

  it("renders an error state when dashboard data fails to load", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn((input: RequestInfo | URL) => {
        const url = String(input);
        if (url.startsWith("/v1/admin/competitions")) {
          return Promise.resolve(
            jsonResponse(
              {
                error: {
                  code: "upstream_error",
                  message: "Unable to load competitions.",
                },
              },
              500,
            ),
          );
        }
        return Promise.resolve(paginatedResponse(0));
      }),
    );

    renderDashboard("superadmin");

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Unable to load dashboard",
    );
    expect(screen.getByText("Unable to load competitions.")).toBeTruthy();
  });
});
