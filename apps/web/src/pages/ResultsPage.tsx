import { useEffect, useMemo, useState } from "react";
import { EmptyState, ErrorState, LoadingState } from "../components/PageState";
import { FixtureResultGroup } from "../components/results/FixtureResultGroup";
import { ResultFixtureCard } from "../components/results/ResultFixtureCard";
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
} from "../lib/apiClient";
import { useAsyncData } from "../lib/asyncData";
import { useLiveRefresh } from "../lib/liveRefresh";

function emptyPaginated<T>(): PaginatedResult<T> {
  return {
    data: [],
    meta: { page: 1, limit: 0, total: 0, hasMore: false },
  };
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

export function ResultsPage() {
  const auth = useAuth();
  const [selectedCompetitionId, setSelectedCompetitionId] = useState("");
  const [selectedSeasonId, setSelectedSeasonId] = useState("");
  const [selectedRoundId, setSelectedRoundId] = useState("");

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
  const [resultsState, reloadResults] = useAsyncData(
    () =>
      selectedCompetitionId
        ? listFixtures({
            competitionId: selectedCompetitionId,
            ...(selectedSeasonId ? { seasonId: selectedSeasonId } : {}),
            ...(selectedRoundId ? { roundId: selectedRoundId } : {}),
            status: "completed",
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

  const resultGroups = useMemo(() => {
    if (resultsState.status !== "ready") return [];
    return buildGroups(resultsState.data.data, Boolean(selectedRoundId));
  }, [resultsState, selectedRoundId]);

  const liveRefresh = useLiveRefresh(
    () => {
      reloadResults();
      if (auth.isAuthenticated) reloadPredictions();
    },
    Boolean(selectedCompetitionId),
    60000,
  );

  return (
    <div className="page results-page">
      <div className="page-heading">
        <p className="eyebrow">SportsRush</p>
        <h1>Results</h1>
        <p>
          Review completed fixtures, compare saved picks, and track how your
          predictions lined up with the final score.
        </p>
      </div>

      <section className="results-toolbar panel">
        <div className="results-selectors">
          <label>
            Competition
            <select
              aria-label="Results competition"
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
              aria-label="Results season"
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
              aria-label="Results round"
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
        <div className="results-toolbar-actions">
          <button
            className="button secondary compact"
            type="button"
            onClick={liveRefresh.refreshNow}
          >
            Refresh results
          </button>
          {liveRefresh.lastRefreshAt ? (
            <span className="muted">
              Refreshed{" "}
              {new Date(liveRefresh.lastRefreshAt).toLocaleTimeString()}
            </span>
          ) : (
            <span className="muted">Refresh checks for completed fixtures</span>
          )}
        </div>
      </section>

      {auth.isAuthenticated ? (
        <section className="panel results-disclaimer">
          <strong>
            Scoring points are not yet exposed by the current API.
          </strong>
          <p>
            This page shows final scores and saved prediction comparisons using
            existing contracts. Numeric points and breakdowns remain deferred.
          </p>
        </section>
      ) : null}

      {competitionsState.status === "loading" ||
      seasonsState.status === "loading" ? (
        <LoadingState label="Loading result filters" />
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
      {resultsState.status === "loading" ? (
        <div className="result-skeleton-list" aria-label="Loading results">
          <div className="result-skeleton-card" />
          <div className="result-skeleton-card" />
          <div className="result-skeleton-card" />
        </div>
      ) : null}
      {resultsState.status === "error" ? (
        <ErrorState message={resultsState.message} onRetry={reloadResults} />
      ) : null}
      {resultsState.status === "ready" &&
      resultsState.data.data.length === 0 ? (
        <EmptyState message="No completed fixtures are available for those selections." />
      ) : null}

      {resultsState.status === "ready"
        ? resultGroups.map((group) => (
            <FixtureResultGroup
              key={group.key}
              title={group.title}
              {...(group.subtitle ? { subtitle: group.subtitle } : {})}
            >
              {group.fixtures.map((fixture) => (
                <ResultFixtureCard
                  key={fixture.id}
                  fixture={fixture}
                  prediction={predictionsByFixture.get(fixture.id)}
                  showPointsPlaceholder={auth.isAuthenticated}
                />
              ))}
            </FixtureResultGroup>
          ))
        : null}
    </div>
  );
}
