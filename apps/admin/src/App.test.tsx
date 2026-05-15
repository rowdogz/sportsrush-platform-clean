import "@testing-library/jest-dom/vitest";
import { render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { App } from "./App";

function jsonResponse(body: unknown): Response {
  return new Response(JSON.stringify(body), {
    headers: { "Content-Type": "application/json" },
  });
}

describe("Admin app shell", () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("renders the SportsRush admin shell", async () => {
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
});
