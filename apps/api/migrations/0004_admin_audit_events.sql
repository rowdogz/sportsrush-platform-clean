-- Migration 0004: Admin/domain audit events
--
-- Append-only audit trail for admin mutations on domain entities.

PRAGMA foreign_keys = ON;

CREATE TABLE audit_events (
  id TEXT PRIMARY KEY,
  actor_user_id TEXT REFERENCES users(id) ON DELETE SET NULL,
  action TEXT NOT NULL,
  target_type TEXT NOT NULL,
  target_id TEXT,
  before_metadata TEXT,
  after_metadata TEXT,
  created_at TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ', 'now')),
  CHECK (length(trim(id)) > 0),
  CHECK (length(trim(action)) > 0),
  CHECK (length(trim(target_type)) > 0)
);

CREATE INDEX idx_audit_events_actor_user_id
  ON audit_events (actor_user_id);

CREATE INDEX idx_audit_events_action
  ON audit_events (action);

CREATE INDEX idx_audit_events_target
  ON audit_events (target_type, target_id);

CREATE INDEX idx_audit_events_created_at
  ON audit_events (created_at);
