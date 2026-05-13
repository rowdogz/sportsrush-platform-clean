# SPORTSRUSH_2_IMPLEMENTATION_SEQUENCE.md
## SportsRush 2.0 — Implementation Sequence & Migration Roadmap

**Sources:** All previous architecture and domain documents  
**Team assumption:** Small team (1–3 engineers) using AI-assisted development  
**Constraint:** WordPress remains live and fully operational at all times until explicit cutover

---

## PART 1 — HIGH-LEVEL PHASE ROADMAP

```
PHASE 0  │ Foundation & Owner Decisions         │ Weeks 1–2
PHASE 1  │ Canonical Scoring Engine              │ Weeks 2–3   ← Gating dependency for everything
PHASE 2  │ Core API: Auth + Fixtures             │ Weeks 3–6
PHASE 3  │ Predictions & Scoring Integration     │ Weeks 6–9   ← MVP backbone
PHASE 4  │ Rankings                              │ Weeks 9–11
PHASE 5  │ Admin Tooling & Ingestion             │ Weeks 10–13  ← Parallel with Phase 4
PHASE 6  │ Web Frontend                          │ Weeks 12–17  ← Parallel with Phase 5
PHASE 7  │ Private Leagues & Payments            │ Weeks 14–17  ← Parallel with Phase 6 late
PHASE 8  │ Data Migration & Parallel Running     │ Weeks 17–20
PHASE 9  │ Mobile Apps                           │ Weeks 18–24  ← After stable API + web
PHASE 10 │ Cutover                               │ Week 20–21
PHASE 11 │ Post-Cutover Hardening                │ Weeks 21–24
```

```
─────────────────────────── MVP CUT LINE ──────────────────────────────────────
Phases 0–8 (+ Phase 10 cutover) constitute the MVP. 
WordPress is fully replaced. Mobile (Phase 9) is post-MVP.
───────────────────────────────────────────────────────────────────────────────
```

**Total estimated wall time to cutover:** 18–21 weeks (small team, AI-assisted)  
**Total estimated wall time to mobile launch:** 22–26 weeks

---

## PART 2 — DETAILED PHASE BREAKDOWN

---

## PHASE 0 — Foundation & Owner Decisions

### Objective
Establish the technical foundation and resolve every Owner Decision that blocks implementation. Nothing is built that assumes an unresolved rule. This phase ends when the team can write code confidently.

### Why This Phase Comes First
Unresolved Owner Decisions (OD-01 through OD-15 in `SPORTSRUSH_CANONICAL_RULES.md`) are implementation blockers, not deferrable options. A scoring formula built on the wrong assumption costs more to fix later than to get right now. The foundation (monorepo, CI, database, environments) is infrastructure that every subsequent phase depends on.

### Domains Involved
- None (infrastructure only)
- All (Owner Decisions affect every domain)

### Dependencies
- None. This is the root phase.

### Deliverables

**Technical:**
- Monorepo initialised (`apps/api`, `apps/web`, `apps/admin`, `packages/types`, `packages/scoring`, `packages/api-client`)
- GitHub Actions CI pipeline: lint, type-check, test on every push
- Three environments configured: `local`, `dev`, `staging` (PostgreSQL + Redis per environment)
- `.env.example` with all required secret keys documented (no real values)
- Migration runner configured (`node-pg-migrate` or `golang-migrate`)
- Migration `001_initial_schema.sql` written (based on live DB export — NOT the committed backup)
- Linting and formatting standards enforced (`eslint`, `prettier`, `typescript strict mode`)
- `packages/types` — shared TypeScript type definitions for all domain entities

**Decisions:**
- All 15 Owner Decisions resolved and appended to `SPORTSRUSH_CANONICAL_RULES.md`
- `scoring_config` row written to the database reflecting the resolved formula

### Pre-Phase Action (Before Week 1)
**Export the live production database.** Run `SHOW TABLES` and `SHOW CREATE TABLE` for all tables including `custom_competitions`, `custom_competition_users`, `wpkl_pool_wpkl_scrape_competitions`, and verify the `round` column on `pool_wpkl_matches`. This export is the basis for `001_initial_schema.sql`. Without it, Phase 2 cannot start.

### APIs Introduced
- None

### Database Changes
- `001_initial_schema.sql` — full SR2.0 schema (all tables empty)

### Frontend / Mobile Impact
- None

### Migration Impact
- None

### Rollback Considerations
- Nothing is live yet. Rollback = delete the repository and start fresh.

### Acceptance Criteria
- [ ] CI pipeline passes on a blank commit
- [ ] All 15 Owner Decisions have a documented resolution in `SPORTSRUSH_CANONICAL_RULES.md`
- [ ] `001_initial_schema.sql` applies cleanly to a fresh PostgreSQL database with zero errors
- [ ] The schema includes `round` column on `matches`, `custom_competitions`, `custom_competition_users`, `team_aliases` tables
- [ ] No secret values exist in the repository

### Suggested Automated Tests
```
test: migration 001 applies to clean DB without errors
test: .env.example contains all required keys
test: no file in repo matches DB credential pattern (grep-based CI check)
```

---

## PHASE 1 — Canonical Scoring Engine

### Objective
Build and exhaustively test the single canonical scoring function before any other domain is written. The scoring engine is the most critical piece of the system — every other component depends on it being correct.

### Why This Phase Comes First (After Foundation)
From `HIGH_RISK_AREAS.md §1`: the current system has two independent scoring formulas producing different results for the same prediction. This phase eliminates that problem permanently by creating the authoritative, fully-tested source of truth before anything else is built on top of it.

If rankings, predictions, or migrations are built before the scoring engine is proven, they may be validated against the wrong formula. Fixing the engine after those systems exist requires a cascade of changes.

### Domains Involved
- **Scoring Engine** (primary)

### Dependencies
- Phase 0 complete (monorepo, types package, Owner Decisions resolved)

