import type { UUID, Timestamp } from "./common";

/**
 * Notification delivery channel.
 */
export type NotificationChannel = "push" | "email";

/**
 * Notification template identifier.
 * Each value maps to a template in the Notifications domain.
 */
export type NotificationType =
  | "results_available" // match results have been published
  | "round_open" // a new prediction round is open
  | "monthly_winner" // monthly winner has been declared
  | "league_member_joined" // someone joined your league
  | "prediction_locked" // predictions are closing soon
  | "welcome" // new user registration
  | "email_verification" // verify email address
  | "password_reset"; // password reset link

/**
 * A stored log of a notification dispatch attempt.
 * Not a queue message — this is the persisted record of what was sent.
 *
 * deliveredAt is set on confirmed delivery (push receipt, email bounce callback).
 * failedAt + failureReason are set if dispatch fails after all retries.
 */
export type NotificationLog = {
  readonly id: UUID;
  readonly userId: UUID;
  readonly channel: NotificationChannel;
  readonly notificationType: NotificationType;
  readonly title: string;
  readonly body: string;
  readonly sentAt: Timestamp;
  readonly deliveredAt: Timestamp | null;
  readonly failedAt: Timestamp | null;
  readonly failureReason: string | null;
};
