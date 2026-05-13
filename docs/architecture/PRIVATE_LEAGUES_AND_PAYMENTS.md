# SportsRush — Private Leagues and Payments

## Overview

Private leagues are a custom feature layered on top of the core Football Pool plugin. They are implemented entirely in the `private_league_rankings` plugin (v3.0, authored by Bperrow) and use **WooCommerce** with the **Stripe payment gateway** to handle paid entry. The system supports both free and paid private mini-leagues that run alongside the main public pool.

---

## How Private Leagues Currently Work

### Concept

A private league is a named competition (`custom_competitions` table) that a subset of users belong to. Within a private league, participants compete using the same predictions they make in the main pool — there are no separate predictions. Rankings within the league are filtered views of the global scoring data.

### Data Model

**`custom_competitions` table:**
| Column | Description |
|--------|------------|
| `id` | Unique league ID |
| `name` | Display name of the league |
| `is_paid` | 1 = requires payment to join |
| `wc_product_id` | WooCommerce product ID (for paid leagues) |
| Other columns | Assumed: `description`, `logo`, `created_by`, `max_members`, etc. |

**`custom_competition_users` table:**
| Column | Description |
|--------|------------|
| `user_id` | WordPress user ID |
| `custom_competition_id` | FK to `custom_competitions.id` |
| `created_at` | Timestamp of when user joined |

**`wpkl_usermeta`:**

- `sr_league_paid_<league_id> = 1` — stored per user per paid league to record payment entitlement. This survives league member table changes and acts as the authoritative paid access record.

### Access Control Rules

```php
function sr_user_can_access_league($user_id, $league):
  if admin → always allow
  if already a member → allow
  if paid league → allow if sr_user_has_paid_entitlement($user_id, $league_id)
  if free private league → deny (must be explicitly added as member)
```

Admins bypass all restrictions. Free league members must be explicitly added by an admin. Paid league members are auto-added on successful payment.

### User-Facing Flow

1. **Browse leagues:** Users visit `/join-leagues/` (shortcode: `[join_league_catalogue]` or similar) to see available private leagues.
2. **Join free league:** A "Join" button directly inserts the user into `custom_competition_users`.
3. **Join paid league:** A "Buy" button (WooCommerce add-to-cart with `sr_go_checkout=1`) takes the user to checkout immediately.
4. **After joining:** Users visit `/private-leagues/` (shortcode: `[private_league_rankings]`) and see a competition selector showing all leagues they belong to.
5. **View rankings:** Rankings for the selected league are displayed — this is a filtered view derived from the main pool's prediction and match data.

### Rankings Within Private Leagues

The private league rankings page queries the main `pool_wpkl_predictions` and `pool_wpkl_matches` tables, but filters participants to only those in `custom_competition_users` for the selected league. This means private league rankings automatically update whenever the main pool scores are recalculated.

---

## Stripe / Payment Integration

### Stack

- **WooCommerce** (core e-commerce) v8+ (inferred from Stripe gateway requirements)
- **WooCommerce Stripe Payment Gateway** v10.3.1 — handles card payments via Stripe's API

### Payment Flow

```
1. User clicks "Join paid league" button
   ↓ (sr_build_one_click_checkout_url)
   Add to cart → immediate redirect to WooCommerce checkout
   (sr_go_checkout=1 in URL triggers redirect filter)

2. Cart add fires: woocommerce_add_to_cart hook
   → WC()->session->set('sr_league_id', $league_id)
   (league ID stored in WooCommerce session)

3. User completes checkout (Stripe card payment)
   → woocommerce_checkout_create_order
   → Order meta: sr_league_id = <league_id>
   → Session cleared

4. Stripe processes payment → WooCommerce webhook
   → woocommerce_order_status_processing (or 'completed')
   → sr_handle_wc_order_paid($order_id) fires

5. sr_handle_wc_order_paid():
   → Reads $order->get_meta('sr_league_id')
   → Reads $order->get_user_id()
   → Calls sr_grant_league_access($user_id, $league_id, mark_paid_entitlement: true)

6. sr_grant_league_access():
   → update_user_meta($user_id, 'sr_league_paid_<league_id>', 1)
   → INSERT INTO custom_competition_users (user_id, custom_competition_id, created_at)
      ON DUPLICATE KEY → (implicit: already a member, skip)

7. User redirected to league page (woocommerce_thankyou hook)
```

