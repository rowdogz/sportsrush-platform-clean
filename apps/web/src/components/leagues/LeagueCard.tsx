import type { PrivateLeagueSummary } from "../../features/types";

export function LeagueCard({
  league,
  active,
  onOpen,
}: {
  readonly league: PrivateLeagueSummary;
  readonly active: boolean;
  readonly onOpen: (leagueId: string) => void;
}) {
  return (
    <article className={`league-card ${active ? "league-card-active" : ""}`}>
      {league.bannerUrl ? (
        <div
          aria-hidden="true"
          className="league-card-banner"
          style={{ backgroundImage: `url(${league.bannerUrl})` }}
        />
      ) : null}
      <div className="league-card-body">
        <div className="league-card-top">
          <div>
            <p className="eyebrow">Private league</p>
            <h3>{league.name}</h3>
          </div>
          <span className="status-chip">{league.viewerRole ?? "member"}</span>
        </div>
        <p>
          {league.description ?? "Linked competition predictions and rankings."}
        </p>
        <dl className="league-card-metrics">
          <div>
            <dt>Members</dt>
            <dd>{league.memberCount}</dd>
          </div>
          <div>
            <dt>Competitions</dt>
            <dd>{league.competitionCount}</dd>
          </div>
        </dl>
        <button
          className="button secondary"
          type="button"
          onClick={() => onOpen(league.id)}
        >
          Open league
        </button>
      </div>
    </article>
  );
}
