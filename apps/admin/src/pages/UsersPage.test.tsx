import "@testing-library/jest-dom/vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { setAccessTokenProvider } from "../lib/apiClient";
import { UsersPage } from "./UsersPage";

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}

function userListResponse(data: readonly Record<string, unknown>[]): Response {
  return jsonResponse({
    data,
    meta: { page: 1, limit: 50, total: data.length, hasMore: false },
  });
}

function user(overrides: Record<string, unknown> = {}) {
  return {
    id: "user-1",
    email: "alice@example.test",
    display_name: "Alice Example",
    role: "user",
    is_active: 1,
    email_verified_at: "2026-01-02T00:00:00.000Z",
    created_at: "2026-01-01T00:00:00.000Z",
    updated_at: "2026-01-03T00:00:00.000Z",
    profile_updated_at: "2026-01-04T00:00:00.000Z",
    legacy_wp_user_id: null,
    ...overrides,
  };
}

describe("UsersPage", () => {
  afterEach(() => {
    vi.restoreAllMocks();
    setAccessTokenProvider(() => null);
    window.localStorage.clear();
  });

  it("shows loading and then renders users", async () => {
    const fetchMock = vi.fn().mockResolvedValueOnce(userListResponse([user()]));
    vi.stubGlobal("fetch", fetchMock);

    render(<UsersPage />);

    expect(screen.getByRole("status")).toHaveTextContent("Loading users…");
    expect(
      await screen.findByRole("cell", { name: "Alice Example" }),
    ).toBeTruthy();
    expect(
      screen.getByRole("cell", { name: "alice@example.test" }),
    ).toBeTruthy();
    expect(screen.getByRole("cell", { name: "user" })).toBeTruthy();
    expect(screen.getByRole("cell", { name: "active" })).toBeTruthy();
    expect(screen.getByRole("cell", { name: "verified" })).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      1,
      "/v1/admin/users?page=1&limit=50",
      expect.objectContaining({ headers: expect.any(Headers) }),
    );
  });

  it("renders an empty state", async () => {
    vi.stubGlobal("fetch", vi.fn().mockResolvedValueOnce(userListResponse([])));

    render(<UsersPage />);

    expect(await screen.findByText("No users found")).toBeTruthy();
    expect(
      screen.getByText("Users will appear here after they register."),
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

    render(<UsersPage />);

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Unable to load users",
    );
    expect(screen.getByText("Admin access is required.")).toBeTruthy();
  });

  it("filters users with supported API query params", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(userListResponse([]))
      .mockResolvedValueOnce(
        userListResponse([
          user({
            id: "user-2",
            email: "admin@example.test",
            display_name: "Admin Example",
            role: "admin",
            is_active: 0,
            email_verified_at: null,
          }),
        ]),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<UsersPage />);

    await screen.findByText("No users found");
    fireEvent.change(screen.getByLabelText("Search"), {
      target: { value: "admin" },
    });
    fireEvent.change(screen.getByLabelText("Role"), {
      target: { value: "admin" },
    });
    fireEvent.change(screen.getByLabelText("Status"), {
      target: { value: "inactive" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Apply filters" }));

    expect(
      await screen.findByRole("cell", { name: "Admin Example" }),
    ).toBeTruthy();
    expect(screen.getByRole("cell", { name: "inactive" })).toBeTruthy();
    expect(screen.getByRole("cell", { name: "unverified" })).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/users?page=1&limit=50&search=admin&role=admin&isActive=false",
      expect.any(Object),
    );
  });

  it("states unsupported write actions are deferred", async () => {
    vi.stubGlobal("fetch", vi.fn().mockResolvedValueOnce(userListResponse([])));

    render(<UsersPage />);

    expect(await screen.findByText("Read-only user management")).toBeTruthy();
    expect(
      screen.getByText(
        "Create, archive, suspend, and role-change actions are deferred until matching admin API endpoints exist.",
      ),
    ).toBeTruthy();
  });
});
