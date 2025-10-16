<?php
/**
 * Email Sequences Template - FIXED VERSION
 * File: templates/email-sequences.php
 * Aligned with other phases for consistency
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$site_id = get_current_blog_id();

// Get saved progress for this phase
$progress_data = get_user_meta($user_id, 'product_launch_progress_email_sequences_' . $site_id, true);
$progress_data = $progress_data ? json_decode($progress_data, true) : array();

// Handle form submission - FIXED to match other phases
if (isset($_POST['save_progress']) && wp_verify_nonce($_POST['_wpnonce'], 'email_sequences_progress')) {
    $form_data = array(
        'email_strategy' => sanitize_textarea_field($_POST['email_strategy'] ?? ''),
        'email_subject_lines' => sanitize_textarea_field($_POST['email_subject_lines'] ?? ''),
        'email_sequence_outline' => sanitize_textarea_field($_POST['email_sequence_outline'] ?? ''),
        'email_cta' => sanitize_textarea_field($_POST['email_cta'] ?? ''),
        'automation_setup' => sanitize_textarea_field($_POST['automation_setup'] ?? ''),
        'personalization' => sanitize_textarea_field($_POST['personalization'] ?? ''),
        'testing_optimization' => sanitize_textarea_field($_POST['testing_optimization'] ?? ''),
        'completed_at' => current_time('mysql')
    );
    
    update_user_meta($user_id, 'product_launch_progress_email_sequences_' . $site_id, json_encode($form_data));
    
    echo '<div class="notice notice-success is-dismissible"><p>Email sequences progress saved successfully!</p></div>';
    $progress_data = $form_data;
}

$is_completed = !empty($progress_data['completed_at']);
?>

<?php
// --- Auto-persist & load progress for phase: email_sequences ---
$user_id = get_current_user_id();
$site_id = get_current_blog_id();
if (function_exists('pl_handle_phase_save_auto')) {
    $progress_data_saved = pl_handle_phase_save_auto('email_sequences');
    if ($progress_data_saved && is_array($progress_data_saved)) {
        $progress_data = $progress_data_saved;
    }
}
if (!isset($progress_data) || !is_array($progress_data) || empty($progress_data)) {
    $saved_raw = get_user_meta($user_id, 'product_launch_progress_email_sequences_' . $site_id, true);
    if ($saved_raw) {
        $decoded = json_decode($saved_raw, true);
        if (is_array($decoded)) $progress_data = $decoded;
    }
}
?>

<?php
$current_phase = 'email_sequences';

$completed_phases = array();
$all_phases = array('market_clarity', 'create_offer', 'create_service', 'build_funnel', 'email_sequences', 'organic_posts', 'facebook_ads', 'launch');
foreach ($all_phases as $phase) {
    $phase_progress = get_user_meta($user_id, 'product_launch_progress_' . $phase . '_' . $site_id, true);
    if (!empty($phase_progress)) { $completed_phases[] = $phase; }
}
?>

<div class="wrap product-launch-phase-container">
    <div class="phase-header">
        <h1>Writing Email Sequences <span class="beta-badge">Beta</span></h1>
        <div class="phase-icon">
            <span class="dashicons dashicons-email-alt"></span>
        </div>
        <p>Craft compelling email campaigns that nurture leads and drive conversions throughout your launch</p>
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
        <p>Need help crafting high-converting email sequences? Our AI coach can help you write compelling subject lines, engaging content, and effective calls-to-action that build on your funnel strategy.</p>
        <button class="start-ai-coaching" data-phase="email_sequences">
            <span class="dashicons dashicons-format-chat"></span>
            Start AI Email Writing Session
        </button>
    </div>

    <!-- Email Sequences Form -->
    <div class="phase-content">
        <form method="post" class="email-sequences-form">
            <?php wp_nonce_field('email_sequences_progress'); ?>
            
            <div class="form-section">
                <h2><span class="dashicons dashicons-chart-line"></span> Email Strategy</h2>
                <p class="section-description">Define your overall email sequence strategy and objectives.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="email_strategy">Email Strategy & Overview</label>
                        </th>
                        <td>
                            <textarea 
                                name="email_strategy" 
                                id="email_strategy" 
                                rows="6" 
                                class="large-text ai-fillable"
                                data-field="email_strategy"
                                placeholder="What's your email sequence strategy? Welcome series, nurture sequence, sales sequence? How does it connect to your funnel?"
                            ><?php echo esc_textarea($progress_data['email_strategy'] ?? ''); ?></textarea>
                            <p class="description">Consider how your email sequences support your funnel and move prospects toward your offer.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-edit"></span> Subject Lines</h2>
                <p class="section-description">Create compelling subject lines that get your emails opened.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="email_subject_lines">Subject Line Strategy</label>
                        </th>
                        <td>
                            <textarea 
                                name="email_subject_lines" 
                                id="email_subject_lines" 
                                rows="6" 
                                class="large-text ai-fillable"
                                data-field="email_subject_lines"
                                placeholder="Brainstorm compelling subject lines for your sequence. Include variations for testing..."
                            ><?php echo esc_textarea($progress_data['email_subject_lines'] ?? ''); ?></textarea>
                            <p class="description">Focus on curiosity, benefit, and urgency. Avoid spam triggers and keep under 50 characters for mobile.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-list-view"></span> Sequence Outline</h2>
                <p class="section-description">Map out each email's purpose and content flow.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="email_sequence_outline">Email Sequence Structure</label>
                        </th>
                        <td>
                            <textarea 
                                name="email_sequence_outline" 
                                id="email_sequence_outline" 
                                rows="8" 
                                class="large-text ai-fillable"
                                data-field="email_sequence_outline"
                                placeholder="Plan each email: Email 1: Welcome & value delivery, Email 2: Story + problem, Email 3: Solution introduction..."
                            ><?php echo esc_textarea($progress_data['email_sequence_outline'] ?? ''); ?></textarea>
                            <p class="description">Give each email a clear purpose. Balance value delivery with sales messaging throughout the sequence.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-megaphone"></span> Calls-to-Action</h2>
                <p class="section-description">Design clear, compelling CTAs that drive action.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="email_cta">Call-to-Action Strategy</label>
                        </th>
                        <td>
                            <textarea 
                                name="email_cta" 
                                id="email_cta" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="email_cta"
                                placeholder="What specific actions do you want readers to take? How will you make CTAs compelling and clear?"
                            ><?php echo esc_textarea($progress_data['email_cta'] ?? ''); ?></textarea>
                            <p class="description">Use action-oriented language and align CTAs with the email's purpose and your funnel goals.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-admin-generic"></span> Automation Setup</h2>
                <p class="section-description">Plan your email automation and triggering system.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="automation_setup">Automation & Triggers</label>
                        </th>
                        <td>
                            <textarea 
                                name="automation_setup" 
                                id="automation_setup" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="automation_setup"
                                placeholder="How will you trigger and automate your sequences? Lead magnet signup, purchase behavior, engagement levels..."
                            ><?php echo esc_textarea($progress_data['automation_setup'] ?? ''); ?></textarea>
                            <p class="description">Consider behavioral triggers, time delays, and segmentation for more targeted messaging.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-groups"></span> Personalization</h2>
                <p class="section-description">Add personal touches that increase engagement and conversion.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="personalization">Personalization Strategy</label>
                        </th>
                        <td>
                            <textarea 
                                name="personalization" 
                                id="personalization" 
                                rows="4" 
                                class="large-text ai-fillable"
                                data-field="personalization"
                                placeholder="How will you personalize emails? Names, preferences, behavior, location, past purchases..."
                            ><?php echo esc_textarea($progress_data['personalization'] ?? ''); ?></textarea>
                            <p class="description">Beyond just names - consider purchase history, engagement level, and demographic data.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-chart-area"></span> Testing & Optimization</h2>
                <p class="section-description">Plan how you'll test and improve your email performance.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="testing_optimization">Testing & Analytics Plan</label>
                        </th>
                        <td>
                            <textarea 
                                name="testing_optimization" 
                                id="testing_optimization" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="testing_optimization"
                                placeholder="What will you test? Subject lines, send times, content, CTAs? How will you measure success and optimize?"
                            ><?php echo esc_textarea($progress_data['testing_optimization'] ?? ''); ?></textarea>
                            <p class="description">Focus on key metrics: open rates, click rates, conversion rates, and unsubscribe rates.</p>
                        </td>
                    </tr>
                </table>
            </div>

                        
        
            
            
            <?php $current_phase = 'email_sequences'; include dirname(__FILE__) . '/clean_navigation_fix.php'; ?>
</form>
    </div>

    <!-- Email Writing Tips -->
    <div class="tips-section">
        <h3><span class="dashicons dashicons-lightbulb"></span> Email Writing Tips</h3>
        <div class="tips-grid">
            <div class="tip-card">
                <h4>Mobile-First Design</h4>
                <p>Most emails are read on mobile. Keep subject lines under 50 characters and use short paragraphs with clear formatting.</p>
            </div>
            <div class="tip-card">
                <h4>One Goal per Email</h4>
                <p>Each email should have one primary purpose and one main CTA. Multiple goals confuse readers and reduce conversions.</p>
            </div>
            <div class="tip-card">
                <h4>Build Relationships First</h4>
                <p>Provide value before asking for sales. Use the 80/20 rule: 80% value, 20% promotion to build trust and engagement.</p>
            </div>
            <div class="tip-card">
                <h4>Test and Iterate</h4>
                <p>A/B test subject lines, send times, and content. Small improvements in open and click rates compound over time.</p>
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
            <h3>Email Sequences Complete!</h3>
            <p>Excellent! You've planned your email nurture and sales sequences. Next, let's create organic social media content to support your launch.</p>
            <p><strong>Completed:</strong> <?php echo date('F j, Y', strtotime($progress_data['completed_at'])); ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* Reuse consistent styles from other phases */
.email-sequences-form .form-section {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 25px;
}

.email-sequences-form .form-section h2 {
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
    gap: 40px;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    opacity: 0.5;
    transition: opacity 0.3s ease;
}

.step.active,
.step.completed {
    opacity: 1;
}

.step-number {
    width: 40px;
    height: 40px;
    background: #e5e7eb;
    color: #6b7280;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-bottom: 8px;
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
    font-size: 12px;
    text-align: center;
    color: #6b7280;
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
        flex-direction: column;
        gap: 20px;
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