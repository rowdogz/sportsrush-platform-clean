import { useCallback, useEffect, useState, type FormEvent } from "react";
import {
  listUsers,
  reactivateUser,
  suspendUser,
  updateUserRole,
  updateUserStatus,
} from "../features/users/api";
import type {
  AdminUser,
  UserListFilters,
  UserRole,
} from "../features/users/types";
import { userRoles } from "../features/users/types";
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
import {
  mergeStoredObject,
  usePersistedAdminState,
} from "../hooks/usePersistedAdminState";
import { ApiError } from "../lib/apiClient";

type UsersState =
  | { readonly status: "loading" }
  | {
      readonly status: "success";
      readonly users: readonly AdminUser[];
    }
  | { readonly status: "error"; readonly message: string };

type FilterValues = {
  readonly search: string;
  readonly role: string;
  readonly status: string;
};

type FeedbackState = AdminFeedbackState;

const emptyFilterValues: FilterValues = {
  search: "",
  role: "",
  status: "",
};

function getErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    return error.message;
  }

  if (error instanceof Error) {
    return error.message;
  }

  return "Unable to load users.";
}

function toOptionalValue(value: string): string | null {
  const trimmedValue = value.trim();
  return trimmedValue ? trimmedValue : null;
}

function toFilters(values: FilterValues): UserListFilters {
  return {
    search: toOptionalValue(values.search),
    role: toOptionalValue(values.role) as UserRole | null,
    isActive:
      values.status === "active"
        ? true
        : values.status === "inactive"
          ? false
          : null,
  };
}