### Deliverables
- `packages/scoring/src/calculateMatchScore.ts` — pure function, no side effects, no DB access
- `packages/scoring/src/types.ts` — `ScoringConfig`, `MatchScore`, `PredictionInput`, `MatchResult` types
- Full unit test suite covering every scenario from `SPORTSRUSH_CANONICAL_RULES.md §1.1–1.6`
- A verification script that runs the scoring function against historical WordPress data and asserts the totals match the current leaderboard (this is the migration correctness check)

### The Canonical Function Signature
```typescript
calculateMatchScore(
  result: MatchResult,       // { home: number, away: number }
  prediction: PredictionInput, // { home: number | null, away: number | null, joker: boolean }
  config: ScoringConfig      // { exactPoints, totoPoints, homeBonusPoints, awayBonusPoints,
                             //   diffBonusPoints, diffBonusMode, jokerEnabled, jokerMultiplier }
): MatchScore
// Returns: { pointsExact, pointsToto, pointsHomebonus, pointsAwayBonus,
//            pointsDiffBonus, pointsJokerMultiplier, total }
```

### APIs Introduced
- None (internal package only, no HTTP endpoints)

### Database Changes
- None (scoring function has no DB access)

### Frontend / Mobile Impact
- None

### Migration Impact
- The verification script (deliverable above) runs against a copy of the WordPress database to prove the formula produces identical totals. Any discrepancy is fixed before Phase 3.

### Rollback Considerations
- Nothing is live. Rollback = revert the package.

### Acceptance Criteria
- [ ] `calculateMatchScore(result={3,1}, prediction={3,1}, config)` → `total = exactPoints + homeBonusPoints + awayBonusPoints` (toto not added)
- [ ] `calculateMatchScore(result={2,0}, prediction={3,0}, config)` → `total = totoPoints + awayBonusPoints + diffBonusPoints` (if margin=2 matches)
- [ ] `calculateMatchScore(result={2,0}, prediction={2,0}, joker=true, config)` → `total = (exactPoints + 2×bonusPoints) × jokerMultiplier`
- [ ] `calculateMatchScore(result={1,0}, prediction={0,1}, config)` → `total = 0` (wrong outcome)
- [ ] `calculateMatchScore(result={2,0}, prediction={null,0}, config)` → handled gracefully (incomplete prediction)
- [ ] Verification script: totals match WordPress leaderboard for a sample of 50+ users within ±0 (exact match or documented exception)
- [ ] 100% branch coverage on the scoring function
- [ ] Function is pure: same inputs always produce same output (100 randomised fuzz runs)

### Suggested Automated Tests
```
test_exact_score_home_win
test_exact_score_draw
test_exact_score_away_win
test_toto_only_home_win
test_toto_only_draw
test_toto_only_away_win
test_home_bonus_only
test_away_bonus_only
test_both_goal_bonuses
test_diff_bonus_with_toto (per resolved OD-01)
test_diff_bonus_excluded_on_exact
test_diff_bonus_draw_behaviour (per resolved OD-01)
test_joker_multiplier_applied (per resolved OD-02)
test_joker_not_applied_when_disabled
test_incomplete_prediction_handled
test_null_result_returns_empty
test_determinism_fuzz_100_iterations
```

---

## PHASE 2 — Core API: Identity, Competitions & Fixtures

### Objective
Build the first three production-quality API domains: Identity & Auth, Competitions, and Fixtures & Results. These are the data model foundations that all remaining domains reference. Establish the admin portal skeleton.

### Why This Phase Comes Now
Auth and fixtures are the baseline data layer. No other domain can be built without a `user_id` and without `match` records. Building these first also allows the admin portal to be usable for data management before the full frontend exists.

### Domains Involved
- Identity & Auth
- Users & Profiles
- Competitions
- Fixtures & Results (without external scraper — admin entry only)
- Admin & Moderation (skeleton: audit log, admin role assertion middleware)

### Dependencies
- Phase 0 (foundation, schema)
- Phase 1 (scoring types — Fixtures domain references `MatchResult` type)

### Deliverables
- Fastify API with route registration, schema validation, error handling middleware
- Identity & Auth module: register, verify email, login, refresh, logout, password reset
- JWT issuance and verification middleware (reusable by all subsequent modules)
- Admin role assertion middleware
- Competitions module: CRUD (admin), list (public)
- Fixtures module: match CRUD (admin), fixture list (public), result entry (admin)
- Audit log: every admin write is logged
- Admin portal skeleton (`apps/admin`): login, competition list, fixture management, result entry

### APIs Introduced
```
POST /auth/register
POST /auth/verify-email
POST /auth/login
POST /auth/refresh
POST /auth/logout
POST /auth/password-reset/request
POST /auth/password-reset/confirm
GET  /users/me
PATCH /users/me
GET  /competitions
GET  /competitions/{id}
POST /admin/competitions
PATCH /admin/competitions/{id}
GET  /fixtures
GET  /fixtures/{id}
GET  /results
POST /admin/fixtures
PATCH /admin/fixtures/{id}
POST /admin/results/{id}
GET  /admin/results/{id}/history
GET  /admin/audit-log
```

### Database Changes
- Migration `002_seed_scoring_config.sql` — insert resolved scoring config row
- Migration `003_add_indexes.sql` — performance indexes on `play_date`, `competition_id`, `round`

### Frontend / Mobile Impact
- `apps/admin` skeleton is deployed to `dev` environment (not public-facing)
- No public web frontend yet

### Migration Impact
- None yet. This phase uses empty tables.

### Rollback Considerations
- The API is not public-facing yet (accessible only via admin). Roll back by reverting the deployment.

### Acceptance Criteria
- [ ] Registration flow works end-to-end (register → verify email → login → receive JWT)
- [ ] A non-admin user cannot access any `/admin/*` endpoint
- [ ] Admin can create a competition, create a match, and enter a result via the admin portal
- [ ] Audit log records every admin write with before/after values
- [ ] Duplicate match insertion (same teams + competition + date) is rejected with a clear error
- [ ] All endpoints return JSON Schema-validated responses (no untyped `any`)
- [ ] CI pipeline includes integration tests against a test database

