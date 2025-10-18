<?php
/**
 * Idea Details Template
 */
if (!defined('ABSPATH')) {
    exit;
}

$idea_id = isset($atts['id']) ? sanitize_text_field($atts['id']) : '';
?>

<div class="pl-idea-details-wrapper">
    <div id="pl-idea-content" class="pl-loading-content">
        <div class="pl-spinner-large"></div>
        <p><?php esc_html_e('Loading idea details...', 'product-launch'); ?></p>
    </div>
</div>

<script>
(function($) {
    $(document).ready(function() {
        if (typeof plLibrary === 'undefined') {
            return;
        }

        const ideaId = '<?php echo esc_js($idea_id); ?>';
        const isLoggedIn = typeof plValidationFrontend !== 'undefined' ? plValidationFrontend.isLoggedIn : false;
        const loginUrl = typeof plValidationFrontend !== 'undefined' ? plValidationFrontend.loginUrl : '#';
        const backLinkTemplate = function() {
            if (plLibrary.libraryUrl && plLibrary.libraryUrl.length) {
                return '<a href="' + plLibrary.libraryUrl + '" class="pl-back-link">← <?php echo esc_js(__('Back to Library', 'product-launch')); ?></a>';
            }

            return '<a href="javascript:history.back()" class="pl-back-link">← <?php echo esc_js(__('Back to Library', 'product-launch')); ?></a>';
        };

        if (!ideaId) {
            showError('<?php echo esc_js(__('Invalid idea ID.', 'product-launch')); ?>');
            return;
        }

        $.ajax({
            url: plLibrary.ajaxurl,
            type: 'POST',
            data: {
                action: 'pl_get_idea_details',
                nonce: plLibrary.nonce,
                idea_id: ideaId
            }
        }).done(function(response) {
            if (response && response.success && response.data && response.data.idea) {
                renderIdeaDetails(response.data.idea);
            } else {
                const message = response && response.data && response.data.message ? response.data.message : '<?php echo esc_js(__('Idea not found.', 'product-launch')); ?>';
                showError(message);
            }
        }).fail(function() {
            showError('<?php echo esc_js(__('Failed to load idea details.', 'product-launch')); ?>');
        });

        function renderIdeaDetails(idea) {
            const score = idea.adjusted_score || idea.validation_score || 0;
            const scoreClass = score >= 70 ? 'high' : (score >= 40 ? 'medium' : 'low');
            const scoreColor = score >= 70 ? '#10B981' : (score >= 40 ? '#F59E0B' : '#EF4444');

            const buttonHtml = isLoggedIn
                ? '<button class="button button-primary button-large pl-push-to-phases" data-idea-id="' + ideaId + '" data-source-type="library">🚀 <?php echo esc_js(__('Push to 8-Phase System', 'product-launch')); ?></button>'
                : '<a href="' + loginUrl + '" class="button button-primary button-large"><?php echo esc_js(__('Log In to Use This Idea', 'product-launch')); ?></a>';

            const ctaButton = isLoggedIn
                ? '<button class="button button-primary button-large pl-push-to-phases" data-idea-id="' + ideaId + '" data-source-type="library"><?php echo esc_js(__('Start 8-Phase Launch Process →', 'product-launch')); ?></button>'
                : '<a href="' + loginUrl + '" class="button button-primary button-large"><?php echo esc_js(__('Log In to Get Started →', 'product-launch')); ?></a>';

            const html =
                '<div class="pl-idea-detail-header">' +
                    backLinkTemplate() +
                    '<div class="pl-detail-hero pl-score-' + scoreClass + '">' +
                        '<div class="pl-hero-content">' +
                            '<h1>' + escapeHtml(idea.business_idea || '<?php echo esc_js(__('Business Idea', 'product-launch')); ?>') + '</h1>' +
                            (idea.validation_date ? '<p class="pl-validation-date"><?php echo esc_js(__('Validated on', 'product-launch')); ?> ' + formatDate(idea.validation_date) + '</p>' : '') +
                        '</div>' +
                        '<div class="pl-hero-score">' +
                            '<div class="pl-score-circle-large" style="--score-color: ' + scoreColor + '; --score-percent: ' + score + '">' +
                                '<div class="pl-score-inner">' +
                                    '<span class="pl-score-value">' + escapeHtml(String(score)) + '</span>' +
                                    '<span class="pl-score-max">/100</span>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="pl-detail-actions">' +
                        buttonHtml +
                    '</div>' +
                '</div>' +
                renderScoreBreakdown(idea) +
                renderAnalysis(idea) +
                '<div class="pl-detail-cta">' +
                    '<h3><?php echo esc_js(__('Ready to Launch This Idea?', 'product-launch')); ?></h3>' +
                    '<p><?php echo esc_js(__('Push this validated idea to our 8-phase launch system and start building your business today.', 'product-launch')); ?></p>' +
                    ctaButton +
                '</div>';

            $('#pl-idea-content').removeClass('pl-loading-content').html(html);
            attachPushHandler();
        }

        function renderScoreBreakdown(idea) {
            if (!idea.scoring_breakdown) {
                return '';
            }

            let html = '<div class="pl-detail-section">' +
                '<h2 class="pl-section-title"><span class="pl-section-icon">📊</span><?php echo esc_js(__('Score Breakdown', 'product-launch')); ?></h2>' +
                '<div class="pl-breakdown-grid">';

            Object.keys(idea.scoring_breakdown).forEach(function(key) {
                const value = idea.scoring_breakdown[key];
                const label = key.replace(/_/g, ' ').replace(/score/gi, '').trim();
                html += '<div class="pl-breakdown-card">' +
                    '<div class="pl-breakdown-header">' +
                        '<span class="pl-breakdown-name">' + escapeHtml(label) + '</span>' +
                        '<span class="pl-breakdown-score">' + escapeHtml(String(value)) + '%</span>' +
                    '</div>' +
                    '<div class="pl-breakdown-progress">' +
                        '<div class="pl-breakdown-bar" style="width: ' + Number(value) + '%"></div>' +
                    '</div>' +
                '</div>';
            });

            html += '</div></div>';
            return html;
        }

        function renderAnalysis(idea) {
            let html = '<div class="pl-detail-section">' +
                '<h2 class="pl-section-title"><span class="pl-section-icon">🔍</span><?php echo esc_js(__('AI Analysis', 'product-launch')); ?></h2>';

            if (idea.ai_assessment_summary) {
                html += '<div class="pl-analysis-box">' +
                    '<h4><?php echo esc_js(__('Assessment Summary', 'product-launch')); ?></h4>' +
                    '<p>' + escapeHtml(idea.ai_assessment_summary) + '</p>' +
                '</div>';
            }

            if (idea.market_validation_summary) {
                html += '<div class="pl-analysis-box">' +
                    '<h4><?php echo esc_js(__('Market Validation', 'product-launch')); ?></h4>' +
                    '<p>' + escapeHtml(idea.market_validation_summary) + '</p>' +
                '</div>';
            }

            html += '</div>';
            return html;
        }

        function attachPushHandler() {
            $('.pl-push-to-phases').off('click').on('click', function(e) {
                e.preventDefault();

                const button = $(this);
                const ideaIdValue = button.data('idea-id');
                const sourceType = button.data('source-type');

                if (!confirm('<?php echo esc_js(__('Push this idea to the 8-phase launch system? This will create a new project with pre-filled data.', 'product-launch')); ?>')) {
                    return;
                }

                button.prop('disabled', true).text('<?php echo esc_js(__('Pushing to system...', 'product-launch')); ?>');

                $.ajax({
                    url: plLibrary.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'pl_push_to_phases',
                        nonce: plLibrary.nonce,
                        idea_id: ideaIdValue,
                        source_type: sourceType
                    }
                }).done(function(response) {
                    if (response && response.success) {
                        alert(response.data.message);
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        } else {
                            button.prop('disabled', false).text('🚀 <?php echo esc_js(__('Push to 8-Phase System', 'product-launch')); ?>');
                        }
                    } else {
                        const message = response && response.data && response.data.message ? response.data.message : '<?php echo esc_js(__('Failed to push idea. Please try again.', 'product-launch')); ?>';
                        alert(message);
                        button.prop('disabled', false).text('🚀 <?php echo esc_js(__('Push to 8-Phase System', 'product-launch')); ?>');
                    }
                }).fail(function() {
                    alert('<?php echo esc_js(__('Failed to push idea. Please try again.', 'product-launch')); ?>');
                    button.prop('disabled', false).text('🚀 <?php echo esc_js(__('Push to 8-Phase System', 'product-launch')); ?>');
                });
            });
        }

        function showError(message) {
            $('#pl-idea-content').html(
                '<div class="pl-error-message">' +
                    '<div class="pl-error-icon">⚠️</div>' +
                    '<p>' + escapeHtml(message) + '</p>' +
                    '<a href="javascript:history.back()" class="button"><?php echo esc_js(__('Go Back', 'product-launch')); ?></a>' +
                '</div>'
            );
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) {
                return escapeHtml(dateString);
            }
            return date.toLocaleDateString();
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    });
})(jQuery);
</script>
