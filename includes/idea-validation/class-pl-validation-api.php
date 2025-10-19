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
    private function save_validation($user_id, $site_id, $business_idea, $api_data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pl_validations';

        $this->maybe_create_validation_tables();

        $inserted = $wpdb->insert($table, array(
            'user_id' => $user_id,
            'site_id' => $site_id,
            'business_idea' => $business_idea,
            'external_api_id' => $api_data['id'] ?? null,
            'validation_score' => $api_data['adjusted_score'] ?? 0,
            'confidence_level' => $api_data['confidence_level'] ?? null,
            'validation_status' => 'completed',
            'enrichment_status' => isset($api_data['enriched']) && $api_data['enriched'] ? 'completed' : 'pending',
            'library_published' => 0,
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
        
        $table_validations = $wpdb->prefix . 'pl_validations';
        $validation_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_validations WHERE external_api_id = %s",
            $external_id
        ));
        
        if (!$validation_id) {
            return false;
        }
        
        $table_enrichment = $wpdb->prefix . 'pl_validation_enrichment';
        
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
    
    /**
     * Get cached enrichment section
     */
    public function get_cached_section($external_id, $section) {
        global $wpdb;
        
        $table_validations = $wpdb->prefix . 'pl_validations';
        $table_enrichment = $wpdb->prefix . 'pl_validation_enrichment';
        
        $validation_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_validations WHERE external_api_id = %s",
            $external_id
        ));
        
        if (!$validation_id) {
            return null;
        }
        
        $cached = $wpdb->get_var($wpdb->prepare(
            "SELECT section_data FROM $table_enrichment WHERE validation_id = %d AND section_type = %s",
            $validation_id,
            $section
        ));
        
        return $cached ? json_decode($cached, true) : null;
    }
}
