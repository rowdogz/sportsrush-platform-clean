import { useMemo } from "react";
import { EmptyState, ErrorState, LoadingState } from "../components/PageState";
import { ActivityFeedCard } from "../components/profile/ActivityFeedCard";
import { ProfileSummaryCard } from "../components/profile/ProfileSummaryCard";
import { useAuth } from "../contexts/AuthContext";
import { useTheme } from "../contexts/ThemeContext";
import type {
  PaginatedResult,
  Prediction,
  PublicFixture,
} from "../features/types";
import { getMe, listFixtures, listMyPredictions } from "../lib/apiClient";
import { useAsyncData } from "../lib/asyncData";

export function ProfilePage({ onLogin }: { readonly onLogin: () => void }) {
  const auth = useAuth();
  const theme = useTheme();

  const [meState] = useAsyncData(
    async () => (auth.accessToken ? getMe(auth.accessToken) : null),
    [auth.accessToken],
  );
  const [predictionsState] = useAsyncData(
    async () =>
      auth.accessToken
        ? listMyPredictions(auth.accessToken)
        : ({
            data: [],
            meta: { page: 1, limit: 25, total: 0, hasMore: false },
          } satisfies PaginatedResult<Prediction>),
    [auth.accessToken],
  );
  const [fixturesState] = useAsyncData(() => listFixtures({}), []);

  const recentPredictions =
    predictionsState.status === "ready"
      ? [...predictionsState.data.data]
          .sort((left, right) => right.updatedAt.localeCompare(left.updatedAt))
          .slice(0, 4)
      : [];

  const fixturesById = useMemo(() => {
    if (fixturesState.status !== "ready")
      return new Map<string, PublicFixture>();
    return new Map(
      fixturesState.data.data.map((fixture) => [fixture.id, fixture]),
    );
  }, [fixturesState]);

  return (
    <div className="page profile-page">
      <div className="page-heading">
        <p className="eyebrow">SportsRush</p>
        <h1>Profile</h1>
        <p>
          Review account details, session state, theme preferences, and the
          first public social/activity foundations.
        </p>
      </div>

      {!auth.isAuthenticated ? (
        <section className="panel">
          <EmptyState message="Login to view your profile, recent prediction activity, and league-related account preferences." />
          <div className="hero-actions">
            <button className="button" type="button" onClick={onLogin}>
              Login
            </button>
          </div>
        </section>
      ) : null}

      {auth.isAuthenticated && meState.status === "loading" ? (
        <LoadingState label="Loading profile" />
      ) : null}
      {auth.isAuthenticated && meState.status === "error" ? (
        <ErrorState message={meState.message} />
      ) : null}

      {auth.isAuthenticated && meState.status === "ready" && meState.data ? (
        <div className="profile-layout">
          <ProfileSummaryCard
            me={meState.data}
            onLogout={auth.signOut}
            onRefreshSession={auth.refreshSession}
          />

          <section className="profile-preferences-card">
            <div className="profile-section-header">
              <h3>Preferences</h3>
              <span className="muted">Mobile-safe account foundation</span>
            </div>
            <div className="profile-preference-row">
              <div>
                <strong>Theme mode</strong>
                <p className="muted">
                  Uses the shared PR-43 theme persistence layer.
                </p>
              </div>
              <button
                className="button secondary compact"
                type="button"
                onClick={() =>
                  theme.setTheme(theme.theme === "dark" ? "light" : "dark")
                }
              >
                Theme: {theme.theme}
              </button>
            </div>
            <div className="profile-preference-row">
              <div>
                <strong>Favourite team</strong>
                <p className="muted">
                  Placeholder only. Backend profile preference support is not
                  available yet.
                </p>
              </div>
              <span className="status-chip">Deferred</span>
            </div>
            <div className="profile-preference-row">
              <div>
                <strong>Notification preferences</strong>
                <p className="muted">
                  Placeholder for future email/push controls once those services
                  exist.
                </p>
              </div>
              <span className="status-chip">Placeholder</span>
            </div>
          </section>

          <ActivityFeedCard
            fixturesById={fixturesById}
            predictions={recentPredictions}
          />

          <section className="profile-preferences-card">
            <div className="profile-section-header">
              <h3>Share cards</h3>
              <span className="muted">Social foundation</span>
            </div>
            <p>
              Prediction share cards and league brag graphics are intentionally
              deferred. This card reserves the UX space without inventing a
              social backend.
            </p>
          </section>
        </div>
      ) : null}
    </div>
  );
}
