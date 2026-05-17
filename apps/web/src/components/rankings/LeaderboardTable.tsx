import type { LeaderboardEntry } from "../../features/types";
import { RankMovementBadge } from "./RankMovementBadge";

export function LeaderboardTable({
  entries,
  currentUserId,
  currentUserEmail,
}: {
  readonly entries: readonly LeaderboardEntry[];
  readonly currentUserId?: string;
  readonly currentUserEmail?: string;
}) {
  return (
    <div className="table-scroll leaderboard-table-wrap">
      <table className="responsive-table leaderboard-table">
        <thead>
          <tr>
            <th>Rank</th>
            <th>User</th>
            <th>Movement</th>
            <th>Points</th>
            <th>Exact</th>
            <th>Correct results</th>
            <th>Scored</th>
          </tr>
        </thead>
        <tbody>
          {entries.map((entry) => {
            const currentUser =
              entry.userId === currentUserId ||
              entry.email === currentUserEmail;
            return (
              <tr
                key={entry.userId}
                className={currentUser ? "leaderboard-row-current" : undefined}
              >
                <td>{entry.rank}</td>
                <td>{entry.displayName ?? entry.email ?? entry.userId}</td>
                <td>
                  <RankMovementBadge movement={entry.movement} />
                </td>
                <td>{entry.totalPoints}</td>
                <td>{entry.exactScores}</td>
                <td>{entry.correctResults}</td>
                <td>{entry.predictionsScored}</td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}
