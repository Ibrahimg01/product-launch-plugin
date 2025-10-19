<?php
/**
 * Validation Frontend Handler
 * Manages user-facing validation interface
 */

if (!defined('ABSPATH')) {
    exit;
}

class PL_Validation_Frontend {
    public function __construct() {
        add_shortcode('pl_validation_form', array($this, 'render_validation_form'));
        add_shortcode('pl_my_validations', array($this, 'render_my_validations'));
        add_shortcode('pl_validation_report', array($this, 'render_validation_report'));

        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_ajax_pl_submit_validation', array($this, 'ajax_submit_validation'));
        add_action('wp_ajax_pl_get_validation_status', array($this, 'ajax_get_validation_status'));
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!is_admin() && is_user_logged_in()) {
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
                'loginUrl' => wp_login_url(get_permalink()),
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
     * Render validation form shortcode
     */
    public function render_validation_form($atts) {
        if (!is_user_logged_in()) {
            return $this->render_backend_login_message(
                __('Idea validation is available from the Product Launch backend. Please log in to continue.', 'product-launch')
            );
        }

        $atts = shortcode_atts(array(
            'title' => __('Validate Your Business Idea', 'product-launch'),
            'show_quota' => 'yes',
            'redirect' => ''
        ), $atts);

        ob_start();
        include PL_PLUGIN_DIR . 'templates/frontend/validation-form.php';
        return ob_get_clean();
    }

    /**
     * Render user's validations list
     */
    public function render_my_validations($atts) {
        if (!is_user_logged_in()) {
            return $this->render_backend_login_message(
                __('Please log in to your Product Launch backend to view validations.', 'product-launch')
            );
        }

        $atts = shortcode_atts(array(
            'per_page' => 10,
            'show_filters' => 'yes'
        ), $atts);

        $user_id = get_current_user_id();
        $access = new PL_Validation_Access();

        $paged = isset($_GET['validation_page']) ? max(1, intval($_GET['validation_page'])) : 1;
        $offset = ($paged - 1) * $atts['per_page'];

        $validations = $access->get_user_validations($user_id, array(
            'limit' => $atts['per_page'],
            'offset' => $offset
        ));

        ob_start();
        include PL_PLUGIN_DIR . 'templates/frontend/my-validations.php';
        return ob_get_clean();
    }

    /**
     * Render validation report
     */
    public function render_validation_report($atts) {
        $atts = shortcode_atts(array(
            'id' => isset($_GET['validation_id']) ? intval($_GET['validation_id']) : 0
        ), $atts);

        if (!$atts['id']) {
            return '<div class="pl-error">' . __('No validation ID provided.', 'product-launch') . '</div>';
        }

        if (!is_user_logged_in()) {
            return $this->render_backend_login_message(
                __('Please log in to your Product Launch backend to view validation reports.', 'product-launch')
            );
        }

        $access = new PL_Validation_Access();
        $validation = $access->get_validation($atts['id']);

        if (!$validation) {
            return '<div class="pl-error">' . __('Validation not found or you do not have permission to view it.', 'product-launch') . '</div>';
        }

        ob_start();
        include PL_PLUGIN_DIR . 'templates/frontend/validation-report.php';
        return ob_get_clean();
    }

    /**
     * AJAX: Submit validation
     */
    public function ajax_submit_validation() {
        check_ajax_referer('pl_validation_frontend', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Please log in to validate your idea.', 'product-launch')));
        }

        $business_idea = isset($_POST['business_idea']) ? sanitize_textarea_field($_POST['business_idea']) : '';

        if (empty($business_idea)) {
            wp_send_json_error(array('message' => __('Please enter your business idea.', 'product-launch')));
        }

        if (strlen($business_idea) < 20) {
            wp_send_json_error(array('message' => __('Please provide more details (minimum 20 characters).', 'product-launch')));
        }

        $user_id = get_current_user_id();
        $site_id = get_current_blog_id();

        // Check quota
        $quota = new PL_Validation_Quota();
        if (!$quota->can_validate($user_id, $site_id)) {
            $quota_info = $quota->get_quota_info($user_id, $site_id);
            wp_send_json_error(array(
                'message' => sprintf(
                    __('You have reached your monthly limit of %d validations. Upgrade for unlimited access.', 'product-launch'),
                    $quota_info['limit']
                ),
                'quota_exceeded' => true
            ));
        }

        // Submit to API
        $api = new PL_Validation_API();
        $result = $api->submit_idea($business_idea, $user_id, $site_id);

        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to submit validation. Please try again.', 'product-launch')));
        }

        // Increment quota usage
        $quota->increment_usage($user_id, $site_id);

        wp_send_json_success(array(
            'message' => __('Validation completed successfully!', 'product-launch'),
            'validation_id' => $result['local_id'],
            'external_id' => $result['external_id'],
            'score' => isset($result['data']['adjusted_score']) ? $result['data']['adjusted_score'] : 0,
            'redirect_url' => add_query_arg('validation_id', $result['local_id'], get_permalink())
        ));
    }

    /**
     * AJAX: Get validation status (for polling)
     */
    public function ajax_get_validation_status() {
        check_ajax_referer('pl_validation_frontend', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $validation_id = isset($_POST['validation_id']) ? absint($_POST['validation_id']) : 0;

        if (!$validation_id) {
            wp_send_json_error(array('message' => 'Invalid validation ID'));
        }

        $access = new PL_Validation_Access();
        $validation = $access->get_validation($validation_id);

        if (!$validation) {
            wp_send_json_error(array('message' => 'Validation not found'));
        }

        wp_send_json_success(array(
            'status' => $validation->validation_status,
            'score' => $validation->validation_score,
            'enrichment_status' => $validation->enrichment_status
        ));
    }

    /**
     * Render a standard login required message for backend-only features.
     *
     * @param string $message Custom message to display.
     * @return string
     */
    private function render_backend_login_message($message) {
        $login_url = wp_login_url(admin_url());

        return '<div class="pl-login-required">'
            . '<p>' . esc_html($message) . '</p>'
            . '<a href="' . esc_url($login_url) . '" class="button">' . esc_html__('Log In', 'product-launch') . '</a>'
            . '</div>';
    }
}

// Initialize frontend
new PL_Validation_Frontend();
