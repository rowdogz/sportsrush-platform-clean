import "@testing-library/jest-dom/vitest";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { App } from "./App";

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json" },
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
    expect(screen.queryByText("No competitions found")).toBeNull();
  });

  it("logs in and renders the SportsRush admin shell", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(
        jsonResponse({
          accessToken: "access-token-123",
          refreshToken: "refresh-token-123",
        }),
      )
      .mockResolvedValueOnce(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
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

    expect(await screen.findByText(/SportsRush Admin/i)).toBeTruthy();
    expect(await screen.findByText("No competitions found")).toBeTruthy();
    expect(window.localStorage.getItem("sr_admin_access_token")).toBe(
      "access-token-123",
    );
    expect(window.localStorage.getItem("sr_admin_refresh_token")).toBe(
      "refresh-token-123",
    );
    expect(fetchMock).toHaveBeenNthCalledWith(
      1,
      "/v1/auth/login",
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
    window.localStorage.setItem("sr_admin_access_token", "stored-access-token");
    window.localStorage.setItem(
      "sr_admin_refresh_token",
      "stored-refresh-token",
    );
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
        }),
      ),
    );
    render(<App />);
    expect(screen.getByText(/SportsRush Admin/i)).toBeTruthy();
    expect(await screen.findByText("No competitions found")).toBeTruthy();
  });

  it("shows admin navigation to authenticated users", async () => {
    window.localStorage.setItem("sr_admin_access_token", "stored-access-token");
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
        }),
      ),
    );

    render(<App />);

    expect(await screen.findByLabelText("Admin navigation")).toBeTruthy();
    expect(
      screen.getByRole("button", { name: "Competitions" }),
    ).toHaveAttribute("aria-current", "page");
    expect(screen.getByRole("button", { name: "Seasons" })).toBeTruthy();
    expect(screen.getByRole("button", { name: "Teams" })).toBeTruthy();
    expect(screen.getByRole("button", { name: "Users" })).toBeTruthy();
    expect(screen.getByRole("button", { name: "Audit Log" })).toBeTruthy();
    expect(screen.getByRole("button", { name: "Fixtures" })).toBeTruthy();
    expect(screen.getByRole("button", { name: "Aliases" })).toBeTruthy();
    expect(screen.getByRole("button", { name: "Rounds" })).toBeTruthy();
  });

  it("renders competitions by default for authenticated users", async () => {
    window.localStorage.setItem("sr_admin_access_token", "stored-access-token");
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
        }),
      ),
    );

    render(<App />);

    expect(
      await screen.findByRole("heading", { name: "Competitions" }),
    ).toBeTruthy();
    expect(
      screen.getByRole("button", { name: "Create competition" }),
    ).toBeTruthy();
  });

  it("opens the Seasons page from admin navigation", async () => {
    window.localStorage.setItem("sr_admin_access_token", "stored-access-token");
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
        }),
      )
      .mockResolvedValueOnce(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
        }),
      )
      .mockResolvedValueOnce(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
        }),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<App />);

    await screen.findByRole("heading", { name: "Competitions" });

    fireEvent.click(screen.getByRole("button", { name: "Seasons" }));
    expect(
      await screen.findByRole("heading", { name: "Seasons" }),
    ).toBeTruthy();
    expect(screen.getByRole("button", { name: "Create season" })).toBeTruthy();
  });

  it("opens the Teams page from admin navigation", async () => {
    window.localStorage.setItem("sr_admin_access_token", "stored-access-token");
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
        }),
      )
      .mockResolvedValueOnce(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
        }),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<App />);

    await screen.findByRole("heading", { name: "Competitions" });

    fireEvent.click(screen.getByRole("button", { name: "Teams" }));
    expect(await screen.findByRole("heading", { name: "Teams" })).toBeTruthy();
    expect(screen.getByRole("button", { name: "Create team" })).toBeTruthy();
  });

  it("opens the Users page from admin navigation", async () => {
    window.localStorage.setItem("sr_admin_access_token", "stored-access-token");
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
        }),
      )
      .mockResolvedValueOnce(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
        }),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<App />);

    await screen.findByRole("heading", { name: "Competitions" });

    fireEvent.click(screen.getByRole("button", { name: "Users" }));
    expect(await screen.findByRole("heading", { name: "Users" })).toBeTruthy();
    expect(screen.getByRole("button", { name: "Apply filters" })).toBeTruthy();
  });

  it("opens the Audit Log page from admin navigation", async () => {
    window.localStorage.setItem("sr_admin_access_token", "stored-access-token");
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
        }),
      )
      .mockResolvedValueOnce(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
        }),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<App />);

    await screen.findByRole("heading", { name: "Competitions" });

    fireEvent.click(screen.getByRole("button", { name: "Audit Log" }));
    expect(
      await screen.findByRole("heading", { name: "Audit Log" }),
    ).toBeTruthy();
    expect(screen.getByRole("button", { name: "Apply filters" })).toBeTruthy();
  });

  it("renders the Audit Log page for the /audit route", async () => {
    window.history.replaceState(null, "", "/audit");
    window.localStorage.setItem("sr_admin_access_token", "stored-access-token");
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
        }),
      ),
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
    window.localStorage.setItem("sr_admin_access_token", "stored-access-token");
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
        }),
      )
      .mockResolvedValueOnce(jsonResponse({ data: [] }));
    vi.stubGlobal("fetch", fetchMock);

    render(<App />);

    await screen.findByRole("heading", { name: "Competitions" });

    fireEvent.click(screen.getByRole("button", { name: "Aliases" }));
    expect(
      await screen.findByRole("heading", { name: "Team Aliases" }),
    ).toBeTruthy();
    expect(
      screen.getByRole("button", { name: "Create team alias" }),
    ).toBeTruthy();
  });

  it("opens the Rounds page from admin navigation", async () => {
    window.localStorage.setItem("sr_admin_access_token", "stored-access-token");
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
        }),
      )
      .mockResolvedValueOnce(jsonResponse({ data: [] }));
    vi.stubGlobal("fetch", fetchMock);

    render(<App />);

    await screen.findByRole("heading", { name: "Competitions" });

    fireEvent.click(screen.getByRole("button", { name: "Rounds" }));
    expect(await screen.findByRole("heading", { name: "Rounds" })).toBeTruthy();
    expect(screen.getByRole("button", { name: "Create round" })).toBeTruthy();
  });

  it("opens the Fixtures page from admin navigation", async () => {
    window.localStorage.setItem("sr_admin_access_token", "stored-access-token");
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
        }),
      )
      .mockResolvedValueOnce(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
        }),
      )
      .mockResolvedValueOnce(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
        }),
      )
      .mockResolvedValueOnce(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
        }),
      );
    vi.stubGlobal("fetch", fetchMock);

    render(<App />);

    await screen.findByRole("heading", { name: "Competitions" });

    fireEvent.click(screen.getByRole("button", { name: "Fixtures" }));
    expect(
      await screen.findByRole("heading", { name: "Fixtures" }),
    ).toBeTruthy();
    expect(screen.getByRole("button", { name: "Create fixture" })).toBeTruthy();
  });

  it("logs out and clears stored session tokens", async () => {
    window.localStorage.setItem("sr_admin_access_token", "stored-access-token");
    window.localStorage.setItem(
      "sr_admin_refresh_token",
      "stored-refresh-token",
    );
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue(
        jsonResponse({
          data: [],
          meta: { page: 1, limit: 50, total: 0, hasMore: false },
        }),
      ),
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