### Suggested Automated Tests
```
test_register_login_flow
test_email_verification_required
test_jwt_expires_after_15_minutes
test_refresh_token_rotation
test_admin_endpoint_rejects_user_role
test_duplicate_match_rejected
test_result_entry_creates_audit_log
test_competition_visibility_filter
test_future_fixture_not_in_results
test_rate_limit_on_login_endpoint
```

---

## PHASE 3 — Predictions & Scoring Integration

### Objective
Implement the Predictions domain with a server-side lock, connect it to the Scoring Engine, and prove end-to-end that a prediction → result → points calculation works correctly. This is the MVP backbone.

### Why This Phase Comes Now
The prediction → score chain is the core product. Without it, nothing else has meaning. This phase is placed after Fixtures (Phase 2) because it reads match data, and after the Scoring Engine (Phase 1) because it drives the calculation. All subsequent phases (Rankings, Frontend) depend on data produced by this phase.

### Domains Involved
- Predictions (primary)
- Scoring Engine (event consumer, connected here)
- Background job infrastructure (BullMQ + Redis)

### Dependencies
- Phase 1 (scoring function)
- Phase 2 (matches exist, auth exists)

### Deliverables
- Predictions module: save/update prediction with server-side lock check
- Lock check is in a serialisable DB transaction (cannot be bypassed by race condition)
- Joker toggle endpoint (if enabled per OD-02)
- BullMQ infrastructure setup (job queues, Redis connection)
- `score-recalculation` job worker: consumes `result.published` → calls scoring function → writes `match_scores`
- End-to-end test harness: enter a result via admin → assert `match_scores` rows are written with correct values

### APIs Introduced
```
GET  /predictions?competition_id=&round=
POST /predictions/{match_id}
GET  /predictions/{match_id}
POST /predictions/{match_id}/joker   (if OD-02: jokers enabled)
GET  /admin/predictions/{match_id}   (post-kickoff only)
POST /admin/predictions/override     (re-open / force-lock)
```

### Database Changes
- Migration `004_add_prediction_lock_config.sql` — ensure `scoring_config.prediction_lock_minutes` column exists

### Frontend / Mobile Impact
- None yet (API-only phase)
- Admin portal: prediction override controls added

### Migration Impact
- None yet on production data
- **Verification gate:** Run scoring engine against a sample of historical WordPress prediction + result data. Assert `match_scores` output matches the current WordPress leaderboard totals. This must pass before Phase 4 starts. Any discrepancy is a Phase 1 scoring engine bug — fix it here.

### Rollback Considerations
- Still not public-facing. Roll back by reverting the deployment and clearing the `predictions` and `match_scores` tables.

### Acceptance Criteria
- [ ] Prediction submitted 31 minutes before kick-off → accepted
- [ ] Prediction submitted 29 minutes before kick-off → rejected with 423 status
- [ ] Direct POST to prediction endpoint for a completed match → rejected
- [ ] Enter a result via admin → `match_scores` rows appear for all users who predicted that match within 30 seconds
- [ ] `match_scores` values match `calculateMatchScore()` output exactly (property-based verification)
- [ ] Correcting a result via admin → `match_scores` updated, `result_corrections` row written
- [ ] Joker: a prediction with joker=true on a 3-1 exact score produces `total = (exactPoints + bonuses) × jokerMultiplier`
- [ ] Score recalculation job is idempotent (running twice produces the same `match_scores`)

### Suggested Automated Tests
```
test_prediction_accepted_before_lock
test_prediction_rejected_after_lock
test_prediction_rejected_for_completed_match
test_prediction_rejected_for_voided_match
test_score_recalculation_fires_on_result_published
test_match_scores_correct_exact_score
test_match_scores_correct_toto
test_match_scores_correct_joker_multiplied
test_recalculation_idempotent
test_result_correction_triggers_recalculation
test_match_scores_zeroed_on_void
test_historical_data_verification (against WP snapshot)
```

---

## PHASE 4 — Rankings

### Objective
Build the Rankings domain: live leaderboards, monthly totals, monthly winner, ranking snapshots, and caching. By end of this phase, SR2.0 has a complete, correct, real-time leaderboard that can be compared side-by-side with WordPress.

### Why This Phase Comes Now
Rankings depend on `match_scores` (Phase 3). Nothing else depends on Rankings being complete before it is needed. Phase 5 (Admin Tooling) can run in parallel. Phase 6 (Frontend) needs Rankings APIs — so this must complete before the public web frontend is built.

### Domains Involved
- Rankings (primary)
- Scoring Engine (event producer)
- Redis caching layer

### Dependencies
- Phase 3 (match_scores populated)

### Deliverables
- Rankings module: competition leaderboard query with DENSE_RANK, pagination
- Monthly points column and monthly winner calculation
- Ranking snapshots written after each `scores.recalculated` event
- Redis cache for ranking responses (60-second TTL, invalidated on `rankings.updated`)
- WebSocket server: clients subscribing to a competition receive a `rankings.updated` push when the leaderboard changes
- Stats panel: top correct scorer, top toto user, monthly winner

### APIs Introduced
```
GET /rankings?competition_id=&page=&limit=
GET /rankings/monthly?competition_id=&year=&month=
GET /rankings/monthly-winner?competition_id=
GET /rankings/stats?competition_id=
GET /leagues/{id}/rankings
GET /leagues/{id}/rankings/monthly
WS  /ws/rankings/{competition_id}  (WebSocket subscription)
```

### Database Changes
- Migration `005_ranking_snapshots.sql` — `ranking_snapshots` and `monthly_winners` tables (may already be in 001; confirm)

### Frontend / Mobile Impact
- None (API-only)
- **Comparison checkpoint:** Point the SR2.0 rankings API at staging data and manually compare output to the live WordPress leaderboard. This is the first human-visible correctness check.