### WooCommerce Product Setup

Each paid league requires a corresponding WooCommerce product (a simple product representing the league entry fee). The product ID is stored in `custom_competitions.wc_product_id`. Admins must create the product in WooCommerce and link it to the league via the Private League Manager admin UI.

---

## League / User Relationships

| Relationship               | How managed                                     |
| -------------------------- | ----------------------------------------------- |
| User → public pool leagues | `pool_wpkl_league_users` (Football Pool plugin) |
| User → private leagues     | `custom_competition_users` (custom plugin)      |
| Paid entitlement record    | `wpkl_usermeta: sr_league_paid_<id>`            |
| Admin can override access  | Via admin manage-users UI                       |

A user can belong to multiple private leagues simultaneously. The `/private-leagues/` page shows all of their leagues in a selector dropdown.

---

## Admin Management

The `private_league_rankings` plugin registers two admin pages:

### 1. Private League Manager (`admin.php?page=private-league-manager`)

- Create, edit, and delete private leagues
- Set name, paid/free status, WooCommerce product ID, and other league properties
- Delete a league (presumably cascades to `custom_competition_users`)

### 2. Private League User Manager (`admin.php?page=private-league-manager-users&league=<id>`)

- View all members of a specific league
- Manually add or remove users
- Useful for free leagues or for handling edge cases (refunds, comp entries, etc.)

---

## Branding / Logo Functionality

The codebase references league logos/branding in the admin UI but the full implementation detail is in parts of `private_league_rankings.php` not fully inspected. Based on the data model, it is likely that `custom_competitions` has a logo column and the admin allows image upload. Team logos in the main pool are handled via `pool_wpkl_teams.photo`. Neither system uses a CDN — images are served directly from the Hostinger server's filesystem.

---

## Security Concerns

### 1. Entitlement Stored as User Meta

Paid access entitlement is stored as `wpkl_usermeta` (`sr_league_paid_<league_id>`). While WP nonce protection guards the admin actions, this meta key is a simple integer flag. If an attacker could write to `wpkl_usermeta` (e.g. via an unpatched plugin), they could grant themselves free access to paid leagues.

### 2. WooCommerce Order ↔ League Binding via Session

The league ID is stored in the WooCommerce session during checkout. If the session is lost between add-to-cart and order completion (e.g. browser close, session expiry), the `sr_league_id` order meta will not be set and `sr_handle_wc_order_paid()` will silently fail to grant access. The user will have paid but not be enrolled. The admin would need to manually enrol them.

### 3. Double-Fire Risk

Both `woocommerce_order_status_processing` and `woocommerce_order_status_completed` are hooked to `sr_handle_wc_order_paid`. For standard Stripe payments, an order may transition through both statuses. The `sr_grant_league_access()` function uses `INSERT IGNORE` style logic for the membership row, and `update_user_meta` is idempotent, so double-firing is safe — but it's an implicit assumption that could break if the function gains side effects.

### 4. No Refund Handling

There is no webhook or handler for WooCommerce refunds or order cancellations. If a user is refunded, their `sr_league_paid_<id>` meta and `custom_competition_users` row persist — they retain access. This must be handled manually by an admin.

### 5. Product Misuse

If a user purchases the WooCommerce product directly (e.g. from a product page, not the join flow), the `sr_league_id` session variable may not be set, meaning `sr_handle_wc_order_paid` cannot determine which league to grant access to. Admins should ensure league products are not browsable in the WooCommerce shop.

### 6. No Rate Limiting on League Join

Free league joining has no rate limiting or CAPTCHA. A bot could bulk-enrol dummy accounts into free leagues, inflating member counts and polluting rankings.

### 7. No CSRF on Join Action

The free-league join flow should use WordPress nonces. If it does not (the code uses `wp_safe_redirect` but the join action handling should be verified), it would be vulnerable to CSRF attacks that enrol users in leagues without their knowledge.
