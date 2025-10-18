<?php

if ( ! function_exists('pl_get_asset_version') ) {
    function pl_get_asset_version( $rel ) {
        $abs = plugin_dir_path(__FILE__) . $rel;
        return file_exists($abs) ? filemtime($abs) : '2.3.56';
    }
}

require_once __DIR__ . '/includes/security/ajax-guard.php';

// === Compatibility shim: pl_test_openai_connection() ===
// Ensures "Test API Connection" works even if helper is missing elsewhere.
// Returns associative array: ['ok'=>bool, 'error'=>string|null, 'http_code'=>int|null]
if (!function_exists('pl_test_openai_connection')) {
    function pl_test_openai_connection($api_key = '', $model = '', $timeout = 30) {
    // Fetch network-wide settings if not provided
    if (empty($api_key)) {
        $opts = get_site_option('pl_network_settings', array());
        $api_key = isset($opts['openai_api_key']) ? trim($opts['openai_api_key']) : '';
        if (empty($model)) {
            $model = isset($opts['ai_model']) ? $opts['ai_model'] : 'gpt-4o-mini';
        }
        if (empty($timeout)) {
            $timeout = isset($opts['api_timeout']) ? intval($opts['api_timeout']) : 30;
        }
    }
    if (empty($api_key)) {
        return array('ok'=>false, 'success'=>false, 'message'=>'Missing OpenAI API key.', 'error'=>'Missing OpenAI API key.', 'http_code'=>null);
    }
    if (empty($model)) { $model = 'gpt-4o-mini'; }
    if (!function_exists('wp_remote_post')) {
        return array('ok'=>false, 'success'=>false, 'message'=>'WP HTTP API unavailable.', 'error'=>'WP HTTP API unavailable.', 'http_code'=>null);
    }
    $endpoint = 'https://api.openai.com/v1/chat/completions';
    $body = array(
        'model' => $model,
        'messages' => array(
            array('role'=>'system', 'content'=>'You are a health check.'),
            array('role'=>'user', 'content'=>'Respond with OK'),
        ),
        'max_tokens' => 2,
        'temperature' => 0
    );
    $args = array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ),
        'body'    => wp_json_encode($body),
        'timeout' => max(5, intval($timeout)),
    );
    $resp = wp_remote_post($endpoint, $args);
    if (is_wp_error($resp)) {
        $msg = $resp->get_error_message();
        return array('ok'=>false, 'success'=>false, 'message'=>$msg, 'error'=>$msg, 'http_code'=>null);
    }
    $code = wp_remote_retrieve_response_code($resp);
    $raw  = wp_remote_retrieve_body($resp);
    if ($code >= 200 and $code < 300) {
        return array('ok'=>true, 'success'=>true, 'message'=>'OK', 'error'=>null, 'http_code'=>$code);
    }
    $msg = 'HTTP ' . $code;
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && isset($decoded['error']['message'])) {
            $msg .= ' - ' . $decoded['error']['message'];
        }
    }
    return array('ok'=>false, 'success'=>false, 'message'=>$msg, 'error'=>$msg, 'http_code'=>$code);
}
}
// ============================================
// SECURITY: AI Response Sanitization (v1.4.0)
// ============================================

/**
 * Sanitize AI responses to prevent XSS attacks
 * 
 * This function strips all HTML tags from AI-generated content
 * to prevent potential XSS if OpenAI API is compromised or returns malicious content
 * 
 * @param string $response Raw AI response
 * @param bool $allow_basic_formatting Whether to allow basic markdown-style formatting
 * @return string Sanitized response
 */
function pl_sanitize_ai_response($response, $allow_basic_formatting = true) {
    if (empty($response) || !is_string($response)) {
        return '';
    }
    
    // Strip all HTML tags first
    $sanitized = wp_strip_all_tags($response, true);
    
    // Optionally preserve basic markdown-style formatting for display
    if ($allow_basic_formatting) {
        // Convert markdown-style bold to safe HTML entity representation
        $sanitized = preg_replace('/\*\*(.*?)\*\*/', '⟦b⟧$1⟦/b⟧', $sanitized);
        // Convert markdown-style italic to safe representation
        $sanitized = preg_replace('/\*(.*?)\*/', '⟦i⟧$1⟦/i⟧', $sanitized);
    }
    
    // Remove any remaining control characters
    $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $sanitized);
    
    // Limit length to prevent memory issues
    $max_length = 50000; // 50KB max
    if (strlen($sanitized) > $max_length) {
        $sanitized = substr($sanitized, 0, $max_length) . '... [truncated]';
    }
    
    return trim($sanitized);
}

/**
 * Validate and sanitize user input for AI queries
 *
 * @param string $input User input
 * @param int $max_length Maximum allowed length
 * @return string|WP_Error Sanitized input or error
 */
function pl_sanitize_user_input($input, $max_length = 5000) {
    if (!is_string($input)) {
        return new WP_Error('invalid_input', __('Input must be a string', 'product-launch'));
    }
    
    // Remove any HTML/script tags
    $sanitized = wp_strip_all_tags($input, true);
    
    // Check length
    if (strlen($sanitized) > $max_length) {
        return new WP_Error('input_too_long', sprintf(
            __('Input exceeds maximum length of %d characters', 'product-launch'),
            $max_length
        ));
    }
    
    // Remove excessive whitespace
    $sanitized = preg_replace('/\s+/', ' ', $sanitized);
    
    return trim($sanitized);
}


/**
 * Plugin Name: Product Launch Coach Enhanced
 * Plugin URI:  https://informationsystems.io/
 * Description: AI-powered 8-phase product launch coaching workflow with real OpenAI integration, sophisticated prompting, and contextual assistance.
 * Version: 2.3.56
 * Author:      Information Systems
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: product-launch
 */

if (!defined('ABSPATH')) exit;

// === Enhanced AI Memory + Migration (Claude) – conditional includes to avoid redeclare ===
if (!function_exists('pl_generate_ai_response_enhanced')) {
    require_once __DIR__ . '/includes/enhanced-ai-memory.php';
}
if (!function_exists('pl_migrate_to_enhanced_memory')) {
    require_once __DIR__ . '/includes/database-migration.php';
}
if (function_exists('pl_migrate_to_enhanced_memory')) {
    register_activation_hook(__FILE__, 'pl_migrate_to_enhanced_memory');
}



define('PL_PLUGIN_VERSION', '2.3.56');
define('PL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PL_PLUGIN_URL', plugin_dir_url(__FILE__));

define('PL_OPTION_GROUP', 'pl_options');
define('PL_OPTION_NAME', 'pl_settings');
define('PL_NONCE_ACTION', 'pl_nonce_action');

// Validation system classes
require_once PL_PLUGIN_DIR . 'includes/idea-validation/class-pl-validation-api.php';
require_once PL_PLUGIN_DIR . 'includes/idea-validation/class-pl-validation-quota.php';
require_once PL_PLUGIN_DIR . 'includes/idea-validation/class-pl-validation-access.php';
require_once PL_PLUGIN_DIR . 'includes/idea-validation/validation-functions.php';

/**
 * Enhanced activation with proper database schema
 */
function pl_activate($network_wide) {
    if (is_multisite() && $network_wide) {
        $sites = get_sites(array('fields' => 'ids'));
        foreach ($sites as $blog_id) {
            switch_to_blog($blog_id);
            pl_create_tables();
            pl_create_validation_tables();
            restore_current_blog();
        }
    } else {
        pl_create_tables();
        pl_create_validation_tables();
    }
}
register_activation_hook(__FILE__, 'pl_activate');

