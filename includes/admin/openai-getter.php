<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('is_get_openai_settings')) {
    /**
     * Retrieve configured OpenAI settings with graceful fallbacks.
     *
     * @return array{key:string|null,model:string|null}
     */
    function is_get_openai_settings() {
        $api_key = '';
        $model = '';

        if (function_exists('pl_get_settings')) {
            $settings = pl_get_settings();
            if (is_array($settings)) {
                $api_key = isset($settings['openai_key']) ? $settings['openai_key'] : (isset($settings['openai_api_key']) ? $settings['openai_api_key'] : '');
                $model   = isset($settings['ai_model']) ? $settings['ai_model'] : (isset($settings['openai_model']) ? $settings['openai_model'] : '');
            }
        }

        if (empty($api_key) && function_exists('pl_get_network_settings')) {
            $network_settings = pl_get_network_settings();
            if (is_array($network_settings)) {
                $api_key = isset($network_settings['openai_key']) ? $network_settings['openai_key'] : (isset($network_settings['openai_api_key']) ? $network_settings['openai_api_key'] : '');
                if (empty($model)) {
                    $model = isset($network_settings['ai_model']) ? $network_settings['ai_model'] : (isset($network_settings['openai_model']) ? $network_settings['openai_model'] : '');
                }
            }
        }

        if (empty($api_key)) {
            $api_key = get_option('pl_openai_key');
        }

        if (empty($api_key)) {
            $api_key = get_option('openai_api_key');
        }

        if (empty($model)) {
            $model = get_option('pl_openai_model');
        }

        if (empty($model)) {
            $model = 'gpt-4o-mini';
        }

        return array(
            'key'   => $api_key ? trim($api_key) : '',
            'model' => $model ? trim($model) : 'gpt-4o-mini',
        );
    }
}
