<?php
/**
 * Agency management class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RW_Agency {
    
    public static function create($name) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('agencies');
        
        $result = $wpdb->insert(
            $table,
            array('name' => sanitize_text_field($name)),
            array('%s')
        );
        
        if ($result === false) {
            return new WP_Error('agency_create_failed', __('Failed to create agency', 'rent-wallet-platform'));
        }
        
        $agency_id = $wpdb->insert_id;
        
        RW_Audit::log('agency_created', 'agency', $agency_id, array('name' => $name));
        
        return $agency_id;
    }
    
    public static function update($agency_id, $data) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('agencies');
        
        $old_agency = self::get($agency_id);
        if (!$old_agency) {
            return new WP_Error('agency_not_found', __('Agency not found', 'rent-wallet-platform'));
        }
        
        $update_data = array();
        $format = array();
        
        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
            $format[] = '%s';
        }
        
        if (empty($update_data)) {
            return true;
        }
        
        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $agency_id),
            $format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('agency_update_failed', __('Failed to update agency', 'rent-wallet-platform'));
        }
        
        RW_Audit::log('agency_updated', 'agency', $agency_id, array(
            'old' => (array) $old_agency,
            'new' => $update_data
        ));
        
        return true;
    }
    
    public static function delete($agency_id) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('agencies');
        $assignments_table = RW_Database::get_table_name('agency_assignments');
        
        $agency = self::get($agency_id);
        if (!$agency) {
            return new WP_Error('agency_not_found', __('Agency not found', 'rent-wallet-platform'));
        }
        
        // Delete assignments first
        $wpdb->delete($assignments_table, array('agency_id' => $agency_id), array('%d'));
        
        // Delete agency
        $result = $wpdb->delete($table, array('id' => $agency_id), array('%d'));
        
        if ($result === false) {
            return new WP_Error('agency_delete_failed', __('Failed to delete agency', 'rent-wallet-platform'));
        }
        
        RW_Audit::log('agency_deleted', 'agency', $agency_id, array('name' => $agency->name));
        
        return true;
    }
    
    public static function get($agency_id) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('agencies');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $agency_id
        ));
    }
    
    public static function get_all($args = array()) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('agencies');
        
        $defaults = array(
            'orderby' => 'name',
            'order' => 'ASC',
            'limit' => 100,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        if (!$orderby) {
            $orderby = 'name ASC';
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} ORDER BY {$orderby} LIMIT %d OFFSET %d",
            $args['limit'],
            $args['offset']
        ));
    }
    
    public static function get_count() {
        global $wpdb;
        
        $table = RW_Database::get_table_name('agencies');
        
        return $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }
    
    public static function assign_landlord($agency_id, $landlord_user_id) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('agency_assignments');
        
        // Check if already assigned
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE agency_id = %d AND landlord_user_id = %d",
            $agency_id,
            $landlord_user_id
        ));
        
        if ($exists) {
            return new WP_Error('already_assigned', __('Landlord is already assigned to this agency', 'rent-wallet-platform'));
        }
        
        $result = $wpdb->insert(
            $table,
            array(
                'agency_id' => $agency_id,
                'landlord_user_id' => $landlord_user_id
            ),
            array('%d', '%d')
        );
        
        if ($result === false) {
            return new WP_Error('assignment_failed', __('Failed to assign landlord to agency', 'rent-wallet-platform'));
        }
        
        $assignment_id = $wpdb->insert_id;
        
        RW_Audit::log('agency_assignment_created', 'agency_assignment', $assignment_id, array(
            'agency_id' => $agency_id,
            'landlord_user_id' => $landlord_user_id
        ));
        
        return $assignment_id;
    }
    
    public static function unassign_landlord($agency_id, $landlord_user_id) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('agency_assignments');
        
        $assignment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE agency_id = %d AND landlord_user_id = %d",
            $agency_id,
            $landlord_user_id
        ));
        
        if (!$assignment) {
            return new WP_Error('not_assigned', __('Landlord is not assigned to this agency', 'rent-wallet-platform'));
        }
        
        $result = $wpdb->delete(
            $table,
            array(
                'agency_id' => $agency_id,
                'landlord_user_id' => $landlord_user_id
            ),
            array('%d', '%d')
        );
        
        if ($result === false) {
            return new WP_Error('unassignment_failed', __('Failed to unassign landlord from agency', 'rent-wallet-platform'));
        }
        
        RW_Audit::log('agency_assignment_deleted', 'agency_assignment', $assignment->id, array(
            'agency_id' => $agency_id,
            'landlord_user_id' => $landlord_user_id
        ));
        
        return true;
    }
    
    public static function get_assigned_landlords($agency_id) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('agency_assignments');
        
        $landlord_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT landlord_user_id FROM {$table} WHERE agency_id = %d",
            $agency_id
        ));
        
        if (empty($landlord_ids)) {
            return array();
        }
        
        $landlords = array();
        foreach ($landlord_ids as $user_id) {
            $user = get_userdata($user_id);
            if ($user) {
                $landlords[] = $user;
            }
        }
        
        return $landlords;
    }
    
    public static function get_landlord_agencies($landlord_user_id) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('agency_assignments');
        $agencies_table = RW_Database::get_table_name('agencies');
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.* FROM {$agencies_table} a
            INNER JOIN {$table} aa ON a.id = aa.agency_id
            WHERE aa.landlord_user_id = %d",
            $landlord_user_id
        ));
    }
    
    public static function is_landlord_assigned($agency_id, $landlord_user_id) {
        global $wpdb;
        
        $table = RW_Database::get_table_name('agency_assignments');
        
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE agency_id = %d AND landlord_user_id = %d",
            $agency_id,
            $landlord_user_id
        ));
    }
}