function pl_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Fixed table name consistency
    $table = $wpdb->prefix . 'product_launch_conversations';
    $sql = "CREATE TABLE IF NOT EXISTS `$table` (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NULL,
        site_id BIGINT UNSIGNED NULL DEFAULT 1,
        phase VARCHAR(64) NOT NULL,
        message_count INT DEFAULT 0,
        history LONGTEXT NULL,
        context_data LONGTEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_phase_site (user_id, phase, site_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

/**
 * Create validation tracking tables
 */
function pl_create_validation_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Main validations table
    $table_validations = $wpdb->prefix . 'pl_validations';
    $sql_validations = "CREATE TABLE IF NOT EXISTS `$table_validations` (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        site_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
        business_idea TEXT NOT NULL,
        external_api_id VARCHAR(255) NULL,
        validation_score INT DEFAULT 0,
        confidence_level VARCHAR(50) NULL,
        validation_status VARCHAR(50) DEFAULT 'pending',
        enrichment_status VARCHAR(50) DEFAULT 'pending',
        core_data LONGTEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_site (user_id, site_id),
        KEY external_id (external_api_id),
        KEY status (validation_status),
        KEY created (created_at)
    ) $charset_collate;";

    // Enrichment cache table
    $table_enrichment = $wpdb->prefix . 'pl_validation_enrichment';
    $sql_enrichment = "CREATE TABLE IF NOT EXISTS `$table_enrichment` (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        validation_id BIGINT UNSIGNED NOT NULL,
        section_type VARCHAR(50) NOT NULL,
        section_data LONGTEXT NULL,
        fetched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY validation_section (validation_id, section_type),
        KEY fetched (fetched_at)
    ) $charset_collate;";

    // User validation quota tracking
    $table_quota = $wpdb->prefix . 'pl_validation_quota';
    $sql_quota = "CREATE TABLE IF NOT EXISTS `$table_quota` (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        site_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
        month_year VARCHAR(7) NOT NULL,
        validations_used INT DEFAULT 0,
        validations_limit INT DEFAULT 3,
        PRIMARY KEY (id),
        UNIQUE KEY user_month (user_id, site_id, month_year)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_validations);
    dbDelta($sql_enrichment);
    dbDelta($sql_quota);
}

/**
 * Enhanced settings with better validation
 */

if (!function_exists('pl_get_settings')) {
function pl_get_settings() {
    if (is_multisite()) {
        $opts = get_site_option(PL_OPTION_NAME, []);
    } else {
        $opts = get_option(PL_OPTION_NAME, []);
    }
    $defaults = [
        'openai_key' => '',
        'ai_model' => 'gpt-4o-mini',
        'rate_limit_per_min' => 20,
        'timeout' => 30,
        'max_tokens' => 1000,
        'temperature' => 0.7,
    ];
    return wp_parse_args($opts, $defaults);

}
}


function pl_field_openai_key() {
    $s = pl_get_settings();
    $val = esc_attr($s['openai_key']);
    echo '<input type="password" id="openai_key" name="'.PL_OPTION_NAME.'[openai_key]" value="'.$val.'" class="regular-text" placeholder="sk-..." /> ';
    echo '<button class="button toggle-api-key" type="button"><span class="dashicons dashicons-visibility"></span> ' . esc_html__('Show', 'product-launch') . '</button>';
    echo '<p class="description">'.esc_html__('Your OpenAI API key. Get one at platform.openai.com', 'product-launch').'</p>';
}

function pl_field_ai_model() {
    $s = pl_get_settings();
    $models = [
        'gpt-4o-mini' => 'GPT-4o Mini (Recommended)',
        'gpt-4o' => 'GPT-4o (Premium)',
        'gpt-4-turbo' => 'GPT-4 Turbo',
        'gpt-3.5-turbo' => 'GPT-3.5 Turbo'
    ];
    echo '<select id="ai_model" name="'.PL_OPTION_NAME.'[ai_model]">';
    foreach ($models as $k => $label) {
        printf('<option value="%s"%s>%s</option>', esc_attr($k), selected($s['ai_model'], $k, false), esc_html($label));
    }
    echo '</select>';
}

function pl_field_max_tokens() {
    $s = pl_get_settings();
    printf('<input type="number" min="100" max="4000" id="max_tokens" name="%s[max_tokens]" value="%d" class="small-text" />',
        PL_OPTION_NAME, intval($s['max_tokens']));
    echo '<p class="description">Maximum tokens for AI responses (100-4000)</p>';
}

function pl_field_temperature() {
    $s = pl_get_settings();
    printf('<input type="number" min="0" max="2" step="0.1" id="temperature" name="%s[temperature]" value="%.1f" class="small-text" />',
        PL_OPTION_NAME, floatval($s['temperature']));
    echo '<p class="description">AI creativity level (0.0-2.0, higher = more creative)</p>';
}

function pl_field_rate_limit() {
    $s = pl_get_settings();
    printf('<input type="number" min="1" max="100" id="rate_limit_per_min" name="%s[rate_limit_per_min]" value="%d" class="small-text" />',
        PL_OPTION_NAME, intval($s['rate_limit_per_min']));
}

function pl_field_timeout() {
    $s = pl_get_settings();
    printf('<input type="number" min="5" max="60" id="timeout" name="%s[timeout]" value="%d" class="small-text" />',
        PL_OPTION_NAME, intval($s['timeout']));
}

/**
 * Menu setup (unchanged)
 */
/**
 * Phase subpages (unchanged)
 */
function pl_render_phase_page($slug, $title, $template_file) {
    echo '<div class="wrap">';
    $file = PL_PLUGIN_DIR . 'templates/' . $template_file;
    if (file_exists($file)) {
        include $file;
    } else {
        echo '<div class="ai-coach-section">';
        echo '<div class="coach-avatar">🤖</div>';
        echo '<p>'.esc_html__('Use the AI coach below to work this phase.','product-launch').'</p>';
        echo '<a href="#" class="button button-primary start-ai-coaching" data-phase="'.esc_attr(pl_phase_from_slug($slug)).'">'.esc_html__('Start AI Coaching','product-launch').'</a>';
        echo '</div>';
    }
    echo '</div>';
}

function pl_phase_from_slug($slug) {
    $map = [
        'product-launch-market'  => 'market_clarity',
        'product-launch-offer'   => 'create_offer',
        'product-launch-service' => 'create_service',
        'product-launch-funnel'  => 'build_funnel',
        'product-launch-emailss'  => 'email_sequences',
        'product-launch-organic' => 'organic_posts',
        'product-launch-ads'     => 'facebook_ads',
        'product-launch-launch'  => 'launch',
    ];
    return isset($map[$slug]) ? $map[$slug] : '';
}

/**
 * Assets (unchanged)
 */
add_action('admin_enqueue_scripts', function($hook){
    if (strpos($hook, 'product-launch') === false) return;
    wp_enqueue_style('pl-css', PL_PLUGIN_URL.'assets/css/product-launch.css', [], PL_PLUGIN_VERSION);
    wp_enqueue_script('pl-js', PL_PLUGIN_URL.'assets/js/product-launch.js', ['jquery'], PL_PLUGIN_VERSION, true);
    wp_localize_script('pl-js', 'productLaunch', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce(PL_NONCE_ACTION),
    ]);
});

/**
 * Dashboard and settings rendering (unchanged)
 */
function pl_render_dashboard() {
    echo '<div class="wrap">';
    $file = PL_PLUGIN_DIR . 'templates/dashboard.php';
    if (file_exists($file)) {
        include $file;
    } else {
        echo '<p>'.esc_html__('Dashboard template missing. Using fallback.', 'product-launch').'</p>';
        echo '<a href="#" class="button start-ai-coaching" data-phase="market_clarity">'.esc_html__('Start Coaching','product-launch').'</a>';
    }
    echo '</div>';
}

function pl_render_settings_page() {
        // Extra security: super admins only on multisite; admins on single site
    if ( is_multisite() && ! is_super_admin() ) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'product-launch'));
    }
    if ( ! current_user_can('manage_options') ) {
        wp_die(__('Insufficient permissions.','product-launch'));
    }
