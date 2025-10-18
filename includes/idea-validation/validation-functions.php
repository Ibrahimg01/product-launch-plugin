<?php
/**
 * Validation Helper Functions
 */

if (!defined('ABSPATH')) exit;

/**
 * Get user's quota information
 */
function pl_get_user_quota($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $quota = new PL_Validation_Quota();
    return $quota->get_quota_info($user_id);
}

/**
 * Check if user can validate
 */
function pl_can_user_validate($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $quota = new PL_Validation_Quota();
    return $quota->can_validate($user_id);
}

/**
 * Get user's validations
 */
function pl_get_user_validations($user_id = null, $args = array()) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $access = new PL_Validation_Access();
    return $access->get_user_validations($user_id, $args);
}

/**
 * Get validation by ID
 */
function pl_get_validation($validation_id, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $access = new PL_Validation_Access();
    return $access->get_validation($validation_id, $user_id);
}

/**
 * Format validation score for display
 */
function pl_format_validation_score($score) {
    $score = (int)$score;
    
    if ($score >= 70) {
        return array(
            'score' => $score,
            'label' => 'Strong Potential',
            'class' => 'success',
            'color' => '#10B981'
        );
    } elseif ($score >= 40) {
        return array(
            'score' => $score,
            'label' => 'Moderate Potential',
            'class' => 'warning',
            'color' => '#F59E0B'
        );
    } else {
        return array(
            'score' => $score,
            'label' => 'Needs Work',
            'class' => 'danger',
            'color' => '#EF4444'
        );
    }
}
