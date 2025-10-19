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

    <div class="pl-network-validation__actions">
        <a class="button button-secondary" href="<?php echo esc_url($dashboard_url); ?>">
            <?php esc_html_e('Validation Dashboard', 'product-launch'); ?>
        </a>
        <a class="button button-primary" href="<?php echo esc_url($ideas_library_url); ?>">
            <?php esc_html_e('Open Ideas Library', 'product-launch'); ?>
        </a>
    </div>

    <div class="pl-network-validation__layout">
        <section class="pl-network-card pl-network-card--primary">
            <h2><?php esc_html_e('Run a Discovery Search', 'product-launch'); ?></h2>
            <p class="description">
                <?php esc_html_e('Describe the problem, niche, or format you want to validate. We will return the strongest matches along with enrichment data so you can publish them to every site.', 'product-launch'); ?>
            </p>

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

            <div id="pl-network-search-feedback" class="notice notice-alt" style="display:none;"></div>

            <div id="pl-network-search-results" class="pl-network-search-results">
                <p class="description">
                    <?php esc_html_e('Run a discovery search to load a batch of pre-validated ideas ready for publishing.', 'product-launch'); ?>
                </p>
            </div>
        </section>

        <aside class="pl-network-card pl-network-card--secondary" aria-label="<?php esc_attr_e('Publishing workflow tips', 'product-launch'); ?>">
            <h2><?php esc_html_e('Suggested Workflow', 'product-launch'); ?></h2>
            <ol class="pl-network-steps">
                <li><?php esc_html_e('Run a discovery search that matches the campaigns you want each subsite to run.', 'product-launch'); ?></li>
                <li><?php esc_html_e('Publish the top-scoring ideas straight into the shared library.', 'product-launch'); ?></li>
                <li><?php esc_html_e('Ask subsites to pull the ideas into their 8-phase launch system and execute.', 'product-launch'); ?></li>
            </ol>

            <div class="pl-network-card__cta">
                <span><?php esc_html_e('Need fresh ideas right away?', 'product-launch'); ?></span>
                <a class="button button-secondary" href="<?php echo esc_url($ideas_library_url); ?>">
                    <?php esc_html_e('Browse Library Catalog', 'product-launch'); ?>
                </a>
            </div>
        </aside>
    </div>

    <div id="pl-network-idea-modal" class="pl-network-idea-modal" aria-hidden="true">
        <div class="pl-network-idea-modal__overlay" role="presentation"></div>
        <div class="pl-network-idea-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="pl-network-idea-modal-title">
            <button type="button" class="pl-network-idea-modal__close" aria-label="<?php esc_attr_e('Close report', 'product-launch'); ?>">&times;</button>
            <div class="pl-network-idea-modal__body">
                <div class="pl-network-idea-modal__content">
                    <header class="pl-network-idea-modal__header">
                        <h2 id="pl-network-idea-modal-title"><?php esc_html_e('Idea report', 'product-launch'); ?></h2>
                        <p class="description"><?php esc_html_e('Select an idea to review the full discovery report.', 'product-launch'); ?></p>
                    </header>
                </div>
            </div>
        </div>
    </div>
</div>
