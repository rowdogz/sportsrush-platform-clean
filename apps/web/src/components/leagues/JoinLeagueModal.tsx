import { useState, type FormEvent } from "react";

export function JoinLeagueModal({
  isOpen,
  isSubmitting,
  error,
  onClose,
  onSubmit,
}: {
  readonly isOpen: boolean;
  readonly isSubmitting: boolean;
  readonly error: string | null;
  readonly onClose: () => void;
  readonly onSubmit: (inviteCode: string) => Promise<void>;
}) {
  const [inviteCode, setInviteCode] = useState("");

  if (!isOpen) return null;

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    await onSubmit(inviteCode.trim());
    setInviteCode("");
  }

  return (
    <div className="modal-backdrop" role="presentation">
      <div
        className="modal-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="join-league-title"
      >
        <div className="modal-header">
          <h2 id="join-league-title">Join private league</h2>
          <button
            className="button secondary compact"
            type="button"
            onClick={onClose}
          >
            Close
          </button>
        </div>
        <form className="stacked-form" onSubmit={(event) => void submit(event)}>
          <label>
            Invite code
            <input
              aria-label="Invite code"
              placeholder="Enter invite code"
              value={inviteCode}
              onChange={(event) =>
                setInviteCode(event.target.value.toUpperCase())
              }
            />
          </label>
          {error ? (
            <p className="form-error" role="alert">
              {error}
            </p>
          ) : null}
          <button className="button" disabled={isSubmitting} type="submit">
            {isSubmitting ? "Joining..." : "Join league"}
          </button>
        </form>
      </div>
    </div>
  );
}
