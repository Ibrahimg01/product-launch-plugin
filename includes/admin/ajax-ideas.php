<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once PL_PLUGIN_DIR . 'includes/phase-mapper.php';
require_once PL_PLUGIN_DIR . 'includes/admin/openai-getter.php';

/**
 * Normalize a mixed idea identifier into the numeric validation ID.
 *
 * @param string|int $idea_id Raw identifier from the request.
 * @return int
 */
function is_admin_normalize_validation_id($idea_id) {
    if (is_numeric($idea_id)) {
        return (int) $idea_id;
    }

    if (is_string($idea_id) && preg_match('/^local-(\d+)$/', $idea_id, $matches)) {
        return (int) $matches[1];
    }

    return 0;
}

/**
 * Retrieve a validation record and decoded report by ID.
 *
 * @param int $validation_id Validation identifier.
 * @return array{record:object,report:array}|WP_Error
 */
function is_admin_get_validation_report($validation_id) {
    if (!$validation_id) {
        return new WP_Error('invalid_id', __('Invalid idea ID.', 'product-launch'));
    }

    $access = new PL_Validation_Access();
    $record = $access->get_validation($validation_id, get_current_user_id());

    if (!$record || empty($record->core_data)) {
        return new WP_Error('missing_report', __('No validation report is available for this idea.', 'product-launch'));
    }

    $decoded = json_decode($record->core_data, true);

    if (!is_array($decoded)) {
        return new WP_Error('invalid_report', __('Stored report data is invalid.', 'product-launch'));
    }

    return array(
        'record' => $record,
        'report' => $decoded,
    );
}

add_action('wp_ajax_is_admin_get_report', function () {
    if (!current_user_can('edit_posts') && !current_user_can('manage_options') && !current_user_can('manage_network_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to view reports.', 'product-launch')), 403);
    }

    $idea_raw = isset($_GET['idea_id']) ? sanitize_text_field(wp_unslash($_GET['idea_id'])) : '';
    $validation_id = is_admin_normalize_validation_id($idea_raw);

    $result = is_admin_get_validation_report($validation_id);

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()), 404);
    }

    wp_send_json_success(array('report' => $result['report']));
});

add_action('wp_ajax_is_admin_push_to_phases', function () {
    check_ajax_referer('is_admin_push_nonce', 'nonce');

    if (!current_user_can('edit_posts') && !current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Insufficient permissions.', 'product-launch')], 403);
    }

    $idea_raw = isset($_POST['idea_id']) ? sanitize_text_field(wp_unslash($_POST['idea_id'])) : '';
    $validation_id = is_admin_normalize_validation_id($idea_raw);
    $overwrite = !empty($_POST['overwrite']);

    $user_id = get_current_user_id();
    $site_id = get_current_blog_id();

    // Use V3 phase data application
    $result = pl_apply_v3_phase_data($validation_id, $user_id, $site_id, $overwrite);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()], 400);
    }

    wp_send_json_success([
        'message' => __('Validation data applied to all 8 phases.', 'product-launch'),
        'redirect' => admin_url('admin.php?page=product-launch-market'),
    ]);
});

