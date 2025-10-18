<?php
/**
 * My Validations Template
 */
if (!defined('ABSPATH')) {
    exit;
}

$quota = new PL_Validation_Quota();
$quota_info = $quota->get_quota_info($user_id);
?>

<div class="pl-my-validations-wrapper">
    
    <!-- Header with Quota -->
    <div class="pl-validations-header">
        <h2><?php _e('My Validations', 'product-launch'); ?></h2>
        
        <div class="pl-header-actions">
            <?php if ($quota_info['unlimited']) : ?>
                <span class="pl-quota-badge unlimited">
                    ✨ <?php _e('Unlimited', 'product-launch'); ?>
                </span>
            <?php else : ?>
                <span class="pl-quota-badge">
                    <?php echo esc_html($quota_info['remaining']); ?> / <?php echo esc_html($quota_info['limit']); ?> 
                    <?php _e('remaining', 'product-launch'); ?>
                </span>
            <?php endif; ?>
            
            <a href="<?php echo esc_url(get_permalink(get_page_by_path('validate-idea'))); ?>" class="button button-primary">
                <?php _e('New Validation', 'product-launch'); ?>
            </a>
        </div>
    </div>
    
    <?php if (empty($validations)) : ?>
        <div class="pl-empty-state">
            <div class="pl-empty-icon">💡</div>
            <h3><?php _e('No validations yet', 'product-launch'); ?></h3>
            <p><?php _e('Start by validating your first business idea!', 'product-launch'); ?></p>
            <a href="<?php echo esc_url(get_permalink(get_page_by_path('validate-idea'))); ?>" class="button button-primary">
                <?php _e('Validate an Idea', 'product-launch'); ?>
            </a>
        </div>
    <?php else : ?>
        <div class="pl-validations-grid">
            <?php foreach ($validations as $validation) : ?>
                <?php
                $score_data = pl_format_validation_score($validation->validation_score);
                $core_data = json_decode($validation->core_data, true);
                ?>
                
                <div class="pl-validation-card">
                    <div class="pl-card-header">
                        <div class="pl-card-score pl-score-<?php echo esc_attr($score_data['class']); ?>">
                            <span class="pl-score-number"><?php echo esc_html($validation->validation_score); ?></span>
                            <span class="pl-score-label"><?php echo esc_html($score_data['label']); ?></span>
                        </div>
                        <div class="pl-card-status">
                            <span class="pl-status-badge pl-status-<?php echo esc_attr($validation->validation_status); ?>">
                                <?php echo esc_html(ucfirst($validation->validation_status)); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="pl-card-body">
                        <h3 class="pl-card-title"><?php echo esc_html(wp_trim_words($validation->business_idea, 12)); ?></h3>
                        
                        <div class="pl-card-meta">
                            <span class="pl-meta-item">
                                <span class="pl-meta-icon">📅</span>
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($validation->created_at))); ?>
                            </span>
                            
                            <?php if ($validation->confidence_level) : ?>
                                <span class="pl-meta-item">
                                    <span class="pl-meta-icon">🎯</span>
                                    <?php echo esc_html(ucfirst($validation->confidence_level)); ?> <?php _e('confidence', 'product-launch'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($core_data && isset($core_data['scoring_breakdown'])) : ?>
                            <div class="pl-card-breakdown">
                                <?php foreach (array_slice($core_data['scoring_breakdown'], 0, 3) as $key => $value) : ?>
                                    <div class="pl-breakdown-item">
                                        <span class="pl-breakdown-label"><?php echo esc_html(ucwords(str_replace('_', ' ', str_replace('_score', '', $key)))); ?></span>
                                        <div class="pl-breakdown-bar">
                                            <div class="pl-breakdown-fill" style="width: <?php echo esc_attr($value); ?>%"></div>
                                        </div>
                                        <span class="pl-breakdown-value"><?php echo esc_html($value); ?>%</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="pl-card-footer">
                        <a href="<?php echo esc_url(add_query_arg('validation_id', $validation->id, get_permalink())); ?>" 
                           class="button pl-view-report">
                            <?php _e('View Full Report', 'product-launch'); ?> →
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
