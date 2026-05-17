# Local dev database seeding

This app includes a dev-only seed dataset for making the admin UI useful during local manual testing.

The seed data is intentionally safe and repeatable:

- Uses the existing D1 schema and migrations.
- Uses `INSERT ... ON CONFLICT ... DO UPDATE` upserts so it can be run more than once.
- Targets the local `sportsrush-dev` D1 database only.
- Does not contain production secrets.
- Does not touch staging or production unless someone deliberately runs it there with a different command.

## Seeded data

The seed file lives at `apps/api/seeds/dev-sportsrush.sql` and includes:

- Rugby League sport row.
- Core competitions: Super League, Challenge Cup, and NRL.
- 2025/2026 seasons.
- Super League, Challenge Cup, and NRL rounds.
- Representative teams and aliases.
- Scheduled, postponed, and completed fixtures with realistic venues and scores.
- A dev superadmin user for logging into the admin app.
- A few audit events so dashboard/audit screens have data.

## Dev admin login

Use this account only for local development:

- Email: `admin@sportsrush.test`
- Password: `password-123`
- Role: `superadmin`

## Apply from a clean local D1 database

From the repository root:

```bash
cd apps/api
pnpm db:migrate:local
pnpm db:seed:dev
```

Equivalent Wrangler commands:

```bash
cd apps/api
wrangler d1 migrations apply sportsrush-dev --local
wrangler d1 execute sportsrush-dev --local --file seeds/dev-sportsrush.sql
```

The local D1 SQLite file is created under `apps/api/.wrangler/`, which is gitignored.

## Re-run safely

The seed command is idempotent. To refresh seeded rows after code changes:

```bash
cd apps/api
pnpm db:seed:dev
```

Existing seeded IDs are updated in place. Data you create manually through the admin UI is left alone unless it reuses the same IDs or unique keys as this seed file.

## Start local admin testing

In one terminal:

```bash
cd apps/api
pnpm dev -- --local
```

In another terminal:

```bash
cd apps/admin
pnpm dev
```

Make sure `apps/admin/.env.local` points to the local API, for example:

```bash
VITE_API_BASE_URL=http://localhost:8788
```

Then open the admin app and log in with the dev admin credentials above. The dashboard, competitions, seasons, rounds, teams, aliases, fixtures, users, and audit log screens should show seeded data.

## Guardrails

- Do not add real production credentials to the seed file.
- Do not run this seed against production.
- Prefer adding realistic structured seed rows here until a verified live WordPress export/import pipeline exists.
