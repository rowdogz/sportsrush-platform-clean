# Secrets and local environment

Local development only needs one secret today:

- `JWT_SECRET` in `apps/api/.dev.vars`

Generate it with:

```bash
openssl rand -base64 32
```

Do not commit `.dev.vars`, `.env.local`, or any real credentials.

For local browser development, API base URLs fall back to the default local
ports in Vite dev mode. Optional examples live in:

- `apps/web/.env.example`
- `apps/admin/.env.example`
- `apps/mobile/.env.example`