### Migration Impact
- None yet on production WordPress. The comparison is against a copy.

### Rollback Considerations
- Rankings are derived data. Roll back = clear `ranking_snapshots`, fix the bug, re-emit `scores.recalculated` events for affected matches.

### Acceptance Criteria
- [ ] Rankings endpoint returns users ordered by total_points DESC with DENSE_RANK applied
- [ ] Two users with equal points share the same rank; next rank is consecutive
- [ ] Monthly points column reflects only the current calendar month
- [ ] Monthly winner reflects only the previous calendar month (1st to last day)
- [ ] Competitions excluded from Monthly Winner display (configured, not hard-coded) show no Monthly Winner row
- [ ] WebSocket client receives an update within 30 seconds of a result being entered
- [ ] Redis cache is hit on the second request; DB not queried on cache hit
- [ ] SR2.0 leaderboard matches WordPress leaderboard for a sample competition (tolerance: zero discrepancy, or documented exception)

### Suggested Automated Tests
```
test_rankings_ordered_by_points_desc
test_dense_rank_on_tie
test_dense_rank_consecutive_after_tie
test_monthly_filter_includes_first_day_of_month
test_monthly_filter_includes_last_day_of_month
test_monthly_filter_excludes_current_month
test_monthly_winner_previous_month_only
test_ranking_snapshot_written_after_recalculation
test_redis_cache_hit_on_second_request
test_websocket_push_received_after_result
test_tiebreaker_applied_consistently (per resolved OD-04)
```

---

## PHASE 5 — Admin Tooling & External Integrations

**This phase runs in parallel with Phase 4 on a separate workstream.**

### Objective
Build the full admin portal and the external data ingestion pipeline (scraper rewrite + alias management). Admin tooling must exist before large-scale data migration. The scraper must be reliable before cutover.

### Why This Phase Comes Now (and in Parallel)
The admin portal is needed to manage fixture data, resolve team aliases, correct results, and perform the migration tasks in Phase 8. The scraper rewrite is a risk item (BBC CSS fragility, missing alias table) that needs testing time before it is trusted in production. Starting both in parallel with Phase 4 is safe because they share no dependencies.

### Domains Involved
- Admin & Moderation (full portal)
- External Integrations (scraper rewrite, alias management)

### Dependencies
- Phase 2 (auth, fixtures, audit log foundation)
- Phase 3 (result entry triggers scoring)

### Deliverables

**Admin Portal (`apps/admin`):**
- Full fixture management (create, edit, reschedule, bulk import)
- Result entry and correction UI with before/after display and mandatory reason field
- Team alias management: list unresolved aliases, map to team, trigger re-run
- User management: view users, suspend/unsuspend
- Scraper status dashboard: last run time, matches found/updated, unresolved alias count
- Scoring config management (superadmin only): view active config, history of changes
- Audit log browser: filterable by entity type, date range, admin user

**External Integrations:**
- Scraper rewrite: `ScraperAdapter` interface with implementations for BBC Sport and RL.com
- CSS class names extracted to a configuration table (`scraper_competition_config`) — not embedded in code
- `ScraperResult` normalisation and alias resolution pipeline
- Ingestion validation (sanity checks on scores, date is past, match exists in DB)
- `scraper_unresolved_aliases` queue with admin notification
- Scraper health check: alert if zero results returned for an expected date
- Scheduled scraper runs (hourly during season, weekly fixture fetch)
- Admin-triggerable manual scraper run

### APIs Introduced
```
GET  /admin/users
POST /admin/users/{id}/suspend
POST /admin/users/{id}/unsuspend
GET  /admin/audit-log?entity_type=&from=&to=
GET  /admin/integrations/scraper/runs
GET  /admin/integrations/scraper/unresolved
POST /admin/integrations/scraper/resolve
POST /admin/integrations/scraper/trigger
PATCH /admin/integrations/scraper/config/{id}
GET  /admin/scoring-config
POST /admin/scoring-config           (superadmin only)
POST /admin/recalculate              (superadmin only)
```

### Database Changes
- Migration `006_scraper_tables.sql` — `scraper_runs`, `scraper_unresolved_aliases`, `scraper_competition_config`
- Migration `007_team_aliases.sql` — `team_aliases` table (replaces hard-coded PHP array)

### Frontend / Mobile Impact
- `apps/admin` reaches full capability this phase
- No public web frontend changes

### Migration Impact
- **Begin seeding the alias table.** Map all current BBC and RL.com team names to `team_id` values using the hard-coded PHP array from `scores.php` as the starting point. This is a one-time data migration of the alias map.
- Run the new scraper against historical dates to verify it matches results already in WordPress. Any discrepancy = alias gap to fix.

### Rollback Considerations
- Scraper runs are read-only against external sources. If a scraper run writes bad data, the result can be corrected via the admin correction UI.
- Alias table changes are reversible.

### Acceptance Criteria
- [ ] Admin can create, edit, and reschedule fixtures without direct DB access
- [ ] Admin correcting a result sees the before/after values and is required to enter a reason
- [ ] Scraper run against yesterday's date returns correct results for all active competitions
- [ ] An unresolved team name creates a row in `scraper_unresolved_aliases` and does not write to `matches`
- [ ] Admin maps an unresolved alias → re-trigger scraper → match is updated
- [ ] Scraper health check fires an alert if zero matches found for an expected date
- [ ] All team names from the hard-coded PHP alias array exist in the new `team_aliases` table
- [ ] Audit log records every admin action with before/after state

### Suggested Automated Tests
```
test_scraper_returns_correct_results_for_known_date
test_unresolved_alias_queued_not_written
test_alias_resolution_allows_rerun_to_succeed
test_scraper_health_alert_on_zero_results
test_ingestion_rejects_future_date_result
test_ingestion_rejects_score_above_sanity_cap
test_admin_result_correction_requires_reason
test_audit_log_records_admin_correction
test_scoring_config_change_logged
```

