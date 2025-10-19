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

    $('.pl-validation-settings form').on('submit', function () {
        const button = $(this).find('input[type="submit"]');
        button.val('Saving...').prop('disabled', true);
    });
});
