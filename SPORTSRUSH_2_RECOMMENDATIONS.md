# SportsRush 2.0 — Architecture Recommendations

## Guiding Principles

The rebuild should eliminate every category of technical debt identified in this codebase:
- **Single source of truth** for scoring logic
- **API-first** so web and mobile share the same backend
- **No scraped credentials in code** — secrets in environment variables only
- **Stateless, horizontally scalable** application tier
- **Type-safe, testable** business logic
- **Real-time** where it matters (live scores, rankings)

The target stack is a **Cloudflare-based architecture** with native mobile apps alongside the web experience.

---

## Recommended Architecture Overview

```
┌─────────────────────────────────────────────────────┐
│                  Cloudflare Edge                     │
│  - DNS, DDoS, WAF, CDN (Pages / R2 for static)      │
│  - Cloudflare Workers for edge logic / routing       │
└─────────┬──────────────────────────┬────────────────┘
          │                          │
          ▼                          ▼
  ┌───────────────┐         ┌────────────────┐
  │  Web App      │         │  Mobile Apps   │
  │  (Next.js)    │         │  (React Native │
  │  on CF Pages  │         │   via Expo)    │
  └───────┬───────┘         └────────┬───────┘
          │  REST / GraphQL           │
          ▼                          ▼
  ┌──────────────────────────────────────────┐
  │            API Server (Hono / tRPC)      │
  │    Running on Cloudflare Workers or      │
  │    a lightweight Node.js container       │
  └──────────────┬───────────────────────────┘
                 │
    ┌────────────┼────────────────┐
    ▼            ▼                ▼
┌─────────┐ ┌─────────┐  ┌─────────────┐
│ Postgres│ │  Redis  │  │  External   │
│(Neon /  │ │(Upstash)│  │  Sports API │
│ Supabase│ │real-time│  │  (optional) │
└─────────┘ └─────────┘  └─────────────┘
```

---

## Suggested Frontend Stack (Web)

### Framework: **Next.js 15** (App Router)
- React Server Components for ranked pages (no client-side waterfall for data)
- Static generation for marketing/info pages
- Server-side rendering for personalised dashboard pages
- Deployed on **Cloudflare Pages** (zero cold starts, edge-rendered)

### Styling: **Tailwind CSS** + **shadcn/ui**
- Utility-first CSS — no large CSS framework to maintain
- shadcn/ui provides accessible, unstyled component primitives
- Design tokens defined once, shared with the mobile app via a shared package

### State Management: **Zustand** (client state) + **React Query / SWR** (server state)
- Predictions form: optimistic updates with AJAX auto-save (replicates current UX)
- Rankings: polling or WebSocket subscription for live updates

### Real-Time: **Cloudflare Durable Objects** or **Supabase Realtime**
- Push ranking changes and live score updates to connected clients via WebSocket
- Eliminates the need for page refreshes to see updated scores/rankings

---

## Suggested Backend Stack

### Runtime: **Node.js** (TypeScript)
### Framework: **Hono** (for Cloudflare Workers) or **Fastify** (for a container)
- Hono is purpose-built for Cloudflare Workers, extremely lightweight
- Fastify is more full-featured if self-hosted on a VPS/container is preferred
- **tRPC** on top of Hono/Fastify for type-safe API contracts shared with the frontend and mobile app

### Key API Modules

| Module | Responsibility |
|--------|---------------|
| `auth` | User registration, login, JWT/session management |
| `fixtures` | CRUD for matches, competitions, teams |
| `predictions` | Submit, update, lock predictions |
| `scoring` | Calculate and serve scores and rankings |
| `leagues` | Public and private league management |
| `payments` | Stripe webhook handling, entitlement management |
| `scraper` | Fixture ingestion jobs (replace Python scripts) |
| `notifications` | Push notifications, email triggers |
| `gamification` | Streaks, achievements, rivals |

