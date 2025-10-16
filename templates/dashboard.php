<?php
/**
 * Dashboard Template
 * File: templates/dashboard.php
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$site_id = get_current_blog_id();

// Get user progress for each phase
$phases = array(
    'market_clarity' => array(
        'title' => 'Getting Market Clarity',
        'description' => 'Identify your target market and positioning',
        'icon' => 'search',
        'url' => admin_url('admin.php?page=product-launch-market')
    ),
    'create_offer' => array(
        'title' => 'Creating Your Offer',
        'description' => 'Design irresistible offers that convert',
        'icon' => 'products',
        'url' => admin_url('admin.php?page=product-launch-offer')
    ),
    'create_service' => array(
        'title' => 'Creating Your Service',
        'description' => 'Build premium service offerings',
        'icon' => 'admin-tools',
        'url' => admin_url('admin.php?page=product-launch-service')
    ),
    'build_funnel' => array(
        'title' => 'Building Sales Funnel',
        'description' => 'Create high-converting sales systems',
        'icon' => 'networking',
        'url' => admin_url('admin.php?page=product-launch-funnel')
    ),
    'email_sequences' => array(
        'title' => 'Writing Email Sequences',
        'description' => 'Craft compelling email campaigns',
        'icon' => 'email-alt',
        'url' => admin_url('admin.php?page=product-launch-emailss')
    ),
    'organic_posts' => array(
        'title' => 'Organic Promo Posts',
        'description' => 'Create engaging social content',
        'icon' => 'share',
        'url' => admin_url('admin.php?page=product-launch-organic')
    ),
    'facebook_ads' => array(
        'title' => 'Creating Facebook Ads',
        'description' => 'Design high-converting ad campaigns',
        'icon' => 'megaphone',
        'url' => admin_url('admin.php?page=product-launch-ads')
    ),
    'launch' => array(
        'title' => 'Launching',
        'description' => 'Execute your product launch',
        'icon' => 'pressthis',
        'url' => admin_url('admin.php?page=product-launch-launch')
    )
);

// Calculate progress
$completed_phases = 0;
$total_phases = count($phases);

foreach ($phases as $phase_key => $phase) {
    $phase_progress = get_user_meta($user_id, 'product_launch_progress_' . $phase_key . '_' . $site_id, true);
    if (!empty($phase_progress)) {
        $completed_phases++;
        $phases[$phase_key]['status'] = 'completed';
    } else {
        $phases[$phase_key]['status'] = 'not-started';
    }
}

$completion_percentage = $total_phases > 0 ? round(($completed_phases / $total_phases) * 100) : 0;
?>

<div class="wrap product-launch-phase-container">
    <div class="phase-header">
        <h1>Product Launch Dashboard <span class="beta-badge">Beta</span></h1>

        <div class="phase-icon">
            <span class="dashicons dashicons-chart-line"></span>
        </div>
        <p>Your comprehensive guide to launching products successfully with AI-powered coaching</p>
    </div>
    
    <!-- Progress Overview -->
    <div class="phase-tracker">
        <h2 class="tracker-title">Launch Progress: <?php echo $completion_percentage; ?>% Complete</h2>
        
        <div class="phase-steps">
            <?php $step = 1; foreach ($phases as $phase_key => $phase) : ?>
            <div class="phase-step <?php echo $phase['status']; ?>" data-phase="<?php echo esc_attr($phase_key); ?>">
                <div class="step-circle <?php echo $phase['status']; ?>">
                    <?php if ($phase['status'] === 'completed') : ?>
                        <span class="dashicons dashicons-yes-alt"></span>
                    <?php else : ?>
                        <?php echo $step; ?>
                    <?php endif; ?>
                </div>
                <div class="step-label <?php echo $phase['status']; ?>">
                    <?php echo esc_html($phase['title']); ?>
                </div>
            </div>
            <?php $step++; endforeach; ?>
        </div>
    </div>
    
    <!-- Phase Cards -->
    <div class="launch-progress-overview">
        <?php foreach ($phases as $phase_key => $phase) : ?>
        <div class="progress-card <?php echo $phase['status']; ?>" data-phase-url="<?php echo esc_url($phase['url']); ?>">
            <div class="card-header">
                <div class="card-icon <?php echo esc_attr($phase_key); ?>">
                    <span class="dashicons dashicons-<?php echo esc_attr($phase['icon']); ?>"></span>
                </div>
                <div class="card-content">
                    <h3><?php echo esc_html($phase['title']); ?></h3>
                    <p><?php echo esc_html($phase['description']); ?></p>
                    <div class="card-status">
                        <span class="status-<?php echo esc_attr($phase['status']); ?>">
                            <?php 
                            switch($phase['status']) {
                                case 'completed':
                                    echo 'Completed';
                                    break;
                                case 'in-progress':
                                    echo 'In Progress';
                                    break;
                                default:
                                    echo 'Not Started';
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="card-action" style="text-align: center;">
                <?php if ($phase['status'] === 'completed') : ?>
                    <a href="<?php echo esc_url($phase['url']); ?>" class="phase-button">
                        <span class="dashicons dashicons-visibility"></span>
                        Review
                    </a><?php else : ?>
                    <a href="<?php echo esc_url($phase['url']); ?>" class="phase-button primary">
                        <span class="dashicons dashicons-arrow-right-alt"></span>
                        Start Phase
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Quick Actions -->
    <div class="ai-coach-section">
        <div class="coach-avatar">
            <span class="dashicons dashicons-businesswoman"></span>
        </div>
        <h3 style="text-align: center; margin-bottom: 15px;">Need Guidance?</h3>
        <p style="text-align: center; margin-bottom: 20px; color: #6b7280;">
            Start a conversation with your AI Product Launch Coach for personalized guidance on any phase.
        </p>
        
        <div class="text-center">
            <button class="start-ai-coaching" data-phase="general">
                <span class="dashicons dashicons-format-chat"></span>
                Start AI Coaching Session
            </button>
        </div>
    </div>
    
    <!-- Launch Statistics -->
    <?php
    global $wpdb;
    $table_name = $wpdb->prefix . 'product_launch_conversations';
    
    $user_stats = $wpdb->get_row($wpdb->prepare(
        "SELECT 
            COUNT(*) as total_conversations,
            COUNT(DISTINCT phase) as phases_engaged,
            COUNT(DISTINCT DATE(created_at)) as active_days
        FROM {$table_name} 
        WHERE user_id = %d AND site_id = %d",
        $user_id, $site_id
    ), ARRAY_A);
    
    if ($user_stats && $user_stats['total_conversations'] > 0) :
    ?>
    <div class="launch-stats">
        <h3>Your Launch Journey</h3>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number"><?php echo intval($user_stats['total_conversations']); ?></div>
                <div class="stat-label">AI Conversations</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo intval($user_stats['phases_engaged']); ?></div>
                <div class="stat-label">Phases Explored</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo intval($user_stats['active_days']); ?></div>
                <div class="stat-label">Active Days</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $completion_percentage; ?>%</div>
                <div class="stat-label">Complete</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recent Activity -->
    <?php
    $recent_conversations = $wpdb->get_results($wpdb->prepare(
        "SELECT phase, created_at 
        FROM {$table_name} 
        WHERE user_id = %d AND site_id = %d 
        ORDER BY created_at DESC 
        LIMIT 5",
        $user_id, $site_id
    ), ARRAY_A);
    
    if (!empty($recent_conversations)) :
    ?>
    <div class="recent-activity">
        <h3>Recent Activity</h3>
        <div class="activity-list">
            <?php foreach ($recent_conversations as $conversation) : ?>
            <div class="activity-item">
                <div class="activity-icon">
                    <span class="dashicons dashicons-format-chat"></span>
                </div>
                <div class="activity-content">
                    <div class="activity-title">
                        AI Coaching: <?php echo esc_html($phases[$conversation['phase']]['title'] ?? ucwords(str_replace('_', ' ', $conversation['phase']))); ?>
                    </div>
                    <div class="activity-time">
                        <?php echo human_time_diff(strtotime($conversation['created_at']), current_time('timestamp')) . ' ago'; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Tips Section -->
    <div class="launch-tips">
        <h3>💡 Pro Tips for Success</h3>
        <div class="tips-grid">
            <div class="tip-card">
                <h4>Start with Market Clarity</h4>
                <p>The clearer you are on your target market, the easier every other phase becomes. Invest time here first.</p>
            </div>
            <div class="tip-card">
                <h4>Test Your Offer Early</h4>
                <p>Get feedback on your offer before building the full funnel. Small adjustments early save big changes later.</p>
            </div>
            <div class="tip-card">
                <h4>Build Your Email List</h4>
                <p>Start collecting emails as early as possible. Your email list is your most valuable launch asset.</p>
            </div>
            <div class="tip-card">
                <h4>Plan Your Launch Timeline</h4>
                <p>Work backwards from your launch date. Give yourself more time than you think you need.</p>
            </div>
        </div>
    </div>
    
</div>

<style>
.card-action {
    margin-top: 15px;
    text-align: center; /* Center the buttons */
}

.card-action .phase-button {
    margin: 5px;
}

.launch-stats {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 30px;
}

.launch-stats h3 {
    text-align: center;
    margin-bottom: 25px;
    color: #1f2937;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
}

.stat-item {
    text-align: center;
    padding: 20px;
    background: #f8fafc;
    border-radius: 8px;
}

.stat-number {
    font-size: 28px;
    font-weight: bold;
    color: #3b82f6;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
}

.recent-activity {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 30px;
}

.recent-activity h3 {
    margin-bottom: 20px;
    color: #1f2937;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8fafc;
    border-radius: 8px;
    border-left: 4px solid #3b82f6;
}

.activity-icon {
    width: 40px;
    height: 40px;
    background: #3b82f6;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.activity-title {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.activity-time {
    font-size: 12px;
    color: #6b7280;
}

.launch-tips {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    border: 1px solid #f59e0b;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 30px;
}

.launch-tips h3 {
    text-align: center;
    margin-bottom: 25px;
    color: #92400e;
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
    font-size: 16px;
}

.tip-card p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
    line-height: 1.5;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .tips-grid {
        grid-template-columns: 1fr;
    }
}
</style>
