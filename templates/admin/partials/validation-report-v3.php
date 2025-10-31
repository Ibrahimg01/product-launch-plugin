<?php
/**
 * Enhanced Validation Report Display - V3
 * Shows multi-signal breakdown with visualization
 */

if (!defined('ABSPATH')) exit;

$breakdown = [
    'market_demand' => $validation->market_demand_score ?? 0,
    'competition' => $validation->competition_score ?? 0,
    'monetization' => $validation->monetization_score ?? 0,
    'feasibility' => $validation->feasibility_score ?? 0,
    'ai_analysis' => $validation->ai_analysis_score ?? 0,
    'social_proof' => $validation->social_proof_score ?? 0,
];

$signals = !empty($validation->signals_data) ? json_decode($validation->signals_data, true) : [];
$recommendations = !empty($validation->recommendations_data) ? json_decode($validation->recommendations_data, true) : [];
?>

<div class="pl-validation-report-v3">
    
    <!-- Hero Score Section -->
    <div class="pl-report-hero">
        <div class="pl-score-display">
            <div class="pl-score-circle">
                <svg viewBox="0 0 200 200" class="pl-score-ring">
                    <circle cx="100" cy="100" r="90" fill="none" stroke="#e5e7eb" stroke-width="12"/>
                    <circle cx="100" cy="100" r="90" fill="none" stroke="#3b82f6" stroke-width="12"
                            stroke-dasharray="<?php echo esc_attr(565 * ($validation->validation_score / 100)); ?> 565"
                            transform="rotate(-90 100 100)"/>
                </svg>
                <div class="pl-score-content">
                    <span class="pl-score-number"><?php echo esc_html($validation->validation_score); ?></span>
                    <span class="pl-score-label">/100</span>
                </div>
            </div>
        </div>
        
        <div class="pl-score-meta">
            <h2><?php echo esc_html(wp_trim_words($validation->business_idea, 12)); ?></h2>
            <div class="pl-confidence-badge pl-confidence-<?php echo esc_attr(strtolower($validation->confidence_level)); ?>">
                <span class="pl-badge-icon">
                    <?php if ($validation->confidence_level === 'high') : ?>🎯<?php endif; ?>
                    <?php if ($validation->confidence_level === 'medium') : ?>⚡<?php endif; ?>
                    <?php if ($validation->confidence_level === 'low') : ?>⚠️<?php endif; ?>
                </span>
                <?php echo esc_html(ucfirst($validation->confidence_level)); ?> Confidence
            </div>
            <p class="pl-validated-date">
                <?php 
                printf(
                    esc_html__('Validated %s', 'product-launch'),
                    human_time_diff(strtotime($validation->created_at), current_time('timestamp'))
                ); 
                ?> ago
            </p>
        </div>
    </div>
    
    <!-- Signal Breakdown Grid -->
    <div class="pl-signals-section">
        <h3><?php esc_html_e('Validation Signals', 'product-launch'); ?></h3>
        
        <div class="pl-signals-grid">
            <?php foreach ($breakdown as $signal_key => $score) : 
                $signal_label = ucwords(str_replace('_', ' ', $signal_key));
                $signal_class = $score >= 70 ? 'high' : ($score >= 40 ? 'medium' : 'low');
                $signal_icon = [
                    'market_demand' => '📈',
                    'competition' => '🎯',
                    'monetization' => '💰',
                    'feasibility' => '🔧',
                    'ai_analysis' => '🤖',
                    'social_proof' => '💬',
                ][$signal_key] ?? '📊';
            ?>
                <div class="pl-signal-card pl-signal-<?php echo esc_attr($signal_class); ?>">
                    <div class="pl-signal-header">
                        <span class="pl-signal-icon"><?php echo $signal_icon; ?></span>
                        <span class="pl-signal-name"><?php echo esc_html($signal_label); ?></span>
                    </div>
                    <div class="pl-signal-score"><?php echo esc_html($score); ?></div>
                    <div class="pl-signal-bar">
                        <div class="pl-signal-fill" style="width: <?php echo esc_attr($score); ?>%"></div>
                    </div>
                    <?php if (isset($signals[$signal_key]['insight'])) : ?>
                        <p class="pl-signal-insight"><?php echo esc_html($signals[$signal_key]['insight']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Recommendations -->
    <?php if (!empty($recommendations['immediate_actions'])) : ?>
        <div class="pl-recommendations-section">
            <h3><?php esc_html_e('Recommended Actions', 'product-launch'); ?></h3>
            
            <div class="pl-actions-list">
                <?php foreach ($recommendations['immediate_actions'] as $action) : 
                    $priority_class = 'pl-priority-' . ($action['priority'] ?? 'medium');
                    $priority_icon = [
                        'high' => '🔥',
                        'medium' => '⚡',
                        'low' => '💡',
                    ][$action['priority'] ?? 'medium'];
                ?>
                    <div class="pl-action-card <?php echo esc_attr($priority_class); ?>">
                        <div class="pl-action-icon"><?php echo $priority_icon; ?></div>
                        <div class="pl-action-content">
                            <h4><?php echo esc_html($action['action']); ?></h4>
                            <p><?php echo esc_html($action['reason']); ?></p>
                            <?php if (isset($action['expected_outcome'])) : ?>
                                <p class="pl-action-outcome">
                                    <strong><?php esc_html_e('Expected:', 'product-launch'); ?></strong>
                                    <?php echo esc_html($action['expected_outcome']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Phase Push CTA -->
    <div class="pl-report-actions">
        <button type="button" 
                class="button button-primary button-hero pl-push-to-phases"
                data-validation-id="<?php echo esc_attr($validation->id); ?>"
                data-has-prefill="<?php echo !empty($validation->phase_prefill_data) ? '1' : '0'; ?>">
            <span class="dashicons dashicons-rocket"></span>
            <?php esc_html_e('Push to 8-Phase Launch System', 'product-launch'); ?>
        </button>
        
        <?php if ($validation->expires_at && strtotime($validation->expires_at) < time()) : ?>
            <button type="button" class="button pl-refresh-validation" data-validation-id="<?php echo esc_attr($validation->id); ?>">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Refresh Validation', 'product-launch'); ?>
            </button>
        <?php endif; ?>
    </div>
    
</div>
