<?php
/*
Plugin Name: Private League Rankings
Description: Private leagues with paid/free support + WooCommerce auto-enrolment + Join League catalogue.
Version: 3.0
Author: Bperrow
*/

if (!defined('ABSPATH')) exit;

/* =========================================================
   CONFIG
   ========================================================= */

function sr_private_leagues_page_url($league_id = null) {
    $base = home_url('/private-leagues/');
    if ($league_id) return $base . '?competition=' . (int)$league_id;
    return $base;
}

function sr_join_leagues_page_url() {
    return home_url('/join-leagues/');
}

/* =========================================================
   HELPERS: membership, counts, entitlements, access
   ========================================================= */

function sr_is_league_paid($league) {
    if (is_object($league) && isset($league->is_paid)) return (int)$league->is_paid === 1;
    if (is_numeric($league)) {
        global $wpdb;
        $is_paid = $wpdb->get_var($wpdb->prepare("SELECT is_paid FROM custom_competitions WHERE id = %d", (int)$league));
        return (int)$is_paid === 1;
    }
    return false;
}

function sr_user_is_league_member($user_id, $league_id) {
    global $wpdb;
    $count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM custom_competition_users
        WHERE user_id = %d AND custom_competition_id = %d
    ", (int)$user_id, (int)$league_id));
    return (int)$count > 0;
}

function sr_user_has_paid_entitlement($user_id, $league_id) {
    return (int)get_user_meta((int)$user_id, 'sr_league_paid_' . (int)$league_id, true) === 1;
}

function sr_league_active_user_count($league_id) {
    global $wpdb;
    $count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM custom_competition_users
        WHERE custom_competition_id = %d
    ", (int)$league_id));
    return (int)$count;
}

// Access rules:
// - Admin always
// - Free league => accessible IF you’re a member (private league concept), OR if it’s join flow we’ll allow joining
// - Paid league => access if member OR paid entitlement
function sr_user_can_access_league($user_id, $league) {
    if (current_user_can('manage_options')) return true;

    $league_id = is_object($league) ? (int)$league->id : (int)$league;

    // If already a member: always can view
    if (sr_user_is_league_member($user_id, $league_id)) return true;

    // Paid entitlement allows access (even if membership missing; we’ll add membership on grant)
    if (sr_is_league_paid($league)) {
        return sr_user_has_paid_entitlement($user_id, $league_id);
    }

    // Free private league: access requires membership
    return false;
}

function sr_grant_league_access($user_id, $league_id, $mark_paid_entitlement = false) {
    global $wpdb;

    $user_id = (int)$user_id;
    $league_id = (int)$league_id;

    if ($user_id <= 0 || $league_id <= 0) return;

    if ($mark_paid_entitlement) {
        update_user_meta($user_id, 'sr_league_paid_' . $league_id, 1);
    }

    if (!sr_user_is_league_member($user_id, $league_id)) {
        $wpdb->insert('custom_competition_users', [
            'user_id' => $user_id,
            'custom_competition_id' => $league_id,
            'created_at' => current_time('mysql'),
        ], ['%d','%d','%s']);
    }
}

/* =========================================================
   WOO: One-click checkout (add-to-cart -> checkout)
   ========================================================= */

add_action('plugins_loaded', function () {
    if (!class_exists('WooCommerce')) return;

    add_filter('woocommerce_add_to_cart_redirect', function ($url) {
        if (isset($_REQUEST['sr_go_checkout']) && (int)$_REQUEST['sr_go_checkout'] === 1) {
            return wc_get_checkout_url();
        }
        return $url;
    });

    // Capture league id into session during add-to-cart
    add_action('woocommerce_add_to_cart', function ($cart_item_key, $product_id) {
        if (isset($_REQUEST['sr_league_id']) && is_numeric($_REQUEST['sr_league_id'])) {
            WC()->session->set('sr_league_id', (int)$_REQUEST['sr_league_id']);
        }
    }, 10, 2);

    // Store league id on the order
    add_action('woocommerce_checkout_create_order', function ($order, $data) {
        $league_id = WC()->session ? (int)WC()->session->get('sr_league_id') : 0;
        if ($league_id > 0) {
            $order->update_meta_data('sr_league_id', $league_id);
        }
    }, 20, 2);

    // Clear session key after order processed
    add_action('woocommerce_checkout_order_processed', function () {
        if (WC()->session) WC()->session->__unset('sr_league_id');
    }, 20);

    // Auto-enrol on payment
    add_action('woocommerce_order_status_processing', 'sr_handle_wc_order_paid');
    add_action('woocommerce_order_status_completed', 'sr_handle_wc_order_paid');

    // Redirect to league after purchase
    add_action('woocommerce_thankyou', 'sr_redirect_to_league_after_purchase', 10);
});

