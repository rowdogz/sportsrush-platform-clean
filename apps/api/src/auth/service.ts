/**
 * Auth service — all business logic for identity and authentication flows.
 *
 * Rules:
 * - Functions accept DbClient + explicit params. No Hono Context here.
 * - Request metadata (IP, user agent) is captured in RequestContext.
 * - Secrets are passed explicitly — never read from env inside this module.
 * - Audit log writes are best-effort: errors are console-warned but do not
 *   propagate to callers (audit failure must not change the HTTP status code).
 * - Only SHA-256 hashes of tokens are ever persisted. Raw values go to clients.
 * - devToken fields in TokenRequestResult are populated regardless of env —
 *   the route handler decides whether to include them in the HTTP response.
 *
 * Legacy migration rule (ACL-1):
 *   is_legacy_migration = 1 AND legacy_migration_completed_at IS NULL
 *   → password login is blocked; caller receives MIGRATION_REQUIRED (401)
 *   directing them to the password reset or magic link flow.
 *   $P$ phpass hashes are never verified here.
 */

import type { DbClient } from "../lib/db";
import type { User, UserProfile, UUID, Timestamp, Role } from "@sr/types";
import type { RegisterInput, LoginInput } from "@sr/validation";
import {
  hashPassword,
  verifyPassword,
  needsRehash,
  isLegacyPhpassHash,
  generateSecureToken,
  hashToken,
  createAccessToken,
  generateRefreshToken,
  hashRefreshToken,
  isSessionExpired,
  isSessionRevoked,
  RESET_TOKEN_EXPIRY_MINUTES,
  RESET_TOKEN_BYTES,
  MAGIC_LINK_TOKEN_BYTES,
} from "@sr/auth";
import {
  ConflictError,
  AuthenticationError,
  NotFoundError,
  InternalError,
} from "../lib/errors";
import {
  type SessionInsertParams,
  type UserProfileRow,
  findUserByEmail,
  findUserById,
  findProfileByUserId,
  createUserWithProfile,
  setPasswordHash,
  setEmailVerified,
  setLegacyMigrationCompleted,
  insertSession,
  findSessionByHash,
  revokeSession,
  revokeAllUserSessions,
  rotateSession,
  insertMagicLink,
  findMagicLinkByHash,
  consumeMagicLink as dbConsumeMagicLink,
  insertPasswordResetToken,
  findPasswordResetByHash,
  consumePasswordResetToken,
  insertAuthAuditLog,
  toUser,
  toProfile,
} from "./repository";

// ── Time helpers ──────────────────────────────────────────────────────────────

function nowIso(): Timestamp {
  return new Date().toISOString() as Timestamp;
}

function addMinutes(minutes: number): Timestamp {
  return new Date(Date.now() + minutes * 60 * 1000).toISOString() as Timestamp;
}

// ── Timing defence ────────────────────────────────────────────────────────────

/**
 * A valid-format PBKDF2 hash used when the email is not found during login.
 * Passing this to verifyPassword ensures a PBKDF2 derivation still executes,
 * making the response latency comparable to a real wrong-password attempt —
 * a constant-time guard against email-existence timing side-channels.
 */
const TIMING_DUMMY_HASH =
  "$pbkdf2-sha256$600000$dGltaW5nZHVtbXlzYWx0AA$dGltaW5nZHVtbXloYXNoAA";

// ── Public types ──────────────────────────────────────────────────────────────

export type RequestContext = {
  readonly ipAddress: string | null;
  readonly userAgent: string | null;
};

export type SessionInfo = {
  readonly id: string;
  readonly expiresAt: string;
};

export type AuthResult = {
  readonly user: User;
  readonly profile: UserProfile;
  readonly accessToken: string;
  readonly refreshToken: string;
  readonly session: SessionInfo;
};

export type RefreshResult = {
  readonly accessToken: string;
  readonly refreshToken: string;
  readonly session: SessionInfo;
};

export type TokenRequestResult = {
  readonly devToken: string | null;
};

