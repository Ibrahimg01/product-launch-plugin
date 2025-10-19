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
        $tables = array(pl_get_validation_table_name('site'));

        if (is_multisite()) {
            $tables[] = pl_get_validation_table_name('network');
            $tables = array_unique($tables);
        }

        $record = null;

        foreach ($tables as $table) {
            $record = $wpdb->get_row($wpdb->prepare(
                "SELECT user_id, library_published FROM $table WHERE id = %d",
                $validation_id
            ));

            if ($record) {
                break;
            }
        }

        if (!$record) {
            return false;
        }

        // User can view their own validations
        if ((int) $record->user_id === (int) $user_id) {
            return true;
        }

        // Published validations are available to all authenticated users.
        if (!empty($record->library_published)) {
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

        $tables = array(pl_get_validation_table_name('site'));

        if (is_multisite()) {
            $tables[] = pl_get_validation_table_name('network');
            $tables = array_unique($tables);
        }

        foreach ($tables as $table) {
            $owner_id = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM $table WHERE id = %d",
                $validation_id
            ));

            if (null !== $owner_id) {
                // Only owner can edit
                return (int) $owner_id === (int) $user_id;
            }
        }

        return false;
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
     * Get validations that have been published to the ideas library.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public function get_published_validations($args = array()) {
        $defaults = array(
            'min_score'      => 0,
            'enriched_only'  => false,
            'search'         => '',
            'order_by'       => 'published_at',
            'order'          => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);

        global $wpdb;
        $table = pl_get_validation_table_name(is_multisite() ? 'network' : 'site');

        $where = "WHERE library_published = 1 AND validation_status = 'completed'";

        if (!empty($args['min_score'])) {
            $where .= $wpdb->prepare(' AND validation_score >= %d', (int) $args['min_score']);
        }

        if (!empty($args['enriched_only'])) {
            $where .= " AND enrichment_status = 'completed'";
        }

        if (!empty($args['search'])) {
            $where .= $wpdb->prepare(' AND business_idea LIKE %s', '%' . $wpdb->esc_like($args['search']) . '%');
        }

        $order_by_allowed = array('validation_score', 'published_at', 'created_at');
        $order_by = in_array($args['order_by'], $order_by_allowed, true) ? $args['order_by'] : 'published_at';
        $order = (isset($args['order']) && 'ASC' === strtoupper($args['order'])) ? 'ASC' : 'DESC';

        $sql = "SELECT * FROM $table $where ORDER BY $order_by $order";

        return $wpdb->get_results($sql);
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
        $tables = array(pl_get_validation_table_name('site'));

        if (is_multisite()) {
            $tables[] = pl_get_validation_table_name('network');
            $tables = array_unique($tables);
        }

        foreach ($tables as $table) {
            $record = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $validation_id
            ));

            if ($record) {
                return $record;
            }
        }

        return false;
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

        if (function_exists('pl_clear_library_cache')) {
            pl_clear_library_cache();
        }

        return true;
    }

    /**
     * Toggle the publish flag for a validation entry.
     *
     * @param int  $validation_id Validation ID.
     * @param bool $publish       Publish status.
     * @param int  $user_id       Acting user ID.
     * @return bool
     */
    public function set_library_publish_status($validation_id, $publish, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!user_can($user_id, 'manage_options') && !user_can($user_id, 'manage_network_options')) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'pl_validations';
        $publish_flag = $publish ? 1 : 0;

        $data = array(
            'library_published' => $publish_flag,
        );
        $format = array('%d');

        if ($publish_flag) {
            $data['published_at'] = current_time('mysql');
            $format[] = '%s';
        } else {
            $data['published_at'] = null;
            $format[] = '%s';
        }

        $result = $wpdb->update(
            $table,
            $data,
            array('id' => $validation_id),
            $format,
            array('%d')
        );

        if (false === $result) {
            return false;
        }

        if (!$publish_flag) {
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE $table SET published_at = NULL WHERE id = %d",
                    $validation_id
                )
            );
        }

        if (function_exists('pl_clear_library_cache')) {
            pl_clear_library_cache();
        }

        return true;
    }
}
