import { useCallback, useEffect, useState, type FormEvent } from "react";
import {
  addPrivateLeagueMember,
  archivePrivateLeague,
  createPrivateLeague,
  listPrivateLeagues,
  removePrivateLeagueMember,
  unarchivePrivateLeague,
  updatePrivateLeague,
} from "../features/private-leagues/api";
import type {
  AdminPrivateLeague,
  PrivateLeagueWritePayload,
} from "../features/private-leagues/types";
import {
  AdminFeedback,
  adminErrorToast,
  adminSuccessToast,
  type AdminFeedbackState,
} from "../components/admin/AdminFeedback";
import {
  AdminTableEmpty,
  AdminTableError,
  AdminTableLoading,
} from "../components/admin/AdminTableState";
import { ApiError } from "../lib/apiClient";

type PageState =
  | { readonly status: "loading" }
  | {
      readonly status: "success";
      readonly leagues: readonly AdminPrivateLeague[];
    }
  | { readonly status: "error"; readonly message: string };

type FormValues = {
  readonly slug: string;
  readonly name: string;
  readonly description: string;
  readonly logoUrl: string;
  readonly bannerUrl: string;
  readonly ownerUserId: string;
  readonly competitionIds: string;
};

type MemberFormValues = {
  readonly leagueId: string;
  readonly userId: string;
  readonly role: "owner" | "admin" | "member";
};

const emptyValues: FormValues = {
  slug: "",
  name: "",
  description: "",
  logoUrl: "",
  bannerUrl: "",
  ownerUserId: "",
  competitionIds: "",
};

const emptyMemberValues: MemberFormValues = {
  leagueId: "",
  userId: "",
  role: "member",
};

function errorMessage(error: unknown): string {
  if (error instanceof ApiError || error instanceof Error) return error.message;
  return "Unable to load private leagues.";
}

function optional(value: string): string | null {
  const trimmed = value.trim();
  return trimmed ? trimmed : null;
}

function competitionIds(value: string): string[] {
  return value
    .split(",")
    .map((item) => item.trim())
    .filter(Boolean);
}

function payload(
  values: FormValues,
  { includeCompetitionIds }: { readonly includeCompetitionIds: boolean },
): PrivateLeagueWritePayload {
  const nextPayload: PrivateLeagueWritePayload = {
    slug: values.slug.trim(),
    name: values.name.trim(),
    description: optional(values.description),
    logoUrl: optional(values.logoUrl),
    bannerUrl: optional(values.bannerUrl),
    ownerUserId: optional(values.ownerUserId),
  };
  const linkedCompetitionIds = competitionIds(values.competitionIds);
  return includeCompetitionIds || linkedCompetitionIds.length > 0
    ? { ...nextPayload, competitionIds: linkedCompetitionIds }
    : nextPayload;
}

function toFormValues(league: AdminPrivateLeague): FormValues {
  return {
    slug: league.slug,
    name: league.name,
    description: league.description ?? "",
    logoUrl: league.logoUrl ?? "",
    bannerUrl: league.bannerUrl ?? "",
    ownerUserId: league.ownerUserId ?? "",
    competitionIds: "",
  };
}

function validate(values: FormValues): string | null {
  if (!values.slug.trim() || !values.name.trim()) {
    return "Slug and name are required.";
  }
  return null;
}

