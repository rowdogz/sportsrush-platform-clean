import type { UUID, Timestamp } from "./common";

/**
 * Entity types that generate audit records.
 */
export type AuditEntityType =
  | "match"
  | "prediction"
  | "prediction_override"
  | "league"
  | "league_member"
  | "user"
  | "competition"
  | "scoring_config"
  | "payment"
  | "team_alias";

/**
 * All auditable actions in the platform.
 * Every value here maps to at least one write operation in the system.
 */
export type AuditAction =
  // Fixtures & Results
  | "result.entered"
  | "result.corrected"
  | "match.created"
  | "match.rescheduled"
  | "match.voided"
  | "match.status_changed"
  // Predictions
  | "prediction.override_opened"
  | "prediction.override_locked"
  // Leagues
  | "league.created"
  | "league.deleted"
  | "league_member.added"
  | "league_member.removed"
  // Users
  | "user.role_changed"
  | "user.force_logged_out"
  // Config
  | "scoring_config.updated"
  // External
  | "team_alias.created"
  | "team_alias.deleted";

/**
 * An audit log entry. Append-only — rows are never updated or deleted.
 *
 * Written within the same database transaction as the action it records.
 * An action without an audit record is an undetectable gap — this is
 * a strict consistency requirement (SPORTSRUSH_2_DOMAIN_MODEL.md consistency tiers).
 *
 * beforeValue / afterValue are JSON snapshots of the entity state.
 * For result corrections, these capture previous and new scores.
 * For deletes, afterValue is null. For creates, beforeValue is null.
 *
 * `reason` is mandatory for result corrections and prediction overrides;
 * optional for other actions.
 *
 * See SPORTSRUSH_CANONICAL_RULES.md §1.8 (result correction audit)
 *     SPORTSRUSH_CANONICAL_RULES.md §3.5 (admin prediction override audit)
 */
export type AuditEvent = {
  readonly id: UUID;
  readonly action: AuditAction;
  readonly entityType: AuditEntityType;
  readonly entityId: UUID;
  readonly performedBy: UUID; // admin user_id; the actor who triggered the action
  readonly performedAt: Timestamp;
  readonly beforeValue: unknown; // JSON snapshot; null for create actions
  readonly afterValue: unknown; // JSON snapshot; null for delete actions
  readonly reason: string | null; // required for corrections/overrides; optional otherwise
};