if (!current_user_can('manage_options')) wp_die(__('Insufficient permissions.','product-launch'));
    echo '<div class="wrap"><h1>'.esc_html__('Product Launch – Settings','product-launch').'</h1>';
    echo is_network_admin() ? '<form method="post" action="edit.php?action=update">' : '<form method="post" action="options.php">';
    if (is_network_admin()) { wp_nonce_field(PL_OPTION_GROUP . '-options'); } else { settings_fields(PL_OPTION_GROUP); }
    do_settings_sections('product-launch-settings');
    submit_button();
    echo '</form>';
    echo '<p><button class="button button-primary test-api-connection" type="button">'.esc_html__('Test API Connection','product-launch').'</button></p>';
    echo '</div>';
}

/**
 * ENHANCED AI COACHING SYSTEM
 */

/**
 * Main chat handler with real OpenAI integration
 */


/**
 * PRIORITY 1 FIXES - AJAX HANDLERS
 * Replace the existing AJAX handler section with this code
 * Location: Around line 350-500 in product_launch_main.php
 */

/**
 * Main chat handler with real OpenAI integration
 * UPDATED: v1.4.0 - Added response sanitization
 */
add_action('wp_ajax_product_launch_chat', 'pl_ajax_chat');
function pl_ajax_chat() {
    PL_Ajax_Guard::guard('chat', 'nonce', 20, 60);

    // Security checks
    if (!wp_verify_nonce($_POST['nonce'] ?? '', PL_NONCE_ACTION)) {
        wp_send_json_error(__('Invalid nonce','product-launch'));
    }
    
    if (!current_user_can('read')) {
        wp_send_json_error(__('Unauthorized','product-launch'));
    }

    // Input validation with size limits
    $history_json = wp_unslash($_POST['history'] ?? '[]');
    $context_json = wp_unslash($_POST['context'] ?? '{}');
    
    if (strlen($history_json) > 500000) {
        wp_send_json_error(__('History data too large','product-launch'));
    }
    if (strlen($context_json) > 100000) {
        wp_send_json_error(__('Context data too large','product-launch'));
    }

    $phase = sanitize_text_field($_POST['phase'] ?? '');
    $message_raw = wp_unslash($_POST['message'] ?? '');
    
    // ✅ NEW: Sanitize user input
    $message = pl_sanitize_user_input($message_raw, 5000);
    if (is_wp_error($message)) {
        wp_send_json_error($message->get_error_message());
    }
    
    $history = json_decode($history_json, true);
    $context = json_decode($context_json, true);
    
    if (!is_array($history)) $history = [];
    if (!is_array($context)) $context = [];
    
    // Limit history array size
    if (count($history) > 100) {
        $history = array_slice($history, -100);
    }

    // Rate limiting
    $user_id = get_current_user_id();
    $limit = intval(pl_get_settings()['rate_limit_per_min']);
    if ($limit > 0) {
        $key = 'pl_rate_' . $user_id;
        $now = time();
        $window = 60;
        $bucket = get_transient($key);
        if (!is_array($bucket)) $bucket = [];
        $bucket = array_filter($bucket, function($t) use ($now, $window){ 
            return ($now - $t) < $window; 
        });
        if (count($bucket) >= $limit) {
            wp_send_json_error(__('Rate limit exceeded. Try again in a minute.','product-launch'));
        }
        $bucket[] = $now;
        set_transient($key, $bucket, 2 * MINUTE_IN_SECONDS);
    }

    // Generate AI response
    $reply = pl_generate_ai_response_enhanced($phase, $message, $history, $context);

    $reply_sanitized = '';

    // ✅ NEW: Sanitize AI response before sending to frontend
    if ($reply !== false) {
        $reply_sanitized = pl_sanitize_ai_response($reply, true);
    }
    
    // Add contextual greeting on first turn
    if (empty($history)) {
        $user_id_g = get_current_user_id();
        $site_id_g = get_current_blog_id();
        if (function_exists('pl_get_user_launch_context') && function_exists('pl_get_contextual_greeting')) {
            $launch_context_g = pl_get_user_launch_context($user_id_g, $site_id_g);
            if (!empty($launch_context_g)) {
                $greet_g = pl_get_contextual_greeting($phase, $launch_context_g);
                if (!empty($greet_g)) {
                    $greet_sanitized = pl_sanitize_ai_response($greet_g, true);
                    $reply_sanitized = $greet_sanitized . "\n\n" . $reply_sanitized;
                }
            }
        }
    }

    if ($reply === false) {
        wp_send_json_error(__('Failed to generate AI response. Please check your settings.','product-launch'));
    }

    // Persist conversation
    pl_save_conversation($user_id, $phase, $message, $reply_sanitized, $history, $context);

    wp_send_json_success($reply_sanitized);
}
/**
/**
 * Enhanced field assistance with contextual AI
 * UPDATED: v1.4.0 - Added input/output sanitization
 */
add_action('wp_ajax_product_launch_field_assist', function() {
    PL_Ajax_Guard::guard('product_launch_field_assist', 'nonce', 20, 60);
    
    if (!wp_verify_nonce($_POST['nonce'] ?? '', PL_NONCE_ACTION)) {
        wp_send_json_error('Invalid nonce', 400);
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Unauthorized', 401);
    }

    // Input size validation
    $context_json = wp_unslash($_POST['context'] ?? '{}');
    if (strlen($context_json) > 100000) {
        wp_send_json_error('Context data too large', 400);
    }

    $user_id = get_current_user_id();
    $phase = sanitize_text_field($_POST['phase'] ?? 'general');
    $field_id = sanitize_text_field($_POST['field_id'] ?? '');
    
    $context = json_decode($context_json, true);
    if (!is_array($context)) $context = [];

    // Add field label to context for better AI prompting
    if (isset($_POST['field_label'])) {
        $context['field_label'] = sanitize_text_field($_POST['field_label']);
    }

    // Rate limiting
    $limit = intval(pl_get_settings()['rate_limit_per_min']);
    if ($limit > 0) {
        $key = 'pl_rate_' . $user_id;
        $now = time();
        $window = 60;
        $bucket = get_transient($key);
        if (!is_array($bucket)) $bucket = [];
        $bucket = array_filter($bucket, function($t) use ($now,$window){ 
            return ($now - $t) < $window; 
        });
        if (count($bucket) >= $limit) {
            wp_send_json_error('Rate limit exceeded. Please try again later.', 429);
        }
        $bucket[] = $now;
        set_transient($key, $bucket, 2 * MINUTE_IN_SECONDS);
    }

    $reply = pl_generate_field_assistance($phase, $field_id, $context);
    
    // ✅ NEW: Sanitize field assistance response
    if ($reply !== false) {
        $reply = pl_sanitize_ai_response($reply, false); // No formatting for fields
    }
    
    if ($reply === false) {
        wp_send_json_error('Failed to get AI assistance', 500);
    }

    wp_send_json_success($reply);
});
/**
 * Field assistance with chat context
 * FIXED: Nonce first, rate limiting, input validation
 */
