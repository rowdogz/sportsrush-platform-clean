import "@testing-library/jest-dom/vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import {
  AdminTableEmpty,
  AdminTableError,
  AdminTableLoading,
} from "./AdminTableState";

describe("AdminTableState", () => {
  it("renders a shared loading state", () => {
    render(<AdminTableLoading message="Loading records…" />);

    expect(screen.getByRole("status")).toHaveTextContent("Loading records…");
  });

  it("renders a shared empty state", () => {
    render(
      <AdminTableEmpty title="No records found" message="Add records first." />,
    );

    expect(screen.getByText("No records found")).toBeTruthy();
    expect(screen.getByText("Add records first.")).toBeTruthy();
  });

  it("renders a shared error state", () => {
    render(<AdminTableError title="Unable to load" message="Forbidden." />);

    expect(screen.getByRole("alert")).toHaveTextContent("Unable to load");
    expect(screen.getByRole("alert")).toHaveTextContent("Forbidden.");
  });

  it("renders retry actions for shared error states", () => {
    const onRetry = vi.fn();
    render(
      <AdminTableError
        title="Unable to load"
        message="Network failed."
        onRetry={onRetry}
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: "Retry" }));
    expect(onRetry).toHaveBeenCalledTimes(1);
  });
});
