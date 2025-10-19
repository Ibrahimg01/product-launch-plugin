<?php
/**
 * Ideas Library Template
 */
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="pl-ideas-library-wrapper">

    <!-- Header -->
    <div class="pl-library-header">
        <div class="pl-library-intro">
            <h1 class="pl-library-title"><?php esc_html_e('Pre-Validated Business Ideas', 'product-launch'); ?></h1>
            <p class="pl-library-description">
                <?php esc_html_e('Browse through AI-validated business opportunities complete with market insights, competitive analysis, and launch strategies.', 'product-launch'); ?>
            </p>
        </div>

        <?php if ('yes' === $atts['show_filters']) : ?>
            <div class="pl-library-filters">
                <div class="pl-filter-row">
                    <div class="pl-filter-search">
                        <input type="text"
                               id="pl-library-search"
                               placeholder="<?php echo esc_attr__('Search business ideas...', 'product-launch'); ?>"
                               class="pl-search-input">
                        <button type="button" id="pl-search-btn" class="pl-search-btn">
                            <span class="dashicons dashicons-search"></span>
                        </button>
                    </div>

                    <div class="pl-filter-selects">
                        <select id="pl-category-filter" class="pl-filter-select">
                            <option value=""><?php esc_html_e('All Categories', 'product-launch'); ?></option>
                            <?php if (!empty($categories) && is_array($categories)) : ?>
                                <?php foreach ($categories as $category) : ?>
                                    <option value="<?php echo esc_attr($category['slug']); ?>">
                                        <?php echo esc_html($category['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>

                        <select id="pl-sort-filter" class="pl-filter-select">
                            <option value="score_desc"><?php esc_html_e('Highest Score', 'product-launch'); ?></option>
                            <option value="score_asc"><?php esc_html_e('Lowest Score', 'product-launch'); ?></option>
                            <option value="date_desc"><?php esc_html_e('Newest First', 'product-launch'); ?></option>
                            <option value="date_asc"><?php esc_html_e('Oldest First', 'product-launch'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="pl-filter-tags" id="pl-active-filters" style="display: none;">
                    <span class="pl-filter-label"><?php esc_html_e('Active filters:', 'product-launch'); ?></span>
                    <div class="pl-filter-tags-list"></div>
                    <button type="button" class="pl-clear-filters"><?php esc_html_e('Clear all', 'product-launch'); ?></button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Ideas Grid -->
    <div id="pl-library-grid" class="pl-library-grid">
        <!-- Loading skeleton -->
        <div class="pl-loading-grid">
            <?php for ($i = 0; $i < (int) $atts['per_page']; $i++) : ?>
                <div class="pl-idea-skeleton">
                    <div class="pl-skeleton-header"></div>
                    <div class="pl-skeleton-line"></div>
                    <div class="pl-skeleton-line short"></div>
                    <div class="pl-skeleton-footer"></div>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Pagination -->
    <div id="pl-library-pagination" class="pl-library-pagination" style="display: none;"></div>

    <!-- Stats -->
    <div class="pl-library-stats">
        <span id="pl-showing-count"></span>
        <span id="pl-total-count"></span>
    </div>
</div>

<script>
(function($) {
    $(document).ready(function() {
        if (typeof plLibrary === 'undefined') {
            return;
        }

        const ajaxUrl = plLibrary.ajaxurl || plLibrary.ajaxurlRelative || (typeof window.ajaxurl !== 'undefined' ? window.ajaxurl : '');
        if (!ajaxUrl) {
            console.error('Product Launch: Unable to determine AJAX URL for ideas library.');
            return;
        }

        const perPage = plLibrary.perPage ? parseInt(plLibrary.perPage, 10) : 12;
        const baseDetailsUrl = plLibrary.detailsUrl || '?idea_id=__IDEA_ID__';
        const categoryLabels = (plLibrary.categoryLabels && typeof plLibrary.categoryLabels === 'object')
            ? plLibrary.categoryLabels
            : {};
        const categorySelect = $('#pl-category-filter');
        const sortSelect = $('#pl-sort-filter');
        const categoryFilterEnabled = categorySelect.length > 0;
        const sortFilterEnabled = sortSelect.length > 0;
        const defaultSort = sortFilterEnabled ? (sortSelect.val() || 'score_desc') : 'score_desc';

        let currentPage = 1;
        let currentFilters = {
            search: '',
            category: categoryFilterEnabled ? (categorySelect.val() || '') : '',
            sort: defaultSort
        };

        loadIdeas();

        $('#pl-search-btn').on('click', function() {
            currentFilters.search = $('#pl-library-search').val();
            currentPage = 1;
            loadIdeas();
        });

        $('#pl-library-search').on('keypress', function(e) {
            if (e.which === 13) {
                $('#pl-search-btn').trigger('click');
            }
        });

        if (categoryFilterEnabled || sortFilterEnabled) {
            $('#pl-category-filter, #pl-sort-filter').on('change', function() {
                currentFilters.category = categoryFilterEnabled ? (categorySelect.val() || '') : '';
                currentFilters.sort = sortFilterEnabled ? (sortSelect.val() || defaultSort) : defaultSort;
                currentPage = 1;
                loadIdeas();
            });
        }

        $(document).on('click', '.pl-clear-filters', function() {
            $('#pl-library-search').val('');
            if (categoryFilterEnabled) {
                categorySelect.val('');
            }
            if (sortFilterEnabled) {
                sortSelect.val(defaultSort);
            }
            currentFilters = { search: '', category: '', sort: defaultSort };
            currentPage = 1;
            loadIdeas();
        });

        function loadIdeas() {
            $('#pl-library-grid').html('<div class="pl-loading-grid">' + generateSkeletons() + '</div>');

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'pl_load_library_ideas',
                    nonce: plLibrary.nonce,
                    page: currentPage,
                    per_page: perPage,
                    min_score: plLibrary.minScore,
                    enriched_only: plLibrary.enrichedOnly,
                    search: currentFilters.search,
                    category: categoryFilterEnabled ? currentFilters.category : '',
                    sort: currentFilters.sort
                }
            }).done(function(response) {
                if (response && response.success) {
                    renderIdeas(response.data);
                    updateStats(response.data);
                    renderPagination(response.data);
                    updateActiveFilters();
                } else {
                    const message = response && response.data && response.data.message ? response.data.message : '<?php echo esc_js(__('Failed to load ideas from library.', 'product-launch')); ?>';
                    showError(message);
                }
            }).fail(function() {
                showError('<?php echo esc_js(__('Failed to load ideas. Please try again.', 'product-launch')); ?>');
            });
        }

        function renderIdeas(data) {
            const ideas = data && Array.isArray(data.items) ? data.items : [];

            if (!ideas.length) {
                $('#pl-library-grid').html(
                    '<div class="pl-no-results">' +
                        '<div class="pl-no-results-icon">🔍</div>' +
                        '<h3><?php echo esc_js(__('No ideas found', 'product-launch')); ?></h3>' +
                        '<p><?php echo esc_js(__('Try adjusting your filters or search terms.', 'product-launch')); ?></p>' +
                    '</div>'
                );
                return;
            }

            const cards = ideas.map(function(idea) {
                return renderIdeaCard(idea);
            }).join('');

            $('#pl-library-grid').html(cards);
        }

        function renderIdeaCard(idea) {
            const ideaId = idea.id || idea.external_api_id || '';
            const score = idea.adjusted_score || idea.validation_score || 0;
            const scoreClass = score >= 70 ? 'high' : (score >= 40 ? 'medium' : 'low');
            const ideaText = idea.business_idea || '';
            const truncated = ideaText.length > 120 ? ideaText.substring(0, 120) + '…' : ideaText;

            const detailUrl = buildDetailsUrl(ideaId);
            const sourceType = idea.source_type || 'library';

            const primaryCategory = getPrimaryCategory(idea);

            const badgesHtmlParts = [];
            if (primaryCategory) {
                badgesHtmlParts.push('<span class="pl-category-badge">' + escapeHtml(primaryCategory) + '</span>');
            }
            if (idea.enriched) {
                badgesHtmlParts.push('<span class="pl-enriched-badge">✨ <?php echo esc_js(__('Enriched', 'product-launch')); ?></span>');
            }
            const badgesHtml = badgesHtmlParts.length
                ? '<div class="pl-idea-badges">' + badgesHtmlParts.join('') + '</div>'
                : '';

            let tagsHtml = '';
            if (Array.isArray(idea.category_labels) && idea.category_labels.length) {
                tagsHtml = '<div class="pl-idea-tags">' +
                    idea.category_labels.slice(0, 3).map(function(label) {
                        return '<span class="pl-tag">' + escapeHtml(label) + '</span>';
                    }).join('') +
                    '</div>';
            } else if (idea.classification && Array.isArray(idea.classification.industries)) {
                tagsHtml = '<div class="pl-idea-tags">' +
                    idea.classification.industries.slice(0, 3).map(function(ind) {
                        return '<span class="pl-tag">' + escapeHtml(ind) + '</span>';
                    }).join('') +
                    '</div>';
            }

            let metricsHtml = '';
            if (idea.metrics) {
                const parts = [];
                if (idea.metrics.total_search_volume) {
                    parts.push(
                        '<span class="pl-metric" title="<?php echo esc_js(__('Monthly search volume', 'product-launch')); ?>">' +
                            '<span class="pl-metric-icon">🔍</span>' +
                            '<span>' + formatNumber(idea.metrics.total_search_volume) + '</span>' +
                        '</span>'
                    );
                }
                if (idea.metrics.competitors_analyzed) {
                    parts.push(
                        '<span class="pl-metric" title="<?php echo esc_js(__('Competitors analyzed', 'product-launch')); ?>">' +
                            '<span class="pl-metric-icon">🏢</span>' +
                            '<span>' + escapeHtml(String(idea.metrics.competitors_analyzed)) + '</span>' +
                        '</span>'
                    );
                }

                if (parts.length) {
                    metricsHtml = '<div class="pl-idea-metrics">' + parts.join('') + '</div>';
                }
            }

            return (
                '<div class="pl-idea-card" data-id="' + escapeAttribute(ideaId) + '" data-source-type="' + escapeAttribute(sourceType) + '">' +
                    '<div class="pl-idea-header">' +
                        '<div class="pl-idea-score pl-score-' + scoreClass + '">' +
                            '<span class="pl-score-number">' + escapeHtml(String(score)) + '</span>' +
                            '<span class="pl-score-label"><?php echo esc_js(__('Score', 'product-launch')); ?></span>' +
                        '</div>' +
                        badgesHtml +
                    '</div>' +
                    '<div class="pl-idea-body">' +
                        '<h3 class="pl-idea-title">' + escapeHtml(truncated) + '</h3>' +
                        tagsHtml +
                        metricsHtml +
                    '</div>' +
                    '<div class="pl-idea-footer">' +
                        '<a href="' + detailUrl + '" class="pl-view-details-btn">' +
                            '<?php echo esc_js(__('View Full Report →', 'product-launch')); ?>' +
                        '</a>' +
                    '</div>' +
                '</div>'
            );
        }

        function buildDetailsUrl(ideaId) {
            if (!ideaId) {
                return '#';
            }

            if (baseDetailsUrl.indexOf('__IDEA_ID__') !== -1) {
                return baseDetailsUrl.replace('__IDEA_ID__', encodeURIComponent(ideaId));
            }

            const hasQuery = baseDetailsUrl.indexOf('?') !== -1;
            const separator = hasQuery ? '&' : '?';
            return baseDetailsUrl + separator + 'idea_id=' + encodeURIComponent(ideaId);
        }

        function formatNumber(num) {
            const number = parseFloat(num);
            if (!number) {
                return '0';
            }
            if (number >= 1000000) {
                return (number / 1000000).toFixed(1) + 'M';
            }
            if (number >= 1000) {
                return (number / 1000).toFixed(1) + 'K';
            }
            return String(number);
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function escapeAttribute(str) {
            return escapeHtml(str).replace(/"/g, '&quot;');
        }

        function generateSkeletons() {
            let html = '';
            for (let i = 0; i < perPage; i++) {
                html += (
                    '<div class="pl-idea-skeleton">' +
                        '<div class="pl-skeleton-header"></div>' +
                        '<div class="pl-skeleton-line"></div>' +
                        '<div class="pl-skeleton-line short"></div>' +
                        '<div class="pl-skeleton-footer"></div>' +
                    '</div>'
                );
            }
            return html;
        }

        function renderPagination(data) {
            const totalPages = data && data.total_pages ? parseInt(data.total_pages, 10) : 1;

            if (!totalPages || totalPages <= 1) {
                $('#pl-library-pagination').hide();
                return;
            }

            let html = '<div class="pl-pagination-controls">';

            if (currentPage > 1) {
                html += '<button class="pl-page-btn" data-page="' + (currentPage - 1) + '">← <?php echo esc_js(__('Previous', 'product-launch')); ?></button>';
            }

            html += '<div class="pl-page-numbers">';
            const maxPagesToShow = Math.min(totalPages, 10);
            for (let i = 1; i <= maxPagesToShow; i++) {
                html += '<button class="pl-page-num ' + (i === currentPage ? 'active' : '') + '" data-page="' + i + '">' + i + '</button>';
            }
            html += '</div>';

            if (currentPage < totalPages) {
                html += '<button class="pl-page-btn" data-page="' + (currentPage + 1) + '"><?php echo esc_js(__('Next →', 'product-launch')); ?></button>';
            }

            html += '</div>';

            $('#pl-library-pagination').html(html).show();

            $('.pl-page-btn, .pl-page-num').off('click').on('click', function() {
                currentPage = parseInt($(this).data('page'), 10);
                loadIdeas();
                $('html, body').animate({ scrollTop: 0 }, 300);
            });
        }

        function updateStats(data) {
            const showing = data && data.items ? data.items.length : 0;
            const total = data && data.total ? data.total : 0;

            $('#pl-showing-count').text('<?php echo esc_js(__('Showing', 'product-launch')); ?> ' + showing + ' <?php echo esc_js(__('ideas', 'product-launch')); ?>');
            $('#pl-total-count').text('<?php echo esc_js(__('of', 'product-launch')); ?> ' + total + ' <?php echo esc_js(__('total', 'product-launch')); ?>');
        }

        function updateActiveFilters() {
            const hasFilters = (currentFilters.search && currentFilters.search.length) || (categoryFilterEnabled && currentFilters.category);

            if (!hasFilters) {
                $('#pl-active-filters').hide();
                return;
            }

            let html = '';
            if (currentFilters.search) {
                html += '<span class="pl-filter-tag"><?php echo esc_js(__('Search:', 'product-launch')); ?> "' + escapeHtml(currentFilters.search) + '"</span>';
            }
            if (currentFilters.category) {
                const label = categoryLabels[currentFilters.category] || currentFilters.category;
                html += '<span class="pl-filter-tag"><?php echo esc_js(__('Category:', 'product-launch')); ?> ' + escapeHtml(label) + '</span>';
            }

            $('#pl-active-filters .pl-filter-tags-list').html(html);
            $('#pl-active-filters').show();
        }

        function showError(message) {
            $('#pl-library-grid').html(
                '<div class="pl-error-message">' +
                    '<div class="pl-error-icon">⚠️</div>' +
                    '<p>' + escapeHtml(message) + '</p>' +
                '</div>'
            );
        }

        function getPrimaryCategory(idea) {
            if (Array.isArray(idea.category_labels) && idea.category_labels.length) {
                return idea.category_labels[0];
            }

            if (idea.classification && Array.isArray(idea.classification.industries) && idea.classification.industries.length) {
                return idea.classification.industries[0];
            }

            if (idea.category) {
                const slug = String(idea.category);
                return categoryLabels[slug] || slug;
            }

            return '';
        }
    });
})(jQuery);
</script>
