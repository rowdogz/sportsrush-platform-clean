# SPORTSRUSH_2_DOMAIN_MODEL.md

## SportsRush 2.0 — Bounded Contexts, Domain Model & Service Design

**Sources:** `SPORTSRUSH_CANONICAL_RULES.md`, `SPORTSRUSH_2_TARGET_ARCHITECTURE.md`, `VERIFIED_FINDINGS.md`  
**Purpose:** Define the bounded contexts, data ownership, event contracts, and consistency requirements before implementation begins. This document is the contract between domains. No domain may reach into another domain's data store directly.

---

## READING GUIDE

**Bounded context** — a logical boundary within which a concept has a single, unambiguous meaning. The word "score" means different things in the Scoring Engine (points earned per match) and in Fixtures & Results (the number of goals). This document clarifies those boundaries.

**Data ownership** — each piece of data has exactly one domain that owns it and is the authoritative source of truth for it. Other domains may hold a read-only projection or cached copy but must not write to data they do not own.

**Events** — asynchronous messages emitted when something significant happens. Consuming domains react to events without coupling themselves to the emitting domain's internals.

---

## CONSISTENCY TIERS

Before defining domains, the consistency requirements are established. These govern architecture decisions throughout.

### Strict Consistency Required (synchronous, transactional)

These operations must be atomic. A partial write is worse than a failure.

| Operation                               | Reason                                                                              |
| --------------------------------------- | ----------------------------------------------------------------------------------- |
| Prediction save + lock check            | A prediction accepted after kick-off is a data integrity violation                  |
| Payment event + entitlement grant       | A paid user who cannot access their league is a support and revenue incident        |
| Result write + status update            | A result without a matching status change causes scoring to fire on incomplete data |
| Audit log write + the action it records | An action without a record is an undetectable gap                                   |

### Eventual Consistency Acceptable (event-driven, async)

These can be seconds to minutes behind and the system remains correct.

| Operation                    | Maximum acceptable lag          | Reason                                            |
| ---------------------------- | ------------------------------- | ------------------------------------------------- |
| Rankings update after result | 30 seconds                      | Leaderboard staleness by seconds is imperceptible |
| Push notification dispatch   | 5 minutes                       | Delivery timing is not business-critical          |
| Ranking snapshot write       | 2 minutes                       | Snapshots are for performance, not correctness    |
| Analytics event processing   | 24 hours                        | Reporting is not real-time                        |
| Monthly winner calculation   | 1 hour (automated 1st of month) | No user depends on sub-minute accuracy            |

---

## SHARED ENTITIES & CANONICAL IDs

### Canonical ID Strategy

All domain entities use **UUID v4** as their primary identifier. This avoids:

- Sequential ID enumeration attacks (vs integer PKs)
- Cross-environment ID collisions during migration
- Coupling to insertion order

**Exception:** During migration from WordPress, a `legacy_id` column holds the original WordPress integer ID (e.g. `wp_user_id`) for mapping purposes. The legacy ID is read-only and deprecated once migration is complete.

### Shared Entity Definitions

The following entities are referenced across multiple domains. Their canonical definition lives in one domain (the owner); other domains hold a foreign key reference only.

| Entity        | Owner Domain       | Canonical ID                               | Referenced By                                    |
| ------------- | ------------------ | ------------------------------------------ | ------------------------------------------------ |
| `User`        | Identity & Auth    | `user_id: UUID`                            | All domains                                      |
| `Competition` | Competitions       | `competition_id: UUID`                     | Fixtures, Rankings, Predictions, Private Leagues |
| `Match`       | Fixtures & Results | `match_id: UUID`                           | Predictions, Scoring, Rankings                   |
| `Team`        | Fixtures & Results | `team_id: UUID`                            | Fixtures, External Integrations                  |
| `Round`       | Fixtures & Results | `(competition_id, round_number)` composite | Predictions, Rankings                            |
| `League`      | Private Leagues    | `league_id: UUID`                          | Payments, Rankings, Notifications                |
| `Prediction`  | Predictions        | `prediction_id: UUID`                      | Scoring                                          |
| `MatchScore`  | Scoring Engine     | `match_score_id: UUID`                     | Rankings                                         |

### Value Objects (no ID, defined by their values)

- `ScoringConfig` — exact points, toto points, home bonus, away bonus, diff bonus, joker multiplier. Owned by Scoring Engine. Versioned (a config version ID is stored so historical recalculations know which config was active at the time).
- `MonthWindow` — `{ year: int, month: int }`. Used by Rankings and Notifications for monthly winner logic.
- `LockTime` — `{ play_date: timestamptz, cutoff_minutes: int }`. Derived by Predictions domain from match data.

---

## DOMAIN 1 — IDENTITY & AUTHENTICATION

### Purpose

The authoritative source of user identity, session management, and authentication tokens. The gatekeeper for all protected operations.

### Responsibilities

- User registration (email + password, OAuth/social)
- Email verification
- Password hashing and verification (bcrypt / Argon2id)
- Legacy phpass hash verification during migration + immediate rehash
- Access token issuance (short-lived JWT, 15 min)
- Refresh token lifecycle (long-lived, HTTP-only cookie on web, SecureStore on mobile, 30-day expiry)
- Refresh token rotation (new token on each refresh, previous invalidated)
- Session revocation (logout, admin force-logout)
- Password reset (signed, short-lived, single-use reset token)
- Rate limiting on authentication endpoints
- Role assignment storage (`user`, `admin`, `superadmin`)

### Data Ownership

```
users           → id, email, email_verified_at, password_hash, role,
                  created_at, legacy_wp_user_id (nullable)
sessions        → id, user_id, refresh_token_hash, created_at,
                  expires_at, revoked_at, user_agent, ip_address
password_resets → id, user_id, token_hash, expires_at, used_at
oauth_accounts  → id, user_id, provider, provider_user_id, created_at
```

### APIs Exposed

