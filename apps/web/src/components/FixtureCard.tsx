import type { PublicFixture } from "../features/types";
import type { ReactNode } from "react";

function teamName(team: PublicFixture["homeTeam"]): string {
  return team.displayName ?? team.shortName ?? team.name;
}

export function FixtureCard({
  fixture,
  action,
  statusLabel,
}: {
  readonly fixture: PublicFixture;
  readonly action?: ReactNode;
  readonly statusLabel?: string;
}) {
  const hasScore = fixture.homeScore !== null && fixture.awayScore !== null;
  const displayStatus = statusLabel ?? fixture.status;
  return (
    <article className="fixture-card">
      <div className="fixture-meta">
        <span>{new Date(fixture.kickoffTime).toLocaleString()}</span>
        <span className={`status status-${displayStatus}`}>
          {displayStatus}
        </span>
      </div>
      <div className="fixture-scoreline">
        <strong>{teamName(fixture.homeTeam)}</strong>
        <span className="score">
          {hasScore ? `${fixture.homeScore} - ${fixture.awayScore}` : "vs"}
        </span>
        <strong>{teamName(fixture.awayTeam)}</strong>
      </div>
      <div className="fixture-meta">
        <span>{fixture.competition.shortName ?? fixture.competition.name}</span>
        <span>{fixture.round.name}</span>
        {fixture.venue ? <span>{fixture.venue}</span> : null}
      </div>
      {action ? <div className="fixture-action">{action}</div> : null}
    </article>
  );
}