// ── Private helpers ───────────────────────────────────────────────────────────

/**
 * Best-effort audit log write. Errors are suppressed so that an audit log
 * INSERT failure never changes the HTTP response status of the calling route.
 */
async function auditLog(
  db: DbClient,
  params: {
    userId: string | null;
    eventType: string;
    ipAddress: string | null;
    userAgent: string | null;
    metadata?: Record<string, unknown>;
  },
): Promise<void> {
  try {
    await insertAuthAuditLog(db, {
      id: crypto.randomUUID(),
      userId: params.userId,
      eventType: params.eventType,
      ipAddress: params.ipAddress,
      userAgent: params.userAgent,
      metadata:
        params.metadata !== undefined ? JSON.stringify(params.metadata) : null,
      createdAt: nowIso(),
    });
  } catch {
    console.warn("[auth audit] failed to write event:", params.eventType);
  }
}

/**
 * Generate a fresh refresh token and build the corresponding SessionInsertParams.
 * Returns the raw token (for the client) alongside the insert params.
 */
async function buildSessionParams(
  userId: string,
  ctx: RequestContext,
): Promise<{ readonly raw: string; readonly params: SessionInsertParams }> {
  const { raw, hash, expiresAt } = await generateRefreshToken();
  const now = nowIso();
  const params: SessionInsertParams = {
    id: crypto.randomUUID(),
    userId,
    refreshTokenHash: hash,
    userAgent: ctx.userAgent,
    ipAddress: ctx.ipAddress,
    now,
    expiresAt: expiresAt.toISOString() as Timestamp,
  };
  return { raw, params };
}

/**
 * Insert a new session and mint an access token. Returns the three values that
 * every auth-result endpoint needs: accessToken, refreshToken, session info.
 */
async function issueTokens(
  db: DbClient,
  jwtSecret: string,
  userRow: { readonly id: string; readonly role: string },
  ctx: RequestContext,
): Promise<{
  readonly accessToken: string;
  readonly refreshToken: string;
  readonly session: SessionInfo;
}> {
  const { raw, params } = await buildSessionParams(userRow.id, ctx);
  await insertSession(db, params);
  const accessToken = await createAccessToken(
    {
      userId: userRow.id as UUID,
      role: userRow.role as Role,
      sessionId: params.id as UUID,
    },
    jwtSecret,
  );
  return {
    accessToken,
    refreshToken: raw,
    session: { id: params.id, expiresAt: params.expiresAt },
  };
}

/**
 * Return a minimal placeholder profile for users whose profile row is
 * unexpectedly absent (should not happen in normal operation but guards
 * against legacy-migration edge cases).
 */
function fallbackProfile(userId: string, now: string): UserProfileRow {
  return {
    user_id: userId,
    display_name: "",
    avatar_url: null,
    timezone: "UTC",
    created_at: now,
    updated_at: now,
  };
}

// ── Service functions ─────────────────────────────────────────────────────────

export async function register(
  db: DbClient,
  jwtSecret: string,
  input: RegisterInput,
  ctx: RequestContext,
): Promise<AuthResult> {
  const emailNormalized = input.email; // Zod already lowercased it

  const existing = await findUserByEmail(db, emailNormalized);
  if (existing !== null) {
    throw new ConflictError(
      "An account with this email address already exists",
    );
  }

  const passwordHash = await hashPassword(input.password);
  const userId = crypto.randomUUID() as UUID;
  const now = nowIso();

  await createUserWithProfile(db, {
    user: {
      id: userId,
      email: input.email,
      emailNormalized,
      passwordHash,
      role: "user",
    },
    profile: { displayName: input.displayName },
    audit: {
      id: crypto.randomUUID(),
      ipAddress: ctx.ipAddress,
      userAgent: ctx.userAgent,
      metadata: JSON.stringify({ email: emailNormalized }),
    },
    now,
  });

  const { accessToken, refreshToken, session } = await issueTokens(
    db,
    jwtSecret,
    { id: userId, role: "user" },
    ctx,
  );

  const userRow = await findUserById(db, userId);
  const profileRow = await findProfileByUserId(db, userId);

  if (userRow === null || profileRow === null) {
    throw new InternalError("Failed to retrieve newly created user");
  }

  return {
    user: toUser(userRow),
    profile: toProfile(profileRow),
    accessToken,
    refreshToken,
    session,
  };
}

