import { describe, it, expect } from "vitest";
import { hasRole, assertRole, RoleError, formatRole } from "./roles";
import type { TokenPayload } from "@sr/types";

function makePayload(role: TokenPayload["role"]): TokenPayload {
  return {
    userId: "00000000-0000-0000-0000-000000000001" as TokenPayload["userId"],
    sessionId:
      "00000000-0000-0000-0000-000000000002" as TokenPayload["sessionId"],
    role,
    exp: Math.floor(Date.now() / 1000) + 900,
    iat: Math.floor(Date.now() / 1000),
  };
}

// ── hasRole ────────────────────────────────────────────────────────────────────

describe("hasRole", () => {
  it("user has the user role", () => {
    expect(hasRole(makePayload("user"), "user")).toBe(true);
  });

  it("user does not have the admin role", () => {
    expect(hasRole(makePayload("user"), "admin")).toBe(false);
  });

  it("user does not have the superadmin role", () => {
    expect(hasRole(makePayload("user"), "superadmin")).toBe(false);
  });

  it("admin has the user role", () => {
    expect(hasRole(makePayload("admin"), "user")).toBe(true);
  });

  it("admin has the admin role", () => {
    expect(hasRole(makePayload("admin"), "admin")).toBe(true);
  });

  it("admin does not have the superadmin role", () => {
    expect(hasRole(makePayload("admin"), "superadmin")).toBe(false);
  });

  it("superadmin has all roles", () => {
    expect(hasRole(makePayload("superadmin"), "user")).toBe(true);
    expect(hasRole(makePayload("superadmin"), "admin")).toBe(true);
    expect(hasRole(makePayload("superadmin"), "superadmin")).toBe(true);
  });
});

// ── assertRole ────────────────────────────────────────────────────────────────

describe("assertRole", () => {
  it("does not throw when role is sufficient", () => {
    expect(() => assertRole(makePayload("admin"), "user")).not.toThrow();
    expect(() => assertRole(makePayload("admin"), "admin")).not.toThrow();
    expect(() =>
      assertRole(makePayload("superadmin"), "superadmin"),
    ).not.toThrow();
  });

  it("throws RoleError when role is insufficient", () => {
    expect(() => assertRole(makePayload("user"), "admin")).toThrowError(
      RoleError,
    );
    expect(() => assertRole(makePayload("admin"), "superadmin")).toThrowError(
      RoleError,
    );
  });

  it("RoleError carries requiredRole and actualRole", () => {
    try {
      assertRole(makePayload("user"), "admin");
    } catch (err) {
      expect(err).toBeInstanceOf(RoleError);
      if (err instanceof RoleError) {
        expect(err.requiredRole).toBe("admin");
        expect(err.actualRole).toBe("user");
      }
    }
  });
});

// ── formatRole ────────────────────────────────────────────────────────────────

describe("formatRole", () => {
  it("returns human-readable labels", () => {
    expect(formatRole("user")).toBe("User");
    expect(formatRole("admin")).toBe("Admin");
    expect(formatRole("superadmin")).toBe("Super Admin");
  });
});