add_action('wp_ajax_product_launch_field_assist_with_context', function() {
    PL_Ajax_Guard::guard('product_launch_field_assist_with_context', 'nonce', 20, 60);
// FIX 1: Nonce check FIRST
    if (!wp_verify_nonce($_POST['nonce'] ?? '', PL_NONCE_ACTION)) {
        wp_send_json_error('Invalid nonce', 400);
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Unauthorized', 401);
    }

    // FIX 4: Input size validation
    $context_json = wp_unslash($_POST['context'] ?? '{}');
    $chat_context_json = wp_unslash($_POST['chat_context'] ?? '{}');
    
    if (strlen($context_json) > 100000) {
        wp_send_json_error('Context data too large', 400);
    }
    if (strlen($chat_context_json) > 200000) {
        wp_send_json_error('Chat context data too large', 400);
    }

    $user_id = get_current_user_id();
    $phase = sanitize_text_field($_POST['phase'] ?? 'general');
    $field_id = sanitize_text_field($_POST['field_id'] ?? '');
    
    $context = json_decode($context_json, true);
    $chat_context = json_decode($chat_context_json, true);
    
    // FIX 4: Validate decoded data
    if (!is_array($context)) $context = array();
    if (!is_array($chat_context)) $chat_context = array();

    // FIX 2: Rate limiting
    $settings = function_exists('pl_get_settings') ? pl_get_settings() : array();
    $limit = intval($settings['rate_limit_per_min'] ?? 0);
    if ($limit > 0) {
        $key = 'pl_rate_' . $user_id;
        $now = time();
        $window = 60;
        $bucket = get_transient($key);
        if (!is_array($bucket)) $bucket = array();
        $bucket = array_filter($bucket, function($t) use ($now,$window){ 
            return ($now - $t) < $window; 
        });
        if (count($bucket) >= $limit) {
            wp_send_json_error('Rate limit exceeded. Please try again later.', 429);
        }
        $bucket[] = $now;
        set_transient($key, $bucket, 2 * MINUTE_IN_SECONDS);
    }

    $reply = pl_generate_field_assistance_with_context($phase, $field_id, $context, $chat_context);
    
    if ($reply === false) {
        wp_send_json_error('Failed to get AI assistance', 500);
    }
    
    wp_send_json_success($reply);
});

/**
 * Enhanced API test with actual OpenAI validation
 * FIXED: Nonce first
 */
add_action('wp_ajax_product_launch_test_api', function() {
    PL_Ajax_Guard::guard('product_launch_test_api', 'nonce', 20, 60);
// FIX 1: Nonce check implied by manage_options, but add explicit check
    if (!wp_verify_nonce($_POST['nonce'] ?? '', PL_NONCE_ACTION)) {
        wp_send_json_error(__('Invalid nonce','product-launch'));
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Unauthorized','product-launch'));
    }
    
    $api_key = sanitize_text_field($_POST['api_key'] ?? '');
    if (empty($api_key)) {
        wp_send_json_error(__('Missing API key','product-launch'));
    }
    
    $test_result = pl_test_openai_connection($api_key);
    
    $_ok = isset($test_result['success']) ? (bool)$test_result['success'] : (bool)($test_result['ok'] ?? false);
    if ($_ok) {
        wp_send_json_success(array('message' => $test_result['message'] ?? ($test_result['error'] ?? 'OK')));
    } else {
        wp_send_json_error(array('message' => $test_result['message'] ?? ($test_result['error'] ?? 'Error')));
    }
});

/**
 * Enhanced save progress with better data handling
 * FIXED: Nonce first, input validation
 */
add_action('wp_ajax_product_launch_save_progress', function() {
    PL_Ajax_Guard::guard('product_launch_save_progress', 'nonce', 20, 60);
// FIX 1: Nonce check FIRST
    if (!wp_verify_nonce($_POST['nonce'] ?? '', PL_NONCE_ACTION)) {
        wp_send_json_error(__('Invalid nonce','product-launch'));
    }
    
    if (!current_user_can('read')) {
        wp_send_json_error(__('Unauthorized','product-launch'));
    }
    
    // FIX 4: Input size validation
    $progress_data = wp_unslash($_POST['progress_data'] ?? '{}');
    if (strlen($progress_data) > 500000) {
        wp_send_json_error(__('Progress data too large','product-launch'));
    }
    
    $user_id = get_current_user_id();
    $phase = sanitize_text_field($_POST['phase'] ?? '');
    
    if ($phase && $progress_data) {
        $site_id = get_current_blog_id();
        $meta_key = 'product_launch_progress_' . $phase . '_' . $site_id;
        update_user_meta($user_id, $meta_key, $progress_data);
    }
    
    wp_send_json_success(true);
});


/**
 * Get user context across phases for continuity
 */
if (!function_exists('pl_get_user_launch_context')) {
function pl_get_user_launch_context($user_id, $site_id) {
    $context = array();
    
    // Get ALL phases in order
    $all_phases = array(
        'market_clarity', 
        'create_offer', 
        'create_service', 
        'build_funnel', 
        'email_sequences', 
        'organic_posts', 
        'facebook_ads'
    );
    
    foreach ($all_phases as $phase) {
        $phase_data = get_user_meta($user_id, 'product_launch_progress_' . $phase . '_' . $site_id, true);
        if ($phase_data) {
            $decoded = json_decode($phase_data, true);
            if (is_array($decoded)) {
                foreach ($decoded as $key => $value) {
                    if (!empty($value) && is_string($value) && strlen(trim($value)) > 10 && $key !== 'completed_at') {
                        // Store with phase prefix for clarity
                        $context[$phase . '_' . $key] = substr(trim($value), 0, 300);
                    }
                }
            }
        }
    }
    
    return $context;
}
}

/**
 * Enhanced initial message with context awareness
 */
if (!function_exists('pl_get_contextual_greeting')) {
function pl_get_contextual_greeting($phase, $context) {
    if (empty($context)) {
        return null;
    }
    
    switch ($phase) {
        case 'create_offer':
            if (!empty($context['market_clarity_target_audience']) && !empty($context['market_clarity_pain_points'])) {
                $audience = substr($context['market_clarity_target_audience'], 0, 100);
                $problems = substr($context['market_clarity_pain_points'], 0, 100);
                return "I can see you've identified your target audience: {$audience}... and their main problems: {$problems}...\n\nNow let's create an irresistible offer that solves these specific problems. What type of solution are you thinking of offering?";
            }
            break;
            
        case 'create_service':
            if (!empty($context['create_offer_main_offer'])) {
                $offer = substr($context['create_offer_main_offer'], 0, 120);
                return "Based on your offer: {$offer}...\n\nLet's design the service delivery that will fulfill this promise. What format do you think would work best for your clients?";
            }
            break;
            
        case 'build_funnel':
            if (!empty($context['market_clarity_target_audience']) && !empty($context['create_offer_main_offer'])) {
                $audience = substr($context['market_clarity_target_audience'], 0, 80);
                $offer = substr($context['create_offer_main_offer'], 0, 80);
                return "Perfect! With your target audience ({$audience}...) and main offer ({$offer}...) defined, let's create a funnel that converts prospects to customers.\n\nWhere do your potential customers typically look for solutions like yours?";
            }
            break;
            
        case 'email_sequences':
            $elements = array();
            if (!empty($context['market_clarity_target_audience'])) {
                $elements[] = "target audience";
            }
            if (!empty($context['create_offer_main_offer'])) {
                $elements[] = "main offer";
            }
            if (!empty($context['build_funnel_funnel_strategy'])) {
                $elements[] = "funnel strategy";
            }
            
            if (count($elements) >= 2) {
                return "Great! I can see you've defined your " . implode(', ', $elements) . ". Now let's create email sequences that nurture your prospects through your funnel and convert them into customers.\n\nWhat's your main goal for your email sequence - nurturing cold leads or converting warm prospects?";
            }
            break;
            
        case 'organic_posts':
            $elements = array();
            if (!empty($context['market_clarity_target_audience'])) {
                $elements[] = "target audience";
            }
            if (!empty($context['create_offer_main_offer'])) {
                $elements[] = "offer";
            }
            if (!empty($context['email_sequences_email_strategy'])) {
                $elements[] = "email strategy";
            }
            
            if (count($elements) >= 2) {
                return "Perfect! With your " . implode(', ', $elements) . " in place, let's create organic content that builds anticipation for your launch and drives people into your funnel.\n\nWhich social platforms does your target audience use most?";
            }
            break;
            
        case 'facebook_ads':
            $elements = array();
            if (!empty($context['market_clarity_target_audience'])) {
                $elements[] = "target audience";
            }
            if (!empty($context['build_funnel_funnel_strategy'])) {
                $elements[] = "funnel";
            }
            if (!empty($context['organic_posts_content_strategy'])) {
                $elements[] = "content strategy";
            }
            
            if (count($elements) >= 2) {
                return "Excellent! You've built your " . implode(', ', $elements) . ". Now let's create Facebook ads that drive qualified traffic to your funnel at a profitable cost.\n\nWhat's your estimated advertising budget for this launch?";
            }
            break;
            
        case 'launch':
            $completed_phases = array();
            $phase_names = array(
                'market_clarity' => 'market clarity',
                'create_offer' => 'offer',
                'create_service' => 'service',
                'build_funnel' => 'funnel',
                'email_sequences' => 'email sequences',
                'organic_posts' => 'content strategy',
                'facebook_ads' => 'ad campaigns'
            );
            
            foreach ($phase_names as $phase_key => $phase_name) {
                if (!empty($context[$phase_key . '_' . array_keys($context)[0]])) {
                    $completed_phases[] = $phase_name;
                }
            }
            
            if (count($completed_phases) >= 4) {
                return "Outstanding! You've completed " . implode(', ', $completed_phases) . ". You're ready to launch with confidence.\n\nWhat's your target launch date, and what are you most concerned about for launch day?";
            }
            break;
    }
    
    return null;
}
}



