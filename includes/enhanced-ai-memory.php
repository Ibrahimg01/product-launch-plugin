<?php
/**
 * Enhanced AI Memory and Context System
 * This creates a comprehensive memory system for the AI coach
 */

// === Compatibility: OpenAI Chat API call ===
// Returns assistant text on success, or false on failure.
// $messages: array of ['role'=>'system|user|assistant', 'content'=>string]
// $settings: array with keys: openai_key, ai_model, max_tokens, temperature, timeout
if (!function_exists('pl_call_openai_api')) {
    function pl_call_openai_api($messages, $settings = array()) {
        if (!is_array($settings) || empty($settings)) {
            if (function_exists('pl_get_settings')) {
                $settings = pl_get_settings();
            } else {
                $settings = array();
            }
        }
        $api_key    = $settings['openai_key'] ?? ($settings['openai_api_key'] ?? '');
        $model      = $settings['ai_model'] ?? 'gpt-4o-mini';
        $max_tokens = isset($settings['max_tokens']) ? intval($settings['max_tokens']) : 1000;
        $temperature= isset($settings['temperature']) ? floatval($settings['temperature']) : 0.7;
        $timeout    = isset($settings['timeout']) ? intval($settings['timeout']) : 30;

        if (empty($api_key) || !is_array($messages) || empty($messages)) {
            return false;
        }

        if (!function_exists('wp_remote_post')) {
            return false;
        }

        $endpoint = 'https://api.openai.com/v1/chat/completions';
        $body = array(
            'model' => $model,
            'messages' => array_values($messages),
            'max_tokens' => max(16, $max_tokens),
            'temperature' => max(0, min(2, $temperature)),
        );

        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode($body),
            'timeout' => max(5, $timeout),
        );

        $resp = wp_remote_post($endpoint, $args);
        if (is_wp_error($resp)) {
            return false;
        }
        $code = wp_remote_retrieve_response_code($resp);
        $raw  = wp_remote_retrieve_body($resp);
        if ($code < 200 || $code >= 300) {
            return false;
        }
        $json = json_decode($raw, true);
        if (!is_array($json) || empty($json['choices'][0]['message']['content'])) {
            return false;
        }
        
        // ✅ NEW: Sanitize response before returning
        $response = $json['choices'][0]['message']['content'];
        
        // Apply sanitization if the main plugin function exists
        if (function_exists('pl_sanitize_ai_response')) {
            $response = pl_sanitize_ai_response($response, true);
        }
        
        return $response;
    }
}


// Compatibility shim: Provide phase-specific prompt if missing
if (!function_exists('pl_get_phase_specific_prompt')) {
    /**
     * Return a concise, opinionated system prompt tailored to a given phase.
     * This keeps Enhanced Memory compatible even if phase prompt helpers were moved.
     *
     * @param string $phase   One of: market_clarity, offer, service, funnel, email_sequences, organic_posts, facebook_ads, launch
     * @param array  $context Current phase form context (key => value)
     * @return string
     */
    function pl_get_phase_specific_prompt($phase, $context = array()) {
        $summary = '';
        if (is_array($context) && !empty($context)) {
            $pairs = array();
            foreach ($context as $k => $v) {
                if (is_string($v) && strlen($v) > 0) {
                    $pairs[] = $k . ': ' . substr($v, 0, 160);
                }
            }
            if (!empty($pairs)) {
                $summary = "\n\nCurrent phase inputs:\n" . implode("\n", array_slice($pairs, 0, 12));
            }
        }

        switch ($phase) {
            case 'market_clarity':
                return "You are a senior positioning strategist. Help the user nail their niche, ICP, pains, outcomes, competitors, and differentiators. Produce clear, specific statements and avoid fluff." . $summary;
            case 'offer':
                return "You are an offer architect. Turn the validated market insights into an irresistible offer: transformation statement, deliverables, pricing, bonuses, guarantees, FAQs." . $summary;
            case 'service':
                return "You are an operations designer. Define the delivery model, scope, milestones, SLAs, onboarding steps, and success criteria for the service/product." . $summary;
            case 'funnel':
                return "You are a funnel strategist. Propose the best funnel for this context (lead magnet, tripwire, core, upsell). Outline pages, core messaging, and key CTAs." . $summary;
            case 'email_sequences':
                return "You are a lifecycle email strategist. Draft the sequence structure (welcome, nurture, conversion, objection-handling). Include subject lines and call‑to‑actions." . $summary;
            case 'organic_posts':
                return "You are a content lead. Propose a 2‑week content plan with post hooks, angles, and CTAs mapped to the funnel. Prioritize platforms already mentioned." . $summary;
            case 'facebook_ads':
                return "You are a paid growth strategist. Recommend campaign structure, audiences, creative angles, and budgets. Provide 3 primary hooks and 5 headlines." . $summary;
            case 'launch':
            default:
                return "You are a launch director. Produce a realistic launch plan (timeline, channels, assets, KPIs), risks, and contingencies based on the current context." . $summary;
        }
    }
}


