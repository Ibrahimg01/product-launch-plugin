<?php
/**
 * Launch Template
 * File: templates/launch.php
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$site_id = get_current_blog_id();

// Define current phase and get completed phases
$current_phase = 'launch';
$completed_phases = array();
$all_phases = array('market_clarity', 'create_offer', 'create_service', 'build_funnel', 'email_sequences', 'organic_posts', 'facebook_ads', 'launch');

foreach ($all_phases as $phase) {
    $phase_progress = get_user_meta($user_id, 'product_launch_progress_' . $phase . '_' . $site_id, true);
    if (!empty($phase_progress)) {
        $completed_phases[] = $phase;
    }
}

// Get saved progress for this phase
$progress_data = get_user_meta($user_id, 'product_launch_progress_launch_' . $site_id, true);
$progress_data = $progress_data ? json_decode($progress_data, true) : array();

// Handle form submission
if (isset($_POST['save_progress']) && wp_verify_nonce($_POST['_wpnonce'], 'launch_progress')) {
    $form_data = array(
        'launch_strategy' => sanitize_textarea_field($_POST['launch_strategy'] ?? ''),
        'launch_timeline' => sanitize_textarea_field($_POST['launch_timeline'] ?? ''),
        'pre_launch_checklist' => sanitize_textarea_field($_POST['pre_launch_checklist'] ?? ''),
        'launch_day_plan' => sanitize_textarea_field($_POST['launch_day_plan'] ?? ''),
        'communication_plan' => sanitize_textarea_field($_POST['communication_plan'] ?? ''),
        'contingency_plan' => sanitize_textarea_field($_POST['contingency_plan'] ?? ''),
        'post_launch_analysis' => sanitize_textarea_field($_POST['post_launch_analysis'] ?? ''),
        'completed_at' => current_time('mysql')
    );
    
    update_user_meta($user_id, 'product_launch_progress_launch_' . $site_id, json_encode($form_data));
    
    echo '<div class="notice notice-success is-dismissible"><p>Launch progress saved successfully!</p></div>';
    $progress_data = $form_data;
}

$is_completed = !empty($progress_data['completed_at']);
?>
<?php
// --- Auto-persist & load progress for phase: launch ---
$user_id = get_current_user_id();
$site_id = get_current_blog_id();
if (function_exists('pl_handle_phase_save_auto')) {
    $progress_data_saved = pl_handle_phase_save_auto('launch');
    if ($progress_data_saved && is_array($progress_data_saved)) {
        $progress_data = $progress_data_saved;
    }
}
if (!isset($progress_data) || !is_array($progress_data) || empty($progress_data)) {
    $saved_raw = get_user_meta($user_id, 'product_launch_progress_launch_' . $site_id, true);
    if ($saved_raw) {
        $decoded = json_decode($saved_raw, true);
        if (is_array($decoded)) $progress_data = $decoded;
    }
}
?>


<div class="wrap product-launch-phase-container">
    <div class="phase-header">
        <div class="phase-icon">
            <span class="dashicons dashicons-pressthis"></span>
        </div>
        <h1>Launching Your Product <span class="beta-badge">Beta</span></h1>
        <p>Execute your product launch with precision and maximum impact</p>
    </div>

    <!-- Progress Indicator -->
    <div class="phase-progress-indicator">
        <div class="progress-steps">
            <div class="step <?php echo ($current_phase == 'market_clarity') ? 'active' : (in_array('market_clarity', $completed_phases) ? 'completed' : ''); ?>">
                <span class="step-number"><?php echo in_array('market_clarity', $completed_phases) ? '✓' : '1'; ?></span>
                <span class="step-label">Market Clarity</span>
            </div>
            <div class="step <?php echo ($current_phase == 'create_offer') ? 'active' : (in_array('create_offer', $completed_phases) ? 'completed' : ''); ?>">
                <span class="step-number"><?php echo in_array('create_offer', $completed_phases) ? '✓' : '2'; ?></span>
                <span class="step-label">Create Offer</span>
            </div>
            <div class="step <?php echo ($current_phase == 'create_service') ? 'active' : (in_array('create_service', $completed_phases) ? 'completed' : ''); ?>">
                <span class="step-number"><?php echo in_array('create_service', $completed_phases) ? '✓' : '3'; ?></span>
                <span class="step-label">Create Service</span>
            </div>
            <div class="step <?php echo ($current_phase == 'build_funnel') ? 'active' : (in_array('build_funnel', $completed_phases) ? 'completed' : ''); ?>">
                <span class="step-number"><?php echo in_array('build_funnel', $completed_phases) ? '✓' : '4'; ?></span>
                <span class="step-label">Build Funnel</span>
            </div>
            <div class="step <?php echo ($current_phase == 'email_sequences') ? 'active' : (in_array('email_sequences', $completed_phases) ? 'completed' : ''); ?>">
                <span class="step-number"><?php echo in_array('email_sequences', $completed_phases) ? '✓' : '5'; ?></span>
                <span class="step-label">Email Sequences</span>
            </div>
            <div class="step <?php echo ($current_phase == 'organic_posts') ? 'active' : (in_array('organic_posts', $completed_phases) ? 'completed' : ''); ?>">
                <span class="step-number"><?php echo in_array('organic_posts', $completed_phases) ? '✓' : '6'; ?></span>
                <span class="step-label">Organic Posts</span>
            </div>
            <div class="step <?php echo ($current_phase == 'facebook_ads') ? 'active' : (in_array('facebook_ads', $completed_phases) ? 'completed' : ''); ?>">
                <span class="step-number"><?php echo in_array('facebook_ads', $completed_phases) ? '✓' : '7'; ?></span>
                <span class="step-label">Facebook Ads</span>
            </div>
            <div class="step <?php echo ($current_phase == 'launch') ? 'active' : (in_array('launch', $completed_phases) ? 'completed' : ''); ?>">
                <span class="step-number"><?php echo in_array('launch', $completed_phases) ? '✓' : '8'; ?></span>
                <span class="step-label">Launch</span>
            </div>
        </div>
    </div>

    <!-- AI Coach Section -->
    <div class="ai-coach-section">
        <div class="coach-avatar">
            <span class="dashicons dashicons-businesswoman"></span>
        </div>
        <h3>Get Expert Guidance</h3>
        <p>Ready to launch? Our AI coach can help you plan your launch timeline, coordinate all elements, and prepare for contingencies to ensure a successful launch.</p>
        <button class="start-ai-coaching" data-phase="launch">
            <span class="dashicons dashicons-format-chat"></span>
            Start AI Launch Strategy Session
        </button>
    </div>

    <!-- Launch Form -->
    <div class="phase-content">
        <form method="post" class="launch-form">
            <?php wp_nonce_field('launch_progress'); ?>
            
            <div class="form-section">
                <h2><span class="dashicons dashicons-chart-line"></span> Launch Strategy</h2>
                <p class="section-description">Define your overall launch strategy and approach.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="launch_strategy">Launch Strategy & Approach</label>
                        </th>
                        <td>
                            <textarea 
                                name="launch_strategy" 
                                id="launch_strategy" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="launch_strategy"
                                placeholder="What's your launch strategy? Soft launch vs big bang? Target date? Key success metrics?"
                            ><?php echo esc_textarea($progress_data['launch_strategy'] ?? ''); ?></textarea>
                            <p class="description">Consider your launch type, duration, target audience activation, and success criteria.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-calendar-alt"></span> Launch Timeline</h2>
                <p class="section-description">Create a detailed timeline for your launch execution.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="launch_timeline">Detailed Launch Timeline</label>
                        </th>
                        <td>
                            <textarea 
                                name="launch_timeline" 
                                id="launch_timeline" 
                                rows="8" 
                                class="large-text ai-fillable"
                                data-field="launch_timeline"
                                placeholder="Plan your timeline: 30 days before, 7 days before, launch day, post-launch follow-up..."
                            ><?php echo esc_textarea($progress_data['launch_timeline'] ?? ''); ?></textarea>
                            <p class="description">Include pre-launch preparation, launch day activities, and post-launch follow-up.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-yes-alt"></span> Pre-Launch Checklist</h2>
                <p class="section-description">Ensure everything is ready before you launch.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="pre_launch_checklist">Pre-Launch Checklist</label>
                        </th>
                        <td>
                            <textarea 
                                name="pre_launch_checklist" 
                                id="pre_launch_checklist" 
                                rows="8" 
                                class="large-text ai-fillable"
                                data-field="pre_launch_checklist"
                                placeholder="List everything that needs to be ready: website, payment system, email sequences, ads, content, legal..."
                            ><?php echo esc_textarea($progress_data['pre_launch_checklist'] ?? ''); ?></textarea>
                            <p class="description">Include technical setup, content preparation, legal requirements, and team coordination.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-clock"></span> Launch Day Plan</h2>
                <p class="section-description">Plan your launch day execution in detail.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="launch_day_plan">Launch Day Execution Plan</label>
                        </th>
                        <td>
                            <textarea 
                                name="launch_day_plan" 
                                id="launch_day_plan" 
                                rows="6" 
                                class="large-text ai-fillable"
                                data-field="launch_day_plan"
                                placeholder="Hour-by-hour launch day plan: emails, social posts, ad activation, monitoring, team roles..."
                            ><?php echo esc_textarea($progress_data['launch_day_plan'] ?? ''); ?></textarea>
                            <p class="description">Include timing for emails, social posts, ad campaigns, and monitoring responsibilities.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-megaphone"></span> Communication Plan</h2>
                <p class="section-description">Plan how you'll communicate throughout the launch.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="communication_plan">Launch Communication Strategy</label>
                        </th>
                        <td>
                            <textarea 
                                name="communication_plan" 
                                id="communication_plan" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="communication_plan"
                                placeholder="How will you announce and promote the launch? Email, social, PR, partnerships, influencers..."
                            ><?php echo esc_textarea($progress_data['communication_plan'] ?? ''); ?></textarea>
                            <p class="description">Include all communication channels and key messages for different audiences.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-shield-alt"></span> Contingency Plan</h2>
                <p class="section-description">Prepare for potential issues and how to handle them.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="contingency_plan">Risk Management & Contingencies</label>
                        </th>
                        <td>
                            <textarea 
                                name="contingency_plan" 
                                id="contingency_plan" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="contingency_plan"
                                placeholder="What could go wrong? Technical issues, low response, high demand, negative feedback? How will you handle each?"
                            ><?php echo esc_textarea($progress_data['contingency_plan'] ?? ''); ?></textarea>
                            <p class="description">Plan for common launch issues: technical problems, unexpected demand, or poor initial response.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-analytics"></span> Post-Launch Analysis</h2>
                <p class="section-description">Plan how you'll measure success and iterate.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="post_launch_analysis">Success Metrics & Analysis Plan</label>
                        </th>
                        <td>
                            <textarea 
                                name="post_launch_analysis" 
                                id="post_launch_analysis" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="post_launch_analysis"
                                placeholder="How will you measure success? Key metrics, analysis timeline, improvement areas, scaling plans..."
                            ><?php echo esc_textarea($progress_data['post_launch_analysis'] ?? ''); ?></textarea>
                            <p class="description">Include KPIs, review schedule, and plans for iteration and scaling.</p>
                        </td>
                    </tr>
                </table>
            </div>

            
        
            
            
            <?php $current_phase = 'launch'; include dirname(__FILE__) . '/clean_navigation_fix.php'; ?>
</form>
    </div>

    <!-- Launch Tips -->
    <div class="tips-section">
        <h3><span class="dashicons dashicons-lightbulb"></span> Launch Success Tips</h3>
        <div class="tips-grid">
            <div class="tip-card">
                <h4>Test Everything Twice</h4>
                <p>Check all systems, links, and processes before launch. Have someone else test the entire customer journey.</p>
            </div>
            <div class="tip-card">
                <h4>Monitor Closely</h4>
                <p>Watch metrics, social mentions, and customer feedback closely during launch. Be ready to respond quickly.</p>
            </div>
            <div class="tip-card">
                <h4>Celebrate Milestones</h4>
                <p>Share progress and celebrate achievements during launch. This builds momentum and engages your audience.</p>
            </div>
            <div class="tip-card">
                <h4>Learn and Iterate</h4>
                <p>Document what works and what doesn't. Use launch insights to improve your next product or campaign.</p>
            </div>
        </div>
    </div>

    <!-- Completion Status -->
    <?php if ($is_completed) : ?>
    <div class="completion-status">
        <div class="status-icon">
            <span class="dashicons dashicons-yes-alt"></span>
        </div>
        <div class="status-content">
            <h3>Launch Plan Complete!</h3>
            <p>Congratulations! You've completed your comprehensive product launch plan. You're now ready to execute your launch with confidence.</p>
            <p><strong>Completed:</strong> <?php echo date('F j, Y', strtotime($progress_data['completed_at'])); ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.launch-form .form-section {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 25px;
}

.launch-form .form-section h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #1f2937;
    margin-bottom: 10px;
    font-size: 20px;
}

.ai-fillable {
    border: 2px solid #e5e7eb !important;
    transition: border-color 0.3s ease;
}

.ai-fillable.ai-filled {
    border-color: #10b981 !important;
    background-color: #f0fdf4 !important;
}

.section-description {
    color: #6b7280;
    margin-bottom: 20px;
    font-style: italic;
}

.form-actions {
    text-align: center;
    padding: 30px;
    background: #f8fafc;
    border-radius: 8px;
    margin-top: 30px;
}

.form-actions .button {
    margin: 0 10px;
}

.phase-progress-indicator {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.progress-steps {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    opacity: 0.5;
    transition: opacity 0.3s ease;
    min-width: 70px;
}

.step.active,
.step.completed {
    opacity: 1;
}

.step-number {
    width: 32px;
    height: 32px;
    background: #e5e7eb;
    color: #6b7280;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-bottom: 6px;
    font-size: 11px;
}

.step.active .step-number {
    background: #3b82f6;
    color: white;
}

.step.completed .step-number {
    background: #10b981;
    color: white;
}

.step-label {
    font-size: 9px;
    text-align: center;
    color: #6b7280;
    line-height: 1.1;
    max-width: 70px;
}

.step.active .step-label,
.step.completed .step-label {
    color: #1f2937;
    font-weight: 600;
}

.tips-section {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    border: 1px solid #f59e0b;
    border-radius: 8px;
    padding: 30px;
    margin-top: 30px;
}

.tips-section h3 {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #92400e;
    margin-bottom: 20px;
}

.tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.tip-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #f59e0b;
}

.tip-card h4 {
    margin: 0 0 10px 0;
    color: #92400e;
}

.tip-card p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
    line-height: 1.5;
}

.completion-status {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    border: 1px solid #10b981;
    border-radius: 8px;
    padding: 30px;
    margin-top: 30px;
    display: flex;
    align-items: center;
    gap: 20px;
}

.status-icon {
    width: 60px;
    height: 60px;
    background: #10b981;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.status-content h3 {
    margin: 0 0 10px 0;
    color: #047857;
}

.status-content p {
    margin: 5px 0;
    color: #065f46;
}

@media (max-width: 768px) {
    .progress-steps {
        gap: 10px;
    }
    
    .step {
        min-width: 60px;
    }
    
    .step-number {
        width: 28px;
        height: 28px;
        font-size: 10px;
    }
    
    .step-label {
        font-size: 8px;
        max-width: 60px;
    }
    
    .tips-grid {
        grid-template-columns: 1fr;
    }
    
    .completion-status {
        flex-direction: column;
        text-align: center;
    }
    
    .form-actions .button {
        display: block;
        margin: 10px auto;
        width: 100%;
        max-width: 300px;
    }
}
</style>