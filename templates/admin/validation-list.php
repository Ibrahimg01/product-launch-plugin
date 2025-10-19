<?php
/**
 * Validations List Template
 */
if (!defined('ABSPATH')) {
    exit;
}
?>

<?php $list_base_url = isset($list_base_url) ? $list_base_url : admin_url('admin.php?page=' . $list_base_slug); ?>

<div class="wrap pl-validation-list">
    <h1 class="wp-heading-inline"><?php _e('All Validations', 'product-launch'); ?></h1>
    <hr class="wp-header-end">

    <div class="pl-list-filters">
        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr($list_base_slug); ?>">

            <select name="status">
                <option value=""><?php _e('All Statuses', 'product-launch'); ?></option>
                <option value="completed" <?php selected($status_filter, 'completed'); ?>><?php _e('Completed', 'product-launch'); ?></option>
                <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('Pending', 'product-launch'); ?></option>
                <option value="failed" <?php selected($status_filter, 'failed'); ?>><?php _e('Failed', 'product-launch'); ?></option>
            </select>

            <input type="search"
                   name="s"
                   value="<?php echo esc_attr($search); ?>"
                   placeholder="<?php esc_attr_e('Search business ideas...', 'product-launch'); ?>">

            <button type="submit" class="button"><?php _e('Filter', 'product-launch'); ?></button>

            <?php if ($status_filter || $search) : ?>
                <a href="<?php echo esc_url($list_base_url); ?>" class="button">
                    <?php _e('Clear Filters', 'product-launch'); ?>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="pl-results-info">
        <p><?php printf(esc_html__('Showing %1$d of %2$d validations', 'product-launch'), count($validations), $total_items); ?></p>
    </div>

    <?php if (empty($validations)) : ?>
        <p><?php _e('No validations found.', 'product-launch'); ?></p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;">&nbsp;<?php _e('ID', 'product-launch'); ?></th>
                    <th><?php _e('Business Idea', 'product-launch'); ?></th>
                    <th><?php _e('User', 'product-launch'); ?></th>
                    <th style="width: 80px;">&nbsp;<?php _e('Score', 'product-launch'); ?></th>
                    <th style="width: 100px;">&nbsp;<?php _e('Status', 'product-launch'); ?></th>
                    <th style="width: 140px;">&nbsp;<?php _e('Library', 'product-launch'); ?></th>
                    <th style="width: 120px;">&nbsp;<?php _e('Date', 'product-launch'); ?></th>
                    <th style="width: 210px;">&nbsp;<?php _e('Actions', 'product-launch'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($validations as $validation) : ?>
                    <tr>
                        <td><?php echo esc_html($validation->id); ?></td>
                        <td>
                            <strong><?php echo esc_html(wp_trim_words($validation->business_idea, 15)); ?></strong>
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
                        <td>
                            <?php
                            $is_published = !empty($validation->library_published);
                            $library_classes = 'pl-library-status ' . ($is_published ? 'pl-library-status--published' : 'pl-library-status--draft');
                            ?>
                            <span class="<?php echo esc_attr($library_classes); ?>">
                                <?php echo $is_published ? esc_html__('Published', 'product-launch') : esc_html__('Not Published', 'product-launch'); ?>
                            </span>
                            <?php if ($is_published && !empty($validation->published_at)) : ?>
                                <div class="description">
                                    <?php
                                    /* translators: %s: published date */
                                    printf(esc_html__('since %s', 'product-launch'), esc_html(date_i18n(get_option('date_format'), strtotime($validation->published_at))));
                                    ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($validation->created_at))); ?></td>
                        <td>
                            <a href="#" class="button button-small pl-view-details" data-id="<?php echo esc_attr($validation->id); ?>">
                                <?php _e('View', 'product-launch'); ?>
                            </a>
                            <a href="#" class="button button-small pl-delete-validation" data-id="<?php echo esc_attr($validation->id); ?>">
                                <?php _e('Delete', 'product-launch'); ?>
                            </a>
                            <a href="#"
                               class="button button-small pl-toggle-publish"
                               data-id="<?php echo esc_attr($validation->id); ?>"
                               data-publish="<?php echo $is_published ? 0 : 1; ?>">
                                <?php echo $is_published ? esc_html__('Remove from Library', 'product-launch') : esc_html__('Publish to Library', 'product-launch'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1) : ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base'      => add_query_arg('paged', '%#%'),
                        'format'    => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total'     => $total_pages,
                        'current'   => $current_page,
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
