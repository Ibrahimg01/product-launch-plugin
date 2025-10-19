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
