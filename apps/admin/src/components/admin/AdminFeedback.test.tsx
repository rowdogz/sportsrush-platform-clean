import "@testing-library/jest-dom/vitest";
import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import {
  AdminFeedback,
  adminErrorToast,
  adminSuccessToast,
} from "./AdminFeedback";

describe("AdminFeedback", () => {
  it("renders shared success feedback", () => {
    render(<AdminFeedback feedback={adminSuccessToast("Saved changes.")} />);

    expect(screen.getByRole("status")).toHaveTextContent("Saved changes.");
  });

  it("renders shared error feedback", () => {
    render(<AdminFeedback feedback={adminErrorToast("Unable to save.")} />);

    expect(screen.getByRole("alert")).toHaveTextContent("Unable to save.");
  });
});
