<?php
/**
 * Validation API Handler
 * Manages communication with external validation API
 */

if (!defined('ABSPATH')) exit;

class PL_Validation_API {
    
    private $api_base = 'https://api.explodingstartup.com/api/ideas';
    private $api_key = 'exp_live_05663cf87e3b406780a939cf079e59f3';

    /**
     * Retrieve the API base endpoint considering network/site overrides.
     */
    private function get_api_base() {
        $base = pl_get_validation_option('pl_validation_api_endpoint', $this->api_base);

        if (empty($base)) {
            $base = $this->api_base;
        }

        return untrailingslashit($base);
    }

    /**
     * Retrieve the API key considering network/site overrides.
     */
    private function get_api_key() {
        $key = pl_get_validation_option('pl_validation_api_key', $this->api_key);

        if (empty($key)) {
            $key = $this->api_key;
        }

        return $key;
    }
    
    /**
     * Submit new idea for validation
     */
    public function submit_idea($business_idea, $user_id, $site_id) {
        $force_demo_mode = apply_filters('pl_validation_force_demo_mode', false, $business_idea, $user_id, $site_id);

        if ($force_demo_mode) {
            return $this->handle_demo_submission($business_idea, $user_id, $site_id, 'forced');
        }

        $api_base = $this->get_api_base();
        $api_key = $this->get_api_key();

        $body = array(
            'business_idea' => $business_idea,
            'enriched' => true,
            'user_metadata' => array(
                'user_id' => $user_id,
                'site_id' => $site_id,
                'source' => 'product_launch_plugin'
            )
        );

        $response = wp_remote_post($api_base, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-API-Key' => $api_key
            ),
            'body' => wp_json_encode($body),
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            error_log('PL Validation API Error: ' . $response->get_error_message());
            return $this->handle_demo_submission($business_idea, $user_id, $site_id, 'request_error');
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200 && $code !== 201) {
            error_log('PL Validation API HTTP Error: ' . $code);
            return $this->handle_demo_submission($business_idea, $user_id, $site_id, 'http_error');
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!$data || !isset($data['id'])) {
            error_log('PL Validation API: Invalid response data');
            return $this->handle_demo_submission($business_idea, $user_id, $site_id, 'invalid_response');
        }

        $local_id = $this->save_validation($user_id, $site_id, $business_idea, $data);

        if (is_wp_error($local_id)) {
            return $local_id;
        }

        if (!$local_id) {
            return new WP_Error('pl_validation_save_failed', __('Unable to store the validation results locally. Please try again.', 'product-launch'));
        }

