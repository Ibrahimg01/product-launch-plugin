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

    /**
     * Validation access helper.
     *
     * @var PL_Validation_Access
     */
    private $access;

    /**
     * Custom category assignments indexed by idea identifier.
     *
     * @var array
     */
    private $category_map = array();

    /**
     * Library category labels indexed by slug.
     *
     * @var array
     */
    private $category_labels = array();

    public function __construct() {
        $this->api = new PL_Validation_API();
        $this->access = new PL_Validation_Access();
        $this->category_map = pl_get_library_category_map();
        $categories = pl_get_library_categories();
        $this->category_labels = wp_list_pluck($categories, 'label', 'slug');

        add_shortcode('pl_ideas_library', array($this, 'render_library'));
        add_shortcode('pl_idea_details', array($this, 'render_idea_details'));

        add_action('wp_ajax_pl_load_library_ideas', array($this, 'ajax_load_ideas'));

        add_action('wp_ajax_pl_get_idea_details', array($this, 'ajax_get_idea_details'));

        add_action('wp_ajax_pl_push_to_phases', array($this, 'ajax_push_to_phases'));

        add_action('init', array($this, 'maybe_create_pages'));

        // Schedule daily sync of library ideas.
        if (!wp_next_scheduled('pl_sync_library_ideas')) {
            wp_schedule_event(time(), 'daily', 'pl_sync_library_ideas');
        }
        add_action('pl_sync_library_ideas', array($this, 'sync_library_ideas'));
    }

    /**
     * Ensure the public library and detail pages exist for the current site.
     */
    public function maybe_create_pages() {
        if (defined('WP_INSTALLING') && WP_INSTALLING) {
            return;
        }

        $this->ensure_page_exists('ideas-library', __('Ideas Library', 'product-launch'), '[pl_ideas_library]');
        $this->ensure_page_exists('idea-details', __('Idea Details', 'product-launch'), '[pl_idea_details]');
    }

    /**
     * Reload category configuration from the database.
     */
    private function refresh_category_context() {
        $categories = pl_get_library_categories();
        $this->category_map = pl_get_library_category_map();
        $this->category_labels = wp_list_pluck($categories, 'label', 'slug');

        return $categories;
    }

    /**
     * Create a page if it does not already exist.
     *
     * @param string $slug      Desired page slug.
     * @param string $title     Page title.
     * @param string $shortcode Page content shortcode.
     */
    private function ensure_page_exists($slug, $title, $shortcode) {
        $existing = get_page_by_path($slug);

        if ($existing instanceof WP_Post) {
            return;
        }

        wp_insert_post(array(
            'post_title'   => $title,
            'post_name'    => sanitize_title($slug),
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => $shortcode,
            'post_author'  => get_current_user_id(),
        ));
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

        $categories = $this->refresh_category_context();

        wp_localize_script('pl-validation-frontend', 'plLibrary', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pl_library'),
            'perPage' => (int) $atts['per_page'],
            'minScore' => (int) $atts['min_score'],
            'enrichedOnly' => $atts['enriched_only'],
            'detailsUrl' => $details_url_base,
            'categoryLabels' => $this->category_labels,
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

        $this->refresh_category_context();

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

        $this->refresh_category_context();

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

        $cache_key = $this->build_cache_key($params, $page, $search, $category, $sort);
        $cached = $this->get_cached_library_payload($cache_key);

        if (false !== $cached) {
            wp_send_json_success($cached);
        }

        $local_items = $this->get_local_library_items(array(
            'min_score' => $min_score,
            'enriched_only' => $enriched_only,
            'search' => $search,
            'category' => $category,
            'sort' => $sort,
        ));

        $ideas = $this->fetch_library_ideas($params);
        $payload = $this->merge_library_payload($ideas, $local_items, $page, $per_page);

        if (false === $payload) {
            wp_send_json_error(array('message' => esc_html__('Failed to load ideas from library.', 'product-launch')));
        }

        $this->set_cached_library_payload($cache_key, $payload);

        wp_send_json_success($payload);
    }

    /**
     * AJAX handler: Get idea details.
     */
    public function ajax_get_idea_details() {
        check_ajax_referer('pl_library', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => esc_html__('Authentication required.', 'product-launch')));
        }

        $this->refresh_category_context();

        $idea_id = isset($_POST['idea_id']) ? sanitize_text_field(wp_unslash($_POST['idea_id'])) : '';

        if (empty($idea_id)) {
            wp_send_json_error(array('message' => esc_html__('Invalid idea ID.', 'product-launch')));
        }

        if ($this->is_local_idea($idea_id)) {
            $idea = $this->get_local_idea_details($idea_id);
        } else {
            $idea = $this->api->get_validation($idea_id, 'core');
            if ($idea) {
                $idea['source_type'] = 'library';
                $idea['id'] = $idea_id;
            }
        }

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
        $endpoint = pl_get_validation_option('pl_validation_api_endpoint', 'https://api.explodingstartup.com/api/ideas');
        $api_key = pl_get_validation_option('pl_validation_api_key', 'exp_live_05663cf87e3b406780a939cf079e59f3');

        if (empty($endpoint)) {
            $endpoint = 'https://api.explodingstartup.com/api/ideas';
        }

        $query_string = http_build_query($params);
        $url = trailingslashit(untrailingslashit($endpoint)) . 'cards?' . $query_string;

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
     * Build a cache key for the supplied query params.
     */
    private function build_cache_key($params, $page, $search, $category, $sort) {
        $key_data = array_merge(
            $params,
            array(
                'page' => (int) $page,
                'search' => (string) $search,
                'category' => (string) $category,
                'sort' => (string) $sort,
            )
        );

        return 'pl_library_cache_' . md5(wp_json_encode($key_data));
    }

    /**
     * Retrieve cached library payload if available.
     */
    private function get_cached_library_payload($cache_key) {
        if (empty($cache_key)) {
            return false;
        }

        return is_multisite() ? get_site_transient($cache_key) : get_transient($cache_key);
    }

    /**
     * Store the combined library payload in cache.
     */
    private function set_cached_library_payload($cache_key, $payload) {
        if (empty($cache_key) || !is_array($payload)) {
            return;
        }

        $duration = $this->get_cache_duration();

        if (is_multisite()) {
            set_site_transient($cache_key, $payload, $duration);
        } else {
            set_transient($cache_key, $payload, $duration);
        }

        if (function_exists('pl_register_library_cache_key')) {
            pl_register_library_cache_key($cache_key);
        }
    }

    /**
     * Determine the cache lifetime in seconds.
     */
    private function get_cache_duration() {
        $hours = (int) pl_get_validation_option('pl_validation_cache_duration', 24);

        return max(1, $hours) * HOUR_IN_SECONDS;
    }

    /**
     * Fetch validations published by the network and format for the library feed.
     */
    private function get_local_library_items($args = array()) {
        $defaults = array(
            'min_score' => 0,
            'enriched_only' => false,
            'search' => '',
            'category' => '',
            'sort' => 'score_desc',
        );

        $args = wp_parse_args($args, $defaults);

        $validations = $this->access->get_published_validations(array(
            'min_score' => $args['min_score'],
            'enriched_only' => $args['enriched_only'],
            'search' => $args['search'],
            'order_by' => 'published_at',
            'order' => 'DESC',
        ));

        if (empty($validations)) {
            return array();
        }

        $items = array();

        foreach ($validations as $validation) {
            $formatted = $this->format_validation_for_library($validation);

            if (!$formatted) {
                continue;
            }

            if (!empty($args['category']) && !$this->idea_matches_category($formatted, $args['category'])) {
                continue;
            }

            $items[] = $formatted;
        }

        if (empty($items)) {
            return array();
        }

        $items = $this->sort_local_items($items, $args['sort']);

        return $this->apply_category_overrides($items);
    }

    /**
     * Convert a validation record into the structure expected by the ideas library.
     */
    private function format_validation_for_library($validation) {
        if (empty($validation->core_data)) {
            return null;
        }

        $core = json_decode($validation->core_data, true);

        if (!is_array($core)) {
            return null;
        }

        $idea = $core;
        $idea['id'] = 'local-' . (int) $validation->id;
        $idea['source_type'] = 'validation';
        $idea['local_validation_id'] = (int) $validation->id;
        $idea['business_idea'] = $validation->business_idea;
        $idea['validation_score'] = (int) $validation->validation_score;
        $idea['adjusted_score'] = isset($core['adjusted_score']) ? (int) $core['adjusted_score'] : (int) $validation->validation_score;
        $idea['enriched'] = ('completed' === $validation->enrichment_status);
        $idea['validation_date'] = isset($core['validation_date']) ? $core['validation_date'] : $validation->created_at;
        $idea['published_at'] = $validation->published_at;
        $idea['origin_label'] = __('Network Published', 'product-launch');
        $idea['category_slugs'] = $this->extract_category_slugs($core);
        $override = $this->get_category_override($idea['id']);

        if (!empty($override)) {
            $idea['category_slugs'] = $override;
        }

        $idea['category_labels'] = $this->map_slugs_to_labels($idea['category_slugs']);
        if (empty($idea['external_api_id'])) {
            $idea['external_api_id'] = 'local-' . (int) $validation->id;
        }

        return $idea;
    }

    /**
     * Extract normalized category slugs from core data.
     */
    private function extract_category_slugs($core) {
        $slugs = array();

        if (isset($core['classification']) && is_array($core['classification'])) {
            $classification = $core['classification'];

            if (!empty($classification['primary_category_slug'])) {
                $slugs[] = sanitize_title($classification['primary_category_slug']);
            }

            if (!empty($classification['primary_category'])) {
                $slugs[] = sanitize_title($classification['primary_category']);
            }

            if (!empty($classification['industries']) && is_array($classification['industries'])) {
                foreach ($classification['industries'] as $industry) {
                    if (is_string($industry)) {
                        $slugs[] = sanitize_title($industry);
                    } elseif (is_array($industry) && isset($industry['slug'])) {
                        $slugs[] = sanitize_title($industry['slug']);
                    }
                }
            }
        }

        $slugs = array_filter(array_unique($slugs));

        return array_values($slugs);
    }

    /**
     * Determine if the idea matches a requested category filter.
     */
    private function idea_matches_category($idea, $category) {
        $category = sanitize_title($category);

        if ('' === $category) {
            return true;
        }

        if (empty($idea['category_slugs'])) {
            return false;
        }

        return in_array($category, $idea['category_slugs'], true);
    }

    /**
     * Retrieve the configured category override for an idea.
     *
     * @param string $identifier Idea identifier.
     * @return array
     */
    private function get_category_override($identifier) {
        $identifier = sanitize_text_field($identifier);

        if ('' === $identifier || empty($this->category_map)) {
            return array();
        }

        if (!isset($this->category_map[$identifier])) {
            return array();
        }

        $slugs = array();

        foreach ((array) $this->category_map[$identifier] as $slug) {
            $slug = sanitize_title($slug);

            if ('' === $slug) {
                continue;
            }

            $slugs[] = $slug;
        }

        return array_values(array_unique($slugs));
    }

    /**
     * Map category slugs to their display labels.
     *
     * @param array $slugs Category slugs.
     * @return array
     */
    private function map_slugs_to_labels($slugs) {
        if (empty($slugs) || empty($this->category_labels)) {
            return array();
        }

        $labels = array();

        foreach ((array) $slugs as $slug) {
            $slug = sanitize_title($slug);

            if ('' === $slug || !isset($this->category_labels[$slug])) {
                continue;
            }

            $labels[] = $this->category_labels[$slug];
        }

        return array_values(array_unique($labels));
    }

    /**
     * Apply configured category overrides to a collection of ideas.
     *
     * @param array $ideas Idea payloads.
     * @return array
     */
    private function apply_category_overrides($ideas) {
        if (empty($ideas) || empty($this->category_map)) {
            return $ideas;
        }

        foreach ($ideas as &$idea) {
            if (!is_array($idea)) {
                continue;
            }

            $identifier = $this->determine_idea_identifier($idea);

            if (!$identifier) {
                continue;
            }

            $override = $this->get_category_override($identifier);

            if (!empty($override)) {
                $idea['category_slugs'] = $override;
                $idea['category_labels'] = $this->map_slugs_to_labels($override);
            } elseif (!empty($idea['category_slugs']) && !isset($idea['category_labels'])) {
                $idea['category_labels'] = $this->map_slugs_to_labels($idea['category_slugs']);
            }
        }

        unset($idea);

        return $ideas;
    }

    /**
     * Determine the identifier used for category overrides.
     *
     * @param array $idea Idea payload.
     * @return string
     */
    private function determine_idea_identifier($idea) {
        if (isset($idea['id']) && is_string($idea['id']) && $idea['id']) {
            return $idea['id'];
        }

        if (isset($idea['external_api_id']) && $idea['external_api_id']) {
            return (string) $idea['external_api_id'];
        }

        return '';
    }

    /**
     * Sort local items in a way that matches remote sort expectations.
     */
    private function sort_local_items($items, $sort) {
        $sort = in_array($sort, array('score_desc', 'score_asc', 'date_desc', 'date_asc'), true) ? $sort : 'score_desc';

        usort($items, function ($a, $b) use ($sort) {
            $score_a = $this->get_idea_score($a);
            $score_b = $this->get_idea_score($b);
            $date_a = isset($a['published_at']) ? strtotime($a['published_at']) : strtotime($a['validation_date'] ?? '');
            $date_b = isset($b['published_at']) ? strtotime($b['published_at']) : strtotime($b['validation_date'] ?? '');

            switch ($sort) {
                case 'score_asc':
                    return $score_a <=> $score_b;
                case 'date_desc':
                    return $date_b <=> $date_a;
                case 'date_asc':
                    return $date_a <=> $date_b;
                case 'score_desc':
                default:
                    return $score_b <=> $score_a;
            }
        });

        return $items;
    }

    /**
     * Retrieve the numeric score for an idea.
     */
    private function get_idea_score($idea) {
        if (isset($idea['adjusted_score'])) {
            return (int) $idea['adjusted_score'];
        }

        if (isset($idea['validation_score'])) {
            return (int) $idea['validation_score'];
        }

        return 0;
    }

    /**
     * Merge local and remote library payloads.
     */
    private function merge_library_payload($remote, $local_items, $page, $per_page) {
        $local_total = is_array($local_items) ? count($local_items) : 0;

        if (false === $remote && 0 === $local_total) {
            return false;
        }

        if (false === $remote) {
            $total_pages = max(1, (int) ceil(max(1, $local_total) / max(1, $per_page)));

            return array(
                'items' => ($page <= 1) ? $local_items : array(),
                'total' => $local_total,
                'total_pages' => $total_pages,
                'page' => $page,
                'local_total' => $local_total,
            );
        }

        $remote_items = isset($remote['items']) && is_array($remote['items']) ? $remote['items'] : array();
        if (!empty($remote_items)) {
            $remote_items = array_map(function ($idea) {
                if (is_array($idea) && !isset($idea['source_type'])) {
                    $idea['source_type'] = 'library';
                }

                return $idea;
            }, $remote_items);
            $remote_items = $this->apply_category_overrides($remote_items);
        }
        $remote_total = isset($remote['total']) ? (int) $remote['total'] : count($remote_items);
        $remote_pages = isset($remote['total_pages']) ? (int) $remote['total_pages'] : 1;

        $payload = $remote;
        $payload['items'] = ($page <= 1)
            ? array_merge($local_items, $remote_items)
            : $remote_items;
        $payload['page'] = $page;
        $payload['local_total'] = $local_total;
        $payload['total'] = $remote_total + $local_total;
        $payload['total_pages'] = max($remote_pages, (int) ceil(max(1, $payload['total']) / max(1, $per_page)));

        return $payload;
    }

    /**
     * Determine if the requested idea ID references a local validation.
     */
    private function is_local_idea($idea_id) {
        return is_string($idea_id) && 0 === strpos($idea_id, 'local-');
    }

    /**
     * Convert a string idea ID into the numeric validation ID.
     */
    private function get_local_validation_id($idea_id) {
        if (is_string($idea_id) && 0 === strpos($idea_id, 'local-')) {
            return (int) substr($idea_id, 6);
        }

        return 0;
    }

    /**
     * Retrieve the formatted idea details for a published local validation.
     */
    private function get_local_idea_details($idea_id) {
        $validation_id = $this->get_local_validation_id($idea_id);

        if (!$validation_id) {
            return null;
        }

        $record = $this->get_published_validation_record($validation_id);

        if (!$record) {
            return null;
        }

        return $this->format_validation_for_library($record);
    }

    /**
     * Fetch a published validation directly from the database.
     */
    private function get_published_validation_record($validation_id) {
        global $wpdb;

        $table = pl_get_validation_table_name(is_multisite() ? 'network' : 'site');

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d AND library_published = 1",
                $validation_id
            )
        );
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
     * Phase 1: Market Clarity.
     */
    private function populate_phase_1($project_id, $idea_data, $external_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pl_phase_data';

        $analysis = isset($idea_data['analysis']) && is_array($idea_data['analysis']) ? $idea_data['analysis'] : array();
        $branding = $this->get_section_data($external_id, 'enrichment/branding');
        $trends = $this->get_section_data($external_id, 'enrichment/trends');
        $competitors = $this->get_section_data($external_id, 'enrichment/competitors');
        $markets = $this->get_section_data($external_id, 'enrichment/markets');

        $phase_data = array(
            'target_audience' => $this->first_non_empty(
                isset($idea_data['target_audience']) ? $idea_data['target_audience'] : '',
                isset($branding['target_audiences']) ? $branding['target_audiences'] : '',
                isset($analysis['audience']) ? $analysis['audience'] : ''
            ),
            'pain_points' => $this->first_non_empty(
                isset($idea_data['customer_pain_points']) ? $idea_data['customer_pain_points'] : '',
                isset($analysis['problem']) ? $analysis['problem'] : ''
            ),
            'value_proposition' => $this->first_non_empty(
                isset($idea_data['value_proposition']) ? $idea_data['value_proposition'] : '',
                isset($branding['value_proposition']) ? $branding['value_proposition'] : ''
            ),
            'market_size' => $this->format_string_list($markets),
            'competitors' => $this->format_string_list($competitors),
            'positioning' => $this->first_non_empty(
                isset($branding['differentiator']) ? $branding['differentiator'] : '',
                isset($idea_data['ai_assessment_summary']) ? $idea_data['ai_assessment_summary'] : ''
            ),
            'market_trends' => $this->format_string_list($trends),
            'validation_score' => isset($idea_data['adjusted_score']) ? (int) $idea_data['adjusted_score'] : 0,
            'confidence_level' => isset($idea_data['confidence_level']) ? $idea_data['confidence_level'] : null,
        );

        $wpdb->replace($table, array(
            'project_id' => $project_id,
            'phase_number' => 1,
            'phase_name' => 'Market Clarity',
            'phase_data' => wp_json_encode($phase_data),
            'status' => 'completed',
            'completed_at' => current_time('mysql'),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));
    }

    /**
     * Phase 2: Create Offer.
     */
    private function populate_phase_2($project_id, $idea_data, $external_id) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
        global $wpdb;
        $table = $wpdb->prefix . 'pl_phase_data';

        $analysis = isset($idea_data['analysis']) && is_array($idea_data['analysis']) ? $idea_data['analysis'] : array();
        $branding = $this->get_section_data($external_id, 'enrichment/branding');

        $phase_data = array(
            'main_offer' => $this->first_non_empty(
                isset($analysis['solution']) ? $analysis['solution'] : '',
                isset($idea_data['business_idea']) ? $idea_data['business_idea'] : '',
                isset($idea_data['summary']) ? $idea_data['summary'] : ''
            ),
            'value_proposition' => $this->first_non_empty(
                isset($idea_data['value_proposition']) ? $idea_data['value_proposition'] : '',
                isset($branding['value_proposition']) ? $branding['value_proposition'] : ''
            ),
            'pricing_strategy' => $this->first_non_empty(
                isset($analysis['business_model']) ? $analysis['business_model'] : '',
                isset($analysis['pricing']) ? $analysis['pricing'] : '',
                isset($idea_data['monetization_strategy']) ? $idea_data['monetization_strategy'] : ''
            ),
            'bonuses' => $this->format_string_list(isset($analysis['recommendations']) ? $analysis['recommendations'] : array()),
            'guarantee' => $this->first_non_empty(
                isset($idea_data['validation_opportunities']) ? $idea_data['validation_opportunities'] : '',
                isset($analysis['risk_mitigation']) ? $analysis['risk_mitigation'] : ''
            ),
            'urgency_scarcity' => $this->format_string_list(isset($idea_data['action_plan']) ? $idea_data['action_plan'] : array()),
        );

        $wpdb->replace($table, array(
            'project_id' => $project_id,
            'phase_number' => 2,
            'phase_name' => 'Create Offer',
            'phase_data' => wp_json_encode($phase_data),
            'status' => 'in_progress',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));
    }

    /**
     * Phase 3: Create Service.
     */
    private function populate_phase_3($project_id, $idea_data, $external_id) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
        global $wpdb;
        $table = $wpdb->prefix . 'pl_phase_data';

        $analysis = isset($idea_data['analysis']) && is_array($idea_data['analysis']) ? $idea_data['analysis'] : array();
        $branding = $this->get_section_data($external_id, 'enrichment/branding');

        $phase_data = array(
            'service_concept' => $this->first_non_empty(
                isset($analysis['solution']) ? $analysis['solution'] : '',
                isset($idea_data['business_idea']) ? $idea_data['business_idea'] : ''
            ),
            'service_packaging' => $this->format_string_list(isset($analysis['key_features']) ? $analysis['key_features'] : array()),
            'delivery_method' => $this->format_string_list(isset($branding['implementation_strategies']) ? $branding['implementation_strategies'] : array()),
            'pricing_model' => $this->first_non_empty(
                isset($analysis['business_model']) ? $analysis['business_model'] : '',
                isset($analysis['pricing']) ? $analysis['pricing'] : '',
                isset($idea_data['monetization_strategy']) ? $idea_data['monetization_strategy'] : ''
            ),
            'client_onboarding' => $this->first_non_empty(
                isset($idea_data['validation_opportunities']) ? $idea_data['validation_opportunities'] : '',
                isset($analysis['risk_mitigation']) ? $analysis['risk_mitigation'] : ''
            ),
            'service_framework' => $this->format_string_list(isset($idea_data['action_plan']) ? $idea_data['action_plan'] : array()),
            'scalability_plan' => $this->first_non_empty(
                isset($idea_data['expansion_ideas']) ? $idea_data['expansion_ideas'] : '',
                isset($analysis['growth']) ? $analysis['growth'] : ''
            ),
        );

        $wpdb->replace($table, array(
            'project_id' => $project_id,
            'phase_number' => 3,
            'phase_name' => 'Create Service',
            'phase_data' => wp_json_encode($phase_data),
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));
    }

    /**
     * Phase 4: Build Funnel.
     */
    private function populate_phase_4($project_id, $idea_data, $external_id) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
        global $wpdb;
        $table = $wpdb->prefix . 'pl_phase_data';

        $analysis = isset($idea_data['analysis']) && is_array($idea_data['analysis']) ? $idea_data['analysis'] : array();
        $branding = $this->get_section_data($external_id, 'enrichment/branding');

        $phase_data = array(
            'funnel_strategy' => $this->first_non_empty(
                isset($idea_data['market_validation_summary']) ? $idea_data['market_validation_summary'] : '',
                $this->format_string_list(isset($idea_data['action_plan']) ? $idea_data['action_plan'] : array())
            ),
            'lead_magnet' => $this->first_non_empty(
                isset($idea_data['validation_opportunities']) ? $idea_data['validation_opportunities'] : '',
                $this->format_string_list(isset($analysis['recommendations']) ? $analysis['recommendations'] : array())
            ),
            'landing_pages' => $this->first_non_empty(
                isset($analysis['solution']) ? $analysis['solution'] : '',
                isset($idea_data['business_idea']) ? $idea_data['business_idea'] : ''
            ),
            'sales_pages' => $this->first_non_empty(
                isset($idea_data['value_proposition']) ? $idea_data['value_proposition'] : '',
                isset($branding['value_proposition']) ? $branding['value_proposition'] : ''
            ),
            'checkout_process' => $this->first_non_empty(
                isset($analysis['business_model']) ? $analysis['business_model'] : '',
                isset($idea_data['monetization_strategy']) ? $idea_data['monetization_strategy'] : ''
            ),
            'upsells_downsells' => $this->first_non_empty(
                isset($idea_data['expansion_ideas']) ? $idea_data['expansion_ideas'] : '',
                $this->format_string_list(isset($analysis['recommendations']) ? $analysis['recommendations'] : array())
            ),
            'funnel_optimization' => $this->first_non_empty(
                isset($idea_data['ai_assessment_summary']) ? $idea_data['ai_assessment_summary'] : '',
                isset($analysis['risk_mitigation']) ? $analysis['risk_mitigation'] : ''
            ),
        );

        $wpdb->replace($table, array(
            'project_id' => $project_id,
            'phase_number' => 4,
            'phase_name' => 'Build Funnel',
            'phase_data' => wp_json_encode($phase_data),
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));
    }

    /**
     * Phase 5: Email Sequences.
     */
    private function populate_phase_5($project_id, $idea_data, $external_id) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
        global $wpdb;
        $table = $wpdb->prefix . 'pl_phase_data';

        $analysis = isset($idea_data['analysis']) && is_array($idea_data['analysis']) ? $idea_data['analysis'] : array();
        $branding = $this->get_section_data($external_id, 'enrichment/branding');
        $seo = $this->get_section_data($external_id, 'enrichment/seo');

        $phase_data = array(
            'email_strategy' => $this->first_non_empty(
                isset($idea_data['market_validation_summary']) ? $idea_data['market_validation_summary'] : '',
                $this->format_string_list(isset($idea_data['action_plan']) ? $idea_data['action_plan'] : array())
            ),
            'email_subject_lines' => $this->format_string_list(isset($seo['seo_keywords']) ? $seo['seo_keywords'] : array()),
            'email_sequence_outline' => $this->format_string_list(isset($idea_data['action_plan']) ? $idea_data['action_plan'] : array()),
            'email_cta' => $this->first_non_empty(
                isset($idea_data['value_proposition']) ? $idea_data['value_proposition'] : '',
                isset($analysis['solution']) ? $analysis['solution'] : ''
            ),
            'automation_setup' => $this->first_non_empty(
                isset($idea_data['validation_opportunities']) ? $idea_data['validation_opportunities'] : '',
                $this->format_string_list(isset($analysis['recommendations']) ? $analysis['recommendations'] : array())
            ),
            'personalization' => $this->first_non_empty(
                isset($idea_data['target_audience']) ? $idea_data['target_audience'] : '',
                isset($branding['target_audiences']) ? $branding['target_audiences'] : ''
            ),
            'testing_optimization' => $this->first_non_empty(
                isset($idea_data['ai_assessment_summary']) ? $idea_data['ai_assessment_summary'] : '',
                isset($analysis['risk_mitigation']) ? $analysis['risk_mitigation'] : ''
            ),
        );

        $wpdb->replace($table, array(
            'project_id' => $project_id,
            'phase_number' => 5,
            'phase_name' => 'Email Sequences',
            'phase_data' => wp_json_encode($phase_data),
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));
    }

    /**
     * Phase 6: Organic Posts.
     */
    private function populate_phase_6($project_id, $idea_data, $external_id) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
        global $wpdb;
        $table = $wpdb->prefix . 'pl_phase_data';

        $analysis = isset($idea_data['analysis']) && is_array($idea_data['analysis']) ? $idea_data['analysis'] : array();
        $branding = $this->get_section_data($external_id, 'enrichment/branding');
        $seo = $this->get_section_data($external_id, 'enrichment/seo');

        $phase_data = array(
            'content_strategy' => $this->first_non_empty(
                isset($idea_data['market_validation_summary']) ? $idea_data['market_validation_summary'] : '',
                isset($analysis['solution']) ? $analysis['solution'] : ''
            ),
            'platform_strategy' => $this->format_string_list(isset($branding['target_audiences']) ? $branding['target_audiences'] : array()),
            'content_themes' => $this->format_string_list(isset($seo['seo_keywords']) ? $seo['seo_keywords'] : array()),
            'post_ideas' => $this->format_string_list(isset($analysis['recommendations']) ? $analysis['recommendations'] : array()),
            'engagement_strategy' => $this->first_non_empty(
                isset($idea_data['validation_opportunities']) ? $idea_data['validation_opportunities'] : '',
                isset($analysis['risk_mitigation']) ? $analysis['risk_mitigation'] : ''
            ),
            'hashtag_strategy' => $this->format_hashtags(isset($seo['seo_keywords']) ? $seo['seo_keywords'] : array()),
            'content_calendar' => $this->format_string_list(isset($idea_data['action_plan']) ? $idea_data['action_plan'] : array()),
        );

        $wpdb->replace($table, array(
            'project_id' => $project_id,
            'phase_number' => 6,
            'phase_name' => 'Organic Posts',
            'phase_data' => wp_json_encode($phase_data),
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));
    }

    /**
     * Phase 7: Facebook Ads.
     */
    private function populate_phase_7($project_id, $idea_data, $external_id) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
        global $wpdb;
        $table = $wpdb->prefix . 'pl_phase_data';

        $analysis = isset($idea_data['analysis']) && is_array($idea_data['analysis']) ? $idea_data['analysis'] : array();
        $branding = $this->get_section_data($external_id, 'enrichment/branding');

        $phase_data = array(
            'campaign_strategy' => $this->first_non_empty(
                isset($idea_data['ai_assessment_summary']) ? $idea_data['ai_assessment_summary'] : '',
                isset($idea_data['market_validation_summary']) ? $idea_data['market_validation_summary'] : ''
            ),
            'audience_targeting' => $this->first_non_empty(
                isset($idea_data['target_audience']) ? $idea_data['target_audience'] : '',
                isset($branding['target_audiences']) ? $branding['target_audiences'] : ''
            ),
            'ad_creative' => $this->format_string_list(isset($analysis['key_features']) ? $analysis['key_features'] : array()),
            'ad_copy' => $this->first_non_empty(
                isset($idea_data['value_proposition']) ? $idea_data['value_proposition'] : '',
                $this->format_string_list(isset($analysis['recommendations']) ? $analysis['recommendations'] : array())
            ),
            'budget_bidding' => $this->first_non_empty(
                isset($idea_data['monetization_strategy']) ? $idea_data['monetization_strategy'] : '',
                isset($idea_data['validation_opportunities']) ? $idea_data['validation_opportunities'] : ''
            ),
            'campaign_structure' => $this->format_string_list(isset($idea_data['action_plan']) ? $idea_data['action_plan'] : array()),
            'tracking_optimization' => $this->first_non_empty(
                isset($idea_data['ai_assessment_summary']) ? $idea_data['ai_assessment_summary'] : '',
                isset($analysis['risk_mitigation']) ? $analysis['risk_mitigation'] : ''
            ),
        );

        $wpdb->replace($table, array(
            'project_id' => $project_id,
            'phase_number' => 7,
            'phase_name' => 'Facebook Ads',
            'phase_data' => wp_json_encode($phase_data),
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));
    }

    /**
     * Phase 8: Launch.
     */
    private function populate_phase_8($project_id, $idea_data, $external_id) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
        global $wpdb;
        $table = $wpdb->prefix . 'pl_phase_data';

        $analysis = isset($idea_data['analysis']) && is_array($idea_data['analysis']) ? $idea_data['analysis'] : array();
        $reddit = $this->get_section_data($external_id, 'platforms/reddit');
        $hackernews = $this->get_section_data($external_id, 'platforms/hackernews');
        $producthunt = $this->get_section_data($external_id, 'platforms/producthunt');

        $reddit_text = $this->format_string_list($reddit);
        $hackernews_text = $this->format_string_list($hackernews);
        $producthunt_text = $this->format_string_list($producthunt);

        $phase_data = array(
            'launch_strategy' => $this->first_non_empty(
                isset($idea_data['ai_assessment_summary']) ? $idea_data['ai_assessment_summary'] : '',
                isset($idea_data['market_validation_summary']) ? $idea_data['market_validation_summary'] : ''
            ),
            'launch_timeline' => $this->format_string_list(isset($idea_data['action_plan']) ? $idea_data['action_plan'] : array()),
            'pre_launch_checklist' => $this->first_non_empty(
                isset($idea_data['validation_opportunities']) ? $idea_data['validation_opportunities'] : '',
                $this->format_string_list(isset($analysis['recommendations']) ? $analysis['recommendations'] : array())
            ),
            'launch_day_plan' => $this->first_non_empty(
                $producthunt_text,
                $this->format_string_list(isset($analysis['key_features']) ? $analysis['key_features'] : array())
            ),
            'communication_plan' => $this->first_non_empty(
                $reddit_text,
                $hackernews_text,
                isset($idea_data['value_proposition']) ? $idea_data['value_proposition'] : ''
            ),
            'contingency_plan' => $this->first_non_empty(
                isset($analysis['risk_mitigation']) ? $analysis['risk_mitigation'] : '',
                isset($analysis['challenges']) ? $analysis['challenges'] : '',
                isset($idea_data['ai_assessment_summary']) ? $idea_data['ai_assessment_summary'] : ''
            ),
            'post_launch_analysis' => $this->first_non_empty(
                isset($idea_data['expansion_ideas']) ? $idea_data['expansion_ideas'] : '',
                isset($analysis['next_steps']) ? $analysis['next_steps'] : '',
                $producthunt_text
            ),
        );

        $wpdb->replace($table, array(
            'project_id' => $project_id,
            'phase_number' => 8,
            'phase_name' => 'Launch',
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
     * Retrieve cached enrichment data with API fallback.
     *
     * @param string $external_id External validation ID.
     * @param string $section     Section path.
     * @return array
     */
    private function get_section_data($external_id, $section) {
        $data = $this->api->get_cached_section($external_id, $section);

        if (!$data) {
            $data = $this->api->get_validation($external_id, $section);
        }

        return is_array($data) ? $data : array();
    }

    /**
     * Convert mixed data structures into newline separated text.
     *
     * @param mixed $items Items to format.
     * @return string
     */
    private function format_string_list($items) {
        if (empty($items)) {
            return '';
        }

        if (is_string($items)) {
            return $items;
        }

        if (is_scalar($items)) {
            return (string) $items;
        }

        if (!is_array($items)) {
            return '';
        }

        $parts = array();

        if ($this->is_assoc_array($items)) {
            foreach ($items as $value) {
                $text = $this->format_string_list($value);

                if ('' !== $text) {
                    $parts[] = $text;
                }
            }

            $parts = array_unique(array_map('trim', array_filter($parts)));

            return implode(' — ', $parts);
        }

        foreach ($items as $item) {
            $text = $this->format_string_list($item);

            if ('' !== $text) {
                $parts[] = $text;
            }
        }

        $parts = array_unique(array_map('trim', array_filter($parts)));

        return implode("\n", $parts);
    }

    /**
     * Determine if an array is associative.
     *
     * @param array $array Array to inspect.
     * @return bool
     */
    private function is_assoc_array($array) {
        if (!is_array($array)) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Get the first non-empty value from a list of possibilities.
     *
     * @param mixed ...$values Values to evaluate.
     * @return string
     */
    private function first_non_empty(...$values) {
        foreach ($values as $value) {
            if (is_array($value)) {
                $text = $this->format_string_list($value);

                if ('' !== trim($text)) {
                    return $text;
                }
            } elseif (is_string($value) && '' !== trim($value)) {
                return $value;
            } elseif (!empty($value) && !is_array($value)) {
                return (string) $value;
            }
        }

        return '';
    }

    /**
     * Generate space separated hashtags from keyword list.
     *
     * @param mixed $keywords Keywords source.
     * @return string
     */
    private function format_hashtags($keywords) {
        if (empty($keywords)) {
            return '';
        }

        if (!is_array($keywords)) {
            $keywords = explode(',', (string) $keywords);
        }

        $hashtags = array();

        foreach ($keywords as $keyword) {
            if (is_array($keyword)) {
                $keyword = $this->format_string_list($keyword);
            }

            $keyword = is_string($keyword) ? trim($keyword) : '';

            if ('' === $keyword) {
                continue;
            }

            $normalized = preg_replace('/[^a-z0-9]+/i', '', strtolower($keyword));

            if ('' === $normalized) {
                continue;
            }

            $hashtags[] = '#' . $normalized;
        }

        $hashtags = array_unique($hashtags);

        return implode(' ', $hashtags);
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

        if (function_exists('pl_clear_library_cache')) {
            pl_clear_library_cache();
        }

        if ($ideas) {
            set_transient('pl_cached_top_ideas', $ideas, DAY_IN_SECONDS);
        }
    }
}

new PL_Ideas_Library();