export async function login(
  db: DbClient,
  jwtSecret: string,
  input: LoginInput,
  ctx: RequestContext,
): Promise<AuthResult> {
  const emailNormalized = input.email;
  const userRow = await findUserByEmail(db, emailNormalized);

  if (userRow === null) {
    // Run PBKDF2 anyway to neutralise timing-based email-enumeration attacks.
    await verifyPassword(input.password, TIMING_DUMMY_HASH);
    throw new AuthenticationError(
      "Invalid email or password",
      "INVALID_CREDENTIALS",
    );
  }

  if (userRow.is_active === 0) {
    throw new AuthenticationError(
      "This account has been suspended. Please contact support.",
      "ACCOUNT_SUSPENDED",
    );
  }

  if (
    userRow.is_legacy_migration === 1 &&
    userRow.legacy_migration_completed_at === null
  ) {
    await auditLog(db, {
      userId: userRow.id,
      eventType: "user.login_failure",
      ipAddress: ctx.ipAddress,
      userAgent: ctx.userAgent,
      metadata: { reason: "legacy_migration_required" },
    });
    throw new AuthenticationError(
      "This account requires migration. Please use the magic link or password reset flow to set a new password.",
      "MIGRATION_REQUIRED",
    );
  }

  if (userRow.password_hash === null) {
    await verifyPassword(input.password, TIMING_DUMMY_HASH);
    throw new AuthenticationError(
      "Invalid email or password",
      "INVALID_CREDENTIALS",
    );
  }

  // Safety guard: $P$ phpass hashes are never verified in this service (ACL-1).
  // This branch is only reachable if legacy_migration_completed_at was set while
  // the phpass hash was never replaced — a data-integrity violation. Return
  // MIGRATION_REQUIRED rather than the misleading INVALID_CREDENTIALS so the
  // user is directed to the correct recovery flow.
  if (isLegacyPhpassHash(userRow.password_hash)) {
    await auditLog(db, {
      userId: userRow.id,
      eventType: "user.login_failure",
      ipAddress: ctx.ipAddress,
      userAgent: ctx.userAgent,
      metadata: { reason: "phpass_hash_integrity_violation" },
    });
    throw new AuthenticationError(
      "This account requires migration. Please use the magic link or password reset flow to set a new password.",
      "MIGRATION_REQUIRED",
    );
  }

  const valid = await verifyPassword(input.password, userRow.password_hash);

  if (!valid) {
    await auditLog(db, {
      userId: userRow.id,
      eventType: "user.login_failure",
      ipAddress: ctx.ipAddress,
      userAgent: ctx.userAgent,
      metadata: { reason: "wrong_password" },
    });
    throw new AuthenticationError(
      "Invalid email or password",
      "INVALID_CREDENTIALS",
    );
  }

  if (needsRehash(userRow.password_hash)) {
    const newHash = await hashPassword(input.password);
    await setPasswordHash(db, userRow.id, newHash, nowIso());
  }

  const { accessToken, refreshToken, session } = await issueTokens(
    db,
    jwtSecret,
    userRow,
    ctx,
  );

  const profileRow = await findProfileByUserId(db, userRow.id);
  const now = nowIso();

  await auditLog(db, {
    userId: userRow.id,
    eventType: "user.login_success",
    ipAddress: ctx.ipAddress,
    userAgent: ctx.userAgent,
    metadata: { sessionId: session.id },
  });

  return {
    user: toUser(userRow),
    profile: toProfile(profileRow ?? fallbackProfile(userRow.id, now)),
    accessToken,
    refreshToken,
    session,
  };
}