/**
 * Enhanced field assistance with chat context
 */
add_action('wp_ajax_product_launch_field_assist_with_context', function() {
    PL_Ajax_Guard::guard('product_launch_field_assist_with_context', 'nonce', 20, 60);
if (!wp_verify_nonce($_POST['nonce'] ?? '', PL_NONCE_ACTION)) {
        wp_send_json_error('Invalid nonce', 400);
    }
    if (!is_user_logged_in()) {
        wp_send_json_error('Unauthorized', 401);
    }

    $user_id = get_current_user_id();
    $phase = sanitize_text_field($_POST['phase'] ?? 'general');
    $field_id = sanitize_text_field($_POST['field_id'] ?? '');
    $context_json = wp_unslash($_POST['context'] ?? '{}');
    $chat_context_json = wp_unslash($_POST['chat_context'] ?? '{}');
    
    $context = json_decode($context_json, true);
    $chat_context = json_decode($chat_context_json, true);
    if (!is_array($context)) $context = array();
    if (!is_array($chat_context)) $chat_context = array();

    $settings = function_exists('pl_get_settings') ? pl_get_settings() : array();
    $limit = intval($settings['rate_limit_per_min'] ?? 0);
    if ($limit > 0) {
        $key = 'pl_rate_' . $user_id;
        $now = time();
        $window = 60;
        $bucket = get_transient($key);
        if (!is_array($bucket)) $bucket = array();
        $bucket = array_filter($bucket, function($t) use ($now,$window){ return ($now - $t) < $window; });
        if (count($bucket) >= $limit) {
            wp_send_json_error('Rate limit exceeded. Please try again later.', 429);
        }
        $bucket[] = $now;
        set_transient($key, $bucket, 2 * MINUTE_IN_SECONDS);
    }

    $reply = pl_generate_field_assistance_with_context($phase, $field_id, $context, $chat_context);
    if ($reply === false) {
        wp_send_json_error('Failed to get AI assistance', 500);
    }
    wp_send_json_success($reply);
});

/**
 * Enhanced Field Assistance Handler
 * Generates truly field-specific content based on field ID and context
 * Version: 2.3.56 - Fixed duplicate content issue
 */
if (!function_exists('pl_generate_field_assistance')) {
function pl_generate_field_assistance($phase, $field_id, $context = array()) {
    $settings = function_exists('pl_get_settings') ? pl_get_settings() : array();
    $api_key = $settings['openai_key'] ?? '';

    if (empty($api_key)) {
        error_log('PL Field Assist: Missing API key');
        return false;
    }

    $field_prompts = pl_get_enhanced_field_prompts($phase, $field_id, $context);

    if (!isset($field_prompts[$field_id])) {
        error_log("PL Field Assist: No prompt found for field '{$field_id}' in phase '{$phase}'");
        $prompt = pl_get_generic_field_prompt($phase, $field_id, $context);
    } else {
        $prompt = $field_prompts[$field_id];
    }

    $messages = array(
        array(
            'role'    => 'system',
            'content' => pl_get_field_system_prompt($phase, $field_id),
        ),
        array(
            'role'    => 'user',
            'content' => $prompt,
        ),
    );

    if (!function_exists('pl_call_openai_api')) {
        error_log('PL Field Assist: pl_call_openai_api() missing');
        return false;
    }

    $result = pl_call_openai_api($messages, $settings);

    if ($result === false) {
        error_log("PL Field Assist: API call failed for field '{$field_id}'");
    }

    return $result;
}
}

/**
 * Enhanced field-specific system prompts
 * Version: 2.3.56
 */
if (!function_exists('pl_get_field_system_prompt')) {
function pl_get_field_system_prompt($phase, $field_id) {
    $base_prompt = "You are an expert product launch strategist and copywriter. Your task is to generate high-quality, specific content for the '{$field_id}' field in the '{$phase}' phase.";
    $base_prompt .= " When asked to fill multiple fields, format your response clearly with field labels like:\n\n";
    $base_prompt .= "[FIELD_NAME]:\nContent here\n\n";
    $base_prompt .= "[NEXT_FIELD]:\nContent here\n\n";

    $field_instructions = array(
        'target_audience'   => " Write a detailed, specific target audience description. Include demographics, psychographics, behaviors, and where they spend time online. Be concrete and specific - avoid generic descriptions.",
        'pain_points'       => " List 3-5 specific, emotionally resonant pain points. Focus on the daily frustrations and struggles this audience experiences. Make each point distinct and actionable.",
        'value_proposition' => " Create a compelling value proposition that clearly communicates the unique transformation you provide. Focus on outcomes and benefits, not features. Make it memorable and specific to this audience.",
        'main_offer'        => " Describe the core product/service offering in detail. Include what customers receive, the format, duration, and the primary transformation/benefit. Be specific about deliverables.",
        'pricing_strategy'  => " Develop a complete pricing strategy including price point, payment options, and value-based justification. Consider market positioning and perceived value.",
        'bonuses'           => " Create 3-5 irresistible bonus items that complement the main offer. Each bonus should add significant perceived value while being relevant to the core transformation.",
        'guarantee'         => " Write a strong, risk-reversing guarantee that builds confidence and removes buyer hesitation. Make it specific, clear, and easy to understand.",
        'email_subject'     => " Write compelling email subject lines that grab attention and create curiosity. Vary the approach - some direct, some curiosity-driven.",
        'email_body'        => " Write engaging email copy that maintains the reader's attention, builds desire, and includes a clear call-to-action. Match the tone to the audience.",
        'ad_headline'       => " Create attention-grabbing ad headlines that stop the scroll and make people want to learn more. Focus on benefit or curiosity.",
        'ad_body'           => " Write compelling ad copy that speaks to the target audience's pain points and desires. Include social proof elements and a clear call-to-action.",
    );

    $specific_instruction = $field_instructions[$field_id] ?? " Generate high-quality, relevant content for this field.";

    return $base_prompt . $specific_instruction . " Output ONLY the requested content without preamble or explanation.";
}
}

/**
 * Enhanced field prompts with better context handling
 * Version: 2.3.56
 */
