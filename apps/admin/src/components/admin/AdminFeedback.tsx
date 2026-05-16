export type AdminFeedbackState =
  | { readonly type: "success"; readonly message: string }
  | { readonly type: "error"; readonly message: string };

export function adminSuccessToast(message: string): AdminFeedbackState {
  return { type: "success", message };
}

export function adminErrorToast(message: string): AdminFeedbackState {
  return { type: "error", message };
}

export function AdminFeedback({
  feedback,
}: {
  readonly feedback: AdminFeedbackState | null;
}) {
  if (!feedback) {
    return null;
  }

  return (
    <div
      className={
        feedback.type === "success"
          ? "feedback-panel success-panel"
          : "feedback-panel error-panel"
      }
      role={feedback.type === "success" ? "status" : "alert"}
    >
      {feedback.message}
    </div>
  );
}