function sr_build_one_click_checkout_url($league_id, $wc_product_id) {
    if (!class_exists('WooCommerce')) return '';
    return add_query_arg(
        [
            'add-to-cart'    => (int)$wc_product_id,
            'sr_go_checkout' => 1,
            'sr_league_id'   => (int)$league_id,
        ],
        home_url('/') // reliable add-to-cart base
    );
}

function sr_handle_wc_order_paid($order_id) {
    if (!$order_id || !function_exists('wc_get_order')) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    $user_id = (int)$order->get_user_id();
    if ($user_id <= 0) return;

    global $wpdb;

    foreach ($order->get_items() as $item) {
        $product_id = (int)$item->get_product_id();
        if ($product_id <= 0) continue;

        $league_id = $wpdb->get_var($wpdb->prepare("
            SELECT id
            FROM custom_competitions
            WHERE wc_product_id = %d AND is_paid = 1
            LIMIT 1
        ", $product_id));

        if ($league_id) {
            // Mark entitlement + add membership
            sr_grant_league_access($user_id, (int)$league_id, true);

            // Set league id on order (for thankyou redirect)
            $order->update_meta_data('sr_league_id', (int)$league_id);
        }
    }

    $order->save();
}

function sr_redirect_to_league_after_purchase($order_id) {
    if (!$order_id || !function_exists('wc_get_order')) return;

    // Prevent loops
    if (isset($_GET['sr_no_redirect']) && (int)$_GET['sr_no_redirect'] === 1) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    // Only redirect logged-in customers (you can extend to guest later)
    $user_id = (int)$order->get_user_id();
    if ($user_id <= 0) return;

    $already = (int)$order->get_meta('_sr_redirected', true);
    if ($already === 1) return;

    $league_id = (int)$order->get_meta('sr_league_id', true);
    if ($league_id <= 0) return;

    $order->update_meta_data('_sr_redirected', 1);
    $order->save();

    $target = sr_private_leagues_page_url($league_id);
    if (!headers_sent()) {
        wp_safe_redirect($target);
        exit;
    }
}

/* =========================================================
   SHORTCODE: Members area (Your leagues only)
   ========================================================= */

add_shortcode('private_league_rankings', 'render_private_league_rankings');

function render_private_league_rankings() {
    global $wpdb;

    if (!is_user_logged_in()) {
        return "<p>Please log in to view private leagues.</p>";
    }

    $user_id = get_current_user_id();

    // Only leagues you are a member of
    $private_leagues = $wpdb->get_results($wpdb->prepare("
        SELECT
            cc.id, cc.name, cc.matchtype_id,
            cc.logo_url, cc.banner_url,
            cc.is_paid, cc.price_gbp, cc.wc_product_id, cc.prize_gbp,
            mt.name AS matchtype_name
        FROM custom_competitions cc
        JOIN custom_competition_users ccu ON cc.id = ccu.custom_competition_id
        LEFT JOIN pool_wpkl_matchtypes mt ON cc.matchtype_id = mt.id
        WHERE ccu.user_id = %d
        ORDER BY cc.created_at DESC
    ", $user_id));

    // If no leagues: show CTA to catalogue
    if (empty($private_leagues)) {
        $join_url = sr_join_leagues_page_url();
        return '<p>You are not part of any private leagues yet.</p>'
            . '<p><a class="button button-primary" href="' . esc_url($join_url) . '">Browse leagues to join</a></p>';
    }

    $selected_id = isset($_GET['competition']) ? (int)$_GET['competition'] : (int)$private_leagues[0]->id;

    $selected = null;
    foreach ($private_leagues as $comp) {
        if ((int)$comp->id === $selected_id) { $selected = $comp; break; }
    }
    if (!$selected) return "<p>Invalid private league selected.</p>";

    // Access check (covers edge case: membership exists but paid league not entitled – you may want to enforce paid even if added manually)
    // Your preference earlier was: if already a member, show the table. We do that.
    $matchtype_id = (int)$selected->matchtype_id;

    // Users in league
    $user_ids = $wpdb->get_col($wpdb->prepare("
        SELECT user_id FROM custom_competition_users WHERE custom_competition_id = %d
    ", (int)$selected_id));
    if (empty($user_ids)) return "<p>No users in this league yet.</p>";
    $user_ids_csv = implode(',', array_map('intval', $user_ids));

    // Rankings
    $rankings_query = "
        SELECT 
            DENSE_RANK() OVER (ORDER BY total_points DESC) AS user_rank,
            user_id,
            user_name,
            total_points,
            current_month_points
        FROM (
            SELECT 
                u.ID AS user_id,
                u.display_name AS user_name,

                SUM(
                    CASE WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 50 ELSE 0 END
                    +
                    CASE
                        WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 0
                        WHEN ((m.home_score > m.away_score AND p.home_score > p.away_score) OR 
                              (m.home_score < m.away_score AND p.home_score < p.away_score) OR 
                              (m.home_score = m.away_score AND p.home_score = p.away_score)) THEN 20
                        ELSE 0
                    END
                    +
                    CASE WHEN m.home_score = p.home_score THEN 10 ELSE 0 END
                    +
                    CASE WHEN m.away_score = p.away_score THEN 10 ELSE 0 END
                    +
                    CASE
                        WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 0
                        WHEN ((m.home_score > m.away_score AND p.home_score > p.away_score) OR 
                              (m.home_score < m.away_score AND p.home_score < p.away_score) OR 
                              (m.home_score = m.away_score AND p.home_score = p.away_score))
                        AND (GREATEST(m.home_score, m.away_score) - LEAST(m.home_score, m.away_score)) =
                            (GREATEST(p.home_score, p.away_score) - LEAST(p.home_score, p.away_score)) THEN 20
                        ELSE 0
                    END
                ) AS total_points,

                SUM(
                    CASE
                        WHEN MONTH(m.play_date) = MONTH(NOW()) AND YEAR(m.play_date) = YEAR(NOW()) THEN
                            (
                                CASE WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 50 ELSE 0 END
                                +
                                CASE
                                    WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 0
                                    WHEN ((m.home_score > m.away_score AND p.home_score > p.away_score) OR 
                                          (m.home_score < m.away_score AND p.home_score < p.away_score) OR 
                                          (m.home_score = m.away_score AND p.home_score = p.away_score)) THEN 20
                                    ELSE 0
                                END
                                +
                                CASE WHEN m.home_score = p.home_score THEN 10 ELSE 0 END
                                +
                                CASE WHEN m.away_score = p.away_score THEN 10 ELSE 0 END
                                +
                                CASE
                                    WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 0
                                    WHEN ((m.home_score > m.away_score AND p.home_score > p.away_score) OR 
                                          (m.home_score < m.away_score AND p.home_score < p.away_score) OR 
                                          (m.home_score = m.away_score AND p.home_score = p.away_score))
                                    AND (GREATEST(m.home_score, m.away_score) - LEAST(m.home_score, m.away_score)) =
                                        (GREATEST(p.home_score, p.away_score) - LEAST(p.home_score, p.away_score)) THEN 20
                                    ELSE 0
                                END
                            )
                        ELSE 0
                    END
                ) AS current_month_points

            FROM wpkl_users u
            JOIN pool_wpkl_predictions p ON p.user_id = u.ID
            JOIN pool_wpkl_matches m ON p.match_id = m.id
            WHERE m.home_score IS NOT NULL
              AND m.away_score IS NOT NULL
              AND m.matchtype_id = $matchtype_id
              AND u.ID IN ($user_ids_csv)
            GROUP BY u.ID
        ) AS sub
    ";

    $rankings = $wpdb->get_results($rankings_query);

    $join_url = sr_join_leagues_page_url();

    ob_start();
    ?>

    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <form method="get" class="football-pool-filter-form" style="margin:0;">
            <label for="competition">Your leagues:</label>
            <select name="competition" id="competition" onchange="this.form.submit();">
                <?php foreach ($private_leagues as $league): ?>
                    <option value="<?php echo esc_attr($league->id); ?>" <?php selected($selected_id, $league->id); ?>>
                        <?php echo esc_html($league->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <a class="button" href="<?php echo esc_url($join_url); ?>">Browse leagues to join</a>
    </div>

    <h3 style="margin-top:16px;"><?php echo esc_html($selected->name); ?> Leaderboard</h3>
    <p style="font-style: italic; color: #666;">Linked to: <?php echo esc_html($selected->matchtype_name); ?></p>

    <?php if (!empty($selected->banner_url)) : ?>
        <div style="margin: 20px 0;">
            <img src="<?php echo esc_url($selected->banner_url); ?>" alt="Banner"
                 style="width: 100%; max-height: 220px; object-fit: cover; border-radius: 10px;">
        </div>
    <?php endif; ?>

    <?php if (!empty($selected->logo_url)) : ?>
        <div style="margin-bottom: 20px; text-align: center;">
            <img src="<?php echo esc_url($selected->logo_url); ?>" alt="Logo" style="max-height: 110px;">
        </div>
    <?php endif; ?>

    <?php if (!empty($rankings)): ?>
        <table class="football-pool-rankings-table" style="width:100%;border-collapse:collapse;margin-top: 20px;">
            <thead>
            <tr style="background-color:#f4f4f4;">
                <th style="padding:8px;border:1px solid #ddd;">Rank</th>
                <th style="padding:8px;border:1px solid #ddd;">User</th>
                <th style="padding:8px;border:1px solid #ddd;">Total Points</th>
                <th style="padding:8px;border:1px solid #ddd;"><?php echo esc_html(date('F')); ?> Points</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rankings as $r): ?>
                <tr style="<?php echo ((int)$r->user_id === (int)$user_id) ? 'background-color:#dff0d8;' : ''; ?>">
                    <td style="padding:8px;border:1px solid #ddd;"><?php echo esc_html($r->user_rank); ?></td>
                    <td style="padding:8px;border:1px solid #ddd;"><?php echo esc_html($r->user_name); ?></td>
                    <td style="padding:8px;border:1px solid #ddd;"><?php echo esc_html($r->total_points); ?></td>
                    <td style="padding:8px;border:1px solid #ddd;"><?php echo esc_html($r->current_month_points); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No predictions available yet for this league.</p>
    <?php endif; ?>

    <?php
    return ob_get_clean();
}

/* =========================================================
   SHORTCODE: Join League catalogue
   Page slug suggestion: /join-leagues/
   ========================================================= */

add_shortcode('sr_join_leagues', function () {
    global $wpdb;

    if (!is_user_logged_in()) {
        return "<p>Please log in to browse and join private leagues.</p>";
    }

    $user_id = get_current_user_id();

    // Handle FREE join action
    if (isset($_GET['sr_join_free'], $_GET['league'], $_GET['_wpnonce']) && is_numeric($_GET['league'])) {
        $league_id = (int)$_GET['league'];

        if (wp_verify_nonce($_GET['_wpnonce'], 'sr_join_free_' . $league_id)) {
            $league = $wpdb->get_row($wpdb->prepare("SELECT * FROM custom_competitions WHERE id = %d LIMIT 1", $league_id));
            if ($league && (int)$league->is_private === 1 && (int)$league->is_paid === 0) {
                sr_grant_league_access($user_id, $league_id, false);
                wp_safe_redirect(sr_private_leagues_page_url($league_id));
                exit;
            }
        }
    }

    // Fetch leagues available to join (private leagues)
    $leagues = $wpdb->get_results("
        SELECT
            cc.id, cc.name, cc.matchtype_id,
            cc.is_private, cc.is_paid, cc.price_gbp, cc.wc_product_id, cc.prize_gbp,
            cc.logo_url, cc.banner_url,
            mt.name AS matchtype_name
        FROM custom_competitions cc
        LEFT JOIN pool_wpkl_matchtypes mt ON cc.matchtype_id = mt.id
        WHERE cc.is_private = 1
        ORDER BY cc.created_at DESC
    ");

    if (empty($leagues)) {
        return "<p>No leagues are available to join yet.</p>";
    }

    ob_start();
    ?>
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <h2 style="margin:0;">Join a Private League</h2>
        <a class="button" href="<?php echo esc_url(sr_private_leagues_page_url()); ?>">Go to your leagues</a>
    </div>

    <p style="color:#666;margin-top:8px;">
        Browse leagues below. Paid leagues unlock automatically after checkout; free leagues join instantly.
    </p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-top:16px;">
        <?php foreach ($leagues as $league): ?>
            <?php
            $league_id = (int)$league->id;
            $is_paid = (int)$league->is_paid === 1;
            $is_member = sr_user_is_league_member($user_id, $league_id);
            $has_entitlement = sr_user_has_paid_entitlement($user_id, $league_id);

            // If entitled but not a member (should be rare), add membership automatically for a smoother experience
            if ($has_entitlement && !$is_member) {
                sr_grant_league_access($user_id, $league_id, false);
                $is_member = true;
            }

            $active_users = sr_league_active_user_count($league_id);

            $price = '£' . number_format((float)($league->price_gbp ?? 0), 2);
            $prize = (isset($league->prize_gbp) && $league->prize_gbp !== null && $league->prize_gbp !== '')
                ? '£' . number_format((float)$league->prize_gbp, 2)
                : null;

            $cta_html = '';
            if ($is_member) {
                $cta_html = '<a class="button button-primary" href="' . esc_url(sr_private_leagues_page_url($league_id)) . '">Go to league</a>';
            } else {
                if ($is_paid) {
                    if (!empty($league->wc_product_id)) {
                        $one_click = sr_build_one_click_checkout_url($league_id, (int)$league->wc_product_id);
                        $cta_html = '<a class="button button-primary" href="' . esc_url($one_click) . '">Pay &amp; Join</a>';
                    } else {
                        $cta_html = '<span style="color:#b32d2e;">Paid league (no product linked yet)</span>';
                    }
                } else {
                    $join_free_url = wp_nonce_url(
                        add_query_arg(['sr_join_free' => 1, 'league' => $league_id], sr_join_leagues_page_url()),
                        'sr_join_free_' . $league_id
                    );
                    $cta_html = '<a class="button button-primary" href="' . esc_url($join_free_url) . '">Join free</a>';
                }
            }
            ?>

            <div style="border:1px solid #e5e5e5;border-radius:12px;overflow:hidden;background:#fff;">
                <?php if (!empty($league->banner_url)) : ?>
                    <img src="<?php echo esc_url($league->banner_url); ?>" alt="Banner"
                         style="width:100%;height:120px;object-fit:cover;">
                <?php endif; ?>

                <div style="padding:14px;">
                    <div style="display:flex;gap:10px;align-items:center;">
                        <?php if (!empty($league->logo_url)) : ?>
                            <img src="<?php echo esc_url($league->logo_url); ?>" alt="Logo" style="width:40px;height:40px;object-fit:contain;">
                        <?php endif; ?>
                        <div>
                            <div style="font-weight:700;"><?php echo esc_html($league->name); ?></div>
                            <div style="color:#666;font-size:13px;">
                                Linked to: <?php echo esc_html($league->matchtype_name ?: 'Unknown'); ?>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;color:#444;font-size:13px;">
                        <span><strong><?php echo (int)$active_users; ?></strong> active users</span>
                        <?php if ($prize !== null): ?>
                            <span>Prize: <strong><?php echo esc_html($prize); ?></strong></span>
                        <?php endif; ?>
                        <?php if ($is_paid): ?>
                            <span>Entry: <strong><?php echo esc_html($price); ?></strong></span>
                        <?php else: ?>
                            <span>Entry: <strong>Free</strong></span>
                        <?php endif; ?>
                    </div>

                    <div style="margin-top:12px;">
                        <?php echo $cta_html; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php
    return ob_get_clean();
});

/* =========================================================
   ADMIN UI: leagues list + create/edit (adds prize_gbp)
   ========================================================= */

add_action('admin_menu', 'sr_register_private_league_admin');

function sr_register_private_league_admin() {
    add_menu_page(
        'Private Leagues',
        'Private Leagues',
        'manage_options',
        'private-league-manager',
        'sr_render_private_league_admin',
        'dashicons-groups',
        80
    );

    add_submenu_page(
        null,
        'Manage Users in League',
        'Manage Users',
        'manage_options',
        'private-league-manager-users',
        'sr_render_private_league_user_manager'
    );
}

add_action('admin_head', function () {
    echo '<style>
      .button-danger { background:#b32d2e !important; border-color:#b32d2e !important; color:#fff !important; }
      .button-danger:hover { background:#8a1f20 !important; border-color:#8a1f20 !important; color:#fff !important; }
    </style>';
});

function sr_render_private_league_admin() {
    global $wpdb;

    if (!current_user_can('manage_options')) wp_die('You do not have permission to access this page.');

    // Delete league
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $delete_id = (int)$_GET['delete'];

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'sr_delete_league_' . $delete_id)) {
            wp_die('Security check failed.');
        }

        $wpdb->delete('custom_competition_users', ['custom_competition_id' => $delete_id], ['%d']);
        $wpdb->delete('custom_competitions', ['id' => $delete_id], ['%d']);

        echo '<div class="updated notice"><p>League deleted.</p></div>';
    }

    // Update league
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_league_id'])) {
        check_admin_referer('sr_update_league', 'sr_league_nonce');

        $update_id = (int)$_POST['update_league_id'];

        $logo_url   = esc_url_raw($_POST['logo_url'] ?? '');
        $banner_url = esc_url_raw($_POST['banner_url'] ?? '');

        $is_private = isset($_POST['is_private']) ? 1 : 0;
        $is_paid    = isset($_POST['is_paid']) ? 1 : 0;

        $price_gbp  = (isset($_POST['price_gbp']) && $_POST['price_gbp'] !== '') ? (float)$_POST['price_gbp'] : 0.00;
        $prize_gbp  = (isset($_POST['prize_gbp']) && $_POST['prize_gbp'] !== '') ? (float)$_POST['prize_gbp'] : null;

        $wc_product_id = (isset($_POST['wc_product_id']) && $_POST['wc_product_id'] !== '') ? (int)$_POST['wc_product_id'] : null;

        if (!$is_paid) {
            $price_gbp = 0.00;
            $wc_product_id = null; // optional: clear link if not paid
        }

        $wpdb->update('custom_competitions', [
            'is_private'   => $is_private,
            'is_paid'      => $is_paid,
            'price_gbp'    => $price_gbp,
            'prize_gbp'    => $prize_gbp,
            'wc_product_id'=> $wc_product_id,
            'logo_url'     => $logo_url,
            'banner_url'   => $banner_url,
        ], ['id' => $update_id]);

        echo '<div class="updated notice"><p>League updated.</p></div>';
    }

    // Create league
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['league_name'], $_POST['matchtype_id'])
        && empty($_POST['update_league_id'])) {

        check_admin_referer('sr_create_league', 'sr_league_nonce');

        $name         = sanitize_text_field($_POST['league_name']);
        $matchtype_id = (int)$_POST['matchtype_id'];

        $is_private = isset($_POST['is_private']) ? 1 : 0;
        $is_paid    = isset($_POST['is_paid']) ? 1 : 0;

        $price_gbp  = (isset($_POST['price_gbp']) && $_POST['price_gbp'] !== '') ? (float)$_POST['price_gbp'] : 0.00;
        $prize_gbp  = (isset($_POST['prize_gbp']) && $_POST['prize_gbp'] !== '') ? (float)$_POST['prize_gbp'] : null;

        $wc_product_id = (isset($_POST['wc_product_id']) && $_POST['wc_product_id'] !== '') ? (int)$_POST['wc_product_id'] : null;

        $logo_url   = esc_url_raw($_POST['logo_url'] ?? '');
        $banner_url = esc_url_raw($_POST['banner_url'] ?? '');

        if (!$is_paid) {
            $price_gbp = 0.00;
            $wc_product_id = null;
        }

        $wpdb->insert('custom_competitions', [
            'name'        => $name,
            'matchtype_id'=> $matchtype_id,
            'is_private'  => $is_private,
            'is_paid'     => $is_paid,
            'price_gbp'   => $price_gbp,
            'prize_gbp'   => $prize_gbp,
            'wc_product_id'=> $wc_product_id,
            'logo_url'    => $logo_url,
            'banner_url'  => $banner_url,
            'created_at'  => current_time('mysql')
        ]);

        echo '<div class="updated notice"><p>League created successfully.</p></div>';
    }

    // Fetch all leagues
    $leagues = $wpdb->get_results("
        SELECT
            cc.id, cc.name, cc.matchtype_id, cc.is_private,
            cc.is_paid, cc.price_gbp, cc.prize_gbp, cc.wc_product_id,
            cc.logo_url, cc.banner_url, cc.created_at,
            mt.name AS matchtype_name
        FROM custom_competitions cc
        LEFT JOIN pool_wpkl_matchtypes mt ON cc.matchtype_id = mt.id
        ORDER BY cc.created_at DESC
    ");

    ?>
    <div class="wrap">
        <h1>Private League Manager</h1>

        <?php if (!empty($leagues)): ?>
            <table class="wp-list-table widefat striped">
                <thead>
                <tr>
                    <th>League Name</th>
                    <th>Linked Competition</th>
                    <th>Private?</th>
                    <th>Paid?</th>
                    <th>Entry</th>
                    <th>Prize</th>
                    <th>WC Product</th>
                    <th>Active Users</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($leagues as $league): ?>
                    <?php $active_users = sr_league_active_user_count((int)$league->id); ?>
                    <tr>
                        <td><?php echo esc_html($league->name); ?></td>
                        <td><?php echo esc_html($league->matchtype_name ?: 'Unknown'); ?></td>
                        <td><?php echo !empty($league->is_private) ? 'Yes' : 'No'; ?></td>
                        <td><?php echo !empty($league->is_paid) ? 'Yes' : 'No'; ?></td>
                        <td><?php echo !empty($league->is_paid) ? '£' . number_format((float)$league->price_gbp, 2) : '-'; ?></td>
                        <td><?php echo ($league->prize_gbp !== null && $league->prize_gbp !== '') ? '£' . number_format((float)$league->prize_gbp, 2) : '-'; ?></td>
                        <td><?php echo !empty($league->wc_product_id) ? (int)$league->wc_product_id : '-'; ?></td>
                        <td><?php echo (int)$active_users; ?></td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=private-league-manager&edit=' . (int)$league->id)); ?>" class="button">Edit</a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=private-league-manager-users&league=' . (int)$league->id)); ?>" class="button">Manage Users</a>

                            <?php
                            $delete_url = wp_nonce_url(
                                admin_url('admin.php?page=private-league-manager&delete=' . (int)$league->id),
                                'sr_delete_league_' . (int)$league->id
                            );
                            ?>
                            <a href="<?php echo esc_url($delete_url); ?>"
                               class="button button-danger"
                               onclick="return confirm('Delete this league? This will also remove all members from it.');">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2 style="margin-top: 40px;">Create New League</h2>
        <form method="post">
            <?php wp_nonce_field('sr_create_league', 'sr_league_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="league_name">League Name</label></th>
                    <td><input type="text" name="league_name" id="league_name" required class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="matchtype_id">Link to Existing Competition</label></th>
                    <td>
                        <select name="matchtype_id" id="matchtype_id" required>
                            <option value="">Select...</option>
                            <?php
                            $matchtypes = $wpdb->get_results("SELECT id, name FROM pool_wpkl_matchtypes ORDER BY name ASC");
                            foreach ($matchtypes as $type) {
                                echo "<option value='" . (int)$type->id . "'>" . esc_html($type->name) . "</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th><label for="is_private">Is Private?</label></th>
                    <td><input type="checkbox" name="is_private" id="is_private" value="1" checked></td>
                </tr>

                <tr>
                    <th><label for="is_paid">Is Paid?</label></th>
                    <td><input type="checkbox" name="is_paid" id="is_paid" value="1"></td>
                </tr>

                <tr>
                    <th><label for="price_gbp">Entry Price (£)</label></th>
                    <td><input type="number" step="0.01" min="0" name="price_gbp" id="price_gbp" class="regular-text" placeholder="e.g. 5.00"></td>
                </tr>

                <tr>
                    <th><label for="prize_gbp">Prize Money (£)</label></th>
                    <td><input type="number" step="0.01" min="0" name="prize_gbp" id="prize_gbp" class="regular-text" placeholder="e.g. 100.00 (leave blank for none)"></td>
                </tr>

                <tr>
                    <th><label for="wc_product_id">WooCommerce Product ID</label></th>
                    <td><input type="number" name="wc_product_id" id="wc_product_id" class="regular-text" placeholder="e.g. 840"></td>
                </tr>

                <tr>
                    <th><label for="logo_url">Logo URL</label></th>
                    <td><input type="url" name="logo_url" id="logo_url" class="regular-text" placeholder="Paste image URL here"></td>
                </tr>

                <tr>
                    <th><label for="banner_url">Banner URL</label></th>
                    <td><input type="url" name="banner_url" id="banner_url" class="regular-text" placeholder="Paste banner image URL here"></td>
                </tr>
            </table>
            <?php submit_button('Create League'); ?>
        </form>

        <?php
        // Edit form
        if (isset($_GET['edit']) && is_numeric($_GET['edit'])):
            $edit_id = (int)$_GET['edit'];
            $edit_league = $wpdb->get_row($wpdb->prepare("SELECT * FROM custom_competitions WHERE id = %d", $edit_id));
            if ($edit_league):
        ?>
            <hr style="margin: 50px 0;">
            <h2>Edit League: <?php echo esc_html($edit_league->name); ?></h2>

            <form method="post">
                <?php wp_nonce_field('sr_update_league', 'sr_league_nonce'); ?>
                <input type="hidden" name="update_league_id" value="<?php echo esc_attr($edit_league->id); ?>">

                <table class="form-table">
                    <tr>
                        <th><label for="edit_is_private">Is Private?</label></th>
                        <td><input type="checkbox" name="is_private" id="edit_is_private" value="1" <?php checked(!empty($edit_league->is_private)); ?>></td>
                    </tr>

                    <tr>
                        <th><label for="edit_is_paid">Is Paid?</label></th>
                        <td><input type="checkbox" name="is_paid" id="edit_is_paid" value="1" <?php checked(!empty($edit_league->is_paid)); ?>></td>
                    </tr>

                    <tr>
                        <th><label for="edit_price_gbp">Entry Price (£)</label></th>
                        <td><input type="number" step="0.01" min="0" name="price_gbp" id="edit_price_gbp" value="<?php echo esc_attr($edit_league->price_gbp); ?>" class="regular-text"></td>
                    </tr>

                    <tr>
                        <th><label for="edit_prize_gbp">Prize Money (£)</label></th>
                        <td><input type="number" step="0.01" min="0" name="prize_gbp" id="edit_prize_gbp" value="<?php echo esc_attr($edit_league->prize_gbp); ?>" class="regular-text"></td>
                    </tr>

                    <tr>
                        <th><label for="edit_wc_product_id">WooCommerce Product ID</label></th>
                        <td><input type="number" name="wc_product_id" id="edit_wc_product_id" value="<?php echo esc_attr($edit_league->wc_product_id); ?>" class="regular-text"></td>
                    </tr>

                    <tr>
                        <th><label for="edit_logo_url">Logo URL</label></th>
                        <td><input type="url" name="logo_url" id="edit_logo_url" value="<?php echo esc_attr($edit_league->logo_url); ?>" class="regular-text"></td>
                    </tr>

                    <tr>
                        <th><label for="edit_banner_url">Banner URL</label></th>
                        <td><input type="url" name="banner_url" id="edit_banner_url" value="<?php echo esc_attr($edit_league->banner_url); ?>" class="regular-text"></td>
                    </tr>
                </table>

                <?php submit_button('Update League'); ?>
            </form>
        <?php
            endif;
        endif;
        ?>
    </div>
    <?php
}

function sr_render_private_league_user_manager() {
    global $wpdb;

    if (!current_user_can('manage_options')) wp_die('You do not have permission to access this page.');

    if (!isset($_GET['league']) || !is_numeric($_GET['league'])) {
        echo '<div class="notice notice-error"><p>No league selected.</p></div>';
        return;
    }

    $league_id = (int)$_GET['league'];

    $league = $wpdb->get_row($wpdb->prepare("
        SELECT cc.*, mt.name AS matchtype_name
        FROM custom_competitions cc
        LEFT JOIN pool_wpkl_matchtypes mt ON cc.matchtype_id = mt.id
        WHERE cc.id = %d
    ", $league_id));

    if (!$league) {
        echo '<div class="notice notice-error"><p>League not found.</p></div>';
        return;
    }

    // Add user
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user_id'])) {
        check_admin_referer('sr_manage_league_users', 'sr_league_users_nonce');

        $user_id = (int)$_POST['add_user_id'];
        if ($user_id > 0) {
            sr_grant_league_access($user_id, $league_id, false);
            echo '<div class="updated notice"><p>User added successfully.</p></div>';
        }
    }

    // Remove user
    if (isset($_GET['remove_user']) && is_numeric($_GET['remove_user'])) {
        $remove_id = (int)$_GET['remove_user'];

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'sr_remove_league_user_' . $league_id . '_' . $remove_id)) {
            wp_die('Security check failed.');
        }

        $wpdb->delete('custom_competition_users', [
            'user_id' => $remove_id,
            'custom_competition_id' => $league_id
        ]);

        echo '<div class="updated notice"><p>User removed successfully.</p></div>';
    }

    $users_in_league = $wpdb->get_results($wpdb->prepare("
        SELECT u.ID, u.display_name, u.user_email
        FROM custom_competition_users ccu
        JOIN wpkl_users u ON ccu.user_id = u.ID
        WHERE ccu.custom_competition_id = %d
        ORDER BY u.display_name ASC
    ", $league_id));

    $all_users = $wpdb->get_results("SELECT ID, display_name FROM wpkl_users ORDER BY display_name ASC");

    ?>
    <div class="wrap">
        <h1>Manage Users for League: <?php echo esc_html($league->name); ?></h1>
        <p><strong>Linked Competition:</strong> <?php echo esc_html($league->matchtype_name ?: 'Unknown'); ?></p>

        <h2>Current Members</h2>
        <?php if (!empty($users_in_league)): ?>
            <table class="wp-list-table widefat striped">
                <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users_in_league as $user): ?>
                    <tr>
                        <td><?php echo esc_html($user->display_name); ?></td>
                        <td><?php echo esc_html($user->user_email); ?></td>
                        <td>
                            <?php
                            $remove_url = wp_nonce_url(
                                admin_url('admin.php?page=private-league-manager-users&league=' . (int)$league_id . '&remove_user=' . (int)$user->ID),
                                'sr_remove_league_user_' . (int)$league_id . '_' . (int)$user->ID
                            );
                            ?>
                            <a href="<?php echo esc_url($remove_url); ?>" class="button">Remove</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No users in this league yet.</p>
        <?php endif; ?>

        <h2 style="margin-top: 40px;">Add User</h2>
        <form method="post">
            <?php wp_nonce_field('sr_manage_league_users', 'sr_league_users_nonce'); ?>
            <select name="add_user_id" required>
                <option value="">Select user...</option>
                <?php foreach ($all_users as $user): ?>
                    <?php
                    $already = false;
                    foreach ($users_in_league as $u) {
                        if ((int)$u->ID === (int)$user->ID) { $already = true; break; }
                    }
                    if ($already) continue;
                    ?>
                    <option value="<?php echo (int)$user->ID; ?>"><?php echo esc_html($user->display_name); ?></option>
                <?php endforeach; ?>
            </select>
            <?php submit_button('Add to League'); ?>
        </form>
    </div>
    <?php
}

/* =========================================================
   NAV MENU: hide Private Leagues menu item if user not in any
   ========================================================= */

function user_is_in_private_league($user_id) {
    global $wpdb;
    $count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM custom_competition_users ccu
        JOIN custom_competitions cc ON ccu.custom_competition_id = cc.id
        WHERE ccu.user_id = %d AND cc.is_private = 1
    ", (int)$user_id));
    return (int)$count > 0;
}

add_filter('wp_nav_menu_items', function ($items, $args) {
    if (!is_user_logged_in() || !user_is_in_private_league(get_current_user_id())) {
        $pattern = '#<li[^>]*>\s*<a[^>]*href=["\']https://sportsrush\.co\.uk/private-leagues/?["\'][^>]*>.*?</a>\s*</li>#i';
        $items = preg_replace($pattern, '', $items);
    }
    return $items;
}, 10, 2);