#!/bin/bash
# Comprehensive Validation Script
# Checks all database tables and P5.js configuration
# Run with: bash config/validate_all.sh

echo "=========================================="
echo "COMPREHENSIVE VALIDATION SCRIPT"
echo "=========================================="
echo ""

# 1. Check Three.js schema
echo "1. Checking Three.js schema..."
php /home/user/CodedArtEmbedded/config/check_threejs_schema.php
echo ""

# 2. Check P5.js piece configuration
echo "2. Checking P5.js piece configuration..."
php /home/user/CodedArtEmbedded/config/check_p5_piece.php
echo ""

# 3. List all tables
echo "3. Listing all database tables..."
php -r "
\$pdo = new PDO('sqlite:/home/user/CodedArtEmbedded/config/codedart.db');
\$tables = \$pdo->query(\"SELECT name FROM sqlite_master WHERE type='table'\")->fetchAll(PDO::FETCH_COLUMN);
echo 'Total tables: ' . count(\$tables) . PHP_EOL;
foreach (\$tables as \$table) {
    \$count = \$pdo->query(\"SELECT COUNT(*) FROM \$table\")->fetchColumn();
    echo \"  - \$table: \$count rows\" . PHP_EOL;
}
"
echo ""

echo "=========================================="
echo "VALIDATION COMPLETE"
echo "=========================================="
echo ""
echo "If you see errors above, run: bash config/fix_database.sh"
echo ""
