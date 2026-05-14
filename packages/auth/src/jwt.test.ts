import { describe, it, expect } from "vitest";
import {
  createAccessToken,
  verifyAccessToken,
  TokenExpiredError,
  TokenInvalidError,
} from "./jwt";

const SECRET = "test-secret-at-least-32-bytes-long!!";
const validClaims = {
  userId: "00000000-0000-0000-0000-000000000001",
  role: "user" as const,
  sessionId: "00000000-0000-0000-0000-000000000002",
};

describe("createAccessToken / verifyAccessToken", () => {
  it("creates a token that verifies successfully", async () => {
    const token = await createAccessToken(validClaims, SECRET);
    const payload = await verifyAccessToken(token, SECRET);
    expect(payload.userId).toBe(validClaims.userId);
    expect(payload.role).toBe(validClaims.role);
    expect(payload.sessionId).toBe(validClaims.sessionId);
  });

  it("payload includes exp and iat", async () => {
    const token = await createAccessToken(validClaims, SECRET);
    const payload = await verifyAccessToken(token, SECRET);
    expect(typeof payload.exp).toBe("number");
    expect(typeof payload.iat).toBe("number");
    expect(payload.exp).toBeGreaterThan(payload.iat);
  });

  it("throws TokenInvalidError for a tampered token", async () => {
    const token = await createAccessToken(validClaims, SECRET);
    const tampered = token.slice(0, -5) + "XXXXX";
    await expect(verifyAccessToken(tampered, SECRET)).rejects.toThrowError(
      TokenInvalidError,
    );
  });

  it("throws TokenInvalidError for a token signed with the wrong secret", async () => {
    const token = await createAccessToken(validClaims, SECRET);
    await expect(
      verifyAccessToken(token, "wrong-secret-that-is-also-32-bytes!!"),
    ).rejects.toThrowError(TokenInvalidError);
  });

  it("throws TokenInvalidError for a completely invalid string", async () => {
    await expect(verifyAccessToken("not.a.jwt", SECRET)).rejects.toThrowError(
      TokenInvalidError,
    );
  });
});

describe("TokenExpiredError", () => {
  it("is an instance of Error", () => {
    const err = new TokenExpiredError();
    expect(err).toBeInstanceOf(Error);
    expect(err.name).toBe("TokenExpiredError");
  });
});

describe("TokenInvalidError", () => {
  it("is an instance of Error", () => {
    const err = new TokenInvalidError();
    expect(err).toBeInstanceOf(Error);
    expect(err.name).toBe("TokenInvalidError");
  });

  it("includes a detail message when provided", () => {
    const err = new TokenInvalidError("bad signature");
    expect(err.message).toContain("bad signature");
  });
});
