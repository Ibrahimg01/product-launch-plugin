<?php
/**
 * Build Funnel Template
 * File: templates/build-funnel.php
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$site_id = get_current_blog_id();

// Get saved progress for this phase
$progress_data = get_user_meta($user_id, 'product_launch_progress_build_funnel_' . $site_id, true);
$progress_data = $progress_data ? json_decode($progress_data, true) : array();

// Handle form submission
if (isset($_POST['save_progress']) && wp_verify_nonce($_POST['_wpnonce'], 'build_funnel_progress')) {
    $form_data = array(
        'funnel_strategy' => sanitize_textarea_field($_POST['funnel_strategy'] ?? ''),
        'lead_magnet' => sanitize_textarea_field($_POST['lead_magnet'] ?? ''),
        'landing_pages' => sanitize_textarea_field($_POST['landing_pages'] ?? ''),
        'sales_pages' => sanitize_textarea_field($_POST['sales_pages'] ?? ''),
        'checkout_process' => sanitize_textarea_field($_POST['checkout_process'] ?? ''),
        'upsells_downsells' => sanitize_textarea_field($_POST['upsells_downsells'] ?? ''),
        'funnel_optimization' => sanitize_textarea_field($_POST['funnel_optimization'] ?? ''),
        'completed_at' => current_time('mysql')
    );
    
    update_user_meta($user_id, 'product_launch_progress_build_funnel_' . $site_id, json_encode($form_data));
    
    echo '<div class="notice notice-success is-dismissible"><p>Funnel building progress saved successfully!</p></div>';
    $progress_data = $form_data;
}

$is_completed = !empty($progress_data['completed_at']);
?>
<?php
// --- Auto-persist & load progress for phase: build_funnel ---
$user_id = get_current_user_id();
$site_id = get_current_blog_id();
if (function_exists('pl_handle_phase_save_auto')) {
    $progress_data_saved = pl_handle_phase_save_auto('build_funnel');
    if ($progress_data_saved && is_array($progress_data_saved)) {
        $progress_data = $progress_data_saved;
    }
}
if (!isset($progress_data) || !is_array($progress_data) || empty($progress_data)) {
    $saved_raw = get_user_meta($user_id, 'product_launch_progress_build_funnel_' . $site_id, true);
    if ($saved_raw) {
        $decoded = json_decode($saved_raw, true);
        if (is_array($decoded)) $progress_data = $decoded;
    }
}
?>

<?php
$current_phase = 'build_funnel';

$completed_phases = array();
$all_phases = array('market_clarity', 'create_offer', 'create_service', 'build_funnel', 'email_sequences', 'organic_posts', 'facebook_ads', 'launch');
foreach ($all_phases as $phase) {
    $phase_progress = get_user_meta($user_id, 'product_launch_progress_' . $phase . '_' . $site_id, true);
    if (!empty($phase_progress)) { $completed_phases[] = $phase; }
}
?>


<div class="wrap product-launch-phase-container">
    <div class="phase-header">
    <h1>Building Sales Funnel <span class="beta-badge">Beta</span></h1>

        <div class="phase-icon">
            <span class="dashicons dashicons-networking"></span>
        </div>
        
        <p>Create high-converting sales systems that efficiently move prospects from awareness to purchase</p>
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
        <p>Need help designing your sales funnel? Our AI coach can help you create compelling lead magnets, optimize landing pages, and design conversion-focused funnels.</p>
        <button class="start-ai-coaching" data-phase="build_funnel">
            <span class="dashicons dashicons-format-chat"></span>
            Start AI Funnel Building Session
        </button>
    </div>

    <!-- Build Funnel Form -->
    <div class="phase-content">
        <form method="post" class="build-funnel-form">
            <?php wp_nonce_field('build_funnel_progress'); ?>
            
            <div class="form-section">
                <h2><span class="dashicons dashicons-chart-line"></span> Funnel Strategy</h2>
                <p class="section-description">Define your overall funnel strategy and customer journey.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="funnel_strategy">Funnel Strategy & Customer Journey</label>
                        </th>
                        <td>
                            <textarea 
                                name="funnel_strategy" 
                                id="funnel_strategy" 
                                rows="6" 
                                class="large-text ai-fillable"
                                data-field="funnel_strategy"
                                placeholder="Map out your customer journey: awareness → interest → consideration → purchase. What's your funnel flow?"
                            ><?php echo esc_textarea($progress_data['funnel_strategy'] ?? ''); ?></textarea>
                            <p class="description">How do prospects move through your funnel? What are the key stages and touchpoints?</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-download"></span> Lead Magnet</h2>
                <p class="section-description">Create compelling lead magnets to capture prospect information.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="lead_magnet">Lead Magnet Strategy</label>
                        </th>
                        <td>
                            <textarea 
                                name="lead_magnet" 
                                id="lead_magnet" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="lead_magnet"
                                placeholder="What lead magnets will you offer? Free guides, webinars, trials, templates? What value do they provide?"
                            ><?php echo esc_textarea($progress_data['lead_magnet'] ?? ''); ?></textarea>
                            <p class="description">Focus on high-value offers that solve immediate problems for your target audience.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-admin-page"></span> Landing Pages</h2>
                <p class="section-description">Design high-converting landing pages for lead capture.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="landing_pages">Landing Page Strategy</label>
                        </th>
                        <td>
                            <textarea 
                                name="landing_pages" 
                                id="landing_pages" 
                                rows="6" 
                                class="large-text ai-fillable"
                                data-field="landing_pages"
                                placeholder="Plan your landing pages: headlines, value propositions, social proof, form fields, design elements..."
                            ><?php echo esc_textarea($progress_data['landing_pages'] ?? ''); ?></textarea>
                            <p class="description">Include key elements: compelling headlines, clear benefits, social proof, and simple forms.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-cart"></span> Sales Pages</h2>
                <p class="section-description">Create persuasive sales pages that convert visitors to customers.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="sales_pages">Sales Page Strategy</label>
                        </th>
                        <td>
                            <textarea 
                                name="sales_pages" 
                                id="sales_pages" 
                                rows="7" 
                                class="large-text ai-fillable"
                                data-field="sales_pages"
                                placeholder="Plan your sales page: headline, problem/solution, features/benefits, social proof, pricing, guarantee, CTA..."
                            ><?php echo esc_textarea($progress_data['sales_pages'] ?? ''); ?></textarea>
                            <p class="description">Structure: attention-grabbing headline, problem identification, solution presentation, proof, offer, and clear CTA.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-money"></span> Checkout Process</h2>
                <p class="section-description">Optimize your checkout process to minimize abandonment.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="checkout_process">Checkout Optimization</label>
                        </th>
                        <td>
                            <textarea 
                                name="checkout_process" 
                                id="checkout_process" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="checkout_process"
                                placeholder="How will you optimize checkout? Payment options, form fields, security badges, mobile optimization..."
                            ><?php echo esc_textarea($progress_data['checkout_process'] ?? ''); ?></textarea>
                            <p class="description">Keep it simple: minimal form fields, multiple payment options, clear security, mobile-friendly.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-plus-alt"></span> Upsells & Downsells</h2>
                <p class="section-description">Maximize revenue with strategic upsells and downsells.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="upsells_downsells">Upsell/Downsell Strategy</label>
                        </th>
                        <td>
                            <textarea 
                                name="upsells_downsells" 
                                id="upsells_downsells" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="upsells_downsells"
                                placeholder="What additional offers will you present? Upgrades, add-ons, payment plans, related products..."
                            ><?php echo esc_textarea($progress_data['upsells_downsells'] ?? ''); ?></textarea>
                            <p class="description">Offer complementary products or upgrades that enhance the main purchase value.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-chart-area"></span> Funnel Optimization</h2>
                <p class="section-description">Plan how you'll test and optimize your funnel performance.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="funnel_optimization">Testing & Optimization Plan</label>
                        </th>
                        <td>
                            <textarea 
                                name="funnel_optimization" 
                                id="funnel_optimization" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="funnel_optimization"
                                placeholder="How will you test and optimize? A/B tests, metrics to track, conversion optimization strategies..."
                            ><?php echo esc_textarea($progress_data['funnel_optimization'] ?? ''); ?></textarea>
                            <p class="description">Include key metrics, testing schedule, and optimization priorities based on data.</p>
                        </td>
                    </tr>
                </table>
            </div>

            
        
            
            
            <?php $current_phase = 'build_funnel'; include dirname(__FILE__) . '/clean_navigation_fix.php'; ?>
</form>
    </div>

    <!-- Funnel Building Tips -->
    <div class="tips-section">
        <h3><span class="dashicons dashicons-lightbulb"></span> Funnel Building Tips</h3>
        <div class="tips-grid">
            <div class="tip-card">
                <h4>Keep It Simple</h4>
                <p>Reduce friction at every step. Fewer form fields, clear navigation, and obvious next steps increase conversions.</p>
            </div>
            <div class="tip-card">
                <h4>Test One Thing at a Time</h4>
                <p>A/B test individual elements (headlines, buttons, forms) rather than entire pages for clearer insights.</p>
            </div>
            <div class="tip-card">
                <h4>Mobile-First Design</h4>
                <p>Most traffic is mobile. Design your funnel for mobile devices first, then adapt for desktop.</p>
            </div>
            <div class="tip-card">
                <h4>Track Every Step</h4>
                <p>Monitor conversion rates at each funnel stage to identify bottlenecks and optimization opportunities.</p>
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
            <h3>Sales Funnel Complete!</h3>
            <p>Fantastic! You've designed your sales funnel strategy. Next, let's create the email sequences that will nurture your leads.</p>
            <p><strong>Completed:</strong> <?php echo date('F j, Y', strtotime($progress_data['completed_at'])); ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* Reuse styles from previous templates */
.build-funnel-form .form-section {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 25px;
}

.build-funnel-form .form-section h2 {
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