/**
 * Get comprehensive user launch context across all phases
 */
function pl_get_comprehensive_launch_context($user_id, $site_id) {
    $context = array(
        'user_profile' => array(),
        'launch_journey' => array(),
        'phase_data' => array(),
        'patterns' => array(),
        'conversation_insights' => array()
    );
    
    // Get all phase data
    $all_phases = array('market_clarity', 'create_offer', 'create_service', 'build_funnel', 'email_sequences', 'organic_posts', 'facebook_ads', 'launch');
    
    foreach ($all_phases as $phase) {
        $phase_data = get_user_meta($user_id, 'product_launch_progress_' . $phase . '_' . $site_id, true);
        if ($phase_data) {
            $decoded = json_decode($phase_data, true);
            if (is_array($decoded)) {
                $context['phase_data'][$phase] = $decoded;
                
                // Extract key insights for easier AI access
                $context['launch_journey'][$phase] = array(
                    'completed' => !empty($decoded['completed_at']),
                    'completion_date' => $decoded['completed_at'] ?? null,
                    'key_elements' => pl_extract_phase_key_elements($phase, $decoded)
                );
            }
        }
    }
    
    // Get conversation insights
    $context['conversation_insights'] = pl_get_conversation_insights($user_id, $site_id);
    
    // Analyze patterns and preferences
    $context['patterns'] = pl_analyze_user_patterns($context['phase_data'], $context['conversation_insights']);
    
    return $context;
}

/**
 * Extract key elements from each phase for AI memory
 */
function pl_extract_phase_key_elements($phase, $data) {
    $key_elements = array();
    
    switch ($phase) {
        case 'market_clarity':
            $key_elements = array(
                'target_audience' => $data['target_audience'] ?? '',
                'main_pain_points' => $data['pain_points'] ?? '',
                'value_prop' => $data['value_proposition'] ?? '',
                'market_position' => $data['positioning'] ?? ''
            );
            break;
            
        case 'create_offer':
            $key_elements = array(
                'core_offer' => $data['main_offer'] ?? '',
                'pricing_model' => $data['pricing_strategy'] ?? '',
                'key_bonuses' => $data['bonuses'] ?? '',
                'guarantee_type' => $data['guarantee'] ?? ''
            );
            break;
            
        case 'create_service':
            $key_elements = array(
                'service_type' => $data['service_concept'] ?? '',
                'delivery_format' => $data['delivery_method'] ?? '',
                'service_pricing' => $data['pricing_model'] ?? ''
            );
            break;
            
        case 'build_funnel':
            $key_elements = array(
                'funnel_type' => $data['funnel_strategy'] ?? '',
                'lead_magnet' => $data['lead_magnet'] ?? '',
                'main_pages' => $data['landing_pages'] ?? ''
            );
            break;
            
        case 'email_sequences':
            $key_elements = array(
                'email_approach' => $data['email_strategy'] ?? '',
                'sequence_plan' => $data['email_sequence_outline'] ?? ''
            );
            break;
            
        case 'organic_posts':
            $key_elements = array(
                'content_strategy' => $data['content_strategy'] ?? '',
                'main_platforms' => $data['platform_strategy'] ?? '',
                'content_themes' => $data['content_themes'] ?? ''
            );
            break;
            
        case 'facebook_ads':
            $key_elements = array(
                'ad_strategy' => $data['campaign_strategy'] ?? '',
                'target_audiences' => $data['audience_targeting'] ?? '',
                'budget_approach' => $data['budget_bidding'] ?? ''
            );
            break;
            
        case 'launch':
            $key_elements = array(
                'launch_strategy' => $data['launch_strategy'] ?? '',
                'launch_timeline' => $data['launch_timeline'] ?? ''
            );
            break;
    }
    
    return array_filter($key_elements); // Remove empty elements
}

