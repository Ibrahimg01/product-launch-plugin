<?php
/**
 * V3 Validation Engine Tests
 * Run with: wp eval-file tests/test-validation-v3.php
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../../');
    require_once ABSPATH . 'wp-load.php';
}

function test_v3_engine() {
    echo "Testing V3 Validation Engine...\n\n";
    
    // Test 1: Engine instantiation
    echo "1. Testing engine instantiation... ";
    $engine = new PL_Validation_Engine_V3();
    echo $engine ? "✅ PASS\n" : "❌ FAIL\n";
    
    // Test 2: Keyword extraction
    echo "2. Testing keyword extraction... ";
    $test_idea = "AI-powered tool for small business marketing automation";
    $keywords = $engine->extract_validation_keywords($test_idea);
    echo is_array($keywords) && count($keywords) > 0 ? "✅ PASS\n" : "❌ FAIL\n";
    
    // Test 3: Database schema
    echo "3. Testing database schema... ";
    global $wpdb;
    $table = $wpdb->prefix . 'pl_validations';
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE '%score%'");
    echo count($columns) >= 7 ? "✅ PASS (Found " . count($columns) . " score columns)\n" : "❌ FAIL\n";
    
    // Test 4: API integration
    echo "4. Testing API class integration... ";
    $api = new PL_Validation_API();
    echo method_exists($api, 'submit_idea') ? "✅ PASS\n" : "❌ FAIL\n";
    
    // Test 5: Phase mapping
    echo "5. Testing phase mapping function... ";
    echo function_exists('pl_apply_v3_phase_data') ? "✅ PASS\n" : "❌ FAIL\n";
    
    // Test 6: No demo code
    echo "6. Checking for demo code removal... ";
    $api_file = file_get_contents(PL_PLUGIN_DIR . 'includes/idea-validation/class-pl-validation-api.php');
    $has_demo = (strpos($api_file, 'handle_demo') !== false || strpos($api_file, 'generate_demo') !== false);
    echo !$has_demo ? "✅ PASS\n" : "❌ FAIL (Demo code still present)\n";
    
    echo "\nAll tests completed!\n";
}

test_v3_engine();
