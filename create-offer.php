<?php
/**
 * Create Offer Template
 * File: templates/create-offer.php
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$site_id = get_current_blog_id();

// Get saved progress for this phase
$progress_data = get_user_meta($user_id, 'product_launch_progress_create_offer_' . $site_id, true);
$progress_data = $progress_data ? json_decode($progress_data, true) : array();

// Handle form submission
if (isset($_POST['save_progress']) && wp_verify_nonce($_POST['_wpnonce'], 'create_offer_progress')) {
    $form_data = array(
        'main_offer' => sanitize_textarea_field($_POST['main_offer'] ?? ''),
        'value_proposition' => sanitize_textarea_field($_POST['value_proposition'] ?? ''),
        'pricing_strategy' => sanitize_textarea_field($_POST['pricing_strategy'] ?? ''),
        'bonuses' => sanitize_textarea_field($_POST['bonuses'] ?? ''),
        'guarantee' => sanitize_textarea_field($_POST['guarantee'] ?? ''),
        'urgency_scarcity' => sanitize_textarea_field($_POST['urgency_scarcity'] ?? ''),
        'objection_handling' => sanitize_textarea_field($_POST['objection_handling'] ?? ''),
        'completed_at' => current_time('mysql')
    );
    
    update_user_meta($user_id, 'product_launch_progress_create_offer_' . $site_id, json_encode($form_data));
    
    echo '<div class="notice notice-success is-dismissible"><p>Offer creation progress saved successfully!</p></div>';
    $progress_data = $form_data;
}

$is_completed = !empty($progress_data['completed_at']);
?>
<?php
// --- Auto-persist & load progress for phase: create_offer ---
$user_id = get_current_user_id();
$site_id = get_current_blog_id();
if (function_exists('pl_handle_phase_save_auto')) {
    $progress_data_saved = pl_handle_phase_save_auto('create_offer');
    if ($progress_data_saved && is_array($progress_data_saved)) {
        $progress_data = $progress_data_saved;
    }
}
if (!isset($progress_data) || !is_array($progress_data) || empty($progress_data)) {
    $saved_raw = get_user_meta($user_id, 'product_launch_progress_create_offer_' . $site_id, true);
    if ($saved_raw) {
        $decoded = json_decode($saved_raw, true);
        if (is_array($decoded)) $progress_data = $decoded;
    }
}
?>

<?php
$current_phase = 'create_offer';

$completed_phases = array();
$all_phases = array('market_clarity', 'create_offer', 'create_service', 'build_funnel', 'email_sequences', 'organic_posts', 'facebook_ads', 'launch');
foreach ($all_phases as $phase) {
    $phase_progress = get_user_meta($user_id, 'product_launch_progress_' . $phase . '_' . $site_id, true);
    if (!empty($phase_progress)) { $completed_phases[] = $phase; }
}
?>


<div class="wrap product-launch-phase-container">
    <div class="phase-header">
    <h1>Creating Your Offer <span class="beta-badge">Beta</span></h1>

        <div class="phase-icon">
            <span class="dashicons dashicons-products"></span>
        </div>
        
        <p>Design irresistible offers that your target market can't refuse</p>
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
        <p>Need help crafting your offer? Our AI coach can help you create compelling value propositions, pricing strategies, and irresistible bonuses.</p>
        <button class="start-ai-coaching" data-phase="create_offer">
            <span class="dashicons dashicons-format-chat"></span>
            Start AI Offer Creation Session
        </button>
    </div>

    <!-- Create Offer Form -->
    <div class="phase-content">
        <form method="post" class="offer-creation-form">
            <?php wp_nonce_field('create_offer_progress'); ?>
            
            <div class="form-section">
                <h2><span class="dashicons dashicons-star-filled"></span> Main Offer</h2>
                <p class="section-description">Define your core product or service offering clearly.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="main_offer">Core Offer Description</label>
                        </th>
                        <td>
                            <textarea 
                                name="main_offer" 
                                id="main_offer" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="main_offer"
                                placeholder="Describe your main product or service offering in detail..."
                            ><?php echo esc_textarea($progress_data['main_offer'] ?? ''); ?></textarea>
                            <p class="description">What exactly are you selling? Be specific about deliverables, outcomes, and format.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-megaphone"></span> Value Proposition</h2>
                <p class="section-description">Clearly communicate the unique value and transformation you provide.</p>
                
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
                                placeholder="What makes your offer unique and valuable? What transformation do you provide?"
                            ><?php echo esc_textarea($progress_data['value_proposition'] ?? ''); ?></textarea>
                            <p class="description">Focus on outcomes and benefits, not just features. What's the end result for your customers?</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-money-alt"></span> Pricing Strategy</h2>
                <p class="section-description">Determine your pricing model and justify your price point.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="pricing_strategy">Pricing Model & Justification</label>
                        </th>
                        <td>
                            <textarea 
                                name="pricing_strategy" 
                                id="pricing_strategy" 
                                rows="4" 
                                class="large-text ai-fillable"
                                data-field="pricing_strategy"
                                placeholder="What's your price point and why? Include payment options, tiers, etc."
                            ><?php echo esc_textarea($progress_data['pricing_strategy'] ?? ''); ?></textarea>
                            <p class="description">Consider one-time vs recurring, payment plans, early bird pricing, etc.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-awards"></span> Bonuses & Add-ons</h2>
                <p class="section-description">Create irresistible bonuses that increase perceived value.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="bonuses">Bonus Offerings</label>
                        </th>
                        <td>
                            <textarea 
                                name="bonuses" 
                                id="bonuses" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="bonuses"
                                placeholder="List your bonus items, their value, and how they complement your main offer..."
                            ><?php echo esc_textarea($progress_data['bonuses'] ?? ''); ?></textarea>
                            <p class="description">Include bonus value and how each bonus supports the main transformation.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-shield-alt"></span> Guarantee</h2>
                <p class="section-description">Reduce risk and increase confidence with a strong guarantee.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="guarantee">Risk Reversal Guarantee</label>
                        </th>
                        <td>
                            <textarea 
                                name="guarantee" 
                                id="guarantee" 
                                rows="3" 
                                class="large-text ai-fillable"
                                data-field="guarantee"
                                placeholder="What guarantee do you offer? Money-back, satisfaction, results-based?"
                            ><?php echo esc_textarea($progress_data['guarantee'] ?? ''); ?></textarea>
                            <p class="description">Make it specific and easy to understand. What conditions apply?</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-clock"></span> Urgency & Scarcity</h2>
                <p class="section-description">Create legitimate urgency to encourage quick action.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="urgency_scarcity">Urgency Elements</label>
                        </th>
                        <td>
                            <textarea 
                                name="urgency_scarcity" 
                                id="urgency_scarcity" 
                                rows="3" 
                                class="large-text ai-fillable"
                                data-field="urgency_scarcity"
                                placeholder="Time limits, quantity limits, price increases, or other urgency factors..."
                            ><?php echo esc_textarea($progress_data['urgency_scarcity'] ?? ''); ?></textarea>
                            <p class="description">Keep it authentic. False scarcity can damage trust.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-format-chat"></span> Objection Handling</h2>
                <p class="section-description">Address common concerns before they become objections.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="objection_handling">Common Objections & Responses</label>
                        </th>
                        <td>
                            <textarea 
                                name="objection_handling" 
                                id="objection_handling" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="objection_handling"
                                placeholder="What objections do prospects have? How do you address price, time, skepticism, etc.?"
                            ><?php echo esc_textarea($progress_data['objection_handling'] ?? ''); ?></textarea>
                            <p class="description">Think about "I can't afford it," "I don't have time," "Will this work for me?" etc.</p>
                        </td>
                    </tr>
                </table>
            </div>

            
        
            
            
            <?php $current_phase = 'create_offer'; include dirname(__FILE__) . '/clean_navigation_fix.php'; ?>
</form>
    </div>

    <!-- Offer Creation Tips -->
    <div class="tips-section">
        <h3><span class="dashicons dashicons-lightbulb"></span> Offer Creation Tips</h3>
        <div class="tips-grid">
            <div class="tip-card">
                <h4>Focus on Outcomes</h4>
                <p>Don't sell features, sell the transformation. What will their life look like after using your product?</p>
            </div>
            <div class="tip-card">
                <h4>Stack Your Value</h4>
                <p>Show the total value of everything included. Make the price feel like a bargain compared to the value.</p>
            </div>
            <div class="tip-card">
                <h4>Address the Biggest Objection</h4>
                <p>Usually it's price or skepticism. Address these head-on in your offer presentation.</p>
            </div>
            <div class="tip-card">
                <h4>Test Your Pricing</h4>
                <p>Start higher than you think, then adjust. It's easier to lower prices than raise them later.</p>
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
            <h3>Offer Creation Complete!</h3>
            <p>Excellent! You've crafted your irresistible offer. Next, let's work on creating your service delivery.</p>
            <p><strong>Completed:</strong> <?php echo date('F j, Y', strtotime($progress_data['completed_at'])); ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.offer-creation-form .form-section {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 25px;
}

.offer-creation-form .form-section h2 {
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

.ai-fillable.ai-filled::after {
    content: "✓ Filled by AI";
    position: absolute;
    right: 10px;
    top: 10px;
    font-size: 12px;
    color: #10b981;
    font-weight: 600;
}

/* Add the existing styles from market-clarity template */
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