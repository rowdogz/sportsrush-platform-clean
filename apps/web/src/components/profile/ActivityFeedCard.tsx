import type { Prediction, PublicFixture } from "../../features/types";

export function ActivityFeedCard({
  predictions,
  fixturesById,
}: {
  readonly predictions: readonly Prediction[];
  readonly fixturesById: ReadonlyMap<string, PublicFixture>;
}) {
  return (
    <section className="activity-feed-card">
      <div className="profile-section-header">
        <h3>Recent prediction activity</h3>
        <span className="muted">Social feed foundation</span>
      </div>
      <div className="activity-feed-list">
        {predictions.map((prediction) => {
          const fixture = fixturesById.get(prediction.fixtureId);
          return (
            <article className="activity-feed-item" key={prediction.id}>
              <strong>
                {fixture
                  ? `${fixture.homeTeam.name} vs ${fixture.awayTeam.name}`
                  : prediction.fixtureId}
              </strong>
              <span>
                Predicted {prediction.homeScore}-{prediction.awayScore}
              </span>
              <span className="muted">
                Updated {new Date(prediction.updatedAt).toLocaleString()}
              </span>
            </article>
          );
        })}
      </div>
    </section>
  );
}
