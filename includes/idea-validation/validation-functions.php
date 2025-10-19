<?php
/**
 * Validation Helper Functions
 */

if (!defined('ABSPATH')) exit;

/**
 * Retrieve the appropriate validations table name based on context.
 *
 * @param string|int $context Table context. Accepts 'site', 'network', or a site ID.
 *
 * @return string
 */
function pl_get_validation_table_name($context = 'site') {
    global $wpdb;

    if (is_multisite()) {
        if ('network' === $context) {
            return $wpdb->base_prefix . 'pl_validations';
        }

        if (is_numeric($context)) {
            return $wpdb->get_blog_prefix((int) $context) . 'pl_validations';
        }
    }

    return $wpdb->prefix . 'pl_validations';
}

/**
 * Retrieve the appropriate validation enrichment table name based on context.
 *
 * @param string|int $context Table context. Accepts 'site', 'network', or a site ID.
 *
 * @return string
 */
function pl_get_validation_enrichment_table_name($context = 'site') {
    global $wpdb;

    if (is_multisite()) {
        if ('network' === $context) {
            return $wpdb->base_prefix . 'pl_validation_enrichment';
        }

        if (is_numeric($context)) {
            return $wpdb->get_blog_prefix((int) $context) . 'pl_validation_enrichment';
        }
    }

    return $wpdb->prefix . 'pl_validation_enrichment';
}

/**
 * Retrieve validation setting with multisite awareness.
 */
function pl_get_validation_option($option_name, $default = '') {
    if (is_multisite()) {
        return get_site_option($option_name, $default);
    }

    return get_option($option_name, $default);
}

/**
 * Update validation setting with multisite awareness.
 */
function pl_update_validation_option($option_name, $value) {
    if (is_multisite()) {
        return update_site_option($option_name, $value);
    }

    return update_option($option_name, $value);
}

/**
 * Get user's quota information
 */
function pl_get_user_quota($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $quota = new PL_Validation_Quota();
    return $quota->get_quota_info($user_id);
}

/**
 * Check if user can validate
 */
function pl_can_user_validate($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $quota = new PL_Validation_Quota();
    return $quota->can_validate($user_id);
}

/**
 * Get user's validations
 */
function pl_get_user_validations($user_id = null, $args = array()) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $access = new PL_Validation_Access();
    return $access->get_user_validations($user_id, $args);
}

/**
 * Get validation by ID
 */
function pl_get_validation($validation_id, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $access = new PL_Validation_Access();
    return $access->get_validation($validation_id, $user_id);
}

/**
 * Format validation score for display
 */
function pl_format_validation_score($score) {
    $score = (int)$score;

    if ($score >= 70) {
        return array(
            'score' => $score,
            'label' => 'Strong Potential',
            'class' => 'success',
            'color' => '#10B981'
        );
    } elseif ($score >= 40) {
        return array(
            'score' => $score,
            'label' => 'Moderate Potential',
            'class' => 'warning',
            'color' => '#F59E0B'
        );
    } else {
        return array(
            'score' => $score,
            'label' => 'Needs Work',
            'class' => 'danger',
            'color' => '#EF4444'
        );
    }
}

/**
 * Retrieve the list of cached library payload keys.
 *
 * @return array
 */
function pl_get_library_cache_keys() {
    $option_name = 'pl_library_cache_keys';
    $keys = is_multisite() ? get_site_option($option_name, array()) : get_option($option_name, array());

    if (!is_array($keys)) {
        $keys = array();
    }

    return $keys;
}

/**
 * Register a cache key so it can be cleared later.
 *
 * @param string $key Cache key.
 */
function pl_register_library_cache_key($key) {
    $keys = pl_get_library_cache_keys();

    if (in_array($key, $keys, true)) {
        return;
    }

    $keys[] = $key;
    $option_name = 'pl_library_cache_keys';

    if (is_multisite()) {
        update_site_option($option_name, $keys);
    } else {
        update_option($option_name, $keys);
    }
}

/**
 * Clear all cached library payloads.
 */
function pl_clear_library_cache() {
    $option_name = 'pl_library_cache_keys';
    $keys = pl_get_library_cache_keys();

    if (!empty($keys)) {
        foreach ($keys as $key) {
            if (is_multisite()) {
                delete_site_transient($key);
            } else {
                delete_transient($key);
            }
        }
    }

    if (is_multisite()) {
        update_site_option($option_name, array());
    } else {
        update_option($option_name, array());
    }
}
