<?php
/**
 * Multi-Signal Validation Engine v3.0
 *
 * This engine is responsible for collecting multiple validation signals,
 * orchestrating AI analysis, and generating actionable recommendations for
 * Product Launch customers. The implementation focuses on providing a solid
 * foundation that can be progressively enhanced with real data sources while
 * remaining fully testable in a development environment.
 *
 * @package ProductLaunch
 */

if (!defined('ABSPATH')) {
    exit;
}

class PL_Validation_Engine_V3 {
    /**
     * Weighting for the composite score.
     *
     * @var array<string,float>
     */
    const WEIGHTS = [
        'market_demand' => 0.25,
        'competition'   => 0.20,
        'monetization'  => 0.15,
        'feasibility'   => 0.10,
        'ai_analysis'   => 0.20,
        'social_proof'  => 0.10,
    ];

    /**
     * Primary validation entry point.
     *
     * @param string $business_idea Business concept provided by the user.
     * @param array  $context       Optional supporting context (target audience, notes, etc.).
     *
     * @return array{
     *     id:string,
     *     business_idea:string,
     *     validation_score:int,
     *     confidence_level:string,
     *     score_breakdown:array,
     *     confidence_score:float,
     *     signals:array,
     *     recommendations:array,
     *     phase_prefill:array,
     *     validated_at:string,
     *     expires_at:string
     * }
     */
    public function validate_idea($business_idea, $context = []) {
        $business_idea = trim((string) $business_idea);

        if ('' === $business_idea) {
            return [];
        }

        $keywords = $this->extract_validation_keywords($business_idea, $context);

        $signals = $this->collect_signals_parallel([
            'market'      => fn() => $this->analyze_market_demand($keywords),
            'competition' => fn() => $this->analyze_competition($keywords),
            'monetize'    => fn() => $this->analyze_monetization($business_idea, $context),
            'feasibility' => fn() => $this->assess_feasibility($business_idea, $context),
            'ai_insights' => fn() => $this->get_ai_validation($business_idea, $context),
            'social'      => fn() => $this->analyze_social_proof($keywords),
        ]);

        $score = $this->calculate_weighted_score($signals);
        $recommendations = $this->generate_recommendations($signals, $score);
        $phase_data = $this->map_to_phases($signals, $recommendations);

        return [
            'id' => $this->generate_validation_id(),
            'business_idea' => $business_idea,
            'validation_score' => $score['overall'],
            'confidence_level' => $score['confidence'],
            'confidence_score' => $score['confidence_score'],
            'score_breakdown' => $score['breakdown'],
            'signals' => $signals,
            'recommendations' => $recommendations,
            'phase_prefill' => $phase_data,
            'validated_at' => current_time('mysql'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
        ];
    }

    /**
     * Extract market validation keywords, defaulting to deterministic fallbacks when AI is not available.
     *
     * @param string $idea    The business idea.
     * @param array  $context Additional context from the user.
     *
     * @return array
     */
    private function extract_validation_keywords($idea, $context = []) {
        $prompt = "Extract 5-8 specific keywords for market validation from this business idea. Focus on searchable terms, problem statements, and industry categories.\n\nIdea: {$idea}\n\nReturn as JSON array.";

        $messages = [
            ['role' => 'system', 'content' => 'You are a market research expert. Extract precise validation keywords.'],
            ['role' => 'user', 'content' => $prompt],
        ];

        $response = $this->call_openai($messages, ['max_tokens' => 300, 'temperature' => 0.2]);

        $keywords = [];

        if ($response) {
            $decoded = json_decode($response, true);
            if (is_array($decoded)) {
                $keywords = array_filter(array_map('sanitize_text_field', $decoded));
            }
        }

        if (!$keywords) {
            $keywords = $this->fallback_keywords($idea, $context);
        }

        return array_slice(array_unique($keywords), 0, 8);
    }

    /**
     * Derive basic keywords locally when AI extraction is not available.
     */
    private function fallback_keywords($idea, $context = []) {
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', strtolower($idea), -1, PREG_SPLIT_NO_EMPTY);
        $tokens = array_filter($tokens, function ($token) {
            return strlen($token) > 3;
        });

        $context_tokens = [];
        if (!empty($context['audience'])) {
            $context_tokens = preg_split('/[^\p{L}\p{N}]+/u', strtolower($context['audience']), -1, PREG_SPLIT_NO_EMPTY);
        }

        $keywords = array_slice(array_unique(array_merge($tokens, $context_tokens)), 0, 6);

        if (!$keywords) {
            $keywords = ['startup', 'launch', 'validation'];
        }

        return $keywords;
    }

    /**
     * Collect each signal sequentially (PHP does not offer async natively, but the method keeps the interface flexible).
     *
     * @param array<string,callable> $callbacks Signal callbacks keyed by label.
     *
     * @return array<string,array>
     */
    private function collect_signals_parallel($callbacks) {
        $results = [];

        foreach ($callbacks as $key => $callback) {
            try {
                $results[$key] = is_callable($callback) ? $callback() : [];
            } catch (Exception $e) {
                $results[$key] = [
                    'score' => 0,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Market demand analysis using placeholder heuristics with hooks for real integrations.
     */
    private function analyze_market_demand($keywords) {
        $volume = $this->get_keyword_volume($keywords);
        $trends = $this->get_google_trends($keywords);

        $score = $this->calculate_demand_score([
            'total_volume' => array_sum(array_column($volume, 'volume')),
            'trend_direction' => isset($trends['trend']) ? (int) $trends['trend'] : 0,
            'seasonality' => $trends['seasonal_variance'] ?? 0,
            'growth_rate' => $trends['yoy_growth'] ?? 0,
        ]);

        return [
            'score' => $score,
            'volume_data' => $volume,
            'trends' => $trends,
            'insight' => $this->generate_demand_insight($score, $trends),
        ];
    }

    /**
     * Competition analysis placeholder.
     */
    private function analyze_competition($keywords) {
        $ph_products = $this->search_producthunt($keywords);
        $github_repos = $this->search_github($keywords);
        $domains = $this->check_domain_availability($keywords);

        $saturation = $this->calculate_saturation([
            'ph_count' => count($ph_products),
            'ph_avg_upvotes' => $this->avg_upvotes($ph_products),
            'github_count' => count($github_repos),
            'domains_available' => count(array_filter($domains, fn($d) => !empty($d['available']))),
        ]);

        return [
            'score' => max(0, 100 - $saturation),
            'saturation_level' => $this->get_saturation_label($saturation),
            'competitors' => array_slice($ph_products, 0, 5),
            'opportunities' => $this->identify_gaps($ph_products),
            'insight' => $this->generate_competition_insight($saturation, $ph_products),
        ];
    }

    /**
     * Monetization assessment.
     */
    private function analyze_monetization($idea, $context = []) {
        $pricing_hint = $this->estimate_price_tolerance($idea, $context);
        $models = $this->suggest_revenue_models($idea, $context);

        $score = $this->normalize_score( ($pricing_hint['confidence'] ?? 0) * 100 );

        return [
            'score' => $score,
            'recommended_model' => $models['primary'] ?? 'subscription',
            'models' => $models['alternatives'] ?? [],
            'insight' => $pricing_hint['insight'] ?? '',
        ];
    }

    /**
     * Technical feasibility assessment.
     */
    private function assess_feasibility($idea, $context = []) {
        $complexity = $this->estimate_technical_complexity($idea, $context);
        $team_fit = $context['team_experience'] ?? '';

        $score = $this->normalize_score(100 - ($complexity['effort_score'] ?? 50));

        return [
            'score' => $score,
            'complexity' => $complexity,
            'team_fit' => $team_fit,
            'insight' => $complexity['insight'] ?? '',
        ];
    }

    /**
     * Execute the AI enhanced validation.
     */
    private function get_ai_validation($idea, $context) {
        $prompt = $this->build_validation_prompt($idea, $context);

        $response = $this->call_openai([
            ['role' => 'system', 'content' => $this->get_validator_system_prompt()],
            ['role' => 'user', 'content' => $prompt],
        ], [
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.3,
            'max_tokens' => 1500,
        ]);

        $analysis = [];
        if ($response) {
            $analysis = json_decode($response, true);
        }

        if (!is_array($analysis) || empty($analysis)) {
            $analysis = $this->fallback_ai_validation($idea, $context);
        }

        $score = isset($analysis['viability_score']) ? (int) $analysis['viability_score'] : 0;

        return array_merge([
            'score' => $this->normalize_score($score),
        ], $analysis);
    }

    /**
     * Social proof analysis using deterministic heuristics.
     */
    private function analyze_social_proof($keywords) {
        $reddit_posts = $this->search_reddit($keywords, [
            'subreddits' => ['entrepreneur', 'startups', 'SaaS', 'smallbusiness'],
            'time_filter' => 'year',
            'limit' => 40,
        ]);

        $sentiment = $this->analyze_sentiment($reddit_posts);

        return [
            'score' => $this->calculate_social_score($sentiment, $reddit_posts),
            'discussions_found' => count($reddit_posts),
            'sentiment_breakdown' => $sentiment,
            'pain_points' => $this->extract_pain_points($reddit_posts),
            'common_requests' => $this->extract_feature_requests($reddit_posts),
            'insight' => $this->generate_social_insight($sentiment),
        ];
    }

    /**
     * Calculate weighted score and derive confidence.
     */
    private function calculate_weighted_score($signals) {
        $breakdown = [
            'market_demand' => $signals['market']['score'] ?? 0,
            'competition' => $signals['competition']['score'] ?? 0,
            'monetization' => $signals['monetize']['score'] ?? 0,
            'feasibility' => $signals['feasibility']['score'] ?? 0,
            'ai_analysis' => $signals['ai_insights']['score'] ?? 0,
            'social_proof' => $signals['social']['score'] ?? 0,
        ];

        $overall = 0;
        foreach (self::WEIGHTS as $key => $weight) {
            $overall += ($breakdown[$key] ?? 0) * $weight;
        }

        $confidence_score = $this->calculate_confidence($breakdown);

        return [
            'overall' => (int) round($overall),
            'breakdown' => $breakdown,
            'confidence' => $this->get_confidence_label($confidence_score),
            'confidence_score' => $confidence_score,
        ];
    }

    /**
     * Create contextual recommendations.
     */
    private function generate_recommendations($signals, $score) {
        $recommendations = [
            'immediate_actions' => [],
            'validation_steps' => [],
            'pivot_suggestions' => [],
            'target_adjustments' => [],
        ];

        if (($signals['market']['score'] ?? 0) < 50) {
            $recommendations['immediate_actions'][] = [
                'priority' => 'high',
                'action' => __('Run a demand validation landing page test.', 'product-launch'),
                'reason' => __('Market demand signals are below the safe threshold.', 'product-launch'),
                'expected_outcome' => __('Collect conversion data and qualitative feedback.', 'product-launch'),
            ];
        }

        if (($signals['competition']['score'] ?? 0) > 70) {
            $recommendations['pivot_suggestions'][] = [
                'priority' => 'medium',
                'suggestion' => __('Differentiate with a niche positioning or underserved audience.', 'product-launch'),
                'opportunities' => $signals['competition']['opportunities'] ?? [],
            ];
        }

        $recommendations['validation_steps'] = array_merge(
            $recommendations['validation_steps'],
            $this->extract_validation_steps($signals['ai_insights'] ?? [])
        );

        return apply_filters('pl_validation_recommendations', $recommendations, $signals, $score);
    }

    /**
     * Map signals into the 8-phase launch plan scaffold.
     */
    private function map_to_phases($signals, $recommendations) {
        return [
            'market_clarity' => [
                'target_audience' => $signals['ai_insights']['ideal_customer_profile']['demographics'] ?? '',
                'pain_points' => $signals['social']['pain_points'] ?? [],
                'value_proposition' => $this->generate_value_prop($signals),
                'market_size' => $this->format_market_size($signals['market'] ?? []),
                'competitors' => $signals['competition']['competitors'] ?? [],
                'positioning' => $signals['ai_insights']['differentiation_strategy'] ?? '',
            ],
            'create_offer' => [
                'main_offer' => $this->generate_offer_description($signals),
                'pricing_strategy' => $this->suggest_pricing($signals['monetize'] ?? []),
                'value_proposition' => $signals['ai_insights']['differentiation_strategy'] ?? '',
                'bonuses' => $this->suggest_bonuses($signals),
                'guarantee' => $this->suggest_guarantee($signals),
            ],
            'create_service' => [
                'service_concept' => $signals['ai_insights']['revenue_models'][0] ?? '',
                'delivery_method' => $this->suggest_delivery_method($signals),
                'pricing_model' => $signals['monetize']['recommended_model'] ?? 'subscription',
                'service_framework' => $recommendations['immediate_actions'] ?? [],
            ],
            'build_funnel' => [
                'funnel_strategy' => $this->suggest_funnel_strategy($signals),
                'lead_magnet' => $this->suggest_lead_magnet($signals['social']['pain_points'] ?? []),
                'landing_pages' => $this->suggest_landing_copy($signals),
            ],
            'email_sequences' => [
                'email_strategy' => $this->suggest_email_strategy($signals),
                'email_subject_lines' => $this->generate_subject_lines($signals),
                'email_sequence_outline' => $this->generate_sequence_outline($signals),
            ],
            'organic_posts' => [
                'content_strategy' => $this->suggest_content_strategy($signals),
                'platform_strategy' => $this->identify_best_platforms($signals),
                'content_themes' => $signals['social']['common_requests'] ?? [],
            ],
            'facebook_ads' => [
                'campaign_strategy' => $this->suggest_ad_strategy($signals),
                'audience_targeting' => $signals['ai_insights']['ideal_customer_profile']['psychographics'] ?? '',
                'ad_creative' => $this->suggest_ad_angles($signals),
            ],
            'launch' => [
                'launch_strategy' => $this->create_launch_plan($signals, $recommendations),
                'launch_timeline' => $signals['ai_insights']['estimated_launch_timeline'] ?? '',
                'pre_launch_checklist' => $recommendations['validation_steps'] ?? [],
            ],
        ];
    }

    /**
     * Perform the OpenAI request with graceful error handling.
     */
    private function call_openai($messages, $options = []) {
        if (!function_exists('wp_remote_post')) {
            return false;
        }

        $settings = function_exists('pl_get_settings') ? pl_get_settings() : [];
        $api_key = $settings['openai_key'] ?? '';

        if (empty($api_key)) {
            return false;
        }

        $defaults = [
            'model' => $settings['ai_model'] ?? 'gpt-4o-mini',
            'temperature' => 0.7,
            'max_tokens' => 1200,
        ];

        $payload = array_merge($defaults, $options, [
            'messages' => $messages,
        ]);

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($payload),
            'timeout' => isset($settings['timeout']) ? (int) $settings['timeout'] : 45,
        ]);

        if (is_wp_error($response)) {
            error_log('PL Validation OpenAI Error: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        return $body['choices'][0]['message']['content'] ?? false;
    }

    /**
     * Create a validation identifier that can be reused as an external reference.
     */
    private function generate_validation_id() {
        return 'val_' . wp_generate_password(16, false);
    }

    /**
     * Provide the system prompt for the validator persona.
     */
    private function get_validator_system_prompt() {
        return <<<PROMPT
You are an expert business validator with 20 years of experience in market research, product launches, and entrepreneurship.

Your task is to provide honest, data-driven validation of business ideas. Consider:
- Market viability and timing
- Competitive landscape
- Technical feasibility
- Monetization potential
- Target audience clarity
- Execution complexity

Provide scores (0-100), specific insights, and actionable recommendations.
Return responses as valid JSON only.
PROMPT;
    }

    /**
     * Build the AI validation prompt.
     */
    private function build_validation_prompt($idea, $context) {
        $context_str = '';
        if (!empty($context)) {
            $context_str = "\n\nAdditional Context:\n" . wp_json_encode($context, JSON_PRETTY_PRINT);
        }

        return <<<PROMPT
Validate this business idea comprehensively:

Idea: {$idea}{$context_str}

Provide a JSON response with:
{
  "viability_score": 0-100,
  "swot_analysis": {
    "strengths": [],
    "weaknesses": [],
    "opportunities": [],
    "threats": []
  },
  "ideal_customer_profile": {
    "demographics": "",
    "psychographics": "",
    "pain_points": [],
    "online_behavior": ""
  },
  "revenue_models": [],
  "key_risks": [],
  "differentiation_strategy": "",
  "estimated_launch_timeline": "",
  "minimum_viable_features": []
}
PROMPT;
    }

    /**
     * Fallback AI validation structure when no response is available.
     */
    private function fallback_ai_validation($idea, $context) {
        $baseline_score = min(90, max(40, strlen($idea)));

        return [
            'viability_score' => $baseline_score,
            'swot_analysis' => [
                'strengths' => [__('Clear problem statement identified.', 'product-launch')],
                'weaknesses' => [__('Requires deeper customer interviews.', 'product-launch')],
                'opportunities' => [__('Emerging demand for automated solutions.', 'product-launch')],
                'threats' => [__('Well-funded incumbents may react quickly.', 'product-launch')],
            ],
            'ideal_customer_profile' => [
                'demographics' => __('Professionals with budget authority', 'product-launch'),
                'psychographics' => __('Value efficiency and rapid iteration', 'product-launch'),
                'pain_points' => [__('Time-consuming manual workflows', 'product-launch')],
                'online_behavior' => __('Active in niche communities and LinkedIn groups', 'product-launch'),
            ],
            'revenue_models' => ['subscription', 'hybrid service/productized'],
            'key_risks' => [__('Need to prove clear ROI within 90 days.', 'product-launch')],
            'differentiation_strategy' => __('Position around measurable outcomes and support.', 'product-launch'),
            'estimated_launch_timeline' => __('8-12 weeks for MVP launch with lean team.', 'product-launch'),
            'minimum_viable_features' => [__('Core workflow automation', 'product-launch'), __('Analytics dashboard', 'product-launch')],
        ];
    }

    /**
     * Keyword volume placeholder returning deterministic data.
     */
    private function get_keyword_volume($keywords) {
        $results = [];
        $base = 700;

        foreach ($keywords as $keyword) {
            $results[] = [
                'keyword' => $keyword,
                'volume' => max(50, $base - (strlen($keyword) * 15)),
            ];
        }

        return apply_filters('pl_validation_keyword_volume', $results, $keywords);
    }

    /**
     * Simulated Google Trends data.
     */
    private function get_google_trends($keywords) {
        $trend = 0;
        if (!empty($keywords)) {
            $trend = strlen($keywords[0]) % 3 - 1; // -1, 0, 1
        }

        return apply_filters('pl_validation_trend_data', [
            'trend' => $trend,
            'seasonal_variance' => 15,
            'yoy_growth' => 8,
            'sample_period' => '24 months',
        ], $keywords);
    }

    /**
     * Translate raw demand indicators into a score.
     */
    private function calculate_demand_score($data) {
        $score = 0;
        $score += min(40, ($data['total_volume'] ?? 0) / 100);
        $score += ($data['trend_direction'] ?? 0) * 10;
        $score += max(-10, min(10, $data['growth_rate'] ?? 0));
        $score -= min(15, abs($data['seasonality'] ?? 0) / 2);

        return $this->normalize_score($score + 50);
    }

    /**
     * Human-readable demand insight.
     */
    private function generate_demand_insight($score, $trends) {
        if ($score >= 70) {
            return __('Strong consistent demand observed with positive growth signals.', 'product-launch');
        }

        if ($score >= 50) {
            return __('Moderate demand with stable year-over-year interest.', 'product-launch');
        }

        if (($trends['trend'] ?? 0) < 0) {
            return __('Demand is trending downward—validate before investing heavily.', 'product-launch');
        }

        return __('Demand signals are inconclusive; run lightweight experiments.', 'product-launch');
    }

    /**
     * Placeholder Product Hunt search results.
     */
    private function search_producthunt($keywords) {
        $results = [];
        foreach (array_slice($keywords, 0, 3) as $keyword) {
            $results[] = [
                'name' => ucwords($keyword) . ' Launch',
                'tagline' => sprintf(__('Tools focusing on %s workflows.', 'product-launch'), $keyword),
                'upvotes' => 120 - strlen($keyword) * 3,
                'url' => 'https://www.producthunt.com',
            ];
        }
        return apply_filters('pl_validation_producthunt_results', $results, $keywords);
    }

    /**
     * Placeholder GitHub results.
     */
    private function search_github($keywords) {
        $results = [];
        foreach (array_slice($keywords, 0, 2) as $keyword) {
            $results[] = [
                'name' => sanitize_title($keyword) . '-toolkit',
                'stars' => max(5, 120 - strlen($keyword) * 4),
                'url' => 'https://github.com',
            ];
        }
        return apply_filters('pl_validation_github_results', $results, $keywords);
    }

    /**
     * Domain availability mock.
     */
    private function check_domain_availability($keywords) {
        $domains = [];
        foreach ($keywords as $keyword) {
            $domains[] = [
                'domain' => sanitize_title($keyword) . '.com',
                'available' => (strlen($keyword) % 2 === 0),
            ];
        }
        return apply_filters('pl_validation_domain_results', $domains, $keywords);
    }

    /**
     * Calculate market saturation proxy.
     */
    private function calculate_saturation($data) {
        $score = 0;
        $score += ($data['ph_count'] ?? 0) * 5;
        $score += ($data['github_count'] ?? 0) * 3;
        $score -= ($data['domains_available'] ?? 0) * 4;
        $score += ($data['ph_avg_upvotes'] ?? 0) / 5;

        return max(0, min(100, $score));
    }

    private function avg_upvotes($products) {
        if (!$products) {
            return 0;
        }
        $total = 0;
        foreach ($products as $product) {
            $total += $product['upvotes'] ?? 0;
        }
        return $total ? $total / count($products) : 0;
    }

    private function get_saturation_label($score) {
        if ($score >= 70) {
            return __('High saturation', 'product-launch');
        }
        if ($score >= 40) {
            return __('Moderate competition', 'product-launch');
        }
        return __('Low competition', 'product-launch');
    }

    private function identify_gaps($products) {
        $gaps = [];
        foreach ($products as $product) {
            if (!empty($product['tagline'])) {
                $gaps[] = sprintf(__('Differentiate from “%s” by specialising in execution speed.', 'product-launch'), $product['name']);
            }
        }
        return $gaps;
    }

    private function generate_competition_insight($saturation, $products) {
        if ($saturation >= 70) {
            return __('Crowded market—double down on positioning or niche audience.', 'product-launch');
        }
        if ($saturation >= 40) {
            return __('Identified competitors, but opportunities remain for differentiation.', 'product-launch');
        }
        if ($products) {
            return __('Few direct competitors—move quickly to establish authority.', 'product-launch');
        }
        return __('No major competitors surfaced; validate for latent demand.', 'product-launch');
    }

    private function estimate_price_tolerance($idea, $context) {
        $length = strlen($idea);
        $confidence = min(1, max(0.3, $length / 400));

        return [
            'confidence' => $confidence,
            'insight' => __('Customers indicate willingness to pay for measurable ROI.', 'product-launch'),
        ];
    }

    private function suggest_revenue_models($idea, $context) {
        $models = ['subscription', 'usage-based', 'service_retainer'];
        return [
            'primary' => $models[0],
            'alternatives' => array_slice($models, 1),
        ];
    }

    private function estimate_technical_complexity($idea, $context) {
        $effort = min(90, max(20, strlen($idea) / 3));
        return [
            'effort_score' => $effort,
            'insight' => __('Scope the MVP to a focused workflow to keep complexity manageable.', 'product-launch'),
        ];
    }

    private function analyze_sentiment($posts) {
        $positive = 0;
        $negative = 0;
        $neutral = 0;

        foreach ($posts as $post) {
            $score = $post['sentiment'] ?? 0;
            if ($score > 0) {
                $positive++;
            } elseif ($score < 0) {
                $negative++;
            } else {
                $neutral++;
            }
        }

        $total = max(1, count($posts));

        return [
            'positive' => $positive,
            'negative' => $negative,
            'neutral' => $neutral,
            'positive_ratio' => $positive / $total,
            'negative_ratio' => $negative / $total,
        ];
    }

    private function calculate_social_score($sentiment, $posts) {
        $score = 50;
        $score += ($sentiment['positive_ratio'] ?? 0) * 40;
        $score -= ($sentiment['negative_ratio'] ?? 0) * 30;
        $score += min(10, count($posts));

        return $this->normalize_score($score);
    }

    private function extract_pain_points($posts) {
        $pain_points = [];
        foreach ($posts as $post) {
            if (!empty($post['pain_point'])) {
                $pain_points[] = sanitize_text_field($post['pain_point']);
            }
        }
        return array_values(array_unique($pain_points));
    }

    private function extract_feature_requests($posts) {
        $requests = [];
        foreach ($posts as $post) {
            if (!empty($post['request'])) {
                $requests[] = sanitize_text_field($post['request']);
            }
        }
        return array_values(array_unique($requests));
    }

    private function generate_social_insight($sentiment) {
        if (($sentiment['positive'] ?? 0) > ($sentiment['negative'] ?? 0)) {
            return __('Communities express optimism—highlight case studies to convert interest.', 'product-launch');
        }
        if (($sentiment['negative'] ?? 0) > ($sentiment['positive'] ?? 0)) {
            return __('Address recurring objections in your messaging before launch.', 'product-launch');
        }
        return __('Mixed conversations detected—use interviews to gather qualitative insights.', 'product-launch');
    }

    private function extract_validation_steps($ai_insights) {
        $steps = $ai_insights['minimum_viable_features'] ?? [];
        if (!$steps) {
            $steps = [
                __('Conduct five customer discovery interviews.', 'product-launch'),
                __('Launch a concierge-style pilot offer.', 'product-launch'),
            ];
        }
        return $steps;
    }

    private function generate_value_prop($signals) {
        $unique = $signals['ai_insights']['differentiation_strategy'] ?? '';
        if ($unique) {
            return $unique;
        }
        return __('Deliver faster outcomes with a guided launch playbook.', 'product-launch');
    }

    private function format_market_size($market_signal) {
        $volume = array_sum(array_column($market_signal['volume_data'] ?? [], 'volume'));
        if ($volume > 2000) {
            return __('Large and growing search demand.', 'product-launch');
        }
        if ($volume > 800) {
            return __('Meaningful demand in a focused niche.', 'product-launch');
        }
        return __('Emerging interest—validate through targeted outreach.', 'product-launch');
    }

    private function generate_offer_description($signals) {
        return __('A guided program that delivers measurable results in under 90 days.', 'product-launch');
    }

    private function suggest_pricing($monetize_signal) {
        $model = $monetize_signal['recommended_model'] ?? 'subscription';
        if ('subscription' === $model) {
            return __('Start with $99-$149/month with annual savings.', 'product-launch');
        }
        if ('usage-based' === $model) {
            return __('Introduce tiered usage pricing with clear overage policies.', 'product-launch');
        }
        return __('Use milestone-based retainers to align incentives.', 'product-launch');
    }

    private function suggest_bonuses($signals) {
        return [
            __('Onboarding workshop', 'product-launch'),
            __('Accountability check-ins', 'product-launch'),
        ];
    }

    private function suggest_guarantee($signals) {
        return __('30-day traction guarantee with documented milestones.', 'product-launch');
    }

    private function suggest_delivery_method($signals) {
        return __('Hybrid self-serve playbooks with expert support.', 'product-launch');
    }

    private function suggest_funnel_strategy($signals) {
        return __('Leverage educational webinars leading into an application funnel.', 'product-launch');
    }

    private function suggest_lead_magnet($pain_points) {
        if ($pain_points) {
            return sprintf(__('Checklist: solve %s in 14 days.', 'product-launch'), $pain_points[0]);
        }
        return __('Launch readiness scorecard.', 'product-launch');
    }

    private function suggest_landing_copy($signals) {
        return [
            __('Headline focused on measurable outcomes.', 'product-launch'),
            __('Social proof from early adopters.', 'product-launch'),
        ];
    }

    private function suggest_email_strategy($signals) {
        return __('3-part nurture emphasising ROI, case studies, and urgency.', 'product-launch');
    }

    private function generate_subject_lines($signals) {
        return [
            __('Ready to validate your next product in 30 days?', 'product-launch'),
            __('How we launch winning offers faster', 'product-launch'),
        ];
    }

    private function generate_sequence_outline($signals) {
        return [
            __('Day 0: Welcome & positioning', 'product-launch'),
            __('Day 2: Case study and proof', 'product-launch'),
            __('Day 4: Offer + CTA', 'product-launch'),
        ];
    }

    private function suggest_content_strategy($signals) {
        return __('Publish weekly founder stories and behind-the-scenes validation progress.', 'product-launch');
    }

    private function identify_best_platforms($signals) {
        return ['LinkedIn', 'Reddit', 'YouTube'];
    }

    private function suggest_ad_strategy($signals) {
        return __('Run retargeting ads focusing on ROI proof and social proof assets.', 'product-launch');
    }

    private function suggest_ad_angles($signals) {
        return [
            __('Launch faster without spinning up a full marketing team.', 'product-launch'),
            __('Proof-driven messaging with founder testimonials.', 'product-launch'),
        ];
    }

    private function create_launch_plan($signals, $recommendations) {
        return [
            __('Week 1-2: Validation interviews & landing page test.', 'product-launch'),
            __('Week 3-4: Beta cohort onboarding.', 'product-launch'),
            __('Week 5-6: Expand to paid acquisition.', 'product-launch'),
        ];
    }

    private function normalize_score($score) {
        return max(0, min(100, (int) round($score)));
    }

    private function calculate_confidence($breakdown) {
        $avg = array_sum($breakdown) / max(1, count($breakdown));
        $variance = 0;
        foreach ($breakdown as $value) {
            $variance += pow($value - $avg, 2);
        }
        $variance = $variance / max(1, count($breakdown));
        $stdev = sqrt($variance);

        $confidence = 100 - min(60, $stdev);
        return round($confidence, 2);
    }

    private function get_confidence_label($confidence) {
        if ($confidence >= 75) {
            return __('High', 'product-launch');
        }
        if ($confidence >= 55) {
            return __('Medium', 'product-launch');
        }
        return __('Low', 'product-launch');
    }

    /**
     * Simulated Reddit search returning normalized data points.
     */
    private function search_reddit($keywords, $args) {
        $posts = [];
        foreach (array_slice($keywords, 0, 3) as $keyword) {
            $posts[] = [
                'title' => sprintf(__('Discussion about %s challenges', 'product-launch'), $keyword),
                'sentiment' => (strlen($keyword) % 3) - 1,
                'pain_point' => sprintf(__('Need better %s workflow', 'product-launch'), $keyword),
                'request' => sprintf(__('Looking for %s automation', 'product-launch'), $keyword),
            ];
        }
        return apply_filters('pl_validation_reddit_results', $posts, $keywords, $args);
    }
}
