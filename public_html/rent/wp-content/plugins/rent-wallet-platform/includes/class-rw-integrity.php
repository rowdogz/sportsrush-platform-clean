<?php
/**
 * Integrity verification class for ledger and audit log chains
 */

if (!defined('ABSPATH')) {
    exit;
}

class RW_Integrity {
    
    public static function verify_ledger_chain() {
        global $wpdb;
        
        $table = RW_Database::get_table_name('transactions');
        
        $transactions = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id ASC");
        
        if (empty($transactions)) {
            return array(
                'status' => 'pass',
                'message' => __('Ledger is empty - no transactions to verify.', 'rent-wallet-platform'),
                'total_records' => 0,
                'verified_records' => 0,
                'first_broken_id' => null,
                'expected_hash' => null,
                'actual_hash' => null
            );
        }
        
        $prev_hash = null;
        $verified = 0;
        
        foreach ($transactions as $tx) {
            // Recreate the canonical representation
            $canonical = RW_Ledger::create_canonical_representation(array(
                'wallet_id' => $tx->wallet_id,
                'counterparty_wallet_id' => $tx->counterparty_wallet_id,
                'tenancy_id' => $tx->tenancy_id,
                'type' => $tx->type,
                'amount_pennies' => $tx->amount_pennies,
                'running_balance_pennies' => $tx->running_balance_pennies,
                'metadata_json' => $tx->metadata_json,
                'prev_hash' => $tx->prev_hash
            ));
            
            // Calculate expected hash
            $expected_hash = RW_Ledger::generate_hash($tx->prev_hash, $canonical);
            
            // Verify prev_hash matches previous record's hash
            if ($prev_hash !== null && $tx->prev_hash !== $prev_hash) {
                return array(
                    'status' => 'fail',
                    'message' => sprintf(__('Chain broken at transaction ID %d: prev_hash mismatch.', 'rent-wallet-platform'), $tx->id),
                    'total_records' => count($transactions),
                    'verified_records' => $verified,
                    'first_broken_id' => $tx->id,
                    'expected_hash' => $prev_hash,
                    'actual_hash' => $tx->prev_hash
                );
            }
            
            // Verify hash
            if ($tx->hash !== $expected_hash) {
                return array(
                    'status' => 'fail',
                    'message' => sprintf(__('Hash mismatch at transaction ID %d.', 'rent-wallet-platform'), $tx->id),
                    'total_records' => count($transactions),
                    'verified_records' => $verified,
                    'first_broken_id' => $tx->id,
                    'expected_hash' => $expected_hash,
                    'actual_hash' => $tx->hash
                );
            }
            
            $prev_hash = $tx->hash;
            $verified++;
        }
        
        return array(
            'status' => 'pass',
            'message' => sprintf(__('All %d ledger transactions verified successfully.', 'rent-wallet-platform'), $verified),
            'total_records' => count($transactions),
            'verified_records' => $verified,
            'first_broken_id' => null,
            'expected_hash' => null,
            'actual_hash' => null,
            'last_hash' => $prev_hash
        );
    }
    
    public static function verify_audit_chain() {
        global $wpdb;
        
        $table = RW_Database::get_table_name('audit_log');
        
        $entries = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id ASC");
        
        if (empty($entries)) {
            return array(
                'status' => 'pass',
                'message' => __('Audit log is empty - no entries to verify.', 'rent-wallet-platform'),
                'total_records' => 0,
                'verified_records' => 0,
                'first_broken_id' => null,
                'expected_hash' => null,
                'actual_hash' => null
            );
        }
        
        $prev_hash = null;
        $verified = 0;
        
        foreach ($entries as $entry) {
            // Recreate the canonical representation
            $canonical = RW_Audit::create_canonical_representation(array(
                'actor_user_id' => $entry->actor_user_id,
                'action' => $entry->action,
                'entity' => $entry->entity,
                'entity_id' => $entry->entity_id,
                'delta_json' => $entry->delta_json,
                'ip' => $entry->ip,
                'user_agent' => $entry->user_agent,
                'prev_hash' => $entry->prev_hash
            ));
            
            // Calculate expected hash
            $expected_hash = RW_Audit::generate_hash($entry->prev_hash, $canonical);
            
            // Verify prev_hash matches previous record's hash
            if ($prev_hash !== null && $entry->prev_hash !== $prev_hash) {
                return array(
                    'status' => 'fail',
                    'message' => sprintf(__('Chain broken at audit log ID %d: prev_hash mismatch.', 'rent-wallet-platform'), $entry->id),
                    'total_records' => count($entries),
                    'verified_records' => $verified,
                    'first_broken_id' => $entry->id,
                    'expected_hash' => $prev_hash,
                    'actual_hash' => $entry->prev_hash
                );
            }
            
            // Verify hash
            if ($entry->hash !== $expected_hash) {
                return array(
                    'status' => 'fail',
                    'message' => sprintf(__('Hash mismatch at audit log ID %d.', 'rent-wallet-platform'), $entry->id),
                    'total_records' => count($entries),
                    'verified_records' => $verified,
                    'first_broken_id' => $entry->id,
                    'expected_hash' => $expected_hash,
                    'actual_hash' => $entry->hash
                );
            }
            
            $prev_hash = $entry->hash;
            $verified++;
        }
        
        return array(
            'status' => 'pass',
            'message' => sprintf(__('All %d audit log entries verified successfully.', 'rent-wallet-platform'), $verified),
            'total_records' => count($entries),
            'verified_records' => $verified,
            'first_broken_id' => null,
            'expected_hash' => null,
            'actual_hash' => null,
            'last_hash' => $prev_hash
        );
    }
    
    public static function verify_all() {
        $ledger_result = self::verify_ledger_chain();
        $audit_result = self::verify_audit_chain();
        
        $overall_status = ($ledger_result['status'] === 'pass' && $audit_result['status'] === 'pass') ? 'pass' : 'fail';
        
        return array(
            'overall_status' => $overall_status,
            'ledger' => $ledger_result,
            'audit' => $audit_result,
            'verified_at' => current_time('mysql')
        );
    }
}