### Scraper Replacement: **TypeScript scraping service**
- Replace Python scripts with TypeScript scraper jobs (using `cheerio` for HTML parsing, `ofetch` for HTTP)
- Run as **scheduled Cloudflare Workers Cron Triggers** — no cron server needed
- Secrets (DB credentials, API keys) stored in Cloudflare Worker Secrets / environment variables — never in code
- Idempotent upsert logic with proper conflict handling
- Consider a paid sports data API (API-Sports, Sportradar) as the primary source to eliminate scraping fragility entirely

---

## Mobile Architecture Recommendations

### Framework: **React Native via Expo** (SDK 51+)
- Single codebase targeting iOS and Android
- Expo Go for development; EAS Build for production builds
- Expo Router for file-based navigation (same mental model as Next.js)
- Shared component library and type definitions with the web app (monorepo)

### Key Mobile Features
- **Predictions entry**: Number pad optimised for score input, swipe gestures for rounds
- **Push notifications**: Match reminders, score alerts, ranking change alerts (via Expo Notifications + FCM/APNs)
- **Live scores widget**: Background fetch or WebSocket subscription for in-progress match updates
- **Offline predictions**: Cache submitted predictions locally; sync when online
- **Biometric auth**: Face ID / fingerprint login via Expo LocalAuthentication

### Shared Code (Monorepo)
Use a **pnpm monorepo** (Turborepo) with packages:
```
apps/
  web/          (Next.js)
  mobile/       (Expo React Native)
packages/
  api/          (tRPC router definitions)
  ui/           (shared component primitives)
  scoring/      (scoring logic as pure TypeScript functions)
  types/        (shared TypeScript types)
```

Keeping scoring logic in a `packages/scoring` package means it is unit-tested once and the exact same code is used by the API, the web app, and potentially the mobile app — eliminating the dual-engine divergence of the current system.

---

## Suggested Database

### Primary: **PostgreSQL** (via **Neon** or **Supabase**)

**Why PostgreSQL over MariaDB:**
- `JSONB` columns for flexible gamification data (achievements, banter)
- Window functions (`DENSE_RANK`, `LAG`, `LEAD`) are first-class and performant
- Row-Level Security (RLS) for per-user data access control (especially useful for private leagues)
- `GENERATED ALWAYS AS` columns for derived values
- Better support for complex indexing strategies
- Native UUID support

**Neon** is recommended for Cloudflare compatibility — it supports HTTP-based queries from edge Workers without needing a persistent TCP connection pool.

**Supabase** is an alternative if you want built-in Realtime, Auth, and Storage in one platform.

### Schema Design Principles for v2

- Use UUIDs as primary keys (no sequential integer exposure)
- Store all datetimes as `TIMESTAMPTZ` (UTC-aware)
- Enforce foreign keys and NOT NULL constraints at the database level
- Version-controlled migrations using **Drizzle ORM** or **Prisma Migrate**
- Separate `competitions`, `seasons`, `rounds`, `matches`, `teams` into clean normalised tables
- Single `predictions` table with a `points_breakdown` JSONB column (computed by the API, stored for audit)
- Single `rankings` materialised view or table updated by the scoring service

---

## Suggested Auth System

### **Lucia Auth** (self-hosted, session-based) or **Better Auth**
- Full control over user data
- No third-party auth vendor dependency
- Supports email/password, OAuth (Facebook, Google) out of the box
- Sessions stored in PostgreSQL
- Works identically for web (cookies) and mobile (JWT tokens)

### Alternative: **Clerk** (managed)
- If dev speed is the priority
- Excellent React + React Native SDKs
- Handles email verification, MFA, social login, user management UI
- Higher cost at scale but zero auth maintenance

### Decisions
- Passwords: bcrypt/argon2 hashing (never MD5/SHA1)
- Sessions: HTTP-only secure cookies for web; JWT tokens in SecureStore for mobile
- OAuth: Facebook connect retained (as currently); add Google and Apple Sign-In for mobile

---

## Suggested Real-Time / Live Score Approach