if (!function_exists('pl_get_enhanced_field_prompts')) {
function pl_get_enhanced_field_prompts($phase, $field_id, $context) {
    $context_str = "";
    if (is_array($context) && !empty($context)) {
        $relevant_context = array();
        foreach ($context as $key => $value) {
            if ($key === $field_id || empty($value) || !is_string($value)) {
                continue;
            }
            if (strlen(trim($value)) > 10) {
                $relevant_context[$key] = substr($value, 0, 200);
            }
        }

        if (!empty($relevant_context)) {
            $context_str = "\n\nContext from other fields:\n";
            foreach ($relevant_context as $k => $v) {
                $context_str .= "- {$k}: {$v}\n";
            }
        }
    }

    $prompts = array();

    if ($phase === 'market_clarity') {
        $prompts['target_audience'] = "Write a detailed target audience description. Include:\n1. Demographics (age range, income level, career stage)\n2. Psychographics (values, interests, lifestyle)\n3. Behaviors (where they hang out online, how they consume content)\n4. Specific challenges they face\n\nBe specific and concrete." . $context_str;

        $prompts['pain_points'] = "List 3-5 specific pain points and frustrations that your target audience experiences daily. For each:\n- Describe the emotional impact\n- Explain why current solutions fall short\n- Make it relatable and specific" . $context_str;

        $prompts['value_proposition'] = "Create a compelling value proposition that:\n1. Identifies the target audience\n2. Names their main problem\n3. Describes your unique solution\n4. States the primary benefit\n\nMake it clear and memorable." . $context_str;
    }

    if ($phase === 'create_offer') {
        $prompts['main_offer'] = "Describe your core offering in detail:\n1. What customers receive\n2. Format and structure\n3. Duration and access\n4. Primary transformation\n5. What makes it different" . $context_str;

        $prompts['pricing_strategy'] = "Develop a pricing strategy:\n1. Recommended price with justification\n2. Payment options\n3. Value-based rationale\n4. Market positioning" . $context_str;

        $prompts['bonuses'] = "Create 3-5 bonus items that:\n1. Complement main offer\n2. Solve related problems\n3. Add perceived value\n\nFor each: name, description, value, benefit" . $context_str;

        $prompts['guarantee'] = "Write a risk-reversing guarantee:\n1. What's guaranteed\n2. Timeframe\n3. Process\n4. Builds confidence" . $context_str;
    }

    if ($phase === 'email_sequences') {
        $prompts['email_subject'] = "Write 5 email subject lines:\n1. Grab attention\n2. Create curiosity/urgency\n3. Relevant to audience\n4. Vary approach\n5. Under 60 characters" . $context_str;

        $prompts['email_body'] = "Write engaging email:\n1. Hook that resonates\n2. Address pain point\n3. Build interest\n4. Social proof\n5. Clear CTA\n6. Conversational tone\n\n300-500 words" . $context_str;
    }

    if ($phase === 'facebook_ads') {
        $prompts['ad_headline'] = "Write 5 Facebook ad headlines:\n1. Stop the scroll\n2. Address pain/desire\n3. Create urgency\n4. Under 40 characters\n5. Vary approach" . $context_str;

        $prompts['ad_body'] = "Write Facebook ad copy:\n1. Hook in first sentence\n2. Speak to pain/desire\n3. Introduce solution\n4. Social proof\n5. Clear CTA\n6. 125-150 words" . $context_str;
    }

    return $prompts;
}
}

/**
 * Generic fallback prompt generator
 * Version: 2.3.56
 */
if (!function_exists('pl_get_generic_field_prompt')) {
function pl_get_generic_field_prompt($phase, $field_id, $context) {
    $context_str = "";
    if (!empty($context)) {
        $context_items = array();
        foreach ($context as $k => $v) {
            if (!empty($v) && is_string($v) && strlen(trim($v)) > 10) {
                $context_items[] = "{$k}: " . substr($v, 0, 150);
            }
        }
        if (!empty($context_items)) {
            $context_str = "\n\nExisting context:\n" . implode("\n", $context_items);
        }
    }

    return "Generate high-quality, specific content for the '{$field_id}' field in the '{$phase}' phase of a product launch. The content should be professional, actionable, and tailored to the field's purpose." . $context_str;
}
}

/**
 * Generate field-specific assistance with chat context
 */
if (!function_exists('pl_generate_field_assistance_with_context')) {
function pl_generate_field_assistance_with_context($phase, $field_id, $context = array(), $chat_context = array()) {
    $settings = function_exists('pl_get_settings') ? pl_get_settings() : array();
    $api_key = $settings['openai_key'] ?? '';
    if (empty($api_key)) {
        return false;
    }

    $field_prompt = pl_get_contextual_field_prompt($phase, $field_id, $context, $chat_context);
    $messages = array(
        array('role' => 'system', 'content' => (function_exists('pl_get_field_system_prompt') ? pl_get_field_system_prompt($phase, $field_id) : 'You are a helpful assistant that writes high-quality, field-specific content.')),
        array('role' => 'user', 'content' => $field_prompt)
    );
    if (function_exists('pl_call_openai_api')) {
        return pl_call_openai_api($messages, $settings);
    }
    return false;
}}

/**
 * Generate contextual field prompt using chat conversation
 */
if (!function_exists('pl_get_contextual_field_prompt')) {
function pl_get_contextual_field_prompt($phase, $field_id, $context, $chat_context) {
    $chat_summary = '';
    if (!empty($chat_context['userMessages']) && is_array($chat_context['userMessages'])) {
        $recent_user_messages = array_slice($chat_context['userMessages'], -3);
        $chat_summary = "Based on our conversation about: " . implode(' ', $recent_user_messages);
    }

    $context_str = "";
    if (is_array($context) && !empty($context)) {
        $relevant = array();
        foreach ($context as $key => $value) {
            if (!empty($value) && is_string($value) && strlen($value) > 10) {
                $relevant[] = $key . ': ' . substr($value, 0, 200);
            }
        }
        if (!empty($relevant)) {
            $context_str = "\n\nExisting form data:\n" . implode("\n", $relevant);
        }
    }

    $field_prompts = array(
        'target_audience' => "Write a detailed target audience description for the '{$field_id}' field. {$chat_summary}\n\nInclude demographics (age, income, career), psychographics (interests, values, behaviors), and specific pain points. Be specific about who this person is and where they spend time online.{$context_str}",
        'pain_points' => "List 3-5 specific pain points and frustrations for the '{$field_id}' field. {$chat_summary}\n\nFocus on emotional and practical problems related to their daily struggles in this context.{$context_str}",
        'value_proposition' => "Create a compelling value proposition for the '{$field_id}' field. {$chat_summary}\n\nClearly communicate your unique solution and the transformation you provide. Focus on outcomes, not features.{$context_str}",
        'market_size' => "Analyze the market size and opportunity for the '{$field_id}' field. {$chat_summary}\n\nInclude information about demand, growth trends, and revenue potential in this specific area.{$context_str}",
        'competitors' => "Identify 3-5 main competitors for the '{$field_id}' field. {$chat_summary}\n\nAnalyze their strengths and weaknesses, and gaps in the market you can exploit in this specific context.{$context_str}",
        'positioning' => "Define how you'll position yourself differently for the '{$field_id}' field. {$chat_summary}\n\nWhat's your unique angle or approach in this specific market?{$context_str}",
        'main_offer' => "Describe your core product or service offering for the '{$field_id}' field. {$chat_summary}\n\nInclude what customers receive, the format, duration, and primary benefits. Focus on the transformation delivered in this context.{$context_str}",
        'pricing_strategy' => "Develop a pricing strategy for the '{$field_id}' field. {$chat_summary}\n\nInclude your price point, payment options, and justification. Consider value-based pricing and market positioning for this specific offering.{$context_str}",
        'bonuses' => "Create 3-5 irresistible bonus items for the '{$field_id}' field. {$chat_summary}\n\nEnsure bonuses complement your main offer and increase perceived value in this specific context.{$context_str}",
        'guarantee' => "Write a strong, specific guarantee for the '{$field_id}' field. {$chat_summary}\n\nReduce risk and increase buyer confidence. Make it clear and easy to understand for this specific offering.{$context_str}",
    );

    return isset($field_prompts[$field_id]) ? $field_prompts[$field_id] : "Generate helpful content for the {$field_id} field in the {$phase} phase. {$chat_summary}{$context_str}";
}}



