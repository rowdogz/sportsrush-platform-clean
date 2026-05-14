import type { UUID, Timestamp } from "./common";

/**
 * Whether a private league requires payment to join.
 *
 * - 'free' — members are added manually by an admin
 * - 'paid' — members join via Stripe; auto-enrolled on successful payment
 */
export type LeagueType = "free" | "paid";

/**
 * A private mini-league. Members compete using their existing competition
 * predictions — there are no separate predictions for league play.
 * Rankings within a league are a filtered view of the global match_scores.
 *
 * Owned by the Private Leagues domain.
 * In legacy WordPress: custom_competitions table.
 *
 * See SPORTSRUSH_CANONICAL_RULES.md §2.5 (Private League Filters)
 * See SPORTSRUSH_2_DOMAIN_MODEL.md — Domain 7 (Private Leagues)
 */
export type PrivateLeague = {
  readonly id: UUID;
  readonly name: string;
  readonly competitionId: UUID; // the competition this league is scoped to
  readonly description: string | null;
  readonly logoUrl: string | null;
  readonly type: LeagueType;
  readonly maxMembers: number | null; // null = unlimited
  /**
   * Stripe product ID for paid leagues.
   * In legacy WordPress this was wc_product_id (WooCommerce).
   * Set during migration by mapping WC products to Stripe products.
   */
  readonly stripeProductId: string | null;
  readonly createdBy: UUID;
  readonly createdAt: Timestamp;
  readonly updatedAt: Timestamp;
};

/**
 * How a user gained membership to a private league.
 *
 * - 'admin'     — manually added by an administrator
 * - 'payment'   — auto-enrolled after successful Stripe payment
 * - 'migration' — carried over from the legacy WordPress custom_competition_users table
 */
export type MembershipGrantedBy = "admin" | "payment" | "migration";

/**
 * A user's membership in a private league.
 * Owned by the Private Leagues domain.
 * In legacy WordPress: custom_competition_users table.
 */
export type LeagueMembership = {
  readonly id: UUID;
  readonly leagueId: UUID;
  readonly userId: UUID;
  readonly joinedAt: Timestamp;
  readonly accessGrantedBy: MembershipGrantedBy;
  readonly paymentEventId: UUID | null; // populated when accessGrantedBy = 'payment'
};

/**
 * Payment entitlement record for a paid league.
 *
 * Records that a user has paid for access, independently of the membership row.
 * If the membership row is ever removed (e.g. by admin error), this record
 * proves payment occurred and is the source of truth for access rights.
 *
 * In legacy WordPress: wpkl_usermeta key 'sr_league_paid_<league_id>'
 * See PRIVATE_LEAGUES_AND_PAYMENTS.md (Security Concerns §1)
 */
export type PaymentEntitlement = {
  readonly id: UUID;
  readonly userId: UUID;
  readonly leagueId: UUID;
  readonly paymentEventId: UUID;
  readonly grantedAt: Timestamp;
  readonly revokedAt: Timestamp | null; // set if entitlement is revoked (e.g. refund)
};
