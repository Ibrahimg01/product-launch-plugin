<?php
/**
 * Create Service Template
 * File: templates/create-service.php
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$site_id = get_current_blog_id();

// Get saved progress for this phase
$progress_data = get_user_meta($user_id, 'product_launch_progress_create_service_' . $site_id, true);
$progress_data = $progress_data ? json_decode($progress_data, true) : array();

// Handle form submission
if (isset($_POST['save_progress']) && wp_verify_nonce($_POST['_wpnonce'], 'create_service_progress')) {
    $form_data = array(
        'service_concept' => sanitize_textarea_field($_POST['service_concept'] ?? ''),
        'service_packaging' => sanitize_textarea_field($_POST['service_packaging'] ?? ''),
        'delivery_method' => sanitize_textarea_field($_POST['delivery_method'] ?? ''),
        'pricing_model' => sanitize_textarea_field($_POST['pricing_model'] ?? ''),
        'client_onboarding' => sanitize_textarea_field($_POST['client_onboarding'] ?? ''),
        'service_framework' => sanitize_textarea_field($_POST['service_framework'] ?? ''),
        'scalability_plan' => sanitize_textarea_field($_POST['scalability_plan'] ?? ''),
        'completed_at' => current_time('mysql')
    );
    
    update_user_meta($user_id, 'product_launch_progress_create_service_' . $site_id, json_encode($form_data));
    
    echo '<div class="notice notice-success is-dismissible"><p>Service creation progress saved successfully!</p></div>';
    $progress_data = $form_data;
}

$is_completed = !empty($progress_data['completed_at']);
?>
<?php
// --- Auto-persist & load progress for phase: create_service ---
$user_id = get_current_user_id();
$site_id = get_current_blog_id();
if (function_exists('pl_handle_phase_save_auto')) {
    $progress_data_saved = pl_handle_phase_save_auto('create_service');
    if ($progress_data_saved && is_array($progress_data_saved)) {
        $progress_data = $progress_data_saved;
    }
}
if (!isset($progress_data) || !is_array($progress_data) || empty($progress_data)) {
    $saved_raw = get_user_meta($user_id, 'product_launch_progress_create_service_' . $site_id, true);
    if ($saved_raw) {
        $decoded = json_decode($saved_raw, true);
        if (is_array($decoded)) $progress_data = $decoded;
    }
}
?>

<?php
$current_phase = 'create_service';

$completed_phases = array();
$all_phases = array('market_clarity', 'create_offer', 'create_service', 'build_funnel', 'email_sequences', 'organic_posts', 'facebook_ads', 'launch');
foreach ($all_phases as $phase) {
    $phase_progress = get_user_meta($user_id, 'product_launch_progress_' . $phase . '_' . $site_id, true);
    if (!empty($phase_progress)) { $completed_phases[] = $phase; }
}
?>


<div class="wrap product-launch-phase-container">
    <div class="phase-header">
    <h1>Creating Your Service <span class="beta-badge">Beta</span></h1>

        <div class="phase-icon">
            <span class="dashicons dashicons-admin-tools"></span>
        </div>
        
        <p>Build premium service offerings that deliver exceptional results and command premium pricing</p>
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
        <p>Need help designing your service offering? Our AI coach can help you structure premium services, create delivery frameworks, and design scalable systems.</p>
        <button class="start-ai-coaching" data-phase="create_service">
            <span class="dashicons dashicons-format-chat"></span>
            Start AI Service Design Session
        </button>
    </div>

    <!-- Create Service Form -->
    <div class="phase-content">
        <form method="post" class="create-service-form">
            <?php wp_nonce_field('create_service_progress'); ?>
            
            <div class="form-section">
                <h2><span class="dashicons dashicons-lightbulb"></span> Service Concept</h2>
                <p class="section-description">Define the core concept and transformation your service provides.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="service_concept">Service Concept & Transformation</label>
                        </th>
                        <td>
                            <textarea 
                                name="service_concept" 
                                id="service_concept" 
                                rows="6" 
                                class="large-text ai-fillable"
                                data-field="service_concept"
                                placeholder="What service will you provide? What transformation or outcome will clients achieve? What problem does it solve?"
                            ><?php echo esc_textarea($progress_data['service_concept'] ?? ''); ?></textarea>
                            <p class="description">Focus on the end result and transformation clients will experience through your service.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-portfolio"></span> Service Packaging</h2>
                <p class="section-description">Structure your service into clear, valuable packages.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="service_packaging">Service Packages & Tiers</label>
                        </th>
                        <td>
                            <textarea 
                                name="service_packaging" 
                                id="service_packaging" 
                                rows="6" 
                                class="large-text ai-fillable"
                                data-field="service_packaging"
                                placeholder="How will you package your service? Different tiers, what's included in each, duration, deliverables..."
                            ><?php echo esc_textarea($progress_data['service_packaging'] ?? ''); ?></textarea>
                            <p class="description">Consider basic, premium, and VIP tiers with clear value differentiation.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-video-alt3"></span> Delivery Method</h2>
                <p class="section-description">Plan how you'll deliver your service to clients.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="delivery_method">Service Delivery Framework</label>
                        </th>
                        <td>
                            <textarea 
                                name="delivery_method" 
                                id="delivery_method" 
                                rows="6" 
                                class="large-text ai-fillable"
                                data-field="delivery_method"
                                placeholder="How will you deliver the service? 1:1 sessions, group coaching, online course, hybrid? What's the format and structure?"
                            ><?php echo esc_textarea($progress_data['delivery_method'] ?? ''); ?></textarea>
                            <p class="description">Include session frequency, duration, communication methods, and any digital components.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-money-alt"></span> Pricing Model</h2>
                <p class="section-description">Structure your pricing for premium positioning and profitability.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="pricing_model">Pricing Strategy & Model</label>
                        </th>
                        <td>
                            <textarea 
                                name="pricing_model" 
                                id="pricing_model" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="pricing_model"
                                placeholder="What's your pricing strategy? Hourly, project-based, retainer, results-based? Price points for each tier?"
                            ><?php echo esc_textarea($progress_data['pricing_model'] ?? ''); ?></textarea>
                            <p class="description">Consider value-based pricing rather than time-based. Price for outcomes and transformation.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-welcome-learn-more"></span> Client Onboarding</h2>
                <p class="section-description">Design a smooth onboarding process that sets expectations.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="client_onboarding">Onboarding Process</label>
                        </th>
                        <td>
                            <textarea 
                                name="client_onboarding" 
                                id="client_onboarding" 
                                rows="6" 
                                class="large-text ai-fillable"
                                data-field="client_onboarding"
                                placeholder="How will you onboard new clients? Welcome process, initial assessments, goal setting, expectations..."
                            ><?php echo esc_textarea($progress_data['client_onboarding'] ?? ''); ?></textarea>
                            <p class="description">Include welcome materials, contracts, initial calls, and expectation-setting processes.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-admin-generic"></span> Service Framework</h2>
                <p class="section-description">Create a systematic framework for consistent delivery.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="service_framework">Delivery Framework & Process</label>
                        </th>
                        <td>
                            <textarea 
                                name="service_framework" 
                                id="service_framework" 
                                rows="7" 
                                class="large-text ai-fillable"
                                data-field="service_framework"
                                placeholder="What's your step-by-step process? Phases, milestones, deliverables, timelines, quality checks..."
                            ><?php echo esc_textarea($progress_data['service_framework'] ?? ''); ?></textarea>
                            <p class="description">Create a repeatable system that ensures consistent quality and results for every client.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-chart-area"></span> Scalability Plan</h2>
                <p class="section-description">Plan how you'll scale your service without sacrificing quality.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="scalability_plan">Scaling Strategy</label>
                        </th>
                        <td>
                            <textarea 
                                name="scalability_plan" 
                                id="scalability_plan" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="scalability_plan"
                                placeholder="How will you scale? Group programs, digital components, team members, automation, premium pricing..."
                            ><?php echo esc_textarea($progress_data['scalability_plan'] ?? ''); ?></textarea>
                            <p class="description">Consider group delivery, digital tools, team expansion, and premium positioning for growth.</p>
                        </td>
                    </tr>
                </table>
            </div>

            
        
            
            
            <?php $current_phase = 'create_service'; include dirname(__FILE__) . '/clean_navigation_fix.php'; ?>
</form>
    </div>

    <!-- Service Creation Tips -->
    <div class="tips-section">
        <h3><span class="dashicons dashicons-lightbulb"></span> Service Creation Tips</h3>
        <div class="tips-grid">
            <div class="tip-card">
                <h4>Focus on Outcomes</h4>
                <p>Sell the transformation, not your time. Package services around the results clients want to achieve.</p>
            </div>
            <div class="tip-card">
                <h4>Create Systems</h4>
                <p>Build repeatable frameworks and processes. This ensures quality and makes your service easier to scale.</p>
            </div>
            <div class="tip-card">
                <h4>Position as Premium</h4>
                <p>Price based on value delivered, not time spent. Premium pricing attracts better clients and higher respect.</p>
            </div>
            <div class="tip-card">
                <h4>Plan for Scale</h4>
                <p>Design your service with growth in mind. Consider how you can serve more clients without linear time increase.</p>
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
            <h3>Service Creation Complete!</h3>
            <p>Outstanding! You've designed your premium service offering. Next, let's build the sales funnel to attract and convert clients.</p>
            <p><strong>Completed:</strong> <?php echo date('F j, Y', strtotime($progress_data['completed_at'])); ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* Reuse styles from previous templates */
.create-service-form .form-section {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 25px;
}

.create-service-form .form-section h2 {
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
