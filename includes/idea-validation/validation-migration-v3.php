<?php
/**
 * Database Migration to V3 Schema
 * Adds multi-signal validation columns
 */

if (!defined('ABSPATH')) exit;

function pl_migrate_validation_schema_to_v3() {
    global $wpdb;

    $tables_to_update = [
        $wpdb->prefix . 'pl_validations',
    ];

    if (is_multisite()) {
        $tables_to_update[] = $wpdb->base_prefix . 'pl_validations';
    }

    foreach ($tables_to_update as $table) {
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) continue;

        // Add new columns if they don't exist
        $columns_to_add = [
            'validation_id' => "VARCHAR(32) UNIQUE NULL AFTER id",
            'confidence_score' => "DECIMAL(5,2) DEFAULT 0 AFTER confidence_level",
            'market_demand_score' => "INT DEFAULT 0 AFTER confidence_score",
            'competition_score' => "INT DEFAULT 0 AFTER market_demand_score",
            'monetization_score' => "INT DEFAULT 0 AFTER competition_score",
            'feasibility_score' => "INT DEFAULT 0 AFTER monetization_score",
            'ai_analysis_score' => "INT DEFAULT 0 AFTER feasibility_score",
            'social_proof_score' => "INT DEFAULT 0 AFTER ai_analysis_score",
            'signals_data' => "LONGTEXT NULL AFTER social_proof_score",
            'recommendations_data' => "LONGTEXT NULL AFTER signals_data",
            'phase_prefill_data' => "LONGTEXT NULL AFTER recommendations_data",
            'expires_at' => "DATETIME NULL AFTER published_at",
        ];

        foreach ($columns_to_add as $column => $definition) {
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM `$table` LIKE '$column'");

            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
                error_log("Added column $column to $table");
            }
        }

        // Add indexes for performance
        $indexes = [
            'idx_validation_id' => "ADD INDEX idx_validation_id (validation_id)",
            'idx_scores' => "ADD INDEX idx_scores (validation_score, confidence_score)",
            'idx_expires' => "ADD INDEX idx_expires (expires_at)",
        ];

        foreach ($indexes as $index_name => $index_sql) {
            $index_exists = $wpdb->get_results("SHOW INDEX FROM `$table` WHERE Key_name = '$index_name'");

            if (empty($index_exists)) {
                $wpdb->query("ALTER TABLE `$table` $index_sql");
                error_log("Added index $index_name to $table");
            }
        }
    }

    // Create signals cache table
    pl_create_signals_cache_table();

    update_option('pl_validation_schema_version', '3.0.0');
    update_option('pl_v3_migration_completed_at', current_time('mysql'));
}

function pl_create_signals_cache_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $table = $wpdb->prefix . 'pl_validation_signals';

    $sql = "CREATE TABLE IF NOT EXISTS `$table` (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        validation_id VARCHAR(32) NOT NULL,
        signal_type VARCHAR(50) NOT NULL,
        signal_data LONGTEXT NULL,
        cached_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NULL,
        PRIMARY KEY (id),
        KEY validation_signal (validation_id, signal_type),
        KEY expires (expires_at)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// Run migration on plugin update
add_action('admin_init', function() {
    $current_version = get_option('pl_validation_schema_version', '0.0.0');

    if (version_compare($current_version, '3.0.0', '<')) {
        pl_migrate_validation_schema_to_v3();
    }
});