        return array(
            'local_id' => $local_id,
            'external_id' => $data['id'],
            'data' => $data
        );
    }

    /**
     * Handle demo submission fallback when API is unavailable.
     */
    private function handle_demo_submission($business_idea, $user_id, $site_id, $reason = 'fallback') {
        $allow_demo = apply_filters('pl_validation_allow_demo_fallback', true, $reason, $business_idea, $user_id, $site_id);

        if (!$allow_demo) {
            return false;
        }

        $demo_data = $this->generate_demo_validation($business_idea);
        $local_id = $this->save_validation($user_id, $site_id, $business_idea, $demo_data);

        if (is_wp_error($local_id)) {
            return $local_id;
        }

        if (!$local_id) {
            return new WP_Error('pl_validation_save_failed', __('Unable to store the demo validation results locally. Please try again.', 'product-launch'));
        }

        return array(
            'local_id' => $local_id,
            'external_id' => $demo_data['id'],
            'data' => $demo_data,
            'is_demo' => true
        );
    }

    /**
     * Generate demo validation data to display when the API is unavailable.
     */
    private function generate_demo_validation($business_idea) {
        $score = wp_rand(72, 90);

        $breakdown = array(
            'market_demand_score' => wp_rand(70, 92),
            'competition_score' => wp_rand(55, 85),
            'monetization_score' => wp_rand(68, 90),
            'execution_score' => wp_rand(60, 88),
            'audience_fit_score' => wp_rand(70, 95)
        );

        $confidence = 'medium';
        if ($score >= 85) {
            $confidence = 'high';
        } elseif ($score < 75) {
            $confidence = 'moderate';
        }

        $idea_summary = wp_trim_words($business_idea, 12, '…');
        $demo_id = 'demo_' . $this->generate_demo_id();

        $demo_data = array(
            'id' => $demo_id,
            'business_idea' => $business_idea,
            'summary' => sprintf(__('Demo snapshot for: %s', 'product-launch'), $idea_summary),
            'adjusted_score' => $score,
            'confidence_level' => $confidence,
            'enriched' => true,
            'is_demo' => true,
            'scoring_breakdown' => $breakdown,
            'ai_assessment' => sprintf(
                __('This demo assessment outlines how "%s" could perform with a focused launch plan.', 'product-launch'),
                $idea_summary
            ),
            'market_validation' => __('Early market research indicates consistent interest from problem-aware buyers seeking rapid solutions.', 'product-launch'),
            'target_audience' => __('Busy professionals, niche community builders, and early adopters who value streamlined execution.', 'product-launch'),
            'customer_pain_points' => __('Customers struggle with disjointed tools, lack of accountability, and slow feedback loops when testing new ideas.', 'product-launch'),
            'validation_opportunities' => __('Leverage a lightweight pilot, collect qualitative feedback within 30 days, and use testimonials to build authority.', 'product-launch'),
            'action_plan' => array(
                __('Launch a pre-registration page to capture interested leads and test positioning.', 'product-launch'),
                __('Offer a limited beta cohort with clear onboarding milestones and success metrics.', 'product-launch'),
                __('Compile audience insights into a go-to-market brief for the first 90 days.', 'product-launch')
            ),
            'expansion_ideas' => __('Expand into a full playbook, community-driven accountability pods, and tiered service offerings after initial traction.', 'product-launch')
        );

        return apply_filters('pl_validation_demo_data', $demo_data, $business_idea);
    }

    /**
     * Create a unique identifier for demo records.
     */
    private function generate_demo_id() {
        if (function_exists('wp_generate_uuid4')) {
            return wp_generate_uuid4();
        }

        return uniqid('pl_demo_', true);
    }

    /**
     * Search for pre-validated ideas using the remote API.
     *
     * @param string $query Search query or topic.
     * @param array  $args  Additional search parameters.
     *
     * @return array|WP_Error
     */
    public function search_prevalidated_ideas($query, $args = array()) {
        $query = trim((string) $query);

        if ('' === $query) {
            return new WP_Error('pl_validation_missing_query', __('Please provide a search term before running discovery.', 'product-launch'));
        }

        $args = wp_parse_args($args, array(
            'limit' => 10,
            'min_score' => null,
            'enriched_only' => false,
        ));

        $args['limit'] = max(1, min((int) $args['limit'], 25));

        $params = array(
            'query' => $query,
            'limit' => $args['limit'],
        );

        if (!empty($args['min_score'])) {
            $params['min_score'] = (int) $args['min_score'];
        }

        if (!empty($args['enriched_only'])) {
            $params['enriched_only'] = $args['enriched_only'] ? 'true' : 'false';
        }

        $endpoint = trailingslashit($this->get_api_base()) . 'search';
        $url = $endpoint . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $response = wp_remote_get($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->get_api_key(),
            ),
            'timeout' => 60,
        ));

        if (is_wp_error($response)) {
            $message = sprintf(
                /* translators: %s: error message */
                __('Unable to contact the validation API: %s', 'product-launch'),
                $response->get_error_message()
            );

            return $this->handle_demo_search_fallback('request_error', $query, $args, $message);
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if (200 !== $code) {
            $message = sprintf(
                /* translators: %d: HTTP status code */
                __('The validation API returned an unexpected status code: %d', 'product-launch'),
                $code
            );

            return $this->handle_demo_search_fallback('http_error', $query, $args, $message);
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($data)) {
            return $this->handle_demo_search_fallback('invalid_response', $query, $args, __('The validation API returned an invalid response.', 'product-launch'));
        }

        if (isset($data['ideas']) && is_array($data['ideas'])) {
            $ideas = $data['ideas'];
        } elseif (isset($data['data']) && is_array($data['data'])) {
            $ideas = $data['data'];
        } else {
            $ideas = is_array($data) ? $data : array();
        }

        $normalized = array();
        foreach ($ideas as $idea) {
            $normalized[] = $this->normalize_search_result($idea);
        }

        if (!empty($args['min_score'])) {
            $min_score = (int) $args['min_score'];
            $normalized = array_values(array_filter($normalized, function ($idea) use ($min_score) {
                return isset($idea['adjusted_score']) && (int) $idea['adjusted_score'] >= $min_score;
            }));
        }

        return array(
            'ideas' => $normalized,
            'meta' => array(
                'count' => count($normalized),
                'query' => $query,
                'limit' => $args['limit'],
                'is_demo' => false,
            ),
        );
    }

    /**
     * Store a discovered idea locally and optionally publish it to the library.
     *
     * @param array $idea_data          Sanitized idea payload.
     * @param int   $user_id            User responsible for publishing.
     * @param int   $site_id            Site identifier.
     * @param bool  $publish_to_library Whether the idea should be immediately available in the library.
     *
     * @return int|WP_Error Validation ID on success.
     */
    public function create_validation_from_idea($idea_data, $user_id, $site_id, $publish_to_library = true) {
        $user_id = $user_id ? (int) $user_id : get_current_user_id();
        $site_id = $site_id ? (int) $site_id : get_current_blog_id();

        $normalized = $this->normalize_search_result($idea_data);

        $table_context = 'site';

        if (is_multisite() && $publish_to_library) {
            $table_context = 'network';
        }

        if (empty($normalized['business_idea'])) {
            return new WP_Error('pl_validation_missing_business_idea', __('Unable to determine the business idea from the supplied data.', 'product-launch'));
        }

        $external_id = isset($normalized['id']) ? $normalized['id'] : '';

        if (!empty($external_id)) {
            $existing_id = $this->get_validation_id_by_external($external_id, $table_context);

            if ($existing_id) {
                $updated = $this->update_validation_from_array($existing_id, $normalized, $publish_to_library, $table_context);

                if (is_wp_error($updated)) {
                    return $updated;
                }

                return (int) $existing_id;
            }
        }

        $result = $this->save_validation($user_id, $site_id, $normalized['business_idea'], $normalized, $publish_to_library, $table_context);

        if (is_wp_error($result)) {
            return $result;
        }

        return (int) $result;
    }

    /**
     * Get validation by external ID
     */
    public function get_validation($external_id, $section = 'core') {
        $endpoint = trailingslashit($this->get_api_base()) . $external_id;

        if ($section !== 'core') {
            $endpoint .= "/{$section}";
        }

        $response = wp_remote_get($endpoint, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->get_api_key()
            ),
            'timeout' => 45
        ));
        
        if (is_wp_error($response)) {
            error_log('PL Validation Get Error: ' . $response->get_error_message());
            return false;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            error_log('PL Validation Get HTTP Error: ' . $code);
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($data && $section !== 'core') {
            $this->cache_enrichment_section($external_id, $section, $data);
        }
        
        return $data;
    }
    
    /**
     * Save validation to local database
     */
    private function save_validation($user_id, $site_id, $business_idea, $api_data, $publish_to_library = false, $context = 'site') {
        global $wpdb;
        $table_context = ('network' === $context) ? 'network' : (is_multisite() ? (int) $site_id : 'site');
        $table = pl_get_validation_table_name($table_context);

        if ('site' === $context) {
            $this->maybe_create_validation_tables();
        }

        $inserted = $wpdb->insert($table, array(
            'user_id' => $user_id,
            'site_id' => $site_id,
            'business_idea' => $business_idea,
            'external_api_id' => $api_data['id'] ?? null,
            'validation_score' => $api_data['adjusted_score'] ?? 0,
            'confidence_level' => $api_data['confidence_level'] ?? null,
            'validation_status' => 'completed',
            'enrichment_status' => isset($api_data['enriched']) && $api_data['enriched'] ? 'completed' : 'pending',
            'library_published' => $publish_to_library ? 1 : 0,
            'published_at' => $publish_to_library ? current_time('mysql') : null,
            'core_data' => wp_json_encode($api_data)
        ));

        if (false === $inserted || empty($wpdb->insert_id)) {
            error_log('PL Validation DB Insert Failed: ' . $wpdb->last_error);

            return new WP_Error(
                'pl_validation_save_failed',
                __('We were unable to store the validation results locally. Please try again.', 'product-launch')
            );
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Update an existing validation record using normalized idea data.
     *
     * @param int   $validation_id      Validation identifier.
     * @param array $api_data           Normalized idea payload.
     * @param bool  $publish_to_library Whether to publish to the network library.
     *
     * @return int|WP_Error
     */
    private function update_validation_from_array($validation_id, $api_data, $publish_to_library = false, $context = 'site') {
        global $wpdb;
        $table_context = ('network' === $context) ? 'network' : (is_multisite() ? get_current_blog_id() : 'site');
        $table = pl_get_validation_table_name($table_context);

        $update = array(
            'business_idea' => $api_data['business_idea'] ?? '',
            'validation_score' => isset($api_data['adjusted_score']) ? (int) $api_data['adjusted_score'] : 0,
            'confidence_level' => $api_data['confidence_level'] ?? null,
            'validation_status' => 'completed',
            'enrichment_status' => !empty($api_data['enriched']) ? 'completed' : 'pending',
            'core_data' => wp_json_encode($api_data),
        );

        if ($publish_to_library) {
            $update['library_published'] = 1;
            $update['published_at'] = current_time('mysql');
        }

        $result = $wpdb->update($table, $update, array('id' => (int) $validation_id));

        if (false === $result) {
            return new WP_Error('pl_validation_update_failed', __('Unable to update the validation record with the new idea data.', 'product-launch'));
        }

        return (int) $validation_id;
    }

    /**
     * Retrieve a validation ID by its external identifier.
     *
     * @param string $external_id External API identifier.
     *
     * @return int Validation ID or 0 when not found.
     */
    private function get_validation_id_by_external($external_id, $context = 'site') {
        $external_id = trim((string) $external_id);

        if ('' === $external_id) {
            return 0;
        }

        global $wpdb;
        $table_context = ('network' === $context) ? 'network' : (is_multisite() ? get_current_blog_id() : 'site');
        $table = pl_get_validation_table_name($table_context);

        $found = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE external_api_id = %s LIMIT 1",
            $external_id
        ));

        return $found ? (int) $found : 0;
    }

    /**
     * Provide demo discovery results when the live API is not reachable.
     */
    private function handle_demo_search_fallback($reason, $query, $args, $error_message = '') {
        $allow_demo = apply_filters('pl_validation_allow_demo_search', true, $reason, $query, $args, $error_message);

        if (!$allow_demo) {
            return new WP_Error(
                'pl_validation_search_unavailable',
                $error_message ? $error_message : __('The validation discovery service is currently unavailable. Please try again in a moment.', 'product-launch')
            );
        }

        if (!empty($error_message)) {
            error_log('PL Validation search fallback (' . $reason . '): ' . $error_message);
        }

        $demo_results = $this->generate_demo_search_results($query, $args);

        if (empty($demo_results)) {
            return new WP_Error(
                'pl_validation_demo_unavailable',
                __('Unable to load demo discovery ideas at this time. Please try again later.', 'product-launch')
            );
        }

        return array(
            'ideas' => $demo_results,
            'meta' => array(
                'count' => count($demo_results),
                'query' => $query,
                'limit' => isset($args['limit']) ? (int) $args['limit'] : count($demo_results),
                'is_demo' => true,
                'demo_message' => __('Showing demo discovery results while the live validation API is unavailable.', 'product-launch'),
            ),
        );
    }

    /**
     * Build a set of demo discovery ideas that mirror the live API payload.
     */
    private function generate_demo_search_results($query, $args) {
        $limit = isset($args['limit']) ? max(1, min((int) $args['limit'], 12)) : 10;
        $requested_min_score = isset($args['min_score']) ? (int) $args['min_score'] : 0;

        $focus = $this->format_demo_focus($query);
        $focus_slug = sanitize_title($focus);

        $templates = array(
            array(
                'title' => __('AI Launch Blueprint for %s', 'product-launch'),
                'summary' => __('A concierge validation sprint combining market research, scoring, and go-to-market insight tailored for %s.', 'product-launch'),
                'score' => array(82, 94),
                'confidence' => 'high',
                'tags' => array('ai-launch', 'playbook', 'validation'),
            ),
            array(
                'title' => __('Community Accelerator for %s Founders', 'product-launch'),
                'summary' => __('Build a paid accelerator with interview scripts, audience outreach templates, and retention loops built for %s.', 'product-launch'),
                'score' => array(78, 90),
                'confidence' => 'medium',
                'tags' => array('community', 'launch', 'accelerator'),
            ),
            array(
                'title' => __('Validation Content Vault for %s Creators', 'product-launch'),
                'summary' => __('Package swipe files, nurture flows, and conversion scripts that help %s ship validated offers faster.', 'product-launch'),
                'score' => array(80, 92),
                'confidence' => 'high',
                'tags' => array('content', 'funnels', 'templates'),
            ),
            array(
                'title' => __('Done-for-you Funnel for %s', 'product-launch'),
                'summary' => __('Deliver a plug-and-play funnel with messaging angles, launch emails, and KPI dashboards aimed at %s.', 'product-launch'),
                'score' => array(76, 88),
                'confidence' => 'medium',
                'tags' => array('funnel', 'automation', 'launch-plan'),
            ),
            array(
                'title' => __('Workflow Automation Stack for %s Teams', 'product-launch'),
                'summary' => __('Bundle SOPs, automations, and AI prompts that remove busywork for %s execution teams.', 'product-launch'),
                'score' => array(84, 95),
                'confidence' => 'high',
                'tags' => array('automation', 'sops', 'ai-assist'),
            ),
        );

        $ideas = array();

        for ($i = 0; $i < $limit; $i++) {
            $template = $templates[$i % count($templates)];
            $title = sprintf($template['title'], $focus);
            $summary = sprintf($template['summary'], $focus);

            $demo = $this->generate_demo_validation($title);

            $min_range = isset($template['score'][0]) ? (int) $template['score'][0] : 70;
            $max_range = isset($template['score'][1]) ? (int) $template['score'][1] : 90;

            if ($requested_min_score > 0) {
                $min_range = max($min_range, $requested_min_score);
            }

            if ($max_range < $min_range) {
                $max_range = $min_range;
            }

            $score = wp_rand($min_range, $max_range);

            $demo['summary'] = $summary;
            $demo['adjusted_score'] = $score;
            $demo['validation_score'] = $score;
            $demo['confidence_level'] = $template['confidence'];
            $demo['origin'] = 'demo_network_search';
            $demo['source_type'] = 'demo';
            $demo['generated_at'] = current_time('mysql');
            $demo['enriched'] = true;

            $demo_tags = isset($demo['tags']) && is_array($demo['tags']) ? $demo['tags'] : array();
            $demo_tags = array_merge($demo_tags, $template['tags'], array($focus_slug));
            $demo['tags'] = array_values(array_unique($demo_tags));

            $ideas[] = $this->normalize_search_result($demo);
        }

        return $ideas;
    }

    /**
     * Format the search term into a human-readable focus string for demo ideas.
     */
    private function format_demo_focus($query) {
        $focus = trim(wp_strip_all_tags((string) $query));

        if ('' === $focus) {
            return __('Modern Founders', 'product-launch');
        }

        $focus = preg_replace('/\s+/', ' ', $focus);
        $focus = wp_trim_words($focus, 6, '');

        if (function_exists('mb_convert_case')) {
            $focus = mb_convert_case($focus, MB_CASE_TITLE, 'UTF-8');
        } else {
            $focus = ucwords(strtolower($focus));
        }

        return $focus;
    }

    /**
     * Normalize and sanitize a search result payload.
     *
     * @param mixed $idea Raw idea payload from API or UI.
     *
     * @return array Normalized idea data.
     */
    private function normalize_search_result($idea) {
        $idea = $this->deep_sanitize_value($idea);

        if (!is_array($idea)) {
            $idea = array();
        }

        $business_idea = '';
        foreach (array('business_idea', 'idea', 'title', 'name', 'headline') as $key) {
            if (!empty($idea[$key]) && is_string($idea[$key])) {
                $business_idea = $idea[$key];
                break;
            }
        }

        if ('' === $business_idea && !empty($idea['summary'])) {
            $business_idea = $idea['summary'];
        }

        if ('' === $business_idea && !empty($idea['description'])) {
            $business_idea = $idea['description'];
        }

        if ('' === $business_idea) {
            $business_idea = __('Untitled Idea', 'product-launch');
        }

        $external_id = '';
        foreach (array('id', 'external_api_id', 'externalId') as $key) {
            if (!empty($idea[$key])) {
                $external_id = (string) $idea[$key];
                break;
            }
        }

        if ('' === $external_id) {
            $external_id = 'network-' . md5($business_idea . wp_json_encode($idea));
        }

        $score = 0;
        foreach (array('adjusted_score', 'score', 'validation_score') as $key) {
            if (isset($idea[$key]) && is_numeric($idea[$key])) {
                $score = (int) round($idea[$key]);
                break;
            }
        }

        $confidence = '';
        foreach (array('confidence_level', 'confidence', 'confidenceScore') as $key) {
            if (!empty($idea[$key]) && is_string($idea[$key])) {
                $confidence = $idea[$key];
                break;
            }
        }

        $summary = '';
        foreach (array('summary', 'description', 'overview') as $key) {
            if (!empty($idea[$key]) && is_string($idea[$key])) {
                $summary = $idea[$key];
                break;
            }
        }

        $normalized = $idea;
        $normalized['id'] = $external_id;
        $normalized['external_api_id'] = $external_id;
        $normalized['business_idea'] = $business_idea;
        $normalized['summary'] = $summary;
        $normalized['adjusted_score'] = $score;
        $normalized['validation_score'] = $score;
        $normalized['confidence_level'] = $confidence;
        $normalized['enriched'] = isset($idea['enriched']) ? (bool) $idea['enriched'] : true;
        $normalized['origin'] = isset($idea['origin']) ? $idea['origin'] : 'network_search';
        $normalized['source_type'] = isset($idea['source_type']) ? $idea['source_type'] : 'network_search';
        $normalized['generated_at'] = isset($idea['generated_at']) ? $idea['generated_at'] : current_time('mysql');

        if (isset($normalized['tags']) && is_array($normalized['tags'])) {
            $normalized['tags'] = array_values(array_map('sanitize_title', $normalized['tags']));
        }

        return $normalized;
    }

    /**
     * Recursively sanitize incoming idea payloads.
     *
     * @param mixed $value Raw payload value.
     *
     * @return mixed Sanitized value.
     */
    private function deep_sanitize_value($value) {
        if (is_array($value)) {
            $clean = array();
            foreach ($value as $key => $sub_value) {
                $clean_key = is_string($key) ? sanitize_key($key) : $key;
                $clean[$clean_key] = $this->deep_sanitize_value($sub_value);
            }
            return $clean;
        }

        if (is_object($value)) {
            return $this->deep_sanitize_value((array) $value);
        }

        if (is_bool($value) || null === $value) {
            return $value;
        }

        if (is_numeric($value)) {
            return 0 + $value;
        }

        return sanitize_textarea_field((string) $value);
    }

    /**
     * Ensure the validation tables exist before attempting to save data.
     */
    private function maybe_create_validation_tables() {
        global $wpdb;

        $table = $wpdb->prefix . 'pl_validations';
        $table_exists = $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $wpdb->esc_like($table)
            )
        );

        if ($table_exists !== $table && function_exists('pl_create_validation_tables')) {
            pl_create_validation_tables();
        }
    }
    
    /**
     * Cache enrichment section data
     */
    private function cache_enrichment_section($external_id, $section, $data) {
        global $wpdb;

        $contexts = is_multisite() ? array('network', 'site') : array('site');

        foreach ($contexts as $context) {
            $table_validations = pl_get_validation_table_name($context);
            $validation_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_validations WHERE external_api_id = %s",
                $external_id
            ));

            if (!$validation_id) {
                continue;
            }

            $table_enrichment = pl_get_validation_enrichment_table_name($context);

            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_enrichment WHERE validation_id = %d AND section_type = %s",
                $validation_id,
                $section
            ));

            if ($exists) {
                $wpdb->update(
                    $table_enrichment,
                    array(
                        'section_data' => wp_json_encode($data),
                        'fetched_at' => current_time('mysql')
                    ),
                    array('id' => $exists)
                );
            } else {
                $wpdb->insert($table_enrichment, array(
                    'validation_id' => $validation_id,
                    'section_type' => $section,
                    'section_data' => wp_json_encode($data)
                ));
            }

            return true;
        }

        return false;
    }
    
    /**
     * Get cached enrichment section
     */
    public function get_cached_section($external_id, $section) {
        global $wpdb;

        $contexts = is_multisite() ? array('network', 'site') : array('site');

        foreach ($contexts as $context) {
            $table_validations = pl_get_validation_table_name($context);
            $table_enrichment = pl_get_validation_enrichment_table_name($context);

            $validation_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_validations WHERE external_api_id = %s",
                $external_id
            ));

            if (!$validation_id) {
                continue;
            }

            $cached = $wpdb->get_var($wpdb->prepare(
                "SELECT section_data FROM $table_enrichment WHERE validation_id = %d AND section_type = %s",
                $validation_id,
                $section
            ));

            if ($cached) {
                return json_decode($cached, true);
            }
        }

        return null;
    }
}