/**
 * Get conversation insights for AI memory
 */
function pl_get_conversation_insights($user_id, $site_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'product_launch_conversations';
    
    $conversations = $wpdb->get_results($wpdb->prepare(
        "SELECT phase, message_count, history, created_at, updated_at 
        FROM $table 
        WHERE user_id = %d AND site_id = %d 
        ORDER BY updated_at DESC",
        $user_id, $site_id
    ), ARRAY_A);
    
    $insights = array(
        'total_sessions' => count($conversations),
        'most_discussed_phases' => array(),
        'recent_topics' => array(),
        'user_communication_style' => '',
        'common_challenges' => array()
    );
    
    foreach ($conversations as $conv) {
        // Count phase discussions
        $phase = $conv['phase'];
        if (!isset($insights['most_discussed_phases'][$phase])) {
            $insights['most_discussed_phases'][$phase] = 0;
        }
        $insights['most_discussed_phases'][$phase] += $conv['message_count'];
        
        // Extract recent topics from conversation history
        if (!empty($conv['history'])) {
            $history = json_decode($conv['history'], true);
            if (is_array($history)) {
                $recent_messages = array_slice($history, -5); // Last 5 messages
                foreach ($recent_messages as $msg) {
                    if ($msg['role'] === 'user' && strlen($msg['content']) > 20) {
                        $insights['recent_topics'][] = substr($msg['content'], 0, 100);
                    }
                }
            }
        }
    }
    
    // Sort most discussed phases
    arsort($insights['most_discussed_phases']);
    
    return $insights;
}

/**
 * Analyze user patterns and preferences
 */
function pl_analyze_user_patterns($phase_data, $conversation_insights) {
    $patterns = array(
        'completion_rate' => 0,
        'preferred_content_length' => 'medium',
        'business_type' => 'unknown',
        'experience_level' => 'beginner',
        'main_challenges' => array(),
        'strengths' => array()
    );
    
    // Calculate completion rate
    $completed_phases = 0;
    $total_phases = count($phase_data);
    
    foreach ($phase_data as $phase => $data) {
        if (!empty($data['completed_at'])) {
            $completed_phases++;
        }
    }
    
    $patterns['completion_rate'] = $total_phases > 0 ? ($completed_phases / $total_phases) * 100 : 0;
    
    // Analyze business type from market clarity
    if (!empty($phase_data['market_clarity']['target_audience'])) {
        $audience = strtolower($phase_data['market_clarity']['target_audience']);
        if (strpos($audience, 'business') !== false || strpos($audience, 'entrepreneur') !== false) {
            $patterns['business_type'] = 'b2b';
        } elseif (strpos($audience, 'consumer') !== false || strpos($audience, 'individual') !== false) {
            $patterns['business_type'] = 'b2c';
        }
    }
    
    // Determine experience level from conversation frequency and completion
    if ($patterns['completion_rate'] > 50 && $conversation_insights['total_sessions'] < 10) {
        $patterns['experience_level'] = 'advanced';
    } elseif ($patterns['completion_rate'] > 25 || $conversation_insights['total_sessions'] > 15) {
        $patterns['experience_level'] = 'intermediate';
    }
    
    return $patterns;
}

/**
 * Generate contextual AI system prompt with full memory
 */
function pl_get_enhanced_system_prompt($phase, $context, $full_launch_context = null) {
    if (!$full_launch_context) {
        $user_id = get_current_user_id();
        $site_id = get_current_blog_id();
        $full_launch_context = pl_get_comprehensive_launch_context($user_id, $site_id);
    }
    
    // Base system prompt (same as before)
    $base_prompt = "You are an expert Product Launch Coach with deep expertise in helping entrepreneurs successfully launch products and services. You provide actionable, specific guidance based on proven frameworks and methodologies.";
    
    // Add memory context
    $memory_context = pl_build_memory_context($full_launch_context, $phase);
    
    // Get phase-specific prompt (using existing function)
    $phase_prompt = pl_get_phase_specific_prompt($phase, $context);
    
    return $base_prompt . "\n\n" . $memory_context . "\n\n" . $phase_prompt;
}

