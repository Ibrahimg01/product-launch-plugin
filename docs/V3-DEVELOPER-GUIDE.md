# V3 Validation Engine - Developer Guide

## Architecture Overview
```
┌─────────────────────────────────────────────────────────┐
│                  WordPress Frontend                      │
│            (Ideas Library / Validation Form)             │
└───────────────────────┬─────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────┐
│              PL_Validation_API                           │
│         (Request Handler & Orchestration)                │
└───────────────────────┬─────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────┐
│          PL_Validation_Engine_V3                         │
│              (Core Validation Logic)                     │
└───────┬──────────┬──────────┬──────────┬───────┬────────┘
        │          │          │          │       │
        ▼          ▼          ▼          ▼       ▼
    ┌───────┐ ┌───────┐ ┌────────┐ ┌──────┐ ┌──────┐
    │OpenAI │ │SerpAPI│ │ Reddit │ │GitHub│ │ DB   │
    └───────┘ └───────┘ └────────┘ └──────┘ └──────┘
```

---

## Class Structure

### `PL_Validation_Engine_V3`

**Location:** `includes/idea-validation/class-pl-validation-engine-v3.php`

**Responsibilities:**
1. Orchestrate multi-signal data collection
2. Calculate composite scores with confidence intervals
3. Generate actionable recommendations
4. Map validation data to 8-phase system

**Public Methods:**
```php
public function validate_idea($business_idea, $context = [])
```
- **Input:** Business idea string + optional context array
- **Returns:** Array with validation results
- **Throws:** Never (uses fallbacks)

**Key Private Methods:**
```php
private function collect_signals_parallel($signals)
```
Non-blocking parallel execution of signal collectors.
```php
private function calculate_weighted_score($signals)
```
Aggregates individual signal scores using predefined weights.
```php
private function map_to_phases($signals, $recommendations)
```
Transforms raw validation data into phase-specific prefill structure.

---

## Database Schema

### Primary Table: `wp_pl_validations`
```sql
CREATE TABLE `wp_pl_validations` (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    validation_id VARCHAR(32) UNIQUE NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    site_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
    business_idea TEXT NOT NULL,
    
    -- Composite Scoring
    validation_score INT DEFAULT 0,
    confidence_level VARCHAR(20) NULL,
    confidence_score DECIMAL(5,2) DEFAULT 0,
    
    -- Individual Signal Scores
    market_demand_score INT DEFAULT 0,
    competition_score INT DEFAULT 0,
    monetization_score INT DEFAULT 0,
    feasibility_score INT DEFAULT 0,
    ai_analysis_score INT DEFAULT 0,
    social_proof_score INT DEFAULT 0,
    
    -- JSON Storage
    signals_data LONGTEXT NULL COMMENT 'Raw signal data',
    recommendations_data LONGTEXT NULL COMMENT 'Action items',
    phase_prefill_data LONGTEXT NULL COMMENT 'Phase mapping',
    
    -- Metadata
    validation_status VARCHAR(50) DEFAULT 'completed',
    library_published TINYINT(1) DEFAULT 0,
    published_at DATETIME NULL,
    expires_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY validation_id (validation_id),
    KEY user_site (user_id, site_id),
    KEY scores (validation_score, confidence_score),
    KEY published (library_published, expires_at)
);
```

### Cache Table: `wp_pl_validation_signals`
```sql
CREATE TABLE `wp_pl_validation_signals` (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    validation_id VARCHAR(32) NOT NULL,
    signal_type VARCHAR(50) NOT NULL,
    signal_data LONGTEXT NULL,
    cached_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    
    KEY validation_signal (validation_id, signal_type),
    KEY expires (expires_at)
);
```

---

## Signal Implementation Details

### 1. Market Demand Signal

**Method:** `analyze_market_demand($keywords)`

**Data Flow:**
```
Keywords → SerpAPI → Search volume data
        ↓
    Google Trends proxy (volume-based)
        ↓
    calculate_demand_score()
        ↓
    Return: score + insights
```

