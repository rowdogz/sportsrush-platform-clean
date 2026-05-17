import "@testing-library/jest-dom/vitest";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { App } from "./App";
import type { UserRole } from "./features/users/types";

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json" },
  });
}

function accessTokenForRole(role: UserRole): string {
  return `header.${window.btoa(JSON.stringify({ role }))}.signature`;
}

function emptyPaginatedResponse(): Response {
  return jsonResponse({
    data: [],
    meta: { page: 1, limit: 50, total: 0, hasMore: false },
  });
}

describe("Admin app shell", () => {
  afterEach(() => {
    vi.restoreAllMocks();
    window.localStorage.clear();
    window.history.replaceState(null, "", "/");
  });

  it("shows the login page when no admin session is stored", () => {
    render(<App />);

    expect(screen.getByRole("heading", { name: "Sign in" })).toBeTruthy();
    expect(screen.queryByText("No admin data yet")).toBeNull();
  });

  it("logs in and renders the SportsRush admin shell", async () => {
    const fetchMock = vi.fn(() => Promise.resolve(emptyPaginatedResponse()));
    fetchMock.mockResolvedValueOnce(
      jsonResponse({
        data: {
          accessToken: "access-token-123",
          refreshToken: "refresh-token-123",
          user: {
            id: "admin-user-1",
            email: "admin@sportsrush.test",
            role: "admin",
          },
          profile: {
            displayName: "Admin User",
          },
          session: {
            id: "session-1",
            expiresAt: "2026-01-01T00:00:00.000Z",
          },
        },
      }),
    );
    vi.stubGlobal("fetch", fetchMock);

    render(<App />);

    fireEvent.change(screen.getByLabelText("Email"), {
      target: { value: "admin@sportsrush.test" },
    });
    fireEvent.change(screen.getByLabelText("Password"), {
      target: { value: "password-123" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Sign in" }));

    expect(
      await screen.findByRole("heading", { name: "SportsRush Admin" }),
    ).toBeTruthy();
    expect(await screen.findByText("No admin data yet")).toBeTruthy();
    expect(window.localStorage.getItem("sr_admin_access_token")).toBe(
      "access-token-123",
    );
    expect(window.localStorage.getItem("sr_admin_refresh_token")).toBe(
      "refresh-token-123",
    );
    expect(fetchMock).toHaveBeenNthCalledWith(
      1,
      expect.stringContaining("/v1/auth/login"),
      expect.objectContaining({
        method: "POST",
        body: JSON.stringify({
          email: "admin@sportsrush.test",
          password: "password-123",
        }),
      }),
    );
  });

  it("renders the admin shell from stored session tokens", async () => {
    window.localStorage.setItem(
      "sr_admin_access_token",
      accessTokenForRole("superadmin"),
    );
    window.localStorage.setItem(
      "sr_admin_refresh_token",
      "stored-refresh-token",
    );
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockImplementation(() => Promise.resolve(emptyPaginatedResponse())),
    );
    render(<App />);
    expect(
      screen.getByRole("heading", { name: "SportsRush Admin" }),
    ).toBeTruthy();
    expect(await screen.findByText("No admin data yet")).toBeTruthy();
  });

  it("shows admin navigation to authenticated users", async () => {
    window.localStorage.setItem(
      "sr_admin_access_token",
      accessTokenForRole("superadmin"),
    );
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockImplementation(() => Promise.resolve(emptyPaginatedResponse())),
    );

    render(<App />);

    expect(await screen.findByLabelText("Admin navigation")).toBeTruthy();
    expect(screen.getByRole("button", { name: "Dashboard" })).toHaveAttribute(
      "aria-current",
      "page",
    );
    expect(screen.getByRole("button", { name: "Competitions" })).toBeTruthy();
    expect(screen.getByRole("button", { name: "Seasons" })).toBeTruthy();
    expect(screen.getByRole("button", { name: "Teams" })).toBeTruthy();
    expect(screen.getByRole("button", { name: "Users" })).toBeTruthy();
    expect(screen.getByRole("button", { name: "Audit Log" })).toBeTruthy();
    expect(screen.getByRole("button", { name: "Fixtures" })).toBeTruthy();
    expect(screen.getByRole("button", { name: "Aliases" })).toBeTruthy();
    expect(screen.getByRole("button", { name: "Rounds" })).toBeTruthy();
  });

  it("renders the dashboard by default for authenticated users", async () => {
    window.localStorage.setItem(
      "sr_admin_access_token",
      accessTokenForRole("superadmin"),
    );
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockImplementation(() => Promise.resolve(emptyPaginatedResponse())),
    );

    render(<App />);

    expect(
      await screen.findByRole("heading", { name: "Dashboard" }),
    ).toBeTruthy();
    expect(screen.getByText("No admin data yet")).toBeTruthy();
  });

  it("opens the Seasons page from admin navigation", async () => {
    window.localStorage.setItem(
      "sr_admin_access_token",
      accessTokenForRole("superadmin"),
    );
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockImplementation(() => Promise.resolve(emptyPaginatedResponse())),
    );

    render(<App />);

    await screen.findByRole("heading", { name: "Dashboard" });

    fireEvent.click(screen.getByRole("button", { name: "Seasons" }));
    expect(
      await screen.findByRole("heading", { name: "Seasons" }),
    ).toBeTruthy();
    expect(screen.getByRole("button", { name: "Create season" })).toBeTruthy();
  });

  it("opens the Teams page from admin navigation", async () => {
    window.localStorage.setItem(
      "sr_admin_access_token",
      accessTokenForRole("superadmin"),
    );
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockImplementation(() => Promise.resolve(emptyPaginatedResponse())),
    );

    render(<App />);

    await screen.findByRole("heading", { name: "Dashboard" });

    fireEvent.click(screen.getByRole("button", { name: "Teams" }));
    expect(await screen.findByRole("heading", { name: "Teams" })).toBeTruthy();
    expect(screen.getByRole("button", { name: "Create team" })).toBeTruthy();
  });

  it("opens the Users page from admin navigation", async () => {
    window.localStorage.setItem(
      "sr_admin_access_token",
      accessTokenForRole("superadmin"),
    );
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockImplementation(() => Promise.resolve(emptyPaginatedResponse())),
    );

    render(<App />);

    await screen.findByRole("heading", { name: "Dashboard" });

    fireEvent.click(screen.getByRole("button", { name: "Users" }));
    expect(await screen.findByRole("heading", { name: "Users" })).toBeTruthy();
    expect(screen.getByRole("button", { name: "Apply filters" })).toBeTruthy();
  });

  it("opens the Audit Log page from admin navigation", async () => {
    window.localStorage.setItem(
      "sr_admin_access_token",
      accessTokenForRole("superadmin"),
    );
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockImplementation(() => Promise.resolve(emptyPaginatedResponse())),
    );

    render(<App />);

    await screen.findByRole("heading", { name: "Dashboard" });

    fireEvent.click(screen.getByRole("button", { name: "Audit Log" }));
    expect(
      await screen.findByRole("heading", { name: "Audit Log" }),
    ).toBeTruthy();
    expect(screen.getByRole("button", { name: "Apply filters" })).toBeTruthy();
  });

  it("hides the Audit Log screen from non-superadmin admins", async () => {
    window.localStorage.setItem(
      "sr_admin_access_token",
      accessTokenForRole("admin"),
    );
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockImplementation(() => Promise.resolve(emptyPaginatedResponse())),
    );

    render(<App />);

    expect(await screen.findByLabelText("Admin navigation")).toBeTruthy();
    expect(
      screen.queryByRole("button", { name: "Audit Log" }),
    ).not.toBeInTheDocument();
  });

  it("shows a forbidden state for direct audit access without superadmin", async () => {
    window.history.replaceState(null, "", "/audit");
    window.localStorage.setItem(
      "sr_admin_access_token",
      accessTokenForRole("admin"),
    );
    vi.stubGlobal("fetch", vi.fn());

    render(<App />);

    expect(await screen.findByRole("alert")).toHaveTextContent("Forbidden");
    expect(
      screen.getByText("Your admin role is not permitted to view this screen."),
    ).toBeTruthy();
  });

  it("renders the Audit Log page for the /audit route", async () => {
    window.history.replaceState(null, "", "/audit");
    window.localStorage.setItem(
      "sr_admin_access_token",
      accessTokenForRole("superadmin"),
    );
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockImplementation(() => Promise.resolve(emptyPaginatedResponse())),
    );

    render(<App />);

    expect(
      await screen.findByRole("heading", { name: "Audit Log" }),
    ).toBeTruthy();
    expect(screen.getByRole("button", { name: "Audit Log" })).toHaveAttribute(
      "aria-current",
      "page",
    );
  });

  it("opens the Team Aliases page from admin navigation", async () => {
    window.localStorage.setItem(
      "sr_admin_access_token",
      accessTokenForRole("superadmin"),
    );
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockImplementation(() => Promise.resolve(emptyPaginatedResponse())),
    );

    render(<App />);

    await screen.findByRole("heading", { name: "Dashboard" });

    fireEvent.click(screen.getByRole("button", { name: "Aliases" }));
    expect(
      await screen.findByRole("heading", { name: "Team Aliases" }),
    ).toBeTruthy();
    expect(
      screen.getByRole("button", { name: "Create team alias" }),
    ).toBeTruthy();
  });

  it("opens the Rounds page from admin navigation", async () => {
    window.localStorage.setItem(
      "sr_admin_access_token",
      accessTokenForRole("superadmin"),
    );
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockImplementation(() => Promise.resolve(emptyPaginatedResponse())),
    );

    render(<App />);

    await screen.findByRole("heading", { name: "Dashboard" });

    fireEvent.click(screen.getByRole("button", { name: "Rounds" }));
    expect(await screen.findByRole("heading", { name: "Rounds" })).toBeTruthy();
    expect(screen.getByRole("button", { name: "Create round" })).toBeTruthy();
  });

  it("opens the Fixtures page from admin navigation", async () => {
    window.localStorage.setItem(
      "sr_admin_access_token",
      accessTokenForRole("superadmin"),
    );
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockImplementation(() => Promise.resolve(emptyPaginatedResponse())),
    );

    render(<App />);

    await screen.findByRole("heading", { name: "Dashboard" });

    fireEvent.click(screen.getByRole("button", { name: "Fixtures" }));
    expect(
      await screen.findByRole("heading", { name: "Fixtures" }),
    ).toBeTruthy();
    expect(screen.getByRole("button", { name: "Create fixture" })).toBeTruthy();
  });

  it("logs out and clears stored session tokens", async () => {
    window.localStorage.setItem(
      "sr_admin_access_token",
      accessTokenForRole("superadmin"),
    );
    window.localStorage.setItem(
      "sr_admin_refresh_token",
      "stored-refresh-token",
    );
    vi.stubGlobal(
      "fetch",
      vi
        .fn()
        .mockImplementation(() => Promise.resolve(emptyPaginatedResponse())),
    );

    render(<App />);

    fireEvent.click(await screen.findByRole("button", { name: "Log out" }));

    await waitFor(() =>
      expect(screen.getByRole("heading", { name: "Sign in" })).toBeTruthy(),
    );
    expect(window.localStorage.getItem("sr_admin_access_token")).toBeNull();
    expect(window.localStorage.getItem("sr_admin_refresh_token")).toBeNull();
  });

  it("shows validation errors before submitting login", () => {
    const fetchMock = vi.fn();
    vi.stubGlobal("fetch", fetchMock);

    render(<App />);
    fireEvent.click(screen.getByRole("button", { name: "Sign in" }));

    expect(screen.getByRole("alert")).toHaveTextContent(
      "Enter an email address and password.",
    );
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it("shows API errors from login", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(
        jsonResponse(
          {
            error: {
              code: "invalid_credentials",
              message: "Invalid email or password.",
            },
          },
          401,
        ),
      ),
    );

    render(<App />);

    fireEvent.change(screen.getByLabelText("Email"), {
      target: { value: "admin@sportsrush.test" },
    });
    fireEvent.change(screen.getByLabelText("Password"), {
      target: { value: "wrong-password" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Sign in" }));

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Invalid email or password.",
    );
  });
});
