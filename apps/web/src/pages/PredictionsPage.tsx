import { useEffect, useMemo, useState } from "react";
import { EmptyState, ErrorState, LoadingState } from "../components/PageState";
import { FixtureGroup } from "../components/predictions/FixtureGroup";
import { PredictionFixtureCard } from "../components/predictions/PredictionFixtureCard";
import type { SaveState } from "../components/predictions/SaveStateIndicator";
import { useAuth } from "../contexts/AuthContext";
import type {
  PaginatedResult,
  Prediction,
  PublicCompetition,
  PublicFixture,
  PublicRound,
  PublicSeason,
} from "../features/types";
import {
  listCompetitions,
  listFixtures,
  listMyPredictions,
  listRounds,
  listSeasons,
  savePrediction,
} from "../lib/apiClient";
import { useAsyncData, type AsyncState, errorMessage } from "../lib/asyncData";
import { useLiveRefresh } from "../lib/liveRefresh";
import type { Screen } from "../App";

function emptyPaginated<T>(): PaginatedResult<T> {
  return {
    data: [],
    meta: { page: 1, limit: 0, total: 0, hasMore: false },
  };
}

function isLockedFixture(fixture: PublicFixture): boolean {
  return (
    fixture.status === "scheduled" &&
    new Date(fixture.kickoffTime).getTime() <= Date.now()
  );
}

function derivedStatus(
  fixture: PublicFixture,
): PublicFixture["status"] | "locked" {
  return isLockedFixture(fixture) ? "locked" : fixture.status;
}

function toPredictionMap(
  result: AsyncState<PaginatedResult<Prediction>>,
  optimistic: Readonly<Record<string, Prediction>>,
): Map<string, Prediction> {
  const entries =
    result.status === "ready"
      ? result.data.data.map(
          (prediction) => [prediction.fixtureId, prediction] as const,
        )
      : [];
  const map = new Map(entries);
  Object.entries(optimistic).forEach(([fixtureId, prediction]) => {
    map.set(fixtureId, prediction);
  });
  return map;
}

function formatGroupDate(kickoffTime: string): string {
  return new Date(kickoffTime).toLocaleDateString(undefined, {
    weekday: "short",
    month: "short",
    day: "numeric",
  });
}

function buildGroups(fixtures: readonly PublicFixture[], roundPinned: boolean) {
  const groups = new Map<
    string,
    { title: string; subtitle?: string; fixtures: PublicFixture[] }
  >();

  fixtures.forEach((fixture) => {
    const dateLabel = formatGroupDate(fixture.kickoffTime);
    const key = roundPinned
      ? dateLabel
      : `${fixture.round.id ?? fixture.round.name}-${dateLabel}`;
    const title = roundPinned ? dateLabel : fixture.round.name;
    const subtitle = roundPinned ? fixture.round.name : dateLabel;
    if (!groups.has(key)) {
      groups.set(key, { title, subtitle, fixtures: [] });
    }
    groups.get(key)!.fixtures.push(fixture);
  });

  return Array.from(groups.entries()).map(([key, value]) => ({
    key,
    ...value,
  }));
}

type PredictionsPageProps = {
  readonly go: (screen: Screen) => void;
};