---

## PHASE 6 — Web Frontend

### Objective
Build the public-facing Next.js web application. By end of this phase, SR2.0 has a complete, usable web product that can serve real users — even while WordPress is still the production system.

### Why This Phase Comes Now
The frontend cannot be built before the APIs it depends on exist and are stable (Phases 2–4). Phase 5 runs in parallel. Starting the frontend in Phase 6 means the API surface is stable enough to build against without constant breaking changes.

### Domains Involved
- All (consuming existing APIs)
- `apps/web` (primary deliverable)
- `packages/api-client` (typed HTTP client, generated from API schemas)

### Dependencies
- Phase 2 (auth APIs)
- Phase 3 (predictions APIs)
- Phase 4 (rankings APIs)
- Phase 5 does not block Phase 6 start — private leagues are added later

### Deliverables
- `packages/api-client` — typed Fetch wrapper for all API endpoints, shared by web and mobile
- Public rankings page with competition filter, DENSE_RANK display, current user highlight
- Monthly winner and stats panel
- Predictions form with AJAX per-field save, loading state, lock state display
- Past results browser
- Fixture list (upcoming matches)
- Authentication flow (login, register, email verification, password reset)
- User profile / notification preferences
- Responsive design (desktop + mobile web)
- Shared design system (`packages/ui` with Tailwind + shadcn/ui)
- Cloudflare deployment configuration (edge caching for public pages)

### Phase 6 Sub-Sequence (internal ordering)
```
6a: Design system + layouts (1 week)
6b: Auth flow + user profile (1 week)
6c: Rankings page — most important, most visible (1 week)
6d: Predictions form (most complex frontend component) (2 weeks)
6e: Fixtures + results browser (1 week)
```

### APIs Introduced
- None new (all APIs built in Phases 2–4)

### Database Changes
- None

### Frontend Impact
- SR2.0 web app deployed to `staging.sportsrush.co.uk` — accessible to internal testers
- Compare SR2.0 predictions form UX against WordPress predictions form UX
- Rankings pages compared side-by-side with WordPress (staging data = copy of production)

### Migration Impact
- None on production WordPress yet. Testing is against staging data only.

### Rollback Considerations
- Web frontend is not yet public. Roll back = point staging DNS to previous deployment.

### Acceptance Criteria
- [ ] A new user can register, verify email, log in, and submit a prediction end-to-end
- [ ] Rankings page shows DENSE_RANK with correct competition filter; logged-in user is highlighted
- [ ] Predictions form saves each field independently on change with a visual save indicator
- [ ] Predictions form shows locked state for matches within 30 minutes or past
- [ ] Attempting to submit a prediction for a locked match via browser dev tools is rejected by the API (server-side enforcement)
- [ ] Public rankings page loads in < 1.5 seconds on a cold cache (Cloudflare edge)
- [ ] Public rankings page loads in < 300ms on a warm cache
- [ ] All pages are responsive from 375px mobile width through desktop
- [ ] No console errors or unhandled promise rejections in any user flow

### Suggested Automated Tests
```
test_e2e_register_verify_login (Playwright)
test_e2e_submit_prediction_before_lock
test_e2e_prediction_rejected_after_lock_in_browser
test_e2e_rankings_page_loads_correct_data
test_e2e_competition_filter_changes_rankings
test_unit_prediction_form_shows_save_indicator
test_unit_lock_state_displayed_correctly
test_unit_rankings_highlights_current_user
test_a11y_no_critical_violations (axe-core)
test_perf_ranking_page_lcp (Lighthouse CI)
```

---

## PHASE 7 — Private Leagues & Payments

**Runs in parallel with Phase 6 late stages (6d onwards).**

### Objective
Implement private leagues, Stripe payment integration, and the entitlement model. Required before cutover because existing private league members must retain access after migration.

### Why This Phase Comes Now
Private Leagues depend on Rankings (Phase 4) being complete (league leaderboards are scoped rankings). Payments depend on Private Leagues existing. Both can run in parallel with Phase 6 late stages because the web frontend for leagues is a smaller surface than the core predictions form.

### Domains Involved
- Private Leagues
- Payments & Entitlements
- Notifications (league.member_joined)

### Dependencies
- Phase 4 (rankings APIs used by league leaderboards)
- Phase 2 (auth)

### Deliverables
- Private Leagues module: league CRUD (admin), membership management, league catalogue, membership check middleware
- League invite codes (if OD-10 resolves to enable)
- Stripe integration: Checkout Session creation, webhook receiver with signature verification, idempotency guard
- `payment_events` table populated on each Stripe webhook
- Post-payment redirect to league rankings page
- Private league web pages (catalogue, league detail, league rankings)
- Admin portal: league management, manual membership grant/revoke

### APIs Introduced
```
GET  /leagues
GET  /leagues/{id}
GET  /leagues/{id}/members     (auth + membership)
POST /leagues/{id}/join        (free leagues)
POST /leagues/{id}/join/invite (invite codes)
POST /leagues/{id}/checkout    (paid leagues)
DELETE /leagues/{id}/members/{user}
POST /webhooks/stripe
GET  /admin/leagues
POST /admin/leagues
PATCH /admin/leagues/{id}
POST /admin/leagues/{id}/members
GET  /admin/payments
```

### Database Changes
- Migration `008_leagues_and_payments.sql` — `leagues`, `league_members`, `league_invites`, `payment_events`

### Migration Impact
- **Stripe product mapping required before Phase 8.** Every existing WooCommerce product linked to a private league must be mapped to a Stripe Product and Price. This mapping is done manually by the admin before migration begins.
- Existing `custom_competition_users` memberships will be migrated as `access_granted_by = 'admin'` rows in `league_members`.

### Rollback Considerations
- Stripe Checkout is an external redirect — if the webhook fails, retry logic handles it
- Payment events table is append-only — no data is lost on rollback
- Roll back = redeploy previous API version; Stripe webhooks will retry and be processed when the endpoint is back

