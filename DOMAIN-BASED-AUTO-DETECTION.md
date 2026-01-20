# Domain-Based Database Auto-Detection

**Feature Added:** 2026-01-20
**Status:** ✅ Implemented and Tested
**Branch:** `claude/consolidate-duplicate-variables-c0kaZ`

---

## Overview

The system now **automatically detects** which database type to use (SQLite or MySQL) based on the domain name, eliminating the most common deployment configuration error.

### Benefits

✅ **Same config.php works on both Replit and Hostinger**
✅ **No need to manually set DB_TYPE**
✅ **Automatic switching when domain changes**
✅ **Reduces deployment errors**
✅ **Safe default (SQLite) for unknown domains**
✅ **Manual override still available if needed**

---

## How It Works

### Detection Logic

The system checks the `HTTP_HOST` server variable and determines the database type:

```php
function autoDetectDatabaseType() {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Production domains use MySQL
    $mysqlDomains = [
        'codedart.org',
        'www.codedart.org',
        'codedart.cfornesa.com',
        'codedart.fornesus.com'
    ];

    if (in_array($host, $mysqlDomains)) {
        return 'mysql';
    }

    // Development/Replit/localhost use SQLite
    return 'sqlite';
}
```

### Domain → Database Mapping

| Domain | Auto-Detected Type | Environment |
|--------|-------------------|-------------|
| `codedart.org` | `mysql` | Production (Hostinger) |
| `www.codedart.org` | `mysql` | Production (Hostinger) |
| `codedart.cfornesa.com` | `mysql` | Production (Hostinger) |
| `codedart.fornesus.com` | `mysql` | Production (Hostinger) |
| `localhost` | `sqlite` | Development |
| `localhost:8000` | `sqlite` | Development |
| `*.repl.co` | `sqlite` | Replit Development |
| Any other domain | `sqlite` | Safe default |

---

## Usage

### Automatic Mode (Recommended)

In your `config/config.php`, simply **omit** the `DB_TYPE` definition:

```php
<?php
// config/config.php

define('ENVIRONMENT', 'production'); // or 'development'

// DB_TYPE is NOT defined - will auto-detect based on domain

// For MySQL (production):
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

// For SQLite (development):
define('DB_PATH', __DIR__ . '/../codedart.db');
```

**Result:**
- On `codedart.org` → Automatically uses MySQL
- On `localhost` or Replit → Automatically uses SQLite
- Same config.php works everywhere!

### Manual Override Mode

If you need to manually specify the database type:

```php
<?php
// config/config.php

// Manually override auto-detection:
define('DB_TYPE', 'mysql'); // or 'sqlite'

// Rest of config...
```

**Use Cases for Manual Override:**
- Testing MySQL on localhost
- Forcing SQLite on a production domain (not recommended)
- Using a custom domain not in the auto-detection list

---

## Implementation Details

### Files Modified

1. **`config/environment.php`** (lines 69-99)
   - Added `autoDetectDatabaseType()` function
   - Added `shouldUseMysql()` helper function

2. **`config/database.php`** (lines 32-42)
   - Auto-detection logic added before connection
   - Logs detected type to error log

3. **`config/config.example.php`** (lines 20-52)
   - Documented auto-detection feature
   - Added examples and comments

### Auto-Detection Code

**Location:** `config/database.php` (lines 32-42)

```php
// Auto-detect DB_TYPE based on domain if not explicitly set
if (!defined('DB_TYPE')) {
    // Load environment detection functions
    require_once __DIR__ . '/environment.php';

    // Auto-detect database type based on domain
    $detectedType = autoDetectDatabaseType();
    define('DB_TYPE', $detectedType);

    error_log('Database: Auto-detected DB_TYPE as "' . $detectedType . '" based on domain: ' . getCurrentDomain());
}
```

### Helper Functions

**Location:** `config/environment.php`

```php
/**
 * Auto-detect database type based on domain
 *
 * @return string 'mysql' for production domains, 'sqlite' for development
 */
function autoDetectDatabaseType() { ... }

/**
 * Check if current domain should use MySQL
 *
 * @return bool True if domain should use MySQL
 */
function shouldUseMysql() {
    return autoDetectDatabaseType() === 'mysql';
}
```

---

## Testing

### Test Results

All auto-detection tests passed successfully:

```bash
php test_auto_detection.php
```

**Output:**
```
✓ PASS | localhost → detected: sqlite (expected: sqlite)
✓ PASS | localhost:8000 → detected: sqlite (expected: sqlite)
✓ PASS | codedart.org → detected: mysql (expected: mysql)
✓ PASS | www.codedart.org → detected: mysql (expected: mysql)
✓ PASS | codedart.cfornesa.com → detected: mysql (expected: mysql)
✓ PASS | codedart.fornesus.com → detected: mysql (expected: mysql)
✓ PASS | example.repl.co → detected: sqlite (expected: sqlite)
✓ PASS | random-domain.com → detected: sqlite (expected: sqlite)
```

### Core System Tests

Slug system tests still pass with auto-detection:

```bash
php test_slug_core.php
```

**Result:** 18/18 tests passed (100%)

---

## Deployment Impact

### Before Auto-Detection

**Problem:** Had to manually change `DB_TYPE` in config.php for each environment

**Replit config.php:**
```php
define('DB_TYPE', 'sqlite'); // ← Must remember to set this
```