export function PredictionsPage({ go }: PredictionsPageProps) {
  const auth = useAuth();
  const [selectedCompetitionId, setSelectedCompetitionId] = useState("");
  const [selectedSeasonId, setSelectedSeasonId] = useState("");
  const [selectedRoundId, setSelectedRoundId] = useState("");
  const [optimisticPredictions, setOptimisticPredictions] = useState<
    Record<string, Prediction>
  >({});
  const [saveStates, setSaveStates] = useState<Record<string, SaveState>>({});

  const [competitionsState] = useAsyncData(listCompetitions, []);
  const [seasonsState] = useAsyncData(
    () =>
      selectedCompetitionId
        ? listSeasons(selectedCompetitionId)
        : Promise.resolve(emptyPaginated<PublicSeason>()),
    [selectedCompetitionId],
  );
  const [roundsState] = useAsyncData(
    () =>
      selectedCompetitionId && selectedSeasonId
        ? listRounds(selectedCompetitionId, selectedSeasonId)
        : Promise.resolve(emptyPaginated<PublicRound>()),
    [selectedCompetitionId, selectedSeasonId],
  );
  const [fixturesState, reloadFixtures] = useAsyncData(
    () =>
      selectedCompetitionId
        ? listFixtures({
            competitionId: selectedCompetitionId,
            ...(selectedSeasonId ? { seasonId: selectedSeasonId } : {}),
            ...(selectedRoundId ? { roundId: selectedRoundId } : {}),
          })
        : Promise.resolve(emptyPaginated<PublicFixture>()),
    [selectedCompetitionId, selectedSeasonId, selectedRoundId],
  );
  const [predictionsState, reloadPredictions] = useAsyncData(
    () =>
      auth.accessToken
        ? listMyPredictions(auth.accessToken)
        : Promise.resolve(emptyPaginated<Prediction>()),
    [auth.accessToken],
  );

  useEffect(() => {
    if (competitionsState.status !== "ready") return;
    const fallbackId = competitionsState.data.data[0]?.id ?? "";
    setSelectedCompetitionId((current) =>
      current && competitionsState.data.data.some((item) => item.id === current)
        ? current
        : fallbackId,
    );
  }, [competitionsState]);

  useEffect(() => {
    if (seasonsState.status !== "ready") return;
    const fallbackId = seasonsState.data.data[0]?.id ?? "";
    setSelectedSeasonId((current) =>
      current && seasonsState.data.data.some((item) => item.id === current)
        ? current
        : fallbackId,
    );
    setSelectedRoundId("");
  }, [seasonsState]);

  useEffect(() => {
    if (roundsState.status !== "ready") return;
    setSelectedRoundId((current) =>
      current && roundsState.data.data.some((item) => item.id === current)
        ? current
        : "",
    );
  }, [roundsState]);

  useEffect(() => {
    if (predictionsState.status === "ready") {
      setOptimisticPredictions({});
    }
  }, [predictionsState]);

  const predictionsByFixture = useMemo(
    () => toPredictionMap(predictionsState, optimisticPredictions),
    [optimisticPredictions, predictionsState],
  );

  const fixtureGroups = useMemo(() => {
    if (fixturesState.status !== "ready") return [];
    return buildGroups(fixturesState.data.data, Boolean(selectedRoundId));
  }, [fixturesState, selectedRoundId]);

  const liveRefresh = useLiveRefresh(
    () => {
      reloadFixtures();
      if (auth.isAuthenticated) reloadPredictions();
    },
    Boolean(selectedCompetitionId),
    30000,
  );

  async function handleSave(
    fixture: PublicFixture,
    scores: { readonly homeScore: number; readonly awayScore: number },
    existing: Prediction | undefined,
  ): Promise<void> {
    if (!auth.accessToken) {
      go("login");
      return;
    }

    const previousPrediction = predictionsByFixture.get(fixture.id);
    const optimisticPrediction: Prediction = {
      id: existing?.id ?? `optimistic-${fixture.id}`,
      userId: existing?.userId ?? auth.user?.id ?? "me",
      fixtureId: fixture.id,
      homeScore: scores.homeScore,
      awayScore: scores.awayScore,
      createdAt: existing?.createdAt ?? new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    };

    setOptimisticPredictions((current) => ({
      ...current,
      [fixture.id]: optimisticPrediction,
    }));
    setSaveStates((current) => ({
      ...current,
      [fixture.id]: { status: "saving", message: "Saving prediction..." },
    }));

    try {
      const saved = await savePrediction(
        auth.accessToken,
        fixture.id,
        scores.homeScore,
        scores.awayScore,
        existing ? "update" : "create",
      );
      setOptimisticPredictions((current) => ({
        ...current,
        [fixture.id]: saved,
      }));
      setSaveStates((current) => ({
        ...current,
        [fixture.id]: {
          status: "saved",
          message: existing ? "Prediction updated." : "Prediction saved.",
        },
      }));
      reloadPredictions();
    } catch (saveError: unknown) {
      setOptimisticPredictions((current) => {
        const next = { ...current };
        if (previousPrediction) {
          next[fixture.id] = previousPrediction;
        } else {
          delete next[fixture.id];
        }
        return next;
      });
      setSaveStates((current) => ({
        ...current,
        [fixture.id]: {
          status: "error",
          message: errorMessage(saveError),
        },
      }));
      reloadFixtures();
    }
  }

  return (
    <div className="page prediction-page">
      <div className="page-heading">
        <p className="eyebrow">SportsRush</p>
        <h1>Predictions</h1>
        <p>
          Enter, update, and track predictions round by round using the live
          fixture schedule.
        </p>
      </div>

      {!auth.isAuthenticated ? (
        <section className="panel prediction-login-banner">
          <div>
            <strong>Sign in to save predictions.</strong>
            <p>
              You can browse fixtures now. Login is required before kickoff to
              save or update picks.
            </p>
          </div>
          <div className="hero-actions">
            <button
              className="button"
              type="button"
              onClick={() => go("login")}
            >
              Login
            </button>
            <button
              className="button secondary"
              type="button"
              onClick={() => go("register")}
            >
              Register
            </button>
          </div>
        </section>
      ) : null}

      <section className="prediction-toolbar panel">
        <div className="prediction-selectors">
          <label>
            Competition
            <select
              aria-label="Competition"
              value={selectedCompetitionId}
              onChange={(event) => setSelectedCompetitionId(event.target.value)}
            >
              {(competitionsState.status === "ready"
                ? competitionsState.data.data
                : []
              ).map((competition: PublicCompetition) => (
                <option key={competition.id} value={competition.id}>
                  {competition.name}
                </option>
              ))}
            </select>
          </label>
          <label>
            Season
            <select
              aria-label="Season"
              disabled={seasonsState.status !== "ready"}
              value={selectedSeasonId}
              onChange={(event) => setSelectedSeasonId(event.target.value)}
            >
              {(seasonsState.status === "ready"
                ? seasonsState.data.data
                : []
              ).map((season) => (
                <option key={season.id} value={season.id}>
                  {season.name}
                </option>
              ))}
            </select>
          </label>
          <label>
            Round
            <select
              aria-label="Round"
              disabled={roundsState.status !== "ready"}
              value={selectedRoundId}
              onChange={(event) => setSelectedRoundId(event.target.value)}
            >
              <option value="">All rounds</option>
              {(roundsState.status === "ready"
                ? roundsState.data.data
                : []
              ).map((round) => (
                <option key={round.id} value={round.id}>
                  {round.name}
                </option>
              ))}
            </select>
          </label>
        </div>
        <div className="prediction-toolbar-actions">
          <button
            className="button secondary compact"
            type="button"
            onClick={liveRefresh.refreshNow}
          >
            Refresh fixtures
          </button>
          {liveRefresh.lastRefreshAt ? (
            <span className="muted">
              Refreshed{" "}
              {new Date(liveRefresh.lastRefreshAt).toLocaleTimeString()}
            </span>
          ) : (
            <span className="muted">Live polling every 30 seconds</span>
          )}
        </div>
      </section>

      {competitionsState.status === "loading" ||
      seasonsState.status === "loading" ? (
        <LoadingState label="Loading prediction filters" />
      ) : null}
      {competitionsState.status === "error" ? (
        <ErrorState message={competitionsState.message} />
      ) : null}
      {seasonsState.status === "error" ? (
        <ErrorState message={seasonsState.message} />
      ) : null}
      {roundsState.status === "error" ? (
        <ErrorState message={roundsState.message} />
      ) : null}

      {fixturesState.status === "loading" ? (
        <div
          className="prediction-skeleton-list"
          aria-label="Loading predictions"
        >
          <div className="prediction-skeleton-card" />
          <div className="prediction-skeleton-card" />
          <div className="prediction-skeleton-card" />
        </div>
      ) : null}
      {fixturesState.status === "error" ? (
        <ErrorState message={fixturesState.message} onRetry={reloadFixtures} />
      ) : null}
      {fixturesState.status === "ready" &&
      fixturesState.data.data.length === 0 ? (
        <EmptyState message="No fixtures are available for those selections." />
      ) : null}

      {fixturesState.status === "ready"
        ? fixtureGroups.map((group) => (
            <FixtureGroup
              key={group.key}
              title={group.title}
              {...(group.subtitle ? { subtitle: group.subtitle } : {})}
            >
              {group.fixtures.map((fixture) => (
                <PredictionFixtureCard
                  key={fixture.id}
                  fixture={fixture}
                  isAuthenticated={auth.isAuthenticated}
                  prediction={predictionsByFixture.get(fixture.id)}
                  saveState={saveStates[fixture.id] ?? { status: "idle" }}
                  status={derivedStatus(fixture)}
                  onLogin={() => go("login")}
                  onSave={handleSave}
                />
              ))}
            </FixtureGroup>
          ))
        : null}
    </div>
  );
}
