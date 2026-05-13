# SPORTSRUSH_2_TARGET_ARCHITECTURE.md
## SportsRush 2.0 — Target Platform Architecture

**Source of truth:** `SPORTSRUSH_CANONICAL_RULES.md`  
**Risk reference:** `HIGH_RISK_AREAS.md`, `VERIFIED_FINDINGS.md`  
**Approach:** This document designs the ideal long-term platform. WordPress patterns are not carried forward unless explicitly justified. Every architectural decision references a specific requirement or risk.

---

## GUIDING PRINCIPLES

1. **One canonical scoring engine.** Every surface (web, mobile, admin, API) reads from the same computed result. No scoring logic is duplicated across layers.
2. **Server-side authority.** Prediction locks, access grants, and scoring decisions are enforced on the server. The client is a display layer, not a trust boundary.
3. **Explicit over silent.** Failed operations surface errors. Unmatched team names trigger alerts. Scraper failures are observable. Nothing fails silently.
4. **Event-driven correctness.** When a result changes, a single event drives all downstream consequences — rankings, monthly totals, private leagues, notifications — in one consistent cascade.
5. **Zero secrets in code.** No credentials, tokens, or keys exist anywhere in the repository.
6. **Mobile and web are first-class equals.** Shared business logic, shared data contracts, consistent behaviour.

---

## SECTION 1 — FRONTEND ARCHITECTURE

### Framework
**Next.js (App Router) with TypeScript.**

Reasons:
- Server-side rendering for public rankings and fixture pages (SEO, first contentful paint)
- Client-side interactivity for the predictions form, live ranking updates, and private leagues
- Edge runtime via Cloudflare Workers for near-instant page delivery globally
- File-system routing reduces boilerplate; nested layouts handle competition/round context naturally
- Large ecosystem, strong TypeScript support, stable release cadence

React alternatives (Remix, SvelteKit) are viable but Next.js has the widest mobile-adjacent talent pool and strongest Cloudflare integration story.

### SSR / CSR Strategy

| Page | Strategy | Reason |
|------|----------|--------|
| Public rankings | SSR (ISR, 60s revalidation) | SEO, shareable URLs, fast cold load |
| Fixture list | SSR (ISR, 5m revalidation) | Rarely changes; cache aggressively |
| Predictions form | CSR (client component) | User-specific, real-time AJAX saves, auth-gated |
| Private league rankings | CSR | Auth-gated, member-specific |
| Monthly winner / stats | SSR (ISR, 60s) | Public, cacheable |
| Admin portal | CSR | Auth-gated, not crawled |
| Live score updates | CSR + WebSocket | Real-time, cannot be SSR |

### State Management
**Zustand for local/session state** (predictions form state, UI toggles).  
**TanStack Query (React Query) for server state** (rankings, fixtures, user data). Handles caching, background refetch, and optimistic updates without a heavy global store.

No Redux. The application does not have enough shared mutable state to justify it.

### Routing
Next.js App Router with the following URL structure:

```
/                           → Landing / latest rankings
/rankings                   → Public rankings (competition filter via ?competition=N)
/predictions                → Predictions form (auth required)
/predictions?round=N        → Specific round
/leagues                    → Private league catalogue
/leagues/[id]               → Private league rankings
/leagues/[id]/join          → Join flow
/fixtures                   → Upcoming fixtures
/results                    → Past results
/admin                      → Admin portal root (separate layout)
/admin/fixtures             → Fixture management
/admin/results              → Result entry and correction
/admin/leagues              → Private league management
/admin/users                → User management
/admin/aliases              → Team alias management
/admin/scraper              → Scraper status and logs
```

### Component Structure
Monorepo with a shared `packages/ui` design system (see below). Application components live in `apps/web/src/components` in three layers:

- **`/ui`** — Pure presentational, no data fetching, no business logic (Button, Card, Table, Modal, Badge)
- **`/features`** — Feature-specific components that may fetch their own data (PredictionForm, RankingsTable, PrivateLeagueCard)
- **`/layouts`** — Page shells (CompetitionLayout with sidebar filter, AdminLayout with sidebar nav)

### Shared Design System
A single `packages/ui` package in the monorepo, built on **Tailwind CSS + shadcn/ui** primitives. Published as an internal package consumed by both `apps/web` and `apps/admin`. Colours, typography, spacing tokens are defined once in `tailwind.config.ts` at the monorepo root.

Design tokens cover: brand colours (SportsRush primary, competition-specific accent colours), typography scale, spacing, border radius, shadow levels.

Icons: **Lucide** (consistent with shadcn/ui, tree-shakeable).

### Admin Portal Separation
The admin portal is a **separate Next.js application** (`apps/admin`) in the same monorepo, served from `admin.sportsrush.co.uk`. Reasons for separation:

- Completely separate Cloudflare Access policy (VPN or email PIN required, independent of the main site session)
- Admin bundle is never served to or downloaded by regular users
- Admin deployments are independent — a bad admin deploy cannot affect the public site
- Role checks are enforced at the infrastructure level (Cloudflare Access), not only at the application level

