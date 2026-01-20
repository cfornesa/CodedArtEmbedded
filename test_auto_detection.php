<?php
/**
 * Test Domain-Based Database Auto-Detection
 */

require_once(__DIR__ . '/config/environment.php');

echo "==============================================\n";
echo "Domain-Based Auto-Detection Test\n";
echo "==============================================\n\n";

// Test 1: Current domain detection
$currentDomain = getCurrentDomain();
echo "Current Domain: $currentDomain\n";

// Test 2: Auto-detect database type
$detectedType = autoDetectDatabaseType();
echo "Auto-Detected DB_TYPE: $detectedType\n";

// Test 3: Should use MySQL?
$shouldUseMysql = shouldUseMysql();
echo "Should use MySQL: " . ($shouldUseMysql ? 'yes' : 'no') . "\n";

// Test 4: Environment detection
echo "Environment: " . getEnvironment() . "\n";
echo "Is Production: " . (isProduction() ? 'yes' : 'no') . "\n";
echo "Is Replit: " . (isReplit() ? 'yes' : 'no') . "\n";
echo "Is Localhost: " . (isLocalhost() ? 'yes' : 'no') . "\n\n";

// Test 5: Simulate different domains
echo "--- Simulated Domain Tests ---\n\n";

$testDomains = [
    'localhost' => 'sqlite',
    'localhost:8000' => 'sqlite',
    'codedart.org' => 'mysql',
    'www.codedart.org' => 'mysql',
    'codedart.cfornesa.com' => 'mysql',
    'codedart.fornesus.com' => 'mysql',
    'example.repl.co' => 'sqlite',
    'random-domain.com' => 'sqlite'
];

foreach ($testDomains as $domain => $expected) {
    // Temporarily override HTTP_HOST
    $_SERVER['HTTP_HOST'] = $domain;
    $detected = autoDetectDatabaseType();
    $status = ($detected === $expected) ? '✓ PASS' : '✗ FAIL';
    echo "$status | $domain → detected: $detected (expected: $expected)\n";
}

echo "\n==============================================\n";
echo "Test Complete\n";
echo "==============================================\n";
