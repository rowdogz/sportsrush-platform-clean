<?php
/**
 * Database management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RW_Database {
    
    public static function activate() {
        self::create_tables();
        self::seed_defaults();
        self::schedule_cron();
        self::generate_hmac_secret();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // 1) rw_agencies
        $sql = "CREATE TABLE {$wpdb->prefix}rw_agencies (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql);
        
        // 2) rw_agency_assignments
        $sql = "CREATE TABLE {$wpdb->prefix}rw_agency_assignments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            agency_id BIGINT UNSIGNED NOT NULL,
            landlord_user_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY agency_landlord (agency_id, landlord_user_id)
        ) $charset_collate;";
        dbDelta($sql);
        
        // 3) rw_properties
        $sql = "CREATE TABLE {$wpdb->prefix}rw_properties (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            landlord_user_id BIGINT UNSIGNED NOT NULL,
            agency_id BIGINT UNSIGNED NULL,
            address_line1 VARCHAR(255) NOT NULL,
            address_line2 VARCHAR(255) NULL,
            city VARCHAR(100) NOT NULL,
            postcode VARCHAR(20) NOT NULL,
            bedrooms INT NULL,
            property_type VARCHAR(50) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY landlord_user_id (landlord_user_id),
            KEY agency_id (agency_id)
        ) $charset_collate;";
        dbDelta($sql);
        
        // 4) rw_tenancies
        $sql = "CREATE TABLE {$wpdb->prefix}rw_tenancies (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            property_id BIGINT UNSIGNED NOT NULL,
            tenant_user_id BIGINT UNSIGNED NOT NULL,
            landlord_user_id BIGINT UNSIGNED NOT NULL,
            rent_amount_pennies BIGINT NOT NULL,
            due_day INT NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY property_id (property_id),
            KEY tenant_user_id (tenant_user_id),
            KEY landlord_user_id (landlord_user_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql);
        
        // 5) rw_wallets
        $sql = "CREATE TABLE {$wpdb->prefix}rw_wallets (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            owner_user_id BIGINT UNSIGNED NOT NULL,
            owner_role VARCHAR(20) NOT NULL,
            currency VARCHAR(3) NOT NULL DEFAULT 'GBP',
            balance_pennies BIGINT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY owner_role_unique (owner_user_id, owner_role),
            KEY owner_user_id (owner_user_id)
        ) $charset_collate;";
        dbDelta($sql);
        
        // 6) rw_payouts
        $sql = "CREATE TABLE {$wpdb->prefix}rw_payouts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            landlord_user_id BIGINT UNSIGNED NOT NULL,
            tenancy_id BIGINT UNSIGNED NOT NULL,
            gross_rent_pennies BIGINT NOT NULL,
            landlord_fee_pennies BIGINT NOT NULL DEFAULT 0,
            landlord_vat_pennies BIGINT NOT NULL DEFAULT 0,
            landlord_cashback_share_pennies BIGINT NOT NULL DEFAULT 0,
            net_paid_out_pennies BIGINT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'simulated_paid',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY landlord_user_id (landlord_user_id),
            KEY tenancy_id (tenancy_id)
        ) $charset_collate;";
        dbDelta($sql);
        
        // 7) rw_fee_policy
        $sql = "CREATE TABLE {$wpdb->prefix}rw_fee_policy (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tenant_fee_bps INT NOT NULL DEFAULT 100,
            landlord_fee_bps INT NOT NULL DEFAULT 100,
            vat_enabled TINYINT NOT NULL DEFAULT 0,
            vat_bps INT NOT NULL DEFAULT 2000,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql);
        
        // 8) rw_reward_tiers
        $sql = "CREATE TABLE {$wpdb->prefix}rw_reward_tiers (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tier_key VARCHAR(50) NOT NULL,
            display_name VARCHAR(100) NOT NULL,
            min_buffer_months DECIMAL(6,2) NOT NULL DEFAULT 0,
            cashback_bps INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY tier_key (tier_key)
        ) $charset_collate;";
        dbDelta($sql);
        
        // 9) rw_transactions (append-only ledger)
        $sql = "CREATE TABLE {$wpdb->prefix}rw_transactions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            wallet_id BIGINT UNSIGNED NOT NULL,
            counterparty_wallet_id BIGINT UNSIGNED NULL,
            tenancy_id BIGINT UNSIGNED NULL,
            type VARCHAR(50) NOT NULL,
            amount_pennies BIGINT NOT NULL,
            running_balance_pennies BIGINT NOT NULL,
            metadata_json LONGTEXT NULL,
            prev_hash CHAR(64) NULL,
            hash CHAR(64) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY wallet_id (wallet_id),
            KEY tenancy_id (tenancy_id),
            KEY type (type),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql);
        
        // 10) rw_audit_log (append-only)
        $sql = "CREATE TABLE {$wpdb->prefix}rw_audit_log (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            actor_user_id BIGINT UNSIGNED NULL,
            action VARCHAR(100) NOT NULL,
            entity VARCHAR(100) NOT NULL,
            entity_id BIGINT UNSIGNED NULL,
            delta_json LONGTEXT NULL,
            ip VARCHAR(45) NULL,
            user_agent TEXT NULL,
            prev_hash CHAR(64) NULL,
            hash CHAR(64) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY actor_user_id (actor_user_id),
            KEY entity (entity),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql);
        
        // 11) rw_notification_logs
        $sql = "CREATE TABLE {$wpdb->prefix}rw_notification_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            channel VARCHAR(20) NOT NULL DEFAULT 'email',
            template_key VARCHAR(100) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            body LONGTEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'sent',
            error_text TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY template_key (template_key),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql);
        
        // 12) rw_user_profile
        $sql = "CREATE TABLE {$wpdb->prefix}rw_user_profile (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            wp_user_id BIGINT UNSIGNED NOT NULL,
            role_label VARCHAR(50) NOT NULL,
            kyc_status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY wp_user_id (wp_user_id)
        ) $charset_collate;";
        dbDelta($sql);
    }
    
    public static function seed_defaults() {
        global $wpdb;
        
        // Seed fee policy if not exists
        $exists = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}rw_fee_policy WHERE id = 1");
        if (!$exists) {
            $wpdb->insert(
                "{$wpdb->prefix}rw_fee_policy",
                array(
                    'id' => 1,
                    'tenant_fee_bps' => 100,
                    'landlord_fee_bps' => 100,
                    'vat_enabled' => 0,
                    'vat_bps' => 2000
                ),
                array('%d', '%d', '%d', '%d', '%d')
            );
        }
        
        // Seed reward tiers if not exists
        $tiers_exist = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}rw_reward_tiers");
        if (!$tiers_exist) {
            $tiers = array(
                array('tier_key' => 'bronze', 'display_name' => 'Bronze', 'min_buffer_months' => 0, 'cashback_bps' => 0),
                array('tier_key' => 'silver', 'display_name' => 'Silver', 'min_buffer_months' => 1, 'cashback_bps' => 25),
                array('tier_key' => 'gold', 'display_name' => 'Gold', 'min_buffer_months' => 2, 'cashback_bps' => 50),
                array('tier_key' => 'platinum', 'display_name' => 'Platinum', 'min_buffer_months' => 6, 'cashback_bps' => 75),
            );
            
            foreach ($tiers as $tier) {
                $wpdb->insert(
                    "{$wpdb->prefix}rw_reward_tiers",
                    $tier,
                    array('%s', '%s', '%f', '%d')
                );
            }
        }
        
        // Create platform wallet (user_id = 0 for platform)
        $platform_wallet = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}rw_wallets WHERE owner_user_id = %d AND owner_role = %s",
                0,
                'platform'
            )
        );
        if (!$platform_wallet) {
            $wpdb->insert(
                "{$wpdb->prefix}rw_wallets",
                array(
                    'owner_user_id' => 0,
                    'owner_role' => 'platform',
                    'currency' => 'GBP',
                    'balance_pennies' => 0
                ),
                array('%d', '%s', '%s', '%d')
            );
        }
    }
    
    public static function schedule_cron() {
        if (!wp_next_scheduled('rw_daily_rent_release')) {
            // Schedule for 6:00 AM site time daily
            $timestamp = strtotime('tomorrow 06:00:00');
            wp_schedule_event($timestamp, 'daily', 'rw_daily_rent_release');
        }
    }
    
    public static function generate_hmac_secret() {
        // Check if already defined in wp-config.php
        if (defined('RW_HMAC_SECRET')) {
            return;
        }
        
        // Check if stored in options
        $secret = get_option('rw_hmac_secret');
        if (!$secret) {
            // Generate a new secret
            $secret = bin2hex(random_bytes(32));
            update_option('rw_hmac_secret', $secret);
        }
    }
    
    public static function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . 'rw_' . $table;
    }
}
