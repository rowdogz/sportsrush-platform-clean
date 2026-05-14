import { describe, it, expect } from "vitest";
import {
  RegisterSchema,
  LoginSchema,
  PasswordResetConfirmSchema,
  PasswordResetRequestSchema,
  RefreshTokenSchema,
  UpdateDisplayNameSchema,
  ChangePasswordSchema,
} from "./auth";

// ── RegisterSchema ────────────────────────────────────────────────────────────

describe("RegisterSchema", () => {
  const valid = {
    email: "test@example.com",
    password: "password123",
    displayName: "TestUser",
  };

  it("accepts valid registration data", () => {
    const result = RegisterSchema.safeParse(valid);
    expect(result.success).toBe(true);
  });

  it("normalises email to lowercase", () => {
    const result = RegisterSchema.safeParse({
      ...valid,
      email: "TEST@EXAMPLE.COM",
    });
    expect(result.success).toBe(true);
    if (result.success) expect(result.data.email).toBe("test@example.com");
  });

  it("trims displayName whitespace", () => {
    const result = RegisterSchema.safeParse({
      ...valid,
      displayName: "  Alice  ",
    });
    expect(result.success).toBe(true);
    if (result.success) expect(result.data.displayName).toBe("Alice");
  });

  it("rejects email without @", () => {
    const result = RegisterSchema.safeParse({ ...valid, email: "notanemail" });
    expect(result.success).toBe(false);
  });

  it("rejects email longer than 254 characters", () => {
    // 246 + '@test.com'(9) = 255 chars — one over the 254 limit
    const result = RegisterSchema.safeParse({
      ...valid,
      email: `${"a".repeat(246)}@test.com`,
    });
    expect(result.success).toBe(false);
  });

  it("rejects password shorter than 8 characters", () => {
    const result = RegisterSchema.safeParse({ ...valid, password: "short" });
    expect(result.success).toBe(false);
  });

  it("rejects password longer than 72 characters", () => {
    const result = RegisterSchema.safeParse({
      ...valid,
      password: "a".repeat(73),
    });
    expect(result.success).toBe(false);
  });

  it("rejects displayName shorter than 2 characters", () => {
    const result = RegisterSchema.safeParse({ ...valid, displayName: "A" });
    expect(result.success).toBe(false);
  });

  it("rejects displayName longer than 50 characters", () => {
    const result = RegisterSchema.safeParse({
      ...valid,
      displayName: "a".repeat(51),
    });
    expect(result.success).toBe(false);
  });

  it("rejects missing email", () => {
    const { email: _email, ...rest } = valid;
    const result = RegisterSchema.safeParse(rest);
    expect(result.success).toBe(false);
  });

  it("rejects missing password", () => {
    const { password: _pw, ...rest } = valid;
    const result = RegisterSchema.safeParse(rest);
    expect(result.success).toBe(false);
  });
});

// ── LoginSchema ───────────────────────────────────────────────────────────────

describe("LoginSchema", () => {
  const valid = { email: "user@example.com", password: "anypassword" };

  it("accepts valid login data", () => {
    expect(LoginSchema.safeParse(valid).success).toBe(true);
  });

  it("normalises email to lowercase", () => {
    const result = LoginSchema.safeParse({
      ...valid,
      email: "User@Example.COM",
    });
    expect(result.success).toBe(true);
    if (result.success) expect(result.data.email).toBe("user@example.com");
  });

  it("rejects empty password", () => {
    expect(LoginSchema.safeParse({ ...valid, password: "" }).success).toBe(
      false,
    );
  });

  it("rejects invalid email", () => {
    expect(LoginSchema.safeParse({ ...valid, email: "bad" }).success).toBe(
      false,
    );
  });
});

// ── PasswordResetRequestSchema ────────────────────────────────────────────────

describe("PasswordResetRequestSchema", () => {
  it("accepts a valid email", () => {
    expect(
      PasswordResetRequestSchema.safeParse({ email: "user@example.com" })
        .success,
    ).toBe(true);
  });

  it("rejects an invalid email", () => {
    expect(
      PasswordResetRequestSchema.safeParse({ email: "notvalid" }).success,
    ).toBe(false);
  });
});

// ── PasswordResetConfirmSchema ────────────────────────────────────────────────

describe("PasswordResetConfirmSchema", () => {
  const valid = { token: "abc123token", newPassword: "newpassword1" };

  it("accepts valid reset data", () => {
    expect(PasswordResetConfirmSchema.safeParse(valid).success).toBe(true);
  });

  it("requires token field", () => {
    const { token: _t, ...rest } = valid;
    expect(PasswordResetConfirmSchema.safeParse(rest).success).toBe(false);
  });

  it("rejects empty token", () => {
    expect(
      PasswordResetConfirmSchema.safeParse({ ...valid, token: "" }).success,
    ).toBe(false);
  });

  it("rejects newPassword shorter than 8 characters", () => {
    expect(
      PasswordResetConfirmSchema.safeParse({ ...valid, newPassword: "short" })
        .success,
    ).toBe(false);
  });
});

// ── RefreshTokenSchema ────────────────────────────────────────────────────────

describe("RefreshTokenSchema", () => {
  it("accepts a refresh token string", () => {
    expect(
      RefreshTokenSchema.safeParse({ refreshToken: "some-opaque-token" })
        .success,
    ).toBe(true);
  });

  it("rejects empty refresh token", () => {
    expect(RefreshTokenSchema.safeParse({ refreshToken: "" }).success).toBe(
      false,
    );
  });
});

// ── UpdateDisplayNameSchema ───────────────────────────────────────────────────

describe("UpdateDisplayNameSchema", () => {
  it("accepts a valid display name", () => {
    expect(
      UpdateDisplayNameSchema.safeParse({ displayName: "CoolUser" }).success,
    ).toBe(true);
  });

  it("rejects display name longer than 50 chars", () => {
    expect(
      UpdateDisplayNameSchema.safeParse({ displayName: "a".repeat(51) })
        .success,
    ).toBe(false);
  });

  it("trims whitespace", () => {
    const result = UpdateDisplayNameSchema.safeParse({
      displayName: "  Trimmed  ",
    });
    expect(result.success).toBe(true);
    if (result.success) expect(result.data.displayName).toBe("Trimmed");
  });
});

// ── ChangePasswordSchema ──────────────────────────────────────────────────────

describe("ChangePasswordSchema", () => {
  const valid = {
    currentPassword: "old-password1",
    newPassword: "new-password1",
  };

  it("accepts valid change password data", () => {
    expect(ChangePasswordSchema.safeParse(valid).success).toBe(true);
  });

  it("rejects when new password equals current password", () => {
    expect(
      ChangePasswordSchema.safeParse({
        currentPassword: "same1234",
        newPassword: "same1234",
      }).success,
    ).toBe(false);
  });

  it("rejects new password shorter than 8 chars", () => {
    expect(
      ChangePasswordSchema.safeParse({ ...valid, newPassword: "short" })
        .success,
    ).toBe(false);
  });
});