### Acceptance Criteria
- [ ] A paid league shows a "Join — £N" button; clicking opens Stripe Checkout
- [ ] On payment completion, Stripe fires the webhook; membership is granted idempotently
- [ ] Paying twice for the same league creates exactly one membership row
- [ ] A non-member navigating directly to a private league URL sees an access-denied message
- [ ] The private league leaderboard uses the same scoring formula as the public leaderboard
- [ ] Webhook with invalid signature returns 400 with no payload processing
- [ ] Webhook with duplicate `stripe_event_id` returns 200 with no DB write
- [ ] Admin can manually grant membership to a user for any league

### Suggested Automated Tests
```
test_stripe_webhook_signature_verified
test_stripe_duplicate_event_idempotent
test_payment_grants_membership
test_double_payment_single_membership_row
test_non_member_denied_league_access
test_member_sees_league_rankings
test_free_join_grants_membership
test_invite_code_grants_membership
test_admin_manual_grant_membership
```

---

## PHASE 8 — Data Migration & Parallel Running

### Objective
Migrate all production WordPress data into SR2.0, run both systems in parallel, and verify correctness before cutover. This phase ends when the team is confident SR2.0 is a correct superset of WordPress functionality.

### Why This Phase Comes Now
All domains are built. Admin tooling exists to manage data. The scraper is reliable. The scoring engine is proven. Only now is it safe to put real production data into the new system.

### Dependencies
- All Phases 0–7 complete and deployed to staging
- Live production database export (not the committed backup)
- Stripe product mapping complete (Phase 7 dependency)
- All Owner Decisions resolved

### Deliverables
- ETL scripts (one per data type, all reversible, all idempotent):
  - `migrate_users.ts` — WordPress users → SR2.0 users + user_profiles
  - `migrate_competitions.ts` — pool_wpkl_matchtypes → competitions
  - `migrate_teams.ts` — pool_wpkl_teams → teams
  - `migrate_matches.ts` — pool_wpkl_matches (including live `round` column) → matches
  - `migrate_predictions.ts` — pool_wpkl_predictions → predictions
  - `migrate_leagues.ts` — custom_competitions → leagues
  - `migrate_memberships.ts` — custom_competition_users → league_members
  - `migrate_aliases.ts` — hardcoded PHP aliases → team_aliases (already done in Phase 5)
- Historical scoring recalculation: once predictions and matches are migrated, run scoring for all historical matches
- Ranking comparison report: SR2.0 rankings vs WordPress rankings, per-user, per-competition
- SR2.0 deployed to `new.sportsrush.co.uk` (invite-only, real data)
- Sync script: new WordPress predictions → SR2.0 (nightly during parallel running)
- Dashboard: side-by-side ranking comparison (admin view, updated daily)

### Migration Sequence (within Phase 8)
```
Step 1: Migrate to staging → run all ETL → run scoring recalculation
Step 2: Generate ranking comparison report (staging SR2.0 vs current WordPress)
Step 3: Fix any discrepancies → re-run comparison until zero delta
Step 4: Migrate to production SR2.0 instance (new.sportsrush.co.uk)
Step 5: Enable daily sync from WordPress → SR2.0
Step 6: Invite 5–10 real users to test new.sportsrush.co.uk (beta testers)
Step 7: Run parallel for minimum 2 weeks (one full prediction round)
Step 8: Daily ranking comparison; fix any discrepancies immediately
Step 9: Sign-off: zero ranking discrepancy for 7 consecutive days → ready for cutover
```

### Rollback Considerations
- ETL scripts are idempotent — can be re-run after fixes
- SR2.0 production instance is separate from WordPress — WordPress is untouched throughout
- If parallel running reveals a correctness issue, fix it on SR2.0 without affecting WordPress users

### Acceptance Criteria
- [ ] Every WordPress user has a corresponding SR2.0 user record with matching `legacy_wp_user_id`
- [ ] Every historical prediction has been migrated and scored in SR2.0
- [ ] SR2.0 total points per user per competition matches WordPress total within ±0 (or documented exception with owner sign-off)
- [ ] Private league memberships are intact in SR2.0
- [ ] Beta testers can log in and submit predictions on `new.sportsrush.co.uk`
- [ ] Sync script populates SR2.0 with same-day WordPress predictions within 24 hours
- [ ] Zero ranking discrepancy maintained for 7 consecutive days during parallel running

### Suggested Automated Tests
```
test_etl_user_count_matches_wordpress
test_etl_prediction_count_matches_wordpress
test_etl_match_count_matches_wordpress
test_scoring_recalculation_matches_wordpress_totals
test_legacy_wp_user_id_mapping_complete
test_league_membership_count_matches_wordpress
test_sync_script_idempotent
```

---

## PHASE 9 — Mobile Apps

**Starts in parallel with Phase 8 parallel running, after API is stable.**

### Objective
Build iOS and Android apps using React Native (Expo). The API is stable and battle-tested at this point. Mobile starts after the web frontend validates the API surface.

### Why This Phase Comes After Phase 6
Stated constraint: "Mobile apps should not be started until stable APIs exist." Phase 6 (web frontend) proved the APIs are stable. The `packages/api-client` is already built and shared. Mobile development is primarily a UI layer on top of proven infrastructure.

### Dependencies
- Phases 2–7 (all APIs stable and tested)
- Phase 6 (packages/api-client built and used by web)
- Phase 8 in progress (real data available)

### Deliverables
- `apps/mobile` (React Native / Expo Managed Workflow)
- Shared `packages/api-client` consumed by mobile (no duplication)
- Auth flow (Expo AuthSession, SecureStore for refresh token)
- Push notification registration (Expo push tokens)
- Rankings screen with competition filter and live WebSocket updates
- Predictions screen with per-field save and lock state
- Private leagues screen
- Fixture and results browser
- User profile and notification preferences
- Expo EAS Build pipeline in CI
- Expo EAS Update for OTA JS patches post-launch

