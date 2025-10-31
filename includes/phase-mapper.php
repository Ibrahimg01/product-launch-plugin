<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('is_phase_map')) {
    /**
     * Map normalized report paths to destination option keys for each phase.
     *
     * @return array
     */
    function is_phase_map() {
        $map = array(
            'market_clarity' => array(
                'pl_market_audience' => 'market.target_audience',
                'pl_market_problem'  => 'market.core_problem',
                'pl_market_outcome'  => 'market.outcomes[0]',
            ),
            'create_offer' => array(
                'pl_offer_summary' => 'phase_prefill.create_offer.pl_offer_summary',
                'pl_offer_promise' => 'phase_prefill.create_offer.pl_offer_promise',
                'pl_offer_pricing' => 'phase_prefill.create_offer.pl_offer_pricing',
            ),
            'create_service' => array(
                'pl_service_packages' => 'phase_prefill.create_service.pl_service_packages',
                'pl_service_delivery' => 'phase_prefill.create_service.pl_service_delivery',
            ),
            'build_funnel' => array(
                'pl_funnel_promise'  => 'phase_prefill.build_funnel.pl_funnel_promise',
                'pl_funnel_headline' => 'phase_prefill.build_funnel.pl_funnel_headline',
                'pl_funnel_cta'      => 'phase_prefill.build_funnel.pl_funnel_cta',
            ),
            'email_sequences' => array(
                'pl_email_big_idea' => 'phase_prefill.email_sequences.pl_email_big_idea',
                'pl_email_angles'   => 'phase_prefill.email_sequences.pl_email_angles[0]',
            ),
            'organic_posts' => array(
                'pl_social_hooks'    => 'phase_prefill.organic_posts.pl_social_hooks[0]',
                'pl_content_pillars' => 'phase_prefill.organic_posts.pl_content_pillars[0]',
            ),
            'facebook_ads' => array(
                'pl_ads_primary_text'   => 'phase_prefill.facebook_ads.pl_ads_primary_text',
                'pl_ads_headline'       => 'phase_prefill.facebook_ads.pl_ads_headline',
                'pl_ads_creative_ideas' => 'phase_prefill.facebook_ads.pl_ads_creative_ideas[0]',
            ),
            'launch' => array(
                'pl_launch_plan' => 'phase_prefill.launch.pl_launch_plan',
                'pl_kpis'        => 'phase_prefill.launch.pl_kpis[0]',
            ),
        );

        return apply_filters('is_phase_map', $map);
    }
}

if (!function_exists('is_phase_dest_registry')) {
    /**
     * Central registry of destination keys grouped by phase.
     *
     * @return array
     */
    function is_phase_dest_registry() {
        $registry = array(
            'market_clarity' => array('pl_market_audience', 'pl_market_problem', 'pl_market_outcome'),
            'create_offer'   => array('pl_offer_summary', 'pl_offer_promise', 'pl_offer_pricing'),
            'create_service' => array('pl_service_packages', 'pl_service_delivery'),
            'build_funnel'   => array('pl_funnel_promise', 'pl_funnel_headline', 'pl_funnel_cta'),
            'email_sequences'=> array('pl_email_big_idea', 'pl_email_angles'),
            'organic_posts'  => array('pl_social_hooks', 'pl_content_pillars'),
            'facebook_ads'   => array('pl_ads_primary_text', 'pl_ads_headline', 'pl_ads_creative_ideas'),
            'launch'         => array('pl_launch_plan', 'pl_kpis'),
        );

        return apply_filters('is_phase_dest_registry', $registry);
    }
}

if (!function_exists('is_overwrite_policy')) {
    /**
     * Per-phase overwrite policy configuration.
     *
     * @return array
     */
    function is_overwrite_policy() {
        $policy = array(
            'default'        => 'fill',
            'market_clarity' => 'fill',
            'create_offer'   => 'fill',
            'create_service' => 'fill',
            'build_funnel'   => 'fill',
            'email_sequences'=> 'fill',
            'organic_posts'  => 'fill',
            'facebook_ads'   => 'fill',
            'launch'         => 'fill',
        );

        return apply_filters('is_overwrite_policy', $policy);
    }
}

if (!function_exists('is_get_by_path')) {
    /**
     * Retrieve a value from an array using dotted notation and optional numeric indexes.
     *
     * @param array  $arr  Source array.
     * @param string $path Dotted path (supports tokens like field[0]).
     *
     * @return mixed|null
     */
    function is_get_by_path($arr, $path) {
        if (!is_array($arr) || '' === $path) {
            return null;
        }

        $parts = explode('.', $path);

        foreach ($parts as $part) {
            if (preg_match('/^([^\[]+)\[(\d+)\]$/', $part, $matches)) {
                $key = $matches[1];
                $index = (int) $matches[2];

                if (!isset($arr[$key]) || !is_array($arr[$key]) || !array_key_exists($index, $arr[$key])) {
                    return null;
                }

                $arr = $arr[$key][$index];
                continue;
            }

            if (!is_array($arr) || !array_key_exists($part, $arr)) {
                return null;
            }

            $arr = $arr[$part];
        }

        return $arr;
    }
}

