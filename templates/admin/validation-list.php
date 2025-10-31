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

    <?php if (!empty($selected_validation_error)) : ?>
        <div class="notice notice-error">
            <p><?php echo esc_html($selected_validation_error); ?></p>
        </div>
    <?php elseif (!empty($selected_validation)) : ?>
        <div class="pl-validation-report-container">
            <?php $validation = $selected_validation; ?>
            <?php include PL_PLUGIN_DIR . 'templates/admin/partials/validation-report-v3.php'; ?>
            <?php unset($validation); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($can_manage_library)) : ?>
        <?php if (!empty($library_messages)) : ?>
            <?php foreach ($library_messages as $message) : ?>
                <div class="notice notice-<?php echo esc_attr($message['type']); ?>">
                    <p><?php echo esc_html($message['text']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="pl-ideas-library-admin__content">
            <div class="pl-ideas-library-admin__surface">
                <h2><?php esc_html_e('Manage Library Categories', 'product-launch'); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field('pl_library_categories_manage'); ?>
                    <p class="description">
                        <?php esc_html_e('Control which categories appear in the ideas library filter. Enter one category per line using the format "Label|slug". The slug will be generated automatically if omitted.', 'product-launch'); ?>
                    </p>
                    <textarea name="pl_library_categories_raw" rows="6" cols="60" class="large-text code"><?php echo esc_textarea($categories_text); ?></textarea>
                    <p>
                        <input type="submit" name="pl_library_categories_submit" class="button button-primary" value="<?php esc_attr_e('Save Categories', 'product-launch'); ?>">
                    </p>
                </form>
            </div>

            <div class="pl-ideas-library-admin__surface">
                <h2><?php esc_html_e('Assign Categories to Published Ideas', 'product-launch'); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field('pl_library_assignments'); ?>
                    <p class="description">
                        <?php esc_html_e('Override the default categories for network published ideas. Select the most relevant categories for each card. Leave blank to fall back to automatic classification.', 'product-launch'); ?>
                    </p>

                    <?php if (empty($assignable_validations)) : ?>
                        <p><?php esc_html_e('No published ideas are available for manual categorization yet.', 'product-launch'); ?></p>
                    <?php else : ?>
                        <table class="widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Idea', 'product-launch'); ?></th>
                                    <th style="width:220px;">
                                        <?php esc_html_e('Categories', 'product-launch'); ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignable_validations as $validation) : ?>
                                    <?php
                                    $idea_key = 'local-' . (int) $validation->id;
                                    $selected_slugs = isset($category_map[$idea_key]) ? (array) $category_map[$idea_key] : array();
                                    $size = max(3, min(6, count($categories)));
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html(wp_trim_words($validation->business_idea, 18)); ?></strong>
                                            <p class="description">
                                                <?php
                                                /* translators: %s: validation score */
                                                printf(esc_html__('Score: %s', 'product-launch'), esc_html($validation->validation_score));
                                                ?>
                                            </p>
                                        </td>
                                        <td>
                                            <input type="hidden" name="library_assignments[<?php echo esc_attr($idea_key); ?>][]" value="">
                                            <select name="library_assignments[<?php echo esc_attr($idea_key); ?>][]" multiple size="<?php echo esc_attr($size); ?>" class="pl-library-category-select">
                                                <?php foreach ($categories as $category) : ?>
                                                    <option value="<?php echo esc_attr($category['slug']); ?>" <?php selected(in_array($category['slug'], $selected_slugs, true)); ?>>
                                                        <?php echo esc_html($category['label']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p>
                            <input type="submit" name="pl_library_assignments_submit" class="button button-primary" value="<?php esc_attr_e('Save Assignments', 'product-launch'); ?>">
                        </p>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    <?php elseif (!empty($library_management_restricted)) : ?>
        <div class="notice notice-info">
            <p>
                <?php esc_html_e('Ideas library categories are managed from the Network Admin dashboard. Please contact a super admin to request updates.', 'product-launch'); ?>
            </p>
        </div>
    <?php endif; ?>

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
                            <a href="<?php echo esc_url(add_query_arg('validation_id', (int) $validation->id, $list_base_url)); ?>" class="button button-small pl-view-details">
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
