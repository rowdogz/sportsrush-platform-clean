import type { Prediction, PublicFixture } from "../../features/types";

function resultKind(
  homeScore: number,
  awayScore: number,
): "home" | "away" | "draw" {
  if (homeScore === awayScore) return "draw";
  return homeScore > awayScore ? "home" : "away";
}

function scoringLabel(
  fixture: PublicFixture,
  prediction: Prediction,
): string | null {
  if (fixture.homeScore === null || fixture.awayScore === null) return null;
  if (
    prediction.homeScore === fixture.homeScore &&
    prediction.awayScore === fixture.awayScore
  ) {
    return "Exact score";
  }
  if (
    resultKind(prediction.homeScore, prediction.awayScore) ===
    resultKind(fixture.homeScore, fixture.awayScore)
  ) {
    return "Correct result";
  }
  return "Missed result";
}

export function PredictionComparison({
  fixture,
  prediction,
}: {
  readonly fixture: PublicFixture;
  readonly prediction: Prediction | undefined;
}) {
  if (!prediction) {
    return (
      <div className="prediction-comparison prediction-comparison-empty">
        <strong>No saved prediction</strong>
        <span>No comparison is available for this fixture.</span>
      </div>
    );
  }

  const badge = scoringLabel(fixture, prediction);
  return (
    <div className="prediction-comparison">
      <div className="prediction-comparison-copy">
        <strong>
          Your prediction: {prediction.homeScore} - {prediction.awayScore}
        </strong>
        <span>Saved {new Date(prediction.updatedAt).toLocaleString()}</span>
      </div>
      {badge ? (
        <span
          className={`result-indicator-badge ${
            badge === "Exact score"
              ? "result-indicator-badge-exact"
              : badge === "Correct result"
                ? "result-indicator-badge-correct"
                : "result-indicator-badge-missed"
          }`}
        >
          {badge}
        </span>
      ) : null}
    </div>
  );
}
