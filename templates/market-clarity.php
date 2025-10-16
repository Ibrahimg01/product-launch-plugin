<?php
/**
 * Market Clarity Template
 * File: templates/market-clarity.php
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$site_id = get_current_blog_id();

// Get saved progress for this phase
$progress_data = get_user_meta($user_id, 'product_launch_progress_market_clarity_' . $site_id, true);
$progress_data = $progress_data ? json_decode($progress_data, true) : array();

// Define current phase and get completed phases
$current_phase = 'market_clarity';
$completed_phases = array();

// Check which phases are completed
$all_phases = ['market_clarity', 'create_offer', 'create_service', 'build_funnel', 'email_sequences', 'organic_posts', 'facebook_ads', 'launch'];
foreach ($all_phases as $phase) {
    $phase_progress = get_user_meta($user_id, 'product_launch_progress_' . $phase . '_' . $site_id, true);
    if (!empty($phase_progress)) {
        $completed_phases[] = $phase;
    }
}

// Handle form submission
if (isset($_POST['save_progress']) && wp_verify_nonce($_POST['_wpnonce'], 'market_clarity_progress')) {
    $form_data = array(
        'target_audience' => sanitize_textarea_field($_POST['target_audience'] ?? ''),
        'pain_points' => sanitize_textarea_field($_POST['pain_points'] ?? ''),
        'value_proposition' => sanitize_textarea_field($_POST['value_proposition'] ?? ''),
        'market_size' => sanitize_textarea_field($_POST['market_size'] ?? ''),
        'competitors' => sanitize_textarea_field($_POST['competitors'] ?? ''),
        'positioning' => sanitize_textarea_field($_POST['positioning'] ?? ''),
        'completed_at' => current_time('mysql')
    );
    
    update_user_meta($user_id, 'product_launch_progress_market_clarity_' . $site_id, json_encode($form_data));
    
    echo '<div class="notice notice-success is-dismissible"><p>Market clarity progress saved successfully!</p></div>';
    $progress_data = $form_data;
}

$is_completed = !empty($progress_data['completed_at']);
?>
<?php
// --- Auto-persist & load progress for phase: market_clarity ---
$user_id = get_current_user_id();
$site_id = get_current_blog_id();
if (function_exists('pl_handle_phase_save_auto')) {
    $progress_data_saved = pl_handle_phase_save_auto('market_clarity');
    if ($progress_data_saved && is_array($progress_data_saved)) {
        $progress_data = $progress_data_saved;
    }
}
if (!isset($progress_data) || !is_array($progress_data) || empty($progress_data)) {
    $saved_raw = get_user_meta($user_id, 'product_launch_progress_market_clarity_' . $site_id, true);
    if ($saved_raw) {
        $decoded = json_decode($saved_raw, true);
        if (is_array($decoded)) $progress_data = $decoded;
    }
}
?>


<div class="wrap product-launch-phase-container">
    <div class="phase-header">
    <h1>Getting Market Clarity <span class="beta-badge">Beta</span></h1>

        <div class="phase-icon">
            <span class="dashicons dashicons-search"></span>
        </div>
        <p>Identify your target market and positioning to build a foundation for your successful product launch</p>
    </div>

    <!-- Progress Indicator -->
    <div class="phase-progress-indicator">
        <div class="progress-steps">
            <div class="step <?php echo ($current_phase == 'market_clarity') ? 'active' : (in_array('market_clarity', $completed_phases) ? 'completed' : ''); ?>">
                <span class="step-number">1</span>
                <span class="step-label">Market Clarity</span>
            </div>
            <div class="step <?php echo ($current_phase == 'create_offer') ? 'active' : (in_array('create_offer', $completed_phases) ? 'completed' : ''); ?>">
                <span class="step-number">2</span>
                <span class="step-label">Create Offer</span>
            </div>
            <div class="step <?php echo ($current_phase == 'create_service') ? 'active' : (in_array('create_service', $completed_phases) ? 'completed' : ''); ?>">
                <span class="step-number">3</span>
                <span class="step-label">Create Service</span>
            </div>
            <div class="step <?php echo ($current_phase == 'build_funnel') ? 'active' : (in_array('build_funnel', $completed_phases) ? 'completed' : ''); ?>">
                <span class="step-number">4</span>
                <span class="step-label">Build Funnel</span>
            </div>
            <div class="step <?php echo ($current_phase == 'email_sequences') ? 'active' : (in_array('email_sequences', $completed_phases) ? 'completed' : ''); ?>">
                <span class="step-number">5</span>
                <span class="step-label">Email Sequences</span>
            </div>
            <div class="step <?php echo ($current_phase == 'organic_posts') ? 'active' : (in_array('organic_posts', $completed_phases) ? 'completed' : ''); ?>">
                <span class="step-number">6</span>
                <span class="step-label">Organic Posts</span>
            </div>
            <div class="step <?php echo ($current_phase == 'facebook_ads') ? 'active' : (in_array('facebook_ads', $completed_phases) ? 'completed' : ''); ?>">
                <span class="step-number">7</span>
                <span class="step-label">Facebook Ads</span>
            </div>
            <div class="step <?php echo ($current_phase == 'launch') ? 'active' : (in_array('launch', $completed_phases) ? 'completed' : ''); ?>">
                <span class="step-number">8</span>
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
        <p>Not sure where to start? Our AI coach can help you identify your target market, understand your competition, and clarify your positioning.</p>
        <button class="start-ai-coaching" data-phase="market_clarity">
            <span class="dashicons dashicons-format-chat"></span>
            Start AI Market Research Session
        </button>
    </div>

    <!-- Market Clarity Form -->
    <div class="phase-content">
        <form method="post" class="market-clarity-form">
            <?php wp_nonce_field('market_clarity_progress'); ?>
            
            <div class="form-section">
                <h2><span class="dashicons dashicons-groups"></span> Target Audience</h2>
                <p class="section-description">Define your ideal customer with specific demographics, psychographics, and behaviors.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="target_audience">Ideal Customer Description</label>
                        </th>
                        <td>
                            <textarea 
                                name="target_audience" 
                                id="target_audience" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="target_audience"
                                placeholder="Describe your ideal customer: age, income, interests, challenges, where they spend time online..."
                            ><?php echo esc_textarea($progress_data['target_audience'] ?? ''); ?></textarea>
                            <p class="description">Be as specific as possible. Include demographics, interests, pain points, and where they consume content.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-warning"></span> Pain Points & Challenges</h2>
                <p class="section-description">What problems does your target audience face that your product solves?</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="pain_points">Key Pain Points</label>
                        </th>
                        <td>
                            <textarea 
                                name="pain_points" 
                                id="pain_points" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="pain_points"
                                placeholder="List the main problems, frustrations, or challenges your target audience experiences..."
                            ><?php echo esc_textarea($progress_data['pain_points'] ?? ''); ?></textarea>
                            <p class="description">Focus on emotional and practical problems that keep them up at night or frustrate them daily.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-star-filled"></span> Value Proposition</h2>
                <p class="section-description">How does your product uniquely solve their problems better than alternatives?</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="value_proposition">Unique Value Proposition</label>
                        </th>
                        <td>
                            <textarea 
                                name="value_proposition" 
                                id="value_proposition" 
                                rows="4" 
                                class="large-text ai-fillable"
                                data-field="value_proposition"
                                placeholder="What makes your solution unique and valuable? What transformation do you provide?"
                            ><?php echo esc_textarea($progress_data['value_proposition'] ?? ''); ?></textarea>
                            <p class="description">Focus on the transformation and outcomes you provide, not just features.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-chart-area"></span> Market Analysis</h2>
                <p class="section-description">Research your market size, competition, and positioning opportunities.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="market_size">Market Size & Opportunity</label>
                        </th>
                        <td>
                            <textarea 
                                name="market_size" 
                                id="market_size" 
                                rows="4" 
                                class="large-text ai-fillable"
                                data-field="market_size"
                                placeholder="How big is your target market? What's the demand for your solution?"
                            ><?php echo esc_textarea($progress_data['market_size'] ?? ''); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="competitors">Competitive Landscape</label>
                        </th>
                        <td>
                            <textarea 
                                name="competitors" 
                                id="competitors" 
                                rows="4" 
                                class="large-text ai-fillable"
                                data-field="competitors"
                                placeholder="Who are your main competitors? What are their strengths and weaknesses?"
                            ><?php echo esc_textarea($progress_data['competitors'] ?? ''); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="positioning">Market Positioning</label>
                        </th>
                        <td>
                            <textarea 
                                name="positioning" 
                                id="positioning" 
                                rows="4" 
                                class="large-text ai-fillable"
                                data-field="positioning"
                                placeholder="How will you position yourself differently? What's your unique angle?"
                            ><?php echo esc_textarea($progress_data['positioning'] ?? ''); ?></textarea>
                        </td>
                    </tr>
                </table>
            </div>

            
        
            
            
            <?php $current_phase = 'market_clarity'; include dirname(__FILE__) . '/clean_navigation_fix.php'; ?>
</form>
    </div>

    <!-- Market Research Tips -->
    <div class="tips-section">
        <h3><span class="dashicons dashicons-lightbulb"></span> Market Research Tips</h3>
        <div class="tips-grid">
            <div class="tip-card">
                <h4>Survey Your Audience</h4>
                <p>Send surveys to your existing audience or potential customers to validate your assumptions about their pain points and desires.</p>
            </div>
            <div class="tip-card">
                <h4>Social Media Research</h4>
                <p>Join Facebook groups, Reddit communities, and forums where your target audience hangs out. Listen to their conversations.</p>
            </div>
            <div class="tip-card">
                <h4>Competitor Analysis</h4>
                <p>Study your competitors' marketing messages, customer reviews, and social media to understand gaps in the market.</p>
            </div>
            <div class="tip-card">
                <h4>One-on-One Interviews</h4>
                <p>Conduct interviews with 5-10 people from your target market to get deep insights into their needs and frustrations.</p>
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
            <h3>Market Clarity Complete!</h3>
            <p>Great work! You've completed your market research. You can now move on to creating your irresistible offer.</p>
            <p><strong>Completed:</strong> <?php echo date('F j, Y', strtotime($progress_data['completed_at'])); ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.market-clarity-form .form-section {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 25px;
}

.market-clarity-form .form-section h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #1f2937;
    margin-bottom: 10px;
    font-size: 20px;
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
    gap: 20px;
    flex-wrap: wrap;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    opacity: 0.5;
    transition: opacity 0.3s ease;
    min-width: 80px;
}

.step.active,
.step.completed {
    opacity: 1;
}

.step-number {
    width: 35px;
    height: 35px;
    background: #e5e7eb;
    color: #6b7280;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 12px;
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
    font-size: 10px;
    text-align: center;
    color: #6b7280;
    line-height: 1.2;
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

.ai-fillable {
    border: 2px solid #e5e7eb !important;
    transition: border-color 0.3s ease;
}

.ai-fillable.ai-filled {
    border-color: #10b981 !important;
    background-color: #f0fdf4 !important;
}

@media (max-width: 768px) {
    .progress-steps {
        gap: 15px;
    }
    
    .step {
        min-width: 70px;
    }
    
    .step-number {
        width: 30px;
        height: 30px;
        font-size: 10px;
    }
    
    .step-label {
        font-size: 9px;
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
