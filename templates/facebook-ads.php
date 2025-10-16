<?php
/**
 * Facebook Ads Template
 * File: templates/facebook-ads.php
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$site_id = get_current_blog_id();

// Get saved progress for this phase
$progress_data = get_user_meta($user_id, 'product_launch_progress_facebook_ads_' . $site_id, true);
$progress_data = $progress_data ? json_decode($progress_data, true) : array();

// Handle form submission
if (isset($_POST['save_progress']) && wp_verify_nonce($_POST['_wpnonce'], 'facebook_ads_progress')) {
    $form_data = array(
        'campaign_strategy' => sanitize_textarea_field($_POST['campaign_strategy'] ?? ''),
        'audience_targeting' => sanitize_textarea_field($_POST['audience_targeting'] ?? ''),
        'ad_creative' => sanitize_textarea_field($_POST['ad_creative'] ?? ''),
        'ad_copy' => sanitize_textarea_field($_POST['ad_copy'] ?? ''),
        'budget_bidding' => sanitize_textarea_field($_POST['budget_bidding'] ?? ''),
        'campaign_structure' => sanitize_textarea_field($_POST['campaign_structure'] ?? ''),
        'tracking_optimization' => sanitize_textarea_field($_POST['tracking_optimization'] ?? ''),
        'completed_at' => current_time('mysql')
    );
    
    update_user_meta($user_id, 'product_launch_progress_facebook_ads_' . $site_id, json_encode($form_data));
    
    echo '<div class="notice notice-success is-dismissible"><p>Facebook Ads progress saved successfully!</p></div>';
    $progress_data = $form_data;
}

$is_completed = !empty($progress_data['completed_at']);
?>
<?php
// --- Auto-persist & load progress for phase: facebook_ads ---
$user_id = get_current_user_id();
$site_id = get_current_blog_id();
if (function_exists('pl_handle_phase_save_auto')) {
    $progress_data_saved = pl_handle_phase_save_auto('facebook_ads');
    if ($progress_data_saved && is_array($progress_data_saved)) {
        $progress_data = $progress_data_saved;
    }
}
if (!isset($progress_data) || !is_array($progress_data) || empty($progress_data)) {
    $saved_raw = get_user_meta($user_id, 'product_launch_progress_facebook_ads_' . $site_id, true);
    if ($saved_raw) {
        $decoded = json_decode($saved_raw, true);
        if (is_array($decoded)) $progress_data = $decoded;
    }
}
?>

<?php
$current_phase = 'facebook_ads';

$completed_phases = array();
$all_phases = array('market_clarity', 'create_offer', 'create_service', 'build_funnel', 'email_sequences', 'organic_posts', 'facebook_ads', 'launch');
foreach ($all_phases as $phase) {
    $phase_progress = get_user_meta($user_id, 'product_launch_progress_' . $phase . '_' . $site_id, true);
    if (!empty($phase_progress)) { $completed_phases[] = $phase; }
}
?>


<div class="wrap product-launch-phase-container">
    <div class="phase-header">
    <h1>Creating Facebook Ads <span class="beta-badge">Beta</span></h1>

        <div class="phase-icon">
            <span class="dashicons dashicons-megaphone"></span>
        </div>
        
        <p>Design high-converting ad campaigns that reach your ideal customers and drive profitable conversions</p>
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



    <!-- Progress Indicator -->
    

    <!-- AI Coach Section -->
    <div class="ai-coach-section">
        <div class="coach-avatar">
            <span class="dashicons dashicons-businesswoman"></span>
        </div>
        <h3>Get Expert Guidance</h3>
        <p>Need help creating profitable Facebook ads? Our AI coach can help you with targeting, ad copy, creative concepts, and campaign optimization strategies.</p>
        <button class="start-ai-coaching" data-phase="facebook_ads">
            <span class="dashicons dashicons-format-chat"></span>
            Start AI Advertising Session
        </button>
    </div>

    <!-- Facebook Ads Form -->
    <div class="phase-content">
        <form method="post" class="facebook-ads-form">
            <?php wp_nonce_field('facebook_ads_progress'); ?>
            
            <div class="form-section">
                <h2><span class="dashicons dashicons-chart-line"></span> Campaign Strategy</h2>
                <p class="section-description">Define your overall Facebook advertising strategy and objectives.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="campaign_strategy">Advertising Strategy & Goals</label>
                        </th>
                        <td>
                            <textarea 
                                name="campaign_strategy" 
                                id="campaign_strategy" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="campaign_strategy"
                                placeholder="What are your Facebook advertising goals? Awareness, traffic, leads, conversions? What's your overall strategy?"
                            ><?php echo esc_textarea($progress_data['campaign_strategy'] ?? ''); ?></textarea>
                            <p class="description">Consider your budget, timeline, and how ads fit into your overall marketing funnel.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-groups"></span> Audience Targeting</h2>
                <p class="section-description">Research and define your target audiences for maximum ROI.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="audience_targeting">Target Audience Research</label>
                        </th>
                        <td>
                            <textarea 
                                name="audience_targeting" 
                                id="audience_targeting" 
                                rows="6" 
                                class="large-text ai-fillable"
                                data-field="audience_targeting"
                                placeholder="Define your target audiences: demographics, interests, behaviors, custom audiences, lookalike audiences..."
                            ><?php echo esc_textarea($progress_data['audience_targeting'] ?? ''); ?></textarea>
                            <p class="description">Include primary and secondary audiences, exclusions, and any custom/lookalike audience strategies.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-format-image"></span> Ad Creative</h2>
                <p class="section-description">Plan your visual content and creative concepts.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ad_creative">Creative Concepts & Visuals</label>
                        </th>
                        <td>
                            <textarea 
                                name="ad_creative" 
                                id="ad_creative" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="ad_creative"
                                placeholder="Describe your ad creative concepts: images, videos, carousels. What visual style and messaging will you use?"
                            ><?php echo esc_textarea($progress_data['ad_creative'] ?? ''); ?></textarea>
                            <p class="description">Consider different formats: single image, video, carousel, collection. Think about mobile-first design.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-edit"></span> Ad Copy</h2>
                <p class="section-description">Write compelling ad copy that converts.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ad_copy">Headlines & Ad Copy</label>
                        </th>
                        <td>
                            <textarea 
                                name="ad_copy" 
                                id="ad_copy" 
                                rows="8" 
                                class="large-text ai-fillable"
                                data-field="ad_copy"
                                placeholder="Write your ad copy: headlines, primary text, descriptions, call-to-action buttons..."
                            ><?php echo esc_textarea($progress_data['ad_copy'] ?? ''); ?></textarea>
                            <p class="description">Include multiple headline and copy variations for testing. Focus on benefits and urgency.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-money"></span> Budget & Bidding</h2>
                <p class="section-description">Set your budget and bidding strategy for optimal performance.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="budget_bidding">Budget & Bidding Strategy</label>
                        </th>
                        <td>
                            <textarea 
                                name="budget_bidding" 
                                id="budget_bidding" 
                                rows="4" 
                                class="large-text ai-fillable"
                                data-field="budget_bidding"
                                placeholder="What's your daily/lifetime budget? Bidding strategy? Target cost per result?"
                            ><?php echo esc_textarea($progress_data['budget_bidding'] ?? ''); ?></textarea>
                            <p class="description">Consider your total budget, daily spend, and target cost per acquisition/conversion.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-networking"></span> Campaign Structure</h2>
                <p class="section-description">Organize your campaigns, ad sets, and ads for optimal performance.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="campaign_structure">Campaign Organization</label>
                        </th>
                        <td>
                            <textarea 
                                name="campaign_structure" 
                                id="campaign_structure" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="campaign_structure"
                                placeholder="How will you structure your campaigns? Different audiences, objectives, creative tests..."
                            ><?php echo esc_textarea($progress_data['campaign_structure'] ?? ''); ?></textarea>
                            <p class="description">Plan your campaign hierarchy: objectives → audiences → creative variations.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-analytics"></span> Tracking & Optimization</h2>
                <p class="section-description">Set up tracking and plan your optimization strategy.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="tracking_optimization">Tracking Setup & Optimization Plan</label>
                        </th>
                        <td>
                            <textarea 
                                name="tracking_optimization" 
                                id="tracking_optimization" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="tracking_optimization"
                                placeholder="How will you track conversions? Facebook Pixel, UTM parameters? What metrics will you optimize for?"
                            ><?php echo esc_textarea($progress_data['tracking_optimization'] ?? ''); ?></textarea>
                            <p class="description">Include pixel setup, conversion events, attribution windows, and optimization strategies.</p>
                        </td>
                    </tr>
                </table>
            </div>

            
        
            
            
            <?php $current_phase = 'facebook_ads'; include dirname(__FILE__) . '/clean_navigation_fix.php'; ?>
</form>
    </div>

    <!-- Facebook Ads Tips -->
    <div class="tips-section">
        <h3><span class="dashicons dashicons-lightbulb"></span> Facebook Advertising Tips</h3>
        <div class="tips-grid">
            <div class="tip-card">
                <h4>Start Small & Test</h4>
                <p>Begin with small budgets and test different audiences, creative, and copy. Scale what works.</p>
            </div>
            <div class="tip-card">
                <h4>Mobile-First Design</h4>
                <p>Most users see ads on mobile. Design creative and copy that works perfectly on small screens.</p>
            </div>
            <div class="tip-card">
                <h4>Focus on Value</h4>
                <p>Lead with benefits and value, not features. Show the transformation your product provides.</p>
            </div>
            <div class="tip-card">
                <h4>Retarget Engaged Users</h4>
                <p>Create custom audiences from website visitors, video viewers, and engaged users for higher conversion rates.</p>
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
            <h3>Facebook Ads Strategy Complete!</h3>
            <p>Excellent! You've planned your Facebook advertising campaigns. Now you're ready for the final phase: executing your launch!</p>
            <p><strong>Completed:</strong> <?php echo date('F j, Y', strtotime($progress_data['completed_at'])); ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* Reuse styles from previous templates */
.facebook-ads-form .form-section {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 25px;
}

.facebook-ads-form .form-section h2 {
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
