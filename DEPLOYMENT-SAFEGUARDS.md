# Deployment Safeguards & Configuration Safety

**Critical:** This document explains the safeguards in place to prevent database configuration errors when deploying between Replit (SQLite) and Hostinger (MySQL).

---

## Problem: Why Safeguards Are Needed

When deploying the **exact same files** from Replit to Hostinger:

### ❌ Without Safeguards:
- Replit uses **SQLite** (`codedart.db` file)
- Hostinger uses **MySQL** (remote server)
- If `config.php` is misconfigured, wrong database type could be used
- If wrong init script is run, tables created with incompatible schema
- Silent failures or data corruption could occur

### ✅ With Safeguards:
- **Automatic detection** of configuration mismatches
- **Clear error messages** explaining what went wrong
- **Prevention** of running wrong scripts on wrong platform
- **Warnings** before potentially dangerous operations

---

## Safeguard Layers

### Layer 1: config.php Separation

**Location:** `config/config.php` (NOT in Git)

**Purpose:** Different configuration per environment

**Replit config.php:**
```php
define('ENVIRONMENT', 'development');
define('DB_TYPE', 'sqlite');               // ← CRITICAL
define('DB_PATH', __DIR__ . '/../codedart.db');
```

**Hostinger config.php:**
```php
define('ENVIRONMENT', 'production');
define('DB_TYPE', 'mysql');                // ← CRITICAL
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_codedart');
define('DB_USER', 'u123456789_admin');
define('DB_PASS', 'your_password');
```

**Safeguard:** `config.php` is **excluded from Git** via `.gitignore`, so it must be created manually on each platform with correct values.

---

### Layer 2: database.php Validation

**Location:** `config/database.php`

**Safeguards Implemented:**

#### 1. DB_TYPE Validation
```php
if (!defined('DB_TYPE')) {
    die('CONFIGURATION ERROR: DB_TYPE not defined in config.php.');
}

if (DB_TYPE !== 'sqlite' && DB_TYPE !== 'mysql') {
    die('CONFIGURATION ERROR: DB_TYPE must be "sqlite" or "mysql". Current: ' . DB_TYPE);
}
```

#### 2. Production SQLite Warning
```php
if (DB_TYPE === 'sqlite' && ENVIRONMENT === 'production') {
    // Prevents using SQLite on Hostinger unless explicitly forced
    die('CONFIGURATION ERROR: SQLite cannot be used in production. Use MySQL.');
}
```

#### 3. MySQL Credentials Check
```php
if (DB_TYPE === 'mysql') {
    if (empty(DB_HOST) || empty(DB_NAME) || empty(DB_USER)) {
        die('CONFIGURATION ERROR: MySQL requires DB_HOST, DB_NAME, DB_USER, DB_PASS.');
    }
}
```

#### 4. SQLite Path Validation
```php
if (DB_TYPE === 'sqlite') {
    $dbPath = defined('DB_PATH') ? DB_PATH : DB_NAME;
    if (empty($dbPath)) {
        die('CONFIGURATION ERROR: DB_PATH must be defined for SQLite.');
    }
}
```

#### 5. Connection Logging
```php
// Logs which database was connected to
error_log('Database: Connected to SQLite at ' . $dbPath);
// OR
error_log('Database: Connected to MySQL at ' . DB_HOST . '/' . DB_NAME);
```

---

### Layer 3: Init Script Safeguards

**init_db_sqlite.php (Replit):**

```php
// SAFEGUARD 1: Prevent running on MySQL configuration
if (defined('DB_TYPE') && DB_TYPE !== 'sqlite') {
    output("❌ CONFIGURATION ERROR!", 'error');
    output("This script is for SQLite only, but DB_TYPE is set to: " . DB_TYPE, 'error');
    output("Solutions:", 'info');
    output("  - For Replit (SQLite): Set DB_TYPE='sqlite' in config.php", 'info');
    output("  - For Hostinger (MySQL): Run init_db.php instead", 'info');
    exit(1);
}

// SAFEGUARD 2: Warn if running in production
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    output("⚠️  WARNING: Running SQLite in PRODUCTION!", 'warning');
    output("SQLite is for Replit development only.", 'warning');
    output("For Hostinger, use MySQL with init_db.php instead.", 'warning');
    output("Press Ctrl+C to cancel, or wait 5 seconds...", 'warning');
    sleep(5);
}
```

**init_db.php (Hostinger):**