```
POST /auth/register
POST /auth/verify-email
POST /auth/login
POST /auth/refresh
POST /auth/logout
POST /auth/password-reset/request
POST /auth/password-reset/confirm
GET  /auth/oauth/{provider}        → redirect to OAuth provider
GET  /auth/oauth/{provider}/callback
```

### Events Emitted

```
user.registered        → { user_id, email, registered_at }
user.email_verified    → { user_id, verified_at }
user.logged_in         → { user_id, session_id, ip_address }
user.logged_out        → { user_id, session_id }
user.password_changed  → { user_id, changed_at }
```

### Dependencies on Other Domains

- **None.** Identity & Auth is a foundational domain with no upstream dependencies. All other domains depend on it.

### Security Considerations

- Timing-safe comparison for all token and password comparisons
- No user-identifiable information in JWT payload beyond `user_id` and `role`
- Refresh tokens are hashed before storage (if stolen from DB, useless)
- Account lockout after N failed attempts (configurable, default: 5 attempts → 15-minute lockout)
- Cloudflare Turnstile (or equivalent) on registration to prevent bot registrations

### Scaling Considerations

- Sessions table is append-heavy; consider partitioning by `created_at` monthly for large user bases
- Token validation is stateless (JWT signature check) — no DB read on most authenticated requests
- Only refresh operations hit the database; short-lived access tokens scale horizontally without DB reads

---

## DOMAIN 2 — USERS & PROFILES

### Purpose

Everything about a user beyond their identity credentials. Preferences, display names, push notification registration, and public-facing profile data.

### Responsibilities

- Display name management (shown on leaderboards, private leagues)
- User preferences (default competition, timezone, notification settings)
- Push token registration and lifecycle (Expo push tokens, APNs, FCM)
- Avatar / profile image management
- User search (admin use: find a user by name or email)

### Data Ownership

```
user_profiles   → user_id (FK → users), display_name, avatar_url,
                  timezone, created_at, updated_at
user_preferences → user_id, default_competition_id, notification_results,
                   notification_round_open, notification_monthly_winner
push_tokens     → id, user_id, token, platform (ios/android/web),
                  created_at, last_seen_at, active
```

### APIs Exposed

```
GET    /users/me                   → current user's profile
PATCH  /users/me                   → update display name, preferences, timezone
GET    /users/{id}/profile         → public profile (display name, avatar only)
POST   /users/me/push-token        → register or refresh a push token
DELETE /users/me/push-token        → deregister on logout
```

### Events Emitted

```
user.display_name_changed   → { user_id, old_name, new_name }
user.push_token_registered  → { user_id, platform }
```

### Dependencies on Other Domains

- **Identity & Auth** — reads `user_id` from the authenticated session; does not own credentials

### Security Considerations

- Display name changes are rate-limited (max 3 changes per 24 hours) to prevent identity confusion on leaderboards
- Avatar uploads are validated (file type, size, malware scan) before storage in Cloudflare R2
- Admin can view full profile including email; regular users can only see display name and avatar of other users

### Scaling Considerations

- Profile reads are frequent (embedded in every rankings row) — cache display names in Redis with a 5-minute TTL, invalidated on `user.display_name_changed`
- Push token table should be indexed on `user_id` and purged of inactive tokens (not seen in > 90 days) by a weekly job

---

## DOMAIN 3 — COMPETITIONS

### Purpose

The authoritative registry of all competitions (sport leagues / match types) that SportsRush operates predictions for. Controls visibility and metadata.

### Responsibilities

- Competition creation and metadata management (name, sport type, description, logo)
- Visibility control (`public`, `private`, `archived`)
- Competition ordering for display
- Linking competitions to private leagues (competition is the matchtype scope for a league)
- Providing the competition list to filters across the platform

### Data Ownership

```
competitions    → id, name, sport (rugby_league/football/etc),
                  description, logo_url, visibility (enum),
                  display_order, created_at, updated_at
```

### APIs Exposed

```
GET  /competitions                 → list visible competitions (public)
GET  /competitions/{id}            → competition detail
POST /admin/competitions           → create competition (admin only)
PATCH /admin/competitions/{id}     → update competition (admin only)
```

### Events Emitted

```
competition.created    → { competition_id, name, visibility }
competition.updated    → { competition_id, changed_fields }
competition.archived   → { competition_id }
```

### Dependencies on Other Domains

- **None.** Competitions is a near-foundational domain.

### Security Considerations

- Only admins can create, edit, or archive competitions
- Archived competitions remain accessible for historical rankings queries; they do not appear in active filters

### Scaling Considerations

- Competition list is a small, infrequently changing dataset — cache aggressively in Redis (1-hour TTL), invalidated on any `competition.*` event
- Maximum expected competitions: dozens, not thousands

---

## DOMAIN 4 — FIXTURES & RESULTS

### Purpose

The authoritative source of truth for match scheduling (when a match happens, between which teams) and match outcomes (what the score was). This domain is the initiator of the entire scoring and rankings cascade.

### Responsibilities

- Match creation (admin or scraper-initiated)
- Match scheduling: `play_date`, `round`, `round_name` (optional), `competition_id`
- Team management (`teams` table: names, logos)
- Match status lifecycle: `scheduled → completed | postponed | abandoned | void`
- Result entry: writing `home_score`, `away_score` (admin or scraper)
- Result correction: overwriting an incorrect result with an audit trail
- Duplicate match prevention (unique constraint on home+away+competition+date)
- Exposing upcoming fixtures and past results to other domains

### Data Ownership

```
matches          → id, competition_id, home_team_id, away_team_id,
                   play_date (timestamptz, UTC), round (int, required),
                   round_name (varchar, nullable), status (enum),
                   home_score (nullable), away_score (nullable),
                   created_at, updated_at, created_by
teams            → id, name, short_name, logo_url, created_at
result_corrections → id, match_id, previous_home, previous_away,
                     corrected_home, corrected_away, corrected_by,
                     corrected_at, reason (required)
```

### APIs Exposed