export async function logout(
  db: DbClient,
  sessionId: string,
  userId: string,
): Promise<void> {
  await revokeSession(db, sessionId, nowIso());
  await auditLog(db, {
    userId,
    eventType: "user.logout",
    ipAddress: null,
    userAgent: null,
    metadata: { sessionId },
  });
}

export async function refresh(
  db: DbClient,
  jwtSecret: string,
  rawRefreshToken: string,
  ctx: RequestContext,
): Promise<RefreshResult> {
  const hash = await hashRefreshToken(rawRefreshToken);
  const sessionRow = await findSessionByHash(db, hash);

  if (sessionRow === null || isSessionRevoked(sessionRow.revoked_at)) {
    throw new AuthenticationError(
      "Refresh token is invalid or has been revoked",
      "INVALID_TOKEN",
    );
  }

  if (isSessionExpired(sessionRow.expires_at)) {
    throw new AuthenticationError(
      "Refresh token has expired. Please log in again.",
      "TOKEN_EXPIRED",
    );
  }

  const userRow = await findUserById(db, sessionRow.user_id);

  if (userRow === null || userRow.is_active === 0) {
    throw new AuthenticationError(
      "Refresh token is invalid or has been revoked",
      "INVALID_TOKEN",
    );
  }

  const { raw, params } = await buildSessionParams(userRow.id, ctx);
  await rotateSession(db, sessionRow.id, params);

  const accessToken = await createAccessToken(
    {
      userId: userRow.id as UUID,
      role: userRow.role as Role,
      sessionId: params.id as UUID,
    },
    jwtSecret,
  );

  await auditLog(db, {
    userId: userRow.id,
    eventType: "user.token_refreshed",
    ipAddress: ctx.ipAddress,
    userAgent: ctx.userAgent,
    metadata: { oldSessionId: sessionRow.id, newSessionId: params.id },
  });

  return {
    accessToken,
    refreshToken: raw,
    session: { id: params.id, expiresAt: params.expiresAt },
  };
}

export async function requestPasswordReset(
  db: DbClient,
  email: string,
): Promise<TokenRequestResult> {
  const userRow = await findUserByEmail(db, email);

  if (userRow === null) {
    return { devToken: null };
  }

  const raw = generateSecureToken(RESET_TOKEN_BYTES);
  const tokenHash = await hashToken(raw);
  const now = nowIso();

  await insertPasswordResetToken(db, {
    id: crypto.randomUUID(),
    userId: userRow.id,
    tokenHash,
    now,
    expiresAt: addMinutes(RESET_TOKEN_EXPIRY_MINUTES),
  });

  await auditLog(db, {
    userId: userRow.id,
    eventType: "user.password_reset_requested",
    ipAddress: null,
    userAgent: null,
  });

  return { devToken: raw };
}

export async function confirmPasswordReset(
  db: DbClient,
  jwtSecret: string,
  rawToken: string,
  newPassword: string,
  ctx: RequestContext,
): Promise<AuthResult> {
  const tokenHash = await hashToken(rawToken);
  const resetRow = await findPasswordResetByHash(db, tokenHash);

  if (resetRow === null) {
    throw new AuthenticationError(
      "Password reset token is invalid or has expired",
      "INVALID_TOKEN",
    );
  }

  if (resetRow.used_at !== null) {
    throw new AuthenticationError(
      "Password reset token has already been used",
      "INVALID_TOKEN",
    );
  }

  if (isSessionExpired(resetRow.expires_at)) {
    throw new AuthenticationError(
      "Password reset token has expired. Please request a new one.",
      "INVALID_TOKEN",
    );
  }

  const userRow = await findUserById(db, resetRow.user_id);
  if (userRow === null) {
    throw new InternalError("User associated with reset token not found");
  }

  const newHash = await hashPassword(newPassword);
  const now = nowIso();

  await consumePasswordResetToken(db, resetRow.id, now);
  await setPasswordHash(db, userRow.id, newHash, now);

  if (
    userRow.is_legacy_migration === 1 &&
    userRow.legacy_migration_completed_at === null
  ) {
    await setLegacyMigrationCompleted(db, userRow.id, now);
    await setEmailVerified(db, userRow.id, now);
  }

  // Revoke all existing sessions — the password was unknown/compromised.
  await revokeAllUserSessions(db, userRow.id, now);

  const { accessToken, refreshToken, session } = await issueTokens(
    db,
    jwtSecret,
    userRow,
    ctx,
  );

  const profileRow = await findProfileByUserId(db, userRow.id);

  await auditLog(db, {
    userId: userRow.id,
    eventType: "user.password_changed",
    ipAddress: ctx.ipAddress,
    userAgent: ctx.userAgent,
  });

  return {
    user: toUser(userRow),
    profile: toProfile(profileRow ?? fallbackProfile(userRow.id, now)),
    accessToken,
    refreshToken,
    session,
  };
}

