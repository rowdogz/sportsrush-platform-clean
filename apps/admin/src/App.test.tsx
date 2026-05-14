import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { App } from "./App";

describe("Admin app shell", () => {
  it("renders the SportsRush admin shell", () => {
    render(<App />);
    expect(screen.getByText(/SportsRush Admin/i)).toBeTruthy();
  });
});
