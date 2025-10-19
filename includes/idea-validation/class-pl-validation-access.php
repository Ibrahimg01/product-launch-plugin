<?php
/**
 * Validation Access Control
 * Manages user permissions for validations
 */

if (!defined('ABSPATH')) exit;

class PL_Validation_Access {
    
    /**
     * Check if user can view validation
     */
    public function can_view_validation($validation_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Admins can view all
        if (user_can($user_id, 'manage_options') || user_can($user_id, 'manage_network_options')) {
            return true;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'pl_validations';
        
        $owner_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $table WHERE id = %d",
            $validation_id
        ));
        
        // User can view their own validations
        if ($owner_id == $user_id) {
            return true;
        }
        
        return apply_filters('pl_validation_can_view', false, $validation_id, $user_id);
    }
    
    /**
     * Check if user can edit/delete validation
     */
    public function can_edit_validation($validation_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Admins can edit all
        if (user_can($user_id, 'manage_options') || user_can($user_id, 'manage_network_options')) {
            return true;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'pl_validations';
        
        $owner_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $table WHERE id = %d",
            $validation_id
        ));
        
        // Only owner can edit
        return $owner_id == $user_id;
    }
    
    /**
     * Check if user can create validations
     */
    public function can_create_validation($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Must be logged in
        if (!$user_id) {
            return false;
        }
        
        // Check quota
        $quota = new PL_Validation_Quota();
        return $quota->can_validate($user_id);
    }
    
    /**
     * Get user's validations
     */
    public function get_user_validations($user_id = null, $args = array()) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $defaults = array(
            'limit' => 10,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'status' => null
        );
        
        $args = wp_parse_args($args, $defaults);
        
        global $wpdb;
        $table = $wpdb->prefix . 'pl_validations';
        
        $where = $wpdb->prepare("WHERE user_id = %d", $user_id);
        
        if ($args['status']) {
            $where .= $wpdb->prepare(" AND validation_status = %s", $args['status']);
        }
        
        $sql = "SELECT * FROM $table 
                $where 
                ORDER BY {$args['orderby']} {$args['order']} 
                LIMIT %d OFFSET %d";
        
        $results = $wpdb->get_results($wpdb->prepare(
            $sql,
            $args['limit'],
            $args['offset']
        ));
        
        return $results;
    }
    
    /**
     * Get validation by ID with access check
     */
    public function get_validation($validation_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$this->can_view_validation($validation_id, $user_id)) {
            return false;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'pl_validations';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $validation_id
        ));
    }
    
    /**
     * Delete validation with access check
     */
    public function delete_validation($validation_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$this->can_edit_validation($validation_id, $user_id)) {
            return false;
        }
        
        global $wpdb;
        
        // Delete from validations table
        $table_validations = $wpdb->prefix . 'pl_validations';
        $wpdb->delete($table_validations, array('id' => $validation_id));
        
        // Delete cached enrichment data
        $table_enrichment = $wpdb->prefix . 'pl_validation_enrichment';
        $wpdb->delete($table_enrichment, array('validation_id' => $validation_id));
        
        return true;
    }
}
