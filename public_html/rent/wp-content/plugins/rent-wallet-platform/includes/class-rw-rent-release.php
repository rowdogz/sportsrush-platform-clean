<?php
/**
 * Rent release processing class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RW_Rent_Release {
    
    public static function process_daily_releases() {
        $tenancies = RW_Tenancy::get_active_tenancies_due_today();
        
        if (empty($tenancies)) {
            return array(
                'processed' => 0,
                'successful' => 0,
                'arrears' => 0,
                'errors' => 0
            );
        }
        
        $results = array(
            'processed' => 0,
            'successful' => 0,
            'arrears' => 0,
            'errors' => 0,
            'details' => array()
        );
        
        foreach ($tenancies as $tenancy) {
            $result = self::process_single_release($tenancy);
            $results['processed']++;
            
            if (is_wp_error($result)) {
                $results['errors']++;
                $results['details'][] = array(
                    'tenancy_id' => $tenancy->id,
                    'status' => 'error',
                    'message' => $result->get_error_message()
                );
            } elseif ($result === 'arrears') {
                $results['arrears']++;
                $results['details'][] = array(
                    'tenancy_id' => $tenancy->id,
                    'status' => 'arrears'
                );
            } else {
                $results['successful']++;
                $results['details'][] = array(
                    'tenancy_id' => $tenancy->id,
                    'status' => 'success',
                    'data' => $result
                );
            }
        }
        
        return $results;
    }
    
    public static function process_single_release($tenancy) {
        global $wpdb;
        
        // Get tenant wallet
        $tenant_wallet = RW_Wallet::get_or_create_wallet($tenancy->tenant_user_id, 'tenant');
        $platform_wallet = RW_Wallet::get_platform_wallet();
        
        $rent = $tenancy->rent_amount_pennies;
        
        // Check if tenant has sufficient balance
        if ($tenant_wallet->balance_pennies < $rent) {
            // Arrears scenario
            self::handle_arrears($tenancy, $tenant_wallet);
            return 'arrears';
        }
        
        // Calculate fees
        $fee_policy = RW_Fee_Policy::get();
        $tenant_fee = RW_Fee_Policy::calculate_tenant_fee($rent);
        $landlord_fee = RW_Fee_Policy::calculate_landlord_fee($rent);
        
        // Calculate VAT on fees if enabled
        $tenant_vat = 0;
        $landlord_vat = 0;
        if ($fee_policy->vat_enabled) {
            $tenant_vat = RW_Fee_Policy::calculate_vat($tenant_fee);
            $landlord_vat = RW_Fee_Policy::calculate_vat($landlord_fee);
        }
        
        // Calculate coverage months BEFORE debiting rent (as per spec)
        $coverage_months = $tenant_wallet->balance_pennies / $rent;
        $tier = RW_Reward_Tiers::get_tier_for_coverage($coverage_months);
        
        // Calculate cashback
        $cashback_total = 0;
        $cashback_platform_share = 0;
        $cashback_landlord_share = 0;
        
        if ($tier && $tier->cashback_bps > 0) {
            $cashback_total = RW_Reward_Tiers::calculate_cashback($rent, $coverage_months);
            // 50/50 split between platform and landlord
            $cashback_platform_share = (int) floor($cashback_total / 2);
            $cashback_landlord_share = $cashback_total - $cashback_platform_share;
        }
        
        // Total tenant deduction
        $total_tenant_deduction = $rent + $tenant_fee + $tenant_vat;
        
        // Check if tenant can cover all charges
        if ($tenant_wallet->balance_pennies < $total_tenant_deduction) {
            self::handle_arrears($tenancy, $tenant_wallet);
            return 'arrears';
        }
        
        // Start atomic transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            $metadata_base = array(
                'tenancy_id' => $tenancy->id,
                'rent_amount' => $rent,
                'tier' => $tier ? $tier->tier_key : 'bronze',
                'coverage_months' => $coverage_months,
                'processed_at' => current_time('mysql')
            );
            
            // 1) Tenant wallet: rent_release (-rent)
            RW_Wallet::debit(
                $tenant_wallet->id,
                $rent,
                'rent_release',
                array_merge($metadata_base, array('description' => 'Monthly rent payment')),
                $tenancy->id,
                $platform_wallet->id
            );
            
            // 2) Tenant wallet: tenant_fee (-tenant_fee)
            if ($tenant_fee > 0) {
                RW_Wallet::debit(
                    $tenant_wallet->id,
                    $tenant_fee,
                    'tenant_fee',
                    array_merge($metadata_base, array('fee_bps' => $fee_policy->tenant_fee_bps)),
                    $tenancy->id,
                    $platform_wallet->id
                );
            }
            
            // 3) Tenant wallet: tenant_vat (-tenant_vat) if VAT enabled
            if ($tenant_vat > 0) {
                RW_Wallet::debit(
                    $tenant_wallet->id,
                    $tenant_vat,
                    'tenant_vat',
                    array_merge($metadata_base, array('vat_bps' => $fee_policy->vat_bps)),
                    $tenancy->id,
                    $platform_wallet->id
                );
            }
            
            // 4) Tenant wallet: cashback_tenant (+cashback_total) if applicable
            if ($cashback_total > 0) {
                RW_Wallet::credit(
                    $tenant_wallet->id,
                    $cashback_total,
                    'cashback_tenant',
                    array_merge($metadata_base, array(
                        'cashback_bps' => $tier->cashback_bps,
                        'cashback_total' => $cashback_total
                    )),
                    $tenancy->id
                );
            }
            
            // 5) Platform wallet: +tenant_fee (+tenant_vat) and -cashback_platform_share
            $platform_income = $tenant_fee + $tenant_vat;
            if ($platform_income > 0) {
                RW_Wallet::credit(
                    $platform_wallet->id,
                    $platform_income,
                    'platform_fee_income',
                    array_merge($metadata_base, array(
                        'tenant_fee' => $tenant_fee,
                        'tenant_vat' => $tenant_vat
                    )),
                    $tenancy->id,
                    $tenant_wallet->id
                );
            }
            
            if ($cashback_platform_share > 0) {
                RW_Wallet::debit(
                    $platform_wallet->id,
                    $cashback_platform_share,
                    'cashback_platform_share',
                    array_merge($metadata_base, array('share' => '50%')),
                    $tenancy->id,
                    $tenant_wallet->id
                );
            }
            
            // 6) Create payout record for landlord
            $net_paid_out = $rent - $landlord_fee - $landlord_vat - $cashback_landlord_share;
            
            $payouts_table = RW_Database::get_table_name('payouts');
            $wpdb->insert(
                $payouts_table,
                array(
                    'landlord_user_id' => $tenancy->landlord_user_id,
                    'tenancy_id' => $tenancy->id,
                    'gross_rent_pennies' => $rent,
                    'landlord_fee_pennies' => $landlord_fee,
                    'landlord_vat_pennies' => $landlord_vat,
                    'landlord_cashback_share_pennies' => $cashback_landlord_share,
                    'net_paid_out_pennies' => $net_paid_out,
                    'status' => 'simulated_paid'
                ),
                array('%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s')
            );
            
            $payout_id = $wpdb->insert_id;
            
            // 7) Ledger entry for payout_simulated on platform wallet
            RW_Ledger::create_transaction(
                $platform_wallet->id,
                'payout_simulated',
                -$net_paid_out,
                RW_Wallet::get_balance($platform_wallet->id) - $net_paid_out,
                array_merge($metadata_base, array(
                    'payout_id' => $payout_id,
                    'landlord_user_id' => $tenancy->landlord_user_id,
                    'net_paid_out' => $net_paid_out
                )),
                $tenancy->id
            );
            
            // Update platform wallet balance for payout
            $current_platform_balance = RW_Wallet::get_balance($platform_wallet->id);
            RW_Wallet::update_balance($platform_wallet->id, $current_platform_balance - $net_paid_out);
            
            $wpdb->query('COMMIT');
            
            // Audit log
            RW_Audit::log('rent_released', 'tenancy', $tenancy->id, array(
                'rent' => $rent,
                'tenant_fee' => $tenant_fee,
                'tenant_vat' => $tenant_vat,
                'landlord_fee' => $landlord_fee,
                'landlord_vat' => $landlord_vat,
                'cashback_total' => $cashback_total,
                'cashback_platform_share' => $cashback_platform_share,
                'cashback_landlord_share' => $cashback_landlord_share,
                'net_paid_out' => $net_paid_out,
                'tier' => $tier ? $tier->tier_key : 'bronze',
                'payout_id' => $payout_id
            ));
            
            // Send notifications
            self::send_rent_release_notifications($tenancy, array(
                'rent' => $rent,
                'tenant_fee' => $tenant_fee,
                'tenant_vat' => $tenant_vat,
                'cashback' => $cashback_total,
                'net_paid_out' => $net_paid_out,
                'tier' => $tier
            ));
            
            return array(
                'payout_id' => $payout_id,
                'rent' => $rent,
                'tenant_fee' => $tenant_fee,
                'tenant_vat' => $tenant_vat,
                'cashback' => $cashback_total,
                'net_paid_out' => $net_paid_out
            );
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('release_failed', $e->getMessage());
        }
    }
    
    private static function handle_arrears($tenancy, $tenant_wallet) {
        // Log arrears
        RW_Audit::log('rent_arrears', 'tenancy', $tenancy->id, array(
            'rent_due' => $tenancy->rent_amount_pennies,
            'balance' => $tenant_wallet->balance_pennies,
            'shortfall' => $tenancy->rent_amount_pennies - $tenant_wallet->balance_pennies
        ));
        
        // Send arrears notification
        $tenant = get_userdata($tenancy->tenant_user_id);
        $property = RW_Property::get($tenancy->property_id);
        
        $shortfall = $tenancy->rent_amount_pennies - $tenant_wallet->balance_pennies;
        
        RW_Notifications::send(
            $tenancy->tenant_user_id,
            'arrears_reminder',
            __('Rent Payment Reminder - Insufficient Funds', 'rent-wallet-platform'),
            sprintf(
                __("Dear %s,\n\nYour rent payment of %s is due today, but your wallet balance of %s is insufficient.\n\nPlease top up your wallet with at least %s to avoid arrears.\n\nProperty: %s\n\nThank you,\nRent Wallet Team", 'rent-wallet-platform'),
                $tenant->display_name,
                RW_Wallet::format_pennies($tenancy->rent_amount_pennies),
                RW_Wallet::format_pennies($tenant_wallet->balance_pennies),
                RW_Wallet::format_pennies($shortfall),
                RW_Property::get_full_address($property)
            )
        );
    }
    
    private static function send_rent_release_notifications($tenancy, $data) {
        $tenant = get_userdata($tenancy->tenant_user_id);
        $landlord = get_userdata($tenancy->landlord_user_id);
        $property = RW_Property::get($tenancy->property_id);
        
        // Tenant notification
        $tenant_message = sprintf(
            __("Dear %s,\n\nYour rent payment has been processed:\n\nRent: %s\nService Fee: %s\n", 'rent-wallet-platform'),
            $tenant->display_name,
            RW_Wallet::format_pennies($data['rent']),
            RW_Wallet::format_pennies($data['tenant_fee'])
        );
        
        if ($data['tenant_vat'] > 0) {
            $tenant_message .= sprintf(__("VAT: %s\n", 'rent-wallet-platform'), RW_Wallet::format_pennies($data['tenant_vat']));
        }
        
        if ($data['cashback'] > 0) {
            $tenant_message .= sprintf(
                __("Cashback Earned (%s tier): %s\n", 'rent-wallet-platform'),
                $data['tier'] ? $data['tier']->display_name : 'Bronze',
                RW_Wallet::format_pennies($data['cashback'])
            );
        }
        
        $tenant_message .= sprintf(
            __("\nProperty: %s\n\nThank you for using Rent Wallet!\n\nRent Wallet Team", 'rent-wallet-platform'),
            RW_Property::get_full_address($property)
        );
        
        RW_Notifications::send(
            $tenancy->tenant_user_id,
            'rent_released_tenant',
            __('Rent Payment Processed', 'rent-wallet-platform'),
            $tenant_message
        );
        
        // Landlord notification
        $landlord_message = sprintf(
            __("Dear %s,\n\nA rent payment has been processed for your property:\n\nProperty: %s\nGross Rent: %s\nNet Payout: %s\n\nThe payout has been simulated and recorded.\n\nThank you,\nRent Wallet Team", 'rent-wallet-platform'),
            $landlord->display_name,
            RW_Property::get_full_address($property),
            RW_Wallet::format_pennies($data['rent']),
            RW_Wallet::format_pennies($data['net_paid_out'])
        );
        
        RW_Notifications::send(
            $tenancy->landlord_user_id,
            'rent_released_landlord',
            __('Rent Payment Received', 'rent-wallet-platform'),
            $landlord_message
        );
    }
    
    public static function run_manual($tenancy_id = null) {
        if ($tenancy_id) {
            $tenancy = RW_Tenancy::get($tenancy_id);
            if (!$tenancy) {
                return new WP_Error('tenancy_not_found', __('Tenancy not found', 'rent-wallet-platform'));
            }
            return self::process_single_release($tenancy);
        }
        
        return self::process_daily_releases();
    }
}