```
GET  /fixtures                      → upcoming matches (paginated, filterable by competition/round)
GET  /fixtures/{id}                 → single match detail
GET  /results                       → past matches with scores (filterable)
GET  /results/{id}                  → single result detail
POST /admin/fixtures                → create match (admin only)
PATCH /admin/fixtures/{id}          → update match details (admin only)
POST /admin/results/{id}            → enter or correct result (admin only)
GET  /admin/results/{id}/history    → correction history for a match
```

### Events Emitted

```
match.created         → { match_id, competition_id, home_team_id, away_team_id,
                          play_date, round }
match.rescheduled     → { match_id, old_play_date, new_play_date }
match.status_changed  → { match_id, old_status, new_status }
result.published      → { match_id, competition_id, home_score, away_score,
                          play_date, round }
result.corrected      → { match_id, competition_id, previous_home, previous_away,
                          corrected_home, corrected_away, corrected_by }
match.voided          → { match_id, competition_id, reason }
```

### Dependencies on Other Domains

- **Competitions** — `competition_id` is a foreign key; competition must exist before a match is created
- **External Integrations** — receives normalised `ScraperResult` objects from the External Integrations domain; applies alias resolution before writing

### Anti-Corruption Layer

The boundary between External Integrations and Fixtures & Results is an explicit translation layer:

- Input type: `ScraperResult { external_home_name, external_away_name, external_competition_name, play_date, home_score, away_score }`
- Translation: alias lookup resolves external names to `team_id` values; external competition name resolves to `competition_id`
- Output: only if all IDs are resolved does the data cross the boundary into the Fixtures domain
- If translation fails: the `ScraperResult` is written to `scraper_unresolved_aliases` and the Fixtures domain is not touched

### Security Considerations

- Result entry and correction are admin-only operations; every write goes through role assertion
- Result corrections require a non-empty `reason` field — silent corrections are not permitted
- Bulk fixture import (CSV or API) is admin-only and validates every row before any are written (all-or-nothing transaction)

### Scaling Considerations

- Upcoming fixtures endpoint is read-heavy during the pre-match window; cache in Redis with a 5-minute TTL, invalidated on `match.created` or `match.rescheduled`
- `result_corrections` is append-only and small; no special scaling needed
- Result publication triggers a downstream cascade; the Fixtures domain emits the event and returns immediately — it does not wait for scoring to complete

---

## DOMAIN 5 — PREDICTIONS

### Purpose

The single point of authority for whether a prediction is accepted or rejected. Owns the user's prediction data. Enforces the lock rule server-side. The Predictions domain is the bridge between user intent and the Scoring Engine.

### Responsibilities

- Accepting or rejecting prediction submissions based on the server-side lock rule
- Storing `home_score`, `away_score`, and `joker` (if enabled) per user per match
- Providing each user's own predictions for display
- Joker validation: enforcing the jokers-per-round limit at submission time
- Exposing predictions for consumption by the Scoring Engine after a result is published
- Admin override: re-opening predictions for a specific match (writes to `prediction_overrides`)

### Data Ownership

```
predictions          → id, user_id, match_id, home_score (nullable),
                       away_score (nullable), joker (bool, default false),
                       created_at, updated_at, locked_at (when lock fired)
prediction_overrides → id, match_id, override_type (open/lock),
                       set_by (admin user_id), reason, created_at
```

### Lock Rule (canonical, per SPORTSRUSH_CANONICAL_RULES.md §3.1)

Before accepting any prediction write:

```
cutoff = match.play_date - INTERVAL '{lock_minutes} MINUTES'
current_time = NOW() [UTC]
if current_time >= cutoff: REJECT with 423 Locked
```

`lock_minutes` is read from `scoring_config.prediction_lock_minutes` (default: 30). Configurable without a code change.

The lock check and the prediction write are in the same database transaction. The lock cannot be bypassed by a race condition.

### APIs Exposed

```
GET  /predictions                  → current user's predictions for a round
                                     (?competition_id=&round=)
POST /predictions/{match_id}       → save or update a prediction (auth required)
                                     body: { home_score, away_score, joker? }
GET  /predictions/{match_id}       → current user's prediction for one match
GET  /admin/predictions/{match_id} → all users' predictions for a match (post-kickoff only)
POST /admin/predictions/override    → re-open or lock a match's predictions (admin only)
```

### Events Emitted

```
prediction.saved      → { prediction_id, user_id, match_id, joker, saved_at }
prediction.locked     → { match_id, locked_at }   [emitted at lock time for notifications]
```

### Dependencies on Other Domains

- **Identity & Auth** — `user_id` from authenticated session
- **Fixtures & Results** — reads `match.play_date` and `match.status` to enforce the lock; will not accept predictions for `completed`, `void`, or `abandoned` matches
- **Private Leagues** — does NOT enforce league membership at prediction time; membership is enforced at the rankings display layer (predictions are per-competition, not per-league)

### Security Considerations

- The lock check is always server-side — the client's stated current time is never trusted
- The `user_id` is always taken from the authenticated session JWT, never from the request body
- `POST /predictions/{match_id}` has a nonce requirement (CSRF protection) on web clients
- The Predictions API does not expose other users' predictions before kick-off — this would allow copying behaviour
- Admin `GET /admin/predictions/{match_id}` is only available for matches with status `completed` or later (preventing admin from seeing live predictions and passing information)

### Scaling Considerations

- Predictions are written most heavily in the 30–60 minute window before kick-off; this is a known write burst
- Each write is a single-row upsert on `(user_id, match_id)` (indexed primary key) — fast at scale
- Read patterns are user-scoped (each user only sees their own predictions) — no fan-out problem

### Strict Consistency Note

The lock check + prediction write must be in a single serialisable transaction. No caching of lock state — the database is the authority.

---

## DOMAIN 6 — SCORING ENGINE

### Purpose

The canonical, deterministic computation of points earned per prediction per match. The Scoring Engine is the most critical domain in the system. It has no user-facing API — it is a pure internal service activated by events from Fixtures & Results.

