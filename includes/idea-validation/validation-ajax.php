<?php
/**
 * AJAX handlers for validation admin
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_pl_test_api_connection', 'pl_ajax_test_api_connection');
function pl_ajax_test_api_connection() {
    check_ajax_referer('pl_validation_admin', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Unauthorized', 'product-launch')));
    }

    $api = new PL_Validation_API();
    $test_idea = 'Test connection - ' . date('Y-m-d H:i:s');
    $result = $api->submit_idea($test_idea, get_current_user_id(), get_current_blog_id());

    if ($result && isset($result['external_id'])) {
        wp_send_json_success(array(
            'message' => sprintf(
                __('API connection is working correctly. Test validation created with ID: %s', 'product-launch'),
                $result['external_id']
            ),
        ));
    }

    wp_send_json_error(array(
        'message' => __('Unable to connect to the validation API. Please check your API key and endpoint settings.', 'product-launch'),
    ));
}

add_action('wp_ajax_pl_delete_validation', 'pl_ajax_delete_validation');
function pl_ajax_delete_validation() {
    check_ajax_referer('pl_validation_admin', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Unauthorized', 'product-launch')));
    }

    $validation_id = isset($_POST['validation_id']) ? absint($_POST['validation_id']) : 0;

    if (!$validation_id) {
        wp_send_json_error(array('message' => __('Invalid validation ID', 'product-launch')));
    }

    $access = new PL_Validation_Access();
    $result = $access->delete_validation($validation_id, get_current_user_id());

    if ($result) {
        wp_send_json_success(array('message' => __('Validation deleted successfully', 'product-launch')));
    }

    wp_send_json_error(array('message' => __('Unable to delete validation', 'product-launch')));
}

add_action('wp_ajax_pl_get_validation_details', 'pl_ajax_get_validation_details');
function pl_ajax_get_validation_details() {
    check_ajax_referer('pl_validation_admin', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Unauthorized', 'product-launch')));
    }

    $validation_id = isset($_POST['validation_id']) ? absint($_POST['validation_id']) : 0;

    if (!$validation_id) {
        wp_send_json_error(array('message' => __('Invalid validation ID', 'product-launch')));
    }

    $access = new PL_Validation_Access();
    $validation = $access->get_validation($validation_id);

    if (!$validation) {
        wp_send_json_error(array('message' => __('Validation not found', 'product-launch')));
    }

    wp_send_json_success(array(
        'validation' => $validation,
        'html'       => __('Details view will be implemented in Phase 4', 'product-launch'),
    ));
}

add_action('wp_ajax_pl_bulk_validation_action', 'pl_ajax_bulk_validation_action');
function pl_ajax_bulk_validation_action() {
    check_ajax_referer('pl_validation_admin', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Unauthorized', 'product-launch')));
    }

    $action = isset($_POST['bulk_action']) ? sanitize_text_field(wp_unslash($_POST['bulk_action'])) : '';
    $validation_ids = isset($_POST['validation_ids']) ? array_map('absint', (array) $_POST['validation_ids']) : array();

    if (empty($action) || empty($validation_ids)) {
        wp_send_json_error(array('message' => __('Invalid request', 'product-launch')));
    }

    $access = new PL_Validation_Access();
    $success_count = 0;

    foreach ($validation_ids as $validation_id) {
        if ('delete' === $action) {
            if ($access->delete_validation($validation_id, get_current_user_id())) {
                $success_count++;
            }
        }
    }

    wp_send_json_success(array(
        'message' => sprintf(
            _n('%d validation %s successfully', '%d validations %s successfully', $success_count, 'product-launch'),
            $success_count,
            'delete' === $action ? __('deleted', 'product-launch') : __('updated', 'product-launch')
        ),
    ));
}
