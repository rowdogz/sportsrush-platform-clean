# Cloudflare Setup — SportsRush API

Steps for provisioning real Cloudflare resources for the SportsRush API.
**None of these steps are performed automatically** — all require a human with
Cloudflare account access and the `wrangler` CLI authenticated.

---

## Prerequisites

```bash
# Install wrangler globally (or use npx)
pnpm add -g wrangler   # or: npm install -g wrangler

# Authenticate with Cloudflare
wrangler login
```

---

## D1 Databases

One D1 database per environment. Create each once; the ID is permanent.

### Step 1 — Create the databases

```bash
# Development
wrangler d1 create sportsrush-dev

# Staging
wrangler d1 create sportsrush-staging

# Production
wrangler d1 create sportsrush-production
```

Each command outputs a block like:

```
✅ Successfully created DB 'sportsrush-dev' in region EEUR
Created your new D1 database.

[[d1_databases]]
binding = "DB"
database_name = "sportsrush-dev"
database_id = "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
```

### Step 2 — Update wrangler.toml

Copy each `database_id` UUID into `apps/api/wrangler.toml`:

| Section                 | Replace placeholder                              |
| ----------------------- | ------------------------------------------------ |
| `[[d1_databases]]`      | `00000000-0000-4000-8000-000000000001` (dev)     |
| `[[env.staging...]]`    | `00000000-0000-4000-8000-000000000002` (staging) |
| `[[env.production...]]` | `00000000-0000-4000-8000-000000000003` (prod)    |

Commit the updated `wrangler.toml`. The `database_id` is not a secret —
it is safe to commit.

### Step 3 — Apply migrations

After updating `wrangler.toml`, apply all pending migrations to each environment.

**Local only (no Cloudflare account needed):**

```bash
# From the repo root:
pnpm --filter @sr/api wrangler d1 migrations apply sportsrush-dev --local

# Or from apps/api/:
wrangler d1 migrations apply sportsrush-dev --local
```

Migrations are applied to a local SQLite file at:
`.wrangler/state/v3/d1/<database_id>/db.sqlite`

**Remote — staging:**

```bash
wrangler d1 migrations apply sportsrush-staging --remote --env staging
```

**Remote — production:**

```bash
wrangler d1 migrations apply sportsrush-production --remote --env production
```

### Check migration status

```bash
# Local
wrangler d1 migrations list sportsrush-dev --local

# Remote staging
wrangler d1 migrations list sportsrush-staging --remote --env staging

# Remote production
wrangler d1 migrations list sportsrush-production --remote --env production
```

---

## Secrets

Secrets are set per environment and are never stored in `wrangler.toml`.

```bash
# Development (used by `wrangler dev` without --local)
wrangler secret put JWT_SECRET
wrangler secret put WEB_ORIGIN       # optional — defaults to '*' if absent

# Staging
wrangler secret put JWT_SECRET  --env staging
wrangler secret put WEB_ORIGIN  --env staging

# Production
wrangler secret put JWT_SECRET  --env production
wrangler secret put WEB_ORIGIN  --env production
```

Generate a strong `JWT_SECRET`:

```bash
openssl rand -base64 32
```

For local development, use `.dev.vars` instead (see `.dev.vars.example`).

---

## Local development (no Cloudflare account)

You can run the API locally without any Cloudflare account using local mode:

```bash
# 1. Copy and fill in the local secrets file
cp apps/api/.dev.vars.example apps/api/.dev.vars
# Edit .dev.vars and replace the JWT_SECRET placeholder

# 2. Apply migrations to the local SQLite database
cd apps/api
wrangler d1 migrations apply sportsrush-dev --local

# 3. Start the local dev server
wrangler dev --local
# Or: pnpm --filter @sr/api dev
```

The local D1 database file is created at:
`.wrangler/state/v3/d1/<database_id>/db.sqlite`

This file is gitignored (`.wrangler/` is in the root `.gitignore`).

---

## Deploy

```bash
# Staging
wrangler deploy --env staging

# Production
wrangler deploy --env production
```

Always apply migrations before deploying a new API version that requires them.

---

## Adding a new migration

1. Create a new file in `apps/api/migrations/` following the naming convention:

   ```
   NNNN_description.sql
   ```

   where `NNNN` is the next sequential zero-padded number (e.g. `0002_create_users.sql`).

2. Write additive SQL only. Never drop columns or tables in an applied migration.
   Use a new migration to undo changes if needed.

3. Test locally before applying remotely:

   ```bash
   wrangler d1 migrations apply sportsrush-dev --local
   wrangler d1 migrations list sportsrush-dev --local
   ```

4. Apply to staging, then production after sign-off.

---

## Risks and notes

| Risk                                            | Mitigation                                                                                                     |
| ----------------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| Applying migrations to production irreversibly  | Always test locally and on staging first. Keep migrations additive.                                            |
| `database_id` placeholder left in wrangler.toml | Local `--local` mode works regardless of ID. Remote operations will fail until real IDs are set.               |
| Rotating `JWT_SECRET` in production             | All existing access tokens become invalid immediately. Coordinate with a planned maintenance window.           |
| `.dev.vars` accidentally committed              | `*.dev.vars` is gitignored at the root `.gitignore` level. The pre-commit hook (future PR) will catch secrets. |
