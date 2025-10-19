jQuery(document).ready(function($) {
    const validationConfig = typeof plValidationFrontend !== 'undefined' ? plValidationFrontend : null;
    // Character counter
    $('#pl-business-idea').on('input', function() {
        const count = $(this).val().length;
        $('#pl-char-count').text(count);

        if (count < 20) {
            $('#pl-char-count').css('color', '#dc3545');
        } else {
            $('#pl-char-count').css('color', '#28a745');
        }
    });

    // Validation form submission
    $('#pl-validation-form').on('submit', function(e) {
        e.preventDefault();

        if (!validationConfig) {
            console.warn('Product Launch validation configuration missing.');
            return;
        }

        const form = $(this);
        const button = form.find('.pl-submit-button');
        const buttonText = button.find('.pl-button-text');
        const buttonLoader = button.find('.pl-button-loader');
        const messageDiv = $('#pl-form-message');
        const businessIdea = $('#pl-business-idea').val().trim();

        // Validate
        if (businessIdea.length < 20) {
            showMessage('error', validationConfig.strings.ideaRequired || 'Please enter your business idea.');
            return;
        }

        // Show loading state
        button.prop('disabled', true);
        buttonText.hide();
        buttonLoader.show();
        messageDiv.hide();

        // Show processing modal
        showProcessingModal();

        // Submit validation
        $.ajax({
            url: validationConfig.ajaxurl,
            type: 'POST',
            data: {
                action: 'pl_submit_validation',
                nonce: validationConfig.nonce,
                business_idea: businessIdea,
                context: validationConfig.context || 'frontend',
                redirect_base: validationConfig.redirectBase || ''
            },
            success: function(response) {
                if (response.success) {
                    // Update progress to complete
                    updateProgress(100);

                    // Wait a moment then redirect
                    setTimeout(function() {
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        } else if (validationConfig.redirectBase) {
                            window.location.href = addQueryArg(validationConfig.redirectBase, 'validation_id', response.data.validation_id);
                        } else {
                            hideProcessingModal();
                            showMessage('success', response.data.message);
                            form[0].reset();
                            $('#pl-char-count').text('0').css('color', '#dc3545');
                        }
                    }, 1500);
                } else {
                    hideProcessingModal();
                    showMessage('error', response.data.message);
                }
            },
            error: function() {
                hideProcessingModal();
                showMessage('error', validationConfig.strings.error || 'An error occurred. Please try again.');
            },
            complete: function() {
                button.prop('disabled', false);
                buttonText.show();
                buttonLoader.hide();
            }
        });

        function showMessage(type, message) {
            messageDiv
                .removeClass('success error')
                .addClass(type)
                .html(message)
                .fadeIn();
        }
    });

    function addQueryArg(base, key, value) {
        try {
            const url = new URL(base, window.location.origin);
            url.searchParams.set(key, value);
            return url.toString();
        } catch (e) {
            const separator = base.indexOf('?') > -1 ? '&' : '?';
            return base + separator + encodeURIComponent(key) + '=' + encodeURIComponent(value);
        }
    }

    // Processing modal functions
    function showProcessingModal() {
        $('#pl-processing-modal').fadeIn();
        animateSteps();
    }

    function hideProcessingModal() {
        $('#pl-processing-modal').fadeOut();
    }

    function animateSteps() {
        const steps = $('.pl-step');
        let currentStep = 0;

        const interval = setInterval(function() {
            if (currentStep > 0) {
                steps.eq(currentStep - 1).addClass('completed').removeClass('active');
            }

            if (currentStep < steps.length) {
                steps.eq(currentStep).addClass('active');
                currentStep++;
            } else {
                clearInterval(interval);
            }
        }, 8000); // ~8 seconds per step for 30-second total
    }

    function updateProgress(percent) {
        $('.pl-progress-fill').css('width', percent + '%');
    }

    // Push to 8 phases
    $('.pl-push-to-phases, .pl-push-to-phases-main').on('click', function() {
        const validationId = $(this).data('validation-id');

        if (confirm('Push this validated idea to the 8-phase launch system?')) {
            // This will be implemented in Phase 5
            alert('Push to 8-phase functionality will be implemented in Phase 5.\nValidation ID: ' + validationId);
        }
    });

    // Load enrichment sections for report page
    if ($('.pl-validation-report-wrapper').length) {
        loadEnrichmentSections();
    }

    function loadEnrichmentSections() {
        // This will be implemented to load additional report sections
        // For now, just hide the loading message
        setTimeout(function() {
            $('.pl-loading-enrichment').fadeOut(function() {
                $('#pl-enrichment-sections').html(
                    '<div class="pl-report-section">' +
                    '<p style="text-align: center; color: #6c757d;">Additional enrichment sections (Trends, SEO, Competitors, etc.) will be loaded here in Phase 5.</p>' +
                    '</div>'
                );
            });
        }, 2000);
    }
});
