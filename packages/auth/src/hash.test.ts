import { describe, it, expect } from "vitest";
import {
  hashPassword,
  verifyPassword,
  needsRehash,
  isLegacyPhpassHash,
  generateSecureToken,
  hashToken,
} from "./hash";
import { PBKDF2_ITERATIONS } from "./constants";

// ── hashPassword / verifyPassword ─────────────────────────────────────────────

describe("hashPassword / verifyPassword", () => {
  it("produces a hash that verifies against the original password", async () => {
    const hash = await hashPassword("correct-horse-battery-staple");
    expect(await verifyPassword("correct-horse-battery-staple", hash)).toBe(
      true,
    );
  });

  it("returns false for an incorrect password", async () => {
    const hash = await hashPassword("correct-horse-battery-staple");
    expect(await verifyPassword("wrong-password", hash)).toBe(false);
  });

  it("produces different hashes for the same password (random salt)", async () => {
    const h1 = await hashPassword("same-password");
    const h2 = await hashPassword("same-password");
    expect(h1).not.toBe(h2);
    // But both verify correctly
    expect(await verifyPassword("same-password", h1)).toBe(true);
    expect(await verifyPassword("same-password", h2)).toBe(true);
  });

  it("hash includes the iteration count", async () => {
    const hash = await hashPassword("mypassword");
    expect(hash).toContain(`$${PBKDF2_ITERATIONS}$`);
  });

  it("hash starts with the expected prefix", async () => {
    const hash = await hashPassword("mypassword");
    expect(hash).toMatch(/^\$pbkdf2-sha256\$/);
  });

  it("returns false for a malformed hash string", async () => {
    expect(await verifyPassword("password", "not-a-valid-hash")).toBe(false);
    expect(await verifyPassword("password", "")).toBe(false);
    expect(await verifyPassword("password", "$unknown$algo$data")).toBe(false);
  });
}, 60_000); // PBKDF2 at 600k iterations takes a few seconds per call in test

// ── needsRehash ───────────────────────────────────────────────────────────────

describe("needsRehash", () => {
  it("returns false for a current-iteration hash", async () => {
    const hash = await hashPassword("testpassword");
    expect(needsRehash(hash)).toBe(false);
  }, 30_000);

  it("returns true for a hash with fewer iterations", () => {
    const oldHash = `$pbkdf2-sha256$100000$somesalt$somehash`;
    expect(needsRehash(oldHash)).toBe(true);
  });

  it("returns true for a malformed hash", () => {
    expect(needsRehash("$P$somephpasshash")).toBe(true);
    expect(needsRehash("garbage")).toBe(true);
  });
});

// ── isLegacyPhpassHash ────────────────────────────────────────────────────────

describe("isLegacyPhpassHash", () => {
  it("returns true for a WordPress phpass hash", () => {
    expect(isLegacyPhpassHash("$P$BhashBase64data")).toBe(true);
  });

  it("returns false for a PBKDF2 hash", () => {
    expect(isLegacyPhpassHash("$pbkdf2-sha256$600000$salt$hash")).toBe(false);
  });

  it("returns false for an empty string", () => {
    expect(isLegacyPhpassHash("")).toBe(false);
  });
});

// ── generateSecureToken ───────────────────────────────────────────────────────

describe("generateSecureToken", () => {
  it("returns a hex string of the correct length", () => {
    const token = generateSecureToken(32);
    expect(token).toMatch(/^[0-9a-f]+$/);
    expect(token.length).toBe(64); // 32 bytes = 64 hex chars
  });

  it("produces unique tokens on each call", () => {
    const t1 = generateSecureToken(32);
    const t2 = generateSecureToken(32);
    expect(t1).not.toBe(t2);
  });
});

// ── hashToken ─────────────────────────────────────────────────────────────────

describe("hashToken", () => {
  it("returns a 64-character hex SHA-256 hash", async () => {
    const hash = await hashToken("my-raw-token");
    expect(hash).toMatch(/^[0-9a-f]{64}$/);
  });

  it("is deterministic for the same input", async () => {
    const h1 = await hashToken("same-token");
    const h2 = await hashToken("same-token");
    expect(h1).toBe(h2);
  });

  it("produces different hashes for different tokens", async () => {
    const h1 = await hashToken("token-a");
    const h2 = await hashToken("token-b");
    expect(h1).not.toBe(h2);
  });
});