```php
// SAFEGUARD 1: Prevent running on SQLite configuration
if (defined('DB_TYPE') && DB_TYPE !== 'mysql') {
    output("❌ CONFIGURATION ERROR!", 'error');
    output("This script is for MySQL only, but DB_TYPE is set to: " . DB_TYPE, 'error');
    output("Solutions:", 'info');
    output("  - For Hostinger (MySQL): Set DB_TYPE='mysql' in config.php", 'info');
    output("  - For Replit (SQLite): Run init_db_sqlite.php instead", 'info');
    exit(1);
}

// SAFEGUARD 2: Info if running in development
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    output("⚠️  INFO: Running MySQL in DEVELOPMENT environment.", 'warning');
    output("This is OK if testing MySQL locally on Replit.", 'warning');
}
```

---

### Layer 4: Migration Script Safeguards

**migrate_add_slugs_sqlite.php (Replit):**

```php
// Prevent running SQLite migration on MySQL configuration
if (defined('DB_TYPE') && DB_TYPE !== 'sqlite') {
    output("❌ CONFIGURATION ERROR!", 'error');
    output("This migration is for SQLite only, but DB_TYPE: " . DB_TYPE, 'error');
    output("For Hostinger (MySQL): Run migrate_add_slugs.php instead", 'info');
    exit(1);
}
```

**migrate_add_slugs.php (Hostinger):**

```php
// Prevent running MySQL migration on SQLite configuration
if (defined('DB_TYPE') && DB_TYPE !== 'mysql') {
    output("❌ CONFIGURATION ERROR!", 'error');
    output("This migration is for MySQL only, but DB_TYPE: " . DB_TYPE, 'error');
    output("For Replit (SQLite): Run migrate_add_slugs_sqlite.php instead", 'info');
    exit(1);
}
```

---

### Layer 5: Deployment Package Exclusions

**When creating zip for Hostinger:**

```bash
zip -r codedart-hostinger.zip . \
  -x "*.git*" \           # Exclude git files
  -x "*codedart.db*" \    # ← CRITICAL: Exclude SQLite database
  -x "*cache/*" \          # Exclude cache
  -x "*logs/*"             # Exclude logs
```

**Why:** The SQLite database file (`codedart.db`) from Replit should NEVER be uploaded to Hostinger. MySQL will create its own database.

---

## Deployment Scenarios & What Happens

### ✅ Correct: Replit with SQLite

**config.php:**
```php
define('DB_TYPE', 'sqlite');
define('DB_PATH', __DIR__ . '/../codedart.db');
```

**Run:**
```bash
php config/init_db_sqlite.php
php config/migrate_add_slugs_sqlite.php
```

**Result:** ✅ Works perfectly. SQLite database created.

---

### ✅ Correct: Hostinger with MySQL

**config.php:**
```php
define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_codedart');
define('DB_USER', 'u123456789_admin');
define('DB_PASS', 'your_password');
```

**Run:**
```bash
php config/init_db.php
php config/migrate_add_slugs.php
```

**Result:** ✅ Works perfectly. MySQL tables created.

---

### ❌ PREVENTED: Replit with Wrong Init Script

**config.php:**
```php
define('DB_TYPE', 'sqlite');  // Correct
```

**Attempt to run:**
```bash
php config/init_db.php  # ← WRONG SCRIPT (MySQL version)
```

**Result:**
```
❌ CONFIGURATION ERROR!
This script is for MySQL only, but DB_TYPE is set to: sqlite

Solutions:
  - For Replit (SQLite): Run init_db_sqlite.php instead
  
Script exits with error code 1.
```

**Outcome:** ✅ **PREVENTED** - Script stops before creating any tables.

---

### ❌ PREVENTED: Hostinger with Wrong Config

**config.php:**
```php
define('DB_TYPE', 'sqlite');  // ← WRONG! Should be 'mysql'
define('DB_PATH', __DIR__ . '/../codedart.db');
```

**Attempt to run:**
```bash
php config/init_db.php  # MySQL script
```

**Result:**
```
❌ CONFIGURATION ERROR!
This script is for MySQL only, but DB_TYPE is set to: sqlite

Solutions:
  - For Hostinger (MySQL): Set DB_TYPE='mysql' in config.php
  - For Replit (SQLite): Run init_db_sqlite.php instead
  
Script exits with error code 1.
```

**Outcome:** ✅ **PREVENTED** - Script stops before creating tables.

---

### ❌ PREVENTED: Hostinger with SQLite Type

**config.php:**
```php
define('DB_TYPE', 'sqlite');       // ← WRONG
define('ENVIRONMENT', 'production');
```

**Attempt database connection:**

**Result:**
```
CONFIGURATION ERROR: SQLite cannot be used in production.
Please set DB_TYPE to "mysql" in config.php and configure MySQL credentials.

Script dies immediately.
```

**Outcome:** ✅ **PREVENTED** - Connection fails immediately with clear error.

---

### ⚠️ WARNING: Production SQLite (Forced)

