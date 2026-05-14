import type { UUID } from "@sr/types";
import type { BaseEvent } from "./base";

/**
 * Emitted after a new user registers (email + password).
 * Triggers: email verification send (synchronous in handler)
 *           Analytics: user_registered event
 */
export type UserRegisteredEvent = BaseEvent & {
  readonly eventType: "user.registered";
  readonly userId: UUID;
  readonly email: string;
  readonly displayName: string;
  readonly isLegacyMigration: boolean; // true if imported from WordPress
};

/**
 * Emitted after a user clicks their email verification link.
 */
export type UserEmailVerifiedEvent = BaseEvent & {
  readonly eventType: "user.email_verified";
  readonly userId: UUID;
};

/**
 * Emitted on successful login. Used for security monitoring and analytics.
 * ipAddress may be null if the request came through a proxy with no forwarding header.
 */
export type UserLoggedInEvent = BaseEvent & {
  readonly eventType: "user.logged_in";
  readonly userId: UUID;
  readonly sessionId: UUID;
  readonly ipAddress: string | null;
  readonly userAgent: string | null;
};

/**
 * Emitted when a session is explicitly revoked (logout or force-logout by admin).
 */
export type UserLoggedOutEvent = BaseEvent & {
  readonly eventType: "user.logged_out";
  readonly userId: UUID;
  readonly sessionId: UUID;
  readonly initiatedBy: "user" | "admin";
};

/**
 * Emitted after a successful password change or reset.
 * All existing sessions are revoked after a password change.
 */
export type UserPasswordChangedEvent = BaseEvent & {
  readonly eventType: "user.password_changed";
  readonly userId: UUID;
  readonly triggeredBy: "user_change" | "reset_token";
};

/**
 * Emitted after a user changes their display name.
 * Rate-limited to 3 changes per 24 hours (enforced in service).
 */
export type UserDisplayNameChangedEvent = BaseEvent & {
  readonly eventType: "user.display_name_changed";
  readonly userId: UUID;
  readonly previousDisplayName: string;
  readonly newDisplayName: string;
};