export function PrivateLeaguesPage() {
  const [state, setState] = useState<PageState>({ status: "loading" });
  const [createValues, setCreateValues] = useState<FormValues>(emptyValues);
  const [editValues, setEditValues] = useState<FormValues>(emptyValues);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [feedback, setFeedback] = useState<AdminFeedbackState | null>(null);
  const [pendingAction, setPendingAction] = useState<string | null>(null);
  const [memberValues, setMemberValues] =
    useState<MemberFormValues>(emptyMemberValues);

  const loadLeagues = useCallback(async (showLoading = false) => {
    if (showLoading) setState({ status: "loading" });
    try {
      const response = await listPrivateLeagues({ includeArchived: true });
      setState({ status: "success", leagues: response.data });
    } catch (error) {
      setState({ status: "error", message: errorMessage(error) });
    }
  }, []);

  useEffect(() => {
    void loadLeagues(true);
  }, [loadLeagues]);

  async function handleCreate(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFeedback(null);
    const validationMessage = validate(createValues);
    if (validationMessage) {
      setFeedback(adminErrorToast(validationMessage));
      return;
    }
    setPendingAction("create");
    try {
      await createPrivateLeague(
        payload(createValues, { includeCompetitionIds: true }),
      );
      setCreateValues(emptyValues);
      await loadLeagues();
      setFeedback(adminSuccessToast("Private league created."));
    } catch (error) {
      setFeedback(adminErrorToast(errorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  async function handleEdit(
    event: FormEvent<HTMLFormElement>,
    league: AdminPrivateLeague,
  ) {
    event.preventDefault();
    setFeedback(null);
    const validationMessage = validate(editValues);
    if (validationMessage) {
      setFeedback(adminErrorToast(validationMessage));
      return;
    }
    setPendingAction(`edit:${league.id}`);
    try {
      await updatePrivateLeague(
        league.id,
        payload(editValues, { includeCompetitionIds: false }),
      );
      setEditingId(null);
      await loadLeagues();
      setFeedback(adminSuccessToast("Private league updated."));
    } catch (error) {
      setFeedback(adminErrorToast(errorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  async function toggleArchive(league: AdminPrivateLeague) {
    setFeedback(null);
    const verb = league.isArchived ? "Unarchive" : "Archive";
    if (!window.confirm(`${verb} ${league.name}?`)) return;
    setPendingAction(`archive:${league.id}`);
    try {
      if (league.isArchived) {
        await unarchivePrivateLeague(league.id);
      } else {
        await archivePrivateLeague(league.id);
      }
      await loadLeagues();
      setFeedback(
        adminSuccessToast(
          `Private league ${league.isArchived ? "unarchived" : "archived"}.`,
        ),
      );
    } catch (error) {
      setFeedback(adminErrorToast(errorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  async function handleAddMember(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFeedback(null);
    if (!memberValues.leagueId || !memberValues.userId.trim()) {
      setFeedback(adminErrorToast("League and user ID are required."));
      return;
    }
    setPendingAction("member:add");
    try {
      await addPrivateLeagueMember(memberValues.leagueId, {
        userId: memberValues.userId.trim(),
        role: memberValues.role,
      });
      await loadLeagues();
      setMemberValues(emptyMemberValues);
      setFeedback(adminSuccessToast("Private league member added."));
    } catch (error) {
      setFeedback(adminErrorToast(errorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  async function handleRemoveMember() {
    setFeedback(null);
    if (!memberValues.leagueId || !memberValues.userId.trim()) {
      setFeedback(adminErrorToast("League and user ID are required."));
      return;
    }
    if (
      !window.confirm(`Remove ${memberValues.userId} from this private league?`)
    ) {
      return;
    }
    setPendingAction("member:remove");
    try {
      await removePrivateLeagueMember(
        memberValues.leagueId,
        memberValues.userId.trim(),
      );
      await loadLeagues();
      setMemberValues(emptyMemberValues);
      setFeedback(adminSuccessToast("Private league member removed."));
    } catch (error) {
      setFeedback(adminErrorToast(errorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  return (
    <section aria-labelledby="private-leagues-title">
      <div className="page-heading">
        <h2 id="private-leagues-title">Private Leagues</h2>
        <p>
          Manage private leagues, invite codes, linked competitions, and league
          branding.
        </p>
      </div>

      <form className="admin-form" onSubmit={handleCreate}>
        <div className="form-heading">
          <h3>Create private league</h3>
        </div>
        <LeagueForm values={createValues} onChange={setCreateValues} />
        <div className="form-actions">
          <button
            className="primary-button"
            type="submit"
            disabled={pendingAction === "create"}
          >
            Create private league
          </button>
        </div>
      </form>

      {state.status === "success" ? (
        <form className="admin-form" onSubmit={handleAddMember}>
          <div className="form-heading">
            <h3>Manage members</h3>
          </div>
          <div className="form-grid">
            <label>
              Private league
              <select
                value={memberValues.leagueId}
                onChange={(event) =>
                  setMemberValues({
                    ...memberValues,
                    leagueId: event.target.value,
                  })
                }
              >
                <option value="">Select a league</option>
                {state.leagues.map((league) => (
                  <option key={league.id} value={league.id}>
                    {league.name}
                  </option>
                ))}
              </select>
            </label>
            <label>
              Member user ID
              <input
                value={memberValues.userId}
                onChange={(event) =>
                  setMemberValues({
                    ...memberValues,
                    userId: event.target.value,
                  })
                }
              />
            </label>
            <label>
              Member role
              <select
                value={memberValues.role}
                onChange={(event) =>
                  setMemberValues({
                    ...memberValues,
                    role: event.target.value as MemberFormValues["role"],
                  })
                }
              >
                <option value="member">Member</option>
                <option value="admin">Admin</option>
                <option value="owner">Owner</option>
              </select>
            </label>
          </div>
          <div className="form-actions">
            <button
              className="primary-button"
              type="submit"
              disabled={pendingAction === "member:add"}
            >
              Add member
            </button>
            <button
              className="secondary-button"
              type="button"
              disabled={pendingAction === "member:remove"}
              onClick={() => {
                void handleRemoveMember();
              }}
            >
              Remove member
            </button>
          </div>
        </form>
      ) : null}

      <AdminFeedback feedback={feedback} />

      {state.status === "loading" ? (
        <AdminTableLoading message="Loading private leagues…" />
      ) : state.status === "error" ? (
        <AdminTableError
          title="Unable to load private leagues"
          message={state.message}
          onRetry={() => void loadLeagues(true)}
        />
      ) : state.leagues.length === 0 ? (
        <AdminTableEmpty
          title="No private leagues found"
          message="Create a private league to start testing league workflows."
        />
      ) : (
        <div className="admin-table-wrapper">
          <table className="admin-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Invite code</th>
                <th>Members</th>
                <th>Competitions</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {state.leagues.map((league) => (
                <tr key={league.id}>
                  <td>
                    <strong>{league.name}</strong>
                    <small>{league.slug}</small>
                  </td>
                  <td>{league.inviteCode}</td>
                  <td>{league.memberCount}</td>
                  <td>{league.competitionCount}</td>
                  <td>{league.isArchived ? "Archived" : "Active"}</td>
                  <td>
                    {editingId === league.id ? (
                      <form
                        className="inline-edit-form"
                        onSubmit={(event) => void handleEdit(event, league)}
                      >
                        <LeagueForm
                          values={editValues}
                          onChange={setEditValues}
                        />
                        <div className="row-actions">
                          <button
                            className="primary-button"
                            type="submit"
                            disabled={pendingAction === `edit:${league.id}`}
                          >
                            Save
                          </button>
                          <button
                            className="secondary-button"
                            type="button"
                            onClick={() => setEditingId(null)}
                          >
                            Cancel
                          </button>
                        </div>
                      </form>
                    ) : (
                      <div className="row-actions">
                        <button
                          className="secondary-button"
                          type="button"
                          onClick={() => {
                            setEditingId(league.id);
                            setEditValues(toFormValues(league));
                          }}
                        >
                          Edit
                        </button>
                        <button
                          className="secondary-button"
                          type="button"
                          onClick={() => void toggleArchive(league)}
                          disabled={pendingAction === `archive:${league.id}`}
                        >
                          {league.isArchived ? "Unarchive" : "Archive"}
                        </button>
                      </div>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </section>
  );
}

function LeagueForm({
  values,
  onChange,
}: {
  readonly values: FormValues;
  readonly onChange: (values: FormValues) => void;
}) {
  return (
    <div className="form-grid">
      <label>
        Slug
        <input
          value={values.slug}
          onChange={(event) =>
            onChange({ ...values, slug: event.target.value })
          }
        />
      </label>
      <label>
        Name
        <input
          value={values.name}
          onChange={(event) =>
            onChange({ ...values, name: event.target.value })
          }
        />
      </label>
      <label>
        Owner user ID
        <input
          value={values.ownerUserId}
          onChange={(event) =>
            onChange({ ...values, ownerUserId: event.target.value })
          }
        />
      </label>
      <label>
        Competition IDs
        <input
          placeholder="Comma separated IDs"
          value={values.competitionIds}
          onChange={(event) =>
            onChange({ ...values, competitionIds: event.target.value })
          }
        />
      </label>
      <label>
        Logo URL
        <input
          value={values.logoUrl}
          onChange={(event) =>
            onChange({ ...values, logoUrl: event.target.value })
          }
        />
      </label>
      <label>
        Banner URL
        <input
          value={values.bannerUrl}
          onChange={(event) =>
            onChange({ ...values, bannerUrl: event.target.value })
          }
        />
      </label>
      <label>
        Description
        <textarea
          value={values.description}
          onChange={(event) =>
            onChange({ ...values, description: event.target.value })
          }
        />
      </label>
    </div>
  );
}
