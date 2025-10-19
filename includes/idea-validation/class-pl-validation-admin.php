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
        if (is_multisite()) {
            // Ensure the Product Launch top-level network menu is registered before
            // we attempt to attach our validation subpages. A later priority prevents
            // WordPress from generating fallback links like
            // wp-admin/network/<slug> (which results in a physical file request and a 404)
            // when the parent menu is not yet available.
            add_action('network_admin_menu', array($this, 'add_network_admin_menu'), 20);
        }
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        $parent_slug = 'product-launch';

        add_submenu_page(
            $parent_slug,
            __('Idea Validation', 'product-launch'),
            __('Idea Validation', 'product-launch'),
            'read',
            'product-launch-validation',
            array($this, 'render_portal_page')
        );

        add_submenu_page(
            $parent_slug,
            __('Ideas Library', 'product-launch'),
            __('Ideas Library', 'product-launch'),
            'read',
            'product-launch-ideas-library',
            array($this, 'render_ideas_library_page')
        );

        add_submenu_page(
            $parent_slug,
            __('My Validations', 'product-launch'),
            __('My Validations', 'product-launch'),
            'manage_options',
            'product-launch-validation-list',
            array($this, 'render_list_page')
        );

        if (is_multisite()) {
            add_submenu_page(
                null,
                __('Validation Settings', 'product-launch'),
                __('Validation Settings', 'product-launch'),
                'manage_options',
                'product-launch-validation-settings',
                array($this, 'render_settings_redirect_page')
            );
        } else {
            add_submenu_page(
                $parent_slug,
                __('Validation Settings', 'product-launch'),
                __('Validation Settings', 'product-launch'),
                'manage_options',
                'product-launch-validation-settings',
                array($this, 'render_settings_page')
            );
        }

        // Backwards compatibility for legacy direct links
        add_submenu_page(
            null,
            __('My Validations', 'product-launch'),
            __('My Validations', 'product-launch'),
            'manage_options',
            'pl-validation-list',
            array($this, 'render_list_page')
        );
    }

    /**
     * Register network admin menu pages for validation management.
     */
    public function add_network_admin_menu() {
        $parent_slug = 'product-launch-network-settings';

        add_submenu_page(
            $parent_slug,
            __('Idea Validation', 'product-launch'),
            __('Idea Validation', 'product-launch'),
            'manage_network_options',
            'product-launch-network-validation',
            array($this, 'render_portal_page')
        );

        add_submenu_page(
            null,
            __('Validation Dashboard', 'product-launch'),
            __('Validation Dashboard', 'product-launch'),
            'manage_network_options',
            'product-launch-network-validation-dashboard',
            array($this, 'render_dashboard_page')
        );

        add_submenu_page(
            $parent_slug,
            __('Ideas Library', 'product-launch'),
            __('Ideas Library', 'product-launch'),
            'manage_network_options',
            'product-launch-network-ideas-library',
            array($this, 'render_ideas_library_page')
        );

        add_submenu_page(
            $parent_slug,
            __('My Validations', 'product-launch'),
            __('My Validations', 'product-launch'),
            'manage_network_options',
            'product-launch-network-validation-list',
            array($this, 'render_list_page')
        );

        add_submenu_page(
            $parent_slug,
            __('Validation Settings', 'product-launch'),
            __('Validation Settings', 'product-launch'),
            'manage_network_options',
            'product-launch-validation-settings',
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
        if (false === strpos($hook, 'product-launch-validation')
            && false === strpos($hook, 'pl-validation')
            && false === strpos($hook, 'product-launch-network-validation')
            && false === strpos($hook, 'product-launch-ideas-library')
            && false === strpos($hook, 'product-launch-network-ideas-library')) {
            return;
        }

        $current_page = isset($_GET['page']) ? $this->sanitize_page_slug(wp_unslash($_GET['page'])) : '';
        $base_page_slug = $current_page ?: (is_network_admin() ? 'product-launch-network-validation' : 'product-launch-validation');
        $base_page_url = $this->get_admin_page_url($base_page_slug);
        $context = is_network_admin() ? 'network_admin' : 'admin';

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
            'networkNonce' => wp_create_nonce('pl_validation_network'),
            'isNetwork' => is_network_admin(),
            'networkStrings' => array(
                'searching' => __('Searching for pre-validated ideas…', 'product-launch'),
                'noResults' => __('No pre-validated ideas were found for this search.', 'product-launch'),
                'pushSuccess' => __('Idea published to the network library.', 'product-launch'),
                'pushError' => __('Unable to publish this idea to the library. Please try again.', 'product-launch'),
                'searchError' => __('Unable to complete the discovery request. Please try again.', 'product-launch'),
                'publishAction' => __('Push to Ideas Library', 'product-launch'),
                'publishedAction' => __('Published', 'product-launch'),
                'publishing' => __('Publishing…', 'product-launch'),
                'metaLabel' => __('Discovered %1$s ideas for “%2$s”.', 'product-launch'),
                'metaLabelNoQuery' => __('Discovered %1$s ideas.', 'product-launch'),
                'ideaColumn' => __('Idea', 'product-launch'),
                'scoreColumn' => __('Score', 'product-launch'),
                'confidenceColumn' => __('Confidence', 'product-launch'),
                'actionsColumn' => __('Actions', 'product-launch'),
                'libraryLinkLabel' => __('Open Ideas Library', 'product-launch'),
            ),
            'networkLinks' => array(
                'library' => $this->get_admin_page_url('product-launch-network-ideas-library'),
                'dashboard' => $this->get_admin_page_url('product-launch-network-validation-dashboard'),
            ),
        ));

        if (false !== strpos($hook, 'product-launch_page_product-launch-validation')
            || false !== strpos($hook, 'product-launch_page_product-launch-ideas-library')
            || false !== strpos($hook, 'product-launch-network-settings_page_product-launch-network-validation')
            || false !== strpos($hook, 'product-launch-network-settings_page_product-launch-network-ideas-library')) {
            wp_enqueue_style(
                'pl-validation-frontend',
                PL_PLUGIN_URL . 'assets/css/validation-frontend.css',
                array(),
                PL_VERSION
            );

            wp_enqueue_script(
                'pl-validation-frontend',
                PL_PLUGIN_URL . 'assets/js/validation-frontend.js',
                array('jquery'),
                PL_VERSION,
                true
            );

            wp_localize_script('pl-validation-frontend', 'plValidationFrontend', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('pl_validation_frontend'),
                'isLoggedIn' => is_user_logged_in(),
                'loginUrl' => wp_login_url($base_page_url),
                'context' => $context,
                'redirectBase' => $base_page_url,
                'strings' => array(
                    'loginRequired' => __('Please log in to validate your business idea.', 'product-launch'),
                    'ideaRequired' => __('Please enter your business idea.', 'product-launch'),
                    'quotaExceeded' => __('You have reached your monthly validation limit.', 'product-launch'),
                    'submitting' => __('Validating your idea...', 'product-launch'),
                    'success' => __('Validation complete!', 'product-launch'),
                    'error' => __('An error occurred. Please try again.', 'product-launch')
                )
            ));
        }
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page($embedded = false) {
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

        $embedded = (bool) $embedded;

        $portal_slug = is_network_admin() ? 'product-launch-network-validation' : 'product-launch-validation';
        $portal_base_url = $this->get_admin_page_url($portal_slug);
        $list_page_slug = is_network_admin() ? 'product-launch-network-validation-list' : 'product-launch-validation-list';
        $list_page_url = $this->get_admin_page_url($list_page_slug);

        include PL_PLUGIN_DIR . 'templates/admin/validation-dashboard.php';
    }

    /**
     * Render ideas library page for administrators and clients.
     */
    public function render_ideas_library_page() {
        if (!current_user_can('read')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'product-launch'));
        }

        $library_content = do_shortcode('[pl_ideas_library]');

        include PL_PLUGIN_DIR . 'templates/admin/ideas-library.php';
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

        $list_base_slug = is_network_admin() ? 'product-launch-network-validation-list' : 'product-launch-validation-list';
        $list_base_url = $this->get_admin_page_url($list_base_slug);
        include PL_PLUGIN_DIR . 'templates/admin/validation-list.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        $capability = is_network_admin() ? 'manage_network_options' : 'manage_options';

        if (!current_user_can($capability)) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'product-launch'));
        }

        if (is_multisite() && !is_network_admin()) {
            echo '<div class="wrap"><h1>' . esc_html__('Validation Settings', 'product-launch') . '</h1>';
            echo '<p>' . esc_html__('Validation settings are managed from the Product Launch network settings area.', 'product-launch') . '</p>';
            echo '<p><a class="button button-primary" href="' . esc_url(network_admin_url('admin.php?page=product-launch-validation-settings')) . '">' . esc_html__('Open Network Validation Settings', 'product-launch') . '</a></p>';
            echo '</div>';
            return;
        }

        if (isset($_POST['pl_validation_settings_submit'])) {
            check_admin_referer('pl_validation_settings');

            $api_key = isset($_POST['pl_validation_api_key']) ? sanitize_text_field(wp_unslash($_POST['pl_validation_api_key'])) : '';
            $api_endpoint = isset($_POST['pl_validation_api_endpoint']) ? esc_url_raw(wp_unslash($_POST['pl_validation_api_endpoint'])) : '';
            $default_limit = isset($_POST['pl_validation_default_limit']) ? absint($_POST['pl_validation_default_limit']) : 3;
            $cache_duration = isset($_POST['pl_validation_cache_duration']) ? absint($_POST['pl_validation_cache_duration']) : 24;
            $auto_enrich = isset($_POST['pl_validation_auto_enrich']) ? 1 : 0;

            pl_update_validation_option('pl_validation_api_key', $api_key);
            pl_update_validation_option('pl_validation_api_endpoint', $api_endpoint);
            pl_update_validation_option('pl_validation_default_limit', max(1, $default_limit));
            pl_update_validation_option('pl_validation_cache_duration', max(1, $cache_duration));
            pl_update_validation_option('pl_validation_auto_enrich', $auto_enrich ? 1 : 0);

            echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully!', 'product-launch') . '</p></div>';
        }

        $api_key = pl_get_validation_option('pl_validation_api_key', 'exp_live_05663cf87e3b406780a939cf079e59f3');
        $api_endpoint = pl_get_validation_option('pl_validation_api_endpoint', 'https://api.explodingstartup.com/api/ideas');
        $default_limit = pl_get_validation_option('pl_validation_default_limit', 3);
        $cache_duration = pl_get_validation_option('pl_validation_cache_duration', 24);
        $auto_enrich = pl_get_validation_option('pl_validation_auto_enrich', 1);

        include PL_PLUGIN_DIR . 'templates/admin/validation-settings.php';
    }

    /**
     * Render notice for subsites directing to network settings.
     */
    public function render_settings_redirect_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'product-launch'));
        }

        echo '<div class="wrap"><h1>' . esc_html__('Validation Settings', 'product-launch') . '</h1>';
        echo '<div class="notice notice-info"><p>' . esc_html__('Validation settings are now managed from the Product Launch network admin.', 'product-launch') . '</p></div>';
        echo '<p><a class="button button-primary" href="' . esc_url(network_admin_url('admin.php?page=product-launch-validation-settings')) . '">' . esc_html__('Go to Network Validation Settings', 'product-launch') . '</a></p>';
        echo '</div>';
    }

    /**
     * Sanitize a page slug for use in URLs.
     */
    private function sanitize_page_slug($slug) {
        $slug = strtolower((string) $slug);

        return preg_replace('/[^a-z0-9_-]/', '', $slug);
    }

    /**
     * Build the appropriate admin URL for a given page slug.
     */
    private function get_admin_page_url($slug) {
        $slug = $this->sanitize_page_slug($slug);

        $base = is_network_admin() ? network_admin_url('admin.php?page=') : admin_url('admin.php?page=');

        return $base . $slug;
    }

    /**
     * Render unified portal page for clients and administrators.
     */
    public function render_portal_page() {
        $page_slug = isset($_GET['page']) ? $this->sanitize_page_slug(wp_unslash($_GET['page'])) : '';
        if (empty($page_slug)) {
            $page_slug = is_network_admin() ? 'product-launch-network-validation' : 'product-launch-validation';
        }

        $base_url = $this->get_admin_page_url($page_slug);
        $context = is_network_admin() ? 'network_admin' : 'admin';

        $validation_id = isset($_GET['validation_id']) ? absint($_GET['validation_id']) : 0;
        $user_id = get_current_user_id();

        if (is_network_admin() && 0 === $validation_id) {
            $this->render_network_portal_page();
            return;
        }

        echo '<div class="wrap pl-validation-portal">';
        echo '<h1>' . esc_html__('Idea Validation', 'product-launch') . '</h1>';

        if ($validation_id) {
            $access = new PL_Validation_Access();
            $validation = $access->get_validation($validation_id, $user_id);

            if ($validation) {
                $back_link_url = apply_filters('pl_validation_report_back_url', $base_url, $context);
                include PL_PLUGIN_DIR . 'templates/frontend/validation-report.php';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__('Validation not found or you do not have permission to view it.', 'product-launch') . '</p></div>';
            }

            echo '</div>';
            return;
        }

        if (current_user_can('manage_options') || current_user_can('manage_network_options')) {
            echo '<div class="pl-validation-portal-section pl-validation-portal-section--dashboard">';
            $this->render_dashboard_page(true);
            echo '</div>';
        }

        if (!$user_id) {
            echo '<p>' . esc_html__('You must be logged in to submit idea validations.', 'product-launch') . '</p>';
            echo '</div>';
            return;
        }

        $atts = array(
            'title' => __('Validate Your Business Idea', 'product-launch'),
            'show_quota' => 'yes',
            'redirect' => ''
        );

        $validation_form_url = apply_filters('pl_validation_form_url', $base_url, $context);
        $validation_report_base = apply_filters('pl_validation_report_base', $base_url, $context);

        echo '<div class="pl-validation-portal-grid">';

        echo '<div class="pl-validation-portal-section pl-validation-portal-section--form">';
        include PL_PLUGIN_DIR . 'templates/frontend/validation-form.php';
        echo '</div>';

        $per_page = 10;
        $paged = isset($_GET['validation_page']) ? max(1, (int) $_GET['validation_page']) : 1;
        $offset = ($paged - 1) * $per_page;

        $access = new PL_Validation_Access();
        $validations = $access->get_user_validations($user_id, array(
            'limit' => $per_page,
            'offset' => $offset
        ));

        echo '<div class="pl-validation-portal-section pl-validation-portal-section--list">';
        include PL_PLUGIN_DIR . 'templates/frontend/my-validations.php';
        echo '</div>';

        echo '</div>';

        echo '</div>';
    }

    /**
     * Render the network (super admin) portal page with idea discovery tools.
     */
    private function render_network_portal_page() {
        if (!current_user_can('manage_network_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'product-launch'));
        }

        $ideas_library_url = $this->get_admin_page_url('product-launch-network-ideas-library');
        $dashboard_url = $this->get_admin_page_url('product-launch-network-validation-dashboard');

        include PL_PLUGIN_DIR . 'templates/admin/network-validation-search.php';
    }
}

if (is_admin()) {
    new PL_Validation_Admin();
}
