import type { LeaderboardEntry } from "../../features/types";
import { RankMovementBadge } from "./RankMovementBadge";

export function LeaderboardCard({
  entry,
  currentUser,
}: {
  readonly entry: LeaderboardEntry;
  readonly currentUser: boolean;
}) {
  return (
    <article
      className={`leaderboard-card ${currentUser ? "leaderboard-card-current" : ""}`}
    >
      <div className="leaderboard-card-top">
        <div>
          <p className="leaderboard-rank">#{entry.rank}</p>
          <h3>{entry.displayName ?? entry.email ?? entry.userId}</h3>
        </div>
        <RankMovementBadge movement={entry.movement} />
      </div>
      <div className="leaderboard-card-score">
        <strong>{entry.totalPoints}</strong>
        <span>points</span>
      </div>
      <dl className="leaderboard-card-metrics">
        <div>
          <dt>Exact</dt>
          <dd>{entry.exactScores}</dd>
        </div>
        <div>
          <dt>Correct</dt>
          <dd>{entry.correctResults}</dd>
        </div>
        <div>
          <dt>Scored</dt>
          <dd>{entry.predictionsScored}</dd>
        </div>
      </dl>
    </article>
  );
}
