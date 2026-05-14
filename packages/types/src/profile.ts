import type { UUID, Timestamp } from "./common";

/**
 * Public-facing user profile. Owned by the Users & Profiles domain.
 * Distinct from User (owned by Identity & Auth).
 *
 * display_name is what appears on leaderboards and league rankings.
 * Rate-limited: max 3 changes per 24 hours (prevents identity confusion).
 *
 * See SPORTSRUSH_2_DOMAIN_MODEL.md — Domain 2 (Users & Profiles)
 */
export type UserProfile = {
  readonly userId: UUID;
  readonly displayName: string;
  readonly avatarUrl: string | null;
  readonly timezone: string; // IANA timezone string, e.g. 'Europe/London'
  readonly createdAt: Timestamp;
  readonly updatedAt: Timestamp;
};

/**
 * Per-user notification and display preferences.
 */
export type UserPreferences = {
  readonly userId: UUID;
  readonly defaultCompetitionId: UUID | null;
  readonly notifyOnResults: boolean;
  readonly notifyOnRoundOpen: boolean;
  readonly notifyOnMonthlyWinner: boolean;
};

/**
 * Push notification platform identifier.
 */
export type PushPlatform = "ios" | "android" | "web";

/**
 * A push notification token for a specific user device.
 * Inactive tokens (not seen in > 90 days) are purged by a weekly job.
 */
export type PushToken = {
  readonly id: UUID;
  readonly userId: UUID;
  readonly token: string;
  readonly platform: PushPlatform;
  readonly createdAt: Timestamp;
  readonly lastSeenAt: Timestamp;
  readonly active: boolean;
};