/**
 * Generic phase save handler with nonce compatibility.
 * Accepts either 'pl_progress_<phase>' or '<phase>_progress' nonces.
 * Persists to user meta: product_launch_progress_<phase>_<site_id>
 */
if (!function_exists('pl_handle_phase_save_auto')) {
function pl_handle_phase_save_auto($phase) {
    if (!isset($_POST['save_progress'])) {
        return null;
    }
    $nonce = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '';
    $ok = wp_verify_nonce($nonce, 'pl_progress_' . $phase) || wp_verify_nonce($nonce, $phase . '_progress');
    if (!$ok) {
        return null;
    }
    $user_id = get_current_user_id();
    $site_id = get_current_blog_id();
    $form_data = array();
    foreach ($_POST as $k => $v) {
        if ($k === '_wpnonce' || $k === '_wp_http_referer' or $k === 'save_progress' or $k === 'submit') {
            continue;
        }
        if (is_string($v)) {
            $clean = (strlen($v) > 180) ? sanitize_textarea_field($v) : sanitize_text_field($v);
            $form_data[$k] = $clean;
        }
    }
    $form_data['completed_at'] = current_time('mysql');
    update_user_meta($user_id, 'product_launch_progress_' . $phase . '_' . $site_id, wp_json_encode($form_data));
    return $form_data;
}
}


// Enqueue continuity bridge after core script
if (!function_exists('plc_enqueue_continuity_bridge')) {
    function plc_enqueue_continuity_bridge() {
        $src = plugins_url('assets/js/plc-memory-bridge.js', __FILE__);
        wp_enqueue_script('plc-memory-bridge', $src, array('jquery'), '0.0.5', true);
    }
    add_action('admin_enqueue_scripts', 'plc_enqueue_continuity_bridge');
}

// ============================================
// CRITICAL FIX: Settings Save Function
// Version: 2.3.56 - Fixed duplicate content issue
// ============================================

if (!function_exists('pl_sanitize_settings')) {
function pl_sanitize_settings($input) {  // ✅ ADDED $input PARAMETER
    // Input validation
    if (!is_array($input)) {
        $input = array();
    }
    
    $out = [];
    $out['openai_key'] = isset($input['openai_key']) ? trim($input['openai_key']) : '';
    $out['ai_model'] = isset($input['ai_model']) ? sanitize_text_field($input['ai_model']) : 'gpt-4o-mini';
    $out['rate_limit_per_min'] = max(1, min(100, intval($input['rate_limit_per_min'] ?? 20)));
    $out['timeout'] = max(5, min(60, intval($input['timeout'] ?? 30)));
    $out['max_tokens'] = max(100, min(4000, intval($input['max_tokens'] ?? 1000)));
    $out['temperature'] = max(0, min(2, floatval($input['temperature'] ?? 0.7)));

    // Save to network options in multisite, site option otherwise
    if (is_multisite()) {
        update_site_option(PL_OPTION_NAME, $out);
    } else {
        update_option(PL_OPTION_NAME, $out);
    }

    return $out;
}
}


/**
 * Handle saving network settings (Multisite) and redirect back to the settings page.
 */



// Network Admin Menu - Settings only here
add_action('network_admin_menu', function() {
    if (is_multisite()) {
        add_menu_page(
            __('Product Launch Settings','product-launch'),
            __('Product Launch','product-launch'),
            'manage_network_options',
            'product-launch-network-settings',
            'pl_render_network_settings_page',
            'dashicons-megaphone',
            30
        );
    }
});



// Network settings registration (Network Admin only)
add_action('admin_init', function() {
    if (!is_multisite() || !is_network_admin()) { return; }
    // Render sections/fields only; saving is handled inline in the page
    add_settings_section('pl_network_api', __('Network-wide OpenAI API Settings', 'product-launch'), function(){
        echo '<p>' . __('These settings apply to all sites in your network.', 'product-launch') . '</p>';
        echo '<div class="notice notice-info"><p><strong>' . __('Note:', 'product-launch') . '</strong> ' . __('Individual site administrators cannot modify these settings.', 'product-launch') . '</p></div>';
    }, 'product-launch-network-settings');
    add_settings_field('openai_key', __('OpenAI API Key', 'product-launch'), 'pl_field_openai_key', 'product-launch-network-settings', 'pl_network_api');
    add_settings_field('ai_model', __('AI Model', 'product-launch'), 'pl_field_ai_model', 'product-launch-network-settings', 'pl_network_api');
    add_settings_field('max_tokens', __('Max Tokens', 'product-launch'), 'pl_field_max_tokens', 'product-launch-network-settings', 'pl_network_api');
    add_settings_field('temperature', __('Temperature', 'product-launch'), 'pl_field_temperature', 'product-launch-network-settings', 'pl_network_api');
    add_settings_section('pl_network_security', __('Network Security & Limits', 'product-launch'), function(){}, 'product-launch-network-settings');
    add_settings_field('rate_limit_per_min', __('Rate limit (requests/min)', 'product-launch'), 'pl_field_rate_limit', 'product-launch-network-settings', 'pl_network_security');
    add_settings_field('timeout', __('API timeout (seconds)', 'product-launch'), 'pl_field_timeout', 'product-launch-network-settings', 'pl_network_security');
});


function pl_sanitize_network_settings($input) {
    $out = [];
    $out['openai_key'] = isset($input['openai_key']) ? trim($input['openai_key']) : '';
    $out['ai_model'] = isset($input['ai_model']) ? sanitize_text_field($input['ai_model']) : 'gpt-4o-mini';
    $out['rate_limit_per_min'] = max(1, min(100, intval($input['rate_limit_per_min'] ?? 20)));
    $out['timeout'] = max(5, min(60, intval($input['timeout'] ?? 30)));
    $out['max_tokens'] = max(100, min(4000, intval($input['max_tokens'] ?? 1000)));
    $out['temperature'] = max(0, min(2, floatval($input['temperature'] ?? 0.7)));
    update_site_option(PL_OPTION_NAME, $out);
    return $out;
}

function pl_render_network_settings_page() {
        if (!current_user_can('manage_network_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'product-launch'));
    }

    // Handle settings update if submitted
    if (isset($_POST['submit']) && isset($_POST['pl_network_nonce']) && wp_verify_nonce($_POST['pl_network_nonce'], 'pl_network_settings_update')) {
        $settings = isset($_POST[PL_OPTION_NAME]) ? (array) $_POST[PL_OPTION_NAME] : [];
        pl_sanitize_network_settings($settings);
        echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'product-launch') . '</p></div>';
    }

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Product Launch – Network Settings', 'product-launch') . '</h1>';
    echo '<p>' . __('Configure API settings for all sites in your network. Individual site administrators cannot modify these settings.', 'product-launch') . '</p>';
    echo '<form method="post" action="">';
    wp_nonce_field('pl_network_settings_update', 'pl_network_nonce');
    do_settings_sections('product-launch-network-settings');
    submit_button(__('Save Network Settings', 'product-launch'));
    echo '</form>';
    echo '<div style="margin-top:20px;">';
    echo '<button class="button button-primary test-api-connection" type="button">' . esc_html__('Test API Connection', 'product-launch') . '</button>';
    echo '</div>';
    echo '</div>';
}


// Handle network settings save




// Site admin menu: Dashboard only (no Settings). Settings are network-only.



