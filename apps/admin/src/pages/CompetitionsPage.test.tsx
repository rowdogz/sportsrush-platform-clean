import "@testing-library/jest-dom/vitest";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { setAccessTokenProvider } from "../lib/apiClient";
import { CompetitionsPage } from "./CompetitionsPage";

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}

function competitionListResponse(
  data: readonly Record<string, unknown>[],
): Response {
  return jsonResponse({
    data,
    meta: { page: 1, limit: 50, total: data.length, hasMore: false },
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
      competitionListResponse([
        {
          id: "competition-1",
          name: "Super League",
          slug: "super-league",
          sport_id: "sport-rugby-league",
          short_name: "SL",
          country_code: "GB",
          legacy_id: 123,
          is_active: 1,
        },
      ]),
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
    expect(screen.getByRole("cell", { name: "SL" })).toBeTruthy();
    expect(screen.getByRole("cell", { name: "GB" })).toBeTruthy();
    expect(screen.getByRole("cell", { name: "123" })).toBeTruthy();
    expect(screen.getByText("Active")).toBeTruthy();
    expect(fetchMock).toHaveBeenCalledWith(
      "/v1/admin/competitions?page=1&limit=50",
      expect.objectContaining({
        headers: expect.any(Headers),
      }),
    );
  });

  it("creates a competition and refreshes the list", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(competitionListResponse([]))
      .mockResolvedValueOnce(
        jsonResponse({
          id: "competition-2",
          name: "Challenge Cup",
          slug: "challenge-cup",
          sportId: "sport-rugby-league",
          shortName: "CC",
          countryCode: "GB",
          legacyId: "456",
          isActive: true,
        }),
      )
      .mockResolvedValueOnce(
        competitionListResponse([
          {
            id: "competition-2",
            name: "Challenge Cup",
            slug: "challenge-cup",
            sport_id: "sport-rugby-league",
            short_name: "CC",
            country_code: "GB",
            legacy_id: "456",
            is_active: 1,
          },
        ]),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<CompetitionsPage />);

    await screen.findByText("No competitions found");
    fireEvent.change(screen.getByLabelText("Sport ID"), {
      target: { value: "sport-rugby-league" },
    });
    fireEvent.change(screen.getByLabelText("Slug"), {
      target: { value: "challenge-cup" },
    });
    fireEvent.change(screen.getByLabelText("Name"), {
      target: { value: "Challenge Cup" },
    });
    fireEvent.change(screen.getByLabelText("Short name"), {
      target: { value: "CC" },
    });
    fireEvent.change(screen.getByLabelText("Country code"), {
      target: { value: "GB" },
    });
    fireEvent.change(screen.getByLabelText("Legacy ID"), {
      target: { value: "456" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Create competition" }));

    expect(await screen.findByText("Competition created.")).toBeTruthy();
    expect(
      await screen.findByRole("cell", { name: "Challenge Cup" }),
    ).toBeTruthy();
    expect(screen.getByLabelText("Name")).toHaveValue("");
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/competitions",
      expect.objectContaining({
        method: "POST",
        body: JSON.stringify({
          sportId: "sport-rugby-league",
          slug: "challenge-cup",
          name: "Challenge Cup",
          shortName: "CC",
          countryCode: "GB",
          legacyId: "456",
        }),
      }),
    );
  });

  it("validates required create fields", async () => {
    const fetchMock = vi.fn().mockResolvedValue(competitionListResponse([]));
    vi.stubGlobal("fetch", fetchMock);

    render(<CompetitionsPage />);

    await screen.findByText("No competitions found");
    fireEvent.click(screen.getByRole("button", { name: "Create competition" }));

    expect(screen.getByRole("alert")).toHaveTextContent(
      "Sport ID, slug, and name are required.",
    );
    expect(fetchMock).toHaveBeenCalledTimes(1);
  });

  it("edits a competition and refreshes the list", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(
        competitionListResponse([
          {
            id: "competition-1",
            name: "Super League",
            slug: "super-league",
            sport_id: "sport-rugby-league",
            short_name: "SL",
            country_code: "GB",
            legacy_id: "123",
            is_active: 1,
          },
        ]),
      )
      .mockResolvedValueOnce(
        jsonResponse({
          id: "competition-1",
          name: "Betfred Super League",
          slug: "super-league",
          sportId: "sport-rugby-league",
          shortName: "BSL",
          countryCode: "GB",
          legacyId: "123",
          isActive: true,
        }),
      )
      .mockResolvedValueOnce(
        competitionListResponse([
          {
            id: "competition-1",
            name: "Betfred Super League",
            slug: "super-league",
            sport_id: "sport-rugby-league",
            short_name: "BSL",
            country_code: "GB",
            legacy_id: "123",
            is_active: 1,
          },
        ]),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<CompetitionsPage />);

    await screen.findByRole("cell", { name: "Super League" });
    fireEvent.click(screen.getByRole("button", { name: "Edit" }));
    fireEvent.change(screen.getByLabelText("Edit name"), {
      target: { value: "Betfred Super League" },
    });
    fireEvent.change(screen.getByLabelText("Edit short name"), {
      target: { value: "BSL" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Save" }));

    expect(await screen.findByText("Competition updated.")).toBeTruthy();
    expect(
      await screen.findByRole("cell", { name: "Betfred Super League" }),
    ).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/competitions/competition-1",
      expect.objectContaining({
        method: "PATCH",
        body: JSON.stringify({
          sportId: "sport-rugby-league",
          slug: "super-league",
          name: "Betfred Super League",
          shortName: "BSL",
          countryCode: "GB",
          legacyId: "123",
        }),
      }),
    );
  });

  it("archives a competition after confirmation", async () => {
    const confirmMock = vi.spyOn(window, "confirm").mockReturnValue(true);
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(
        competitionListResponse([
          {
            id: "competition-1",
            name: "Super League",
            slug: "super-league",
            sport_id: "sport-rugby-league",
            country_code: "GB",
            is_active: 1,
          },
        ]),
      )
      .mockResolvedValueOnce(jsonResponse({}))
      .mockResolvedValueOnce(competitionListResponse([]));
    vi.stubGlobal("fetch", fetchMock);

    render(<CompetitionsPage />);

    await screen.findByRole("cell", { name: "Super League" });
    fireEvent.click(screen.getByRole("button", { name: "Archive" }));

    expect(await screen.findByText("Competition archived.")).toBeTruthy();
    expect(await screen.findByText("No competitions found")).toBeTruthy();
    expect(confirmMock).toHaveBeenCalledWith("Archive Super League?");
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/competitions/competition-1/archive",
      expect.objectContaining({ method: "POST" }),
    );
  });

  it("does not archive when confirmation is cancelled", async () => {
    vi.spyOn(window, "confirm").mockReturnValue(false);
    const fetchMock = vi.fn().mockResolvedValue(
      competitionListResponse([
        {
          id: "competition-1",
          name: "Super League",
          slug: "super-league",
          sport_id: "sport-rugby-league",
          country_code: "GB",
          is_active: 1,
        },
      ]),
    );
    vi.stubGlobal("fetch", fetchMock);

    render(<CompetitionsPage />);

    await screen.findByRole("cell", { name: "Super League" });
    fireEvent.click(screen.getByRole("button", { name: "Archive" }));

    expect(fetchMock).toHaveBeenCalledTimes(1);
  });

  it("shows API errors from write actions", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(competitionListResponse([]))
      .mockResolvedValueOnce(
        jsonResponse(
          {
            error: {
              code: "duplicate_slug",
              message: "Competition slug already exists.",
            },
          },
          409,
        ),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<CompetitionsPage />);

    await screen.findByText("No competitions found");
    fireEvent.change(screen.getByLabelText("Sport ID"), {
      target: { value: "sport-rugby-league" },
    });
    fireEvent.change(screen.getByLabelText("Slug"), {
      target: { value: "super-league" },
    });
    fireEvent.change(screen.getByLabelText("Name"), {
      target: { value: "Super League" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Create competition" }));

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Competition slug already exists.",
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
