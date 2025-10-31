<?php
/**
 * Validation API handler (local multi-signal engine)
 *
 * The previous implementation proxied requests to the ExplodingStartup API and
 * provided simulated responses. Version 3 introduces an in-house multi-signal
 * validation engine that aggregates market, competition, monetisation, and
 * social proof data while leveraging AI analysis. This class is now
 * responsible for orchestrating requests to {@see PL_Validation_Engine_V3},
 * persisting reports to the database, and exposing helper methods used across
 * the plugin.
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once PL_PLUGIN_DIR . 'includes/idea-validation/class-pl-validation-engine-v3.php';

class PL_Validation_API {
    /** @var PL_Validation_Engine_V3 */
    private $engine_v3;

    public function __construct() {
        $this->engine_v3 = new PL_Validation_Engine_V3();
    }

    public function submit_idea($business_idea, $user_id, $site_id) {
        $business_idea = trim((string) $business_idea);

        if ('' === $business_idea) {
            return new WP_Error('pl_validation_missing_idea', __('Please enter a business idea before validating.', 'product-launch'));
        }

        if (!is_user_logged_in()) {
            return new WP_Error('pl_validation_auth_required', __('You must be logged in to validate ideas.', 'product-launch'));
        }

        // Use V3 engine for validation
        $validation_result = $this->engine_v3->validate_idea($business_idea, [
            'user_id' => $user_id,
            'site_id' => $site_id,
        ]);

        if (!$validation_result) {
            return new WP_Error(
                'pl_validation_failed',
                __('Validation engine encountered an error. Please try again.', 'product-launch')
            );
        }

        // Save to database with enhanced schema
        $local_id = $this->save_validation_v3($user_id, $site_id, $validation_result);

        if (is_wp_error($local_id)) {
            return $local_id;
        }

        return [
            'local_id' => $local_id,
            'validation_id' => $validation_result['id'],
            'data' => $validation_result,
        ];
    }

    private function save_validation_v3($user_id, $site_id, $validation_data) {
        $this->maybe_create_validation_tables();

        global $wpdb;
        $table = pl_get_validation_table_name(is_multisite() ? 'network' : 'site');

        $inserted = $wpdb->insert($table, [
            'validation_id' => $validation_data['id'],
            'user_id' => $user_id,
            'site_id' => $site_id,
            'business_idea' => $validation_data['business_idea'],
            'validation_score' => $validation_data['validation_score'],
            'confidence_level' => $validation_data['confidence_level'],
            'confidence_score' => $validation_data['score_breakdown']['confidence_score'] ?? 0,
            'market_demand_score' => $validation_data['score_breakdown']['market_demand'] ?? 0,
            'competition_score' => $validation_data['score_breakdown']['competition'] ?? 0,
            'monetization_score' => $validation_data['score_breakdown']['monetization'] ?? 0,
            'feasibility_score' => $validation_data['score_breakdown']['feasibility'] ?? 0,
            'ai_analysis_score' => $validation_data['score_breakdown']['ai_analysis'] ?? 0,
            'social_proof_score' => $validation_data['score_breakdown']['social_proof'] ?? 0,
            'validation_status' => 'completed',
            'signals_data' => wp_json_encode($validation_data['signals']),
            'recommendations_data' => wp_json_encode($validation_data['recommendations']),
            'phase_prefill_data' => wp_json_encode($validation_data['phase_prefill']),
            'expires_at' => $validation_data['expires_at'],
            'created_at' => current_time('mysql'),
        ]);

        if (false === $inserted) {
            error_log('PL V3 Validation Save Failed: ' . $wpdb->last_error);
            return new WP_Error('save_failed', __('Failed to save validation.', 'product-launch'));
        }

        if (!empty($validation_data['signals'])) {
            $this->cache_signals($validation_data['id'], $validation_data['signals']);
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Search previously validated ideas stored locally.
     */
    public function search_prevalidated_ideas($query, $args = []) {
        $query = trim((string) $query);

        if ('' === $query) {
            return new WP_Error('pl_validation_missing_query', __('Please provide a search term before running discovery.', 'product-launch'));
        }

        $args = wp_parse_args($args, [
            'limit' => 10,
            'min_score' => null,
            'enriched_only' => false,
        ]);

        $args['limit'] = max(1, min((int) $args['limit'], 25));

        global $wpdb;
        $table = pl_get_validation_table_name(is_multisite() ? 'network' : 'site');

        $where = $wpdb->prepare('WHERE library_published = 1 AND business_idea LIKE %s', '%' . $wpdb->esc_like($query) . '%');

        if (!empty($args['min_score'])) {
            $where .= $wpdb->prepare(' AND validation_score >= %d', (int) $args['min_score']);
        }

        if (!empty($args['enriched_only'])) {
            $where .= $wpdb->prepare(" AND confidence_level IN (%s,%s)", 'High', 'Medium');
        }

        $sql = $wpdb->prepare(
            "SELECT * FROM $table $where ORDER BY validation_score DESC LIMIT %d",
            $args['limit']
        );

        $records = $wpdb->get_results($sql, ARRAY_A);

        $ideas = [];
        foreach ($records as $record) {
            $ideas[] = $this->normalize_search_result($record);
        }

        return [
            'ideas' => $ideas,
            'meta' => [
                'count' => count($ideas),
                'query' => $query,
                'limit' => $args['limit'],
                'is_sample' => false,
            ],
        ];
    }

    /**
     * Create a validation record from an idea payload.
     */
    public function create_validation_from_idea($idea_data, $user_id, $site_id, $publish_to_library = true) {
        $user_id = $user_id ? (int) $user_id : get_current_user_id();
        $site_id = $site_id ? (int) $site_id : get_current_blog_id();

        if (!is_array($idea_data)) {
            return new WP_Error('pl_validation_invalid_payload', __('Invalid idea payload supplied.', 'product-launch'));
        }

        $normalized = $this->normalize_search_result($idea_data);

        if (empty($normalized['business_idea'])) {
            return new WP_Error('pl_validation_missing_business_idea', __('Unable to determine the business idea from the supplied data.', 'product-launch'));
        }

        $existing_id = 0;
        if (!empty($normalized['external_api_id'])) {
            $existing_id = $this->get_validation_id_by_external($normalized['external_api_id']);
        }

        if ($existing_id) {
            return $this->update_validation_from_array($existing_id, $normalized, $publish_to_library);
        }

        $result = $this->save_validation($user_id, $site_id, $normalized['business_idea'], $normalized, $publish_to_library);

        if (is_wp_error($result)) {
            return $result;
        }

        return (int) $result;
    }

    /**
     * Retrieve a validation record by validation ID or database ID.
     */
    public function get_validation($validation_identifier, $section = 'core') {
        global $wpdb;

        $tables = [pl_get_validation_table_name('site')];
        if (is_multisite()) {
            $tables[] = pl_get_validation_table_name('network');
            $tables = array_unique($tables);
        }

        foreach ($tables as $table) {
            $record = null;

            if (is_numeric($validation_identifier)) {
                $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $validation_identifier), ARRAY_A);
            } else {
                $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE validation_id = %s OR external_api_id = %s", $validation_identifier, $validation_identifier), ARRAY_A);
            }

            if ($record) {
                if ('signals' === $section) {
                    return json_decode($record['signals_data'] ?? '[]', true);
                }
                if ('recommendations' === $section) {
                    return json_decode($record['recommendations_data'] ?? '[]', true);
                }
                if ('phase' === $section) {
                    return json_decode($record['phase_prefill_data'] ?? '[]', true);
                }
                return $record;
            }
        }

        return false;
    }

    /**
     * Persist validation data to the database.
     */
    private function save_validation($user_id, $site_id, $business_idea, $report, $publish_to_library = false) {
        global $wpdb;

        $this->maybe_create_validation_tables();

        $table = pl_get_validation_table_name(is_multisite() && $publish_to_library ? 'network' : 'site');

        $scores = $report['score_breakdown'] ?? [];

        $data = [
            'validation_id' => $report['id'] ?? $this->generate_local_validation_id(),
            'user_id' => $user_id,
            'site_id' => $site_id,
            'business_idea' => $business_idea,
            'external_api_id' => $report['id'] ?? null,
            'validation_score' => (int) ($report['validation_score'] ?? 0),
            'confidence_level' => $report['confidence_level'] ?? '',
            'confidence_score' => isset($report['confidence_score']) ? (float) $report['confidence_score'] : 0,
            'market_demand_score' => $scores['market_demand'] ?? 0,
            'competition_score' => $scores['competition'] ?? 0,
            'monetization_score' => $scores['monetization'] ?? 0,
            'feasibility_score' => $scores['feasibility'] ?? 0,
            'ai_analysis_score' => $scores['ai_analysis'] ?? 0,
            'social_proof_score' => $scores['social_proof'] ?? 0,
            'validation_status' => 'completed',
            'enrichment_status' => 'completed',
            'library_published' => $publish_to_library ? 1 : 0,
            'published_at' => $publish_to_library ? current_time('mysql') : null,
            'core_data' => wp_json_encode($report),
            'signals_data' => wp_json_encode($report['signals'] ?? []),
            'recommendations_data' => wp_json_encode($report['recommendations'] ?? []),
            'phase_prefill_data' => wp_json_encode($report['phase_prefill'] ?? []),
            'expires_at' => $report['expires_at'] ?? null,
        ];

        $formats = [
            '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%f', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s'
        ];

        $inserted = $wpdb->insert($table, $data, $formats);

        if (false === $inserted) {
            error_log('PL Validation DB Insert Failed: ' . $wpdb->last_error);
            return new WP_Error('pl_validation_save_failed', __('We were unable to store the validation results locally. Please try again.', 'product-launch'));
        }

        $this->cache_signals($data['validation_id'], $report['signals'] ?? []);

        return (int) $wpdb->insert_id;
    }

    /**
     * Update an existing validation record with new data.
     */
    private function update_validation_from_array($validation_id, $report, $publish_to_library = false) {
        global $wpdb;

        $table = pl_get_validation_table_name(is_multisite() && $publish_to_library ? 'network' : 'site');

        $scores = $report['score_breakdown'] ?? [];

        $data = [
            'business_idea' => $report['business_idea'] ?? '',
            'validation_score' => $report['validation_score'] ?? ($report['adjusted_score'] ?? 0),
            'confidence_level' => $report['confidence_level'] ?? '',
            'confidence_score' => isset($report['confidence_score']) ? (float) $report['confidence_score'] : 0,
            'market_demand_score' => $scores['market_demand'] ?? ($report['market_demand_score'] ?? 0),
            'competition_score' => $scores['competition'] ?? ($report['competition_score'] ?? 0),
            'monetization_score' => $scores['monetization'] ?? ($report['monetization_score'] ?? 0),
            'feasibility_score' => $scores['feasibility'] ?? ($report['feasibility_score'] ?? 0),
            'ai_analysis_score' => $scores['ai_analysis'] ?? ($report['ai_analysis_score'] ?? 0),
            'social_proof_score' => $scores['social_proof'] ?? ($report['social_proof_score'] ?? 0),
            'core_data' => wp_json_encode($report),
            'signals_data' => wp_json_encode($report['signals'] ?? []),
            'recommendations_data' => wp_json_encode($report['recommendations'] ?? []),
            'phase_prefill_data' => wp_json_encode($report['phase_prefill'] ?? []),
            'expires_at' => $report['expires_at'] ?? null,
        ];

        if ($publish_to_library) {
            $data['library_published'] = 1;
            $data['published_at'] = current_time('mysql');
        }

        $updated = $wpdb->update($table, $data, ['id' => (int) $validation_id]);

        if (false === $updated) {
            return new WP_Error('pl_validation_update_failed', __('Unable to update the validation record with the new idea data.', 'product-launch'));
        }

        if (!empty($report['signals'])) {
            $validation_key = $report['validation_id'] ?? $report['external_api_id'] ?? '';
            if ($validation_key) {
                $this->cache_signals($validation_key, $report['signals']);
            }
        }

        return (int) $validation_id;
    }

    /**
     * Cache signal data for quick retrieval.
     */
    private function cache_signals($validation_id, $signals) {
        if (empty($validation_id) || empty($signals)) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'pl_validation_signals';

        foreach ($signals as $type => $payload) {
            $signal_key = sanitize_key($type);

            $wpdb->delete($table, [
                'validation_id' => $validation_id,
                'signal_type' => $signal_key,
            ]);

            $wpdb->insert(
                $table,
                [
                    'validation_id' => $validation_id,
                    'signal_type' => $signal_key,
                    'signal_data' => wp_json_encode($payload),
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                ],
                ['%s', '%s', '%s', '%s']
            );
        }
    }

    /**
     * Look up an existing validation by external identifier.
     */
    private function get_validation_id_by_external($external_id) {
        if ('' === trim((string) $external_id)) {
            return 0;
        }

        global $wpdb;
        $table = pl_get_validation_table_name(is_multisite() ? 'network' : 'site');

        $found = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE validation_id = %s OR external_api_id = %s LIMIT 1", $external_id, $external_id));

        return $found ? (int) $found : 0;
    }

    /**
     * Normalise idea payloads to the internal representation.
     */
    private function normalize_search_result($idea) {
        if ($idea instanceof WP_Post) {
            $idea = (array) $idea;
        }

        if (!is_array($idea)) {
            return [];
        }

        $idea = $this->deep_sanitize_value($idea);

        $business_idea = '';
        foreach (['business_idea', 'idea', 'summary', 'title'] as $key) {
            if (!empty($idea[$key])) {
                $business_idea = (string) $idea[$key];
                break;
            }
        }

        $external_id = '';
        foreach (['validation_id', 'external_api_id', 'externalId', 'id'] as $key) {
            if (!empty($idea[$key])) {
                $external_id = (string) $idea[$key];
                break;
            }
        }

        if ('' === $external_id) {
            $external_id = $this->generate_local_validation_id();
        }

        $score = 0;
        foreach (['validation_score', 'adjusted_score', 'score'] as $key) {
            if (isset($idea[$key]) && is_numeric($idea[$key])) {
                $score = (int) round($idea[$key]);
                break;
            }
        }

        $confidence = '';
        foreach (['confidence_level', 'confidence', 'confidenceScore'] as $key) {
            if (!empty($idea[$key]) && is_string($idea[$key])) {
                $confidence = $idea[$key];
                break;
            }
        }

        $normalized = $idea;
        $normalized['business_idea'] = $business_idea;
        $normalized['external_api_id'] = $external_id;
        $normalized['validation_id'] = $external_id;
        $normalized['validation_score'] = $score;
        $normalized['confidence_level'] = $confidence;

        if (isset($normalized['signals_data'])) {
            $decoded_signals = json_decode($normalized['signals_data'], true);
            if (is_array($decoded_signals)) {
                $normalized['signals'] = $decoded_signals;
            }
        }

        return $normalized;
    }

    /**
     * Recursively sanitise an incoming payload.
     */
    private function deep_sanitize_value($value) {
        if (is_array($value)) {
            $clean = [];
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

    private function maybe_create_validation_tables() {
        if (!function_exists('pl_create_validation_tables')) {
            return;
        }

        pl_create_validation_tables();
    }

    private function generate_local_validation_id() {
        return 'val_' . wp_generate_password(12, false);
    }
}
