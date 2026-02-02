<?php
/**
 * Demo data generator class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RW_Demo_Data {
    
    public static function generate() {
        global $wpdb;
        
        $results = array(
            'agency' => null,
            'landlords' => array(),
            'tenants' => array(),
            'properties' => array(),
            'tenancies' => array(),
            'wallet_credits' => array()
        );
        
        // 1. Create 1 agency
        $agency_id = RW_Agency::create('Demo Property Management Ltd');
        if (is_wp_error($agency_id)) {
            return $agency_id;
        }
        $results['agency'] = $agency_id;
        
        // 2. Create 2 landlords
        $landlord_data = array(
            array('username' => 'demo_landlord_1', 'email' => 'landlord1@demo.rentwallet.local', 'name' => 'John Smith'),
            array('username' => 'demo_landlord_2', 'email' => 'landlord2@demo.rentwallet.local', 'name' => 'Sarah Johnson')
        );
        
        foreach ($landlord_data as $data) {
            $user_id = self::create_user($data['username'], $data['email'], $data['name'], 'rw_landlord');
            if (!is_wp_error($user_id)) {
                $results['landlords'][] = $user_id;
                
                // Assign first landlord to agency
                if (count($results['landlords']) === 1) {
                    RW_Agency::assign_landlord($agency_id, $user_id);
                }
                
                // Create landlord wallet
                RW_Wallet::get_or_create_wallet($user_id, 'landlord');
            }
        }
        
        // 3. Create 3 tenants
        $tenant_data = array(
            array('username' => 'demo_tenant_1', 'email' => 'tenant1@demo.rentwallet.local', 'name' => 'Alice Brown'),
            array('username' => 'demo_tenant_2', 'email' => 'tenant2@demo.rentwallet.local', 'name' => 'Bob Wilson'),
            array('username' => 'demo_tenant_3', 'email' => 'tenant3@demo.rentwallet.local', 'name' => 'Carol Davis')
        );
        
        foreach ($tenant_data as $data) {
            $user_id = self::create_user($data['username'], $data['email'], $data['name'], 'rw_tenant');
            if (!is_wp_error($user_id)) {
                $results['tenants'][] = $user_id;
                
                // Create tenant wallet
                RW_Wallet::get_or_create_wallet($user_id, 'tenant');
            }
        }
        
        // 4. Create 3 properties
        $property_data = array(
            array(
                'landlord_index' => 0,
                'agency_id' => $agency_id,
                'address_line1' => '123 High Street',
                'address_line2' => 'Flat 4',
                'city' => 'London',
                'postcode' => 'SW1A 1AA',
                'bedrooms' => 2,
                'property_type' => 'flat'
            ),
            array(
                'landlord_index' => 0,
                'agency_id' => $agency_id,
                'address_line1' => '45 Oak Avenue',
                'address_line2' => '',
                'city' => 'Manchester',
                'postcode' => 'M1 2AB',
                'bedrooms' => 3,
                'property_type' => 'house'
            ),
            array(
                'landlord_index' => 1,
                'agency_id' => null,
                'address_line1' => '78 Park Lane',
                'address_line2' => 'Unit 12',
                'city' => 'Birmingham',
                'postcode' => 'B1 3CD',
                'bedrooms' => 1,
                'property_type' => 'studio'
            )
        );
        
        foreach ($property_data as $data) {
            $landlord_id = $results['landlords'][$data['landlord_index']];
            
            $property_id = RW_Property::create(array(
                'landlord_user_id' => $landlord_id,
                'agency_id' => $data['agency_id'],
                'address_line1' => $data['address_line1'],
                'address_line2' => $data['address_line2'],
                'city' => $data['city'],
                'postcode' => $data['postcode'],
                'bedrooms' => $data['bedrooms'],
                'property_type' => $data['property_type']
            ));
            
            if (!is_wp_error($property_id)) {
                $results['properties'][] = $property_id;
            }
        }
        
        // 5. Create 3 tenancies with varying scenarios
        $today = (int) date('j');
        $due_day = min($today, 28); // Use today or 28 if today > 28
        
        $tenancy_data = array(
            // Tenant 1: Good balance (cashback scenario - 3 months coverage)
            array(
                'property_index' => 0,
                'tenant_index' => 0,
                'rent_pounds' => 1200,
                'due_day' => $due_day,
                'wallet_balance_months' => 3 // Gold tier
            ),
            // Tenant 2: Low balance (arrears scenario)
            array(
                'property_index' => 1,
                'tenant_index' => 1,
                'rent_pounds' => 950,
                'due_day' => $due_day,
                'wallet_balance_months' => 0.5 // Less than 1 month - will be in arrears
            ),
            // Tenant 3: Moderate balance (Silver tier)
            array(
                'property_index' => 2,
                'tenant_index' => 2,
                'rent_pounds' => 800,
                'due_day' => $due_day,
                'wallet_balance_months' => 1.5 // Silver tier
            )
        );
        
        foreach ($tenancy_data as $data) {
            $property = RW_Property::get($results['properties'][$data['property_index']]);
            $tenant_id = $results['tenants'][$data['tenant_index']];
            $rent_pennies = $data['rent_pounds'] * 100;
            
            $tenancy_id = RW_Tenancy::create(array(
                'property_id' => $results['properties'][$data['property_index']],
                'tenant_user_id' => $tenant_id,
                'landlord_user_id' => $property->landlord_user_id,
                'rent_amount_pennies' => $rent_pennies,
                'due_day' => $data['due_day'],
                'start_date' => date('Y-m-d', strtotime('-3 months'))
            ));
            
            if (!is_wp_error($tenancy_id)) {
                $results['tenancies'][] = $tenancy_id;
                
                // Credit tenant wallet
                $credit_amount = (int) ($rent_pennies * $data['wallet_balance_months']);
                if ($credit_amount > 0) {
                    $credit_result = RW_Wallet::manual_credit(
                        $tenant_id,
                        $credit_amount,
                        get_current_user_id(),
                        'Demo data initial credit'
                    );
                    
                    $results['wallet_credits'][] = array(
                        'tenant_id' => $tenant_id,
                        'amount' => $credit_amount,
                        'months_coverage' => $data['wallet_balance_months']
                    );
                }
            }
        }
        
        // Create agency staff user
        $agency_staff_id = self::create_user(
            'demo_agency_staff',
            'agency@demo.rentwallet.local',
            'Agency Manager',
            'rw_agency_staff'
        );
        
        if (!is_wp_error($agency_staff_id)) {
            RW_Roles::set_user_agency_id($agency_staff_id, $agency_id);
            $results['agency_staff'] = $agency_staff_id;
        }
        
        // Log demo data generation
        RW_Audit::log('demo_data_generated', 'system', null, $results);
        
        return $results;
    }
    
    private static function create_user($username, $email, $display_name, $role) {
        // Check if user exists
        $existing = get_user_by('login', $username);
        if ($existing) {
            // Update role if needed
            $existing->set_role($role);
            return $existing->ID;
        }
        
        $user_id = wp_insert_user(array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => wp_generate_password(16),
            'display_name' => $display_name,
            'first_name' => explode(' ', $display_name)[0],
            'last_name' => isset(explode(' ', $display_name)[1]) ? explode(' ', $display_name)[1] : '',
            'role' => $role
        ));
        
        if (!is_wp_error($user_id)) {
            // Create user profile
            global $wpdb;
            $profile_table = RW_Database::get_table_name('user_profile');
            $wpdb->insert(
                $profile_table,
                array(
                    'wp_user_id' => $user_id,
                    'role_label' => $role,
                    'kyc_status' => 'verified'
                ),
                array('%d', '%s', '%s')
            );
        }
        
        return $user_id;
    }
    
    public static function cleanup() {
        global $wpdb;
        
        // Delete demo users
        $demo_users = get_users(array(
            'search' => '*@demo.rentwallet.local',
            'search_columns' => array('user_email')
        ));
        
        foreach ($demo_users as $user) {
            wp_delete_user($user->ID);
        }
        
        // Delete demo agency
        $agencies_table = RW_Database::get_table_name('agencies');
        $wpdb->delete($agencies_table, array('name' => 'Demo Property Management Ltd'), array('%s'));
        
        return true;
    }
}