### Migration Impact
- None (mobile is a new client for existing APIs)
- App store submission is independent of the web cutover

### Rollback Considerations
- Mobile app rollback = Expo OTA update reverting the JS bundle (< 5 minutes)
- Native binary rollback = app store revert (24–72 hours depending on platform)

### Acceptance Criteria
- [ ] iOS and Android apps pass end-to-end prediction submission flow
- [ ] Push notification received within 5 minutes of a result being published
- [ ] App opens to a valid stale cache when offline (no crash)
- [ ] Auth persists across app restarts (SecureStore refresh token)
- [ ] App passes Apple App Store and Google Play Store review

---

## PHASE 10 — Cutover

### Objective
Move `sportsrush.co.uk` from WordPress to SR2.0. A controlled, low-risk DNS switch with a 48-hour rollback window.

### Why This Phase Comes After Phase 8
Cutover requires: zero ranking discrepancy (Phase 8 sign-off), scraper reliability (Phase 5), admin tooling (Phase 5), full web frontend (Phase 6), and private leagues (Phase 7).

### Cutover Sequence
```
T-7 days:    Announce scheduled maintenance window to users (email + site banner on WordPress)
T-1 day:     Final staging smoke test with production data copy
T-0 (start): Final WordPress data sync (all predictions up to this point)
T-0 +15min:  WordPress set to maintenance mode (display message: "We're upgrading — back in 1 hour")
T-0 +20min:  Final SR2.0 data import (any predictions submitted in last sync window)
T-0 +25min:  Run ranking comparison one final time — must be zero delta
T-0 +30min:  DNS change: sportsrush.co.uk → SR2.0 Cloudflare
T-0 +35min:  Smoke tests against production SR2.0
T-0 +40min:  Health checks pass → maintenance mode lifted → users can access SR2.0
T-0 +48h:    Rollback window closes if no critical issues
T+7 days:    WordPress archived (database backup retained for 1 year)
```

### Rollback Trigger Criteria
Immediate rollback if any of the following are observed within 48 hours:
- Ranking totals for any user differ from expected values
- Predictions are being accepted after kick-off (lock rule failure)
- Payment webhook failures > 3 in any 10-minute window
- Error rate > 2% on any core endpoint
- Any admin-confirmed data loss

### Rollback Execution (< 10 minutes)
```
1. DNS revert: sportsrush.co.uk → WordPress host
2. WordPress maintenance mode disabled
3. Export any SR2.0 predictions submitted since cutover
4. Manually import those predictions into WordPress
5. Announce rollback to users
```

### Acceptance Criteria
- [ ] Smoke tests pass on production SR2.0 within 5 minutes of DNS change
- [ ] A test user can log in, submit a prediction, and see their ranking on SR2.0
- [ ] Admin can enter a result and see rankings update
- [ ] Error rate < 0.1% in first 30 minutes post-cutover
- [ ] No data loss confirmed by ranking comparison check

---

## PHASE 11 — Post-Cutover Hardening

### Objective
Stabilise, monitor, and harden SR2.0 in production. Address anything discovered during the first real-load week. Begin planning post-MVP features.

### Deliverables
- Runbook for all operational procedures (scraper failure, result correction, user support)
- Monitoring dashboards configured (error rate, API latency, job queue depth, scraper health)
- Alerting rules validated (Slack, PagerDuty per architecture doc)
- Performance review: identify any slow queries, missing indexes
- Mobile app submission (if Phase 9 is complete)
- Gamification domain planning (if prioritised)

---

## PART 3 — RECOMMENDED MVP CUT LINE

```
  ┌─────────────────────────────────────────────────────────────────────┐
  │  MVP = Phases 0–8 + Phase 10 (cutover)                             │
  │                                                                     │
  │  Included:                                                          │
  │  ✓ Canonical scoring engine                                         │
  │  ✓ Predictions with server-side lock                                │
  │  ✓ Live rankings (web)                                              │
  │  ✓ Private leagues + Stripe payments                                │
  │  ✓ Admin portal (fixtures, results, aliases, users)                 │
  │  ✓ Reliable scraper pipeline                                        │
  │  ✓ Full data migration from WordPress                               │
  │  ✓ Web frontend (desktop + responsive)                              │
  │                                                                     │
  │  Post-MVP (do not block cutover):                                   │
  │  ✗ Mobile apps (Phase 9)                                            │
  │  ✗ Gamification & Achievements (Phase 12 — future)                  │
  │  ✗ Analytics backend (Posthog used at launch, custom later)         │
  │  ✗ Subscription / recurring payment model                           │
  │  ✗ Invite code system (if OD-10 deferred)                           │
  └─────────────────────────────────────────────────────────────────────┘
```

**Do not add features between Phase 8 and Phase 10.** The cutover window is not the time to introduce risk. Any new feature that is not required to replace WordPress functionality is deferred to Phase 11.

---

## PART 4 — RECOMMENDED PARALLEL WORKSTREAMS

For a team of 2–3 engineers with AI assistance, the following work can proceed in parallel once their dependencies are met:

```
STREAM A (Core):    Phase 0 → 1 → 2 → 3 → 4 → 8 → 10
STREAM B (Admin):   Starts at Phase 2 complete → Phase 5 (admin + integrations)
STREAM C (Frontend): Starts at Phase 4 complete → Phase 6 → Phase 7 (leagues UI)
STREAM D (Mobile):  Starts at Phase 6 complete → Phase 9
```

```
Week:  1   2   3   4   5   6   7   8   9  10  11  12  13  14  15  16  17  18  19  20  21
A:    [0][0-1][1][2  ][2  ][2-3][3 ][3  ][4  ][4 ][  ][   ][   ][   ][   ][   ][8  ][8  ][10]
B:                    [   ][   ][   ][5  ][5  ][5  ][5 ][   ][   ][   ][   ][   ][   ][   ][   ]
C:                                             [   ][   ][6  ][6  ][6  ][6  ][7  ][7  ][   ][   ]
D:                                                                              [9  ][9  ][9  ][9 ]
```

