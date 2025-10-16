<?php
/**
 * Organic Posts Template
 * File: templates/organic-posts.php
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$site_id = get_current_blog_id();

// Get saved progress for this phase
$progress_data = get_user_meta($user_id, 'product_launch_progress_organic_posts_' . $site_id, true);
$progress_data = $progress_data ? json_decode($progress_data, true) : array();

// Handle form submission
if (isset($_POST['save_progress']) && wp_verify_nonce($_POST['_wpnonce'], 'organic_posts_progress')) {
    $form_data = array(
        'content_strategy' => sanitize_textarea_field($_POST['content_strategy'] ?? ''),
        'platform_strategy' => sanitize_textarea_field($_POST['platform_strategy'] ?? ''),
        'content_themes' => sanitize_textarea_field($_POST['content_themes'] ?? ''),
        'post_ideas' => sanitize_textarea_field($_POST['post_ideas'] ?? ''),
        'engagement_strategy' => sanitize_textarea_field($_POST['engagement_strategy'] ?? ''),
        'hashtag_strategy' => sanitize_textarea_field($_POST['hashtag_strategy'] ?? ''),
        'content_calendar' => sanitize_textarea_field($_POST['content_calendar'] ?? ''),
        'completed_at' => current_time('mysql')
    );
    
    update_user_meta($user_id, 'product_launch_progress_organic_posts_' . $site_id, json_encode($form_data));
    
    echo '<div class="notice notice-success is-dismissible"><p>Organic posts progress saved successfully!</p></div>';
    $progress_data = $form_data;
}

$is_completed = !empty($progress_data['completed_at']);
?>
<?php
// --- Auto-persist & load progress for phase: organic_posts ---
$user_id = get_current_user_id();
$site_id = get_current_blog_id();
if (function_exists('pl_handle_phase_save_auto')) {
    $progress_data_saved = pl_handle_phase_save_auto('organic_posts');
    if ($progress_data_saved && is_array($progress_data_saved)) {
        $progress_data = $progress_data_saved;
    }
}
if (!isset($progress_data) || !is_array($progress_data) || empty($progress_data)) {
    $saved_raw = get_user_meta($user_id, 'product_launch_progress_organic_posts_' . $site_id, true);
    if ($saved_raw) {
        $decoded = json_decode($saved_raw, true);
        if (is_array($decoded)) $progress_data = $decoded;
    }
}
?>

<?php
$current_phase = 'organic_posts';

$completed_phases = array();
$all_phases = array('market_clarity', 'create_offer', 'create_service', 'build_funnel', 'email_sequences', 'organic_posts', 'facebook_ads', 'launch');
foreach ($all_phases as $phase) {
    $phase_progress = get_user_meta($user_id, 'product_launch_progress_' . $phase . '_' . $site_id, true);
    if (!empty($phase_progress)) { $completed_phases[] = $phase; }
}
?>


<div class="wrap product-launch-phase-container">
    <div class="phase-header">
    <h1>Organic Promo Posts <span class="beta-badge">Beta</span></h1>

        <div class="phase-icon">
            <span class="dashicons dashicons-share"></span>
        </div>
        
        <p>Create engaging organic social media content that builds anticipation for your launch</p>
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
        <p>Need help creating engaging social content? Our AI coach can help you develop content themes, write compelling posts, and plan your content calendar.</p>
        <button class="start-ai-coaching" data-phase="organic_posts">
            <span class="dashicons dashicons-format-chat"></span>
            Start AI Content Creation Session
        </button>
    </div>

    <!-- Organic Posts Form -->
    <div class="phase-content">
        <form method="post" class="organic-posts-form">
            <?php wp_nonce_field('organic_posts_progress'); ?>
            
            <div class="form-section">
                <h2><span class="dashicons dashicons-chart-area"></span> Content Strategy</h2>
                <p class="section-description">Define your overall organic content strategy and goals.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="content_strategy">Overall Content Strategy</label>
                        </th>
                        <td>
                            <textarea 
                                name="content_strategy" 
                                id="content_strategy" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="content_strategy"
                                placeholder="What's your content strategy? Goals, target audience behavior, content mix, posting frequency..."
                            ><?php echo esc_textarea($progress_data['content_strategy'] ?? ''); ?></textarea>
                            <p class="description">How does organic content support your launch? What's your content-to-promotion ratio?</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-admin-site-alt3"></span> Platform Strategy</h2>
                <p class="section-description">Choose your platforms and tailor content for each.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="platform_strategy">Platform-Specific Approach</label>
                        </th>
                        <td>
                            <textarea 
                                name="platform_strategy" 
                                id="platform_strategy" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="platform_strategy"
                                placeholder="Which platforms will you focus on? How will you adapt content for each (Instagram, Facebook, LinkedIn, TikTok, etc.)?"
                            ><?php echo esc_textarea($progress_data['platform_strategy'] ?? ''); ?></textarea>
                            <p class="description">Consider where your audience spends time and how content performs on each platform.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-category"></span> Content Themes</h2>
                <p class="section-description">Develop consistent themes that align with your brand and audience interests.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="content_themes">Content Pillars/Themes</label>
                        </th>
                        <td>
                            <textarea 
                                name="content_themes" 
                                id="content_themes" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="content_themes"
                                placeholder="What are your main content themes? Educational, behind-the-scenes, testimonials, tips, entertainment..."
                            ><?php echo esc_textarea($progress_data['content_themes'] ?? ''); ?></textarea>
                            <p class="description">Typically 3-5 main themes that provide value and build toward your launch.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-edit"></span> Post Ideas</h2>
                <p class="section-description">Brainstorm specific post ideas and content concepts.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="post_ideas">Specific Post Concepts</label>
                        </th>
                        <td>
                            <textarea 
                                name="post_ideas" 
                                id="post_ideas" 
                                rows="8" 
                                class="large-text ai-fillable"
                                data-field="post_ideas"
                                placeholder="List specific post ideas: tutorials, Q&As, day-in-the-life, transformation stories, tips, countdown posts..."
                            ><?php echo esc_textarea($progress_data['post_ideas'] ?? ''); ?></textarea>
                            <p class="description">Aim for 20-30 post ideas to create a content bank. Include mix of formats: text, images, videos, carousels.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-heart"></span> Engagement Strategy</h2>
                <p class="section-description">Plan how you'll engage with your audience and build community.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="engagement_strategy">Community Engagement Plan</label>
                        </th>
                        <td>
                            <textarea 
                                name="engagement_strategy" 
                                id="engagement_strategy" 
                                rows="4" 
                                class="large-text ai-fillable"
                                data-field="engagement_strategy"
                                placeholder="How will you encourage engagement? Questions, polls, stories, user-generated content, responding to comments..."
                            ><?php echo esc_textarea($progress_data['engagement_strategy'] ?? ''); ?></textarea>
                            <p class="description">Engagement is key to organic reach. Plan interactive elements and response strategies.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-tag"></span> Hashtag Strategy</h2>
                <p class="section-description">Research and plan your hashtag approach for discoverability.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="hashtag_strategy">Hashtag Research & Strategy</label>
                        </th>
                        <td>
                            <textarea 
                                name="hashtag_strategy" 
                                id="hashtag_strategy" 
                                rows="4" 
                                class="large-text ai-fillable"
                                data-field="hashtag_strategy"
                                placeholder="List relevant hashtags: branded, industry, niche, trending. Include mix of high and low competition hashtags..."
                            ><?php echo esc_textarea($progress_data['hashtag_strategy'] ?? ''); ?></textarea>
                            <p class="description">Research hashtags your audience uses. Mix popular and niche tags for better discoverability.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="form-section">
                <h2><span class="dashicons dashicons-calendar-alt"></span> Content Calendar</h2>
                <p class="section-description">Plan your posting schedule and content timing.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="content_calendar">Posting Schedule & Timeline</label>
                        </th>
                        <td>
                            <textarea 
                                name="content_calendar" 
                                id="content_calendar" 
                                rows="5" 
                                class="large-text ai-fillable"
                                data-field="content_calendar"
                                placeholder="Plan your posting schedule: frequency, best times, pre-launch timeline, launch week strategy..."
                            ><?php echo esc_textarea($progress_data['content_calendar'] ?? ''); ?></textarea>
                            <p class="description">When will you post? How often? What's your timeline leading up to launch?</p>
                        </td>
                    </tr>
                </table>
            </div>

            
        
            
            
            <?php $current_phase = 'organic_posts'; include dirname(__FILE__) . '/clean_navigation_fix.php'; ?>
</form>
    </div>

    <!-- Content Creation Tips -->
    <div class="tips-section">
        <h3><span class="dashicons dashicons-lightbulb"></span> Organic Content Tips</h3>
        <div class="tips-grid">
            <div class="tip-card">
                <h4>Value Before Promotion</h4>
                <p>Follow the 80/20 rule: 80% valuable content, 20% promotional. Build trust before asking for sales.</p>
            </div>
            <div class="tip-card">
                <h4>Show Your Face</h4>
                <p>Personal content performs better. Show behind-the-scenes, your story, and the human side of your business.</p>
            </div>
            <div class="tip-card">
                <h4>Engage Authentically</h4>
                <p>Respond to comments quickly and meaningfully. Ask questions and create conversation, don't just broadcast.</p>
            </div>
            <div class="tip-card">
                <h4>Batch Create Content</h4>
                <p>Set aside time to create multiple posts at once. Use scheduling tools to maintain consistency.</p>
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
            <h3>Organic Content Strategy Complete!</h3>
            <p>Perfect! You've planned your organic social media strategy. Next, let's create your Facebook advertising campaigns.</p>
            <p><strong>Completed:</strong> <?php echo date('F j, Y', strtotime($progress_data['completed_at'])); ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
/* Use the same styles as previous templates */
.organic-posts-form .form-section {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 25px;
}

.organic-posts-form .form-section h2 {
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