/**
 * Build comprehensive memory context for AI
 */
function pl_build_memory_context($full_context, $current_phase) {
    $memory_sections = array();
    
    // User Profile Summary
    $patterns = $full_context['patterns'];
    $profile = "USER PROFILE:\n";
    $profile .= "- Experience Level: " . ucfirst($patterns['experience_level']) . "\n";
    $profile .= "- Business Type: " . ucfirst($patterns['business_type']) . "\n";
    $profile .= "- Completion Rate: " . round($patterns['completion_rate']) . "%\n";
    $memory_sections[] = $profile;
    
    // Launch Journey Progress
    if (!empty($full_context['launch_journey'])) {
        $journey = "LAUNCH JOURNEY PROGRESS:\n";
        foreach ($full_context['launch_journey'] as $phase => $info) {
            $status = $info['completed'] ? "✓ COMPLETED" : "○ In Progress";
            $journey .= "- " . ucwords(str_replace('_', ' ', $phase)) . ": $status\n";
        }
        $memory_sections[] = $journey;
    }
    
    // Key Established Elements
    $key_elements = pl_extract_established_elements($full_context['phase_data']);
    if (!empty($key_elements)) {
        $elements = "ESTABLISHED ELEMENTS:\n";
        foreach ($key_elements as $element => $value) {
            if (!empty($value)) {
                $elements .= "- " . ucwords(str_replace('_', ' ', $element)) . ": " . substr($value, 0, 100) . "...\n";
            }
        }
        $memory_sections[] = $elements;
    }
    
    // Previous Challenges and Insights
    if (!empty($full_context['conversation_insights']['most_discussed_phases'])) {
        $challenges = "PREVIOUS FOCUS AREAS:\n";
        $top_phases = array_slice($full_context['conversation_insights']['most_discussed_phases'], 0, 3, true);
        foreach ($top_phases as $phase => $count) {
            $challenges .= "- " . ucwords(str_replace('_', ' ', $phase)) . " (discussed extensively)\n";
        }
        $memory_sections[] = $challenges;
    }
    
    // Current Context Instructions
    $context_instructions = "COACHING INSTRUCTIONS:\n";
    $context_instructions .= "- Reference specific elements from their previous work when relevant\n";
    $context_instructions .= "- Build upon established foundations rather than starting from scratch\n";
    $context_instructions .= "- Acknowledge their progress and journey so far\n";
    $context_instructions .= "- Maintain consistency with their established brand and approach\n";
    $context_instructions .= "- Current Phase Focus: " . ucwords(str_replace('_', ' ', $current_phase)) . "\n";
    $memory_sections[] = $context_instructions;
    
    return implode("\n", $memory_sections);
}

/**
 * Extract established elements across all phases
 */
function pl_extract_established_elements($phase_data) {
    $elements = array();
    
    // Core business elements that carry through all phases
    if (!empty($phase_data['market_clarity']['target_audience'])) {
        $elements['target_audience'] = $phase_data['market_clarity']['target_audience'];
    }
    
    if (!empty($phase_data['market_clarity']['value_proposition'])) {
        $elements['value_proposition'] = $phase_data['market_clarity']['value_proposition'];
    }
    
    if (!empty($phase_data['create_offer']['main_offer'])) {
        $elements['main_offer'] = $phase_data['create_offer']['main_offer'];
    }
    
    if (!empty($phase_data['create_offer']['pricing_strategy'])) {
        $elements['pricing_strategy'] = $phase_data['create_offer']['pricing_strategy'];
    }
    
    if (!empty($phase_data['create_service']['service_concept'])) {
        $elements['service_concept'] = $phase_data['create_service']['service_concept'];
    }
    
    return $elements;
}

/**
 * Enhanced AI response generation with full memory
 */
