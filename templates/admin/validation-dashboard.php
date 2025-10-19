<?php
/**
 * Admin Dashboard Template
 */
if (!defined('ABSPATH')) {
    exit;
}
?>

<?php
$wrapper_class = isset($embedded) && $embedded
    ? 'pl-validation-dashboard pl-validation-dashboard--embedded'
    : 'wrap pl-validation-dashboard';
$heading_tag = (isset($embedded) && $embedded) ? 'h2' : 'h1';
?>

<div class="<?php echo esc_attr($wrapper_class); ?>">
    <<?php echo esc_html($heading_tag); ?>><?php _e('Idea Validation Dashboard', 'product-launch'); ?></<?php echo esc_html($heading_tag); ?>>

    <div class="pl-stats-grid">
        <div class="pl-stat-card">
            <div class="pl-stat-icon">📊</div>
            <div class="pl-stat-content">
                <div class="pl-stat-value"><?php echo number_format_i18n($total_validations); ?></div>
                <div class="pl-stat-label"><?php _e('Total Validations', 'product-launch'); ?></div>
            </div>
        </div>

        <div class="pl-stat-card">
            <div class="pl-stat-icon">✅</div>
            <div class="pl-stat-content">
                <div class="pl-stat-value"><?php echo number_format_i18n($completed_validations); ?></div>
                <div class="pl-stat-label"><?php _e('Completed', 'product-launch'); ?></div>
            </div>
        </div>

        <div class="pl-stat-card">
            <div class="pl-stat-icon">⏳</div>
            <div class="pl-stat-content">
                <div class="pl-stat-value"><?php echo number_format_i18n($pending_validations); ?></div>
                <div class="pl-stat-label"><?php _e('Pending', 'product-launch'); ?></div>
            </div>
        </div>

        <div class="pl-stat-card">
            <div class="pl-stat-icon">⭐</div>
            <div class="pl-stat-content">
                <div class="pl-stat-value"><?php echo number_format_i18n($avg_score, 1); ?></div>
                <div class="pl-stat-label"><?php _e('Average Score', 'product-launch'); ?></div>
            </div>
        </div>

        <div class="pl-stat-card">
            <div class="pl-stat-icon">📅</div>
            <div class="pl-stat-content">
                <div class="pl-stat-value"><?php echo number_format_i18n($this_month); ?></div>
                <div class="pl-stat-label"><?php _e('This Month', 'product-launch'); ?></div>
            </div>
        </div>
    </div>

    <div class="pl-recent-section">
        <h2><?php _e('Recent Validations', 'product-launch'); ?></h2>

        <?php if (empty($recent)) : ?>
            <p><?php _e('No validations yet.', 'product-launch'); ?></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Business Idea', 'product-launch'); ?></th>
                        <th><?php _e('User', 'product-launch'); ?></th>
                        <th><?php _e('Score', 'product-launch'); ?></th>
                        <th><?php _e('Status', 'product-launch'); ?></th>
                        <th><?php _e('Date', 'product-launch'); ?></th>
                        <th><?php _e('Actions', 'product-launch'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $validation) : ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html(wp_trim_words($validation->business_idea, 10)); ?></strong>
                            </td>
                            <td><?php echo esc_html($validation->display_name); ?></td>
                            <td>
                                <?php
                                $score_class = 'low';
                                if ((int) $validation->validation_score >= 70) {
                                    $score_class = 'high';
                                } elseif ((int) $validation->validation_score >= 40) {
                                    $score_class = 'medium';
                                }
                                ?>
                                <span class="pl-score-badge pl-score-<?php echo esc_attr($score_class); ?>">
                                    <?php echo esc_html($validation->validation_score); ?>
                                </span>
                            </td>
                            <td>
                                <span class="pl-status-badge pl-status-<?php echo esc_attr($validation->validation_status); ?>">
                                    <?php echo esc_html(ucfirst($validation->validation_status)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($validation->created_at))); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=product-launch-validation&validation_id=' . absint($validation->id))); ?>" class="button button-small">
                                    <?php _e('View', 'product-launch'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="pl-view-all">
                <a href="<?php echo esc_url(admin_url('admin.php?page=product-launch-validation-list')); ?>" class="button">
                    <?php _e('View All Validations', 'product-launch'); ?>
                </a>
            </p>
        <?php endif; ?>
    </div>
</div>
