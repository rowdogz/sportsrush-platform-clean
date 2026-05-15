import "@testing-library/jest-dom/vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { setAccessTokenProvider } from "../lib/apiClient";
import { SeasonsPage } from "./SeasonsPage";

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

function seasonListResponse(
  data: readonly Record<string, unknown>[],
): Response {
  return jsonResponse({
    data,
    meta: { page: 1, limit: 50, total: data.length, hasMore: false },
  });
}

function season(overrides: Record<string, unknown> = {}) {
  return {
    id: "season-1",
    competition_id: "competition-1",
    slug: "2026",
    name: "2026 Season",
    starts_on: "2026-01-01",
    ends_on: "2026-12-31",
    is_active: 1,
    legacy_id: null,
    ...overrides,
  };
}

describe("SeasonsPage", () => {
  afterEach(() => {
    vi.restoreAllMocks();
    setAccessTokenProvider(() => null);
    window.localStorage.clear();
  });

  it("shows loading and then renders seasons", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(competitionListResponse())
      .mockResolvedValueOnce(seasonListResponse([season()]));
    vi.stubGlobal("fetch", fetchMock);

    render(<SeasonsPage />);

    expect(screen.getByRole("status")).toHaveTextContent("Loading seasons…");
    expect(
      await screen.findByRole("cell", { name: "2026 Season" }),
    ).toBeTruthy();
    expect(screen.getByRole("cell", { name: "Super League" })).toBeTruthy();
    expect(screen.getByRole("cell", { name: "active" })).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/seasons?page=1&limit=50",
      expect.objectContaining({ headers: expect.any(Headers) }),
    );
  });

  it("renders an empty state", async () => {
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockResolvedValueOnce(competitionListResponse())
        .mockResolvedValueOnce(seasonListResponse([])),
    );

    render(<SeasonsPage />);

    expect(await screen.findByText("No seasons found")).toBeTruthy();
    expect(
      screen.getByText("Seasons will appear here after they are added."),
    ).toBeTruthy();
  });

  it("creates a season and refreshes the list", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(competitionListResponse())
      .mockResolvedValueOnce(seasonListResponse([]))
      .mockResolvedValueOnce(jsonResponse({ data: season() }, 201))
      .mockResolvedValueOnce(seasonListResponse([season()]));
    vi.stubGlobal("fetch", fetchMock);

    render(<SeasonsPage />);

    await screen.findByText("No seasons found");
    fireEvent.change(screen.getByLabelText("Competition"), {
      target: { value: "competition-1" },
    });
    fireEvent.change(screen.getByLabelText("Slug"), {
      target: { value: "2026" },
    });
    fireEvent.change(screen.getByLabelText("Name"), {
      target: { value: "2026 Season" },
    });
    fireEvent.change(screen.getByLabelText("Starts on"), {
      target: { value: "2026-01-01" },
    });
    fireEvent.change(screen.getByLabelText("Ends on"), {
      target: { value: "2026-12-31" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Create season" }));

    expect(await screen.findByText("Season created.")).toBeTruthy();
    expect(
      await screen.findByRole("cell", { name: "2026 Season" }),
    ).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      3,
      "/v1/admin/seasons",
      expect.objectContaining({
        method: "POST",
        body: JSON.stringify({
          competitionId: "competition-1",
          slug: "2026",
          name: "2026 Season",
          startsOn: "2026-01-01",
          endsOn: "2026-12-31",
          legacyId: null,
          isActive: true,
        }),
      }),
    );
  });

  it("edits a season and refreshes the list", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(competitionListResponse())
      .mockResolvedValueOnce(seasonListResponse([season()]))
      .mockResolvedValueOnce(
        jsonResponse({ data: season({ name: "2026 Regular Season" }) }),
      )
      .mockResolvedValueOnce(
        seasonListResponse([season({ name: "2026 Regular Season" })]),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<SeasonsPage />);

    await screen.findByRole("cell", { name: "2026 Season" });
    fireEvent.click(screen.getByRole("button", { name: "Edit" }));
    fireEvent.change(screen.getByLabelText("Edit name"), {
      target: { value: "2026 Regular Season" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Save" }));

    expect(await screen.findByText("Season updated.")).toBeTruthy();
    expect(
      await screen.findByRole("cell", { name: "2026 Regular Season" }),
    ).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      3,
      "/v1/admin/seasons/season-1",
      expect.objectContaining({
        method: "PATCH",
        body: expect.stringContaining("2026 Regular Season"),
      }),
    );
  });

  it("activates a season and refreshes the list", async () => {
    const inactiveSeason = season({ is_active: 0 });
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(competitionListResponse())
      .mockResolvedValueOnce(seasonListResponse([inactiveSeason]))
      .mockResolvedValueOnce(jsonResponse({ data: season({ is_active: 1 }) }))
      .mockResolvedValueOnce(seasonListResponse([season({ is_active: 1 })]));
    vi.stubGlobal("fetch", fetchMock);

    render(<SeasonsPage />);

    await screen.findByRole("cell", { name: "inactive" });
    fireEvent.click(screen.getByRole("button", { name: "Activate" }));

    expect(await screen.findByText("Season activated.")).toBeTruthy();
    expect(await screen.findByRole("cell", { name: "active" })).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      3,
      "/v1/admin/seasons/season-1/activate",
      expect.objectContaining({
        method: "POST",
        body: JSON.stringify({ competitionId: "competition-1" }),
      }),
    );
  });

  it("filters seasons", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(competitionListResponse())
      .mockResolvedValueOnce(seasonListResponse([]))
      .mockResolvedValueOnce(seasonListResponse([season()]));
    vi.stubGlobal("fetch", fetchMock);

    render(<SeasonsPage />);

    await screen.findByText("No seasons found");
    fireEvent.change(screen.getByLabelText("Filter competition"), {
      target: { value: "competition-1" },
    });
    fireEvent.change(screen.getByLabelText("Search"), {
      target: { value: "2026" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Apply filters" }));

    expect(
      await screen.findByRole("cell", { name: "2026 Season" }),
    ).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      3,
      "/v1/admin/seasons?page=1&limit=50&competitionId=competition-1&search=2026",
      expect.any(Object),
    );
  });

  it("renders an API error state", async () => {
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockResolvedValueOnce(competitionListResponse())
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
        ),
    );

    render(<SeasonsPage />);

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Unable to load seasons",
    );
    expect(screen.getByText("Admin access is required.")).toBeTruthy();
  });

  it("validates required season fields", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(competitionListResponse())
      .mockResolvedValueOnce(seasonListResponse([]));
    vi.stubGlobal("fetch", fetchMock);

    render(<SeasonsPage />);

    await screen.findByText("No seasons found");
    fireEvent.click(screen.getByRole("button", { name: "Create season" }));

    expect(screen.getByRole("alert")).toHaveTextContent(
      "Competition, slug, and name are required.",
    );
    expect(fetchMock).toHaveBeenCalledTimes(2);
  });
});