export async function requestMagicLink(
  db: DbClient,
  email: string,
): Promise<TokenRequestResult> {
  const userRow = await findUserByEmail(db, email);

  if (userRow === null) {
    return { devToken: null };
  }

  const raw = generateSecureToken(MAGIC_LINK_TOKEN_BYTES);
  const tokenHash = await hashToken(raw);
  const now = nowIso();

  await insertMagicLink(db, {
    id: crypto.randomUUID(),
    userId: userRow.id,
    tokenHash,
    emailNormalized: userRow.email_normalized,
    now,
    expiresAt: addMinutes(RESET_TOKEN_EXPIRY_MINUTES),
  });

  await auditLog(db, {
    userId: userRow.id,
    eventType: "user.magic_link_requested",
    ipAddress: null,
    userAgent: null,
  });

  return { devToken: raw };
}

export async function consumeMagicLink(
  db: DbClient,
  jwtSecret: string,
  rawToken: string,
  ctx: RequestContext,
): Promise<AuthResult> {
  const tokenHash = await hashToken(rawToken);
  const linkRow = await findMagicLinkByHash(db, tokenHash);

  if (linkRow === null) {
    throw new AuthenticationError(
      "Magic link token is invalid or has expired",
      "INVALID_TOKEN",
    );
  }

  if (linkRow.used_at !== null) {
    throw new AuthenticationError(
      "Magic link has already been used",
      "INVALID_TOKEN",
    );
  }

  if (isSessionExpired(linkRow.expires_at)) {
    throw new AuthenticationError(
      "Magic link has expired. Please request a new one.",
      "INVALID_TOKEN",
    );
  }

  const userRow = await findUserById(db, linkRow.user_id);
  if (userRow === null) {
    throw new InternalError("User associated with magic link not found");
  }

  const now = nowIso();

  await dbConsumeMagicLink(db, linkRow.id, now);
  await setEmailVerified(db, userRow.id, now);

  if (
    userRow.is_legacy_migration === 1 &&
    userRow.legacy_migration_completed_at === null
  ) {
    await setLegacyMigrationCompleted(db, userRow.id, now);
  }

  const { accessToken, refreshToken, session } = await issueTokens(
    db,
    jwtSecret,
    userRow,
    ctx,
  );

  const profileRow = await findProfileByUserId(db, userRow.id);

  await auditLog(db, {
    userId: userRow.id,
    eventType: "user.magic_link_used",
    ipAddress: ctx.ipAddress,
    userAgent: ctx.userAgent,
  });

  return {
    user: toUser(userRow),
    profile: toProfile(profileRow ?? fallbackProfile(userRow.id, now)),
    accessToken,
    refreshToken,
    session,
  };
}

export async function getMe(
  db: DbClient,
  userId: string,
): Promise<{ readonly user: User; readonly profile: UserProfile }> {
  const userRow = await findUserById(db, userId);
  if (userRow === null) {
    throw new NotFoundError("User not found");
  }
  const profileRow = await findProfileByUserId(db, userId);
  if (profileRow === null) {
    throw new NotFoundError("User profile not found");
  }
  return { user: toUser(userRow), profile: toProfile(profileRow) };
}
