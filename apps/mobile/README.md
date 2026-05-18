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
pnpm --filter @sr/mobile dev
```

For iOS Simulator on the same Mac, the default API base URL of
`http://localhost:8788` works with the local Workers API.

For Expo Go on a physical device, create `apps/mobile/.env.local` with your Mac's
LAN IP, for example:

```sh
EXPO_PUBLIC_API_BASE_URL=http://192.168.1.249:8788
```

Then restart Expo.
