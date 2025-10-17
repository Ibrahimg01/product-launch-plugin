<?php
/**
 * Universal Navigation for All Phase Templates
 * Provides: Back to Dashboard, Previous/Next Phase, Save Progress, Clear Fields
 */

if (!defined('ABSPATH')) exit;

// Phase navigation map
$phase_map = [
    'market_clarity' => [
        'title' => 'Market Clarity',
        'prev' => null,
        'next' => 'create_offer',
        'prev_url' => admin_url('admin.php?page=product-launch'),
        'next_url' => admin_url('admin.php?page=product-launch-offer')
    ],
    'create_offer' => [
        'title' => 'Create Offer',
        'prev' => 'market_clarity',
        'next' => 'create_service',
        'prev_url' => admin_url('admin.php?page=product-launch-market'),
        'next_url' => admin_url('admin.php?page=product-launch-service')
    ],
    'create_service' => [
        'title' => 'Create Service',
        'prev' => 'create_offer',
        'next' => 'build_funnel',
        'prev_url' => admin_url('admin.php?page=product-launch-offer'),
        'next_url' => admin_url('admin.php?page=product-launch-funnel')
    ],
    'build_funnel' => [
        'title' => 'Build Funnel',
        'prev' => 'create_service',
        'next' => 'email_sequences',
        'prev_url' => admin_url('admin.php?page=product-launch-service'),
        'next_url' => admin_url('admin.php?page=product-launch-emailss')
    ],
    'email_sequences' => [
        'title' => 'Email Sequences',
        'prev' => 'build_funnel',
        'next' => 'organic_posts',
        'prev_url' => admin_url('admin.php?page=product-launch-funnel'),
        'next_url' => admin_url('admin.php?page=product-launch-organic')
    ],
    'organic_posts' => [
        'title' => 'Organic Posts',
        'prev' => 'email_sequences',
        'next' => 'facebook_ads',
        'prev_url' => admin_url('admin.php?page=product-launch-emailss'),
        'next_url' => admin_url('admin.php?page=product-launch-ads')
    ],
    'facebook_ads' => [
        'title' => 'Facebook Ads',
        'prev' => 'organic_posts',
        'next' => 'launch',
        'prev_url' => admin_url('admin.php?page=product-launch-organic'),
        'next_url' => admin_url('admin.php?page=product-launch-launch')
    ],
    'launch' => [
        'title' => 'Launch',
        'prev' => 'facebook_ads',
        'next' => null,
        'prev_url' => admin_url('admin.php?page=product-launch-ads'),
        'next_url' => admin_url('admin.php?page=product-launch')
    ]
];

// Get current phase info
$current = isset($current_phase) ? $current_phase : 'market_clarity';
$phase_info = isset($phase_map[$current]) ? $phase_map[$current] : $phase_map['market_clarity'];

?>

<div class="form-actions">
    <!-- Back to Dashboard -->
    <a href="<?php echo admin_url('admin.php?page=product-launch'); ?>" class="button button-secondary">
        <span class="dashicons dashicons-arrow-left-alt"></span>
        Back to Dashboard
    </a>

    <!-- Previous Phase -->
    <?php if ($phase_info['prev']): ?>
    <a href="<?php echo esc_url($phase_info['prev_url']); ?>" class="button button-secondary">
        <span class="dashicons dashicons-arrow-left-alt2"></span>
        Previous Phase
    </a>
    <?php endif; ?>

    <!-- Save Progress -->
    <button type="submit" name="save_progress" class="button button-primary">
        <span class="dashicons dashicons-saved"></span>
        Save Progress
    </button>

    <!-- Clear All Fields -->
    <button type="button" class="button button-secondary clear-all-fields" onclick="return confirm('Are you sure you want to clear all fields? This cannot be undone.');">
        <span class="dashicons dashicons-trash"></span>
        Clear All Fields
    </button>

    <!-- Next Phase -->
    <?php if ($phase_info['next']): ?>
    <a href="<?php echo esc_url($phase_info['next_url']); ?>" class="button button-primary">
        Next Phase
        <span class="dashicons dashicons-arrow-right-alt2"></span>
    </a>
    <?php else: ?>
    <a href="<?php echo admin_url('admin.php?page=product-launch'); ?>" class="button button-primary">
        Return to Dashboard
        <span class="dashicons dashicons-dashboard"></span>
    </a>
    <?php endif; ?>
</div>

<style>
.form-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
    align-items: center;
    padding: 30px;
    background: #f8fafc;
    border-radius: 8px;
    margin-top: 30px;
    border-top: 2px solid #e5e7eb;
}

.form-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    font-size: 14px;
    min-width: 150px;
    justify-content: center;
}

.form-actions .button .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.form-actions .button-primary {
    background: #3b82f6;
    border-color: #3b82f6;
}

.form-actions .button-primary:hover {
    background: #2563eb;
    border-color: #2563eb;
}

.form-actions .button-secondary {
    background: white;
    border-color: #d1d5db;
    color: #374151;
}

.form-actions .button-secondary:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
}

.clear-all-fields {
    color: #dc2626 !important;
    border-color: #dc2626 !important;
}

.clear-all-fields:hover {
    background: #fef2f2 !important;
    border-color: #b91c1c !important;
}

@media (max-width: 768px) {
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .button {
        width: 100%;
        max-width: 300px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Clear All Fields functionality
    $('.clear-all-fields').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to clear all fields? This cannot be undone.')) {
            return;
        }
        
        // Clear all textareas and inputs in the form
        $(this).closest('form').find('textarea, input[type="text"]').val('').trigger('change');
        
        // Remove AI-filled classes
        $('.ai-fillable').removeClass('ai-filled field-empty field-thin field-good');
        
        // Show notification
        if (window.productLaunchCoach && typeof window.productLaunchCoach.showNotification === 'function') {
            window.productLaunchCoach.showNotification('All fields cleared!', 'info');
        } else {
            alert('All fields have been cleared!');
        }
    });
});
</script>
