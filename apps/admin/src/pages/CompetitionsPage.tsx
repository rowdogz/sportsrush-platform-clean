import { useEffect, useState } from "react";
import { listCompetitions } from "../features/competitions/api";
import type { AdminCompetition } from "../features/competitions/types";
import { ApiError } from "../lib/apiClient";

type CompetitionsState =
  | { readonly status: "loading" }
  | {
      readonly status: "success";
      readonly competitions: readonly AdminCompetition[];
    }
  | { readonly status: "error"; readonly message: string };

function getErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    return error.message;
  }

  if (error instanceof Error) {
    return error.message;
  }

  return "Unable to load competitions.";
}

export function CompetitionsPage() {
  const [state, setState] = useState<CompetitionsState>({
    status: "loading",
  });

  useEffect(() => {
    let isMounted = true;

    async function loadCompetitions() {
      try {
        const response = await listCompetitions();
        if (isMounted) {
          setState({ status: "success", competitions: response.data });
        }
      } catch (error) {
        if (isMounted) {
          setState({ status: "error", message: getErrorMessage(error) });
        }
      }
    }

    void loadCompetitions();

    return () => {
      isMounted = false;
    };
  }, []);

  return (
    <section aria-labelledby="competitions-title">
      <div className="page-heading">
        <h2 id="competitions-title">Competitions</h2>
        <p>Read-only overview of competitions configured in SportsRush.</p>
      </div>

      {state.status === "loading" ? (
        <div className="state-panel" role="status">
          Loading competitions…
        </div>
      ) : null}

      {state.status === "error" ? (
        <div className="state-panel error-panel" role="alert">
          <strong>Unable to load competitions</strong>
          <span>{state.message}</span>
        </div>
      ) : null}

      {state.status === "success" && state.competitions.length === 0 ? (
        <div className="state-panel">
          <strong>No competitions found</strong>
          <span>Competitions will appear here after they are added.</span>
        </div>
      ) : null}

      {state.status === "success" && state.competitions.length > 0 ? (
        <div className="competitions-table-wrapper">
          <table className="competitions-table">
            <thead>
              <tr>
                <th scope="col">Name</th>
                <th scope="col">Slug</th>
                <th scope="col">Sport ID</th>
                <th scope="col">Country</th>
                <th scope="col">Status</th>
              </tr>
            </thead>
            <tbody>
              {state.competitions.map((competition) => (
                <tr key={competition.id}>
                  <td>{competition.name}</td>
                  <td>{competition.slug}</td>
                  <td>{competition.sportId}</td>
                  <td>{competition.countryCode ?? "—"}</td>
                  <td>
                    <span
                      className={
                        competition.isActive
                          ? "status-pill status-pill-active"
                          : "status-pill status-pill-inactive"
                      }
                    >
                      {competition.isActive ? "Active" : "Inactive"}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      ) : null}
    </section>
  );
}