### Architecture
1. **Score ingestion worker** (scheduled Cloudflare Cron every 2 minutes during match windows) fetches results from a sports API or scraper.
2. Worker computes whether any scores have changed since last run.
3. If scores changed → publish change event to **Cloudflare Durable Objects** or **Upstash Pub/Sub**.
4. Web clients subscribed via WebSocket (or SSE) receive the update and re-render the affected row.
5. Mobile clients receive a **push notification** via Expo Notifications.

### Ranking Recalculation
- Rankings are **incrementally updated** on each score change — no full batch recalculation.
- The scoring formula in `packages/scoring` is pure TypeScript; it runs inside the Worker with no database overhead for simple calculations.
- A materialised table (`rankings_snapshot`) is updated atomically per match result, not per full recalculation cycle.
- This replaces the current two-table swap mechanism with a proper transactional update.

---

## Suggested Migration Strategy Away from WordPress

### Phase 1 — API Layer (No User Impact)
1. Build the new TypeScript API alongside the live WordPress site.
2. Implement all data models: competitions, fixtures, teams, predictions, scoring, private leagues.
3. Migrate the database from MariaDB → PostgreSQL using `pgloader` or a custom ETL script.
4. Run the new scraper pipeline alongside the old Python scripts — validate output matches.
5. Build and unit-test the scoring engine in `packages/scoring`.

### Phase 2 — New Web Frontend (Parallel Run)
1. Build the Next.js web app consuming the new API.
2. Launch at a new subdomain (e.g. `app.sportsrush.co.uk`) with invite-only beta access.
3. Keep the WordPress site live at `sportsrush.co.uk` during this phase.
4. Gather user feedback; refine UX and scoring display.

### Phase 3 — Mobile App (Beta)
1. Launch the Expo mobile app on TestFlight (iOS) and Google Play internal testing.
2. Use the same API as the web app — no additional backend work.
3. Enable push notifications for match reminders and score alerts.

### Phase 4 — Cutover
1. Set the DNS for `sportsrush.co.uk` to point to Cloudflare Pages (new Next.js app).
2. Decommission the WordPress/Hostinger environment.
3. Keep a read-only database snapshot of historical WordPress data for reference.
4. Decommission Python cron scripts (replaced by Cloudflare Workers Cron).

### Data Migration Concerns
- **Users:** Migrate `wpkl_users` → new users table. Passwords (WordPress phpass hashes) must be migrated carefully — use a "login and re-hash" strategy on first login.
- **Predictions:** Migrate `pool_wpkl_predictions` → new predictions table. Historical predictions for rankings continuity.
- **Matches + Teams:** Migrate `pool_wpkl_matches`, `pool_wpkl_teams`, `pool_wpkl_matchtypes` → new schema.
- **Private Leagues:** Migrate `custom_competitions` + `custom_competition_users` → new leagues schema.
- **Scores/Rankings History:** Recalculate from raw predictions data using the new unified scoring engine — do not migrate the `pool_wpkl_scorehistory` tables directly.

### What to Abandon
- All WordPress infrastructure (CMS, plugins, themes)
- The Football Pool plugin (entirely replaced by the custom scoring engine)
- The Python scraping scripts (replaced by TypeScript Workers)
- Hostinger shared hosting (replaced by Cloudflare Pages + Workers + Neon)
- MailPoet (replaced by Resend or Postmark with a custom email service)
- WooCommerce (replaced by direct Stripe API integration)

---

## Infrastructure Cost Estimate (Cloudflare Stack)

| Service | Tier | Monthly Cost (est.) |
|---------|------|---------------------|
| Cloudflare Pages + Workers | Paid plan | ~$5–20 |
| Neon PostgreSQL | Launch plan | ~$19 |
| Upstash Redis | Pay-per-use | ~$5–20 |
| Resend (email) | Free → Pro | $0–20 |
| Stripe | % of revenue | ~0.25–1.5% |
| Expo EAS Build | Production | ~$99/mo |
| **Total** | | **~$130–180/mo** |

This compares favourably to Hostinger shared hosting which has hard scaling limits. The Cloudflare Workers platform scales to millions of requests with no infrastructure management.
