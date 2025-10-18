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

    $('.pl-delete-validation').on('click', function (e) {
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

    $('.pl-view-details').on('click', function (e) {
        e.preventDefault();
        const validationId = $(this).data('id');
        window.alert('View details functionality will be implemented in Phase 4. Validation ID: ' + validationId);
    });

    $('.pl-validation-settings form').on('submit', function () {
        const button = $(this).find('input[type="submit"]');
        button.val('Saving...').prop('disabled', true);
    });
});
