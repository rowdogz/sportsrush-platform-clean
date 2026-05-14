# SportsRush Database Schema

This document tracks the D1 schema owned by `apps/api/migrations`.

## Migration order

| Migration | Purpose |
|---|---|
| `0001_foundation.sql` | Baseline migration and foreign-key enforcement policy. |
| `0008_competitions_teams_fixtures_results.sql` | Competitions, teams, fixtures and result-correction schema for PR-08. |

> Applied migrations must not be edited. Add a new numbered migration for every schema change.

## PR-08: competitions, teams and fixtures

### `sports`

Top-level sport catalogue.

Key fields:
- `id` UUID `TEXT` primary key.
- `slug` unique stable key.
- `name`.
- optional `legacy_id`.

### `competitions`

Competition/tournament within a sport.

Key rules:
- Belongs to `sports`.
- `UNIQUE (sport_id, slug)` prevents duplicate competition slugs within a sport.
- Optional `country_code`.
- Supports soft activation through `is_active`.

### `seasons`

Competition season container.

Key rules:
- Belongs to `competitions`.
- Unique slug within competition.
- Supports active season tracking.
- Optional legacy references for migration.

### `teams`

Canonical team records.

Key rules:
- Belongs to a sport.
- `slug` unique within sport.
- Separate `display_name` and `short_name` support UI flexibility.
- Optional `legacy_id`.

### `team_aliases`

Alias mapping layer for scraper/import resilience.

Important SportsRush rule:
- Team aliases must be strict enough to prevent silent fixture mismatches.

Key rules:
- `normalized_alias` is globally unique per sport.
- Aliases are linked directly to canonical teams.
- `source` tracks where the alias originated.
- `priority` supports deterministic matching in future ingestion flows.
- `is_active` allows retiring aliases safely.

### `competition_teams`

Season participation table.

Purpose:
- Tracks which teams belong to which competition season.
- Allows future support for promotion/relegation and changing league structures.

### `rounds`

Canonical round metadata.

Important SportsRush rule:
- `round` and `round_name` are first-class concepts because predictions pages depend heavily on them.

Key rules:
- Unique round number/code within season.
- Stable `display_order`.
- Optional scheduling windows.

### `fixtures`

Core fixture/match table.

Important SportsRush rules implemented:
- Duplicate fixture prevention.
- First-class round metadata.
- Auditable result lifecycle.
- Multiple fixture states.

Supported statuses:
- `scheduled`
- `postponed`
- `abandoned`
- `void`
- `cancelled`
- `completed`

Key rules:
- Home/away teams cannot be the same.
- Completed fixtures must contain scores.
- Duplicate fixtures prevented by:
  - `(season_id, home_team_id, away_team_id, scheduled_at)`
- External imports protected through:
  - `UNIQUE (external_source, external_id)`
- `round`, `round_name`, `round_order` are deliberately denormalised for stability.
- Legacy IDs are retained for migration and reconciliation.

### `result_corrections`

Append-only audit trail for result edits.

Important SportsRush rule:
- Result changes must always be auditable.
- Result correction history must not be deleted automatically with fixtures.

Key rules:
- Stores both previous and corrected values.
- Correction reason is mandatory.
- Supports user attribution.
- Never overwrites historical correction entries.
- Fixture deletion is restricted once corrections exist.

## Timestamp policy

All timestamps are stored as ISO 8601 `TEXT`.

Example:

```text
2026-05-14T12:00:00.000Z
```

## Primary key policy

All primary keys use UUID `TEXT` identifiers unless a future migration documents a strong alternative rationale.
