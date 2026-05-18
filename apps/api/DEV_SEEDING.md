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
- A regular demo user for public web/mobile prediction flows.
- A few audit events so dashboard/audit screens have data.

## Dev admin login

Use this account only for local development:

- Email: `admin@sportsrush.test`
- Password: `password-123`
- Role: `superadmin`

Public app demo user:

- Email: `fan@sportsrush.test`
- Password: `password-123`
- Role: `user`

## Apply from a clean local D1 database

From the repository root:

```bash
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
pnpm db:seed:dev
```

Existing seeded IDs are updated in place. Data you create manually through the admin UI is left alone unless it reuses the same IDs or unique keys as this seed file.

## Start local platform testing

From the repository root:

```bash
pnpm db:reset:local
pnpm dev
```

Expected local ports:

- API: `http://localhost:8788`
- Web: `http://localhost:3000`
- Admin: `http://localhost:3001`
- Expo Metro: `http://localhost:8081`

If you only need API, web, and admin:

```bash
pnpm dev:core
```

Then open the admin app and log in with the dev admin credentials above. The
dashboard, competitions, seasons, rounds, teams, aliases, fixtures, users,
private leagues, operations, and audit log screens should show seeded data.

## Guardrails

- Do not add real production credentials to the seed file.
- Do not run this seed against production.
- Prefer adding realistic structured seed rows here until a verified live WordPress export/import pipeline exists.