The admin app shares `packages/ui`, `packages/api-client`, and `packages/scoring` from the monorepo.

---

## SECTION 2 — MOBILE ARCHITECTURE

### Framework
**React Native with Expo (Managed Workflow), TypeScript.**

Reasons:
- Maximum code sharing with the web frontend (shared business logic, shared API client, shared type definitions)
- Expo handles OTA updates, push notification infrastructure (Expo Push Notifications → APNs / FCM), and native build pipelines without requiring custom native modules at launch
- Expo EAS Build replaces Xcode/Android Studio for CI builds
- When native modules are eventually needed, the managed workflow can be ejected selectively

### Shared Code Strategy
Monorepo structure enables sharing:

| Package | Web | Mobile |
|---------|-----|--------|
| `packages/api-client` | ✓ | ✓ |
| `packages/scoring` | ✓ | ✓ |
| `packages/types` | ✓ | ✓ |
| `packages/ui` | ✓ (Tailwind) | ✗ (native components) |
| `packages/hooks` | ✓ (React) | ✓ (React Native) |

The mobile app (`apps/mobile`) has its own native component layer. UI components are not shared — Tailwind does not apply to React Native. Business logic, data fetching, type definitions, and the scoring engine are shared.

### Authentication
**Expo SecureStore** for storing the refresh token on device (hardware-backed secure enclave on iOS and Android). Access tokens are short-lived (15 minutes), stored in memory (not SecureStore). The app refreshes silently using the stored refresh token.

On first launch: OAuth 2.0 via browser-based login (Expo AuthSession). No in-app WebView authentication — the system browser handles it. Social login (Google) is supported via the same OAuth flow.

### Push Notifications
**Expo Push Notifications** as the abstraction layer, routing to APNs (iOS) and FCM (Android).

