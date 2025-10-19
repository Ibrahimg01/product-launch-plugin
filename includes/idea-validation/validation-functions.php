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
 * Retrieve the default set of library categories.
 *
 * @return array[]
 */
function pl_get_default_library_categories() {
    return array(
        array('slug' => 'saas', 'label' => __('SaaS', 'product-launch')),
        array('slug' => 'marketplace', 'label' => __('Marketplace', 'product-launch')),
        array('slug' => 'ecommerce', 'label' => __('E-commerce', 'product-launch')),
        array('slug' => 'ai-ml', 'label' => __('AI/ML', 'product-launch')),
        array('slug' => 'mobile', 'label' => __('Mobile App', 'product-launch')),
        array('slug' => 'productivity', 'label' => __('Productivity', 'product-launch')),
        array('slug' => 'education', 'label' => __('Education', 'product-launch')),
        array('slug' => 'health', 'label' => __('Health & Wellness', 'product-launch')),
    );
}

/**
 * Retrieve the configured library categories.
 *
 * @return array[]
 */
function pl_get_library_categories() {
    $stored = pl_get_validation_option('pl_library_categories', array());

    if (!is_array($stored) || empty($stored)) {
        $stored = pl_get_default_library_categories();
    }

    $categories = array();

    foreach ($stored as $category) {
        if (!is_array($category)) {
            continue;
        }

        $slug = isset($category['slug']) ? sanitize_title($category['slug']) : '';
        $label = isset($category['label']) ? wp_kses_post($category['label']) : '';

        if ('' === $slug || '' === $label) {
            continue;
        }

        $categories[$slug] = array(
            'slug'  => $slug,
            'label' => $label,
        );
    }

    if (empty($categories)) {
        $defaults = pl_get_default_library_categories();

        foreach ($defaults as $category) {
            $slug = isset($category['slug']) ? sanitize_title($category['slug']) : '';
            $label = isset($category['label']) ? wp_kses_post($category['label']) : '';

            if ('' === $slug || '' === $label) {
                continue;
            }

            $categories[$slug] = array(
                'slug'  => $slug,
                'label' => $label,
            );
        }
    }

    return apply_filters('pl_library_categories', array_values($categories));
}

/**
 * Persist the configured library categories.
 *
 * @param array $categories Array of category arrays containing slug and label keys.
 * @return bool
 */
function pl_update_library_categories($categories) {
    return pl_update_validation_option('pl_library_categories', $categories);
}

/**
 * Retrieve the custom category assignment map.
 *
 * @return array
 */
function pl_get_library_category_map() {
    $map = pl_get_validation_option('pl_library_category_map', array());

    if (!is_array($map)) {
        return array();
    }

    $normalized = array();

    foreach ($map as $idea_key => $slugs) {
        $idea_key = sanitize_text_field($idea_key);

        if ('' === $idea_key) {
            continue;
        }

        if (!is_array($slugs)) {
            $slugs = array($slugs);
        }

        $sanitized = array();

        foreach ($slugs as $slug) {
            $slug = sanitize_title($slug);

            if ('' === $slug) {
                continue;
            }

            $sanitized[] = $slug;
        }

        if ($sanitized) {
            $normalized[$idea_key] = array_values(array_unique($sanitized));
        }
    }

    return $normalized;
}

/**
 * Persist the custom category assignment map.
 *
 * @param array $map Category assignment map.
 * @return bool
 */
function pl_update_library_category_map($map) {
    return pl_update_validation_option('pl_library_category_map', $map);
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
