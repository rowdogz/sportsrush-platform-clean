import { useMemo, useState } from "react";
import type { FormEvent, ReactNode } from "react";
import {
  AdSlot,
  PremiumHook,
  SponsorshipSlot,
} from "./components/CommercialSlots";
import { AppHeader } from "./components/AppHeader";
import { FixtureCard } from "./components/FixtureCard";
import { EmptyState, ErrorState, LoadingState } from "./components/PageState";
import { AuthProvider, useAuth } from "./contexts/AuthContext";
import { ThemeProvider } from "./contexts/ThemeContext";
import type {
  LeaderboardEntry,
  PaginatedResult,
  Prediction,
  PublicCompetition,
  PublicFixture,
} from "./features/types";
import {
  listCompetitions,
  listFixtures,
  listLeaderboards,
  listMyPredictions,
  confirmPasswordReset,
  requestPasswordReset,
  savePrediction,
  type FixtureFilters,
} from "./lib/apiClient";
import { type AsyncState, errorMessage, useAsyncData } from "./lib/asyncData";
import { trackEvent } from "./lib/commercial";
import { useLiveRefresh } from "./lib/liveRefresh";
import { PredictionsPage } from "./pages/PredictionsPage";
import { RankingsPage } from "./pages/RankingsPage";
import { ResultsPage } from "./pages/ResultsPage";

export type Screen =
  | "home"
  | "competitions"
  | "fixtures"
  | "results"
  | "rankings"
  | "predictions"
  | "login"
  | "register"
  | "reset";

function AppShell() {
  const [screen, setScreen] = useState<Screen>("home");
  const auth = useAuth();
  const navItems: readonly {
    readonly screen: Screen;
    readonly label: string;
  }[] = [
    { screen: "home", label: "Home" },
    { screen: "competitions", label: "Competitions" },
    { screen: "fixtures", label: "Fixtures" },
    { screen: "results", label: "Results" },
    { screen: "rankings", label: "Rankings" },
    { screen: "predictions", label: "Predictions" },
  ];

  function go(nextScreen: Screen): void {
    setScreen(nextScreen);
    trackEvent({
      name: "navigation_clicked",
      properties: { screen: nextScreen },
    });
  }

  return (
    <div className="app-shell">
      <AppHeader
        currentScreen={screen}
        isAuthenticated={auth.isAuthenticated}
        navItems={navItems}
        onLogin={() => go("login")}
        onLogout={auth.signOut}
        onNavigate={go}
        onRegister={() => go("register")}
        user={auth.user}
      />
      <main>
        {screen === "home" ? <HomePage setScreen={go} /> : null}
        {screen === "competitions" ? <CompetitionsPage /> : null}
        {screen === "fixtures" ? <FixturesPage /> : null}
        {screen === "results" ? <ResultsPage /> : null}
        {screen === "rankings" ? <RankingsPage /> : null}
        {screen === "predictions" ? <PredictionsPage go={go} /> : null}
        {screen === "login" ? <AuthPage mode="login" go={go} /> : null}
        {screen === "register" ? <AuthPage mode="register" go={go} /> : null}
        {screen === "reset" ? <PasswordResetPage go={go} /> : null}
      </main>
      <footer className="site-footer">
        <span>SportsRush platform preview</span>
        <span>Privacy-safe analytics hooks enabled</span>
      </footer>
    </div>
  );
}

