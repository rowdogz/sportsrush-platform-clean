# Local development

This runbook brings up the full SportsRush platform locally on macOS for end-to-end
testing:

- `apps/api`
- `apps/web`
- `apps/admin`
- `apps/mobile`

## Prerequisites

- macOS
- Node.js `20.x`
- `pnpm` `10.x`
- Xcode + iOS Simulator if you want native iOS testing
- Expo Go if you want physical-device testing

## One-time setup

From the repository root:

```bash
pnpm install --frozen-lockfile
cp apps/api/.dev.vars.example apps/api/.dev.vars
```

Generate a local JWT secret and paste it into `apps/api/.dev.vars`:

```bash
openssl rand -base64 32
```

## Local database

Reset, migrate, and seed the local D1 database:

```bash
pnpm db:reset:local
```

This removes the local Wrangler D1 SQLite state under
`apps/api/.wrangler/state/v3/d1/miniflare-D1DatabaseObject`, reapplies
migrations, and seeds realistic SportsRush demo data.

If you only need to apply new migrations:

```bash
pnpm db:migrate:local
```

If you only need to refresh the seed rows:

```bash
pnpm db:seed:dev
```

## Start the platform

Run all four apps together:

```bash
pnpm dev
```

Stable local ports:

- API: [http://localhost:8788](http://localhost:8788)
- Web: [http://localhost:3000](http://localhost:3000)
- Admin: [http://localhost:3001](http://localhost:3001)
- Expo Metro: [http://localhost:8081](http://localhost:8081)

If you only need the browser apps and API:

```bash
pnpm dev:core
```

If you only need Expo:

```bash
pnpm dev:mobile
```

## Local env defaults

Local browser apps no longer require checked-in `.env.local` files:

- `apps/web` defaults to `http://localhost:8788` for the API in Vite dev mode
- `apps/admin` defaults to `http://localhost:8788` for the API in Vite dev mode
- `apps/web` defaults the admin link to `http://localhost:3001` in Vite dev mode

Optional example env files:

- `apps/web/.env.example`
- `apps/admin/.env.example`
- `apps/mobile/.env.example`

## Seeded demo accounts

Public demo user:

- Email: `fan@sportsrush.test`
- Password: `password-123`
- Role: `user`

Admin demo user:

- Email: `admin@sportsrush.test`
- Password: `password-123`
- Role: `superadmin`

## Seeded demo data

The seed dataset includes:

- competitions: Super League, Challenge Cup, NRL
- seasons: 2025 and 2026 coverage
- rounds: seeded across the active competitions
- teams and aliases for realistic fixture/admin testing
- completed, scheduled, and postponed fixtures
- a seeded private league: `Dev Super League Predictors`
- seeded predictions and scoring rows for demo users
- audit events for dashboard and operations screens

## Web checks

Expected local browser flow on [http://localhost:3000](http://localhost:3000):

- homepage renders
- competitions loads seeded competitions
- fixtures loads seeded fixtures
- results and rankings render seeded data
- predictions supports login and seeded prediction state
- leagues and profile pages work with the seeded demo user
- admin link opens the local admin app

## Admin checks

Expected local admin flow on [http://localhost:3001](http://localhost:3001):

- login works with `admin@sportsrush.test`
- dashboard renders seeded summaries
- competitions, seasons, rounds, teams, aliases, fixtures, users, audit log render
- private leagues renders the seeded league
- operations renders scoring/automation/admin tooling against seeded data

## Mobile checks

The Expo app defaults to `http://localhost:8788`, which works for the iOS
Simulator on the same Mac.

Start Metro:

```bash
pnpm dev:mobile
```

Open the iOS simulator:

```bash
cd apps/mobile
pnpm ios
```

For Expo Go on a physical device, create `apps/mobile/.env.local` with your
Mac's LAN IP address:

```bash
EXPO_PUBLIC_API_BASE_URL=http://192.168.1.249:8788
```

Then restart Metro and scan the Expo QR code.

## Healthcheck

Once API, web, and admin are running:

```bash
pnpm healthcheck:local
```

This checks:

- `GET /health`
- `GET /v1/public/fixtures?page=1&limit=1`
- web shell availability on port `3000`
- admin shell availability on port `3001`

## Troubleshooting

- `JWT_SECRET` error: update `apps/api/.dev.vars` with a generated value
- API not reachable from web/admin: confirm port `8788` is free
- Expo app cannot reach the API on a physical device: use your Mac LAN IP in
  `apps/mobile/.env.local`
- stale database state: run `pnpm db:reset:local`
- missing seed data in admin: make sure the API started after migrate/seed
- floating Vite ports from older shells: stop old `vite` processes before rerunning
