<?php
/**
 * Shared modals for Ideas admin screens.
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="is-report-modal" class="is-modal" hidden>
    <div class="is-modal-inner">
        <p><?php esc_html_e('Loading…', 'product-launch'); ?></p>
    </div>
</div>

<div id="is-push-modal" class="is-modal" hidden>
    <div class="is-modal-inner">
        <h3><?php esc_html_e('Push to 8 Phases', 'product-launch'); ?></h3>
        <label class="is-overwrite">
            <input type="checkbox" id="is-overwrite" />
            <?php esc_html_e('Overwrite existing fields', 'product-launch'); ?>
        </label>
        <div id="is-field-list">
            <p><?php esc_html_e('Loading…', 'product-launch'); ?></p>
        </div>
        <div class="is-modal-actions">
            <button type="button" class="button is-cancel"><?php esc_html_e('Cancel', 'product-launch'); ?></button>
            <button type="button" class="button button-primary is-apply"><?php esc_html_e('Apply', 'product-launch'); ?></button>
        </div>
    </div>
</div>
