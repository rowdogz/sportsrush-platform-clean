import type { AuthMe } from "../../features/types";

export function ProfileSummaryCard({
  me,
  onLogout,
  onRefreshSession,
}: {
  readonly me: AuthMe;
  readonly onLogout: () => void;
  readonly onRefreshSession: () => Promise<void>;
}) {
  return (
    <section className="profile-summary-card">
      <p className="eyebrow">Account</p>
      <h2>{me.profile.displayName ?? me.user.email}</h2>
      <p>{me.user.email}</p>
      <dl className="profile-meta-list">
        <div>
          <dt>Role</dt>
          <dd>{me.user.role}</dd>
        </div>
        <div>
          <dt>Timezone</dt>
          <dd>{me.profile.timezone ?? "UTC"}</dd>
        </div>
      </dl>
      <div className="hero-actions">
        <button
          className="button secondary"
          type="button"
          onClick={() => void onRefreshSession()}
        >
          Refresh session
        </button>
        <button className="button" type="button" onClick={onLogout}>
          Logout
        </button>
      </div>
    </section>
  );
}