function HomePage({
  setScreen,
}: {
  readonly setScreen: (screen: Screen) => void;
}) {
  const [fixturesState] = useAsyncData(
    () => listFixtures({ status: "scheduled" }),
    [],
  );
  const upcoming =
    fixturesState.status === "ready" ? fixturesState.data.data.slice(0, 3) : [];
  return (
    <div className="page-grid">
      <section className="hero">
        <p className="eyebrow">Fixtures, predictions and rankings</p>
        <h1>Predict every score. Track every table.</h1>
        <p>
          SportsRush brings public fixtures, private league hooks, predictions
          and leaderboards into one responsive experience.
        </p>
        <div className="hero-actions">
          <button
            className="button"
            type="button"
            onClick={() => setScreen("fixtures")}
          >
            Browse fixtures
          </button>
          <button
            className="button secondary"
            type="button"
            onClick={() => setScreen("rankings")}
          >
            View rankings
          </button>
        </div>
      </section>
      <SponsorshipSlot label="Homepage hero sponsor" />
      <section className="panel">
        <h2>Upcoming fixtures</h2>
        {fixturesState.status === "loading" ? (
          <LoadingState label="Loading fixtures" />
        ) : null}
        {fixturesState.status === "error" ? (
          <ErrorState message={fixturesState.message} />
        ) : null}
        {fixturesState.status === "ready" && upcoming.length === 0 ? (
          <EmptyState message="No upcoming fixtures yet." />
        ) : null}
        <div className="fixture-list">
          {upcoming.map((fixture) => (
            <FixtureCard key={fixture.id} fixture={fixture} />
          ))}
        </div>
      </section>
      <AdSlot placement="home-sidebar" />
    </div>
  );
}

function CompetitionsPage() {
  const [state, reload] = useAsyncData(listCompetitions, []);
  return (
    <Page title="Competitions" subtitle="Public SportsRush competitions.">
      <StatefulList
        state={state}
        retry={reload}
        empty="No competitions are available yet."
      >
        {(result: PaginatedResult<PublicCompetition>) => (
          <div className="card-grid">
            {result.data.map((competition) => (
              <article className="entity-card" key={competition.id}>
                <p className="eyebrow">{competition.sportId}</p>
                <h3>{competition.name}</h3>
                <p>{competition.shortName ?? competition.slug}</p>
                {competition.countryCode ? (
                  <span>{competition.countryCode}</span>
                ) : null}
              </article>
            ))}
          </div>
        )}
      </StatefulList>
    </Page>
  );
}

function FixturesPage() {
  const [status, setStatus] = useState("");
  const filters = useMemo<FixtureFilters>(
    () => (status ? { status } : {}),
    [status],
  );
  const [state, reload] = useAsyncData(() => listFixtures(filters), [status]);
  const liveRefresh = useLiveRefresh(reload, true, 30000);
  return (
    <Page title="Fixtures" subtitle="Browse upcoming and live fixtures.">
      <div className="toolbar">
        <label>
          Status
          <select
            value={status}
            onChange={(event) => setStatus(event.target.value)}
          >
            <option value="">All</option>
            <option value="scheduled">Scheduled</option>
            <option value="live">Live</option>
            <option value="postponed">Postponed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </label>
        <button
          className="button secondary"
          type="button"
          onClick={liveRefresh.refreshNow}
        >
          Refresh
        </button>
        {liveRefresh.lastRefreshAt ? (
          <span className="muted">
            Last refreshed{" "}
            {new Date(liveRefresh.lastRefreshAt).toLocaleTimeString()}
          </span>
        ) : null}
      </div>
      <FixtureList
        state={state}
        retry={reload}
        empty="No fixtures match those filters."
      />
    </Page>
  );
}

function AuthPage({
  mode,
  go,
}: {
  readonly mode: "login" | "register";
  readonly go: (screen: Screen) => void;
}) {
  const auth = useAuth();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [displayName, setDisplayName] = useState("");
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  async function submit(event: FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault();
    setError(null);
    setMessage(null);
    if (!email.trim() || !password) {
      setError("Enter an email address and password.");
      return;
    }
    if (mode === "register" && displayName.trim().length < 2) {
      setError("Enter a display name of at least 2 characters.");
      return;
    }
    try {
      if (mode === "login") {
        await auth.signIn(email.trim(), password);
      } else {
        await auth.signUp(
          email.trim(),
          password,
          displayName.trim() || undefined,
        );
      }
      setMessage("You are signed in.");
      go("fixtures");
    } catch (submitError: unknown) {
      setError(errorMessage(submitError));
    }
  }

  return (
    <Page
      title={mode === "login" ? "Login" : "Register"}
      subtitle="Secure SportsRush account access."
    >
      <form className="form-card" onSubmit={(event) => void submit(event)}>
        {mode === "register" ? (
          <label>
            Display name
            <input
              value={displayName}
              onChange={(event) => setDisplayName(event.target.value)}
            />
          </label>
        ) : null}
        <label>
          Email
          <input
            autoComplete="email"
            type="email"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
          />
        </label>
        <label>
          Password
          <input
            autoComplete={
              mode === "login" ? "current-password" : "new-password"
            }
            type="password"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
          />
        </label>
        {error ? (
          <p className="form-error" role="alert">
            {error}
          </p>
        ) : null}
        {message ? <p className="form-success">{message}</p> : null}
        <button className="button" type="submit" disabled={auth.isLoading}>
          {auth.isLoading
            ? "Submitting..."
            : mode === "login"
              ? "Login"
              : "Register"}
        </button>
        {mode === "login" ? (
          <button
            className="link-button"
            type="button"
            onClick={() => go("reset")}
          >
            Forgotten password?
          </button>
        ) : null}
      </form>
    </Page>
  );
}