export function UsersPage() {
  const [state, setState] = useState<UsersState>({ status: "loading" });
  const [filters, setFilters] = usePersistedAdminState(
    "sr-admin:users:filters",
    emptyFilterValues,
    mergeStoredObject(emptyFilterValues),
  );
  const [roleDrafts, setRoleDrafts] = useState<Record<string, UserRole>>({});
  const [statusDrafts, setStatusDrafts] = useState<Record<string, string>>({});
  const [feedback, setFeedback] = useState<FeedbackState | null>(null);
  const [pendingAction, setPendingAction] = useState<string | null>(null);

  const loadUsers = useCallback(
    async (
      nextFilters: FilterValues,
      { showLoading = false }: { readonly showLoading?: boolean } = {},
    ) => {
      if (showLoading) {
        setState({ status: "loading" });
      }

      try {
        const response = await listUsers(toFilters(nextFilters));
        setState({ status: "success", users: response.data });
        setRoleDrafts(
          Object.fromEntries(response.data.map((user) => [user.id, user.role])),
        );
        setStatusDrafts(
          Object.fromEntries(
            response.data.map((user) => [
              user.id,
              user.isActive ? "active" : "inactive",
            ]),
          ),
        );
      } catch (error) {
        setState({ status: "error", message: getErrorMessage(error) });
      }
    },
    [],
  );

  useEffect(() => {
    void loadUsers(filters, { showLoading: true });
  }, [loadUsers]);

  async function handleFilterSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    await loadUsers(filters, { showLoading: true });
  }

  async function handleRoleUpdate(user: AdminUser) {
    const role = roleDrafts[user.id] ?? user.role;
    setFeedback(null);
    if (role === user.role) {
      setFeedback(adminErrorToast("Choose a different role first."));
      return;
    }
    if (
      !window.confirm(
        `Change ${user.email}'s role from ${user.role} to ${role}?`,
      )
    ) {
      return;
    }

    setPendingAction(`role:${user.id}`);
    try {
      await updateUserRole(user.id, role);
      await loadUsers(filters);
      setFeedback(adminSuccessToast("User role updated."));
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  async function handleStatusUpdate(user: AdminUser) {
    const status =
      statusDrafts[user.id] ?? (user.isActive ? "active" : "inactive");
    const isActive = status === "active";
    setFeedback(null);
    if (isActive === user.isActive) {
      setFeedback(adminErrorToast("Choose a different status first."));
      return;
    }
    if (
      !window.confirm(
        `${isActive ? "Reactivate" : "Deactivate"} ${user.email}?`,
      )
    ) {
      return;
    }

    setPendingAction(`status:${user.id}`);
    try {
      await updateUserStatus(user.id, isActive);
      await loadUsers(filters);
      setFeedback(adminSuccessToast("User status updated."));
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  async function handleSuspendToggle(user: AdminUser) {
    setFeedback(null);
    const action = user.isActive ? "Suspend" : "Reactivate";
    if (!window.confirm(`${action} ${user.email}?`)) {
      return;
    }

    setPendingAction(`suspend:${user.id}`);
    try {
      if (user.isActive) {
        await suspendUser(user.id);
      } else {
        await reactivateUser(user.id);
      }
      await loadUsers(filters);
      setFeedback(
        adminSuccessToast(
          user.isActive ? "User suspended." : "User reactivated.",
        ),
      );
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  return (
    <section aria-labelledby="users-title">
      <div className="page-heading">
        <h2 id="users-title">Users</h2>
        <p>Review registered SportsRush users and admin access roles.</p>
      </div>

      <form className="admin-form" onSubmit={handleFilterSubmit}>
        <div className="form-heading">
          <h3>Filter users</h3>
        </div>
        <div className="form-grid">
          <label>
            Search
            <input
              value={filters.search}
              onChange={(event) =>
                setFilters({ ...filters, search: event.target.value })
              }
            />
          </label>
          <label>
            Role
            <select
              value={filters.role}
              onChange={(event) =>
                setFilters({ ...filters, role: event.target.value })
              }
            >
              <option value="">All roles</option>
              {userRoles.map((role) => (
                <option key={role} value={role}>
                  {role}
                </option>
              ))}
            </select>
          </label>
          <label>
            Status
            <select
              value={filters.status}
              onChange={(event) =>
                setFilters({ ...filters, status: event.target.value })
              }
            >
              <option value="">All statuses</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </label>
        </div>
        <button className="secondary-button" type="submit">
          Apply filters
        </button>
      </form>

      <AdminFeedback feedback={feedback} />

      {state.status === "loading" ? (
        <AdminTableLoading message="Loading users…" />
      ) : null}

      {state.status === "error" ? (
        <AdminTableError title="Unable to load users" message={state.message} />
      ) : null}

      {state.status === "success" && state.users.length === 0 ? (
        <AdminTableEmpty
          title="No users found"
          message="Users will appear here after they register."
        />
      ) : null}

      {state.status === "success" && state.users.length > 0 ? (
        <div className="admin-table-wrapper">
          <table className="admin-table">
            <thead>
              <tr>
                <th scope="col">User</th>
                <th scope="col">Verification</th>
                <th scope="col">Role</th>
                <th scope="col">Status</th>
                <th scope="col">Email</th>
                <th scope="col">Created</th>
                <th scope="col">Updated</th>
                <th scope="col">Legacy</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {state.users.map((user) => (
                <tr key={user.id}>
                  <td>{user.displayName ?? "—"}</td>
                  <td>{user.email}</td>
                  <td>{user.role}</td>
                  <td>
                    <span
                      className={
                        user.isActive
                          ? "status-pill status-pill-active"
                          : "status-pill status-pill-inactive"
                      }
                    >
                      {user.isActive ? "active" : "inactive"}
                    </span>
                  </td>
                  <td>{user.emailVerifiedAt ? "verified" : "unverified"}</td>
                  <td>{user.createdAt}</td>
                  <td>{user.profileUpdatedAt ?? user.updatedAt}</td>
                  <td>{user.legacyWpUserId ?? "—"}</td>
                  <td>
                    <div className="row-actions">
                      <label>
                        <select
                          aria-label={`Role for ${user.email}`}
                          value={roleDrafts[user.id] ?? user.role}
                          onChange={(event) =>
                            setRoleDrafts({
                              ...roleDrafts,
                              [user.id]: event.target.value as UserRole,
                            })
                          }
                        >
                          {userRoles.map((role) => (
                            <option key={role} value={role}>
                              {role}
                            </option>
                          ))}
                        </select>
                      </label>
                      <button
                        className="secondary-button"
                        type="button"
                        disabled={pendingAction === `role:${user.id}`}
                        onClick={() => void handleRoleUpdate(user)}
                      >
                        Update role
                      </button>
                      <label>
                        <select
                          aria-label={`Status for ${user.email}`}
                          value={
                            statusDrafts[user.id] ??
                            (user.isActive ? "active" : "inactive")
                          }
                          onChange={(event) =>
                            setStatusDrafts({
                              ...statusDrafts,
                              [user.id]: event.target.value,
                            })
                          }
                        >
                          <option value="active">active</option>
                          <option value="inactive">inactive</option>
                        </select>
                      </label>
                      <button
                        className="secondary-button"
                        type="button"
                        disabled={pendingAction === `status:${user.id}`}
                        onClick={() => void handleStatusUpdate(user)}
                      >
                        Update status
                      </button>
                      <button
                        className={
                          user.isActive ? "danger-button" : "secondary-button"
                        }
                        type="button"
                        disabled={pendingAction === `suspend:${user.id}`}
                        onClick={() => void handleSuspendToggle(user)}
                      >
                        {user.isActive ? "Suspend" : "Reactivate"}
                      </button>
                    </div>
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