**Scoring Logic:**
```php
volume_score = min(100, (total_volume / 100,000) * 50)
trend_score = (trend_direction + 1) * 25  // Maps -1,0,1 to 0,25,50
growth_score = min(30, yoy_growth_rate)

final_score = volume_score + trend_score + growth_score
```

**Fallback:** Returns estimated ranges when SerpAPI unavailable.

---

### 2. Competition Signal

**Method:** `analyze_competition($keywords)`

**Data Sources:**
- ProductHunt (cached/manual for now - no public API)
- GitHub API (repository search)
- Domain availability checks

**Scoring Logic:**
```php
ph_saturation = min(50, product_count * 2)
github_saturation = min(30, repo_count)
domain_saturation = (domains_available == 0) ? 20 : 0

saturation = ph_saturation + github_saturation + domain_saturation
competition_score = 100 - saturation  // Inverted: lower competition = higher score
```

---

### 3. AI Analysis Signal

**Method:** `get_ai_validation($idea, $context)`

**Prompt Structure:**
```
System: Expert business validator with 20 years experience
User: Validate this idea with JSON response including:
    - viability_score (0-100)
    - swot_analysis
    - ideal_customer_profile
    - revenue_models
    - key_risks
    - differentiation_strategy
```

**Response Format:**
```json
{
  "viability_score": 75,
  "swot_analysis": {
    "strengths": ["..."],
    "weaknesses": ["..."],
    "opportunities": ["..."],
    "threats": ["..."]
  },
  "ideal_customer_profile": {
    "demographics": "...",
    "psychographics": "...",
    "pain_points": ["..."]
  },
  "revenue_models": ["SaaS subscription", "Usage-based"],
  "key_risks": ["..."],
  "differentiation_strategy": "...",
  "estimated_launch_timeline": "3-6 months"
}
```

---

### 4. Social Proof Signal

**Method:** `analyze_social_proof($keywords)`

**Data Flow:**
```
Keywords → Reddit OAuth → Search r/entrepreneur, r/startups, etc.
        ↓
    Extract posts (title + selftext)
        ↓
    analyze_sentiment() - Positive/Neutral/Negative
        ↓
    extract_pain_points() - NLP pattern matching
        ↓
    extract_feature_requests() - Wish list items
        ↓
    calculate_social_score()
```

**Scoring Logic:**
```php
sentiment_score = positive_percent - negative_percent
discussion_score = min(40, post_count * 2)

social_score = max(0, min(100, sentiment_score + discussion_score))
```

---

## Confidence Calculation

**Purpose:** Measure how much signals agree with each other.

**Algorithm:**
```php
1. Calculate mean of all 6 signal scores
2. Calculate standard deviation
3. confidence = 100 - standard_deviation
4. Label:
   - high: confidence >= 75
   - medium: 50 <= confidence < 75
   - low: confidence < 50
```

**Example:**
```
Signals: [80, 85, 78, 82, 79, 81]
Mean: 80.83
Std Dev: 2.48
Confidence: 97.52 → "high"
```

**Low confidence example:**
```
Signals: [90, 45, 70, 30, 85, 20]
Mean: 56.67
Std Dev: 28.64
Confidence: 71.36 → "medium"
```

---

## Phase Mapping Structure

**Method:** `map_to_phases($signals, $recommendations)`

**Returns:**
```php
[
    'market_clarity' => [
        'target_audience' => string,
        'pain_points' => array,
        'value_proposition' => string,
        'market_size' => string,
        'competitors' => array,
        'positioning' => string,
    ],
    'create_offer' => [...],
    'create_service' => [...],
    'build_funnel' => [...],
    'email_sequences' => [...],
    'organic_posts' => [...],
    'facebook_ads' => [...],
    'launch' => [...],
]
```

**Application:**
```php
pl_apply_v3_phase_data($validation_id, $user_id, $site_id, $overwrite = false);
```

Stores in WordPress user meta:
- Key: `product_launch_progress_{phase}_{site_id}`
- Value: JSON-encoded phase data
- Merge strategy: Preserves existing user input unless `$overwrite = true`

