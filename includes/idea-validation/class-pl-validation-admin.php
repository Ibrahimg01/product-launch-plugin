<?php
/**
 * Validation Admin Interface
 * Manages admin dashboard and settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class PL_Validation_Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Idea Validation', 'product-launch'),
            __('Idea Validation', 'product-launch'),
            'manage_options',
            'pl-validation',
            array($this, 'render_dashboard_page'),
            'dashicons-lightbulb',
            30
        );

        add_submenu_page(
            'pl-validation',
            __('Dashboard', 'product-launch'),
            __('Dashboard', 'product-launch'),
            'manage_options',
            'pl-validation',
            array($this, 'render_dashboard_page')
        );

        add_submenu_page(
            'pl-validation',
            __('All Validations', 'product-launch'),
            __('All Validations', 'product-launch'),
            'manage_options',
            'pl-validation-list',
            array($this, 'render_list_page')
        );

        add_submenu_page(
            'pl-validation',
            __('Settings', 'product-launch'),
            __('Settings', 'product-launch'),
            'manage_options',
            'pl-validation-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('pl_validation_settings', 'pl_validation_api_key');
        register_setting('pl_validation_settings', 'pl_validation_api_endpoint');
        register_setting('pl_validation_settings', 'pl_validation_default_limit');
        register_setting('pl_validation_settings', 'pl_validation_cache_duration');
        register_setting('pl_validation_settings', 'pl_validation_auto_enrich');
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'pl-validation') === false) {
            return;
        }

        wp_enqueue_style(
            'pl-validation-admin',
            PL_PLUGIN_URL . 'assets/css/validation-admin.css',
            array(),
            PL_VERSION
        );

        wp_enqueue_script(
            'pl-validation-admin',
            PL_PLUGIN_URL . 'assets/js/validation-admin.js',
            array('jquery'),
            PL_VERSION,
            true
        );

        wp_localize_script('pl-validation-admin', 'plValidation', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('pl_validation_admin'),
        ));
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'pl_validations';

        $total_validations = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $completed_validations = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE validation_status = 'completed'");
        $pending_validations = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE validation_status = 'pending'");
        $avg_score = (float) $wpdb->get_var("SELECT AVG(validation_score) FROM $table WHERE validation_status = 'completed'");

        $recent = $wpdb->get_results(
            "SELECT v.*, u.display_name " .
            "FROM $table v " .
            "LEFT JOIN {$wpdb->users} u ON v.user_id = u.ID " .
            "ORDER BY v.created_at DESC " .
            "LIMIT 10"
        );

        $month_start = date('Y-m-01 00:00:00');
        $this_month = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE created_at >= %s",
                $month_start
            )
        );

        include PL_PLUGIN_DIR . 'templates/admin/validation-dashboard.php';
    }

    /**
     * Render validations list page
     */
    public function render_list_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'pl_validations';

        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $offset = ($current_page - 1) * $per_page;

        $status_filter = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';

        $where = 'WHERE 1=1';
        $where_args = array();

        if ($status_filter) {
            $where .= ' AND validation_status = %s';
            $where_args[] = $status_filter;
        }

        if ($search) {
            $where .= ' AND business_idea LIKE %s';
            $where_args[] = '%' . $wpdb->esc_like($search) . '%';
        }

        $count_query = "SELECT COUNT(*) FROM $table $where";
        if ($where_args) {
            $total_items = (int) $wpdb->get_var($wpdb->prepare($count_query, $where_args));
        } else {
            $total_items = (int) $wpdb->get_var($count_query);
        }

        $total_pages = $total_items > 0 ? (int) ceil($total_items / $per_page) : 1;

        $list_query = "SELECT v.*, u.display_name " .
            "FROM $table v " .
            "LEFT JOIN {$wpdb->users} u ON v.user_id = u.ID " .
            "$where " .
            "ORDER BY v.created_at DESC " .
            "LIMIT %d OFFSET %d";

        $query_args = $where_args;
        $query_args[] = $per_page;
        $query_args[] = $offset;

        if ($query_args) {
            $validations = $wpdb->get_results($wpdb->prepare($list_query, $query_args));
        } else {
            $validations = $wpdb->get_results($list_query);
        }

        include PL_PLUGIN_DIR . 'templates/admin/validation-list.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (isset($_POST['pl_validation_settings_submit'])) {
            check_admin_referer('pl_validation_settings');

            $api_key = isset($_POST['pl_validation_api_key']) ? sanitize_text_field(wp_unslash($_POST['pl_validation_api_key'])) : '';
            $api_endpoint = isset($_POST['pl_validation_api_endpoint']) ? esc_url_raw(wp_unslash($_POST['pl_validation_api_endpoint'])) : '';
            $default_limit = isset($_POST['pl_validation_default_limit']) ? absint($_POST['pl_validation_default_limit']) : 3;
            $cache_duration = isset($_POST['pl_validation_cache_duration']) ? absint($_POST['pl_validation_cache_duration']) : 24;
            $auto_enrich = isset($_POST['pl_validation_auto_enrich']) ? 1 : 0;

            update_option('pl_validation_api_key', $api_key);
            update_option('pl_validation_api_endpoint', $api_endpoint);
            update_option('pl_validation_default_limit', $default_limit);
            update_option('pl_validation_cache_duration', $cache_duration);
            update_option('pl_validation_auto_enrich', $auto_enrich);

            echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully!', 'product-launch') . '</p></div>';
        }

        $api_key = get_option('pl_validation_api_key', 'exp_live_05663cf87e3b406780a939cf079e59f3');
        $api_endpoint = get_option('pl_validation_api_endpoint', 'https://api.explodingstartup.com/api/ideas');
        $default_limit = get_option('pl_validation_default_limit', 3);
        $cache_duration = get_option('pl_validation_cache_duration', 24);
        $auto_enrich = get_option('pl_validation_auto_enrich', 1);

        include PL_PLUGIN_DIR . 'templates/admin/validation-settings.php';
    }
}

if (is_admin()) {
    new PL_Validation_Admin();
}