// Consolidated menu registration with hidden phase pages for non-super-admins
add_action('admin_menu', function() {
    add_menu_page(
        __('Product Launch','product-launch'),
        __('Product Launch','product-launch'),
        'read',
        'product-launch',
        'pl_render_dashboard',
        'dashicons-megaphone',
        3
    );
    

// Alias slugs to keep backward compatibility (e.g., product-launch-emails -> email-sequences)
add_action('admin_menu', function () {
    $aliases = [
        'product-launch-emails' => 'email-sequences',
    ];
    foreach ($aliases as $slug => $phase_key) {
        add_submenu_page(
            null,
            __('Writing Email Sequences','product-launch'),
            __('Writing Email Sequences','product-launch'),
            'read',
            $slug,
            function() use ($phase_key) { plc_render_phase_router($phase_key); }
        );
    }
}, 6);

add_submenu_page('product-launch', __('Dashboard','product-launch'), __('Dashboard','product-launch'), 'read', 'product-launch', 'pl_render_dashboard');

    $plc_phases = [
        'product-launch-market'   => ['key' => 'market-clarity',     'menu' => __('Getting Market Clarity','product-launch')],
        'product-launch-offer'    => ['key' => 'create-offer',       'menu' => __('Creating Your Offer','product-launch')],
        'product-launch-service'  => ['key' => 'create-service',     'menu' => __('Creating Your Service','product-launch')],
        'product-launch-funnel'   => ['key' => 'build-funnel',       'menu' => __('Building Sales Funnel','product-launch')],
        'product-launch-emailss'   => ['key' => 'email-sequences',    'menu' => __('Writing Email Sequences','product-launch')],
        'product-launch-organic'  => ['key' => 'organic-posts',      'menu' => __('Organic Promo Posts','product-launch')],
        'product-launch-ads'      => ['key' => 'facebook-ads',       'menu' => __('Creating Facebook Ads','product-launch')],
        'product-launch-launch'   => ['key' => 'launch',             'menu' => __('Launching','product-launch')],
    ];
    foreach ($plc_phases as $slug => $data) {
        add_submenu_page(
            'product-launch',
            $data['menu'],
            $data['menu'],
            'read',
            $slug,
            function() use ($data) { plc_render_phase_router($data['key']); }
        );
    }
}, 9);

// Hide phase pages from submenu for non-super-admins (but keep accessible)
add_action('admin_menu', function() {
    if (is_multisite() && is_super_admin()) return;
    $phases = [
        'product-launch-market',
        'product-launch-offer',
        'product-launch-service',
        'product-launch-funnel',
        'product-launch-emailss',
        'product-launch-organic',
        'product-launch-ads',
        'product-launch-launch',
    ];
    foreach ($phases as $slug) {
        remove_submenu_page('product-launch', $slug);
    }
}, 100);


// Ensure phase pages are registered as hidden pages too (so URL access never fails)
add_action('admin_menu', function () {
    $plc_phases = [
        'product-launch-market'   => ['key' => 'market-clarity',     'menu' => __('Getting Market Clarity','product-launch')],
        'product-launch-offer'    => ['key' => 'create-offer',       'menu' => __('Creating Your Offer','product-launch')],
        'product-launch-service'  => ['key' => 'create-service',     'menu' => __('Creating Your Service','product-launch')],
        'product-launch-funnel'   => ['key' => 'build-funnel',       'menu' => __('Building Sales Funnel','product-launch')],
        'product-launch-emailss'   => ['key' => 'email-sequences',    'menu' => __('Writing Email Sequences','product-launch')],
        'product-launch-organic'  => ['key' => 'organic-posts',      'menu' => __('Organic Promo Posts','product-launch')],
        'product-launch-ads'      => ['key' => 'facebook-ads',       'menu' => __('Creating Facebook Ads','product-launch')],
        'product-launch-launch'   => ['key' => 'launch',             'menu' => __('Launching','product-launch')],
    ];
    foreach ($plc_phases as $slug => $data) {
        // Register a hidden page with no parent; keeps route working even if submenu entries are removed
        add_submenu_page(
            null,
            $data['menu'],
            $data['menu'],
            'read',
            $slug,
            function() use ($data) { plc_render_phase_router($data['key']); }
        );
    }
}, 5);
function plc_render_phase_router($phase_key) {
    $allowed = [
        'market-clarity','create-offer','create-service',
        'build-funnel','email-sequences','organic-posts',
        'facebook-ads','launch'
    ];
    if (!in_array($phase_key, $allowed, true)) {
        wp_die(__('Invalid phase.', 'product-launch'));
    }
    $tpl = plugin_dir_path(__FILE__) . 'templates/' . $phase_key . '.php';
    if (file_exists($tpl)) {
        require $tpl;
    } else {
        wp_die(sprintf(__('Template not found for phase: %s', 'product-launch'), esc_html($phase_key)));
    }
}


// === Email Sequences legacy slug redirect ===

/* removed redirect block (email pages) */




add_action('admin_footer', function () {
    if (!current_user_can('read')) return;
    $page = isset($_GET['page']) ? $_GET['page'] : '';
    if (strpos($page, 'product-launch-') === 0) {
        $ver = defined('PL_PLUGIN_VERSION') ? PL_PLUGIN_VERSION : 'unknown';
        $path = __FILE__;
        echo "<!-- PLC Enhanced Active – version {$ver} – main: {$path} -->";
    }
});



// === SAFETY: Email Sequences alias submenus to avoid any redirect loops ===
add_action('admin_menu', function() {
    if (!function_exists('plc_render_phase_router')) { return; }
    $cb = function() { plc_render_phase_router('email-sequences'); };
    add_submenu_page('product-launch', '', null, 'read', 'product-launch-emails', $cb);
    add_submenu_page('product-launch', '', null, 'read', 'product-launch-emailsss', $cb);
}, 99);
add_action('admin_menu', function() {
    remove_submenu_page('product-launch', 'product-launch-emails');
    remove_submenu_page('product-launch', 'product-launch-emailsss');
}, 100);




// === Enqueue quality indicators on all Product Launch admin pages ===
if (!function_exists('pl_enqueue_quality_indicators')) {
function pl_enqueue_quality_indicators($hook){
    // Detect plugin admin pages broadly
    $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    $framed = isset($_GET['framed-menu']) ? sanitize_text_field($_GET['framed-menu']) : '';
    $is_pl_page = (strpos($page, 'product-launch') !== false) || (strpos($framed, 'product-launch') !== false);

    if (!$is_pl_page) return;

    // Version for cache-busting
    if (defined('PL_VERSION')) { $ver = PL_VERSION; }
    elseif (defined('PRODUCT_LAUNCH_VERSION')) { $ver = PRODUCT_LAUNCH_VERSION; }
    else { $ver = '1.0.0'; }

    // Enqueue dedicated handle (independent of other scripts)
    wp_enqueue_style('pl-indicators', plugins_url('assets/css/pl-indicators.css', __FILE__), array(), $ver);
    wp_enqueue_script('pl-indicators', plugins_url('assets/js/pl-indicators.js', __FILE__), array('jquery'), $ver, true);
}}
add_action('admin_enqueue_scripts', 'pl_enqueue_quality_indicators', 99);


// === PL Selective Override Assets (instance-only override + CSS alignment) ===
add_action('admin_enqueue_scripts', function() {
    wp_enqueue_style(
        'pl-selective-modal',
        plugin_dir_url(__FILE__) . 'assets/css/pl-selective-modal.css',
        array(),
        pl_get_asset_version('assets/css/pl-selective-modal.css')
    );
    wp_enqueue_script(
        'pl-selective-override',
        plugin_dir_url(__FILE__) . 'assets/js/pl-selective-override.js',
        array('pl-js'),
        pl_get_asset_version('assets/js/pl-selective-override.js'),
        true
    );
}, 100);
