# Secrets & Environment Variables

All secrets are managed outside the codebase. No secret value is ever committed to
git. This document describes every required secret, its purpose, and where to obtain
it.

---

## Local Development

For local development, secrets are stored in:

- **Root `.env`** — used by non-Cloudflare tooling (scripts, test runners)
- **`apps/api/.dev.vars`** — used by `wrangler dev --local`; Cloudflare's equivalent
  of `.env` for Workers

Both files are gitignored. Templates are committed as `.env.example` and
`apps/api/.dev.vars.example`.

---

## Secret Reference

### `JWT_SECRET`

**Purpose:** Signs and verifies JWT access tokens. Compromise means any attacker can
forge tokens for any user.  
**Required in:** All environments (local through production)  
**Format:** Random hex string, minimum 32 characters  
**Generate:**

```bash
openssl rand -hex 32
```

**Set locally:** Add to `.env` and `apps/api/.dev.vars`  
**Set in production:** `wrangler secret put JWT_SECRET --env production`

---

### `STRIPE_SECRET_KEY`

**Purpose:** Authenticates all calls to the Stripe API (checkout sessions, refunds).  
**Required in:** All environments  
**Format:** `sk_test_...` for test; `sk_live_...` for production  
**Obtain:** Stripe Dashboard → Developers → API Keys  
**Note:** Use `sk_test_` in all non-production environments. Never use a live key in
development.  
**Set in production:** `wrangler secret put STRIPE_SECRET_KEY --env production`

---

### `STRIPE_WEBHOOK_SECRET`

**Purpose:** Verifies that webhook events posted to `POST /webhooks/stripe` genuinely
came from Stripe (signature verification).  
**Required in:** All environments  
**Format:** `whsec_...`  
**Obtain:** Stripe Dashboard → Developers → Webhooks → select endpoint → Signing
secret  
**Note:** A separate webhook endpoint (and therefore a separate secret) is required
for each environment. Do not share the production webhook secret with staging.  
**Set in production:** `wrangler secret put STRIPE_WEBHOOK_SECRET --env production`

---

### `SPORTS_API_KEY`

**Purpose:** Authenticates requests to the commercial sports data API (fixture and
result ingestion).  
**Required in:** `staging`, `production` (not strictly needed locally if using mock
data)  
**Vendor:** TBD — see Owner Decision OD-14 in `docs/architecture/SPORTSRUSH_CANONICAL_RULES.md`  
**Set in production:** `wrangler secret put SPORTS_API_KEY --env production`

---

### `CLOUDFLARE_API_TOKEN` (CI/CD only)

**Purpose:** Authorises GitHub Actions to deploy Workers, apply D1 migrations, and
manage KV/Queues.  
**Required in:** GitHub Actions only — never in application code  
**Obtain:** Cloudflare Dashboard → Profile → API Tokens → Create Token  
**Required permissions:**

- `Workers Scripts: Edit`
- `D1: Edit`
- `Workers KV Storage: Edit`
- `Cloudflare Pages: Edit` (for web/admin)

**Set in GitHub:** Repository Settings → Secrets → Actions → `CLOUDFLARE_API_TOKEN`

---

### `CLOUDFLARE_ACCOUNT_ID` (CI/CD only)

**Purpose:** Identifies the Cloudflare account for Wrangler in CI.  
**Obtain:** Cloudflare Dashboard → right sidebar → Account ID  
**Set in GitHub:** Repository Settings → Secrets → Actions → `CLOUDFLARE_ACCOUNT_ID`

---

## Rotating a Secret

1. Generate the new value
2. Update it in the target environment:
   ```bash
   wrangler secret put SECRET_NAME --env production
   ```
3. Redeploy the Worker (required for the new secret to take effect):
   ```bash
   wrangler deploy --env production
   ```
4. Verify the health check endpoint responds correctly
5. Revoke the old secret value in the issuing service (Stripe, Cloudflare, etc.)

---

## Checking What Secrets Are Set

```bash
# List all secrets set for an environment (names only — values are never shown)
wrangler secret list --env production
wrangler secret list --env staging
wrangler secret list --env dev
```

---

## Adding a New Secret

1. Add it to `.env.example` with a description and placeholder value (no real value)
2. Add it to `apps/api/.dev.vars.example` with a placeholder
3. Document it in this file
4. Set it in all environments via `wrangler secret put`
5. Add it to the `Env.Bindings` type in `apps/api/src/lib/env.ts`
