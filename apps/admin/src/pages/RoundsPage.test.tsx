import "@testing-library/jest-dom/vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { setAccessTokenProvider } from "../lib/apiClient";
import { RoundsPage } from "./RoundsPage";

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}

function roundListResponse(data: readonly Record<string, unknown>[]): Response {
  return jsonResponse({ data });
}

describe("RoundsPage", () => {
  afterEach(() => {
    vi.restoreAllMocks();
    setAccessTokenProvider(() => null);
    window.localStorage.clear();
  });

  it("shows loading and then renders rounds", async () => {
    const fetchMock = vi.fn().mockResolvedValue(
      roundListResponse([
        {
          id: "round-1",
          season_id: "season-2026",
          round: "QF",
          round_name: "Quarter Final",
          display_order: 10,
          starts_at: "2026-03-01T00:00:00.000Z",
          ends_at: "2026-03-07T23:59:59.000Z",
          legacy_id: "legacy-1",
        },
      ]),
    );
    vi.stubGlobal("fetch", fetchMock);

    render(<RoundsPage />);

    expect(screen.getByRole("status")).toHaveTextContent("Loading rounds…");
    expect(await screen.findByRole("cell", { name: "QF" })).toBeTruthy();
    expect(screen.getByRole("cell", { name: "Quarter Final" })).toBeTruthy();
    expect(screen.getByRole("cell", { name: "season-2026" })).toBeTruthy();
    expect(screen.getByRole("cell", { name: "10" })).toBeTruthy();
    expect(
      screen.getByRole("cell", { name: "2026-03-01T00:00:00.000Z" }),
    ).toBeTruthy();
    expect(
      screen.getByRole("cell", { name: "2026-03-07T23:59:59.000Z" }),
    ).toBeTruthy();
    expect(fetchMock).toHaveBeenCalledWith(
      "/v1/admin/rounds?seasonId=season-current",
      expect.objectContaining({
        headers: expect.any(Headers),
      }),
    );
  });

  it("renders an empty state", async () => {
    vi.stubGlobal("fetch", vi.fn().mockResolvedValue(roundListResponse([])));

    render(<RoundsPage />);

    expect(await screen.findByText("No rounds found")).toBeTruthy();
    expect(
      screen.getByText("Rounds will appear here after they are added."),
    ).toBeTruthy();
  });

  it("renders an API error state", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(
        jsonResponse(
          {
            error: {
              code: "validation_error",
              message: "Invalid query parameters",
            },
          },
          400,
        ),
      ),
    );

    render(<RoundsPage />);

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Unable to load rounds",
    );
    expect(screen.getByText("Invalid query parameters")).toBeTruthy();
  });

  it("applies the required season filter", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(roundListResponse([]))
      .mockResolvedValueOnce(roundListResponse([]));
    vi.stubGlobal("fetch", fetchMock);

    render(<RoundsPage />);

    await screen.findByText("No rounds found");
    fireEvent.change(screen.getByLabelText("Filter season ID"), {
      target: { value: "season-2027" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Apply filters" }));

    expect(await screen.findByText("No rounds found")).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/rounds?seasonId=season-2027",
      expect.any(Object),
    );
  });

  it("creates a round and refreshes the list", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(roundListResponse([]))
      .mockResolvedValueOnce(
        jsonResponse({
          data: {
            id: "round-2",
            season_id: "season-current",
            round: "SF",
            round_name: "Semi Final",
            display_order: 20,
            starts_at: null,
            ends_at: null,
            legacy_id: "legacy-2",
          },
        }),
      )
      .mockResolvedValueOnce(
        roundListResponse([
          {
            id: "round-2",
            season_id: "season-current",
            round: "SF",
            round_name: "Semi Final",
            display_order: 20,
            starts_at: null,
            ends_at: null,
            legacy_id: "legacy-2",
          },
        ]),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<RoundsPage />);

    await screen.findByText("No rounds found");
    fireEvent.change(screen.getByLabelText("Round"), {
      target: { value: "SF" },
    });
    fireEvent.change(screen.getByLabelText("Round name"), {
      target: { value: "Semi Final" },
    });
    fireEvent.change(screen.getByLabelText("Display order"), {
      target: { value: "20" },
    });
    fireEvent.change(screen.getByLabelText("Legacy ID"), {
      target: { value: "legacy-2" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Create round" }));

    expect(await screen.findByText("Round created.")).toBeTruthy();
    expect(await screen.findByRole("cell", { name: "SF" })).toBeTruthy();
    expect(screen.getByLabelText("Round")).toHaveValue("");
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/rounds",
      expect.objectContaining({
        method: "POST",
        body: JSON.stringify({
          seasonId: "season-current",
          round: "SF",
          roundName: "Semi Final",
          displayOrder: 20,
          startsAt: null,
          endsAt: null,
          legacyId: "legacy-2",
        }),
      }),
    );
  });

  it("validates required create fields", async () => {
    const fetchMock = vi.fn().mockResolvedValue(roundListResponse([]));
    vi.stubGlobal("fetch", fetchMock);

    render(<RoundsPage />);

    await screen.findByText("No rounds found");
    fireEvent.click(screen.getByRole("button", { name: "Create round" }));

    expect(screen.getByRole("alert")).toHaveTextContent(
      "Season ID, round, round name, and display order are required.",
    );
    expect(fetchMock).toHaveBeenCalledTimes(1);
  });

  it("validates display order as an integer", async () => {
    const fetchMock = vi.fn().mockResolvedValue(roundListResponse([]));
    vi.stubGlobal("fetch", fetchMock);

    render(<RoundsPage />);

    await screen.findByText("No rounds found");
    fireEvent.change(screen.getByLabelText("Round"), {
      target: { value: "SF" },
    });
    fireEvent.change(screen.getByLabelText("Round name"), {
      target: { value: "Semi Final" },
    });
    fireEvent.change(screen.getByLabelText("Display order"), {
      target: { value: "20.5" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Create round" }));

    expect(screen.getByRole("alert")).toHaveTextContent(
      "Display order must be an integer.",
    );
    expect(fetchMock).toHaveBeenCalledTimes(1);
  });

  it("edits a round and refreshes the list", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(
        roundListResponse([
          {
            id: "round-1",
            season_id: "season-current",
            round: "QF",
            round_name: "Quarter Final",
            display_order: 10,
            starts_at: null,
            ends_at: null,
            legacy_id: null,
          },
        ]),
      )
      .mockResolvedValueOnce(
        jsonResponse({
          data: {
            id: "round-1",
            season_id: "season-current",
            round: "QF",
            round_name: "Updated Quarter Final",
            display_order: 15,
            starts_at: null,
            ends_at: null,
            legacy_id: null,
          },
        }),
      )
      .mockResolvedValueOnce(
        roundListResponse([
          {
            id: "round-1",
            season_id: "season-current",
            round: "QF",
            round_name: "Updated Quarter Final",
            display_order: 15,
            starts_at: null,
            ends_at: null,
            legacy_id: null,
          },
        ]),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<RoundsPage />);

    await screen.findByRole("cell", { name: "Quarter Final" });
    fireEvent.click(screen.getByRole("button", { name: "Edit" }));
    fireEvent.change(screen.getByLabelText("Edit round name"), {
      target: { value: "Updated Quarter Final" },
    });
    fireEvent.change(screen.getByLabelText("Edit display order"), {
      target: { value: "15" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Save" }));

    expect(await screen.findByText("Round updated.")).toBeTruthy();
    expect(
      await screen.findByRole("cell", { name: "Updated Quarter Final" }),
    ).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/rounds/round-1",
      expect.objectContaining({
        method: "PATCH",
        body: JSON.stringify({
          seasonId: "season-current",
          round: "QF",
          roundName: "Updated Quarter Final",
          displayOrder: 15,
          startsAt: null,
          endsAt: null,
          legacyId: null,
        }),
      }),
    );
  });

  it("shows API errors from writes", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(roundListResponse([]))
      .mockResolvedValueOnce(
        jsonResponse(
          {
            error: {
              code: "validation_error",
              message: "Invalid request body",
            },
          },
          400,
        ),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<RoundsPage />);

    await screen.findByText("No rounds found");
    fireEvent.change(screen.getByLabelText("Round"), {
      target: { value: "SF" },
    });
    fireEvent.change(screen.getByLabelText("Round name"), {
      target: { value: "Semi Final" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Create round" }));

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Invalid request body",
    );
  });
});
