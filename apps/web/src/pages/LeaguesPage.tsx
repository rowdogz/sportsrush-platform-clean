import { useEffect, useMemo, useState } from "react";
import { EmptyState, ErrorState, LoadingState } from "../components/PageState";
import { JoinLeagueModal } from "../components/leagues/JoinLeagueModal";
import { LeagueCard } from "../components/leagues/LeagueCard";
import { LeagueStandingsPreview } from "../components/leagues/LeagueStandingsPreview";
import { useAuth } from "../contexts/AuthContext";
import type {
  LeaderboardEntry,
  PaginatedResult,
  PrivateLeagueDetail,
  PrivateLeagueSummary,
  PublicCompetition,
} from "../features/types";
import {
  getPrivateLeague,
  joinPrivateLeague,
  listCompetitions,
  listMyLeaderboards,
  listPrivateLeagues,
} from "../lib/apiClient";
import { errorMessage, useAsyncData } from "../lib/asyncData";

export function LeaguesPage({ onLogin }: { readonly onLogin: () => void }) {
  const auth = useAuth();
  const [search, setSearch] = useState("");
  const [selectedLeagueId, setSelectedLeagueId] = useState<string | null>(null);
  const [joinOpen, setJoinOpen] = useState(false);
  const [joinError, setJoinError] = useState<string | null>(null);
  const [isJoining, setIsJoining] = useState(false);
  const [joinSuccess, setJoinSuccess] = useState<string | null>(null);

  const [competitionsState] = useAsyncData(listCompetitions, []);
  const [leaguesState, reloadLeagues] = useAsyncData(
    async () =>
      auth.accessToken
        ? listPrivateLeagues(auth.accessToken, search)
        : ({
            data: [],
            meta: { page: 1, limit: 25, total: 0, hasMore: false },
          } satisfies PaginatedResult<PrivateLeagueSummary>),
    [auth.accessToken, search],
  );

  useEffect(() => {
    if (leaguesState.status !== "ready") return;
    if (
      selectedLeagueId &&
      leaguesState.data.data.some((league) => league.id === selectedLeagueId)
    ) {
      return;
    }
    setSelectedLeagueId(leaguesState.data.data[0]?.id ?? null);
  }, [leaguesState, selectedLeagueId]);

  const [detailState, reloadDetail] = useAsyncData(
    async () =>
      auth.accessToken && selectedLeagueId
        ? getPrivateLeague(auth.accessToken, selectedLeagueId)
        : null,
    [auth.accessToken, selectedLeagueId],
  );

  const [standingsState, reloadStandings] = useAsyncData(
    async () =>
      auth.accessToken && selectedLeagueId
        ? listMyLeaderboards(auth.accessToken, {
            privateLeagueId: selectedLeagueId,
          })
        : {
            data: [],
            meta: { page: 1, limit: 25, total: 0, hasMore: false },
          },
    [auth.accessToken, selectedLeagueId],
  );

  const privateLeagues =
    leaguesState.status === "ready" ? leaguesState.data.data : [];
  const selectedLeague =
    detailState.status === "ready" ? detailState.data : null;

  const publicCompetitionCards =
    competitionsState.status === "ready"
      ? competitionsState.data.data.slice(0, 3)
      : [];

  const linkedCompetitionNames = useMemo(() => {
    if (!selectedLeague) return "";
    return selectedLeague.competitions
      .map((competition) => competition.competitionName)
      .filter(Boolean)
      .join(" · ");
  }, [selectedLeague]);

  async function handleJoin(inviteCode: string) {
    if (!auth.accessToken) return;
    if (!inviteCode.trim()) {
      setJoinError("Enter an invite code.");
      return;
    }
    setJoinError(null);
    setJoinSuccess(null);
    setIsJoining(true);
    try {
      const joined = await joinPrivateLeague(
        auth.accessToken,
        inviteCode.trim(),
      );
      setJoinSuccess(`Joined ${joined.name}.`);
      setJoinOpen(false);
      setSelectedLeagueId(joined.id);
      reloadLeagues();
      reloadDetail();
      reloadStandings();
    } catch (error: unknown) {
      setJoinError(errorMessage(error));
    } finally {
      setIsJoining(false);
    }
  }

  return (
    <div className="page leagues-page">
      <div className="page-heading">
        <p className="eyebrow">SportsRush</p>
        <h1>Leagues</h1>
        <p>
          Track competition communities, join private leagues by invite code,
          and preview league standings without leaving the SportsRush shell.
        </p>
      </div>

      <section className="panel">
        <div className="profile-section-header">
          <h2>Public competitions</h2>
          <span className="muted">League cards and banners foundation</span>
        </div>
        {competitionsState.status === "loading" ? (
          <LoadingState label="Loading competitions" />
        ) : null}
        {competitionsState.status === "error" ? (
          <ErrorState message={competitionsState.message} />
        ) : null}
        {competitionsState.status === "ready" ? (
          <div className="card-grid">
            {publicCompetitionCards.map((competition: PublicCompetition) => (
              <article className="entity-card" key={competition.id}>
                <p className="eyebrow">Public league</p>
                <h3>{competition.name}</h3>
                <p>{competition.shortName ?? competition.slug}</p>
                <span>{competition.countryCode ?? "Global"}</span>
              </article>
            ))}
          </div>
        ) : null}
      </section>

      <section className="panel">
        <div className="profile-section-header">
          <h2>Private leagues</h2>
          <div className="hero-actions">
            {auth.isAuthenticated ? (
              <>
                <button
                  className="button secondary"
                  type="button"
                  onClick={() => setJoinOpen(true)}
                >
                  Join with invite code
                </button>
                <label className="league-search">
                  Search
                  <input
                    aria-label="League search"
                    placeholder="Search your leagues"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                  />
                </label>
              </>
            ) : (
              <button className="button" type="button" onClick={onLogin}>
                Login to manage leagues
              </button>
            )}
          </div>
        </div>

        {joinSuccess ? <p className="form-success">{joinSuccess}</p> : null}

        {!auth.isAuthenticated ? (
          <EmptyState message="Login to view private leagues, join by invite code, and unlock league standings." />
        ) : null}

        {auth.isAuthenticated && leaguesState.status === "loading" ? (
          <LoadingState label="Loading private leagues" />
        ) : null}
        {auth.isAuthenticated && leaguesState.status === "error" ? (
          <ErrorState message={leaguesState.message} onRetry={reloadLeagues} />
        ) : null}
        {auth.isAuthenticated &&
        leaguesState.status === "ready" &&
        privateLeagues.length === 0 ? (
          <EmptyState message="You have not joined any private leagues yet." />
        ) : null}

        {auth.isAuthenticated &&
        leaguesState.status === "ready" &&
        privateLeagues.length > 0 ? (
          <div className="league-layout">
            <div className="league-list">
              {privateLeagues.map((league) => (
                <LeagueCard
                  key={league.id}
                  active={league.id === selectedLeagueId}
                  league={league}
                  onOpen={setSelectedLeagueId}
                />
              ))}
            </div>

            <div className="league-detail-column">
              {detailState.status === "loading" ? (
                <LoadingState label="Loading league overview" />
              ) : null}
              {detailState.status === "error" ? (
                <ErrorState
                  message={detailState.message}
                  onRetry={reloadDetail}
                />
              ) : null}
              {selectedLeague ? (
                <LeagueDetailPanel
                  league={selectedLeague}
                  linkedCompetitionNames={linkedCompetitionNames}
                  standingsState={standingsState}
                  {...(auth.user ? { currentUserId: auth.user.id } : {})}
                  onRetryStandings={reloadStandings}
                />
              ) : null}
            </div>
          </div>
        ) : null}
      </section>

      <JoinLeagueModal
        error={joinError}
        isOpen={joinOpen}
        isSubmitting={isJoining}
        onClose={() => {
          setJoinOpen(false);
          setJoinError(null);
        }}
        onSubmit={handleJoin}
      />
    </div>
  );
}

