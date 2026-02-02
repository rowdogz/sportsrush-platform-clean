<?php
/**
 * Export functionality class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RW_Exports {
    
    public static function export_tenancies($args = array()) {
        $tenancies = RW_Tenancy::get_all(array_merge($args, array('limit' => 10000)));
        
        $csv_data = array();
        $csv_data[] = array(
            'ID',
            'Property ID',
            'Property Address',
            'Tenant ID',
            'Tenant Name',
            'Tenant Email',
            'Landlord ID',
            'Landlord Name',
            'Rent (GBP)',
            'Due Day',
            'Start Date',
            'End Date',
            'Status',
            'Created At'
        );
        
        foreach ($tenancies as $tenancy) {
            $property = RW_Property::get($tenancy->property_id);
            $tenant = get_userdata($tenancy->tenant_user_id);
            $landlord = get_userdata($tenancy->landlord_user_id);
            
            $csv_data[] = array(
                $tenancy->id,
                $tenancy->property_id,
                $property ? RW_Property::get_full_address($property) : '',
                $tenancy->tenant_user_id,
                $tenant ? $tenant->display_name : '',
                $tenant ? $tenant->user_email : '',
                $tenancy->landlord_user_id,
                $landlord ? $landlord->display_name : '',
                number_format($tenancy->rent_amount_pennies / 100, 2),
                $tenancy->due_day,
                $tenancy->start_date,
                $tenancy->end_date,
                $tenancy->status,
                $tenancy->created_at
            );
        }
        
        return self::generate_csv_with_manifest($csv_data, 'tenancies');
    }
    
    public static function export_ledger($args = array()) {
        $transactions = RW_Ledger::get_transactions(array_merge($args, array('limit' => 100000)));
        
        $csv_data = array();
        $csv_data[] = array(
            'ID',
            'Wallet ID',
            'Owner User ID',
            'Owner Role',
            'Counterparty Wallet ID',
            'Tenancy ID',
            'Type',
            'Amount (GBP)',
            'Running Balance (GBP)',
            'Metadata',
            'Hash',
            'Created At'
        );
        
        foreach ($transactions as $tx) {
            $wallet = RW_Wallet::get_wallet($tx->wallet_id);
            
            $csv_data[] = array(
                $tx->id,
                $tx->wallet_id,
                $wallet ? $wallet->owner_user_id : '',
                $wallet ? $wallet->owner_role : '',
                $tx->counterparty_wallet_id,
                $tx->tenancy_id,
                $tx->type,
                number_format($tx->amount_pennies / 100, 2),
                number_format($tx->running_balance_pennies / 100, 2),
                $tx->metadata_json,
                $tx->hash,
                $tx->created_at
            );
        }
        
        return self::generate_csv_with_manifest($csv_data, 'ledger');
    }
    
    public static function export_payouts($args = array()) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('payouts');
        
        $where = "1=1";
        $params = array();
        
        if (!empty($args['landlord_user_id'])) {
            $where .= " AND landlord_user_id = %d";
            $params[] = $args['landlord_user_id'];
        }
        
        if (!empty($args['date_from'])) {
            $where .= " AND created_at >= %s";
            $params[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where .= " AND created_at <= %s";
            $params[] = $args['date_to'];
        }
        
        $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT 100000";
        
        if (!empty($params)) {
            $payouts = $wpdb->get_results($wpdb->prepare($sql, $params));
        } else {
            $payouts = $wpdb->get_results($sql);
        }
        
        $csv_data = array();
        $csv_data[] = array(
            'ID',
            'Landlord ID',
            'Landlord Name',
            'Tenancy ID',
            'Gross Rent (GBP)',
            'Landlord Fee (GBP)',
            'Landlord VAT (GBP)',
            'Cashback Share (GBP)',
            'Net Paid Out (GBP)',
            'Status',
            'Created At'
        );
        
        foreach ($payouts as $payout) {
            $landlord = get_userdata($payout->landlord_user_id);
            
            $csv_data[] = array(
                $payout->id,
                $payout->landlord_user_id,
                $landlord ? $landlord->display_name : '',
                $payout->tenancy_id,
                number_format($payout->gross_rent_pennies / 100, 2),
                number_format($payout->landlord_fee_pennies / 100, 2),
                number_format($payout->landlord_vat_pennies / 100, 2),
                number_format($payout->landlord_cashback_share_pennies / 100, 2),
                number_format($payout->net_paid_out_pennies / 100, 2),
                $payout->status,
                $payout->created_at
            );
        }
        
        return self::generate_csv_with_manifest($csv_data, 'payouts');
    }
    
    public static function export_audit_log($args = array()) {
        $entries = RW_Audit::get_entries(array_merge($args, array('limit' => 100000)));
        
        $csv_data = array();
        $csv_data[] = array(
            'ID',
            'Actor User ID',
            'Actor Name',
            'Action',
            'Entity',
            'Entity ID',
            'Delta',
            'IP',
            'Hash',
            'Created At'
        );
        
        foreach ($entries as $entry) {
            $actor = $entry->actor_user_id ? get_userdata($entry->actor_user_id) : null;
            
            $csv_data[] = array(
                $entry->id,
                $entry->actor_user_id,
                $actor ? $actor->display_name : 'System',
                $entry->action,
                $entry->entity,
                $entry->entity_id,
                $entry->delta_json,
                $entry->ip,
                $entry->hash,
                $entry->created_at
            );
        }
        
        return self::generate_csv_with_manifest($csv_data, 'audit_log');
    }
    
    public static function export_properties($args = array()) {
        $properties = RW_Property::get_all(array_merge($args, array('limit' => 10000)));
        
        $csv_data = array();
        $csv_data[] = array(
            'ID',
            'Landlord ID',
            'Landlord Name',
            'Agency ID',
            'Address Line 1',
            'Address Line 2',
            'City',
            'Postcode',
            'Bedrooms',
            'Property Type',
            'Status',
            'Created At'
        );
        
        foreach ($properties as $property) {
            $landlord = get_userdata($property->landlord_user_id);
            $agency = $property->agency_id ? RW_Agency::get($property->agency_id) : null;
            
            $csv_data[] = array(
                $property->id,
                $property->landlord_user_id,
                $landlord ? $landlord->display_name : '',
                $property->agency_id,
                $property->address_line1,
                $property->address_line2,
                $property->city,
                $property->postcode,
                $property->bedrooms,
                $property->property_type,
                $property->status,
                $property->created_at
            );
        }
        
        return self::generate_csv_with_manifest($csv_data, 'properties');
    }
    
    private static function generate_csv_with_manifest($data, $type) {
        $csv_content = '';
        foreach ($data as $row) {
            $csv_content .= self::array_to_csv_line($row) . "\n";
        }
        
        $row_count = count($data) - 1; // Exclude header
        $last_hash = '';
        
        // Get last hash based on type
        if ($type === 'ledger') {
            $last_hash = RW_Ledger::get_last_hash();
        } elseif ($type === 'audit_log') {
            $last_hash = RW_Audit::get_last_hash();
        }
        
        $manifest = array(
            'export_type' => $type,
            'exported_at' => current_time('mysql'),
            'row_count' => $row_count,
            'last_hash' => $last_hash,
            'checksum' => md5($csv_content)
        );
        
        return array(
            'csv' => $csv_content,
            'manifest' => $manifest,
            'filename' => $type . '_export_' . date('Y-m-d_His') . '.csv'
        );
    }
    
    private static function array_to_csv_line($array) {
        $escaped = array_map(function($field) {
            if ($field === null) {
                return '';
            }
            $field = str_replace('"', '""', $field);
            if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
                return '"' . $field . '"';
            }
            return $field;
        }, $array);
        
        return implode(',', $escaped);
    }
    
    public static function download_csv($data, $filename) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $data;
        exit;
    }
    
    public static function download_with_manifest($export_result) {
        // Create a zip with CSV and manifest
        $zip = new ZipArchive();
        $zip_filename = sys_get_temp_dir() . '/' . str_replace('.csv', '.zip', $export_result['filename']);
        
        if ($zip->open($zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFromString($export_result['filename'], $export_result['csv']);
            $zip->addFromString('manifest.json', wp_json_encode($export_result['manifest'], JSON_PRETTY_PRINT));
            $zip->close();
            
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($zip_filename) . '"');
            header('Content-Length: ' . filesize($zip_filename));
            header('Pragma: no-cache');
            header('Expires: 0');
            
            readfile($zip_filename);
            unlink($zip_filename);
            exit;
        }
        
        // Fallback to just CSV
        self::download_csv($export_result['csv'], $export_result['filename']);
    }
}
