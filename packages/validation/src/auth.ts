import { z } from "zod";
import { EmailSchema } from "./common";

/**
 * Password constraints.
 * Min 8: basic NIST SP 800-63B requirement.
 * Max 72: PBKDF2 / Argon2 pre-hash truncation threshold — nothing above this
 * provides additional security and could cause confusion about what was hashed.
 */
const PasswordSchema = z
  .string()
  .min(8, { message: "Password must be at least 8 characters" })
  .max(72, { message: "Password must be 72 characters or fewer" });

/**
 * Registration input.
 * email is normalised to lowercase.
 * displayName is trimmed; enforces the 2–50 character range for leaderboard display.
 */
export const RegisterSchema = z.object({
  email: EmailSchema,
  password: PasswordSchema,
  displayName: z
    .string()
    .min(2, { message: "Display name must be at least 2 characters" })
    .max(50, { message: "Display name must be 50 characters or fewer" })
    .trim(),
});

/**
 * Login input.
 * password has no min constraint here — we return a generic error regardless
 * of why authentication failed, to prevent enumeration.
 */
export const LoginSchema = z.object({
  email: EmailSchema,
  password: z
    .string()
    .min(1, { message: "Password is required" })
    .max(72, { message: "Password must be 72 characters or fewer" }),
});

/**
 * Password reset — step 1: request a reset link.
 */
export const PasswordResetRequestSchema = z.object({
  email: EmailSchema,
});

/**
 * Password reset — step 2: submit the token and choose a new password.
 */
export const PasswordResetConfirmSchema = z.object({
  token: z
    .string()
    .min(1, { message: "Reset token is required" })
    .max(512, { message: "Invalid token" }),
  newPassword: PasswordSchema,
});

/**
 * Refresh token exchange — used to obtain a new access token.
 */
export const RefreshTokenSchema = z.object({
  refreshToken: z.string().min(1, { message: "Refresh token is required" }),
});

/**
 * Display name update — rate-limited to 3 changes per 24 hours (enforced in service).
 */
export const UpdateDisplayNameSchema = z.object({
  displayName: z
    .string()
    .min(2, { message: "Display name must be at least 2 characters" })
    .max(50, { message: "Display name must be 50 characters or fewer" })
    .trim(),
});

/**
 * Change password — requires the current password for verification.
 */
export const ChangePasswordSchema = z
  .object({
    currentPassword: z
      .string()
      .min(1, { message: "Current password is required" }),
    newPassword: PasswordSchema,
  })
  .refine((data) => data.currentPassword !== data.newPassword, {
    message: "New password must be different from current password",
    path: ["newPassword"],
  });

/**
 * Magic link — step 1: request a sign-in link via email.
 * Returns the same success response whether or not the email is registered
 * (prevents email enumeration). In non-production environments the raw token
 * is included in the response body for testing.
 */
export const MagicLinkRequestSchema = z.object({
  email: EmailSchema,
});

/**
 * Magic link — step 2: consume the one-time token from the email link.
 */
export const MagicLinkConsumeSchema = z.object({
  token: z
    .string()
    .min(1, { message: "Token is required" })
    .max(512, { message: "Invalid token" }),
});

export type RegisterInput = z.infer<typeof RegisterSchema>;
export type LoginInput = z.infer<typeof LoginSchema>;
export type PasswordResetRequestInput = z.infer<
  typeof PasswordResetRequestSchema
>;
export type PasswordResetConfirmInput = z.infer<
  typeof PasswordResetConfirmSchema
>;
export type RefreshTokenInput = z.infer<typeof RefreshTokenSchema>;
export type UpdateDisplayNameInput = z.infer<typeof UpdateDisplayNameSchema>;
export type ChangePasswordInput = z.infer<typeof ChangePasswordSchema>;
export type MagicLinkRequestInput = z.infer<typeof MagicLinkRequestSchema>;
export type MagicLinkConsumeInput = z.infer<typeof MagicLinkConsumeSchema>;