---

## API Integration Patterns

### OpenAI
```php
private function call_openai($messages, $options = []) {
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $this->openai_key,
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode([
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 2000,
            ...$ options
        ]),
        'timeout' => 45,
    ]);
    
    // Error handling + parsing
}
```

### Reddit OAuth
```php
private function get_reddit_access_token() {
    // Check 1-hour transient cache
    $cached = get_transient('pl_reddit_access_token');
    if ($cached) return $cached;
    
    // Request new token
    $response = wp_remote_post('https://www.reddit.com/api/v1/access_token', [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret),
        ],
        'body' => ['grant_type' => 'client_credentials'],
    ]);
    
    // Cache for 1 hour
    set_transient('pl_reddit_access_token', $token, 3600);
}
```

---

## Error Handling Strategy

**Philosophy:** Never fail completely - degrade gracefully.

1. **API Unavailable:**
   - Log error with `error_log()`
   - Use fallback data generators
   - Continue with remaining signals

2. **Invalid Response:**
   - Validate JSON structure
   - Return empty arrays/defaults
   - Flag in confidence calculation

3. **Rate Limits:**
   - Respect HTTP 429 responses
   - Use transient caching (1 hour for tokens, 24 hours for rarely-changing data)
   - Queue validation for retry if user-initiated

**Example:**
```php
$response = wp_remote_get($url);

if (is_wp_error($response)) {
    error_log('API Error: ' . $response->get_error_message());
    return $this->generate_fallback_data();
}

$code = wp_remote_retrieve_response_code($response);
if ($code === 429) {
    error_log('Rate limit hit - using cached data');
    return $this->get_cached_or_fallback();
}
```

---

## Testing

### Unit Testing Individual Signals

**Test file:** `tests/test-validation-v3.php`
```php
// Test keyword extraction
$engine = new PL_Validation_Engine_V3();
$keywords = $engine->extract_validation_keywords("AI scheduling tool");
assert(is_array($keywords) && count($keywords) >= 3);

// Test scoring logic
$mock_signals = [
    'market' => ['score' => 80],
    'competition' => ['score' => 70],
    // ...
];
$score = $engine->calculate_weighted_score($mock_signals);
assert($score['overall'] >= 0 && $score['overall'] <= 100);
```

### Integration Testing
```bash
# Full validation flow
wp eval '
$api = new PL_Validation_API();
$result = $api->submit_idea(
    "Project management tool for remote teams",
    get_current_user_id(),
    get_current_blog_id()
);
print_r($result);
'
```

### Monitoring in Production

**Log validation performance:**
```php
add_action('pl_validation_completed', function($validation_id, $duration) {
    error_log("Validation {$validation_id} completed in {$duration}s");
}, 10, 2);
```

---

## Performance Optimization

### Parallel Signal Collection

**Currently:** Sequential (15-30s per validation)
**Future:** Use async HTTP requests
```php
// Pseudo-code for future implementation
$promises = [
    'market' => async_http_get($serp_url),
    'reddit' => async_http_get($reddit_url),
    'github' => async_http_get($github_url),
];

$results = await_all($promises);
```

### Caching Strategy

1. **Reddit OAuth tokens:** 1 hour (transient)
2. **Keyword volume data:** 24 hours (for popular keywords)
3. **GitHub repo counts:** 6 hours
4. **Validation results:** 30 days (expires_at column)

---

## Extending the System

### Adding a New Signal

**Example: Adding "Funding Trends" signal (7th signal)**

1. **Update weights in engine:**
```php
const WEIGHTS = [
    'market_demand' => 0.22,
    'competition' => 0.18,
    'monetization' => 0.13,
    'feasibility' => 0.09,
    'ai_analysis' => 0.18,
    'social_proof' => 0.09,
    'funding_trends' => 0.11,  // NEW
];
```

