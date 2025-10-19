<?php
/**
 * Network idea discovery interface.
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap pl-network-validation">
    <h1><?php esc_html_e('Idea Discovery & Library Publishing', 'product-launch'); ?></h1>
    <p class="pl-network-validation__intro">
        <?php esc_html_e('Launch API-powered discovery runs to surface niched digital product opportunities. Push the winning, pre-validated ideas directly into the network library for every subsite to deploy.', 'product-launch'); ?>
    </p>

    <div class="pl-network-validation__links">
        <a class="button" href="<?php echo esc_url($dashboard_url); ?>">
            <?php esc_html_e('View Validation Dashboard', 'product-launch'); ?>
        </a>
        <a class="button button-primary" href="<?php echo esc_url($ideas_library_url); ?>">
            <?php esc_html_e('Open Ideas Library', 'product-launch'); ?>
        </a>
    </div>

    <form id="pl-network-search-form" class="pl-network-search-form" action="#" method="post">
        <div class="pl-network-search-fields">
            <div class="pl-field">
                <label for="pl-network-search-query"><?php esc_html_e('Search topic or niche', 'product-launch'); ?></label>
                <input type="text" id="pl-network-search-query" name="query" placeholder="<?php echo esc_attr__('e.g. AI marketing automation for coaches', 'product-launch'); ?>" required />
            </div>
            <div class="pl-field">
                <label for="pl-network-search-min-score"><?php esc_html_e('Minimum score', 'product-launch'); ?></label>
                <input type="number" id="pl-network-search-min-score" name="min_score" min="0" max="100" step="5" value="60" />
            </div>
            <div class="pl-field">
                <label for="pl-network-search-limit"><?php esc_html_e('Results', 'product-launch'); ?></label>
                <select id="pl-network-search-limit" name="limit">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="15">15</option>
                    <option value="20">20</option>
                </select>
            </div>
            <div class="pl-field pl-field--toggle">
                <label for="pl-network-search-enriched">
                    <input type="checkbox" id="pl-network-search-enriched" name="enriched_only" value="1" checked />
                    <?php esc_html_e('Require enriched insights', 'product-launch'); ?>
                </label>
            </div>
        </div>
        <div class="pl-network-search-actions">
            <button type="submit" class="button button-primary">
                <?php esc_html_e('Run Discovery Search', 'product-launch'); ?>
            </button>
            <span class="pl-network-search-status" id="pl-network-search-status" aria-live="polite"></span>
        </div>
    </form>

    <div id="pl-network-search-feedback" class="notice" style="display:none;"></div>

    <div id="pl-network-search-results" class="pl-network-search-results">
        <p class="description">
            <?php esc_html_e('Run a discovery search to load a batch of pre-validated ideas ready for publishing.', 'product-launch'); ?>
        </p>
    </div>
</div>
