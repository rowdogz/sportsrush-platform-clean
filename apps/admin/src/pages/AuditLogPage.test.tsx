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

function auditListResponse(data: readonly Record<string, unknown>[]): Response {
  return jsonResponse({
    data,
    meta: { page: 1, limit: 50, total: data.length, hasMore: false },
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

  it("renders expandable metadata details", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValueOnce(auditListResponse([auditEvent()])),
    );

    render(<AuditLogPage />);

    fireEvent.click(await screen.findByText("View metadata"));
    expect(screen.getAllByText(/"role":/).length).toBeGreaterThan(0);
    expect(screen.getAllByText(/"before": "user"/).length).toBeGreaterThan(0);
    expect(screen.getAllByText(/"after": "admin"/).length).toBeGreaterThan(0);
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
});
