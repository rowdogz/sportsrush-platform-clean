import "@testing-library/jest-dom/vitest";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { setAccessTokenProvider } from "../lib/apiClient";
import { TeamsPage } from "./TeamsPage";

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}

function teamListResponse(data: readonly Record<string, unknown>[]): Response {
  return jsonResponse({
    data,
    meta: { page: 1, limit: 50, total: data.length, hasMore: false },
  });
}

describe("TeamsPage", () => {
  afterEach(() => {
    vi.restoreAllMocks();
    setAccessTokenProvider(() => null);
    window.localStorage.clear();
  });

  it("shows loading and then renders teams", async () => {
    const fetchMock = vi.fn().mockResolvedValue(
      teamListResponse([
        {
          id: "team-1",
          sport_id: "sport-rugby-league",
          slug: "wigan-warriors",
          name: "Wigan Warriors",
          short_name: "Wigan",
          display_name: "Wigan Warriors RLFC",
          country_code: "GB",
          legacy_id: 123,
          is_active: 1,
        },
      ]),
    );
    vi.stubGlobal("fetch", fetchMock);

    render(<TeamsPage />);

    expect(screen.getByRole("status")).toHaveTextContent("Loading teams…");
    expect(
      await screen.findByRole("cell", { name: "Wigan Warriors" }),
    ).toBeTruthy();
    expect(
      screen.getByRole("cell", { name: "Wigan Warriors RLFC" }),
    ).toBeTruthy();
    expect(screen.getByRole("cell", { name: "wigan-warriors" })).toBeTruthy();
    expect(
      screen.getByRole("cell", { name: "sport-rugby-league" }),
    ).toBeTruthy();
    expect(screen.getByRole("cell", { name: "Wigan" })).toBeTruthy();
    expect(screen.getByRole("cell", { name: "GB" })).toBeTruthy();
    expect(screen.getByRole("cell", { name: "123" })).toBeTruthy();
    expect(screen.getByText("Active")).toBeTruthy();
    expect(fetchMock).toHaveBeenCalledWith(
      "/v1/admin/teams?page=1&limit=50",
      expect.objectContaining({
        headers: expect.any(Headers),
      }),
    );
  });

  it("renders an empty state", async () => {
    vi.stubGlobal("fetch", vi.fn().mockResolvedValue(teamListResponse([])));

    render(<TeamsPage />);

    expect(await screen.findByText("No teams found")).toBeTruthy();
    expect(
      screen.getByText("Teams will appear here after they are added."),
    ).toBeTruthy();
  });

  it("renders an API error state", async () => {
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

    render(<TeamsPage />);

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Unable to load teams",
    );
    expect(screen.getByText("Admin access is required.")).toBeTruthy();
  });

  it("creates a team and refreshes the list", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(teamListResponse([]))
      .mockResolvedValueOnce(
        jsonResponse({
          id: "team-2",
          sportId: "sport-rugby-league",
          slug: "st-helens",
          name: "St Helens",
          shortName: "Saints",
          displayName: "St Helens RLFC",
          countryCode: "GB",
          legacyId: "456",
          isActive: true,
        }),
      )
      .mockResolvedValueOnce(
        teamListResponse([
          {
            id: "team-2",
            sport_id: "sport-rugby-league",
            slug: "st-helens",
            name: "St Helens",
            short_name: "Saints",
            display_name: "St Helens RLFC",
            country_code: "GB",
            legacy_id: "456",
            is_active: 1,
          },
        ]),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<TeamsPage />);

    await screen.findByText("No teams found");
    fireEvent.change(screen.getByLabelText("Sport ID"), {
      target: { value: "sport-rugby-league" },
    });
    fireEvent.change(screen.getByLabelText("Slug"), {
      target: { value: "st-helens" },
    });
    fireEvent.change(screen.getByLabelText("Name"), {
      target: { value: "St Helens" },
    });
    fireEvent.change(screen.getByLabelText("Short name"), {
      target: { value: "Saints" },
    });
    fireEvent.change(screen.getByLabelText("Display name"), {
      target: { value: "St Helens RLFC" },
    });
    fireEvent.change(screen.getByLabelText("Country code"), {
      target: { value: "GB" },
    });
    fireEvent.change(screen.getByLabelText("Legacy ID"), {
      target: { value: "456" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Create team" }));

    expect(await screen.findByText("Team created.")).toBeTruthy();
    expect(await screen.findByRole("cell", { name: "St Helens" })).toBeTruthy();
    expect(screen.getByLabelText("Name")).toHaveValue("");
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/teams",
      expect.objectContaining({
        method: "POST",
        body: JSON.stringify({
          sportId: "sport-rugby-league",
          slug: "st-helens",
          name: "St Helens",
          shortName: "Saints",
          displayName: "St Helens RLFC",
          countryCode: "GB",
          legacyId: "456",
        }),
      }),
    );
  });

  it("validates required create fields", async () => {
    const fetchMock = vi.fn().mockResolvedValue(teamListResponse([]));
    vi.stubGlobal("fetch", fetchMock);

    render(<TeamsPage />);

    await screen.findByText("No teams found");
    fireEvent.click(screen.getByRole("button", { name: "Create team" }));

    expect(screen.getByRole("alert")).toHaveTextContent(
      "Sport ID, slug, and name are required.",
    );
    expect(fetchMock).toHaveBeenCalledTimes(1);
  });

  it("edits a team and refreshes the list", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(
        teamListResponse([
          {
            id: "team-1",
            sport_id: "sport-rugby-league",
            slug: "wigan-warriors",
            name: "Wigan Warriors",
            short_name: "Wigan",
            display_name: "Wigan Warriors",
            country_code: "GB",
            legacy_id: "123",
            is_active: 1,
          },
        ]),
      )
      .mockResolvedValueOnce(
        jsonResponse({
          id: "team-1",
          sportId: "sport-rugby-league",
          slug: "wigan-warriors",
          name: "Wigan Warriors",
          shortName: "Wigan",
          displayName: "Wigan Warriors RLFC",
          countryCode: "GB",
          legacyId: "123",
          isActive: true,
        }),
      )
      .mockResolvedValueOnce(
        teamListResponse([
          {
            id: "team-1",
            sport_id: "sport-rugby-league",
            slug: "wigan-warriors",
            name: "Wigan Warriors",
            short_name: "Wigan",
            display_name: "Wigan Warriors RLFC",
            country_code: "GB",
            legacy_id: "123",
            is_active: 1,
          },
        ]),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<TeamsPage />);

    await screen.findByRole("cell", { name: "wigan-warriors" });
    fireEvent.click(screen.getByRole("button", { name: "Edit" }));
    fireEvent.change(screen.getByLabelText("Edit display name"), {
      target: { value: "Wigan Warriors RLFC" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Save" }));

    expect(await screen.findByText("Team updated.")).toBeTruthy();
    expect(
      await screen.findByRole("cell", { name: "Wigan Warriors RLFC" }),
    ).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/teams/team-1",
      expect.objectContaining({
        method: "PATCH",
        body: JSON.stringify({
          sportId: "sport-rugby-league",
          slug: "wigan-warriors",
          name: "Wigan Warriors",
          shortName: "Wigan",
          displayName: "Wigan Warriors RLFC",
          countryCode: "GB",
          legacyId: "123",
        }),
      }),
    );
  });

  it("archives a team after confirmation", async () => {
    const confirmMock = vi.spyOn(window, "confirm").mockReturnValue(true);
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(
        teamListResponse([
          {
            id: "team-1",
            sport_id: "sport-rugby-league",
            slug: "wigan-warriors",
            name: "Wigan Warriors",
            country_code: "GB",
            is_active: 1,
          },
        ]),
      )
      .mockResolvedValueOnce(jsonResponse({}))
      .mockResolvedValueOnce(teamListResponse([]));
    vi.stubGlobal("fetch", fetchMock);

    render(<TeamsPage />);

    await screen.findByRole("cell", { name: "Wigan Warriors" });
    fireEvent.click(screen.getByRole("button", { name: "Archive" }));

    expect(await screen.findByText("Team archived.")).toBeTruthy();
    expect(await screen.findByText("No teams found")).toBeTruthy();
    expect(confirmMock).toHaveBeenCalledWith("Archive Wigan Warriors?");
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/teams/team-1/archive",
      expect.objectContaining({ method: "POST" }),
    );
  });

  it("shows API errors from write actions", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(teamListResponse([]))
      .mockResolvedValueOnce(
        jsonResponse(
          {
            error: {
              code: "duplicate_slug",
              message: "Team slug already exists.",
            },
          },
          409,
        ),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<TeamsPage />);

    await screen.findByText("No teams found");
    fireEvent.change(screen.getByLabelText("Sport ID"), {
      target: { value: "sport-rugby-league" },
    });
    fireEvent.change(screen.getByLabelText("Slug"), {
      target: { value: "wigan-warriors" },
    });
    fireEvent.change(screen.getByLabelText("Name"), {
      target: { value: "Wigan Warriors" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Create team" }));

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Team slug already exists.",
    );
  });

  it("sends the configured bearer token when present", async () => {
    setAccessTokenProvider(() => "token-123");
    const fetchMock = vi.fn().mockResolvedValue(teamListResponse([]));
    vi.stubGlobal("fetch", fetchMock);

    render(<TeamsPage />);

    await waitFor(() => expect(fetchMock).toHaveBeenCalled());
    const [, init] = fetchMock.mock.calls[0] as [string, RequestInit];
    expect(init.headers).toBeInstanceOf(Headers);
    expect((init.headers as Headers).get("Authorization")).toBe(
      "Bearer token-123",
    );
  });
});
