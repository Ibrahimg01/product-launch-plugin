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
                            '<button type="button" class="button button-secondary pl-network-publish-idea" data-index="' + index + '">' +
                                escapeHtml(networkStrings.publishAction || 'Push to Ideas Library') +
                            '</button>' +
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

            if (libraryLink) {
                text += ' · ' + libraryLink;
            }

            return '<p class="pl-network-search-meta">' + text + '</p>';
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