function pl_generate_ai_response_enhanced($phase, $message, $history = [], $context = []) {
    $settings = pl_get_settings();
    $api_key = $settings['openai_key'];
    
    if (empty($api_key)) {
        error_log('Product Launch: OpenAI API key not configured');
        return false;
    }

    $user_id = get_current_user_id();
    $site_id = get_current_blog_id();
    
    // Get comprehensive launch context
    $full_launch_context = pl_get_comprehensive_launch_context($user_id, $site_id);
    
    // Build messages with enhanced context
    $messages = array();
    
    // Enhanced system prompt with full memory
    $system_prompt = pl_get_enhanced_system_prompt($phase, $context, $full_launch_context);
    $messages[] = array(
        'role' => 'system',
        'content' => $system_prompt
    );
    
    // Add conversation history (limit to last 15 for context)
    $recent_history = array_slice($history, -15);
    foreach ($recent_history as $msg) {
        if (isset($msg['role']) && isset($msg['content'])) {
            $messages[] = array(
                'role' => ($msg['role'] === 'user' ? 'user' : 'assistant'),
                'content' => $msg['content']
            );
        }
    }
    
    // Add current message with context reference
    $enhanced_message = pl_enhance_user_message($message, $full_launch_context, $phase);
    $messages[] = array('role' => 'user', 'content' => $enhanced_message);

    return pl_call_openai_api($messages, $settings);
}

/**
 * Enhance user message with relevant context
 */
function pl_enhance_user_message($message, $full_launch_context, $current_phase) {
    // If the message is very short or seems to lack context, add relevant background
    if (strlen($message) < 50) {
        $context_hint = "";
        
        // Add relevant context based on what's been established
        $established = pl_extract_established_elements($full_launch_context['phase_data']);
        if (!empty($established['target_audience'])) {
            $audience_snippet = substr($established['target_audience'], 0, 100);
            $context_hint .= "\n[Context: My target audience is: $audience_snippet...]";
        }
        
        if (!empty($established['main_offer'])) {
            $offer_snippet = substr($established['main_offer'], 0, 100);
            $context_hint .= "\n[Context: My main offer is: $offer_snippet...]";
        }
        
        if (!empty($context_hint)) {
            return $message . $context_hint;
        }
    }
    
    return $message;
}

/**
 * Replace the old function with enhanced version
 */
// Update the AJAX handler to use the enhanced function
add_action('wp_ajax_product_launch_chat', 'pl_ajax_chat_enhanced');
function pl_ajax_chat_enhanced() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', PL_NONCE_ACTION)) {
        wp_send_json_error(__('Invalid nonce','product-launch'));
    }
    if (!current_user_can('read')) wp_send_json_error(__('Unauthorized','product-launch'));

    $phase = sanitize_text_field($_POST['phase'] ?? '');
    $message = wp_kses_post($_POST['message'] ?? '');
    $history_json = wp_unslash($_POST['history'] ?? '[]');
    $context_json = wp_unslash($_POST['context'] ?? '{}');
    
    $history = json_decode($history_json, true);
    $context = json_decode($context_json, true);
    if (!is_array($history)) $history = [];
    if (!is_array($context)) $context = [];

    // Rate limiting (same as before)
    $user_id = get_current_user_id();
    $limit = intval(pl_get_settings()['rate_limit_per_min']);
    if ($limit > 0) {
        $key = 'pl_rate_' . $user_id;
        $now = time();
        $window = 60;
        $bucket = get_transient($key);
        if (!is_array($bucket)) $bucket = [];
        $bucket = array_filter($bucket, function($t) use ($now, $window){ return ($now - $t) < $window; });
        if (count($bucket) >= $limit) {
            wp_send_json_error(__('Rate limit exceeded. Try again in a minute.','product-launch'));
        }
        $bucket[] = $now;
        set_transient($key, $bucket, 2 * MINUTE_IN_SECONDS);
    }

    // Use enhanced AI response generation
    $reply = pl_generate_ai_response_enhanced($phase, $message, $history, $context);
    
    if ($reply === false) {
        wp_send_json_error(__('Failed to generate AI response. Please check your settings.','product-launch'));
    }

    // Persist conversation
    pl_save_conversation($user_id, $phase, $message, $reply, $history, $context);

    wp_send_json_success($reply);
}

