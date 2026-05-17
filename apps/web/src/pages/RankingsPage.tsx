import { useEffect, useMemo, useState } from "react";
import { EmptyState, ErrorState, LoadingState } from "../components/PageState";
import { LeaderboardCard } from "../components/rankings/LeaderboardCard";
import { LeaderboardFilters } from "../components/rankings/LeaderboardFilters";
import { LeaderboardTable } from "../components/rankings/LeaderboardTable";
import {
  UserRankHighlight,
  isCurrentUserEntry,
} from "../components/rankings/UserRankHighlight";
import { useAuth } from "../contexts/AuthContext";
import type { LeaderboardEntry, PublicCompetition } from "../features/types";
import { listCompetitions, listLeaderboards } from "../lib/apiClient";
import { useAsyncData } from "../lib/asyncData";
import { useLiveRefresh } from "../lib/liveRefresh";

export function RankingsPage() {
  const auth = useAuth();
  const [selectedCompetitionId, setSelectedCompetitionId] = useState("");
  const [search, setSearch] = useState("");
  const [competitionsState] = useAsyncData(listCompetitions, []);
  const [rankingsState, reloadRankings] = useAsyncData(
    () => listLeaderboards(selectedCompetitionId || undefined),
    [selectedCompetitionId],
  );
  const liveRefresh = useLiveRefresh(reloadRankings, true, 60000);

  useEffect(() => {
    if (competitionsState.status !== "ready") return;
    if (selectedCompetitionId) return;
    setSelectedCompetitionId(competitionsState.data.data[0]?.id ?? "");
  }, [competitionsState, selectedCompetitionId]);

  const filteredEntries = useMemo(() => {
    if (rankingsState.status !== "ready") return [];
    const query = search.trim().toLowerCase();
    if (!query) return [...rankingsState.data.data];
    return rankingsState.data.data.filter((entry) => {
      const name = (entry.displayName ?? "").toLowerCase();
      const email = (entry.email ?? "").toLowerCase();
      const id = entry.userId.toLowerCase();
      return (
        name.includes(query) || email.includes(query) || id.includes(query)
      );
    });
  }, [rankingsState, search]);

  const currentUserEntry = useMemo(() => {
    const user = auth.user;
    if (!user) return null;
    return (
      filteredEntries.find((entry) => isCurrentUserEntry(entry, user)) ?? null
    );
  }, [auth.user, filteredEntries]);

  return (
    <div className="page rankings-page">
      <div className="page-heading">
        <p className="eyebrow">SportsRush</p>
        <h1>Rankings</h1>
        <p>
          Follow public leaderboards across competitions with responsive tables,
          quick search, and current-user context.
        </p>
      </div>

      <section className="rankings-toolbar panel">
        <LeaderboardFilters
          competitions={
            competitionsState.status === "ready"
              ? (competitionsState.data.data as readonly PublicCompetition[])
              : []
          }
          selectedCompetitionId={selectedCompetitionId}
          search={search}
          onCompetitionChange={setSelectedCompetitionId}
          onSearchChange={setSearch}
        />
        <div className="rankings-toolbar-actions">
          <button
            className="button secondary compact"
            type="button"
            onClick={liveRefresh.refreshNow}
          >
            Refresh leaderboard
          </button>
          {liveRefresh.lastRefreshAt ? (
            <span className="muted">
              Refreshed{" "}
              {new Date(liveRefresh.lastRefreshAt).toLocaleTimeString()}
            </span>
          ) : (
            <span className="muted">Refreshes every 60 seconds</span>
          )}
        </div>
      </section>

      {competitionsState.status === "loading" ? (
        <LoadingState label="Loading competitions" />
      ) : null}
      {competitionsState.status === "error" ? (
        <ErrorState message={competitionsState.message} />
      ) : null}
      {rankingsState.status === "loading" ? (
        <LoadingState label="Loading rankings" />
      ) : null}
      {rankingsState.status === "error" ? (
        <ErrorState message={rankingsState.message} onRetry={reloadRankings} />
      ) : null}

      {currentUserEntry ? (
        <UserRankHighlight entry={currentUserEntry} active />
      ) : null}

      {rankingsState.status === "ready" && filteredEntries.length === 0 ? (
        <EmptyState message="No leaderboard entries match those filters." />
      ) : null}

      {rankingsState.status === "ready" && filteredEntries.length > 0 ? (
        <>
          <div className="leaderboard-cards">
            {filteredEntries.map((entry) => (
              <LeaderboardCard
                key={entry.userId}
                entry={entry}
                currentUser={isCurrentUserEntry(entry, auth.user)}
              />
            ))}
          </div>
          <LeaderboardTable
            entries={filteredEntries}
            {...(auth.user
              ? {
                  currentUserEmail: auth.user.email,
                  currentUserId: auth.user.id,
                }
              : {})}
          />
        </>
      ) : null}
    </div>
  );
}
