#!/bin/bash
# Comprehensive Database Fix and Validation Script
# Run with: bash config/fix_database.sh
# Must be run from the CodedArtEmbedded directory

set -e  # Exit on any error

echo "=========================================="
echo "DATABASE DIAGNOSTIC AND FIX SCRIPT"
echo "=========================================="
echo ""

# Step 1: Find the database file
echo "Step 1: Locating database..."
if [ -f "config/codedart.db" ]; then
    DB_PATH="config/codedart.db"
    echo "✓ Found database at: $DB_PATH"
else
    echo "❌ Database not found at expected location"
    echo "Creating new database..."
    touch config/codedart.db
    DB_PATH="config/codedart.db"
    echo "✓ Created database at: $DB_PATH"
fi
echo ""

# Step 2: Check current state
echo "Step 2: Checking current database state..."
php -r "
\$pdo = new PDO('sqlite:config/codedart.db');
\$tables = \$pdo->query(\"SELECT name FROM sqlite_master WHERE type='table'\")->fetchAll(PDO::FETCH_COLUMN);
echo 'Tables found: ' . count(\$tables) . PHP_EOL;
foreach (\$tables as \$table) {
    echo '  - ' . \$table . PHP_EOL;
}
"
echo ""

# Step 3: Initialize all tables
echo "Step 3: Initializing/fixing database tables..."
php config/init_all_tables.php
echo ""

# Step 4: Verify schema
echo "Step 4: Verifying schema..."
php config/check_threejs_schema.php
echo ""

# Step 5: Test save functionality (if test script exists)
if [ -f "config/test_threejs_save.php" ]; then
    echo "Step 5: Testing Three.js save functionality..."
    php config/test_threejs_save.php
    echo ""
else
    echo "Step 5: Skipping save test (test_threejs_save.php not found)"
    echo ""
fi

echo "=========================================="
echo "DATABASE FIX COMPLETE"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Try creating a new Three.js piece"
echo "2. Set background color and scale animation"
echo "3. Save and verify it works"
echo ""
