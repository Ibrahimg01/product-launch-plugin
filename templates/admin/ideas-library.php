<?php
/**
 * Admin Ideas Library Wrapper
 */
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap pl-ideas-library-admin">
    <h1><?php esc_html_e('Ideas Library', 'product-launch'); ?></h1>

    <p class="description">
        <?php esc_html_e('Explore pre-validated business ideas curated by the Product Launch network. Review the insights and push promising concepts directly into your 8-phase launch plan.', 'product-launch'); ?>
    </p>

    <?php if (!empty($library_messages)) : ?>
        <?php foreach ($library_messages as $message) : ?>
            <div class="notice notice-<?php echo esc_attr($message['type']); ?>">
                <p><?php echo esc_html($message['text']); ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($can_manage_library)) : ?>
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

    <?php if (!empty($discovery_url)) : ?>
        <div class="pl-ideas-library-admin__callout" role="region" aria-label="<?php esc_attr_e('Discovery search shortcut', 'product-launch'); ?>">
            <div class="pl-ideas-library-admin__callout-text">
                <strong><?php esc_html_e('Need more ideas to populate the library?', 'product-launch'); ?></strong>
                <p>
                    <?php esc_html_e('Launch a discovery search to generate demo-friendly ideas, validate the scores, and publish them to every subsite in one click.', 'product-launch'); ?>
                </p>
            </div>
            <div class="pl-ideas-library-admin__callout-actions">
                <a class="button button-secondary" href="<?php echo esc_url($discovery_url); ?>">
                    <?php esc_html_e('Open Discovery Search', 'product-launch'); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div class="pl-ideas-library-admin__content">
        <div class="pl-ideas-library-admin__surface">
        <?php
        if (!empty($library_content)) {
            echo $library_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            echo '<div class="notice notice-info"><p>' . esc_html__('No ideas are currently available in the library.', 'product-launch') . '</p></div>';
        }
        ?>
        </div>
    </div>
</div>