function PasswordResetPage({ go }: { readonly go: (screen: Screen) => void }) {
  const auth = useAuth();
  const [email, setEmail] = useState("");
  const [token, setToken] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  async function submit(event: FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault();
    setError(null);
    setMessage(null);
    if (!email.trim()) {
      setError("Enter your email address.");
      return;
    }
    try {
      setMessage(await requestPasswordReset(email.trim()));
    } catch (submitError: unknown) {
      setError(errorMessage(submitError));
    }
  }

  async function confirm(event: FormEvent<HTMLFormElement>): Promise<void> {
    event.preventDefault();
    setError(null);
    setMessage(null);
    if (!token.trim() || !newPassword) {
      setError("Enter a reset token and new password.");
      return;
    }
    try {
      const response = await confirmPasswordReset(token.trim(), newPassword);
      auth.applyAuthResponse(response);
      setMessage("Password reset complete. You can continue signed in.");
      go("fixtures");
    } catch (submitError: unknown) {
      setError(errorMessage(submitError));
    }
  }

  return (
    <Page
      title="Reset password"
      subtitle="Request a secure password reset link."
    >
      <form className="form-card" onSubmit={(event) => void submit(event)}>
        <label>
          Email
          <input
            type="email"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
          />
        </label>
        {error ? (
          <p className="form-error" role="alert">
            {error}
          </p>
        ) : null}
        {message ? <p className="form-success">{message}</p> : null}
        <button className="button" type="submit">
          Send reset link
        </button>
        <button
          className="link-button"
          type="button"
          onClick={() => go("login")}
        >
          Back to login
        </button>
      </form>
      <form
        className="form-card stacked-form"
        onSubmit={(event) => void confirm(event)}
      >
        <h2>Confirm reset</h2>
        <label>
          Reset token
          <input
            value={token}
            onChange={(event) => setToken(event.target.value)}
          />
        </label>
        <label>
          New password
          <input
            type="password"
            value={newPassword}
            onChange={(event) => setNewPassword(event.target.value)}
          />
        </label>
        <button className="button" type="submit">
          Confirm new password
        </button>
      </form>
    </Page>
  );
}