The scoring formula is defined in `SPORTSRUSH_CANONICAL_RULES.md §1.1–1.6`. That document is the specification; this domain is its implementation.

### Responsibilities

- Listening for `result.published` and `result.corrected` events
- Fetching all predictions for the affected match
- Computing `MatchScore` for each prediction using the canonical formula (`packages/scoring`)
- Writing `match_scores` rows atomically (all predictions for a match in one transaction)
- Emitting `scores.recalculated` upon completion
- Maintaining `scoring_config` (the source of all formula parameters)
- Versioning scoring config changes (so historical recalculations use the correct config)
- On `match.voided`: zeroing all `match_scores` for the match and emitting `scores.recalculated`

### Data Ownership

```
match_scores       → id, user_id, match_id, competition_id,
                     points_exact, points_toto, points_home_bonus,
                     points_away_bonus, points_diff_bonus,
                     points_joker_multiplier, total_points,
                     scoring_config_version, calculated_at
scoring_config     → id (version), exact_points, toto_points,
                     home_bonus_points, away_bonus_points,
                     diff_bonus_points, diff_bonus_mode (enum),
                     joker_enabled, joker_multiplier,
                     prediction_lock_minutes, valid_from, created_by
```

### Internal API (no HTTP endpoint — internal service calls only)

```
scoringEngine.recalculateMatch(match_id)    → triggered by event consumer
scoringEngine.getConfig(at_date?)           → returns config version active at given date
scoringEngine.recalculateCompetition(       → admin-triggered full recalculation
  competition_id, from_date?, to_date?
)
```

### Events Consumed

```
result.published    → triggers recalculateMatch(match_id)
result.corrected    → triggers recalculateMatch(match_id)
match.voided        → triggers zero-out of match_scores for match_id
```

### Events Emitted

```
scores.recalculated → { match_id, competition_id, user_count_affected, calculated_at }
```

### Dependencies on Other Domains

