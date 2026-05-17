import type { LeaderboardEntry } from "../../features/types";
import { RankMovementBadge } from "../rankings/RankMovementBadge";

export function LeagueStandingsPreview({
  entries,
  currentUserId,
}: {
  readonly entries: readonly LeaderboardEntry[];
  readonly currentUserId?: string;
}) {
  return (
    <div className="league-standings-preview">
      <div className="league-standings-header">
        <h3>League standings</h3>
        <span className="muted">Top {entries.length}</span>
      </div>
      <div className="league-standings-list">
        {entries.map((entry) => (
          <div
            key={entry.userId}
            className={`league-standing-row ${
              entry.userId === currentUserId
                ? "league-standing-row-current"
                : ""
            }`}
          >
            <span className="league-standing-rank">#{entry.rank}</span>
            <div className="league-standing-copy">
              <strong>
                {entry.displayName ?? entry.email ?? entry.userId}
              </strong>
              <span>{entry.totalPoints} pts</span>
            </div>
            <RankMovementBadge movement={entry.movement} />
          </div>
        ))}
      </div>
    </div>
  );
}