add_action('wp_ajax_is_admin_validate_idea', function () {
    check_ajax_referer('is_admin_push_nonce', 'nonce');

    if (!current_user_can('edit_posts') && !current_user_can('manage_options') && !current_user_can('manage_network_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to validate ideas.', 'product-launch')), 403);
    }

    $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
    $description = isset($_POST['description']) ? wp_kses_post(wp_unslash($_POST['description'])) : '';
    $audience = isset($_POST['audience']) ? sanitize_text_field(wp_unslash($_POST['audience'])) : '';
    $problem = isset($_POST['problem']) ? sanitize_text_field(wp_unslash($_POST['problem'])) : '';
    $outcome = isset($_POST['outcome']) ? sanitize_text_field(wp_unslash($_POST['outcome'])) : '';

    if ('' === $title) {
        wp_send_json_error(array('message' => __('Idea title is required.', 'product-launch')), 400);
    }

    $settings = is_get_openai_settings();
    $api_key = isset($settings['key']) ? trim((string) $settings['key']) : '';
    $model = isset($settings['model']) && '' !== $settings['model'] ? $settings['model'] : 'gpt-4o-mini';

    if ('' === $api_key) {
        wp_send_json_error(array('message' => __('OpenAI API key is missing from settings.', 'product-launch')), 500);
    }

    $payload = array(
        'model' => $model,
        'response_format' => array('type' => 'json_object'),
        'messages' => array(
            array(
                'role' => 'system',
                'content' => 'Return a normalized JSON business-idea validation report that includes a "phase_prefill" section matching 8 product-launch phases. Keep it specific and actionable.',
            ),
            array(
                'role' => 'user',
                'content' => wp_json_encode(array(
                    'idea' => array('title' => $title),
                    'description' => $description,
                    'audience' => $audience,
                    'problem' => $problem,
                    'outcome' => $outcome,
                )),
            ),
        ),
        'temperature' => 0.3,
    );

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ),
        'timeout' => 30,
        'body' => wp_json_encode($payload),
    ));

    if (is_wp_error($response)) {
        if (current_user_can('manage_options')) {
            error_log('[IS Ideas] OpenAI request failed: ' . $response->get_error_message());
        }
        wp_send_json_error(array('message' => __('OpenAI request failed. Please try again.', 'product-launch')), 500);
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $content = isset($body['choices'][0]['message']['content']) ? $body['choices'][0]['message']['content'] : '';
    $report = json_decode($content, true);

    if (!is_array($report) || empty($report['idea']['title'])) {
        if (current_user_can('manage_options')) {
            error_log('[IS Ideas] Invalid AI response: ' . print_r($body, true));
        }
        wp_send_json_error(array('message' => __('AI response was invalid or incomplete.', 'product-launch')), 500);
    }

    global $wpdb;
    $table = pl_get_validation_table_name(is_multisite() ? 'network' : 'site');
    $now = current_time('mysql');
    $user_id = get_current_user_id();
    $site_id = get_current_blog_id();

    $business_idea = isset($report['idea']['summary']) ? sanitize_text_field($report['idea']['summary']) : $report['idea']['title'];
    $business_idea = sanitize_text_field($business_idea);

    $validation_id = isset($report['idea']['external_id']) && !empty($report['idea']['external_id'])
        ? sanitize_text_field($report['idea']['external_id'])
        : 'val_' . wp_generate_password(12, false);

    $score_breakdown = isset($report['score']['breakdown']) && is_array($report['score']['breakdown'])
        ? $report['score']['breakdown']
        : array();

    $data = array(
        'validation_id'       => $validation_id,
        'user_id'             => $user_id,
        'site_id'             => $site_id,
        'business_idea'       => $business_idea,
        'external_api_id'     => $validation_id,
        'validation_score'    => isset($report['score']['overall']) ? (int) $report['score']['overall'] : 0,
        'confidence_level'    => isset($report['score']['band']) ? sanitize_text_field($report['score']['band']) : '',
        'confidence_score'    => isset($report['score']['confidence']) ? (float) $report['score']['confidence'] : 0,
        'market_demand_score' => isset($score_breakdown['market_demand']) ? (int) $score_breakdown['market_demand'] : 0,
        'competition_score'   => isset($score_breakdown['competition']) ? (int) $score_breakdown['competition'] : 0,
        'monetization_score'  => isset($score_breakdown['monetization']) ? (int) $score_breakdown['monetization'] : 0,
        'feasibility_score'   => isset($score_breakdown['feasibility']) ? (int) $score_breakdown['feasibility'] : 0,
        'ai_analysis_score'   => isset($score_breakdown['ai_analysis']) ? (int) $score_breakdown['ai_analysis'] : 0,
        'social_proof_score'  => isset($score_breakdown['social_proof']) ? (int) $score_breakdown['social_proof'] : 0,
        'validation_status'   => 'completed',
        'enrichment_status'   => 'completed',
        'library_published'   => 1,
        'published_at'        => $now,
        'expires_at'          => isset($report['expires_at']) ? sanitize_text_field($report['expires_at']) : null,
        'core_data'           => wp_json_encode($report),
        'signals_data'        => wp_json_encode(isset($report['signals']) ? $report['signals'] : array()),
        'recommendations_data'=> wp_json_encode(isset($report['recommendations']) ? $report['recommendations'] : array()),
        'phase_prefill_data'  => wp_json_encode(isset($report['phase_prefill']) ? $report['phase_prefill'] : array()),
        'created_at'          => $now,
        'updated_at'          => $now,
    );

    $formats = array(
        '%s', // validation_id
        '%d', // user_id
        '%d', // site_id
        '%s', // business_idea
        '%s', // external_api_id
        '%d', // validation_score
        '%s', // confidence_level
        '%f', // confidence_score
        '%d', // market_demand_score
        '%d', // competition_score
        '%d', // monetization_score
        '%d', // feasibility_score
        '%d', // ai_analysis_score
        '%d', // social_proof_score
        '%s', // validation_status
        '%s', // enrichment_status
        '%d', // library_published
        '%s', // published_at
        '%s', // expires_at
        '%s', // core_data
        '%s', // signals_data
        '%s', // recommendations_data
        '%s', // phase_prefill_data
        '%s', // created_at
        '%s', // updated_at
    );

    $inserted = $wpdb->insert($table, $data, $formats);

    if (false === $inserted) {
        if (current_user_can('manage_options')) {
            error_log('[IS Ideas] Failed to store validation: ' . $wpdb->last_error);
        }
        wp_send_json_error(array('message' => __('Unable to store the validation report.', 'product-launch')), 500);
    }

    $validation_id = (int) $wpdb->insert_id;

    if (function_exists('pl_clear_library_cache')) {
        pl_clear_library_cache();
    }

    wp_send_json_success(array(
        'idea_id' => 'local-' . $validation_id,
        'report'  => $report,
    ));
});

add_action('wp_ajax_is_admin_mapper_check', function () {
    check_ajax_referer('is_admin_push_nonce', 'nonce');

    if (!current_user_can('manage_options') && !current_user_can('manage_network_options')) {
        wp_send_json_error(array('message' => __('You do not have permission to run mapper checks.', 'product-launch')), 403);
    }

    $problems = is_phase_mapper_selfcheck();

    wp_send_json_success(array('problems' => $problems));
});
