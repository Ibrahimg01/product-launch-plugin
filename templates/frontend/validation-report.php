<?php
/**
 * Validation Report Template
 */
if (!defined('ABSPATH')) {
    exit;
}

$core_data = json_decode($validation->core_data, true);
$score_data = pl_format_validation_score($validation->validation_score);
?>

<div class="pl-validation-report-wrapper">
    
    <!-- Header -->
    <div class="pl-report-header">
        <a href="<?php echo esc_url(get_permalink(get_page_by_path('my-validations'))); ?>" class="pl-back-link">
            ← <?php _e('Back to My Validations', 'product-launch'); ?>
        </a>
        
        <h1 class="pl-report-title"><?php _e('Validation Report', 'product-launch'); ?></h1>
        
        <div class="pl-report-actions">
            <button class="button pl-export-pdf" disabled>
                <span>📄</span> <?php _e('Export PDF', 'product-launch'); ?>
            </button>
            <button class="button pl-push-to-phases" data-validation-id="<?php echo esc_attr($validation->id); ?>">
                <span>🚀</span> <?php _e('Push to 8 Phases', 'product-launch'); ?>
            </button>
        </div>
    </div>
    
    <!-- Hero Section -->
    <div class="pl-report-hero">
        <div class="pl-hero-content">
            <h2 class="pl-hero-idea"><?php echo esc_html($validation->business_idea); ?></h2>
            <div class="pl-hero-meta">
                <span><?php _e('Validated on', 'product-launch'); ?> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($validation->created_at))); ?></span>
                <?php if ($validation->confidence_level) : ?>
                    <span class="pl-confidence-badge pl-confidence-<?php echo esc_attr(strtolower($validation->confidence_level)); ?>">
                        <?php echo esc_html(ucfirst($validation->confidence_level)); ?> <?php _e('Confidence', 'product-launch'); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="pl-hero-score">
            <div class="pl-score-circle" style="--score-color: <?php echo esc_attr($score_data['color']); ?>; --score-percent: <?php echo esc_attr($validation->validation_score); ?>">
                <div class="pl-score-inner">
                    <span class="pl-score-value"><?php echo esc_html($validation->validation_score); ?></span>
                    <span class="pl-score-max">/100</span>
                </div>
            </div>
            <div class="pl-score-label"><?php echo esc_html($score_data['label']); ?></div>
        </div>
    </div>
    
    <!-- Score Breakdown -->
    <?php if ($core_data && isset($core_data['scoring_breakdown'])) : ?>
        <div class="pl-report-section pl-score-breakdown">
            <h3 class="pl-section-title">
                <span class="pl-section-icon">📊</span>
                <?php _e('Score Breakdown', 'product-launch'); ?>
            </h3>
            
            <div class="pl-breakdown-grid">
                <?php foreach ($core_data['scoring_breakdown'] as $key => $value) : ?>
                    <div class="pl-breakdown-card">
                        <div class="pl-breakdown-header">
                            <span class="pl-breakdown-name"><?php echo esc_html(ucwords(str_replace('_', ' ', str_replace('_score', '', $key)))); ?></span>
                            <span class="pl-breakdown-score"><?php echo esc_html($value); ?>%</span>
                        </div>
                        <div class="pl-breakdown-progress">
                            <div class="pl-breakdown-bar" style="width: <?php echo esc_attr($value); ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Analysis Summary -->
    <?php if ($core_data) : ?>
        <div class="pl-report-section pl-analysis-summary">
            <h3 class="pl-section-title">
                <span class="pl-section-icon">🔍</span>
                <?php _e('AI Analysis Summary', 'product-launch'); ?>
            </h3>
            
            <div class="pl-analysis-content">
                <?php if (isset($core_data['ai_assessment'])) : ?>
                    <div class="pl-analysis-box">
                        <h4><?php _e('AI Assessment', 'product-launch'); ?></h4>
                        <p><?php echo esc_html($core_data['ai_assessment']); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($core_data['market_validation'])) : ?>
                    <div class="pl-analysis-box">
                        <h4><?php _e('Market Validation', 'product-launch'); ?></h4>
                        <p><?php echo esc_html($core_data['market_validation']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Enrichment Sections Placeholder -->
    <div id="pl-enrichment-sections" class="pl-enrichment-sections">
        <div class="pl-loading-enrichment">
            <div class="pl-spinner"></div>
            <p><?php _e('Loading detailed analysis...', 'product-launch'); ?></p>
        </div>
    </div>
    
    <!-- Next Steps CTA -->
    <div class="pl-report-section pl-next-steps">
        <h3 class="pl-section-title">
            <span class="pl-section-icon">🚀</span>
            <?php _e('Ready to Launch?', 'product-launch'); ?>
        </h3>
        
        <div class="pl-next-steps-content">
            <p><?php _e('Push this validated idea to our 8-phase launch system to start building your business.', 'product-launch'); ?></p>
            <button class="button button-primary button-large pl-push-to-phases-main" data-validation-id="<?php echo esc_attr($validation->id); ?>">
                <?php _e('Start 8-Phase Launch Process', 'product-launch'); ?> →
            </button>
        </div>
    </div>
    
</div>

<script>
// Load enrichment data for this validation
jQuery(document).ready(function($) {
    const validationId = <?php echo intval($validation->id); ?>;
    const externalId = '<?php echo esc_js($validation->external_api_id); ?>';
    
    // This will be implemented to load additional sections
    // (trends, SEO, competitors, etc.) via AJAX
    console.log('Validation report loaded for ID:', validationId);
});
</script>
