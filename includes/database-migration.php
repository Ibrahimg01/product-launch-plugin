<?php
/**
 * Database migration for enhanced memory system - SECURED VERSION
 * Version: 1.4.0 - SQL Injection Fix Applied
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Migrate to enhanced memory system using safe dbDelta() method
 * 
 * @return bool Success status
 */
function pl_migrate_to_enhanced_memory() {
    global $wpdb;
    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // ✅ SECURITY FIX: Using dbDelta() exclusively - no string interpolation
    // This prevents SQL injection vulnerabilities
    
    // Table 1: Enhanced memory storage
    $memory_table = $wpdb->prefix . 'product_launch_memory';
    
    $sql_memory = "CREATE TABLE $memory_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        site_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
        memory_type ENUM('pattern', 'preference', 'insight', 'milestone') NOT NULL,
        memory_key VARCHAR(100) NOT NULL,
        memory_value LONGTEXT NOT NULL,
        confidence_score DECIMAL(3,2) DEFAULT 0.50,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_lookup (user_id, site_id, memory_type),
        KEY memory_search (memory_key, confidence_score),
        KEY updated_index (updated_at)
    ) $charset_collate;";
    
    dbDelta($sql_memory);
    
    // Table 2: Add indexes to existing conversations table for performance
    $conversations_table = $wpdb->prefix . 'product_launch_conversations';
    
    $sql_conversations = "CREATE TABLE $conversations_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NULL,
        site_id BIGINT UNSIGNED NULL DEFAULT 1,
        phase VARCHAR(64) NOT NULL,
        message_count INT DEFAULT 0,
        history LONGTEXT NULL,
        context_data LONGTEXT NULL,
        user_insights LONGTEXT NULL COMMENT 'Extracted user patterns and preferences',
        context_summary TEXT NULL COMMENT 'Summary of key context for quick AI access',
        memory_tags VARCHAR(500) NULL COMMENT 'Searchable tags for memory retrieval',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_phase_site (user_id, phase, site_id),
        KEY created_at (created_at),
        KEY recent_activity (created_at DESC),
        KEY user_phase_date (user_id, phase, created_at DESC)
    ) $charset_collate;";
    
    dbDelta($sql_conversations);
    
    // Table 3: Rate limiting (for enhanced security)
    $rate_limit_table = $wpdb->prefix . 'product_launch_rate_limits';
    
    $sql_rate_limits = "CREATE TABLE $rate_limit_table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        action VARCHAR(64) NOT NULL,
        timestamp INT UNSIGNED NOT NULL,
        PRIMARY KEY (id),
        KEY user_action (user_id, action),
        KEY cleanup (timestamp)
    ) $charset_collate;";
    
    dbDelta($sql_rate_limits);
    
    // Log successful migration
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[Product Launch] Database migration completed successfully (v1.4.0)');
    }
    
    return true;
}

/**
 * Cleanup old rate limit entries (run via cron or on-demand)
 * 
 * @return int Number of rows deleted
 */
function pl_cleanup_rate_limits() {
    global $wpdb;
    
    $table = $wpdb->prefix . 'product_launch_rate_limits';
    $one_hour_ago = time() - 3600;
    
    $deleted = $wpdb->query($wpdb->prepare(
        "DELETE FROM $table WHERE timestamp < %d",
        $one_hour_ago
    ));
    
    return $deleted;
}

/**
 * Check if migration has been run
 * 
 * @return bool
 */
function pl_is_migration_complete() {
    global $wpdb;
    
    $memory_table = $wpdb->prefix . 'product_launch_memory';
    $rate_limit_table = $wpdb->prefix . 'product_launch_rate_limits';
    
    // Check if both new tables exist
    $memory_exists = $wpdb->get_var("SHOW TABLES LIKE '$memory_table'") === $memory_table;
    $rate_limit_exists = $wpdb->get_var("SHOW TABLES LIKE '$rate_limit_table'") === $rate_limit_table;
    
    return $memory_exists && $rate_limit_exists;
}

// Schedule cleanup cron job
if (!wp_next_scheduled('pl_cleanup_rate_limits_cron')) {
    wp_schedule_event(time(), 'hourly', 'pl_cleanup_rate_limits_cron');
}

add_action('pl_cleanup_rate_limits_cron', 'pl_cleanup_rate_limits');
