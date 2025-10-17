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
    gap: 12px;
    justify-content: center;
    align-items: center;
    padding: 30px 20px;
    background: linear-gradient(to bottom, #f8fafc, #f1f5f9);
    border-radius: 12px;
    margin-top: 30px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.form-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.form-actions .button .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.form-actions .button-primary {
    background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
    border-color: #3b82f6 !important;
    color: white !important;
}

.form-actions .button-primary:hover {
    background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
    border-color: #2563eb !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.form-actions .button-secondary {
    background: white !important;
    border: 1px solid #d1d5db !important;
    color: #374151 !important;
}

.form-actions .button-secondary:hover {
    background: #f9fafb !important;
    border-color: #9ca3af !important;
    transform: translateY(-1px);
}

.clear-all-fields {
    background: white !important;
    color: #dc2626 !important;
    border: 1px solid #fecaca !important;
}

.clear-all-fields:hover {
    background: #fef2f2 !important;
    border-color: #dc2626 !important;
    transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 768px) {
    .form-actions {
        flex-direction: column;
        gap: 10px;
    }
    
    .form-actions .button {
        width: 100%;
        max-width: 300px;
        justify-content: center;
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