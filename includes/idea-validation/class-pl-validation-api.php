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
     * Submit new idea for validation
     */
    public function submit_idea($business_idea, $user_id, $site_id) {
        $body = array(
            'business_idea' => $business_idea,
            'enriched' => true,
            'user_metadata' => array(
                'user_id' => $user_id,
                'site_id' => $site_id,
                'source' => 'product_launch_plugin'
            )
        );
        
        $response = wp_remote_post($this->api_base, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->api_key
            ),
            'body' => wp_json_encode($body),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            error_log('PL Validation API Error: ' . $response->get_error_message());
            return false;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200 && $code !== 201) {
            error_log('PL Validation API HTTP Error: ' . $code);
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!$data || !isset($data['id'])) {
            error_log('PL Validation API: Invalid response data');
            return false;
        }
        
        $local_id = $this->save_validation($user_id, $site_id, $business_idea, $data);
        
        return array(
            'local_id' => $local_id,
            'external_id' => $data['id'],
            'data' => $data
        );
    }
    
    /**
     * Get validation by external ID
     */
    public function get_validation($external_id, $section = 'core') {
        $endpoint = "{$this->api_base}/{$external_id}";
        
        if ($section !== 'core') {
            $endpoint .= "/{$section}";
        }
        
        $response = wp_remote_get($endpoint, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->api_key
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
        
        $wpdb->insert($table, array(
            'user_id' => $user_id,
            'site_id' => $site_id,
            'business_idea' => $business_idea,
            'external_api_id' => $api_data['id'] ?? null,
            'validation_score' => $api_data['adjusted_score'] ?? 0,
            'confidence_level' => $api_data['confidence_level'] ?? null,
            'validation_status' => 'completed',
            'enrichment_status' => isset($api_data['enriched']) && $api_data['enriched'] ? 'completed' : 'pending',
            'core_data' => wp_json_encode($api_data)
        ));
        
        return $wpdb->insert_id;
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
