<?php
/**
 * Database migration for enhanced memory system
 * Add this to your plugin activation
 */

function pl_migrate_to_enhanced_memory() {
    global $wpdb;
    
    // Add new columns to conversations table for better memory
    $table_name = $wpdb->prefix . 'product_launch_conversations';
    
    $columns_to_add = array(
        'user_insights' => 'LONGTEXT NULL COMMENT "Extracted user patterns and preferences"',
        'context_summary' => 'TEXT NULL COMMENT "Summary of key context for quick AI access"',
        'memory_tags' => 'VARCHAR(500) NULL COMMENT "Searchable tags for memory retrieval"'
    );
    
    foreach ($columns_to_add as $column => $definition) {
        $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN IF NOT EXISTS {$column} {$definition}");
    }
    
    // Create memory index table for faster retrieval
    $memory_table = $wpdb->prefix . 'product_launch_memory';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS `$memory_table` (
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
        KEY user_memory (user_id, site_id, memory_type),
        KEY memory_lookup (memory_key, confidence_score)
    ) $charset_collate;";
    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// Add to plugin activation hook
register_activation_hook(__FILE__, 'pl_migrate_to_enhanced_memory');