function PredictionForm({
  fixture,
  existing,
  onSaved,
}: {
  readonly fixture: PublicFixture;
  readonly existing: Prediction | undefined;
  readonly onSaved: () => void;
}) {
  const auth = useAuth();
  const [homeScore, setHomeScore] = useState(
    existing?.homeScore.toString() ?? "",
  );
  const [awayScore, setAwayScore] = useState(
    existing?.awayScore.toString() ?? "",
  );
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const locked = new Date(fixture.kickoffTime).getTime() <= Date.now();

  async function submit(): Promise<void> {
    if (!auth.accessToken) {
      setError("Login to save predictions.");
      return;
    }
    const home = Number(homeScore);
    const away = Number(awayScore);
    if (
      !Number.isInteger(home) ||
      !Number.isInteger(away) ||
      home < 0 ||
      away < 0
    ) {
      setError("Enter valid non-negative scores.");
      return;
    }
    setError(null);
    setMessage("Saving prediction...");
    try {
      await savePrediction(
        auth.accessToken,
        fixture.id,
        home,
        away,
        existing ? "update" : "create",
      );
      setMessage(existing ? "Prediction updated." : "Prediction saved.");
      onSaved();
    } catch (submitError: unknown) {
      setMessage(null);
      setError(errorMessage(submitError));
    }
  }

  if (locked)
    return <span className="prediction-status">Locked at kickoff</span>;
  if (!auth.isAuthenticated)
    return <span className="prediction-status">Login to predict</span>;
  return (
    <div className="prediction-form">
      <label>
        Home
        <input
          aria-label={`Home prediction for ${fixture.homeTeam.name}`}
          inputMode="numeric"
          min="0"
          type="number"
          value={homeScore}
          onChange={(event) => setHomeScore(event.target.value)}
        />
      </label>
      <label>
        Away
        <input
          aria-label={`Away prediction for ${fixture.awayTeam.name}`}
          inputMode="numeric"
          min="0"
          type="number"
          value={awayScore}
          onChange={(event) => setAwayScore(event.target.value)}
        />
      </label>
      <button
        className="button compact"
        type="button"
        onClick={() => void submit()}
      >
        {existing ? "Update" : "Save"}
      </button>
      {message ? <span className="form-success">{message}</span> : null}
      {error ? (
        <span className="form-error" role="alert">
          {error}
        </span>
      ) : null}
    </div>
  );
}

function FixtureList({
  state,
  retry,
  empty,
}: {
  readonly state: AsyncState<PaginatedResult<PublicFixture>>;
  readonly retry: () => void;
  readonly empty: string;
}) {
  const auth = useAuth();
  const [predictionsState, reloadPredictions] = useAsyncData(
    async () =>
      auth.accessToken
        ? listMyPredictions(auth.accessToken)
        : ({
            data: [],
            meta: { page: 1, limit: 0, total: 0, hasMore: false },
          } satisfies PaginatedResult<Prediction>),
    [auth.accessToken],
  );
  const predictionsByFixture = useMemo(() => {
    if (predictionsState.status !== "ready")
      return new Map<string, Prediction>();
    return new Map(
      predictionsState.data.data.map((prediction) => [
        prediction.fixtureId,
        prediction,
      ]),
    );
  }, [predictionsState]);

  return (
    <StatefulList state={state} retry={retry} empty={empty}>
      {(result: PaginatedResult<PublicFixture>) => (
        <div className="fixture-list">
          {result.data.map((fixture) => {
            const existingPrediction = predictionsByFixture.get(fixture.id);
            const isLocked =
              fixture.status === "scheduled" &&
              new Date(fixture.kickoffTime).getTime() <= Date.now();

            return (
              <FixtureCard
                key={fixture.id}
                fixture={fixture}
                {...(isLocked ? { statusLabel: "locked" } : {})}
                action={
                  <PredictionForm
                    fixture={fixture}
                    existing={existingPrediction}
                    onSaved={reloadPredictions}
                  />
                }
              />
            );
          })}
        </div>
      )}
    </StatefulList>
  );
}

function StatefulList<T>({
  state,
  retry,
  empty,
  children,
}: {
  readonly state: AsyncState<PaginatedResult<T>>;
  readonly retry: () => void;
  readonly empty: string;
  readonly children: (result: PaginatedResult<T>) => ReactNode;
}) {
  if (state.status === "loading") return <LoadingState />;
  if (state.status === "error") {
    return <ErrorState message={state.message} onRetry={retry} />;
  }
  if (state.data.data.length === 0) return <EmptyState message={empty} />;
  return children(state.data);
}

function Page({
  title,
  subtitle,
  children,
}: {
  readonly title: string;
  readonly subtitle: string;
  readonly children: ReactNode;
}) {
  return (
    <div className="page">
      <div className="page-heading">
        <p className="eyebrow">SportsRush</p>
        <h1>{title}</h1>
        <p>{subtitle}</p>
      </div>
      {children}
      <PremiumHook />
    </div>
  );
}

export function App() {
  return (
    <ThemeProvider>
      <AuthProvider>
        <AppShell />
      </AuthProvider>
    </ThemeProvider>
  );
}
