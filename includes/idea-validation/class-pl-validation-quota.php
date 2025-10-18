<?php
/**
 * Validation Quota Manager
 * Handles user validation limits and quota tracking
 */

if (!defined('ABSPATH')) exit;

class PL_Validation_Quota {
    
    /**
     * Check if user can validate (has quota remaining)
     */
    public function can_validate($user_id, $site_id = null) {
        if (!$site_id) {
            $site_id = get_current_blog_id();
        }
        
        // Check if user has unlimited access (admin or specific role)
        if ($this->has_unlimited_access($user_id)) {
            return true;
        }
        
        $limit = $this->get_user_limit($user_id, $site_id);
        $used = $this->get_used_validations($user_id, $site_id);
        
        return $used < $limit;
    }
    
    /**
     * Get user's validation limit for current month
     */
    public function get_user_limit($user_id, $site_id = null) {
        if (!$site_id) {
            $site_id = get_current_blog_id();
        }
        
        // Check if unlimited
        if ($this->has_unlimited_access($user_id)) {
            return PHP_INT_MAX;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'pl_validation_quota';
        $month_year = date('Y-m');
        
        $limit = $wpdb->get_var($wpdb->prepare(
            "SELECT validations_limit FROM $table 
             WHERE user_id = %d AND site_id = %d AND month_year = %s",
            $user_id, $site_id, $month_year
        ));
        
        // Default limit: 3 per month for free users
        return $limit !== null ? (int)$limit : 3;
    }
    
    /**
     * Get number of validations used this month
     */
    public function get_used_validations($user_id, $site_id = null) {
        if (!$site_id) {
            $site_id = get_current_blog_id();
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'pl_validation_quota';
        $month_year = date('Y-m');
        
        $used = $wpdb->get_var($wpdb->prepare(
            "SELECT validations_used FROM $table 
             WHERE user_id = %d AND site_id = %d AND month_year = %s",
            $user_id, $site_id, $month_year
        ));
        
        return $used !== null ? (int)$used : 0;
    }
    
    /**
     * Increment validation usage
     */
    public function increment_usage($user_id, $site_id = null) {
        if (!$site_id) {
            $site_id = get_current_blog_id();
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'pl_validation_quota';
        $month_year = date('Y-m');
        
        // Check if record exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table 
             WHERE user_id = %d AND site_id = %d AND month_year = %s",
            $user_id, $site_id, $month_year
        ));
        
        if ($exists) {
            // Increment existing
            $wpdb->query($wpdb->prepare(
                "UPDATE $table SET validations_used = validations_used + 1 
                 WHERE id = %d",
                $exists
            ));
        } else {
            // Create new record
            $wpdb->insert($table, array(
                'user_id' => $user_id,
                'site_id' => $site_id,
                'month_year' => $month_year,
                'validations_used' => 1,
                'validations_limit' => 3 // Default limit
            ));
        }
        
        return true;
    }
    
    /**
     * Check if user has unlimited access
     */
    public function has_unlimited_access($user_id) {
        // Admins get unlimited
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        // Check for premium membership (customize based on your membership plugin)
        // Example: if using Paid Memberships Pro
        if (function_exists('pmpro_hasMembershipLevel')) {
            $premium_levels = array(2, 3, 4); // Adjust based on your membership levels
            if (pmpro_hasMembershipLevel($premium_levels, $user_id)) {
                return true;
            }
        }
        
        return apply_filters('pl_validation_unlimited_access', false, $user_id);
    }
    
    /**
     * Set custom limit for user
     */
    public function set_user_limit($user_id, $limit, $site_id = null) {
        if (!$site_id) {
            $site_id = get_current_blog_id();
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'pl_validation_quota';
        $month_year = date('Y-m');
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table 
             WHERE user_id = %d AND site_id = %d AND month_year = %s",
            $user_id, $site_id, $month_year
        ));
        
        if ($exists) {
            $wpdb->update(
                $table,
                array('validations_limit' => $limit),
                array('id' => $exists)
            );
        } else {
            $wpdb->insert($table, array(
                'user_id' => $user_id,
                'site_id' => $site_id,
                'month_year' => $month_year,
                'validations_used' => 0,
                'validations_limit' => $limit
            ));
        }
        
        return true;
    }
    
    /**
     * Get quota info for display
     */
    public function get_quota_info($user_id, $site_id = null) {
        if (!$site_id) {
            $site_id = get_current_blog_id();
        }
        
        $unlimited = $this->has_unlimited_access($user_id);
        $used = $this->get_used_validations($user_id, $site_id);
        $limit = $this->get_user_limit($user_id, $site_id);
        $remaining = $unlimited ? PHP_INT_MAX : max(0, $limit - $used);
        
        return array(
            'unlimited' => $unlimited,
            'used' => $used,
            'limit' => $limit,
            'remaining' => $remaining,
            'can_validate' => $unlimited || $remaining > 0,
            'month' => date('F Y')
        );
    }
}