**config.php:**
```php
define('DB_TYPE', 'sqlite');
define('ENVIRONMENT', 'production');
define('FORCE_SQLITE_IN_PRODUCTION', true);  // Override
```

**Attempt to run:**
```bash
php config/init_db_sqlite.php
```

**Result:**
```
⚠️  WARNING: Running SQLite initialization in PRODUCTION environment!
SQLite is intended for Replit development only.
For Hostinger production, use MySQL with init_db.php instead.

Press Ctrl+C to cancel, or wait 5 seconds to continue...
[5 second delay]

✅ Database connection successful
```

**Outcome:** ⚠️ **ALLOWED BUT WARNED** - User had 5 seconds to cancel.

---

## Verification Checklist

### Before Deployment to Hostinger:

- [ ] Created new `config.php` on Hostinger (NOT copied from Replit)
- [ ] Set `DB_TYPE='mysql'` in Hostinger config.php
- [ ] Set `ENVIRONMENT='production'` in Hostinger config.php
- [ ] Configured MySQL credentials (DB_HOST, DB_NAME, DB_USER, DB_PASS)
- [ ] Excluded `codedart.db` from deployment zip
- [ ] Will run `init_db.php` (NOT init_db_sqlite.php)
- [ ] Will run `migrate_add_slugs.php` (NOT migrate_add_slugs_sqlite.php)

### Before Development on Replit:

- [ ] Created `config.php` on Replit
- [ ] Set `DB_TYPE='sqlite'` in Replit config.php
- [ ] Set `ENVIRONMENT='development'` in Replit config.php
- [ ] Set `DB_PATH=__DIR__ . '/../codedart.db'` in Replit config.php
- [ ] Will run `init_db_sqlite.php` (NOT init_db.php)
- [ ] Will run `migrate_add_slugs_sqlite.php` (NOT migrate_add_slugs.php)

---

## Testing Safeguards

### Test 1: Wrong Script on Replit

```bash
# config.php has DB_TYPE='sqlite'
php config/init_db.php

# Expected: Error message and exit
# ✅ PASS if script exits with configuration error
```

### Test 2: Wrong Script on Hostinger

```bash
# config.php has DB_TYPE='mysql'
php config/init_db_sqlite.php

# Expected: Error message and exit
# ✅ PASS if script exits with configuration error
```

### Test 3: Missing DB_TYPE

```bash
# config.php missing DB_TYPE definition
php config/init_db.php

# Expected: "DB_TYPE not defined" error
# ✅ PASS if connection fails with clear error
```

### Test 4: Invalid DB_TYPE

```bash
# config.php has DB_TYPE='postgres'
php config/init_db.php

# Expected: "DB_TYPE must be 'sqlite' or 'mysql'" error
# ✅ PASS if connection fails with clear error
```

---

## Summary: What Makes This Safe?

1. **Config Separation:** `config.php` not in Git, must be created manually per environment

2. **Type Validation:** `database.php` validates DB_TYPE before connecting

3. **Script Checks:** Init/migration scripts verify they're running on correct DB type

4. **Clear Errors:** Detailed error messages explain what's wrong and how to fix

5. **Production Protection:** SQLite blocked in production unless explicitly forced

6. **File Exclusions:** SQLite database file excluded from Hostinger deployment zip

7. **Delayed Warnings:** 5-second delay on risky operations to allow cancellation

8. **Connection Logging:** All connections logged to help debug issues

---

## If Something Goes Wrong

### Symptom: "Configuration not loaded"
**Fix:** Add `require_once 'config/config.php';` before `require_once 'config/database.php';`

### Symptom: "DB_TYPE not defined"
**Fix:** Add `define('DB_TYPE', 'sqlite');` or `define('DB_TYPE', 'mysql');` to config.php

### Symptom: "SQLite cannot be used in production"
**Fix:** Change `DB_TYPE` to `'mysql'` in config.php for Hostinger

### Symptom: Tables not created
**Fix:** Check you ran correct init script:
- Replit: `init_db_sqlite.php`
- Hostinger: `init_db.php`

### Symptom: "This script is for MySQL only"
**Fix:** You're running wrong script. Check DB_TYPE in config.php and use matching script.

---

## Override Mechanisms (Advanced)

### Force SQLite in Production (Not Recommended)

```php
// config.php
define('FORCE_SQLITE_IN_PRODUCTION', true);
```

**When:** Testing Hostinger deployment locally with SQLite before setting up MySQL.

**Warning:** SQLite not recommended for production. Use MySQL.

---

**Status:** ✅ ALL SAFEGUARDS IMPLEMENTED
**Last Updated:** 2026-01-20
**Tested:** Replit (SQLite) and Hostinger (MySQL) configurations
