import "@testing-library/jest-dom/vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { setAccessTokenProvider } from "../lib/apiClient";
import { AuditLogPage } from "./AuditLogPage";

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}

function auditListResponse(
  data: readonly Record<string, unknown>[],
  meta: Record<string, unknown> = {},
): Response {
  return jsonResponse({
    data,
    meta: { page: 1, limit: 50, total: data.length, hasMore: false, ...meta },
  });
}

function csvResponse(body: string, status = 200): Response {
  return new Response(body, {
    status,
    headers: {
      "Content-Type": "text/csv",
      "Content-Disposition":
        'attachment; filename="audit-events-2026-05-14.csv"',
    },
  });
}

function auditEvent(overrides: Record<string, unknown> = {}) {
  return {
    id: "audit-1",
    actorUserId: "admin-user",
    actorEmail: "admin@example.test",
    actorDisplayName: "Admin User",
    action: "user.role.update",
    entityType: "user",
    entityId: "user-1",
    summary: "user.role.update on user user-1",
    beforeMetadata: { role: "user" },
    afterMetadata: { role: "admin" },
    changes: { role: { before: "user", after: "admin" } },
    createdAt: "2026-05-14T13:00:00.000Z",
    correlationId: null,
    ...overrides,
  };
}

describe("AuditLogPage", () => {
  afterEach(() => {
    vi.restoreAllMocks();
    setAccessTokenProvider(() => null);
    window.localStorage.clear();
  });

  it("shows loading and then renders audit events", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(auditListResponse([auditEvent()]));
    vi.stubGlobal("fetch", fetchMock);

    render(<AuditLogPage />);

    expect(screen.getByRole("status")).toHaveTextContent(
      "Loading audit events…",
    );
    expect(
      await screen.findByRole("cell", {
        name: "Admin User admin@example.test",
      }),
    ).toBeTruthy();
    expect(screen.getByRole("cell", { name: "user.role.update" })).toBeTruthy();
    expect(screen.getByRole("cell", { name: "user user-1" })).toBeTruthy();
    expect(
      screen.getByRole("cell", { name: "user.role.update on user user-1" }),
    ).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      1,
      "/v1/admin/audit-events?page=1&limit=50",
      expect.objectContaining({ headers: expect.any(Headers) }),
    );
  });

  it("renders expandable event details", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValueOnce(
        auditListResponse([
          auditEvent({
            correlationId: "correlation-1",
            beforeMetadata: { profile: { role: "user" } },
            afterMetadata: { profile: { role: "admin" } },
            changes: {
              profile: {
                before: { role: "user" },
                after: { role: "admin" },
              },
            },
          }),
        ]),
      ),
    );

    render(<AuditLogPage />);

    fireEvent.click(await screen.findByText("View event details"));
    expect(screen.getByText("Timestamp")).toBeTruthy();
    expect(screen.getAllByText("Actor").length).toBeGreaterThan(1);
    expect(screen.getByText("Correlation ID")).toBeTruthy();
    expect(screen.getByText("correlation-1")).toBeTruthy();
    expect(screen.getAllByText(/"role":/).length).toBeGreaterThan(0);
    expect(screen.getAllByText(/"profile":/).length).toBeGreaterThan(0);
  });

  it("renders an empty state", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValueOnce(auditListResponse([])),
    );

    render(<AuditLogPage />);

    expect(await screen.findByText("No audit events found")).toBeTruthy();
    expect(
      screen.getByText(
        "Admin mutations will appear here after they are recorded.",
      ),
    ).toBeTruthy();
  });

  it("renders an API error state", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValueOnce(
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

    render(<AuditLogPage />);

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Unable to load audit events",
    );
    expect(screen.getByText("Admin access is required.")).toBeTruthy();
  });

  it("submits supported audit filters", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(auditListResponse([]))
      .mockResolvedValueOnce(
        auditListResponse([
          auditEvent({
            id: "audit-2",
            action: "team.update",
            entityType: "team",
            entityId: "team-1",
            summary: "team.update on team team-1",
          }),
        ]),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<AuditLogPage />);

    await screen.findByText("No audit events found");
    fireEvent.change(screen.getByLabelText("Actor user ID"), {
      target: { value: "admin-user" },
    });
    fireEvent.change(screen.getByLabelText("Entity type"), {
      target: { value: "team" },
    });
    fireEvent.change(screen.getByLabelText("Entity ID"), {
      target: { value: "team-1" },
    });
    fireEvent.change(screen.getByLabelText("Action"), {
      target: { value: "team.update" },
    });
    fireEvent.change(screen.getByLabelText("Date from"), {
      target: { value: "2026-05-14T12:00" },
    });
    fireEvent.change(screen.getByLabelText("Date to"), {
      target: { value: "2026-05-15T12:00" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Apply filters" }));

    expect(
      await screen.findByRole("cell", { name: "team.update" }),
    ).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/audit-events?page=1&limit=50&actorUserId=admin-user&entityType=team&entityId=team-1&action=team.update&dateFrom=2026-05-14T12%3A00&dateTo=2026-05-15T12%3A00",
      expect.any(Object),
    );
  });

  it("pages through audit events while preserving filters", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(
        auditListResponse([auditEvent()], {
          page: 1,
          limit: 50,
          total: 75,
          hasMore: true,
        }),
      )
      .mockResolvedValueOnce(
        auditListResponse(
          [
            auditEvent({
              id: "audit-2",
              action: "team.update",
              entityType: "team",
              entityId: "team-1",
              summary: "team.update on team team-1",
            }),
          ],
          { page: 1, limit: 50, total: 75, hasMore: true },
        ),
      )
      .mockResolvedValueOnce(
        auditListResponse(
          [
            auditEvent({
              id: "audit-3",
              action: "team.archive",
              entityType: "team",
              entityId: "team-1",
              summary: "team.archive on team team-1",
            }),
          ],
          { page: 2, limit: 50, total: 75, hasMore: false },
        ),
      )
      .mockResolvedValueOnce(
        auditListResponse(
          [
            auditEvent({
              id: "audit-4",
              action: "team.update",
              entityType: "team",
              entityId: "team-1",
              summary: "team.update on team team-1",
            }),
          ],
          { page: 1, limit: 50, total: 75, hasMore: true },
        ),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<AuditLogPage />);

    expect(await screen.findAllByText("Page 1")).toHaveLength(2);
    expect(
      screen.getAllByRole("button", { name: "Previous" })[0]!,
    ).toBeDisabled();
    expect(screen.getAllByRole("button", { name: "Next" })[0]!).toBeEnabled();

    fireEvent.change(screen.getByLabelText("Entity type"), {
      target: { value: "team" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Apply filters" }));
    expect(
      await screen.findByRole("cell", { name: "team.update" }),
    ).toBeTruthy();

    fireEvent.click(screen.getAllByRole("button", { name: "Next" })[0]!);
    expect(
      await screen.findByRole("cell", { name: "team.archive" }),
    ).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      3,
      "/v1/admin/audit-events?page=2&limit=50&entityType=team",
      expect.any(Object),
    );
    expect(screen.getAllByRole("button", { name: "Next" })[0]!).toBeDisabled();

    fireEvent.click(screen.getAllByRole("button", { name: "Previous" })[0]!);
    expect(
      await screen.findByRole("cell", { name: "team.update" }),
    ).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      4,
      "/v1/admin/audit-events?page=1&limit=50&entityType=team",
      expect.any(Object),
    );
  });

  it("changes page size and reloads from page one", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(
        auditListResponse([auditEvent()], {
          page: 1,
          limit: 50,
          total: 125,
          hasMore: true,
        }),
      )
      .mockResolvedValueOnce(
        auditListResponse(
          [
            auditEvent({
              id: "audit-2",
              action: "fixture.update",
              entityType: "fixture",
              entityId: "fixture-1",
              summary: "fixture.update on fixture fixture-1",
            }),
          ],
          { page: 1, limit: 100, total: 125, hasMore: true },
        ),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<AuditLogPage />);

    expect(await screen.findAllByText("Page 1")).toHaveLength(2);
    fireEvent.change(screen.getAllByLabelText("Page size")[0]!, {
      target: { value: "100" },
    });

    expect(
      await screen.findByRole("cell", { name: "fixture.update" }),
    ).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/audit-events?page=1&limit=100",
      expect.any(Object),
    );
  });

  it("exports CSV with current filters", async () => {
    const createObjectUrl = vi.fn().mockReturnValue("blob:audit-events");
    const revokeObjectUrl = vi.fn();
    Object.defineProperty(URL, "createObjectURL", {
      configurable: true,
      value: createObjectUrl,
    });
    Object.defineProperty(URL, "revokeObjectURL", {
      configurable: true,
      value: revokeObjectUrl,
    });
    const clickSpy = vi
      .spyOn(HTMLAnchorElement.prototype, "click")
      .mockImplementation(() => undefined);
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(auditListResponse([]))
      .mockResolvedValueOnce(csvResponse("occurredAt,actorUserId\n"));
    vi.stubGlobal("fetch", fetchMock);

    render(<AuditLogPage />);

    await screen.findByText("No audit events found");
    fireEvent.change(screen.getByLabelText("Actor user ID"), {
      target: { value: "admin-user" },
    });
    fireEvent.change(screen.getByLabelText("Entity type"), {
      target: { value: "team" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Export CSV" }));

    expect(
      await screen.findByRole("button", { name: "Export CSV" }),
    ).toBeEnabled();
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/audit-events/export?actorUserId=admin-user&entityType=team",
      expect.objectContaining({ headers: expect.any(Headers) }),
    );
    expect(createObjectUrl).toHaveBeenCalledWith(expect.any(Blob));
    expect(clickSpy).toHaveBeenCalled();
    expect(revokeObjectUrl).toHaveBeenCalledWith("blob:audit-events");
  });

  it("renders an export failure state", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(auditListResponse([]))
      .mockResolvedValueOnce(
        jsonResponse(
          {
            error: {
              code: "export_failed",
              message: "Unable to export audit events.",
            },
          },
          500,
        ),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<AuditLogPage />);

    await screen.findByText("No audit events found");
    fireEvent.click(screen.getByRole("button", { name: "Export CSV" }));

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Unable to export audit events",
    );
    expect(screen.getByText("Unable to export audit events.")).toBeTruthy();
  });
});