Notification triggers (handled server-side):
- Predictions form opens for an upcoming round (24 hours before first match)
- Lock reminder (30 minutes before first match in the user's selected rounds)
- Result published (match completed, scores entered)
- Monthly winner announced (1st of each month, automated)
- Private league: new member joined / result updated

Push tokens are registered at login and stored against the user record. Tokens are refreshed on each app launch.

### Offline Handling
The mobile app uses **TanStack Query with persistence** (via AsyncStorage) to cache:
- Last fetched rankings (shown stale while refetching)
- Upcoming fixtures for the next 7 days
- User's current predictions

Prediction submission requires connectivity — a queued offline submission is not implemented at launch (the server-side lock makes offline queuing unsafe). The app displays a clear "You are offline — predictions cannot be saved" message.

### Live Updates
**WebSocket connection** (via the API's WebSocket endpoint) when the app is in the foreground and a live round is in progress. On background: push notifications for result events. On foreground return: automatic TanStack Query refetch.

---

## SECTION 3 — BACKEND / API ARCHITECTURE

### API Framework
**Node.js with Fastify, TypeScript.**

Reasons:
- Fastify is significantly faster than Express and has first-class TypeScript + JSON Schema support
- Fastify's schema-based request validation (via Ajv) provides automatic input sanitisation with no boilerplate
- TypeScript throughout ensures type contracts are shared with the frontend via `packages/types`
- Node.js shares runtime with the frontend monorepo (single language across the stack, shared packages)

Alternative considered: Go. Go would be faster at runtime but eliminates code sharing with the frontend and increases context-switching cost for a small team. Revisit if throughput requirements demand it.

### Service Boundaries
SR2.0 launches as a **modular monolith** — a single deployed API process with clearly separated internal modules. This gives the benefits of service separation (independent concerns, testable in isolation) without the operational overhead of microservices at early scale.

Internal modules (each owns its own DB tables and exports a service interface):

```
api/
├── modules/
│   ├── auth/          → Session management, token issuance, user identity
│   ├── users/         → User profiles, preferences, push token registration
│   ├── competitions/  → Competition (matchtype) management
│   ├── fixtures/      → Match creation, fixture management
│   ├── results/       → Result ingestion, correction, audit trail
│   ├── predictions/   → Prediction save, lock enforcement, history
│   ├── scoring/       → Canonical scoring engine, recalculation jobs
│   ├── rankings/      → Ranking queries, snapshots, monthly winner
│   ├── leagues/       → Private league management, membership
│   ├── payments/      → Stripe webhook handling, entitlement grants
│   ├── notifications/ → Push notification dispatch
│   ├── scraper/       → External data ingestion, alias resolution
│   └── admin/         → Admin-only endpoints, audit log access
```

Each module can be extracted into its own service later if scale demands it. The interfaces between modules are enforced by TypeScript — a module cannot directly query another module's tables.

### Auth Model
**JWT with short-lived access tokens and long-lived refresh tokens.**

- Access token: signed JWT, 15-minute expiry, contains `user_id`, `roles`, `session_id`
- Refresh token: opaque random string, 30-day expiry, stored in the database (revocable), delivered as an HTTP-only `Secure` `SameSite=Strict` cookie on web; stored in SecureStore on mobile
- Token rotation: each refresh issues a new refresh token and invalidates the old one (refresh token rotation)
- Session revocation: stored in a `sessions` table — signing out invalidates the token immediately

**Roles:** `user` (default), `admin`, `superadmin`. Role membership stored in the database, embedded in the access token at issue time.

### Rate Limiting
**Cloudflare Rate Limiting** as the first layer (IP-based, before the request reaches the origin).  
**Fastify Rate Limit plugin** as the second layer (route-level, user-session-based for authenticated endpoints).

Limits per environment (production defaults, configurable):
- `POST /predictions/:id` — 60 requests/minute per user (one per score field change)
- `POST /auth/login` — 10 attempts/minute per IP
- `POST /auth/refresh` — 30 requests/minute per user
- Admin endpoints — 120 requests/minute per admin session
- Scraper ingest endpoints — internal only (not exposed to internet)

### Background Jobs
**BullMQ with Redis** for job queuing and processing.

Job queues:

| Queue | Triggered by | Description |
|-------|-------------|-------------|
| `score-recalculation` | Result written or corrected | Recalculate all affected user scores for the match |
| `ranking-snapshot` | Score recalculation complete | Write a snapshot of current rankings to the snapshots table |
| `notification-dispatch` | Round open / result published / monthly winner | Dispatch push notifications to relevant users |
| `scraper-run` | Scheduled cron | Trigger fixture / result ingestion from external sources |
| `alias-resolution` | Scraper finds unknown team name | Queue for admin review |

Jobs are idempotent — running the same job twice produces the same result. Jobs include a `job_id` that prevents duplicate processing (BullMQ deduplication by job ID).

### Scheduled Tasks
Managed by **node-cron** within the API process (or by a separate Cloudflare Worker cron trigger if the API is serverless). Not web-accessible. No equivalent to `run_cron_job.php`.

| Task | Schedule | Action |
|------|----------|--------|
| Results scraper | Every hour during season (configurable) | Fetch yesterday's and today's results |
| Fixture scraper | Every Monday 06:00 UTC | Fetch next 2 weeks of fixtures |
| Monthly winner announcement | 00:05 UTC on 1st of each month | Calculate winner for previous month, dispatch notification |
| Stale session cleanup | Daily 03:00 UTC | Delete expired sessions from DB |
| Scraper health check | Every 6 hours | Assert scraper returned > 0 results for expected dates; alert if not |

### Live Events / WebSocket Strategy
**WebSocket server via Fastify + @fastify/websocket**, backed by Redis pub/sub for multi-instance coordination.

When a result is written:
1. Result module emits a `result.published` event to Redis pub/sub channel `results`
2. Scoring worker picks it up, recalculates, emits `rankings.updated` to Redis pub/sub
3. WebSocket server subscribers receive `rankings.updated` and push a message to all connected clients watching that competition
4. Clients receive the event and refetch rankings via TanStack Query (no raw WS data is rendered directly)

WebSocket connections require a valid access token (passed in the initial HTTP upgrade request header). Unauthenticated upgrade requests are rejected.

---

## SECTION 4 — DATABASE ARCHITECTURE

### Database Choice
**PostgreSQL (primary datastore).**  
**Redis (cache, job queue, pub/sub, rate limiting state).**

PostgreSQL reasons:
- Strong ACID guarantees for scoring, payment, and membership operations
- Native JSON/JSONB columns for flexible metadata (notification payloads, scraper raw data)
- Window functions (`DENSE_RANK()`, `LAG()`, `ROW_NUMBER()`) for rankings queries — confirmed already in use in the current system
- Row-level security for future multi-tenancy if needed
- Excellent managed hosting options (Supabase, Neon, Railway Postgres, RDS)

No MongoDB, no DynamoDB. Relational data (users, predictions, matches, memberships) belongs in a relational database.

### Schema Organisation
Tables are grouped by bounded context. All tables use `snake_case`. No WordPress-style prefix on table names.

```
-- Identity
users                       → id, email, display_name, role, created_at
sessions                    → id, user_id, refresh_token_hash, expires_at, revoked_at
push_tokens                 → id, user_id, token, platform, created_at

-- Competitions & Fixtures
competitions                → id, name, sport, visibility, created_at
matches                     → id, competition_id, home_team_id, away_team_id,
                              play_date (timestamptz), round, round_name,
                              status (enum), home_score, away_score, created_at, updated_at
teams                       → id, name, short_name, logo_url
team_aliases                → id, team_id, external_name, source (bbc/rlcom/etc), created_at

-- Predictions & Scoring
predictions                 → id, user_id, match_id, home_score, away_score,
                              joker (bool), locked_at, created_at, updated_at
match_scores                → id, user_id, match_id, points_exact, points_toto,
                              points_home_bonus, points_away_bonus, points_diff_bonus,
                              points_joker_multiplier, total_points, calculated_at
ranking_snapshots           → id, competition_id, snapshot_date, user_id, rank,
                              total_points, month_points, created_at

-- Results audit
result_corrections          → id, match_id, previous_home, previous_away,
                              corrected_home, corrected_away, corrected_by, corrected_at, reason

-- Private Leagues
leagues                     → id, name, competition_id, owner_id, is_paid, price_gbp,
                              stripe_product_id, prize_gbp, logo_url, status, created_at
league_members              → id, league_id, user_id, joined_at, access_granted_by,
                              payment_intent_id (nullable)

-- Payments
payment_events              → id, user_id, stripe_event_id, event_type, league_id,
                              amount_pence, status, processed_at, raw_payload (jsonb)

-- Scraper
scraper_runs                → id, source, run_date, status, matches_found,
                              matches_updated, errors (jsonb), created_at
scraper_unresolved_aliases  → id, scraper_run_id, external_name, source, raw_context, created_at

-- Audit
audit_log                   → id, user_id, action, entity_type, entity_id,
                              before (jsonb), after (jsonb), ip_address, created_at
```

### Migration Strategy
**Sequential numbered SQL migration files**, applied by a migration runner (recommend **golang-migrate** or **node-pg-migrate**).

```
migrations/
├── 001_initial_schema.sql
├── 002_add_round_name_to_matches.sql
├── 003_add_league_status_column.sql
...
```

Rules:
- Migrations are append-only and never edited after they run in any environment
- A `schema_migrations` table records applied migrations with timestamps
- Destructive migrations (DROP COLUMN, DROP TABLE) require a separate `down` migration file
- Production migrations require a manual approval step and a database backup taken immediately before
- All schema changes go through the migration system — no ad-hoc `ALTER TABLE` statements

### Auditing / Versioning
Every table that holds business-critical data has `created_at` and `updated_at` timestamps. High-importance mutations (result corrections, access grants, prediction unlocks) write to the `audit_log` table with before/after snapshots in JSONB.

The `result_corrections` table provides a dedicated, queryable history of all score changes separate from the general audit log.

### Caching Approach
**Redis** with explicit cache keys and TTLs.

| Data | Cache key pattern | TTL | Invalidation |
|------|------------------|-----|-------------|
| Rankings for competition | `rankings:{competition_id}` | 60 seconds | On `rankings.updated` event |
| Monthly winner | `monthly_winner:{competition_id}:{YYYY-MM}` | 5 minutes | On `rankings.updated` |
| Upcoming fixtures | `fixtures:upcoming:{competition_id}` | 5 minutes | On fixture create/update |
| User predictions for round | `predictions:{user_id}:{round}:{competition_id}` | 30 seconds | On prediction save |
| Team aliases lookup | `aliases:{source}` | 1 hour | On alias table update |

No cache stampede: cache keys are populated using a **stale-while-revalidate** pattern backed by BullMQ background jobs.

### Analytics / Event Tracking
A `user_events` table (or separate analytics database) captures behavioural events:
- Prediction submitted
- Prediction form opened
- League joined
- Match viewed

At launch, these are written to PostgreSQL. Future: stream to a time-series store (ClickHouse, TimescaleDB) or an analytics SaaS (Posthog, Plausible) for dashboard reporting without impacting the primary database.

---

## SECTION 5 — SCORING / RANKINGS ARCHITECTURE

### Single Canonical Scoring Engine
A single `packages/scoring` TypeScript package, usable by both the API and (read-only) by the frontend.

The package exports one pure function with no side effects:

```typescript
calculateMatchScore(
  result: { home: number; away: number },
  prediction: { home: number; away: number; joker: boolean },
  config: ScoringConfig
): MatchScore

// ScoringConfig contains: exactPoints, totoPoints, homeBonusPoints,
// awayBonusPoints, diffBonusPoints, diffBonusMode, jokerMultiplier
// All values are loaded from the database config table at startup.

// MatchScore contains: pointsExact, pointsToto, pointsHomeBonus,
// pointsAwayBonus, pointsDiffBonus, pointsJoker, total
```

This function is:
- Deterministic (same inputs always produce same outputs)
- Fully unit tested (every scoring scenario from §1.1–1.6 of the canonical rules is a test case)
- Framework-agnostic (no database calls, no side effects, importable from any context)

The scoring config is loaded once at API startup and refreshed via a config change event. An admin cannot change the scoring formula mid-season without an explicit versioned config change (logged to the audit log).

### Recalculation Strategy
**Event-driven, cascading, atomic.**

When a result is written to the database:

```
1. results module writes match.home_score, match.away_score, match.status='completed'
2. results module emits 'result.written' event (BullMQ)
3. scoring worker picks up the event
4. scoring worker fetches all predictions for the match
5. scoring worker calls calculateMatchScore() for each prediction
6. scoring worker writes/updates match_scores rows in a single transaction
7. scoring worker emits 'scores.updated' event (BullMQ)
8. rankings worker picks up 'scores.updated'
9. rankings worker invalidates Redis cache for affected competition(s)
10. rankings worker writes a new ranking_snapshot row
11. rankings worker emits 'rankings.updated' to Redis pub/sub
12. WebSocket server receives 'rankings.updated', notifies connected clients
13. Notification worker receives 'scores.updated', dispatches result push notifications
```

Every step is logged. If any step fails, BullMQ retries with exponential backoff. Steps 4–6 are transactional — partial score writes do not occur.

### Historical Recalculation
When a result is **corrected**:

Same cascade as above, but the scoring worker first reads the previous `match_scores` values, writes a `result_corrections` row, then recalculates and overwrites `match_scores`. All downstream steps (ranking snapshots, cache invalidation, notifications) follow the same path. No separate "recalculation" command is needed — correction is a first-class operation.

### Ranking Snapshots
Ranking snapshots (`ranking_snapshots` table) serve two purposes:
1. Historical record — "what was the ranking on date X?"
2. Performance — read-heavy public ranking pages can serve from the latest snapshot rather than recalculating on every request

Snapshots are written after every score recalculation. The public rankings API reads from the latest snapshot (or a fresh calculation if no snapshot exists). Admin correction causes a new snapshot immediately.

### Tie Handling
DENSE_RANK semantics enforced at the database level using PostgreSQL's `DENSE_RANK()` window function. Tiebreaker columns (to be confirmed per Owner Decision OD-04) are included in the `ORDER BY` clause of the window function — the tiebreaker is applied consistently because it is part of the single query, not applied afterwards in application code.

---

## SECTION 6 — FIXTURE / RESULTS INGESTION ARCHITECTURE

### External APIs (Primary)
Replace the CSS scraper with a commercial sports data API as the primary source of truth.

Recommended evaluation candidates:
- **API-Football (RapidAPI)** — broad football coverage, predictable JSON schema
- **SportRadar** — professional-grade, used by broadcasters; more expensive
- **TheSportsDB** — free tier available, Rugby League coverage variable

For Rugby League specifically: **RL.com** or **Betfair Exchange API** as data sources. The current `real-score-updater-rlcom-superleague.py` scraper is the fallback model, not the primary.

**Evaluation criteria before vendor selection:**
- Does it cover Super League and all competitions currently in SportsRush?
- What is the result publication latency after full-time?
- Is there a webhook / push option, or is polling required?
- What are the rate limits and pricing?

### Scraper Fallback Strategy
If a commercial API is not available or affordable for all competitions, the BBC Sport and RL.com scrapers are retained as fallback, but redesigned:

- Scrapers are isolated in the `scraper` module with a well-defined output schema
- Each scraper outputs `ScraperResult[]` — a standard type regardless of source: `{ home_team_external_name, away_team_external_name, home_score, away_score, play_date, competition_external_name }`
- The scraper does not know about DB team IDs — alias resolution is handled by the `scraper` module separately
- CSS class names are extracted to a configuration file, not embedded in scraping logic. When BBC changes their HTML, only the config changes (not the code)
- If zero results are returned for a date that should have matches, a `scraper.health.failure` alert fires (email + admin dashboard flag)

### Alias Management
The `team_aliases` table (per §4.3 of canonical rules) is managed via the admin portal:
- Admin sees a list of all unresolved aliases from the `scraper_unresolved_aliases` table
- Admin selects the correct team from a searchable dropdown and submits
- A new `team_aliases` row is created
- The scraper is re-run for the affected date (via admin trigger)

The admin notification for unresolved aliases fires immediately — not on the next page load.

### Ingestion Validation
Before writing any scraped data to the database, the ingestion pipeline validates:
1. Team names resolve to known `team_id` values via the alias table (if not, enqueue to `scraper_unresolved_aliases` and skip)
2. Match `play_date` is in the past (we do not write results for future matches)
3. Match exists in the `matches` table (result updates only; new fixture creation is a separate admin-only flow)
4. Scores are non-negative integers less than a sanity cap (e.g. < 100 — a result of 150-0 is more likely a scraper error than a real score)
5. The match `status` is `scheduled` or `completed` (not `void` or `postponed`)

Validation failures are logged with the raw scraped data and the specific failure reason.

### Admin Correction Workflow
An admin correction is a first-class UI workflow in the admin portal:
1. Admin searches for the match (by date, teams, or competition)
2. Current scores are shown with an "Edit Result" button
3. Admin enters corrected scores and provides a mandatory reason string
4. Confirmation modal shows before/after values
5. On confirm: the result is written, a `result_corrections` row is created, the full recalculation cascade fires
6. The admin is shown the recalculated rankings immediately

Corrections cannot be made by non-admin users. All corrections appear in the audit log.

---

## SECTION 7 — PAYMENTS / PRIVATE LEAGUE ARCHITECTURE

### Stripe Integration
**Stripe Checkout + Stripe Webhooks** (not a WooCommerce layer).

Flow:
1. User clicks "Join League" on a paid private league
2. Frontend calls `POST /leagues/{id}/checkout` (authenticated)
3. API creates a Stripe Checkout Session with `metadata: { league_id, user_id }` and an idempotency key of `checkout-{user_id}-{league_id}-{timestamp_rounded_to_5min}`
4. Frontend redirects to Stripe-hosted Checkout (no card data touches SR2.0 servers)
5. On payment success: Stripe fires `checkout.session.completed` webhook to `POST /webhooks/stripe`
6. Webhook handler verifies the Stripe signature before processing any payload
7. Webhook handler calls `leagues.grantAccess(user_id, league_id, payment_intent_id)` — idempotent
8. Webhook stores the raw event in `payment_events` with status `processed`
9. User is redirected to the league rankings page (Stripe Checkout `success_url`)

### Idempotency Handling
Every access grant operation checks for an existing `league_members` row before inserting. The database has a `UNIQUE(league_id, user_id)` constraint as the final guarantee. Duplicate Stripe webhooks (Stripe may deliver the same event multiple times) are deduplicated via the `stripe_event_id` column in `payment_events` — if an event ID is already present with status `processed`, the handler returns 200 immediately with no DB write.

### Entitlements Model
Access to a private league is represented as a row in `league_members`. The entitlement is checked on every request to a league-gated endpoint. There is no session-level caching of entitlements — the DB check is fast (indexed on `league_id, user_id`) and always current (revocation is immediate).

Entitlement sources tracked in `league_members.access_granted_by`:
- `admin` — manual admin grant
- `payment` — Stripe webhook (stored with `payment_intent_id`)
- `invite` — invite code mechanism (future)

### Subscription / Future Monetisation Support
The `leagues` table includes a `stripe_product_id` column, and `league_members` includes `payment_intent_id`. The schema is designed to accommodate:
- **One-time payment** (current model): Stripe Checkout with a Price in payment mode
- **Recurring subscription** (future): Stripe Checkout with a Price in subscription mode; cancellation hook fires `leagues.revokeAccess()`
- **Site-wide subscription** (future): A `user_subscriptions` table granting access to all visible leagues while a Stripe subscription is active
- **Prize pool contributions** (future): `leagues.prize_gbp` is already tracked in the schema

No subscription logic is built at launch — the schema accommodates it without migration.

---

## SECTION 8 — SECURITY ARCHITECTURE

### Auth / Session Model
See Section 3 (Auth Model) for token details. Additional rules:

- Passwords are hashed with **bcrypt** (cost factor 12) or **Argon2id** (recommended)
- Email verification is required before a new account can submit predictions
- Password reset uses a short-lived (15-minute), single-use, signed token delivered to the registered email
- OAuth 2.0 social login (Google initially) is supported — social accounts have no stored password
- Failed login attempts are rate-limited at the IP level (Cloudflare) and the user-account level (5 failed attempts → 30-minute lockout with unlock-via-email option)

### CSRF Strategy
Web requests use **double-submit cookie** CSRF protection:
- On session creation, a random `csrf_token` is set as a non-HTTP-only cookie (readable by JS)
- Every state-changing request includes the `csrf_token` value in the `X-CSRF-Token` header
- The server compares the header value to the cookie value; mismatch → 403

Mobile API requests use the `Authorization: Bearer <access_token>` header — no CSRF risk (cookies are not sent from mobile clients, making CSRF impossible by default).

No WordPress-style nonces. The double-submit pattern is stateless and scales without a nonce store.

### Secrets Management
**No secrets in code or environment files in the repository.**

Secrets are managed via:
- **Cloudflare Workers secrets** (for Cloudflare Worker deployments)
- **Platform environment variables** injected at runtime (Railway / Render / Fly.io)
- A `.env.example` file in the repository with placeholder values and documentation — never `.env` with real values

Required secrets (stored externally):
- `DATABASE_URL` — PostgreSQL connection string
- `REDIS_URL` — Redis connection string
- `JWT_SECRET` — signing key for access tokens
- `STRIPE_SECRET_KEY` — Stripe API key
- `STRIPE_WEBHOOK_SECRET` — Stripe webhook signature verification key
- `SPORTS_API_KEY` — commercial fixture/results API key
- `PUSH_NOTIFICATION_KEY` — Expo push notification key

Rotation procedure: update the secret in the platform's secret store, redeploy. No file changes, no code changes.

### Admin Permissions
Three-layer admin security:

1. **Infrastructure layer:** Cloudflare Access protects `admin.sportsrush.co.uk` — requires an email PIN or SSO before the browser can reach the application at all
2. **Application layer:** Every admin API endpoint calls `assertRole(request, 'admin')` at the start of the handler — this check is not optional and is not inherited from middleware alone
3. **Database layer:** The admin DB user has `SELECT, INSERT, UPDATE` on most tables but `DELETE` only on specific tables — destructive operations require an explicit admin escalation or are soft-deletes

Admin roles:
- `admin` — full access to fixture management, result entry, alias management, league management, user management
- `superadmin` — additionally: scoring config changes, user role changes, audit log access

### Audit Logging
The `audit_log` table captures every state-changing admin action. Log entries are immutable — the audit log table has no `UPDATE` or `DELETE` grants for any application DB user. Entries include:

- `user_id` (the admin performing the action)
- `action` (e.g. `result.corrected`, `league.member.added`, `alias.created`)
- `entity_type` and `entity_id` (what was changed)
- `before` and `after` as JSONB (the state before and after the change)
- `ip_address` and `user_agent` of the admin's session
- `created_at`

Audit logs are retained for 2 years minimum (legal/compliance baseline).

### Abuse Prevention
- Rate limiting at Cloudflare (IP level) and API (user level) as defined in Section 3
- Prediction saves are validated against the server-side lock — replayed POST requests after lock time are rejected
- Webhook endpoints verify signatures before processing any payload (Stripe: `stripe-signature` header)
- Admin endpoints are behind Cloudflare Access (separate auth layer from user login)
- File uploads (league logos, team badges) are validated for type, size, and scanned for malware before storage — served from Cloudflare R2, not directly from the application server

---

## SECTION 9 — DEPLOYMENT ARCHITECTURE

### Environments

| Environment | Purpose | URL | Database |
|-------------|---------|-----|----------|
| `local` | Developer machine | localhost | Local PostgreSQL |
| `dev` | Shared development, branch previews | `dev.sportsrush.co.uk` | Dev PostgreSQL instance |
| `staging` | Pre-production, full data clone, QA | `staging.sportsrush.co.uk` | Anonymised clone of production |
| `production` | Live site | `sportsrush.co.uk` | Production PostgreSQL |

**No shared secrets between environments.** Each environment has its own independently rotatable credentials.

Staging must be functionally identical to production (same config, same Stripe test mode, same Cloudflare setup). The only differences are credentials and Stripe mode (test vs live).

### CI/CD
**GitHub Actions** for all CI/CD pipelines.

Pipeline stages:

```
Push to any branch:
  → Lint (ESLint, TypeScript type check)
  → Unit tests (scoring engine, alias resolver, scoring edge cases)
  → Integration tests (API endpoint tests against a test database)
  → Build check (Next.js build succeeds)

Pull request merged to main:
  → All above +
  → Deploy to dev environment (automatic)
  → Run database migrations on dev
  → Run smoke tests against dev

Manual promotion to staging:
  → Deploy to staging
  → Run migrations on staging
  → Run full E2E test suite (Playwright)

Manual promotion to production:
  → Approval gate (requires two approvers in GitHub)
  → Take database backup (automated, verified before proceeding)
  → Apply migrations (with rollback plan verified)
  → Blue/green deploy (old version remains live until health checks pass on new version)
  → Smoke tests against production
  → Automated rollback if smoke tests fail
```

### Rollback Strategy
**Blue/green deployment** on the API and frontend: the previous version remains deployed and receives no traffic while the new version warms up. If health checks fail, traffic is rerouted to the previous version within 30 seconds.

**Database rollback:** Every production migration includes a `down` migration. If a deployment causes a database-level issue, the `down` migration is applied. Application code that ran against the new schema must be compatible with the rolled-back schema for at least one deploy cycle (backwards-compatible migrations only — no dropping columns that the current code still reads).

### Infrastructure Separation
```
Cloudflare (edge/CDN)
├── DNS
├── CDN cache (public rankings, fixture pages)
├── Cloudflare Access (admin subdomain protection)
├── Rate Limiting (WAF rules)
└── R2 (static assets: team logos, league banners)

Application hosts (e.g. Railway, Fly.io, Render)
├── apps/web (Next.js)
├── apps/admin (Next.js)
├── api (Fastify)
└── workers (BullMQ consumers — can be same or separate process)

Managed services
├── PostgreSQL (Supabase, Neon, or Railway Postgres)
├── Redis (Upstash Redis or Railway Redis)
└── Stripe (payments)

Mobile
├── Expo EAS Build (CI/CD for iOS/Android)
└── Expo EAS Update (OTA JS updates)
```

### Monitoring / Logging
**Structured JSON logs** from the API (`pino` logger — Fastify's native logger). Every request logs: `request_id`, `user_id` (if authenticated), `method`, `url`, `status_code`, `duration_ms`.

**Error tracking:** Sentry for both the API and the frontend web apps. Source maps uploaded at build time. Mobile crash reporting via Sentry's React Native SDK.

**Metrics and alerting:** Datadog or Grafana Cloud (whichever the operator prefers). Key alerts:
- Scraper returns 0 results for an expected date (Slack alert, <5 min notification)
- API error rate > 1% over 5-minute window (PagerDuty)
- Database connection pool exhausted (PagerDuty)
- Stripe webhook failures > 3 in 10 minutes (Slack alert)
- Ranking recalculation job queue depth > 50 (Slack warning)

---

## SECTION 10 — MIGRATION STRATEGY

### Principle
**Zero big-bang migrations.** The WordPress site remains live and fully functional throughout the migration. SR2.0 is built and validated in parallel. The cutover is a DNS change with instant rollback capability.

### Phase 1: Foundation (Weeks 1–4)
- Set up the SR2.0 monorepo, CI/CD, and all three environments
- Export the live production database schema (including tables missing from the backup: `custom_competitions`, `custom_competition_users`, `wpkl_pool_wpkl_scrape_competitions`)
- Write and validate migration files 001–N that produce a clean SR2.0 schema from scratch
- Resolve all 15 Owner Decisions from `SPORTSRUSH_CANONICAL_RULES.md` — no scoring or rules code is written until OD-01 through OD-15 are resolved
- Implement the canonical scoring engine (`packages/scoring`) with full unit test coverage before any other module is started

### Phase 2: Core API (Weeks 5–10)
- Build API modules in dependency order: auth → competitions → fixtures → predictions → scoring → rankings
- All modules have integration tests
- Scraper rewrite (or API vendor selection) with alias management
- Admin portal: fixture management, result entry, alias management
- WordPress site remains live throughout — no users are migrated yet

### Phase 3: Frontend and Mobile Alpha (Weeks 11–16)
- Build SR2.0 web frontend (public rankings, predictions form, private leagues)
- Build mobile app (React Native / Expo)
- Payments integration (Stripe Checkout + webhooks)
- Internal testing: verify that SR2.0 rankings match the WordPress site rankings for the same historical data
- Any discrepancy is a bug in SR2.0 — investigate and fix before proceeding

### Phase 4: User Migration
**Users are migrated, not asked to re-register.**

Migration steps:
1. Export WordPress `wp_users` and `wp_usermeta` tables
2. Transform to SR2.0 `users` schema (map `display_name`, `user_email`, `user_registered`)
3. Passwords: WordPress uses `phpass` hashing (a non-standard bcrypt variant). Options:
   - **Option A (recommended):** Migrate password hashes into SR2.0. On first login post-cutover, if the stored hash is a legacy phpass hash, verify it using a phpass library, then immediately rehash with bcrypt/Argon2id and replace the stored hash. Transparent to the user.
   - **Option B:** Force a password reset for all users at cutover. Simpler but disruptive.
4. Private league memberships are migrated from `custom_competition_users`
5. Historical predictions are migrated from `pool_wpkl_predictions`
6. Historical match scores are migrated from `pool_wpkl_matches`
7. SR2.0 scoring engine recalculates all historical scores from the migrated predictions — this is the canonical recomputation that produces the verified baseline

### Phase 5: Parallel Running (2 Weeks)
Run SR2.0 and WordPress simultaneously:
- SR2.0 is accessible at `new.sportsrush.co.uk` (invite-only for testers)
- Both sites show live data (scraper runs on both systems)
- Predictions submitted on WordPress are replayed to SR2.0 via a sync script (not vice versa — WordPress is still the master)
- Rankings are compared daily — any discrepancy investigated and resolved
- Load testing performed against SR2.0 staging

### Phase 6: Cutover
1. Maintenance window: 30–60 minutes, announced 1 week in advance
2. Final data sync from WordPress to SR2.0 (predictions, any last results)
3. WordPress set to maintenance mode (read-only, no new predictions accepted)
4. SR2.0 final data import and ranking verification
5. DNS change: `sportsrush.co.uk` → SR2.0 Cloudflare
6. Health checks pass → cutover complete
7. WordPress remains running but inaccessible via the main domain for 48 hours (rollback window)

### Rollback Safety
For 48 hours post-cutover:
- The WordPress database is not deleted or modified
- The WordPress application server remains running, pointed at the old database
- Rollback is a single DNS change back to the WordPress host
- If rollback is triggered: any predictions submitted to SR2.0 in the rollback window are manually exported and imported into WordPress (small number expected in a 48-hour window)

After 48 hours with no rollback: WordPress is archived. Database backup is taken and stored for 1 year.

---

## ARCHITECTURE DECISION RECORD (ADR) SUMMARY

| Decision | Choice | Key reason |
|----------|--------|------------|
| API language | TypeScript / Node.js | Shared types and packages with frontend |
| API framework | Fastify | Performance, schema validation, TypeScript-first |
| Frontend framework | Next.js | SSR + CSR flexibility, Cloudflare Edge compatibility |
| Mobile framework | React Native (Expo) | Code sharing with web, OTA updates |
| Database | PostgreSQL | Relational integrity, window functions, mature ecosystem |
| Cache / queue | Redis (BullMQ) | Job queuing + pub/sub in one system |
| Payments | Stripe (direct, no WooCommerce) | Idempotency, webhooks, no WordPress dependency |
| Auth | JWT + refresh tokens | Stateless tokens for mobile; revocable sessions |
| Admin isolation | Separate subdomain + Cloudflare Access | Defence in depth; admin bundle never served to users |
| Deployments | Blue/green via GitHub Actions | Zero-downtime with instant rollback |
| Scoring engine | Single shared TypeScript package | Eliminates the dual-engine problem confirmed in VERIFIED_FINDINGS.md |

---

*This document is the target state. It does not describe the migration path in full implementation detail — that is a phase-by-phase project plan built once Owner Decisions are resolved and the team is assembled.*