2. **Add database column:**
```sql
ALTER TABLE wp_pl_validations 
ADD COLUMN funding_trends_score INT DEFAULT 0 
AFTER social_proof_score;
```

3. **Implement collector method:**
```php
private function analyze_funding_trends($keywords) {
    // Call Crunchbase API or similar
    $startups = $this->search_crunchbase($keywords);
    
    $score = $this->calculate_funding_score([
        'recent_rounds' => count($startups),
        'avg_funding' => array_avg($startups, 'funding_amount'),
        'investor_interest' => $this->measure_investor_activity($startups),
    ]);
    
    return [
        'score' => $score,
        'recent_startups' => $startups,
        'insight' => $this->generate_funding_insight($score),
    ];
}
```

4. **Add to validation flow:**
```php
$signals = $this->collect_signals_parallel([
    'market' => $this->analyze_market_demand($keywords),
    'competition' => $this->analyze_competition($keywords),
    'monetize' => $this->analyze_monetization($business_idea),
    'feasibility' => $this->assess_feasibility($business_idea),
    'ai_insights' => $this->get_ai_validation($business_idea, $context),
    'social' => $this->analyze_social_proof($keywords),
    'funding' => $this->analyze_funding_trends($keywords),  // NEW
]);
```

5. **Update UI template** to display 7th signal card.

---

## Security Considerations

### API Key Storage

**Current:** WordPress site options (plain text)
**Recommended for production:** Encryption at rest
```php
function pl_store_api_key_encrypted($service, $key) {
    $encryption_key = wp_salt('auth');
    $iv = openssl_random_pseudo_bytes(16);
    
    $encrypted = openssl_encrypt($key, 'AES-256-CBC', $encryption_key, 0, $iv);
    
    update_site_option("pl_api_{$service}", base64_encode($iv . $encrypted));
}
```

### Rate Limiting

**Prevent abuse of validation endpoint:**
```php
function pl_check_validation_rate_limit($user_id) {
    $key = "pl_validation_limit_{$user_id}";
    $count = get_transient($key) ?: 0;
    
    if ($count >= 10) {  // 10 validations per hour
        return new WP_Error('rate_limit', 'Too many validations. Please wait.');
    }
    
    set_transient($key, $count + 1, HOUR_IN_SECONDS);
    return true;
}
```

### Input Sanitization

**All user input must be sanitized:**
```php
$business_idea = sanitize_textarea_field($_POST['business_idea']);
$business_idea = wp_kses_post($business_idea);  // Strip dangerous HTML
```

---

## Deployment Checklist

- [ ] Run database migration: `pl_migrate_validation_schema_to_v3()`
- [ ] Configure OpenAI API key (required)
- [ ] Configure SerpAPI key (recommended)
- [ ] Configure Reddit API (optional)
- [ ] Test validation flow with real idea
- [ ] Verify phase prefill works
- [ ] Check API usage in first week
- [ ] Monitor error logs for API failures
- [ ] Set up budget alerts for API costs

---

## Troubleshooting

### OpenAI Errors

**Error:** "Invalid API key"
**Fix:** Check key in Settings, ensure no extra spaces

**Error:** "Rate limit exceeded"
**Fix:** Upgrade OpenAI plan or implement request queuing

### SerpAPI Issues

**Error:** "Insufficient credits"
**Fix:** Check dashboard, refill credits

**Error:** "Search failed"
**Fix:** Verify firewall allows serpapi.com, check logs

### Reddit API Issues

**Error:** "401 Unauthorized"
**Fix:** Regenerate client secret, update settings

**Error:** "Too many requests"
**Fix:** Reddit allows 60 requests/minute - add delay between calls

---

## Maintenance

### Weekly Tasks
- Check API usage/costs
- Review error logs for patterns
- Clear expired validation cache

### Monthly Tasks
- Audit validation quality (user feedback)
- Update keyword fallback data with recent trends
- Review and adjust signal weights if needed

### Quarterly Tasks
- Update API integrations (check for new features)
- Analyze most-validated niches
- Optimize slow signals

---

*Last updated: January 2024*