// === Compatibility: Conversation persistence ===
// Lightweight storage in user meta to avoid fatals if full storage module isn't present.
if (!function_exists('pl_get_conversations')) {
    /**
     * Get conversations for a user. If $phase is provided, return only that phase.
     */
    function pl_get_conversations($user_id, $phase = null, $limit = 50) {
        $all = get_user_meta($user_id, 'pl_conversations', true);
        if (!is_array($all)) { $all = array(); }
        if ($phase && isset($all[$phase]) && is_array($all[$phase])) {
            $conv = array_slice(array_values($all[$phase]), -abs(intval($limit)));
            return $conv;
        }
        // Flatten and cap overall
        $merged = array();
        foreach ($all as $p => $items) {
            if (is_array($items)) {
                foreach ($items as $it) { $merged[] = $it; }
            }
        }
        // Sort by time asc then slice last $limit
        usort($merged, function($a,$b){ return ($a['time'] ?? 0) <=> ($b['time'] ?? 0); });
        return array_slice($merged, -abs(intval($limit)));
    }
}

if (!function_exists('pl_save_conversation')) {
    /**
     * Save a conversation exchange safely.
     * Keeps last 100 items per phase to avoid bloating user meta.
     */
    function pl_save_conversation($user_id, $phase, $message, $reply, $history = array(), $context = array()) {
        $phase = sanitize_key($phase ?: 'general');
        $record = array(
            'time'    => time(),
            'phase'   => $phase,
            'message' => is_string($message) ? $message : wp_json_encode($message),
            'reply'   => is_string($reply) ? $reply : wp_json_encode($reply),
            'history' => is_array($history) ? $history : array(),
            'context' => is_array($context) ? $context : array(),
        );
        $all = get_user_meta($user_id, 'pl_conversations', true);
        if (!is_array($all)) { $all = array(); }
        if (!isset($all[$phase]) || !is_array($all[$phase])) { $all[$phase] = array(); }
        $all[$phase][] = $record;
        // Bound per phase
        $max_per_phase = 100;
        if (count($all[$phase]) > $max_per_phase) {
            $all[$phase] = array_slice($all[$phase], -$max_per_phase);
        }
        update_user_meta($user_id, 'pl_conversations', $all);
        return true;
    }
}



// === Compatibility: Field assistance generator ===
// Returns a concise string suggestion for a given field, or false on failure.
if (!function_exists('pl_generate_field_assistance')) {
    function pl_generate_field_assistance($phase, $field_id, $context = array()) {
        $phase = sanitize_key($phase ?: 'general');
        $field_id = sanitize_key($field_id ?: 'unknown');
        if (!is_array($context)) { $context = array(); }

        // Build a minimal context summary to keep prompts small
        $summary_parts = array();
        foreach (array('business_name','offer','audience','pain_points','value_proposition','positioning') as $k) {
            if (!empty($context[$k]) && is_string($context[$k])) {
                $summary_parts[] = ucfirst(str_replace('_',' ', $k)) . ': ' . wp_strip_all_tags($context[$k]);
            }
        }
        $summary = implode("\n", array_slice($summary_parts, 0, 6));

        $field_hint = $context['field_label'] ?? $field_id;
        $instructions = "You are an assistant helping fill a single form field for a product launch workflow.\n".
                        "Phase: {$phase}\nField: {$field_hint}\n".
                        "Context (may be partial):\n{$summary}\n\n".
                        "Write only the value for this one field. No headings, no explanations. Keep it concise and specific.\n".
                        "Maximum length: 500 characters.";

        $messages = array(
            array('role'=>'system', 'content'=> $instructions),
            array('role'=>'user',   'content'=> isset($context['user_prompt']) ? (string)$context['user_prompt'] : 'Generate the best value for this field based on the context.'),
        );

        // Call the shared OpenAI helper
        if (!function_exists('pl_call_openai_api')) {
            return false;
        }
        if (function_exists('pl_get_settings')) {
            $settings = pl_get_settings();
        } else {
            $settings = array();
        }

        $reply = pl_call_openai_api($messages, $settings);
        if (!is_string($reply) || $reply === '') {
            return false;
        }
        // Post-process: trim, strip quotes, enforce max length
        $reply = trim($reply);
        $reply = preg_replace('/^["\\\']|["\\\']$/', '', $reply);
        if (strlen($reply) > 1000) {
            $reply = substr($reply, 0, 1000);
        }
        return $reply;
    }
}

