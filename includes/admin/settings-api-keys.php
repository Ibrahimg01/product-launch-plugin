<?php
/**
 * API Keys Management Interface
 * Network Admin Settings for V3 Validation APIs
 */

if (!defined('ABSPATH')) exit;

function pl_render_api_keys_settings() {
    if (!current_user_can('manage_network_options')) {
        wp_die(__('You do not have permission to access this page.'));
    }
    
    // Save settings
    if (isset($_POST['pl_save_api_keys']) && check_admin_referer('pl_api_keys_settings')) {
        update_site_option('pl_serp_api_key', sanitize_text_field($_POST['serp_api_key'] ?? ''));
        update_site_option('pl_reddit_client_id', sanitize_text_field($_POST['reddit_client_id'] ?? ''));
        update_site_option('pl_reddit_client_secret', sanitize_text_field($_POST['reddit_client_secret'] ?? ''));
        
        echo '<div class="notice notice-success"><p>' . __('API keys saved successfully.', 'product-launch') . '</p></div>';
    }
    
    $serp_key = get_site_option('pl_serp_api_key', '');
    $reddit_id = get_site_option('pl_reddit_client_id', '');
    $reddit_secret = get_site_option('pl_reddit_client_secret', '');
    ?>
    
    <div class="wrap">
        <h1><?php _e('Validation API Keys', 'product-launch'); ?></h1>
        
        <div class="pl-api-keys-intro">
            <p><?php _e('Configure external API credentials for the Multi-Signal Validation Engine.', 'product-launch'); ?></p>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('pl_api_keys_settings'); ?>
            
            <table class="form-table">
                
                <!-- SerpAPI Key -->
                <tr>
                    <th scope="row">
                        <label for="serp_api_key"><?php _e('SerpAPI Key', 'product-launch'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="serp_api_key" 
                               name="serp_api_key" 
                               value="<?php echo esc_attr($serp_key); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php printf(
                                __('Get your API key from <a href="%s" target="_blank">SerpAPI</a> for keyword research data.', 'product-launch'),
                                'https://serpapi.com/manage-api-key'
                            ); ?>
                        </p>
                    </td>
                </tr>
                
                <!-- Reddit Client ID -->
                <tr>
                    <th scope="row">
                        <label for="reddit_client_id"><?php _e('Reddit Client ID', 'product-launch'); ?></label>
                    </th>
                    <td>
                        <input type="text" 
                               id="reddit_client_id" 
                               name="reddit_client_id" 
                               value="<?php echo esc_attr($reddit_id); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php printf(
                                __('Create an app at <a href="%s" target="_blank">Reddit Apps</a> for social proof analysis.', 'product-launch'),
                                'https://www.reddit.com/prefs/apps'
                            ); ?>
                        </p>
                    </td>
                </tr>
                
                <!-- Reddit Client Secret -->
                <tr>
                    <th scope="row">
                        <label for="reddit_client_secret"><?php _e('Reddit Client Secret', 'product-launch'); ?></label>
                    </th>
                    <td>
                        <input type="password" 
                               id="reddit_client_secret" 
                               name="reddit_client_secret" 
                               value="<?php echo esc_attr($reddit_secret); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php _e('Your Reddit app secret key.', 'product-launch'); ?>
                        </p>
                    </td>
                </tr>
                
            </table>
            
            <p class="submit">
                <button type="submit" name="pl_save_api_keys" class="button button-primary">
                    <?php _e('Save API Keys', 'product-launch'); ?>
                </button>
            </p>
        </form>
        
        <!-- API Status Check -->
        <div class="pl-api-status">
            <h2><?php _e('API Connection Status', 'product-launch'); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Service', 'product-launch'); ?></th>
                        <th><?php _e('Status', 'product-launch'); ?></th>
                        <th><?php _e('Weight', 'product-launch'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>OpenAI (AI Analysis)</td>
                        <td><?php echo pl_check_api_status('openai') ? '✅ Connected' : '❌ Not configured'; ?></td>
                        <td>20%</td>
                    </tr>
                    <tr>
                        <td>SerpAPI (Market Demand)</td>
                        <td><?php echo !empty($serp_key) ? '✅ Configured' : '⚠️ Not configured'; ?></td>
                        <td>25%</td>
                    </tr>
                    <tr>
                        <td>Reddit API (Social Proof)</td>
                        <td><?php echo (!empty($reddit_id) && !empty($reddit_secret)) ? '✅ Configured' : '⚠️ Not configured'; ?></td>
                        <td>10%</td>
                    </tr>
                    <tr>
                        <td>GitHub API (Competition)</td>
                        <td>✅ Always available</td>
                        <td>20%</td>
                    </tr>
                </tbody>
            </table>
            <p class="description">
                <?php _e('Missing APIs will use estimated data. For best results, configure all services.', 'product-launch'); ?>
            </p>
        </div>
        
    </div>
    <?php
}

function pl_check_api_status($service) {
    switch ($service) {
        case 'openai':
            $settings = pl_get_settings();
            return !empty($settings['openai_key']);
        case 'serp':
            return !empty(get_site_option('pl_serp_api_key'));
        case 'reddit':
            return !empty(get_site_option('pl_reddit_client_id'));
        default:
            return false;
    }
}
