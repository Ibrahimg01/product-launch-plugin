jQuery(document).ready(function ($) {
    $('#pl-test-api-connection').on('click', function () {
        const button = $(this);
        const result = $('#pl-api-status-result');

        button.prop('disabled', true).text('Testing...');
        result.removeClass('success error').hide();

        $.ajax({
            url: plValidation.ajaxurl,
            type: 'POST',
            data: {
                action: 'pl_test_api_connection',
                nonce: plValidation.nonce,
            },
            success(response) {
                if (response.success) {
                    result
                        .addClass('success')
                        .html('<strong>✓ Connection successful!</strong><br>' + response.data.message)
                        .show();
                } else {
                    result
                        .addClass('error')
                        .html('<strong>✗ Connection failed!</strong><br>' + response.data.message)
                        .show();
                }
            },
            error() {
                result
                    .addClass('error')
                    .html('<strong>✗ Connection failed!</strong><br>Unable to reach the server.')
                    .show();
            },
            complete() {
                button.prop('disabled', false).text('Test Connection');
            },
        });
    });

    $(document).on('click', '.pl-delete-validation', function (e) {
        e.preventDefault();

        if (!window.confirm('Are you sure you want to delete this validation? This action cannot be undone.')) {
            return;
        }

        const button = $(this);
        const validationId = button.data('id');
        const row = button.closest('tr');

        button.prop('disabled', true).text('Deleting...');

        $.ajax({
            url: plValidation.ajaxurl,
            type: 'POST',
            data: {
                action: 'pl_delete_validation',
                nonce: plValidation.nonce,
                validation_id: validationId,
            },
            success(response) {
                if (response.success) {
                    row.fadeOut(300, function () {
                        $(this).remove();

                        $('<div class="notice notice-success is-dismissible"><p>Validation deleted successfully!</p></div>')
                            .insertAfter('.wp-heading-inline')
                            .delay(3000)
                            .fadeOut();
                    });
                } else {
                    window.alert('Error: ' + response.data.message);
                    button.prop('disabled', false).text('Delete');
                }
            },
            error() {
                window.alert('Error: Unable to delete validation.');
                button.prop('disabled', false).text('Delete');
            },
        });
    });

    $(document).on('click', '.pl-view-details', function (e) {
        e.preventDefault();
        const validationId = $(this).data('id');
        window.alert('View details functionality will be implemented in Phase 4. Validation ID: ' + validationId);
    });

    $(document).on('click', '.pl-toggle-publish', function (e) {
        e.preventDefault();

        const button = $(this);
        const validationId = button.data('id');
        const publish = parseInt(button.data('publish'), 10) === 1;
        const row = button.closest('tr');
        const badge = row.find('.pl-library-status');

        button.prop('disabled', true).text(publish ? 'Publishing…' : 'Unpublishing…');

        $.ajax({
            url: plValidation.ajaxurl,
            type: 'POST',
            data: {
                action: 'pl_toggle_validation_publish',
                nonce: plValidation.nonce,
                validation_id: validationId,
                publish: publish ? 1 : 0,
            },
        }).done(function (response) {
            if (!response || !response.success) {
                const message = response && response.data && response.data.message
                    ? response.data.message
                    : 'Unable to update publish status.';
                window.alert(message);
                return;
            }

            const nextPublishState = publish ? 0 : 1;
            button.data('publish', nextPublishState);
            button.prop('disabled', false).text(nextPublishState === 1 ? 'Publish to Library' : 'Remove from Library');

            if (badge.length) {
                if (publish) {
                    badge
                        .removeClass('pl-library-status--draft')
                        .addClass('pl-library-status--published')
                        .text('Published');
                } else {
                    badge
                        .removeClass('pl-library-status--published')
                        .addClass('pl-library-status--draft')
                        .text('Not Published');
                }
            }

            $('.notice.pl-inline-feedback').remove();
            $('<div class="notice notice-success is-dismissible pl-inline-feedback"><p>' + response.data.message + '</p></div>')
                .insertAfter('.wp-heading-inline')
                .delay(3000)
                .fadeOut();
        }).fail(function () {
            window.alert('Unable to update publish status.');
        }).always(function () {
            if (button.prop('disabled')) {
                button.prop('disabled', false).text(publish ? 'Publish to Library' : 'Remove from Library');
            }
        });
    });

    const networkWrapper = $('.pl-network-validation');
    if (networkWrapper.length && typeof plValidation !== 'undefined') {
        const networkStrings = plValidation.networkStrings || {};
        const networkLinks = plValidation.networkLinks || {};
        const networkNonce = plValidation.networkNonce || '';

        let discoveryResults = [];

        const form = $('#pl-network-search-form');
        const resultsContainer = $('#pl-network-search-results');
        const feedback = $('#pl-network-search-feedback');
        const status = $('#pl-network-search-status');
        const modal = $('#pl-network-idea-modal');
        const modalDialog = modal.find('.pl-network-idea-modal__dialog');
        const modalBody = modal.find('.pl-network-idea-modal__body');
        const modalOverlay = modal.find('.pl-network-idea-modal__overlay');
        const modalClose = modal.find('.pl-network-idea-modal__close');
        if (modalDialog.length && !modalDialog.attr('tabindex')) {
            modalDialog.attr('tabindex', '-1');
        }
        let previouslyFocused = null;

        form.on('submit', function (event) {
            event.preventDefault();

            const query = $.trim($('#pl-network-search-query').val());
            const minScore = parseInt($('#pl-network-search-min-score').val(), 10) || 0;
            const limit = parseInt($('#pl-network-search-limit').val(), 10) || 10;
            const enrichedOnly = $('#pl-network-search-enriched').is(':checked') ? 1 : 0;

            if (!query) {
                showFeedback('error', networkStrings.searchError || 'Please enter a search term.');
                return;
            }

            const submitButton = form.find('button[type="submit"]');
            submitButton.prop('disabled', true);

            showFeedback('', '');
            setStatus(networkStrings.searching || 'Searching for ideas…');
            resultsContainer.addClass('is-loading');

            $.ajax({
                url: plValidation.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'pl_network_search_ideas',
                    nonce: networkNonce,
                    query,
                    min_score: minScore,
                    limit,
                    enriched_only: enrichedOnly,
                },
            }).done(function (response) {
                if (!response || !response.success || !response.data) {
                    const message = response && response.data && response.data.message
                        ? response.data.message
                        : (networkStrings.searchError || 'Unable to complete the discovery request. Please try again.');
                    showFeedback('error', message);
                    discoveryResults = [];
                    resultsContainer.empty();
                    return;
                }

                discoveryResults = Array.isArray(response.data.ideas) ? response.data.ideas : [];
                renderResults(discoveryResults, response.data.meta || {});

                if (!discoveryResults.length) {
                    showFeedback('info', networkStrings.noResults || 'No pre-validated ideas were found for this search.');
                }
            }).fail(function () {
                showFeedback('error', networkStrings.searchError || 'Unable to complete the discovery request. Please try again.');
                discoveryResults = [];
                resultsContainer.empty();
            }).always(function () {
                submitButton.prop('disabled', false);
                setStatus('');
                resultsContainer.removeClass('is-loading');
            });
        });

        $(document).on('click', '.pl-network-publish-idea', function (event) {
            event.preventDefault();

            const button = $(this);
            const index = parseInt(button.data('index'), 10);
            const idea = discoveryResults[index];

            if (!idea) {
                showFeedback('error', networkStrings.pushError || 'Unable to publish this idea to the library. Please try again.');
                return;
            }

            button.prop('disabled', true).text(networkStrings.publishing || 'Publishing…');
            showFeedback('', '');

            $.ajax({
                url: plValidation.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'pl_network_push_idea',
                    nonce: networkNonce,
                    idea: JSON.stringify(idea),
                },
            }).done(function (response) {
                if (!response || !response.success) {
                    const message = response && response.data && response.data.message
                        ? response.data.message
                        : (networkStrings.pushError || 'Unable to publish this idea to the library. Please try again.');
                    showFeedback('error', message);
                    button.prop('disabled', false).text(networkStrings.publishAction || 'Push to Ideas Library');
                    return;
                }

                button
                    .addClass('pl-network-published')
                    .text(networkStrings.publishedAction || 'Published')
                    .prop('disabled', true);

                showFeedback('success', response.data.message || networkStrings.pushSuccess || 'Idea published to the network library.');
            }).fail(function () {
                showFeedback('error', networkStrings.pushError || 'Unable to publish this idea to the library. Please try again.');
                button.prop('disabled', false).text(networkStrings.publishAction || 'Push to Ideas Library');
            });
        });

        $(document).on('click', '.pl-network-view-report', function (event) {
            event.preventDefault();

            const button = $(this);
            const index = parseInt(button.data('index'), 10);
            const idea = discoveryResults[index];

            if (!idea) {
                showFeedback('error', networkStrings.reportError || 'Unable to open the idea report. Please try again.');
                return;
            }

            openIdeaModal(idea, button.get(0));
        });

        modalClose.on('click', function (event) {
            event.preventDefault();
            closeIdeaModal();
        });

        modalOverlay.on('click', function () {
            closeIdeaModal();
        });

        $(document).on('keydown', function (event) {
            if ('Escape' === event.key && modal.hasClass('is-visible')) {
                event.preventDefault();
                closeIdeaModal();
            }
        });

        function openIdeaModal(idea, triggerElement) {
            const reportHtml = buildIdeaReport(idea);

            if (!reportHtml) {
                showFeedback('error', networkStrings.reportError || 'Unable to open the idea report. Please try again.');
                return;
            }

            previouslyFocused = triggerElement || document.activeElement;

            modalBody.html(reportHtml);
            modal.addClass('is-visible').attr('aria-hidden', 'false');
            $('body').addClass('pl-network-modal-open');

            modalDialog.focus();
        }

        function closeIdeaModal() {
            if (!modal.hasClass('is-visible')) {
                return;
            }

            modal.removeClass('is-visible').attr('aria-hidden', 'true');
            $('body').removeClass('pl-network-modal-open');
            modalBody.empty();

            if (previouslyFocused && typeof previouslyFocused.focus === 'function') {
                previouslyFocused.focus();
            }

            previouslyFocused = null;
        }

        function buildIdeaReport(idea) {
            if (!idea || typeof idea !== 'object') {
                return '';
            }

            const title = idea.business_idea ? escapeHtml(idea.business_idea) : escapeHtml(networkStrings.untitledIdea || 'Untitled Idea');
            const scoreValue = parseInt(typeof idea.adjusted_score !== 'undefined' ? idea.adjusted_score : idea.validation_score, 10);
            const scoreBadge = createScoreBadge(isFinite(scoreValue) ? scoreValue : 0);
            const confidence = idea.confidence_level
                ? '<span class="pl-network-idea-modal__confidence">' + escapeHtml(String(idea.confidence_level)) + '</span>'
                : '';
            const generated = idea.generated_at
                ? '<span class="pl-network-idea-modal__generated">' + escapeHtml(networkStrings.reportGenerated || 'Generated') + ': '
                    + escapeHtml(formatDateTime(idea.generated_at)) + '</span>'
                : '';
            const tags = collectIdeaTags(idea);
            const tagsHtml = tags.length
                ? '<div class="pl-network-idea-modal__tags"><strong>' + escapeHtml(networkStrings.reportTags || 'Tags') + ':</strong> '
                    + tags.map(function (tag) {
                        return '<span class="pl-network-idea-modal__tag">' + escapeHtml(tag) + '</span>';
                    }).join('') + '</div>'
                : '';

            let bodyHtml = '';

            const summaryHtml = idea.summary ? '<p>' + formatMultiline(idea.summary) + '</p>' : '';
            if (summaryHtml) {
                bodyHtml += renderSection(networkStrings.reportSummary || 'Summary', summaryHtml);
            }

            const breakdownHtml = renderScoreBreakdown(idea.scoring_breakdown);
            if (breakdownHtml) {
                bodyHtml += renderSection(networkStrings.reportScoreBreakdown || 'Score breakdown', breakdownHtml);
            }

            const insightsHtml = renderOptionalParagraph(idea.ai_assessment_summary || idea.ai_assessment);
            if (insightsHtml) {
                bodyHtml += renderSection(networkStrings.reportInsights || 'Insights', insightsHtml);
            }

            const marketHtml = renderOptionalParagraph(idea.market_validation_summary || idea.market_validation);
            if (marketHtml) {
                bodyHtml += renderSection(networkStrings.reportMarket || 'Market validation', marketHtml);
            }

            const audienceHtml = renderOptionalParagraph(idea.target_audience);
            if (audienceHtml) {
                bodyHtml += renderSection(networkStrings.reportAudience || 'Target audience', audienceHtml);
            }

            const painHtml = renderOptionalParagraph(idea.customer_pain_points);
            if (painHtml) {
                bodyHtml += renderSection(networkStrings.reportPainPoints || 'Customer pain points', painHtml);
            }

            const opportunityHtml = renderOptionalParagraph(idea.validation_opportunities);
            if (opportunityHtml) {
                bodyHtml += renderSection(networkStrings.reportOpportunities || 'Validation opportunities', opportunityHtml);
            }

            const actionHtml = renderOptionalList(idea.action_plan);
            if (actionHtml) {
                bodyHtml += renderSection(networkStrings.reportActionPlan || 'Action plan', actionHtml);
            }

            if (!bodyHtml) {
                bodyHtml = '<div class="pl-network-idea-modal__empty">' + escapeHtml(networkStrings.reportEmpty || 'No additional insights were provided for this idea.') + '</div>';
            }

            return ''
                + '<div class="pl-network-idea-modal__content" role="document">'
                    + '<header class="pl-network-idea-modal__header">'
                        + '<h2 id="pl-network-idea-modal-title">' + title + '</h2>'
                        + '<div class="pl-network-idea-modal__meta">'
                            + scoreBadge
                            + confidence
                            + generated
                        + '</div>'
                        + tagsHtml
                    + '</header>'
                    + '<div class="pl-network-idea-modal__body-content">' + bodyHtml + '</div>'
                + '</div>';
        }

        function createScoreBadge(score) {
            const safeScore = isFinite(score) ? score : 0;
            const badgeClass = safeScore >= 70 ? 'high' : (safeScore >= 50 ? 'medium' : 'low');

            return '<span class="pl-network-idea-modal__score pl-score-' + badgeClass + '">' +
                '<span class="pl-network-idea-modal__score-value">' + escapeHtml(String(safeScore)) + '</span>' +
                '<span class="pl-network-idea-modal__score-label">' + escapeHtml(networkStrings.scoreColumn || 'Score') + '</span>' +
            '</span>';
        }

        function renderScoreBreakdown(breakdown) {
            if (!breakdown || typeof breakdown !== 'object') {
                return '';
            }

            const entries = Object.keys(breakdown).map(function (key) {
                const value = breakdown[key];

                if (typeof value === 'undefined' || value === null || value === '') {
                    return '';
                }

                const label = formatLabel(key);
                const displayValue = isFinite(parseFloat(value)) ? parseFloat(value).toString() : String(value);

                return '<div class="pl-network-idea-modal__metric">'
                    + '<span class="pl-network-idea-modal__metric-label">' + escapeHtml(label) + '</span>'
                    + '<span class="pl-network-idea-modal__metric-value">' + escapeHtml(displayValue) + '</span>'
                    + '</div>';
            }).filter(Boolean);

            if (!entries.length) {
                return '';
            }

            return '<div class="pl-network-idea-modal__metrics-grid">' + entries.join('') + '</div>';
        }

        function renderSection(title, innerHtml) {
            if (!innerHtml) {
                return '';
            }

            return '<section class="pl-network-idea-modal__section">'
                + '<h3>' + escapeHtml(title) + '</h3>'
                + innerHtml
                + '</section>';
        }

        function renderOptionalParagraph(value) {
            if (!value) {
                return '';
            }

            if (Array.isArray(value)) {
                return renderOptionalList(value);
            }

            return '<p>' + formatMultiline(value) + '</p>';
        }

        function renderOptionalList(value) {
            if (!value) {
                return '';
            }

            const items = Array.isArray(value) ? value : String(value).split(/\r?\n/);
            const normalized = items.map(function (item) {
                return typeof item === 'string' ? item.trim() : String(item);
            }).filter(function (item) {
                return item.length > 0;
            });

            if (!normalized.length) {
                return '';
            }

            return '<ul class="pl-network-idea-modal__list">' + normalized.map(function (item) {
                return '<li>' + escapeHtml(item) + '</li>';
            }).join('') + '</ul>';
        }

        function collectIdeaTags(idea) {
            const tags = [];

            if (Array.isArray(idea.tags)) {
                tags.push.apply(tags, idea.tags);
            }

            if (Array.isArray(idea.category_labels)) {
                tags.push.apply(tags, idea.category_labels);
            }

            const unique = [];

            tags.forEach(function (tag) {
                const normalized = typeof tag === 'string' ? tag.trim() : '';

                if (normalized && -1 === unique.indexOf(normalized)) {
                    unique.push(normalized);
                }
            });

            return unique;
        }

        function formatMultiline(value) {
            return escapeHtml(String(value)).replace(/\r?\n/g, '<br>');
        }

        function formatLabel(key) {
            return String(key)
                .replace(/_/g, ' ')
                .replace(/\b\w/g, function (match) { return match.toUpperCase(); });
        }

        function formatDateTime(value) {
            const date = new Date(value);

            if (isNaN(date.getTime())) {
                return String(value);
            }

            return date.toLocaleString();
        }

        function renderResults(ideas, meta) {
            if (!Array.isArray(ideas) || !ideas.length) {
                resultsContainer.html('<p class="description">' + escapeHtml(networkStrings.noResults || 'No pre-validated ideas were found for this search.') + '</p>');
                return;
            }

            const tableRows = ideas.map(function (idea, index) {
                const title = idea.business_idea ? escapeHtml(idea.business_idea) : 'Untitled Idea';
                const summary = idea.summary ? escapeHtml(idea.summary) : '';
                const score = typeof idea.adjusted_score !== 'undefined' ? parseInt(idea.adjusted_score, 10) : (idea.validation_score || 0);
                const confidence = idea.confidence_level ? escapeHtml(idea.confidence_level) : '—';
                const origin = idea.origin ? escapeHtml(idea.origin) : '';
                const tags = Array.isArray(idea.tags) ? idea.tags.map(escapeHtml).join(', ') : '';
                const badgeClass = score >= 70 ? 'pl-score-high' : (score >= 50 ? 'pl-score-medium' : 'pl-score-low');

                let metaDetails = '';
                if (origin || tags) {
                    const pieces = [];
                    if (origin) {
                        pieces.push(origin);
                    }
                    if (tags) {
                        pieces.push(tags);
                    }
                    metaDetails = '<p class="pl-network-idea-meta">' + pieces.join(' · ') + '</p>';
                }

                return (
                    '<tr>' +
                        '<td>' +
                            '<strong>' + title + '</strong>' +
                            (summary ? '<p class="description">' + summary + '</p>' : '') +
                            metaDetails +
                        '</td>' +
                        '<td class="pl-network-score-cell">' +
                            '<span class="pl-score-badge ' + badgeClass + '">' + score + '</span>' +
                        '</td>' +
                        '<td>' + confidence + '</td>' +
                        '<td>' +
                            '<div class="pl-network-actions">' +
                                '<button type="button" class="button button-secondary pl-network-view-report" data-index="' + index + '">' +
                                    escapeHtml(networkStrings.viewReport || 'View Report') +
                                '</button>' +
                                '<button type="button" class="button button-primary pl-network-publish-idea" data-index="' + index + '">' +
                                    escapeHtml(networkStrings.publishAction || 'Push to Ideas Library') +
                                '</button>' +
                            '</div>' +
                        '</td>' +
                    '</tr>'
                );
            }).join('');

            const metaInfo = formatMeta(meta, ideas.length);

            const table = (
                (metaInfo ? metaInfo : '') +
                '<table class="widefat fixed striped pl-network-search-table">' +
                    '<thead>' +
                        '<tr>' +
                            '<th>' + escapeHtml(networkStrings.ideaColumn || 'Idea') + '</th>' +
                            '<th>' + escapeHtml(networkStrings.scoreColumn || 'Score') + '</th>' +
                            '<th>' + escapeHtml(networkStrings.confidenceColumn || 'Confidence') + '</th>' +
                            '<th class="pl-network-actions-col">' + escapeHtml(networkStrings.actionsColumn || 'Actions') + '</th>' +
                        '</tr>' +
                    '</thead>' +
                    '<tbody>' + tableRows + '</tbody>' +
                '</table>'
            );

            resultsContainer.html(table);
        }

        function formatMeta(meta, fallbackCount) {
            if (!meta || typeof meta !== 'object') {
                return '';
            }

            const count = parseInt(meta.count, 10) || fallbackCount || 0;
            const query = meta.query ? escapeHtml(meta.query) : '';
            const isDemo = !!meta.is_demo;
            const demoMessage = meta.demo_message ? escapeHtml(meta.demo_message) : '';

            if (count <= 0) {
                return '';
            }

            let text = '';
            if (query && networkStrings.metaLabel) {
                text = networkStrings.metaLabel.replace('%1$s', count).replace('%2$s', query);
            } else if (networkStrings.metaLabelNoQuery) {
                text = networkStrings.metaLabelNoQuery.replace('%1$s', count);
            } else {
                text = 'Discovered ' + count + ' ideas.';
            }

            const libraryLink = networkLinks.library ? '<a href="' + networkLinks.library + '">' + escapeHtml(networkStrings.libraryLinkLabel || 'Open Ideas Library') + '</a>' : '';

            let rendered = text;

            if (libraryLink) {
                rendered += ' · ' + libraryLink;
            }

            if (isDemo && demoMessage) {
                rendered += '<br><span class="pl-network-demo-note">' + demoMessage + '</span>';
            }

            return '<p class="pl-network-search-meta">' + rendered + '</p>';
        }

        function showFeedback(type, message) {
            if (!feedback.length) {
                return;
            }

            feedback.removeClass('notice-error notice-success notice-info');

            if (!message) {
                feedback.hide();
                return;
            }

            let noticeClass = 'notice-info';
            if ('error' === type) {
                noticeClass = 'notice-error';
            } else if ('success' === type) {
                noticeClass = 'notice-success';
            }

            feedback
                .addClass(noticeClass)
                .text(message)
                .show();
        }

        function setStatus(message) {
            if (!status.length) {
                return;
            }

            status.text(message || '');
        }

        function escapeHtml(value) {
            if (typeof value === 'undefined' || value === null) {
                return '';
            }

            return $('<div />').text(value).html();
        }
    }

    $('.pl-validation-settings form').on('submit', function () {
        const button = $(this).find('input[type="submit"]');
        button.val('Saving...').prop('disabled', true);
    });
});
