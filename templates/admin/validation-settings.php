<?php
/**
 * Settings Page Template
 */
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap pl-validation-settings">
    <h1><?php _e('Validation Settings', 'product-launch'); ?></h1>

    <?php if (is_multisite() && is_network_admin()) : ?>
        <div class="notice notice-info">
            <p><?php _e('These settings apply network-wide and cannot be changed from individual subsites.', 'product-launch'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('pl_validation_settings'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="pl_validation_serp_api_key"><?php _e('Keyword Intelligence API Key', 'product-launch'); ?></label>
                </th>
                <td>
                    <input type="text"
                           id="pl_validation_serp_api_key"
                           name="pl_validation_serp_api_key"
                           value="<?php echo esc_attr($serp_key); ?>"
                           class="regular-text">
                    <p class="description">
                        <?php _e('Used for Google Trends and keyword volume requests (SerpAPI/DataForSEO).', 'product-launch'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="pl_validation_reddit_api_key"><?php _e('Reddit API Key', 'product-launch'); ?></label>
                </th>
                <td>
                    <input type="text"
                           id="pl_validation_reddit_api_key"
                           name="pl_validation_reddit_api_key"
                           value="<?php echo esc_attr($reddit_key); ?>"
                           class="regular-text">
                    <p class="description">
                        <?php _e('Required for sentiment analysis across startup subreddits.', 'product-launch'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="pl_validation_producthunt_token"><?php _e('Product Hunt Token', 'product-launch'); ?></label>
                </th>
                <td>
                    <input type="text"
                           id="pl_validation_producthunt_token"
                           name="pl_validation_producthunt_token"
                           value="<?php echo esc_attr($producthunt_token); ?>"
                           class="regular-text">
                    <p class="description">
                        <?php _e('Enables competition lookups from the Product Hunt API.', 'product-launch'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="pl_validation_default_limit"><?php _e('Default Monthly Limit', 'product-launch'); ?></label>
                </th>
                <td>            </tr>

            <tr>
                <th scope="row">
                    <label for="pl_validation_default_limit"><?php _e('Default Monthly Limit', 'product-launch'); ?></label>
                </th>
                <td>
                    <input type="number"
                           id="pl_validation_default_limit"
                           name="pl_validation_default_limit"
                           value="<?php echo esc_attr($default_limit); ?>"
                           min="1"
                           max="100">
                    <p class="description">
                        <?php _e('Number of validations free users can run per month (default: 3)', 'product-launch'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="pl_validation_cache_duration"><?php _e('Cache Duration (hours)', 'product-launch'); ?></label>
                </th>
                <td>
                    <input type="number"
                           id="pl_validation_cache_duration"
                           name="pl_validation_cache_duration"
                           value="<?php echo esc_attr($cache_duration); ?>"
                           min="1"
                           max="168">
                    <p class="description">
                        <?php _e('How long to cache enrichment data (default: 24 hours)', 'product-launch'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="pl_validation_auto_enrich"><?php _e('Auto-Enrich', 'product-launch'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox"
                               id="pl_validation_auto_enrich"
                               name="pl_validation_auto_enrich"
                               value="1"
                               <?php checked($auto_enrich, 1); ?>>
                        <?php _e('Automatically fetch enrichment data when creating validations', 'product-launch'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit"
                   name="pl_validation_settings_submit"
                   class="button button-primary"
                   value="<?php esc_attr_e('Save Settings', 'product-launch'); ?>">
        </p>
    </form>

    <div class="pl-api-status">
        <h2><?php _e('API Connection Status', 'product-launch'); ?></h2>
        <button type="button" class="button" id="pl-test-api-connection">
            <?php _e('Test Connection', 'product-launch'); ?>
        </button>
        <div id="pl-api-status-result"></div>
    </div>
</div>
