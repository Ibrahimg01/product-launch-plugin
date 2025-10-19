<?php
/**
 * Validation Form Template
 */
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$quota_info = null;

if ($user_id) {
    $quota = new PL_Validation_Quota();
    $quota_info = $quota->get_quota_info($user_id);
}
?>

<div class="pl-validation-form-wrapper">
    
    <?php if ($atts['show_quota'] === 'yes' && $quota_info) : ?>
        <div class="pl-quota-display">
            <?php if ($quota_info['unlimited']) : ?>
                <div class="pl-quota-unlimited">
                    <span class="pl-quota-icon">✨</span>
                    <span class="pl-quota-text"><?php _e('Unlimited validations', 'product-launch'); ?></span>
                </div>
            <?php else : ?>
                <div class="pl-quota-limited">
                    <div class="pl-quota-info">
                        <span class="pl-quota-label"><?php _e('Validations remaining:', 'product-launch'); ?></span>
                        <span class="pl-quota-value"><?php echo esc_html($quota_info['remaining']); ?> / <?php echo esc_html($quota_info['limit']); ?></span>
                    </div>
                    <div class="pl-quota-bar">
                        <div class="pl-quota-progress" style="width: <?php echo ($quota_info['limit'] > 0 ? ($quota_info['remaining'] / $quota_info['limit']) * 100 : 0); ?>%"></div>
                    </div>
                    <p class="pl-quota-month"><?php echo esc_html($quota_info['month']); ?></p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="pl-validation-form-container">
        <h2 class="pl-form-title"><?php echo esc_html($atts['title']); ?></h2>

        <form id="pl-validation-form" class="pl-validation-form">
            <?php wp_nonce_field('pl_validation_frontend', 'pl_validation_nonce'); ?>

            <div class="pl-form-field">
                <label for="pl-business-idea">
                    <?php _e('Describe Your Business Idea', 'product-launch'); ?>
                    <span class="pl-required">*</span>
                </label>
                <textarea
                    id="pl-business-idea"
                    name="business_idea"
                    rows="6"
                    placeholder="<?php _e('Example: A mobile app that connects local farmers directly with restaurants, eliminating middlemen and ensuring fresh produce delivery within 24 hours...', 'product-launch'); ?>"
                    required
                    minlength="20"
                ></textarea>
                <p class="pl-field-description">
                    <?php _e('Provide as much detail as possible (minimum 20 characters). Include target market, key features, and what problem it solves.', 'product-launch'); ?>
                </p>
                <div class="pl-char-counter">
                    <span id="pl-char-count">0</span> <?php _e('characters', 'product-launch'); ?>
                </div>
            </div>

            <div class="pl-form-actions">
                <button type="submit" class="button button-primary pl-submit-button">
                    <span class="pl-button-text"><?php _e('Validate My Idea', 'product-launch'); ?></span>
                    <span class="pl-button-loader" style="display: none;">
                        <span class="pl-spinner"></span>
                        <?php _e('Validating...', 'product-launch'); ?>
                    </span>
                </button>
            </div>

            <div id="pl-form-message" class="pl-form-message" style="display: none;"></div>
        </form>

        <!-- Processing Modal -->
        <div id="pl-processing-modal" class="pl-modal" style="display: none;">
            <div class="pl-modal-content">
                <div class="pl-modal-header">
                    <h3><?php _e('Validating Your Idea', 'product-launch'); ?></h3>
                </div>
                <div class="pl-modal-body">
                    <div class="pl-progress-steps">
                        <div class="pl-step active">
                            <span class="pl-step-icon">📝</span>
                            <span class="pl-step-text"><?php _e('Analyzing your idea...', 'product-launch'); ?></span>
                        </div>
                        <div class="pl-step">
                            <span class="pl-step-icon">🔍</span>
                            <span class="pl-step-text"><?php _e('Researching market data...', 'product-launch'); ?></span>
                        </div>
                        <div class="pl-step">
                            <span class="pl-step-icon">📊</span>
                            <span class="pl-step-text"><?php _e('Calculating validation score...', 'product-launch'); ?></span>
                        </div>
                        <div class="pl-step">
                            <span class="pl-step-icon">✅</span>
                            <span class="pl-step-text"><?php _e('Generating report...', 'product-launch'); ?></span>
                        </div>
                    </div>
                    <div class="pl-progress-bar">
                        <div class="pl-progress-fill"></div>
                    </div>
                    <p class="pl-progress-note"><?php _e('This may take 30-60 seconds. Please do not close this window.', 'product-launch'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