if (!function_exists('pl_apply_v3_phase_data')) {
    /**
     * Apply V3 phase prefill data to user meta
     *
     * @param int $validation_id Validation database ID
     * @param int $user_id Target user ID
     * @param int $site_id Target site ID
     * @param bool $overwrite Whether to overwrite existing data
     * @return bool|WP_Error
     */
    function pl_apply_v3_phase_data($validation_id, $user_id, $site_id, $overwrite = false) {
        global $wpdb;
        $table = pl_get_validation_table_name(is_multisite() ? 'network' : 'site');

        $validation = $wpdb->get_row($wpdb->prepare(
            "SELECT phase_prefill_data FROM $table WHERE id = %d",
            $validation_id
        ));

        if (!$validation || empty($validation->phase_prefill_data)) {
            return new WP_Error('no_data', __('No phase data available for this validation.', 'product-launch'));
        }

        $phase_data = json_decode($validation->phase_prefill_data, true);

        if (!is_array($phase_data)) {
            return new WP_Error('invalid_data', __('Phase data is corrupted.', 'product-launch'));
        }

        $phases = [
            'market_clarity',
            'create_offer',
            'create_service',
            'build_funnel',
            'email_sequences',
            'organic_posts',
            'facebook_ads',
            'launch',
        ];

        foreach ($phases as $phase) {
            if (empty($phase_data[$phase])) {
                continue;
            }

            $meta_key = 'product_launch_progress_' . $phase . '_' . $site_id;
            $existing = get_user_meta($user_id, $meta_key, true);

            if ($existing && !$overwrite) {
                // Merge new data with existing, preserving user entries
                $existing_data = json_decode($existing, true);
                if (is_array($existing_data)) {
                    $phase_data[$phase] = array_merge($phase_data[$phase], $existing_data);
                }
            }

            update_user_meta(
                $user_id,
                $meta_key,
                wp_json_encode($phase_data[$phase])
            );
        }

        return true;
    }
}

if (!function_exists('is_apply_phase_mapping')) {
    /**
     * Apply selected mappings to WordPress options representing the launch phases.
     *
     * @param array $report    Normalized validation report.
     * @param array $selected  Destination keys selected by the administrator.
     * @param bool  $overwrite Whether to overwrite existing values.
     */
    function is_apply_phase_mapping(array $report, array $selected, $overwrite = false) {
        if (empty($report) || empty($selected)) {
            return;
        }

        $map = is_phase_map();
        $policy = is_overwrite_policy();
        $selected = array_values(array_unique(array_map('sanitize_key', $selected)));

        foreach ($map as $phase => $pairs) {
            if (empty($pairs) || !is_array($pairs)) {
                continue;
            }

            $phase_policy = isset($policy[$phase]) ? $policy[$phase] : (isset($policy['default']) ? $policy['default'] : 'fill');
            $phase_overwrite = ('overwrite' === $phase_policy);

            foreach ($pairs as $dst_key => $src_path) {
                if (!in_array($dst_key, $selected, true)) {
                    continue;
                }

                $new_value = is_get_by_path($report, $src_path);
                if (null === $new_value || '' === $new_value) {
                    continue;
                }

                $current = get_option($dst_key, '');
                $should_overwrite = $overwrite || $phase_overwrite;

                if ('' !== $current && !$should_overwrite) {
                    if (defined('IS_IDEAS_DEBUG') && IS_IDEAS_DEBUG) {
                        error_log(sprintf('[IS Ideas] Skip %s (phase %s) existing value retained.', $dst_key, $phase));
                    }
                    continue;
                }

                $stored_value = is_array($new_value) ? wp_json_encode($new_value) : $new_value;
                update_option($dst_key, $stored_value);

                if (defined('IS_IDEAS_DEBUG') && IS_IDEAS_DEBUG) {
                    error_log(sprintf('[IS Ideas] Wrote %s (phase %s) overwrite=%s', $dst_key, $phase, $should_overwrite ? '1' : '0'));
                }
            }
        }
    }
}

if (!function_exists('is_phase_mapper_selfcheck')) {
    /**
     * Validate that destination keys and map pairs are well-formed.
     *
     * @return array List of detected issues.
     */
    function is_phase_mapper_selfcheck() {
        $problems = array();
        $registry = is_phase_dest_registry();

        foreach ($registry as $phase => $keys) {
            foreach ((array) $keys as $key) {
                if (empty($key) || !is_string($key)) {
                    $problems[] = array(
                        'phase' => $phase,
                        'key'   => $key,
                        'issue' => 'empty_or_invalid_key',
                    );
                    continue;
                }

                $current = get_option($key, null);
                if (null === $current) {
                    $problems[] = array(
                        'phase' => $phase,
                        'key'   => $key,
                        'issue' => 'not_set_yet',
                    );
                }
            }
        }

        $map = is_phase_map();
        foreach ($map as $phase => $pairs) {
            if (empty($pairs) || !is_array($pairs)) {
                continue;
            }

            foreach ($pairs as $dst_key => $src_path) {
                if (empty($dst_key) || empty($src_path)) {
                    $problems[] = array(
                        'phase' => $phase,
                        'key'   => $dst_key,
                        'issue' => 'map_pair_missing',
                    );
                }
            }
        }

        return apply_filters('is_phase_mapper_selfcheck', $problems);
    }
}
