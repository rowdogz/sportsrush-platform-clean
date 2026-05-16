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

  it("restores persisted user filters on page revisit", async () => {
    window.localStorage.setItem(
      "sr-admin:users:filters",
      JSON.stringify({ search: "admin", role: "admin", status: "inactive" }),
    );
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(userListResponse([user({ role: "admin" })]));
    vi.stubGlobal("fetch", fetchMock);

    render(<UsersPage />);

    expect(
      await screen.findByRole("cell", { name: "Alice Example" }),
    ).toBeTruthy();
    expect(screen.getByLabelText("Search")).toHaveValue("admin");
    expect(screen.getByLabelText("Role")).toHaveValue("admin");
    expect(screen.getByLabelText("Status")).toHaveValue("inactive");
    expect(fetchMock).toHaveBeenNthCalledWith(
      1,
      "/v1/admin/users?page=1&limit=50&search=admin&role=admin&isActive=false",
      expect.objectContaining({ headers: expect.any(Headers) }),
    );
  });

  it("updates a user role after confirmation", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(userListResponse([user()]))
      .mockResolvedValueOnce(jsonResponse({ data: user({ role: "admin" }) }))
      .mockResolvedValueOnce(userListResponse([user({ role: "admin" })]));
    vi.stubGlobal("fetch", fetchMock);
    vi.spyOn(window, "confirm").mockReturnValue(true);

    render(<UsersPage />);

    await screen.findByRole("cell", { name: "Alice Example" });
    fireEvent.change(screen.getByLabelText("Role for alice@example.test"), {
      target: { value: "admin" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Update role" }));

    expect(await screen.findByText("User role updated.")).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/users/user-1/role",
      expect.objectContaining({
        method: "PATCH",
        body: JSON.stringify({ role: "admin" }),
      }),
    );
  });

  it("updates a user status after confirmation", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(userListResponse([user()]))
      .mockResolvedValueOnce(jsonResponse({ data: user({ is_active: 0 }) }))
      .mockResolvedValueOnce(userListResponse([user({ is_active: 0 })]));
    vi.stubGlobal("fetch", fetchMock);
    vi.spyOn(window, "confirm").mockReturnValue(true);

    render(<UsersPage />);

    await screen.findByRole("cell", { name: "Alice Example" });
    fireEvent.change(screen.getByLabelText("Status for alice@example.test"), {
      target: { value: "inactive" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Update status" }));

    expect(await screen.findByText("User status updated.")).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/users/user-1/status",
      expect.objectContaining({
        method: "PATCH",
        body: JSON.stringify({ isActive: false }),
      }),
    );
  });

  it("suspends a user after confirmation", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(userListResponse([user()]))
      .mockResolvedValueOnce(jsonResponse({ data: user({ is_active: 0 }) }))
      .mockResolvedValueOnce(userListResponse([user({ is_active: 0 })]));
    vi.stubGlobal("fetch", fetchMock);
    vi.spyOn(window, "confirm").mockReturnValue(true);

    render(<UsersPage />);

    await screen.findByRole("cell", { name: "Alice Example" });
    fireEvent.click(screen.getByRole("button", { name: "Suspend" }));

    expect(await screen.findByText("User suspended.")).toBeTruthy();
    expect(fetchMock).toHaveBeenNthCalledWith(
      2,
      "/v1/admin/users/user-1/suspend",
      expect.objectContaining({ method: "POST" }),
    );
  });

  it("does not suspend a user when confirmation is cancelled", async () => {
    const fetchMock = vi.fn().mockResolvedValueOnce(userListResponse([user()]));
    vi.stubGlobal("fetch", fetchMock);
    vi.spyOn(window, "confirm").mockReturnValue(false);

    render(<UsersPage />);

    await screen.findByRole("cell", { name: "Alice Example" });
    fireEvent.click(screen.getByRole("button", { name: "Suspend" }));

    expect(fetchMock).toHaveBeenCalledTimes(1);
  });

  it("shows validation feedback when role is unchanged", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValueOnce(userListResponse([user()])),
    );

    render(<UsersPage />);

    await screen.findByRole("cell", { name: "Alice Example" });
    fireEvent.click(screen.getByRole("button", { name: "Update role" }));

    expect(
      await screen.findByText("Choose a different role first."),
    ).toBeTruthy();
  });

  it("shows API errors from user actions", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(userListResponse([user()]))
      .mockResolvedValueOnce(
        jsonResponse(
          {
            error: {
              code: "ADMIN_DOMAIN_ERROR",
              message: "Admins cannot deactivate or suspend their own account.",
            },
          },
          422,
        ),
      );
    vi.stubGlobal("fetch", fetchMock);
    vi.spyOn(window, "confirm").mockReturnValue(true);

    render(<UsersPage />);

    await screen.findByRole("cell", { name: "Alice Example" });
    fireEvent.click(screen.getByRole("button", { name: "Suspend" }));

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Admins cannot deactivate or suspend their own account.",
    );
  });
});