**Practical split for a 2-person team:**
- Engineer 1: Stream A (core API, scoring, domains) + Stream B (admin portal when A is between phases)
- Engineer 2: Stream C (web frontend) starting at Week 10, then Stream D (mobile) from Week 15

---

## PART 5 — HIGHEST-RISK MIGRATION POINTS

### Risk 1: Scoring Formula Verification (Phase 1 + Phase 8)
**Severity: Critical.** If `calculateMatchScore` produces different totals than the WordPress custom rankings SQL, every user's all-time score will be wrong after migration. The verification script in Phase 1 and the ranking comparison report in Phase 8 are the two checkpoints. Neither is optional.

**Mitigation:** Run the verification script early (Phase 1) on a sample of real WordPress data. If it fails, fix the scoring engine before Phases 2–4 are built on top of it.

### Risk 2: Missing `round` Column in Backup (Phase 0)
**Severity: High.** The committed SQL backup does not have the `round` column. If Phase 0 uses the backup instead of a live export, the migrations will produce a schema that crashes the predictions plugin. 

**Mitigation:** The pre-Phase 0 action (live DB export) is mandatory. The backup must not be used as the schema source.

### Risk 3: Password Migration (Phase 8, Step 1)
**Severity: High.** WordPress stores passwords using `phpass` (prefix `$P$`). SR2.0 uses Argon2id. If the migration does not handle legacy hashes, all migrated users will be unable to log in.

**Mitigation:** The `phpass` library is integrated into the auth module. On first login post-migration, if the stored hash starts with `$P$`, verify with phpass, rehash with Argon2id, and silently update. Users never notice. Test this path explicitly in Phase 2.

### Risk 4: Private League Data Not in Backup (Phase 0 + Phase 7)
**Severity: High.** `custom_competitions` and `custom_competition_users` are not in the committed backup. If the live export is not taken before Phase 0, these tables' structure is unknown and the schema cannot be designed correctly.

**Mitigation:** Live DB export in Phase 0 pre-action. `SHOW CREATE TABLE custom_competitions` must be run before any schema decisions.

### Risk 5: WooCommerce → Stripe Product Mapping (Phase 7)
**Severity: Medium.** Every paid private league has a `wc_product_id` in WordPress. SR2.0 uses `stripe_product_id`. These must be mapped before Phase 8 migration, or paid league membership grants will not work post-cutover.

**Mitigation:** Create a mapping spreadsheet in Phase 7. An admin manually creates a Stripe Product for each WooCommerce product and records the mapping. Automated during Phase 8 ETL.

### Risk 6: BBC Scraper CSS Class Changes (Phase 5)
**Severity: Medium.** BBC Sport may change their CSS class names at any time. If this happens during Phase 5 testing, the scraper returns zero results silently.

**Mitigation:** The health check alert (zero results for expected date) is a Phase 5 deliverable — it must be implemented before the scraper is relied upon. Phase 5 acceptance criteria include a live test against a known date.

### Risk 7: Cutover Data Window (Phase 10)
**Severity: Medium.** Between the last WordPress sync and the DNS switch, some users may submit predictions on WordPress that are not in SR2.0. If these are not imported before the switch, those users lose their predictions.

**Mitigation:** The maintenance window is used to freeze WordPress before the final sync. The gap is bounded to < 30 minutes. Any predictions submitted in that window are re-imported from the WordPress database before the DNS switch. This is a manual step in the cutover runbook.

---

## PART 6 — TEST STRATEGY PER PHASE

### Testing Philosophy
Given a small team using AI-assisted development:
- **Unit tests:** Automated, run on every commit, written alongside the code
- **Integration tests:** Automated, run against a test database in CI, written per API route
- **E2E tests:** Playwright, run on staging before every promotion to production, written for critical user flows only
- **Migration tests:** Automated scripts that compare SR2.0 data to WordPress data — the primary correctness check
- **Manual exploratory testing:** Done by the team at the end of each phase before sign-off

| Phase | Unit | Integration | E2E | Migration | Manual |
|-------|------|-------------|-----|-----------|--------|
| 0 | Migration file tests | — | — | — | Schema review |
| 1 | Scoring function (100% branch) | — | — | Verification script | Formula review with owner |
| 2 | Module tests | Auth + fixtures API | — | — | Admin portal walkthrough |
| 3 | Lock rule tests | Prediction + scoring API | — | Historical scoring check | End-to-end result entry |
| 4 | Ranking calculation | Rankings API | — | WP comparison report | Leaderboard review |
| 5 | Scraper adapter | Ingestion pipeline | — | Alias table validation | Scraper run live test |
| 6 | Component tests | — | Auth, predictions, rankings | — | Full UX review on staging |
| 7 | Payment handler | Stripe webhook | League join + payment | Membership migration | Paid league purchase |
| 8 | ETL scripts | — | Beta user flows | Full ranking comparison | Parallel running review |
| 9 | Mobile components | — | E2E on simulator | — | Device testing |
| 10 | — | Smoke tests | Production smoke | Final ranking check | Live monitoring |

### Non-Negotiable Test Gates
These tests must pass before the next phase begins. They are not skippable under time pressure:

1. **Phase 1 gate:** Scoring function verification script matches WordPress totals
2. **Phase 3 gate:** Historical scoring check — SR2.0 scores match WordPress for a 50-user sample
3. **Phase 4 gate:** Leaderboard comparison report — zero discrepancy
4. **Phase 8 gate:** Full ranking comparison — zero discrepancy for 7 consecutive days
5. **Phase 10 gate:** Production smoke tests — login, predict, see ranking all work within 5 minutes of DNS switch

---

*Implementation sequence version 1.0. Review and update at the start of each phase. Owner Decisions that remain unresolved at Phase 0 completion will delay the phase that first depends on them — resolve all 15 before any code is written.*