**Hostinger config.php:**
```php
define('DB_TYPE', 'mysql'); // ← Must remember to change this
```

**Risk:** Forgetting to change DB_TYPE leads to wrong database type being used

### After Auto-Detection

**Solution:** Same config.php works on both platforms

**Universal config.php:**
```php
// DB_TYPE omitted - auto-detects based on domain
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_pass');
define('DB_PATH', __DIR__ . '/../codedart.db'); // For SQLite
```

**Benefit:** Zero configuration changes needed between environments

---

## Safeguards

### Layer 0: Domain-Based Auto-Detection

See [DEPLOYMENT-SAFEGUARDS.md](DEPLOYMENT-SAFEGUARDS.md) for full documentation.

**Key Points:**
- Auto-detection happens if `DB_TYPE` is not manually defined
- Logs detected type to error log for debugging
- Validated in `database.php` before connection
- Safe default: Defaults to SQLite for unknown domains
- Production domains explicitly listed in code

---

## Adding New Production Domains

To add a new domain that should use MySQL:

1. Edit `config/environment.php`
2. Add domain to `$mysqlDomains` array in `autoDetectDatabaseType()`:

```php
$mysqlDomains = [
    'codedart.org',
    'www.codedart.org',
    'codedart.cfornesa.com',
    'codedart.fornesus.com',
    'yournewdomain.com' // ← Add here
];
```

3. Commit and push changes
4. No config.php changes needed!

---

## Troubleshooting

### How to Check What Was Auto-Detected

**Option 1:** Check error logs
```bash
tail -f /path/to/error.log | grep "Auto-detected"
```

**Output:**
```
Auto-detected DB_TYPE as "sqlite" based on domain: localhost
```

**Option 2:** Run test script
```bash
php test_auto_detection.php
```

**Output:**
```
Current Domain: localhost
Auto-Detected DB_TYPE: sqlite
Should use MySQL: no
```

### Force Manual Database Type

If auto-detection isn't working as expected:

```php
// config/config.php
define('DB_TYPE', 'mysql'); // Overrides auto-detection
```

### Verify Connection

Check that database connection is using the correct type:

```bash
php -r "
require 'config/config.php';
require 'config/database.php';
\$pdo = getDBConnection();
echo 'Driver: ' . \$pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . PHP_EOL;
"
```

**Expected Output (on localhost):**
```
Driver: sqlite
```

**Expected Output (on codedart.org):**
```
Driver: mysql
```

---

## Migration from Manual DB_TYPE

### If You Already Have config.php with DB_TYPE Defined

**You don't need to change anything!**

The auto-detection only triggers if `DB_TYPE` is **not defined**. Your existing config.php will continue to work exactly as before.

### To Switch to Auto-Detection (Optional)

1. **Backup your config.php:**
```bash
cp config/config.php config/config.php.backup
```

2. **Edit config/config.php:**
```php
// Comment out or remove this line:
// define('DB_TYPE', 'sqlite');
```

3. **Test the connection:**
```bash
php test_auto_detection.php
```

4. **Verify everything works:**
```bash
php test_slug_core.php
```

---

## Documentation Updates

The following documentation has been updated to reflect auto-detection:

- ✅ [DEPLOYMENT-GUIDE.md](DEPLOYMENT-GUIDE.md) - Usage instructions and examples
- ✅ [DEPLOYMENT-SAFEGUARDS.md](DEPLOYMENT-SAFEGUARDS.md) - Layer 0 safeguard documentation
- ✅ [config.example.php](config/config.example.php) - Template with auto-detection comments

---

## FAQ

### Q: Is auto-detection safe?
**A:** Yes. It uses explicit domain matching and defaults to SQLite (safer for development) for unknown domains.

### Q: Can I still manually set DB_TYPE?
**A:** Yes. If you define `DB_TYPE` in config.php, it will override auto-detection.

### Q: What happens on a new domain not in the list?
**A:** It defaults to SQLite (development mode), which is the safer option.

### Q: How do I add a new production domain?
**A:** Edit `config/environment.php` and add the domain to the `$mysqlDomains` array in `autoDetectDatabaseType()`.

### Q: Does this work with subdomains?
**A:** Yes, but you must explicitly add each subdomain to the list. Wildcards are not supported.

### Q: Will this break my existing setup?
**A:** No. If `DB_TYPE` is already defined in your config.php, auto-detection is skipped.

---

## Related Files

- **`config/environment.php`** - Auto-detection functions
- **`config/database.php`** - Database connection with auto-detection
- **`config/config.example.php`** - Configuration template
- **`test_auto_detection.php`** - Auto-detection test suite
- **`DEPLOYMENT-GUIDE.md`** - Deployment instructions
- **`DEPLOYMENT-SAFEGUARDS.md`** - Safety mechanisms documentation

---

## Changelog

**2026-01-20:**
- ✅ Implemented domain-based auto-detection
- ✅ Added `autoDetectDatabaseType()` and `shouldUseMysql()` functions
- ✅ Updated database.php to use auto-detection
- ✅ Updated documentation
- ✅ Created test suite
- ✅ All tests passing (100%)

---

**Created by:** Claude (Sonnet 4.5)
**Addresses User Request:** "is there a way to ensure that the MySQL database configuration is used only when the domain name is 'codedart.org' and default to SQLite otherwise?"
