# SportsRush Mobile

Expo-ready React Native foundation for the SportsRush user experience.

This app is intentionally thin in PR-42:

- consumes `/v1/public/fixtures` and `/v1/public/leaderboards`
- uses the shared prediction API shape for score entry
- stores tokens through mobile-safe async storage
- includes sponsorship/commercial slot structure
- avoids direct database access and backend scoring logic

Run locally after installing workspace dependencies:

```sh
pnpm --filter @sr/mobile start
```

Follow-up work should add full login/register/reset screens, native navigation
tabs, device-specific polish, and production environment configuration.