- **Fixtures & Results** — consumes `result.published` events; reads `match.home_score`, `match.away_score`
- **Predictions** — reads `predictions` table (read-only access to another domain's data — this is a deliberate exception, governed by a read-only DB role, not direct service coupling)
- **Competitions** — reads `competition_id` from match data for the `scores.recalculated` event payload

### Security Considerations

- Scoring is not user-triggerable — it only fires on events from Fixtures & Results
- Scoring config changes are admin-only and audit-logged (a corrupt config change could destroy all rankings)
- No HTTP endpoint is exposed for scoring operations; it is entirely event-driven
- The `packages/scoring` function is pure and has no database or network access — it cannot be exploited to leak data

### Strict Consistency Note

All `match_scores` writes for a given match are in a single database transaction. If the transaction fails, BullMQ retries the job. Partial scoring (some users scored, others not) must never persist.

### Scaling Considerations

- The largest single recalculation job is for a full competition reset — bounded by the number of users × matches in a season (thousands of rows, not millions for SR2.0 scale)
- BullMQ concurrency for the `score-recalculation` queue should be limited to prevent simultaneous recalculations of the same match from concurrent events (use a job lock keyed on `match_id`)

---

## DOMAIN 7 — RANKINGS

### Purpose

Aggregates individual match scores into competition-wide, monthly, and private league leaderboards. Provides the ranked, paginated views that users see. Rankings are a derived view of data owned by the Scoring Engine.

### Responsibilities

- Computing and serving ranked leaderboards (competition-wide, monthly, private league)
- Applying DENSE_RANK with the configured tiebreaker
- Monthly winner calculation (previous calendar month)
- Writing ranking snapshots after each recalculation
- Serving snapshots for performance (public pages)
- Cache invalidation on `scores.recalculated`
- Providing stats panel data (top correct scorer, top toto user, monthly winner)

### Data Ownership

```
ranking_snapshots → id, competition_id, league_id (nullable),
                    snapshot_at, user_id, rank, total_points,
                    month_points, correct_scores, toto_count, created_at
monthly_winners   → id, competition_id, year, month, user_id,
                    total_points, confirmed_at
```

Rankings themselves are not stored row-by-row in a permanent table — they are computed from `match_scores` on demand and written to `ranking_snapshots` for serving. The snapshot is the materialised view.

### APIs Exposed

```
GET /rankings                      → competition leaderboard
                                     (?competition_id=&page=&limit=)
GET /rankings/monthly              → current month points, same filter
GET /rankings/monthly-winner       → previous month winner per competition
GET /rankings/stats                → stats panel (top correct, top toto, etc.)
GET /leagues/{id}/rankings         → private league leaderboard (auth + membership required)
GET /leagues/{id}/rankings/monthly → monthly view within a private league
```

### Events Consumed

```
scores.recalculated  → triggers snapshot write + cache invalidation
competition.archived → marks competition's snapshots as historical
```

### Events Emitted

```
rankings.updated        → { competition_id, snapshot_id, updated_at }
monthly_winner.declared → { competition_id, year, month, user_id, total_points }
```

### Dependencies on Other Domains

- **Scoring Engine** — reads `match_scores` (read-only access); consumes `scores.recalculated` events
- **Competitions** — reads competition metadata for display
- **Private Leagues** — reads `league_members` to scope leaderboards (read-only access)
- **Users & Profiles** — joins `user_profiles.display_name` for display in rankings

### Security Considerations

- Private league rankings require membership verification on every request (no caching of membership status)
- Public competition rankings are unauthenticated — no user-identifiable data is exposed beyond display name and points (no email, no user ID in public responses)
- The monthly winner declaration event triggers a notification; notifications are delivered to all users in the competition, not just the winner

### Scaling Considerations

- Public rankings pages are the most-read endpoints on the platform; all reads serve from Redis-cached snapshots (60-second TTL)
- The snapshot computation query uses PostgreSQL window functions (`DENSE_RANK()`) on the `match_scores` table — efficient for hundreds to low thousands of users; revisit if user base exceeds 10,000 per competition
- Cache key structure: `rankings:{competition_id}:{page}:{limit}` and `rankings:monthly:{competition_id}:{YYYY-MM}`

---

## DOMAIN 8 — PRIVATE LEAGUES

### Purpose

Allows groups of users to compete within their own leaderboard, scoped to a specific competition. Manages league creation, membership, display, and the catalogue of available leagues.

### Responsibilities

- League creation (admin or authorised user — per Owner Decision OD-13)
- League metadata (name, banner, logo, description, prize)
- Membership management (grant, revoke, list members)
- Access control: only members can view private league rankings
- League catalogue: browsable list of public leagues available to join
- Linking leagues to a WooCommerce product or Stripe product for paid access
- Generating shareable league invite links

### Data Ownership

```
leagues           → id, name, competition_id, owner_id,
                    is_paid, price_gbp, stripe_product_id, prize_gbp,
                    logo_url, banner_url, description,
                    visibility (public/unlisted), status (active/closed/archived),
                    created_at, updated_at
league_members    → id, league_id, user_id, joined_at,
                    access_granted_by (admin/payment/invite),
                    payment_intent_id (nullable), revoked_at (nullable)
league_invites    → id, league_id, invite_code, created_by,
                    expires_at, max_uses, use_count, created_at
```

### APIs Exposed

```
GET  /leagues                       → public league catalogue (paginated)
GET  /leagues/{id}                  → league detail (members count, prize, competition)
GET  /leagues/{id}/members          → member list (auth + membership required)
POST /leagues/{id}/join             → free join (if not paid)
POST /leagues/{id}/join/invite      → join via invite code
POST /leagues/{id}/checkout         → initiate Stripe checkout (if paid)
DELETE /leagues/{id}/members/{user} → leave or remove member (owner/admin)
POST /admin/leagues                 → create league (admin only, or owner if OD-13 resolved)
PATCH /admin/leagues/{id}           → update league metadata
DELETE /admin/leagues/{id}          → archive league
POST /admin/leagues/{id}/members    → manually grant membership
```

### Events Emitted

```
league.created          → { league_id, name, competition_id, is_paid }
league.member_joined    → { league_id, user_id, access_granted_by }
league.member_removed   → { league_id, user_id, removed_by }
league.archived         → { league_id }
```

### Dependencies on Other Domains

- **Competitions** — `competition_id` scopes all league rankings
- **Payments & Entitlements** — delegates paid access grant to Payments domain; Private Leagues domain does not process payments directly
- **Rankings** — Rankings domain reads `league_members` to scope leaderboards (one-way read)
- **Identity & Auth** — membership checks require an authenticated `user_id`
- **Notifications** — consumes `league.member_joined` to send welcome notifications

### Security Considerations

- League membership is checked on every rankings request — no session-level caching
- Unlisted leagues are not discoverable via the catalogue API; only accessible via direct URL or invite code
- Only league owners (if OD-13 is resolved) or admins can add/remove members
- Invite codes are single-use-capable, expiry-capable, and invalidated on league archive

### Scaling Considerations

- Member count per league is bounded by the sport's community size — no horizontal scaling concern at launch
- League catalogue reads are cacheable (1-minute TTL); league membership is not cached (revocation must be immediate)

---

## DOMAIN 9 — PAYMENTS & ENTITLEMENTS

### Purpose

Manages money. Processes Stripe payment events, maintains a permanent record of all payment events, and grants or revokes entitlements (league memberships) as a result. Does not make business decisions about what access means — it delegates to Private Leagues.

### Responsibilities

- Receiving and verifying Stripe webhook events (signature validation)
- Idempotency: processing each Stripe event exactly once
- Mapping `stripe_product_id` → `league_id` via the `leagues` table
- Calling `leagues.grantMembership(user_id, league_id, payment_intent_id)` on successful payment
- Calling `leagues.revokeMembership(user_id, league_id)` on refund/cancellation (if Owner Decision OD-12 resolves to revoke)
- Storing all payment events for audit and reconciliation
- Creating Stripe Checkout Sessions on behalf of the Private Leagues domain

### Data Ownership

```
payment_events    → id, stripe_event_id (unique), event_type,
                    user_id, league_id, amount_pence, currency,
                    stripe_payment_intent_id, status (received/processed/failed),
                    processed_at, raw_payload (jsonb), created_at
```

The Payments domain does NOT own `league_members` — it calls Private Leagues' service interface to grant/revoke membership. The entitlement decision belongs to Private Leagues; the payment record belongs to Payments.

### APIs Exposed

```
POST /webhooks/stripe              → Stripe webhook receiver (unauthenticated, signature-verified)
POST /leagues/{id}/checkout        → create Stripe Checkout Session (auth required)
                                     → delegates to Payments, returns checkout_url
GET  /admin/payments               → payment event log (admin only)
GET  /admin/payments/{event_id}    → single event detail with raw payload
```

### Events Emitted

```
payment.completed   → { user_id, league_id, payment_intent_id, amount_pence }
payment.refunded    → { user_id, league_id, payment_intent_id }
payment.failed      → { user_id, league_id, stripe_event_id }
```

### Dependencies on Other Domains

- **Private Leagues** — calls `leagues.grantMembership()` / `leagues.revokeMembership()` service methods
- **Identity & Auth** — maps Stripe customer to `user_id` (Stripe metadata contains `user_id` at checkout creation time)

### Security Considerations

- **The Stripe webhook endpoint is the highest-risk endpoint in the system.** It is unauthenticated by definition (Stripe calls it). Defences:
  1. Stripe webhook signature verification (`stripe-signature` header) is mandatory and checked before any payload parsing
  2. If signature verification fails, return 400 immediately with no logging of payload content
  3. The webhook endpoint is on an IP allowlist at the Cloudflare WAF level (Stripe publishes their IP ranges)
- Idempotency: `stripe_event_id` has a unique constraint — a duplicate webhook attempt hits the constraint and returns 200 silently
- No card data, no PAN, no CVV ever touches SR2.0 servers — Stripe Checkout handles it entirely

### Strict Consistency Note

Payment event write + membership grant must be in a single transaction. If the grant fails, the payment event is marked `failed` and the job retries. A user who paid must always get access; a retry must never double-grant.

---

## DOMAIN 10 — NOTIFICATIONS

### Purpose

Delivers timely, relevant messages to users across push (iOS/Android), email, and (future) in-app channels. A pure consumer domain — it never originates events, only reacts to them.

### Responsibilities

- Receiving events from other domains and translating them to notification payloads
- Reading user notification preferences before dispatch (users can opt out per type)
- Dispatching push notifications via Expo Push API (→ APNs / FCM)
- Dispatching transactional emails (registration, password reset, monthly winner)
- Rate limiting notification volume per user (no spam)
- Handling delivery failures and deactivating stale push tokens

### Data Ownership

```
notification_log  → id, user_id, type, channel (push/email),
                    payload (jsonb), status (sent/failed/opted_out),
                    sent_at, created_at
```

User notification preferences live in `Users & Profiles` (domain 2).

### Events Consumed

```
user.registered          → send welcome email
user.email_verified      → (no notification)
result.published         → send push: "Full time results are in"
prediction.locked        → send push: "Predictions closing in 30 min" (if user has unsubmitted round)
rankings.updated         → (no direct notification — too frequent)
monthly_winner.declared  → send push to all competition users + email to winner
league.member_joined     → send push to new member: "Welcome to {league}"
payment.completed        → send email receipt (via Stripe — not SR2.0 email)
```

### APIs Exposed

```
GET  /notifications/preferences     → current user's notification settings
PATCH /notifications/preferences    → update preferences
GET  /admin/notifications/log       → delivery log (admin only)
POST /admin/notifications/broadcast → send a custom broadcast (admin only, rate-limited)
```

### Events Emitted

- None. Notifications is a terminal consumer domain.

### Dependencies on Other Domains

- **Users & Profiles** — reads notification preferences and push tokens before dispatch
- **All event-emitting domains** — subscribes to events via the message bus

### Security Considerations

- Broadcast notifications (admin-sent) require admin role and are rate-limited (max 1 per hour per competition)
- Push tokens are user-specific; notifications are never sent to all tokens — always filtered to the relevant user set
- Email templates are server-rendered with escaped content (no user-supplied HTML in emails)

### Scaling Considerations

- Push notification dispatch is async via BullMQ; a single result event may trigger thousands of pushes
- Expo Push API supports batching (up to 100 tokens per request) — the dispatcher batches automatically
- Delivery failures from Expo (token invalid, unregistered) trigger soft-delete of the push token in `Users & Profiles`

---

## DOMAIN 11 — ADMIN & MODERATION

### Purpose

A cross-cutting domain that provides privileged operational capabilities. Admin & Moderation does not own primary data — it acts on other domains through their published service interfaces and APIs. All actions are audit-logged.

### Responsibilities

- Audit log: recording every admin action across all domains
- User moderation: suspending, unsuspending, or deleting user accounts
- Fixture management UI: creating and editing matches (delegates to Fixtures & Results)
- Result entry and correction UI (delegates to Fixtures & Results)
- Team alias management: reviewing unresolved aliases and mapping them (delegates to External Integrations)
- Scraper status monitoring: seeing the health of ingestion runs
- Scoring config management: updating formula parameters (delegates to Scoring Engine)
- Broadcasting admin notifications (delegates to Notifications)
- Full-recalculation trigger: for extraordinary events (delegates to Scoring Engine)

### Data Ownership

```
audit_log         → id, user_id, action, entity_type, entity_id,
                    before (jsonb), after (jsonb), ip_address,
                    user_agent, created_at
```

The audit log is write-once. No UPDATE or DELETE permission exists on this table for any application user.

### APIs Exposed

All `/admin/*` routes across every domain. The Admin domain provides the access control layer (role assertion) on top of each domain's admin-facing operations. No domain exposes admin operations without a role assertion.

```
GET  /admin/audit-log              → paginated audit log (superadmin only)
GET  /admin/audit-log/{entity_id}  → all actions on a specific entity
POST /admin/users/{id}/suspend     → suspend user account
POST /admin/users/{id}/unsuspend
POST /admin/recalculate            → trigger full recalculation for competition
GET  /admin/scraper/status         → last scraper run results, unresolved aliases
POST /admin/scraper/run            → manually trigger a scraper run
```

### Security Considerations

- Admin portal is on a separate subdomain protected by Cloudflare Access (before the app loads)
- Every admin endpoint asserts `role === 'admin'` or `role === 'superadmin'` regardless of Cloudflare Access
- Scoring config changes require `superadmin` role (changing the formula is the most sensitive admin action)
- Audit log is immutable and append-only at the DB level

---

## DOMAIN 12 — GAMIFICATION & ACHIEVEMENTS

### Purpose

Future domain. Provides badges, streaks, milestones, and competitive incentives. Not built at launch — designed here so the data model does not preclude it.

### Responsibilities (future)

- Defining achievement types (e.g. "First correct score", "10-week prediction streak")
- Evaluating achievement criteria after each `scores.recalculated` event
- Granting achievements to users
- Displaying achievements on user profiles and rankings

### Data Ownership (future)

```
achievement_types → id, name, description, icon_url, trigger_event, criteria (jsonb)
user_achievements → id, user_id, achievement_type_id, granted_at, match_id (nullable)
```

### Events Consumed (future)

```
scores.recalculated   → check streak and milestone criteria
user.registered       → grant "Welcome" badge
monthly_winner.declared → grant "Monthly Winner" badge
```

### Events Emitted (future)

```
achievement.granted  → { user_id, achievement_type_id, granted_at }
```

### Launch Posture

The `achievement_types` and `user_achievements` tables are created in the initial migration (empty) so the schema is ready. No processing logic is built at launch. The `achievement.granted` event is consumed by Notifications when the domain is built.

---

## DOMAIN 13 — ANALYTICS & REPORTING

### Purpose

Collects and analyses behavioural and business events to support product decisions, admin reporting, and future personalisation. Operates entirely independently of operational data — it reads events, not tables from other domains.

### Responsibilities

- Receiving and storing user event streams (predictions submitted, rankings viewed, leagues joined)
- Providing admin reporting dashboards (active users per week, predictions per round, revenue)
- Future: personalisation data (most popular competitions, churn risk)

### Data Ownership

```
user_events  → id, user_id (nullable for anonymous), event_type,
               properties (jsonb), occurred_at, session_id
```

Or delegated to an external analytics platform (Posthog, Mixpanel, Amplitude) at launch to avoid building reporting infrastructure.

### Events Consumed

All events from all domains, via an append-only event log (the analytics domain subscribes to a separate Redis stream or message bus topic that receives copies of all events).

### Dependency Direction

Analytics is a pure sink — it receives events but emits none and writes to no other domain's tables. Other domains do not depend on Analytics.

### Launch Posture

At launch: instrument the frontend and mobile apps with Posthog (or equivalent). No custom analytics backend is built. The `user_events` table is created but acts as a buffer for raw events, with reporting deferred to the external tool. Graduate to a custom reporting layer when product scale justifies it.

---

## DOMAIN 14 — EXTERNAL INTEGRATIONS

### Purpose

Insulates the rest of the platform from the fragility of external data sources. Owns the scraping, data fetching, alias resolution, and normalisation of external data. All other domains see clean, validated, domain-typed data — never raw scraper output.

### Responsibilities

- Scheduling and executing scraper runs (BBC Sport, RL.com, or commercial API)
- Normalising external match data to `ScraperResult` format
- Running alias resolution: external team names → `team_id` values via the `team_aliases` table
- Queuing unresolved aliases for admin review
- Storing scraper run metadata and results
- Alerting when a scraper run returns zero results for an expected date
- Applying ingestion validation rules before passing data to Fixtures & Results

### Data Ownership

```
team_aliases              → id, team_id (FK → teams), external_name,
                            source (bbc/rlcom/sportradar/etc),
                            created_at, created_by
scraper_runs              → id, source, target_date, status,
                            matches_found, matches_updated,
                            errors (jsonb), created_at, duration_ms
scraper_unresolved_aliases → id, scraper_run_id, external_name,
                             source, raw_context (jsonb), created_at
scraper_competition_config → id, competition_id, source, external_name,
                             active, start_date, end_date
```

### APIs Exposed (admin only)

```
GET  /admin/integrations/scraper/runs         → run history
GET  /admin/integrations/scraper/unresolved   → unresolved aliases awaiting mapping
POST /admin/integrations/scraper/resolve      → map external_name → team_id
POST /admin/integrations/scraper/trigger      → manual scraper run
GET  /admin/integrations/scraper/config       → competition scrape config
PATCH /admin/integrations/scraper/config/{id} → enable/disable a competition scrape
```

### Events Emitted

```
scraper.run_completed     → { source, target_date, matches_found, matches_updated }
scraper.alias_unresolved  → { external_name, source, scraper_run_id }
scraper.health_failed     → { source, target_date, reason }
```

### Anti-Corruption Layer (detailed)

This is the most important anti-corruption layer in the system.

```
External world                External Integrations          Fixtures & Results
─────────────────             ──────────────────────         ─────────────────
BBC HTML page          →      ScraperAdapter.fetch()    →   FixturesService.applyResult()
RL.com HTML page       →      normalise() + resolve()   →   (only called if all IDs resolve)
SportRadar JSON API    →      ScraperResult { ... }     →
```

The `ScraperResult` type is the membrane. Nothing from the external world crosses it. The Fixtures & Results domain never sees external team names, external competition names, or raw HTML — only typed, validated, ID-resolved records.

### Security Considerations

- Scrapers run server-side only — no web-accessible cron endpoint (the `run_cron_job.php` anti-pattern is explicitly eliminated)
- Commercial API keys are stored in the secrets manager, not in code
- Scraper HTTP requests use a rotating User-Agent and respect rate limits to avoid IP bans
- External data is treated as untrusted: scores outside a sanity range (e.g. > 99 per team) are flagged, not written

### Dependencies on Other Domains

- **Fixtures & Results** — calls `FixturesService.applyResult()` after successful alias resolution; reads `teams` table for alias lookup
- **Admin & Moderation** — exposes alias resolution UI through Admin domain

---

## EVENT FLOW DIAGRAMS

### Flow 1: User Submits a Prediction

```
User → POST /predictions/{match_id}
         │
         ▼
    Predictions Domain
    ├── Assert: user authenticated (Identity & Auth)
    ├── Assert: match exists + play_date (Fixtures & Results, read-only)
    ├── Assert: NOW() < play_date - 30min  [SYNCHRONOUS, STRICT]
    ├── Upsert prediction row
    └── Emit: prediction.saved
         │
         ▼
    Analytics Domain (async)
    └── Log: user_events.prediction_submitted
```

### Flow 2: Result Published

```
Admin → POST /admin/results/{match_id}
         │
         ▼
    Fixtures & Results Domain
    ├── Assert: admin role
    ├── Write match.home_score, match.away_score, status='completed'  [SYNCHRONOUS]
    ├── Write result_corrections row (if correction)
    ├── Write audit_log row
    └── Emit: result.published  [async from here]
         │
         ├──► Scoring Engine (BullMQ worker)
         │    ├── Fetch all predictions for match
         │    ├── Calculate match_scores for all users  [SYNCHRONOUS per batch]
         │    ├── Write match_scores (single transaction)
         │    └── Emit: scores.recalculated
         │              │
         │              ├──► Rankings Domain (BullMQ worker)
         │              │    ├── Invalidate Redis cache
         │              │    ├── Write ranking_snapshot
         │              │    └── Emit: rankings.updated
         │              │              │
         │              │              └──► WebSocket server → connected clients
         │              │
         │              └──► Gamification Domain (future, BullMQ worker)
         │                   └── Check achievement criteria
         │
         └──► Notifications Domain (BullMQ worker)
              └── Dispatch push: "Full time results are in"
```

### Flow 3: Payment Completed

```
Stripe → POST /webhooks/stripe
          │
          ▼
     Payments Domain
     ├── Verify Stripe signature  [SYNCHRONOUS, if fails: abort]
     ├── Check idempotency (stripe_event_id)  [SYNCHRONOUS]
     ├── Write payment_events row
     ├── Call leagues.grantMembership(user_id, league_id)  [SYNCHRONOUS, STRICT]
     │    └── Private Leagues Domain
     │         ├── Check existing membership (idempotency guard)
     │         ├── Insert league_members row
     │         └── Emit: league.member_joined
     │                    │
     │                    └──► Notifications Domain (async)
     │                         └── Push: "Welcome to {league}"
     └── Emit: payment.completed (async)
               │
               └──► Analytics Domain
```

### Flow 4: Monthly Winner (Automated)

```
Cron (1st of month, 00:05 UTC)
     │
     ▼
Rankings Domain (scheduled job)
├── For each active competition:
│   ├── Query previous month's match_scores
│   ├── Aggregate per user
│   ├── Apply DENSE_RANK
│   ├── Identify winner (or tie — per Owner Decision OD-06)
│   ├── Write monthly_winners row
│   └── Emit: monthly_winner.declared
│              │
│              └──► Notifications Domain (async)
│                   ├── Push to all competition members
│                   └── Email to winner
└── Done
```

---

## OWNERSHIP BOUNDARIES SUMMARY

| Data                           | Owner                 | Can Read                           | Cannot Read                             |
| ------------------------------ | --------------------- | ---------------------------------- | --------------------------------------- |
| `users`, `sessions`            | Identity & Auth       | All domains (user_id only)         | Raw password hashes                     |
| `user_profiles`                | Users & Profiles      | All domains (display_name)         | Preferences (private)                   |
| `competitions`                 | Competitions          | All domains                        | —                                       |
| `matches`, `teams`             | Fixtures & Results    | All domains                        | —                                       |
| `predictions`                  | Predictions           | Scoring Engine (read-only DB role) | Other users' predictions (pre-kick-off) |
| `match_scores`                 | Scoring Engine        | Rankings (read-only DB role)       | —                                       |
| `ranking_snapshots`            | Rankings              | Public APIs                        | —                                       |
| `leagues`, `league_members`    | Private Leagues       | Rankings (read-only), Payments     | —                                       |
| `payment_events`               | Payments              | Admin only                         | —                                       |
| `team_aliases`, `scraper_runs` | External Integrations | Admin only                         | —                                       |
| `audit_log`                    | Admin & Moderation    | Superadmin only                    | All write                               |

---

## SYNCHRONOUS vs EVENT-DRIVEN SUMMARY

| Operation                    | Pattern                                     | Reason                                                 |
| ---------------------------- | ------------------------------------------- | ------------------------------------------------------ |
| Prediction save + lock check | Synchronous transaction                     | Lock bypass is a data integrity violation              |
| Result entry                 | Synchronous write → async cascade           | Admin needs instant confirmation; scoring can be async |
| Scoring recalculation        | Async (BullMQ)                              | Can take seconds; user does not wait                   |
| Rankings update              | Async (BullMQ)                              | 30-second lag is acceptable                            |
| Push notification dispatch   | Async (BullMQ)                              | Delivery timing is not critical                        |
| Payment → access grant       | Synchronous (within webhook handler)        | User paid; access must be reliable                     |
| Alias resolution             | Synchronous (within scraper run)            | Blocking — scraper waits for resolution before writing |
| Monthly winner               | Async (scheduled job)                       | Can tolerate minutes of delay                          |
| Audit log write              | Synchronous (within the action transaction) | An action without a record is undetectable             |

---

## LEGACY WORDPRESS COEXISTENCE (Anti-Corruption Layers)

During the parallel-running migration phase, WordPress continues to be the system of record. These ACLs govern coexistence:

### ACL-1: WordPress User Identity → SR2.0 Identity

- `users.legacy_wp_user_id` column maps WP integer IDs to SR2.0 UUIDs
- On SR2.0 login with a migrated account: if stored hash is `$P$` prefix (phpass), verify with phpass library, then immediately rehash with Argon2id and overwrite
- The mapping is one-way during migration; WP does not know about SR2.0 IDs

### ACL-2: WordPress Prediction Data → SR2.0 Predictions

- Migrated via a one-time ETL script: `pool_wpkl_predictions` → `predictions`
- `has_joker` column maps to `joker` boolean
- NULL scores (incomplete predictions) are migrated as NULL — not zero

### ACL-3: WordPress Match Data → SR2.0 Matches

- `pool_wpkl_matches` → `matches`; `matchtype_id` → `competition_id`
- `round` column: populated from the live database (missing from backup) before migration
- Status: all past matches with scores → `completed`; future matches → `scheduled`

### ACL-4: WordPress Private League Data

- `custom_competitions` → `leagues`
- `custom_competition_users` → `league_members` (with `access_granted_by = 'admin'` for all migrated rows)
- `wc_product_id` → `stripe_product_id` (requires mapping WooCommerce products to Stripe products before cutover)

### ACL-5: Scraper Output Format

- Current scraper output: ad-hoc PHP/Python writes directly to `pool_wpkl_matches`
- SR2.0 scraper output: `ScraperResult` typed objects passed through the External Integrations anti-corruption layer
- During parallel running: the legacy scraper continues writing to WordPress; a sync script reads new WordPress results and emits `result.published` events into SR2.0

---

_Domain model version 1.0. All Owner Decisions from `SPORTSRUSH_CANONICAL_RULES.md` must be resolved before implementation begins. Domains with unresolved Owner Decisions: Scoring Engine (OD-01, OD-02), Rankings (OD-04, OD-05, OD-06), Predictions (OD-07), Fixtures & Results (OD-08, OD-09), Private Leagues (OD-10, OD-11, OD-12, OD-13)._
