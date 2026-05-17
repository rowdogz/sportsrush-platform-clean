import "@testing-library/jest-dom/vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { setAccessTokenProvider } from "../lib/apiClient";
import { PrivateLeaguesPage } from "./PrivateLeaguesPage";

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}

function listResponse(data: readonly Record<string, unknown>[]): Response {
  return jsonResponse({
    data,
    meta: { page: 1, limit: 50, total: data.length, hasMore: false },
  });
}

function league(overrides: Record<string, unknown> = {}) {
  return {
    id: "league-1",
    slug: "test-league",
    name: "Test League",
    description: "A private league",
    logoUrl: null,
    bannerUrl: null,
    inviteCode: "ABC123DEF4",
    ownerUserId: "admin-user",
    isArchived: false,
    memberCount: 2,
    competitionCount: 1,
    createdAt: "2026-01-01T00:00:00.000Z",
    updatedAt: "2026-01-01T00:00:00.000Z",
    archivedAt: null,
    ...overrides,
  };
}

describe("PrivateLeaguesPage", () => {
  afterEach(() => {
    vi.restoreAllMocks();
    setAccessTokenProvider(() => null);
    window.localStorage.clear();
  });

  it("shows loading and then renders private leagues", async () => {
    const fetchMock = vi.fn().mockResolvedValueOnce(listResponse([league()]));
    vi.stubGlobal("fetch", fetchMock);

    render(<PrivateLeaguesPage />);

    expect(screen.getByRole("status")).toHaveTextContent(
      "Loading private leagues…",
    );
    expect(
      await screen.findByRole("cell", { name: "ABC123DEF4" }),
    ).toBeTruthy();
    expect(screen.getByRole("cell", { name: "Active" })).toBeTruthy();
  });

  it("renders empty and error states", async () => {
    vi.stubGlobal("fetch", vi.fn().mockResolvedValueOnce(listResponse([])));
    render(<PrivateLeaguesPage />);
    expect(await screen.findByText("No private leagues found")).toBeTruthy();

    vi.restoreAllMocks();
    vi.stubGlobal("fetch", vi.fn().mockRejectedValueOnce(new Error("Boom")));
    render(<PrivateLeaguesPage />);
    expect(
      await screen.findByText("Unable to load private leagues"),
    ).toBeTruthy();
  });

  it("creates a private league and refreshes the list", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(listResponse([]))
      .mockResolvedValueOnce(jsonResponse({ data: league() }, 201))
      .mockResolvedValueOnce(listResponse([league()]));
    vi.stubGlobal("fetch", fetchMock);

    render(<PrivateLeaguesPage />);

    await screen.findByText("No private leagues found");
    fireEvent.change(screen.getByLabelText("Slug"), {
      target: { value: "test-league" },
    });
    fireEvent.change(screen.getByLabelText("Name"), {
      target: { value: "Test League" },
    });
    fireEvent.change(screen.getByLabelText("Competition IDs"), {
      target: { value: "competition-1" },
    });
    fireEvent.click(
      screen.getByRole("button", { name: "Create private league" }),
    );

    expect(await screen.findByText("Private league created.")).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/private-leagues",
      expect.objectContaining({ method: "POST" }),
    );
  });

  it("edits and archives a private league", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(listResponse([league()]))
      .mockResolvedValueOnce(
        jsonResponse({ data: league({ name: "Updated" }) }),
      )
      .mockResolvedValueOnce(listResponse([league({ name: "Updated" })]))
      .mockResolvedValueOnce(
        jsonResponse({ data: league({ isArchived: true }) }),
      )
      .mockResolvedValueOnce(listResponse([league({ isArchived: true })]));
    vi.stubGlobal("fetch", fetchMock);
    vi.spyOn(window, "confirm").mockReturnValue(true);

    render(<PrivateLeaguesPage />);

    await screen.findByRole("cell", { name: "ABC123DEF4" });
    fireEvent.click(screen.getByRole("button", { name: "Edit" }));
    fireEvent.change(screen.getAllByLabelText("Name")[1]!, {
      target: { value: "Updated" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Save" }));
    expect(await screen.findByText("Private league updated.")).toBeTruthy();

    fireEvent.click(screen.getByRole("button", { name: "Archive" }));
    expect(await screen.findByText("Private league archived.")).toBeTruthy();
  });
});
