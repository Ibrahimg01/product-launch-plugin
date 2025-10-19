<?php
/**
 * Admin AI Validation intake page.
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap pl-ai-validation-admin">
    <h1><?php esc_html_e('AI Validation', 'product-launch'); ?></h1>

    <p><?php esc_html_e('Submit an idea to generate a normalized validation report and optional phase prefill data.', 'product-launch'); ?></p>

    <div id="is-admin-notices"></div>

    <form id="is-validate-form" class="is-validate-form">
        <input type="text" name="title" placeholder="<?php echo esc_attr__('Idea title *', 'product-launch'); ?>" required />
        <textarea name="description" placeholder="<?php echo esc_attr__('1–2 paragraph description', 'product-launch'); ?>"></textarea>
        <input type="text" name="audience" placeholder="<?php echo esc_attr__('Target audience', 'product-launch'); ?>" />
        <input type="text" name="problem" placeholder="<?php echo esc_attr__('Core problem', 'product-launch'); ?>" />
        <input type="text" name="outcome" placeholder="<?php echo esc_attr__('Desired outcome', 'product-launch'); ?>" />
        <button class="button button-primary" type="submit"><?php esc_html_e('Validate Idea', 'product-launch'); ?></button>
    </form>

    <div id="is-validate-result" class="is-validate-result" hidden></div>

    <?php include PL_PLUGIN_DIR . 'templates/admin/partials/ideas-modals.php'; ?>
</div>
