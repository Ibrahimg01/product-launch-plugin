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

    <div class="pl-ideas-library-admin__content">
        <?php
        if (!empty($library_content)) {
            echo $library_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            echo '<div class="notice notice-info"><p>' . esc_html__('No ideas are currently available in the library.', 'product-launch') . '</p></div>';
        }
        ?>
    </div>
</div>
