import "@testing-library/jest-dom/vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { setAccessTokenProvider } from "../lib/apiClient";
import { TeamAliasesPage } from "./TeamAliasesPage";

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}

function aliasListResponse(data: readonly Record<string, unknown>[]): Response {
  return jsonResponse({ data });
}

describe("TeamAliasesPage", () => {
  afterEach(() => {
    vi.restoreAllMocks();
    setAccessTokenProvider(() => null);
    window.localStorage.clear();
  });

  it("shows loading and then renders team aliases", async () => {
    const fetchMock = vi.fn().mockResolvedValue(
      aliasListResponse([
        {
          id: "alias-1",
          team_id: "team-wigan",
          sport_id: "sport-rugby-league",
          alias: "Wigan Warriors",
          normalized_alias: "wigan warriors",
          source: "bbc sport",
          priority: 100,
          is_active: 1,
          legacy_id: "legacy-1",
        },
      ]),
    );
    vi.stubGlobal("fetch", fetchMock);

    render(<TeamAliasesPage />);

    expect(screen.getByRole("status")).toHaveTextContent(
      "Loading team aliases…",
    );
    expect(
      await screen.findByRole("cell", { name: "Wigan Warriors" }),
    ).toBeTruthy();
    expect(screen.getByRole("cell", { name: "wigan warriors" })).toBeTruthy();
    expect(screen.getByRole("cell", { name: "bbc sport" })).toBeTruthy();
    expect(screen.getByRole("cell", { name: "team-wigan" })).toBeTruthy();
    expect(
      screen.getByRole("cell", { name: "sport-rugby-league" }),
    ).toBeTruthy();
    expect(screen.getByRole("cell", { name: "Active" })).toBeTruthy();
    expect(fetchMock).toHaveBeenCalledWith(
      "/v1/admin/team-aliases?sportId=sport-rugby-league",
      expect.objectContaining({
        headers: expect.any(Headers),
      }),
    );
  });

  it("renders an empty state", async () => {
    vi.stubGlobal("fetch", vi.fn().mockResolvedValue(aliasListResponse([])));

    render(<TeamAliasesPage />);

    expect(await screen.findByText("No team aliases found")).toBeTruthy();
    expect(
      screen.getByText("Team aliases will appear here after they are added."),
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

    render(<TeamAliasesPage />);

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Unable to load team aliases",
    );
    expect(screen.getByText("Admin access is required.")).toBeTruthy();
  });

  it("applies supported source and alias filters", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(aliasListResponse([]))
      .mockResolvedValueOnce(aliasListResponse([]));
    vi.stubGlobal("fetch", fetchMock);

    render(<TeamAliasesPage />);

    await screen.findByText("No team aliases found");
    fireEvent.change(screen.getByLabelText("Filter source"), {
      target: { value: "BBC Sport" },
    });
    fireEvent.change(screen.getByLabelText("Filter alias"), {
      target: { value: "Wigan Warriors" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Apply filters" }));

    expect(await screen.findByText("No team aliases found")).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/team-aliases?sportId=sport-rugby-league&source=BBC+Sport&alias=Wigan+Warriors",
      expect.any(Object),
    );
  });

  it("creates a team alias and refreshes the list", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(aliasListResponse([]))
      .mockResolvedValueOnce(
        jsonResponse({
          data: {
            id: "alias-2",
            team_id: "team-saints",
            sport_id: "sport-rugby-league",
            alias: "Saints",
            normalized_alias: "saints",
            source: "manual",
            priority: 90,
            is_active: 1,
            legacy_id: "789",
          },
        }),
      )
      .mockResolvedValueOnce(
        aliasListResponse([
          {
            id: "alias-2",
            team_id: "team-saints",
            sport_id: "sport-rugby-league",
            alias: "Saints",
            normalized_alias: "saints",
            source: "manual",
            priority: 90,
            is_active: 1,
            legacy_id: "789",
          },
        ]),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<TeamAliasesPage />);

    await screen.findByText("No team aliases found");
    fireEvent.change(screen.getByLabelText("Team ID"), {
      target: { value: "team-saints" },
    });
    fireEvent.change(screen.getByLabelText("Alias"), {
      target: { value: "Saints" },
    });
    fireEvent.change(screen.getByLabelText("Priority"), {
      target: { value: "90" },
    });
    fireEvent.change(screen.getByLabelText("Legacy ID"), {
      target: { value: "789" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Create team alias" }));

    expect(await screen.findByText("Team alias created.")).toBeTruthy();
    expect(await screen.findByRole("cell", { name: "Saints" })).toBeTruthy();
    expect(screen.getByLabelText("Team ID")).toHaveValue("");
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/team-aliases",
      expect.objectContaining({
        method: "POST",
        body: JSON.stringify({
          teamId: "team-saints",
          sportId: "sport-rugby-league",
          alias: "Saints",
          normalizedAlias: null,
          source: "manual",
          priority: 90,
          isActive: true,
          legacyId: "789",
        }),
      }),
    );
  });

  it("validates required create fields", async () => {
    const fetchMock = vi.fn().mockResolvedValue(aliasListResponse([]));
    vi.stubGlobal("fetch", fetchMock);

    render(<TeamAliasesPage />);

    await screen.findByText("No team aliases found");
    fireEvent.click(screen.getByRole("button", { name: "Create team alias" }));

    expect(screen.getByRole("alert")).toHaveTextContent(
      "Team ID, sport ID, and alias are required.",
    );
    expect(fetchMock).toHaveBeenCalledTimes(1);
  });

  it("edits a team alias and refreshes the list", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(
        aliasListResponse([
          {
            id: "alias-1",
            team_id: "team-wigan",
            sport_id: "sport-rugby-league",
            alias: "Wigan Warriors",
            normalized_alias: "wigan warriors",
            source: "bbc sport",
            priority: 100,
            is_active: 1,
            legacy_id: null,
          },
        ]),
      )
      .mockResolvedValueOnce(
        jsonResponse({
          data: {
            id: "alias-1",
            team_id: "team-wigan",
            sport_id: "sport-rugby-league",
            alias: "Wigan RL",
            normalized_alias: "wigan rl",
            source: "sportradar",
            priority: 80,
            is_active: 1,
            legacy_id: null,
          },
        }),
      )
      .mockResolvedValueOnce(
        aliasListResponse([
          {
            id: "alias-1",
            team_id: "team-wigan",
            sport_id: "sport-rugby-league",
            alias: "Wigan RL",
            normalized_alias: "wigan rl",
            source: "sportradar",
            priority: 80,
            is_active: 1,
            legacy_id: null,
          },
        ]),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<TeamAliasesPage />);

    await screen.findByRole("cell", { name: "Wigan Warriors" });
    fireEvent.click(screen.getByRole("button", { name: "Edit" }));
    fireEvent.change(screen.getByLabelText("Edit alias"), {
      target: { value: "Wigan RL" },
    });
    fireEvent.change(screen.getByLabelText("Edit source"), {
      target: { value: "Sportradar" },
    });
    fireEvent.change(screen.getByLabelText("Edit priority"), {
      target: { value: "80" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Save" }));

    expect(await screen.findByText("Team alias updated.")).toBeTruthy();
    expect(await screen.findByRole("cell", { name: "Wigan RL" })).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/team-aliases/alias-1",
      expect.objectContaining({
        method: "PATCH",
        body: JSON.stringify({
          teamId: "team-wigan",
          sportId: "sport-rugby-league",
          alias: "Wigan RL",
          normalizedAlias: "wigan warriors",
          source: "Sportradar",
          priority: 80,
          isActive: true,
          legacyId: null,
        }),
      }),
    );
  });

  it("deletes a team alias after confirmation", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(
        aliasListResponse([
          {
            id: "alias-1",
            team_id: "team-wigan",
            sport_id: "sport-rugby-league",
            alias: "Wigan Warriors",
            normalized_alias: "wigan warriors",
            source: "bbc sport",
            priority: 100,
            is_active: 1,
            legacy_id: null,
          },
        ]),
      )
      .mockResolvedValueOnce(jsonResponse({ data: { deleted: true } }))
      .mockResolvedValueOnce(aliasListResponse([]));
    vi.stubGlobal("fetch", fetchMock);
    vi.spyOn(window, "confirm").mockReturnValue(true);

    render(<TeamAliasesPage />);

    await screen.findByRole("cell", { name: "Wigan Warriors" });
    fireEvent.click(screen.getByRole("button", { name: "Delete" }));

    expect(window.confirm).toHaveBeenCalledWith("Delete alias Wigan Warriors?");
    expect(await screen.findByText("Team alias deleted.")).toBeTruthy();
    expect(await screen.findByText("No team aliases found")).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/team-aliases/alias-1",
      expect.objectContaining({ method: "DELETE" }),
    );
  });

  it("sends the configured bearer token", async () => {
    setAccessTokenProvider(() => "access-token-123");
    const fetchMock = vi.fn().mockResolvedValue(aliasListResponse([]));
    vi.stubGlobal("fetch", fetchMock);

    render(<TeamAliasesPage />);

    await screen.findByText("No team aliases found");
    const headers = fetchMock.mock.calls[0]?.[1]?.headers as Headers;
    expect(headers.get("Authorization")).toBe("Bearer access-token-123");
  });
});