function LeagueDetailPanel({
  league,
  linkedCompetitionNames,
  standingsState,
  currentUserId,
  onRetryStandings,
}: {
  readonly league: PrivateLeagueDetail;
  readonly linkedCompetitionNames: string;
  readonly standingsState:
    | { readonly status: "loading" }
    | { readonly status: "error"; readonly message: string }
    | {
        readonly status: "ready";
        readonly data: PaginatedResult<LeaderboardEntry>;
      };
  readonly currentUserId?: string;
  readonly onRetryStandings: () => void;
}) {
  return (
    <section className="league-detail-card">
      {league.bannerUrl ? (
        <div
          aria-hidden="true"
          className="league-detail-banner"
          style={{ backgroundImage: `url(${league.bannerUrl})` }}
        />
      ) : null}
      <div className="league-detail-body">
        <p className="eyebrow">League overview</p>
        <h2>{league.name}</h2>
        <p>{league.description ?? "Private league overview and standings."}</p>
        <div className="league-detail-meta">
          <span>{league.memberCount} members</span>
          <span>
            {linkedCompetitionNames ||
              "Competition links available after setup"}
          </span>
        </div>

        <div className="league-members-panel">
          <div className="profile-section-header">
            <h3>Members</h3>
            <span className="muted">{league.members.length} total</span>
          </div>
          <div className="league-members-list">
            {league.members.map((member) => (
              <div className="league-member-row" key={member.userId}>
                <strong>
                  {member.displayName ?? member.email ?? member.userId}
                </strong>
                <span>{member.role}</span>
              </div>
            ))}
          </div>
        </div>

        {standingsState.status === "loading" ? (
          <LoadingState label="Loading league standings" />
        ) : null}
        {standingsState.status === "error" ? (
          <ErrorState
            message={standingsState.message}
            onRetry={onRetryStandings}
          />
        ) : null}
        {standingsState.status === "ready" &&
        standingsState.data.data.length === 0 ? (
          <EmptyState message="No league standings are available yet." />
        ) : null}
        {standingsState.status === "ready" &&
        standingsState.data.data.length > 0 ? (
          <LeagueStandingsPreview
            entries={standingsState.data.data.slice(0, 5)}
            {...(currentUserId ? { currentUserId } : {})}
          />
        ) : null}
      </div>
    </section>
  );
}
