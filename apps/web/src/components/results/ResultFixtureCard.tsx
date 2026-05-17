import type { Prediction, PublicFixture } from "../../features/types";
import { PointsBreakdownBadge } from "./PointsBreakdownBadge";
import { PredictionComparison } from "./PredictionComparison";
import { ResultStatusBadge } from "./ResultStatusBadge";

function teamName(team: PublicFixture["homeTeam"]): string {
  return team.displayName ?? team.shortName ?? team.name;
}

function initials(team: PublicFixture["homeTeam"]): string {
  return (team.shortName ?? team.name).slice(0, 3).toUpperCase();
}

function formatKickoff(kickoffTime: string): string {
  return new Date(kickoffTime).toLocaleString(undefined, {
    weekday: "short",
    month: "short",
    day: "numeric",
    hour: "numeric",
    minute: "2-digit",
  });
}

export function ResultFixtureCard({
  fixture,
  prediction,
  showPointsPlaceholder,
}: {
  readonly fixture: PublicFixture;
  readonly prediction: Prediction | undefined;
  readonly showPointsPlaceholder: boolean;
}) {
  return (
    <article className={`result-card result-card-${fixture.status}`}>
      <div className="result-card-header">
        <div>
          <p className="result-kickoff">{formatKickoff(fixture.kickoffTime)}</p>
          <h3>{fixture.round.name}</h3>
          <p className="result-context">
            {fixture.competition.shortName ?? fixture.competition.name}
            {fixture.venue ? ` · ${fixture.venue}` : ""}
          </p>
        </div>
        <ResultStatusBadge status={fixture.status} />
      </div>

      <div className="result-card-body">
        <div className="result-team">
          <span className="team-mark">{initials(fixture.homeTeam)}</span>
          <div>
            <strong>{teamName(fixture.homeTeam)}</strong>
            <span>Home</span>
          </div>
        </div>

        <div className="result-score-panel">
          <div className="result-score-display">
            <span>{fixture.homeScore ?? "—"}</span>
            <span className="prediction-separator">-</span>
            <span>{fixture.awayScore ?? "—"}</span>
          </div>
          {showPointsPlaceholder ? <PointsBreakdownBadge /> : null}
        </div>

        <div className="result-team result-team-away">
          <div>
            <strong>{teamName(fixture.awayTeam)}</strong>
            <span>Away</span>
          </div>
          <span className="team-mark">{initials(fixture.awayTeam)}</span>
        </div>
      </div>

      <PredictionComparison fixture={fixture} prediction={prediction} />
    </article>
  );
}
