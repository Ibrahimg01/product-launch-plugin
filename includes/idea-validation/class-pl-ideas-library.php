<?php
/**
 * Ideas Library Manager
 * Manages pre-validated business ideas library
 */

if (!defined('ABSPATH')) {
    exit;
}

class PL_Ideas_Library {

    /**
     * Validation API client instance.
     *
     * @var PL_Validation_API
     */
    private $api;

    public function __construct() {
        $this->api = new PL_Validation_API();

        add_shortcode('pl_ideas_library', array($this, 'render_library'));
        add_shortcode('pl_idea_details', array($this, 'render_idea_details'));

        add_action('wp_ajax_pl_load_library_ideas', array($this, 'ajax_load_ideas'));

        add_action('wp_ajax_pl_get_idea_details', array($this, 'ajax_get_idea_details'));

        add_action('wp_ajax_pl_push_to_phases', array($this, 'ajax_push_to_phases'));

        // Schedule daily sync of library ideas.
        if (!wp_next_scheduled('pl_sync_library_ideas')) {
            wp_schedule_event(time(), 'daily', 'pl_sync_library_ideas');
        }
        add_action('pl_sync_library_ideas', array($this, 'sync_library_ideas'));
    }

    /**
     * Render ideas library shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_library($atts) {
        if (!is_user_logged_in()) {
            return $this->render_backend_login_message(
                __('Please log in to your Product Launch backend to browse the ideas library.', 'product-launch')
            );
        }

        $atts = shortcode_atts(array(
            'per_page' => 12,
            'show_filters' => 'yes',
            'min_score' => 40,
            'enriched_only' => 'yes',
        ), $atts);

        wp_enqueue_style('pl-validation-frontend');
        wp_enqueue_script('pl-validation-frontend');

        $details_page = get_page_by_path('idea-details');
        $details_url_base = $details_page ? get_permalink($details_page) : get_permalink();
        $details_url_base = add_query_arg('idea_id', '__IDEA_ID__', $details_url_base);

        wp_localize_script('pl-validation-frontend', 'plLibrary', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pl_library'),
            'perPage' => (int) $atts['per_page'],
            'minScore' => (int) $atts['min_score'],
            'enrichedOnly' => $atts['enriched_only'],
            'detailsUrl' => $details_url_base,
        ));

        ob_start();
        include PL_PLUGIN_DIR . 'templates/frontend/ideas-library.php';
        return ob_get_clean();
    }

    /**
     * Render idea details shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_idea_details($atts) {
        if (!is_user_logged_in()) {
            return $this->render_backend_login_message(
                __('Please log in to your Product Launch backend to view idea details.', 'product-launch')
            );
        }

        $atts = shortcode_atts(array(
            'id' => isset($_GET['idea_id']) ? sanitize_text_field(wp_unslash($_GET['idea_id'])) : '',
        ), $atts);

        if (empty($atts['id'])) {
            return '<div class="pl-error">' . esc_html__('No idea ID provided.', 'product-launch') . '</div>';
        }

        wp_enqueue_style('pl-validation-frontend');
        wp_enqueue_script('pl-validation-frontend');

        $library_page = get_page_by_path('ideas-library');
        $library_url = $library_page ? get_permalink($library_page) : '';

        wp_localize_script('pl-validation-frontend', 'plLibrary', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pl_library'),
            'libraryUrl' => $library_url,
        ));

        ob_start();
        include PL_PLUGIN_DIR . 'templates/frontend/idea-details.php';
        return ob_get_clean();
    }

    /**
     * AJAX handler: Load library ideas.
     */
    public function ajax_load_ideas() {
        check_ajax_referer('pl_library', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => esc_html__('Authentication required.', 'product-launch')));
        }

        $page = isset($_POST['page']) ? max(1, (int) $_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? max(1, min(50, (int) $_POST['per_page'])) : 12;
        $min_score = isset($_POST['min_score']) ? (int) $_POST['min_score'] : 40;
        $enriched_only = isset($_POST['enriched_only']) && 'yes' === $_POST['enriched_only'];
        $search = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $category = isset($_POST['category']) ? sanitize_text_field(wp_unslash($_POST['category'])) : '';
        $sort = isset($_POST['sort']) ? sanitize_text_field(wp_unslash($_POST['sort'])) : 'score_desc';

        $params = array(
            'page' => $page,
            'per_page' => $per_page,
            'min_score' => $min_score,
        );

        if ($enriched_only) {
            $params['enriched_only'] = 'true';
        }

        if (!empty($search)) {
            $params['search'] = $search;
        }

        if (!empty($category)) {
            $params['category'] = $category;
        }

        $sort_map = array(
            'score_desc' => array('sort_by' => 'score', 'order' => 'desc'),
            'score_asc' => array('sort_by' => 'score', 'order' => 'asc'),
            'date_desc' => array('sort_by' => 'date', 'order' => 'desc'),
            'date_asc' => array('sort_by' => 'date', 'order' => 'asc'),
        );

        if (isset($sort_map[$sort])) {
            $params = array_merge($params, $sort_map[$sort]);
        }

        $ideas = $this->fetch_library_ideas($params);

        if (false === $ideas) {
            wp_send_json_error(array('message' => esc_html__('Failed to load ideas from library.', 'product-launch')));
        }

        wp_send_json_success($ideas);
    }

    /**
     * AJAX handler: Get idea details.
     */
    public function ajax_get_idea_details() {
        check_ajax_referer('pl_library', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => esc_html__('Authentication required.', 'product-launch')));
        }

        $idea_id = isset($_POST['idea_id']) ? sanitize_text_field(wp_unslash($_POST['idea_id'])) : '';

        if (empty($idea_id)) {
            wp_send_json_error(array('message' => esc_html__('Invalid idea ID.', 'product-launch')));
        }

        $idea = $this->api->get_validation($idea_id, 'core');

        if (!$idea) {
            wp_send_json_error(array('message' => esc_html__('Idea not found.', 'product-launch')));
        }

        wp_send_json_success(array('idea' => $idea));
    }

    /**
     * Render a login required message for backend-only views.
     *
     * @param string $message Notice message.
     * @return string
     */
    private function render_backend_login_message($message) {
        $login_url = wp_login_url(admin_url());

        return '<div class="pl-login-required">'
            . '<p>' . esc_html($message) . '</p>'
            . '<a href="' . esc_url($login_url) . '" class="button">' . esc_html__('Log In', 'product-launch') . '</a>'
            . '</div>';
    }

    /**
     * AJAX handler: Push idea to 8 phases.
     */
    public function ajax_push_to_phases() {
        check_ajax_referer('pl_library', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => esc_html__('Please log in to use this feature.', 'product-launch')));
        }

        $idea_id = isset($_POST['idea_id']) ? sanitize_text_field(wp_unslash($_POST['idea_id'])) : '';
        $source_type = isset($_POST['source_type']) ? sanitize_text_field(wp_unslash($_POST['source_type'])) : 'library';

        if (empty($idea_id)) {
            wp_send_json_error(array('message' => esc_html__('Invalid idea ID.', 'product-launch')));
        }

        $user_id = get_current_user_id();
        $site_id = get_current_blog_id();

        if ('validation' === $source_type) {
            $access = new PL_Validation_Access();
            $validation = $access->get_validation((int) $idea_id, $user_id);

            if (!$validation) {
                wp_send_json_error(array('message' => esc_html__('Validation not found or access denied.', 'product-launch')));
            }

            $idea_data = json_decode($validation->core_data, true);
            $business_idea = $validation->business_idea;
            $external_id = $validation->external_api_id;
        } else {
            $idea_data = $this->api->get_validation($idea_id, 'core');

            if (!$idea_data || !is_array($idea_data)) {
                wp_send_json_error(array('message' => esc_html__('Idea not found in library.', 'product-launch')));
            }

            $business_idea = isset($idea_data['business_idea']) ? $idea_data['business_idea'] : '';
            $external_id = $idea_id;
        }

        if (empty($business_idea)) {
            $business_idea = isset($idea_data['summary']) ? $idea_data['summary'] : '';
        }

        if (empty($business_idea)) {
            $business_idea = esc_html__('New Launch Project', 'product-launch');
        }

        $project_id = $this->create_launch_project($user_id, $site_id, $business_idea, $idea_data, $external_id);

        if (!$project_id) {
            wp_send_json_error(array('message' => esc_html__('Failed to create launch project.', 'product-launch')));
        }

        $this->populate_phases($project_id, $idea_data, $external_id);

        $project_url = $this->get_project_url($project_id);

        wp_send_json_success(array(
            'message' => esc_html__('Idea successfully pushed to 8-phase system!', 'product-launch'),
            'project_id' => $project_id,
            'redirect_url' => $project_url,
        ));
    }

    /**
     * Fetch ideas from library API.
     *
     * @param array $params Query parameters.
     * @return array|false
     */
    private function fetch_library_ideas($params = array()) {
        $endpoint = get_option('pl_validation_api_endpoint', 'https://api.explodingstartup.com/api/ideas');
        $api_key = get_option('pl_validation_api_key', 'exp_live_05663cf87e3b406780a939cf079e59f3');

        $query_string = http_build_query($params);
        $url = trailingslashit($endpoint) . 'cards?' . $query_string;

        $response = wp_remote_get($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-API-Key' => $api_key,
            ),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            error_log('PL Library API Error: ' . $response->get_error_message());
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if (200 !== (int) $code) {
            error_log('PL Library API HTTP Error: ' . $code);
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        return $data;
    }

    /**
     * Create launch project from validated idea.
     *
     * @param int    $user_id        User ID.
     * @param int    $site_id        Site ID.
     * @param string $business_idea  Business idea summary.
     * @param array  $idea_data      Validation data.
     * @param string $external_id    External validation ID.
     * @return int|false
     */
    private function create_launch_project($user_id, $site_id, $business_idea, $idea_data, $external_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pl_projects';

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND validation_external_id = %s",
            $user_id,
            $external_id
        ));

        if ($existing) {
            return (int) $existing;
        }

        $project_name = wp_trim_words($business_idea, 10, '...');
        if (empty($project_name)) {
            $project_name = esc_html__('Launch Project', 'product-launch');
        }

        $inserted = $wpdb->insert($table, array(
            'user_id' => $user_id,
            'site_id' => $site_id,
            'project_name' => $project_name,
            'project_description' => $business_idea,
            'validation_score' => isset($idea_data['adjusted_score']) ? (int) $idea_data['adjusted_score'] : 0,
            'validation_external_id' => $external_id,
            'status' => 'active',
            'current_phase' => 1,
            'created_at' => current_time('mysql'),
        ));

        if (false === $inserted) {
            return false;
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Populate all project phases.
     *
     * @param int    $project_id Project ID.
     * @param array  $idea_data  Idea data.
     * @param string $external_id External validation ID.
     */
    private function populate_phases($project_id, $idea_data, $external_id) {
        $this->populate_phase_1($project_id, $idea_data, $external_id);
        $this->populate_phase_2($project_id, $idea_data, $external_id);
        $this->populate_phase_3($project_id, $idea_data, $external_id);
        $this->populate_phase_4($project_id, $idea_data, $external_id);
        $this->populate_phase_5($project_id, $idea_data, $external_id);
        $this->populate_phase_6($project_id, $idea_data, $external_id);
        $this->populate_phase_7($project_id, $idea_data, $external_id);
        $this->populate_phase_8($project_id, $idea_data, $external_id);
    }

    /**
     * Phase 1: Market Research.
     */
    private function populate_phase_1($project_id, $idea_data, $external_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pl_phase_data';

        $trends = $this->api->get_cached_section($external_id, 'enrichment/trends');
        if (!$trends) {
            $trends = $this->api->get_validation($external_id, 'enrichment/trends');
        }

        $competitors = $this->api->get_cached_section($external_id, 'enrichment/competitors');
        if (!$competitors) {
            $competitors = $this->api->get_validation($external_id, 'enrichment/competitors');
        }

        $markets = $this->api->get_cached_section($external_id, 'enrichment/markets');
        if (!$markets) {
            $markets = $this->api->get_validation($external_id, 'enrichment/markets');
        }

        $phase_data = array(
            'market_trends' => $trends,
            'competitors' => $competitors,
            'target_markets' => $markets,
            'validation_score' => isset($idea_data['adjusted_score']) ? (int) $idea_data['adjusted_score'] : 0,
            'confidence_level' => isset($idea_data['confidence_level']) ? $idea_data['confidence_level'] : null,
        );

        $wpdb->replace($table, array(
            'project_id' => $project_id,
            'phase_number' => 1,
            'phase_name' => 'Market Research',
            'phase_data' => wp_json_encode($phase_data),
            'status' => 'completed',
            'completed_at' => current_time('mysql'),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));
    }

    /**
     * Phase 2: Product Definition.
     */
    private function populate_phase_2($project_id, $idea_data, $external_id) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
        global $wpdb;
        $table = $wpdb->prefix . 'pl_phase_data';

        $analysis = isset($idea_data['analysis']) && is_array($idea_data['analysis']) ? $idea_data['analysis'] : array();

        $phase_data = array(
            'problem_statement' => isset($analysis['problem']) ? $analysis['problem'] : '',
            'solution' => isset($analysis['solution']) ? $analysis['solution'] : '',
            'key_features' => isset($analysis['key_features']) ? $analysis['key_features'] : array(),
            'value_proposition' => isset($idea_data['value_proposition']) ? $idea_data['value_proposition'] : '',
            'unique_selling_points' => isset($analysis['recommendations']) ? $analysis['recommendations'] : array(),
        );

        $wpdb->replace($table, array(
            'project_id' => $project_id,
            'phase_number' => 2,
            'phase_name' => 'Product Definition',
            'phase_data' => wp_json_encode($phase_data),
            'status' => 'in_progress',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));
    }

    /**
     * Phase 3: Branding & Positioning.
     */
    private function populate_phase_3($project_id, $idea_data, $external_id) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
        global $wpdb;
        $table = $wpdb->prefix . 'pl_phase_data';

        $branding = $this->api->get_cached_section($external_id, 'enrichment/branding');
        if (!$branding) {
            $branding = $this->api->get_validation($external_id, 'enrichment/branding');
        }

        $phase_data = array(
            'brand_names' => isset($branding['brand_names']) ? $branding['brand_names'] : array(),
            'positioning' => isset($branding['differentiator']) ? $branding['differentiator'] : '',
            'target_audiences' => isset($branding['target_audiences']) ? $branding['target_audiences'] : array(),
            'messaging' => isset($branding['value_proposition']) ? $branding['value_proposition'] : '',
        );

        $wpdb->replace($table, array(
            'project_id' => $project_id,
            'phase_number' => 3,
            'phase_name' => 'Branding & Positioning',
            'phase_data' => wp_json_encode($phase_data),
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));
    }

    /**
     * Phase 4: Technical Planning.
     */
    private function populate_phase_4($project_id, $idea_data, $external_id) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
        global $wpdb;
        $table = $wpdb->prefix . 'pl_phase_data';

        $branding = $this->api->get_cached_section($external_id, 'enrichment/branding');
        if (!$branding) {
            $branding = $this->api->get_validation($external_id, 'enrichment/branding');
        }

        $phase_data = array(
            'implementation_strategies' => isset($branding['implementation_strategies']) ? $branding['implementation_strategies'] : array(),
            'technical_requirements' => array(),
            'development_roadmap' => array(),
        );

        $wpdb->replace($table, array(
            'project_id' => $project_id,
            'phase_number' => 4,
            'phase_name' => 'Technical Planning',
            'phase_data' => wp_json_encode($phase_data),
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));
    }

    /**
     * Phase 5: Marketing Strategy.
     */
    private function populate_phase_5($project_id, $idea_data, $external_id) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
        global $wpdb;
        $table = $wpdb->prefix . 'pl_phase_data';

        $seo = $this->api->get_cached_section($external_id, 'enrichment/seo');
        if (!$seo) {
            $seo = $this->api->get_validation($external_id, 'enrichment/seo');
        }

        $phase_data = array(
            'seo_keywords' => isset($seo['seo_keywords']) ? $seo['seo_keywords'] : array(),
            'content_strategy' => array(),
            'social_media_plan' => array(),
            'paid_advertising' => array(),
        );

        $wpdb->replace($table, array(
            'project_id' => $project_id,
            'phase_number' => 5,
            'phase_name' => 'Marketing Strategy',
            'phase_data' => wp_json_encode($phase_data),
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));
    }

    /**
     * Phase 6: Launch Preparation.
     */
    private function populate_phase_6($project_id, $idea_data, $external_id) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
        global $wpdb;
        $table = $wpdb->prefix . 'pl_phase_data';

        $phase_data = array(
            'launch_checklist' => array(),
            'beta_testing_plan' => array(),
            'feedback_collection' => array(),
        );

        $wpdb->replace($table, array(
            'project_id' => $project_id,
            'phase_number' => 6,
            'phase_name' => 'Launch Preparation',
            'phase_data' => wp_json_encode($phase_data),
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));
    }

    /**
     * Phase 7: Go-to-Market.
     */
    private function populate_phase_7($project_id, $idea_data, $external_id) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
        global $wpdb;
        $table = $wpdb->prefix . 'pl_phase_data';

        $reddit = $this->api->get_cached_section($external_id, 'platforms/reddit');
        if (!$reddit) {
            $reddit = $this->api->get_validation($external_id, 'platforms/reddit');
        }

        $hackernews = $this->api->get_cached_section($external_id, 'platforms/hackernews');
        if (!$hackernews) {
            $hackernews = $this->api->get_validation($external_id, 'platforms/hackernews');
        }

        $producthunt = $this->api->get_cached_section($external_id, 'platforms/producthunt');
        if (!$producthunt) {
            $producthunt = $this->api->get_validation($external_id, 'platforms/producthunt');
        }

        $phase_data = array(
            'launch_channels' => array(
                'reddit' => $reddit,
                'hackernews' => $hackernews,
                'producthunt' => $producthunt,
            ),
            'pr_strategy' => array(),
            'influencer_outreach' => array(),
        );

        $wpdb->replace($table, array(
            'project_id' => $project_id,
            'phase_number' => 7,
            'phase_name' => 'Go-to-Market',
            'phase_data' => wp_json_encode($phase_data),
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));
    }

    /**
     * Phase 8: Growth & Scale.
     */
    private function populate_phase_8($project_id, $idea_data, $external_id) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
        global $wpdb;
        $table = $wpdb->prefix . 'pl_phase_data';

        $phase_data = array(
            'growth_metrics' => array(),
            'scaling_plan' => array(),
            'optimization_strategies' => array(),
        );

        $wpdb->replace($table, array(
            'project_id' => $project_id,
            'phase_number' => 8,
            'phase_name' => 'Growth & Scale',
            'phase_data' => wp_json_encode($phase_data),
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));
    }

    /**
     * Build project URL.
     *
     * @param int $project_id Project ID.
     * @return string
     */
    private function get_project_url($project_id) {
        $url = home_url('/project/');

        return add_query_arg('id', (int) $project_id, $url);
    }

    /**
     * Sync library ideas (scheduled daily).
     */
    public function sync_library_ideas() {
        $ideas = $this->fetch_library_ideas(array(
            'page' => 1,
            'per_page' => 50,
            'min_score' => 70,
            'enriched_only' => 'true',
            'sort_by' => 'score',
            'order' => 'desc',
        ));

        if ($ideas) {
            set_transient('pl_cached_top_ideas', $ideas, DAY_IN_SECONDS);
        }
    }
}

new PL_Ideas_Library();
