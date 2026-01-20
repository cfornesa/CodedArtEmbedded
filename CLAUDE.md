# CodedArtEmbedded Refactoring & Enhancement Plan

## Project Overview
Comprehensive refactoring to eliminate variable redundancies, consolidate duplicate code, create a database-backed system with a **unified administrative interface** for managing art pieces across all four art types. Includes multi-user authentication, image URL management with CORS proxy, email notifications for all CRUD operations, and compatibility with Replit development environment and Hostinger deployment.

## Current State Analysis

### Directory Structure (To Be Preserved)
```
CodedArtEmbedded/
├── a-frame/          ✅ PRESERVE - A-Frame WebVR art directory
├── c2/               ✅ PRESERVE - c2.js art directory
├── p5/               ✅ PRESERVE - p5.js art directory
├── three-js/         ✅ PRESERVE - Three.js art directory
├── resources/        🔄 CONSOLIDATE - Shared templates and content
├── css/              ✅ KEEP - Stylesheets
├── js/               ✅ KEEP - JavaScript libraries
├── img/              ✅ KEEP - Images organized by framework
└── [root files]      🔄 IMPROVE - Top-level PHP files (efficiency improvements only)
```

### Current Issues Identified

#### 1. **Variable Redundancies** (23 instances)
- `$page_name` - Defined individually in every PHP page (23 times)
- `$tagline` - Duplicated across all pages with minor variations (23 times)
- `$piece_name` - Redundantly defined in c2 pages

#### 2. **No Database**
- Entirely file-based system
- No dynamic content management
- Manual code editing required to add/edit/delete art pieces

#### 3. **Content Management Issues**
- Art pieces hardcoded in PHP files
- No administrative interface
- Difficult to maintain and update

#### 4. **Template Inconsistencies**
- Two sets of header/footer files (standard vs "-level")
- Relative path variations (`resources/templates/` vs `../resources/templates/`)

## Proposed Solution

### Phase 1: Variable Consolidation ✅
**Goal:** Eliminate all duplicate variables and centralize configuration

#### Actions:
1. **Create unified configuration system** (`/config/config.php`)
   - Consolidate all common variables
   - Create page registry with metadata
   - Centralize tagline templates
   - Domain-based configuration

2. **Update all PHP files** to use centralized config
   - Remove redundant variable definitions
   - Import from config instead

3. **Preserve folder structure** for a-frame, c2, p5, three-js
   - Only update variable references
   - Maintain existing file locations

### Phase 2: Database Architecture ✅
**Goal:** Create MySQL database with tables for each art type

#### Database Schema:

**Database Name:** `codedart_db`

**Tables:**

1. **`aframe_art`** - A-Frame pieces
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- title (VARCHAR 255)
- description (TEXT)
- file_path (VARCHAR 255) - Path to PHP file
- thumbnail_url (VARCHAR 500) - Image URL (supports WEBP, JPG, JPEG, PNG)
- texture_urls (TEXT) - JSON array of image URLs for textures
- scene_type (ENUM: 'space', 'alt', 'custom')
- configuration (TEXT) - JSON with full piece configuration details
- tags (TEXT) - Comma-separated tags
- created_by (INT) - Foreign key to users table
- created_at (DATETIME)
- updated_at (DATETIME)
- status (ENUM: 'active', 'draft', 'archived')
- sort_order (INT) - Display ordering
```

2. **`c2_art`** - c2.js pieces
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- title (VARCHAR 255)
- description (TEXT)
- file_path (VARCHAR 255)
- thumbnail_url (VARCHAR 500) - Image URL
- image_urls (TEXT) - JSON array of image URLs used in piece
- canvas_count (INT) - Number of canvases
- js_files (TEXT) - JSON array of JS file paths
- configuration (TEXT) - JSON with full piece configuration details
- tags (TEXT)
- created_by (INT) - Foreign key to users table
- created_at (DATETIME)
- updated_at (DATETIME)
- status (ENUM: 'active', 'draft', 'archived')
- sort_order (INT)
```

3. **`p5_art`** - p5.js pieces
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- title (VARCHAR 255)
- description (TEXT)
- file_path (VARCHAR 255)
- piece_path (VARCHAR 255) - Path to piece/*.php file
- thumbnail_url (VARCHAR 500) - Image URL
- screenshot_url (VARCHAR 500) - PNG screenshot URL
- image_urls (TEXT) - JSON array of image URLs used in piece
- configuration (TEXT) - JSON with full piece configuration details
- tags (TEXT)
- created_by (INT) - Foreign key to users table
- created_at (DATETIME)
- updated_at (DATETIME)
- status (ENUM: 'active', 'draft', 'archived')
- sort_order (INT)
```

4. **`threejs_art`** - Three.js pieces
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- title (VARCHAR 255)
- description (TEXT)
- file_path (VARCHAR 255)
- embedded_path (VARCHAR 255) - *-whole.php version
- js_file (VARCHAR 255)
- thumbnail_url (VARCHAR 500) - Image URL
- texture_urls (TEXT) - JSON array of texture image URLs
- configuration (TEXT) - JSON with full piece configuration details
- tags (TEXT)
- created_by (INT) - Foreign key to users table
- created_at (DATETIME)
- updated_at (DATETIME)
- status (ENUM: 'active', 'draft', 'archived')
- sort_order (INT)
```

5. **`users`** - User accounts for admin access
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- email (VARCHAR 255, UNIQUE)
- password_hash (VARCHAR 255) - bcrypt hashed password
- first_name (VARCHAR 100)
- last_name (VARCHAR 100)
- status (ENUM: 'active', 'inactive', 'pending')
- email_verified (BOOLEAN, DEFAULT FALSE)
- verification_token (VARCHAR 255)
- reset_token (VARCHAR 255)
- reset_token_expiry (DATETIME)
- last_login (DATETIME)
- created_at (DATETIME)
- updated_at (DATETIME)
```

6. **`site_config`** - Global site settings
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- setting_key (VARCHAR 100, UNIQUE)
- setting_value (TEXT)
- setting_type (ENUM: 'string', 'int', 'bool', 'json')
- description (TEXT)
- updated_at (DATETIME)
```

7. **`activity_log`** - Track all CRUD operations for email notifications
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- user_id (INT) - Foreign key to users table
- action_type (ENUM: 'create', 'update', 'delete')
- art_type (ENUM: 'aframe', 'c2', 'p5', 'threejs')
- art_id (INT) - ID of the art piece
- configuration_snapshot (TEXT) - JSON of full configuration at time of action
- created_at (DATETIME)
```

#### Database Files:
- `/config/database.php` - Database connection handler (PDO with error handling)
- `/config/init_db.php` - Database initialization script
- `/config/seed_data.php` - Populate with existing art pieces

#### Additional Features:

**CORS Proxy for Images:**
- Location: `/admin/includes/cors-proxy.php`
- Automatically detects if image URL is CORS-compliant
- Only proxies non-CORS-compliant images
- Supports: WEBP, JPG, JPEG, PNG formats
- Caches proxied images for performance

**Email Notifications:**
- Sent from: `admin@codedart.org`
- Triggers: Create, Edit, Delete operations
- Content: Full configuration details with shape-by-shape breakdown
- Purpose: Backup in case of system failure
- Uses PHPMailer with SMTP

### Phase 3: Administrative Interface & Authentication ✅
**Goal:** Create unified admin panel with multi-user authentication and CRUD operations for all art types

#### Unified Admin Structure:

**Location:** `/admin/` (root-level directory)

**Admin Pages:**
1. **`/admin/login.php`** - User login page
2. **`/admin/register.php`** - New user registration (email + password + RECAPTCHA)
3. **`/admin/dashboard.php`** - Main dashboard with tabs for each art type
4. **`/admin/aframe.php`** - A-Frame art management
5. **`/admin/c2.php`** - c2.js art management
6. **`/admin/p5.php`** - p5.js art management
7. **`/admin/threejs.php`** - Three.js art management
8. **`/admin/profile.php`** - User profile and settings
9. **`/admin/logout.php`** - Logout handler

#### Admin Features:
- **Unified Login** - Single authentication for all art types
- **User Registration** - Email/password with RECAPTCHA verification
- **List View** - Display all pieces in a table with thumbnails (image URLs)
- **Add New** - Form to create new art piece entry with image URL fields
- **Edit** - Update existing piece metadata and image URLs
- **Delete** - Remove piece from database (with confirmation)
- **Image URL Management** - Input fields for WEBP, JPG, JPEG, PNG URLs
- **CORS Proxy** - Automatically proxy non-CORS-compliant images
- **Reorder** - Drag-and-drop or manual sort ordering
- **Preview** - View the art piece
- **Status Toggle** - Active/Draft/Archived
- **Email Notifications** - Auto-send on create/edit/delete with full configuration details

#### Shared Admin Components:
- `/admin/includes/` (new directory)
  - `auth.php` - Authentication handler and session management
  - `cors-proxy.php` - CORS proxy for image URLs
  - `email-notifications.php` - Email sending functions
  - `functions.php` - Shared PHP functions for CRUD
  - `header.php` - Common admin page header
  - `nav.php` - Navigation between admin sections
- `/admin/assets/` (new directory)
  - `admin.css` - Admin interface styling
  - `admin.js` - Client-side functionality

#### Security:
- **Multi-user authentication** - Database-backed user accounts
- **Password hashing** - bcrypt for secure password storage
- **Email verification** - Confirm email before account activation
- **RECAPTCHA** - Prevent bot registrations (Google reCAPTCHA v3)
- **Session management** - Secure PHP sessions
- **CSRF protection** - Tokens on all forms
- **SQL injection prevention** - Prepared statements
- **Input validation** - Sanitize all user inputs
- **Rate limiting** - Prevent brute force attacks

### Phase 4: Gallery Page Updates ✅
**Goal:** Update index pages to pull from database instead of hardcoded content

#### Files to Update:
- `/a-frame/index.php` - Query `aframe_art` table
- `/c2/index.php` - Query `c2_art` table
- `/p5/index.php` - Query `p5_art` table
- `/three-js/index.php` - Query `threejs_art` table

#### Dynamic Loading:
- Replace hardcoded HTML with database queries
- Generate thumbnails dynamically
- Sort by `sort_order` field
- Filter by `status = 'active'`

### Phase 5: Template Consolidation ✅
**Goal:** Simplify template system while maintaining functionality

#### Actions:
1. **Merge header variants** into single smart template
   - Auto-detect directory level
   - Adjust paths dynamically

2. **Merge footer variants** into single smart template

3. **Update navigation** to be database-driven (optional enhancement)

4. **Create helper functions** for common operations
   - `/resources/helpers.php`
   - Path resolution
   - URL generation
   - Asset loading

### Phase 6: Testing & Compatibility ✅
**Goal:** Ensure deployment works on both Replit and Hostinger

#### Replit Compatibility:
- Uses PHP 8.2 built-in server
- Port 8000 → 80 mapping
- Local SQLite fallback option (if MySQL unavailable)
- Environment-based database config

#### Hostinger Compatibility:
- MySQL database configuration
- cPanel phpMyAdmin access
- File permissions handling
- .htaccess configuration (if needed)

#### Configuration File:
```php
// /config/environment.php
// Auto-detect environment and configure accordingly
if (isset($_ENV['REPL_ID'])) {
    // Replit environment
    define('ENVIRONMENT', 'development');
    define('DB_TYPE', 'sqlite'); // or mysql if configured
} else {
    // Hostinger production
    define('ENVIRONMENT', 'production');
    define('DB_TYPE', 'mysql');
}
```

## Implementation Order

### ✅ Step 1: Setup & Configuration (Foundation)
1. Create `/config/` directory
2. Write `config.php` (consolidated variables)
3. Write `database.php` (DB connection)
4. Write `environment.php` (env detection)
5. Write `helpers.php` (utility functions)

### ✅ Step 2: Database Creation
1. Write `init_db.php` (schema creation)
2. Write `seed_data.php` (populate existing art)
3. Test database locally
4. Verify on Replit

### ✅ Step 3: Variable Consolidation
1. Update all 23 PHP files to use `config.php`
2. Remove duplicate variable definitions
3. Test all pages load correctly
4. Verify multi-domain support still works

### ✅ Step 4: Admin Interface Development
1. Create `/resources/admin/` directory
2. Build shared admin components
3. Implement authentication system
4. Create `a-frame/admin.php`
5. Create `c2/admin.php`
6. Create `p5/admin.php`
7. Create `three-js/admin.php`
8. Test CRUD operations for each

### ✅ Step 5: Gallery Pages Update
1. Update `a-frame/index.php` (database-driven)
2. Update `c2/index.php` (database-driven)
3. Update `p5/index.php` (database-driven)
4. Update `three-js/index.php` (database-driven)
5. Test display and filtering

### ✅ Step 6: Template Consolidation
1. Merge `header.php` and `header-level.php`
2. Merge `footer.php` and `footer-level.php`
3. Update all file references
4. Test across all pages

### ✅ Step 7: Testing & Quality Assurance
1. Test all pages on Replit
2. Test admin interfaces
3. Test database operations
4. Test multi-domain functionality
5. Verify deployment to Hostinger
6. Performance testing
7. Security audit

### ✅ Step 8: Documentation
1. Update README.md with new structure
2. Document admin usage
3. Document database schema
4. Create deployment guide
5. Write maintenance instructions

## File Structure After Refactoring

```
CodedArtEmbedded/
├── config/                    [NEW - NOT IN GIT]
│   ├── config.php            [NEW] - **SENSITIVE - NOT IN GIT**
│   ├── config.example.php    [NEW] - Template with placeholders
│   ├── database.php          [NEW] - DB connection handler
│   ├── environment.php       [NEW] - Environment detection
│   ├── helpers.php           [NEW] - Utility functions
│   ├── init_db.php          [NEW] - Database schema
│   └── seed_data.php        [NEW] - Initial data
│
├── admin/                    [NEW DIRECTORY - Unified Admin]
│   ├── login.php            [NEW] - User login
│   ├── register.php         [NEW] - User registration + RECAPTCHA
│   ├── dashboard.php        [NEW] - Main admin dashboard
│   ├── aframe.php           [NEW] - A-Frame art management
│   ├── c2.php               [NEW] - c2.js art management
│   ├── p5.php               [NEW] - p5.js art management
│   ├── threejs.php          [NEW] - Three.js art management
│   ├── profile.php          [NEW] - User profile
│   ├── logout.php           [NEW] - Logout handler
│   ├── includes/            [NEW SUBDIRECTORY]
│   │   ├── auth.php         [NEW] - Authentication system
│   │   ├── cors-proxy.php   [NEW] - CORS proxy for images
│   │   ├── email-notifications.php [NEW] - Email system
│   │   ├── functions.php    [NEW] - Shared CRUD functions
│   │   ├── header.php       [NEW] - Admin header
│   │   └── nav.php          [NEW] - Admin navigation
│   └── assets/              [NEW SUBDIRECTORY]
│       ├── admin.css        [NEW] - Admin styling
│       └── admin.js         [NEW] - Admin client-side code
│
├── a-frame/                  [PRESERVED]
│   ├── index.php            [UPDATED] - Database-driven
│   └── [existing files]     [UPDATED] - Use config.php
│
├── c2/                       [PRESERVED]
│   ├── index.php            [UPDATED] - Database-driven
│   └── [existing files]     [UPDATED] - Use config.php
│
├── p5/                       [PRESERVED]
│   ├── index.php            [UPDATED] - Database-driven
│   └── [existing files]     [UPDATED] - Use config.php
│
├── three-js/                 [PRESERVED]
│   ├── index.php            [UPDATED] - Database-driven
│   └── [existing files]     [UPDATED] - Use config.php
│
├── resources/
│   ├── templates/           [UPDATED]
│   │   ├── name.php         [KEPT - domain config]
│   │   ├── head.php         [UPDATED]
│   │   ├── header.php       [MERGED - smart template]
│   │   ├── footer.php       [MERGED - smart template]
│   │   └── navigation.php   [UPDATED]
│   └── content/             [UPDATED]
│       └── aframe/          [UPDATED]
│
├── [root files]             [UPDATED]
│   ├── index.php            [UPDATED]
│   ├── about.php            [UPDATED]
│   ├── blog.php             [UPDATED]
│   └── guestbook.php        [UPDATED]
│
├── css/                     [KEPT AS-IS]
├── js/                      [KEPT AS-IS]
├── img/                     [KEPT AS-IS]
├── .replit                  [UPDATED - add DB config]
├── replit.nix               [UPDATED - add MySQL if needed]
├── .gitignore               [UPDATED - exclude config/config.php]
├── README.md                [UPDATED]
└── CLAUDE.md                [THIS FILE]
```

## Configuration File Setup

### Location
**`/config/config.php`** - **CRITICAL: This file MUST NOT be committed to Git**

### Purpose
Contains all sensitive credentials and environment-specific settings that should never be exposed in the repository.

### What Should Be In config.php

```php
<?php
/**
 * Configuration File - SENSITIVE DATA
 * This file contains credentials and should NEVER be committed to Git
 * Copy config.example.php to config.php and fill in your values
 */

// Environment Detection
define('ENVIRONMENT', getenv('REPL_ID') ? 'development' : 'production');

// ==========================================
// DATABASE CONFIGURATION
// ==========================================
if (ENVIRONMENT === 'production') {
    // Hostinger MySQL Credentials
    define('DB_HOST', 'localhost'); // Or your MySQL host
    define('DB_NAME', 'your_database_name');
    define('DB_USER', 'your_database_user');
    define('DB_PASS', 'your_database_password');
    define('DB_CHARSET', 'utf8mb4');
} else {
    // Replit Development (SQLite or MySQL)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'codedart_db');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');
}

// ==========================================
// SMTP / EMAIL CONFIGURATION
// ==========================================
define('SMTP_HOST', 'mail.codedart.org'); // Your SMTP server
define('SMTP_PORT', 587); // 587 for TLS, 465 for SSL
define('SMTP_SECURE', 'tls'); // 'tls' or 'ssl'
define('SMTP_USERNAME', 'admin@codedart.org');
define('SMTP_PASSWORD', 'your_email_password');
define('SMTP_FROM_EMAIL', 'admin@codedart.org');
define('SMTP_FROM_NAME', 'CodedArt Admin');

// ==========================================
// RECAPTCHA CONFIGURATION
// ==========================================
define('RECAPTCHA_SITE_KEY', 'your_recaptcha_site_key_here');
define('RECAPTCHA_SECRET_KEY', 'your_recaptcha_secret_key_here');

// ==========================================
// SECURITY SETTINGS
// ==========================================
define('SESSION_LIFETIME', 3600); // Session timeout in seconds (1 hour)
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes in seconds

// ==========================================
// CORS PROXY SETTINGS
// ==========================================
define('CORS_PROXY_ENABLED', true);
define('CORS_CACHE_DIR', __DIR__ . '/../cache/cors/');
define('CORS_CACHE_LIFETIME', 86400); // 24 hours in seconds
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/jpg']);

// ==========================================
// APPLICATION SETTINGS
// ==========================================
define('SITE_URL', ENVIRONMENT === 'production' ? 'https://codedart.org' : 'http://localhost:8000');
define('ADMIN_URL', SITE_URL . '/admin');
define('TIMEZONE', 'America/New_York'); // Your timezone

// ==========================================
// NOTIFICATION SETTINGS
// ==========================================
define('SEND_EMAIL_NOTIFICATIONS', true); // Set to false to disable emails
define('ADMIN_EMAIL', 'admin@codedart.org'); // Receives all notifications

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting based on environment
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}
```

### Template File: config.example.php

A **template version** with placeholder values should be created as `/config/config.example.php` and committed to Git. This serves as documentation for what values need to be configured.

```php
<?php
/**
 * Configuration File Template
 * Copy this file to config.php and fill in your actual values
 * NEVER commit config.php to Git!
 */

// Database Configuration
define('DB_HOST', 'your_db_host_here');
define('DB_NAME', 'your_db_name_here');
define('DB_USER', 'your_db_user_here');
define('DB_PASS', 'your_db_password_here');

// SMTP Configuration
define('SMTP_HOST', 'your_smtp_host');
define('SMTP_USERNAME', 'your_email@domain.com');
define('SMTP_PASSWORD', 'your_email_password');

// reCAPTCHA Configuration
define('RECAPTCHA_SITE_KEY', 'your_site_key');
define('RECAPTCHA_SECRET_KEY', 'your_secret_key');

// ... etc
```

### .gitignore Updates

Add these lines to `.gitignore`:

```
# Sensitive configuration files
/config/config.php

# Cache directories
/cache/

# Log files
/logs/
*.log

# Environment-specific files
.env
.env.local
```

### Setup Instructions

1. **On Replit:**
   - Copy `config.example.php` to `config.php`
   - Fill in development credentials
   - Use Replit Secrets for sensitive values (optional)

2. **On Hostinger:**
   - Copy `config.example.php` to `config.php` via cPanel File Manager
   - Fill in production MySQL credentials from cPanel
   - Fill in SMTP credentials from email settings
   - Set up Google reCAPTCHA and add keys
   - Ensure file permissions: `chmod 600 config/config.php` (only owner can read/write)

3. **Required External Services:**
   - MySQL database (Hostinger provides this)
   - SMTP server for sending emails (Hostinger email or external like SendGrid)
   - Google reCAPTCHA account (free at https://www.google.com/recaptcha)

### Security Notes

- **NEVER** commit `config.php` to Git
- Use strong, unique passwords for all credentials
- Regularly rotate SMTP passwords
- Keep reCAPTCHA keys secure
- Use environment variables on production when possible
- Restrict file permissions on production server

## Benefits of This Approach

### 1. **Maintainability**
- Single source of truth for variables
- Database-driven content
- Easy to add/edit/remove art pieces
- No code editing required for content changes

### 2. **Organization**
- Preserved folder structure for art types
- Centralized configuration
- Consistent admin interface

### 3. **Scalability**
- Easy to add new art pieces via admin
- Database can grow without code changes
- New art types can be added following same pattern

### 4. **Efficiency**
- No duplicate variable definitions
- Reduced code redundancy
- Faster development for new features

### 5. **Compatibility**
- Works on Replit development environment
- Deploys to Hostinger production
- Environment-aware configuration

## Security Considerations

1. **Database Security**
   - Prepared statements for all queries
   - Input validation and sanitization
   - Error logging (not displayed to users)

2. **Admin Access**
   - Password-protected admin pages
   - Session management
   - CSRF tokens on forms
   - Logout functionality

3. **File Security**
   - Upload validation (if implemented)
   - File type restrictions
   - Size limits

4. **Environment Variables**
   - Sensitive data in config (not in git)
   - Different credentials for dev/prod

## Features Included in This Plan

✅ Multi-user authentication system with registration
✅ Email verification for new accounts
✅ RECAPTCHA to prevent bot registrations
✅ Image URL management (WEBP, JPG, JPEG, PNG)
✅ CORS proxy for non-compliant images
✅ Email notifications on all CRUD operations
✅ Configuration backup via email (protection against system failure)
✅ Unified admin interface across all art types
✅ Single login for all admin sections
✅ Password reset functionality
✅ Activity logging for all operations

## Future Enhancements (Out of Current Scope)

- Automatic thumbnail generation from URLs
- Version control / history for art pieces
- Analytics dashboard with visitor statistics
- Public REST API for art gallery
- Advanced search and filtering on frontend
- RSS feed for new art pieces
- Image upload directly via admin (currently URL-based)
- Two-factor authentication (2FA)
- Admin role permissions (currently all users have same access)
- Bulk operations (import/export multiple pieces)
- Real-time preview of art pieces in admin interface
- Collaborative editing with conflict resolution

## Success Criteria

✅ All variable redundancies eliminated across 23 PHP files
✅ Four art directories preserved with original structure (a-frame, c2, p5, three-js)
✅ Database created with 7 tables (4 art types + users + site_config + activity_log)
✅ Unified admin interface functional at `/admin/` directory
✅ Multi-user authentication system with registration and email verification
✅ RECAPTCHA integration prevents bot registrations
✅ Image URL management replaces file uploads
✅ CORS proxy handles non-CORS-compliant images automatically
✅ Email notifications sent on all CRUD operations with full configuration details
✅ Emails sent from admin@codedart.org with proper SMTP configuration
✅ Gallery pages pull from database dynamically
✅ Works on Replit development environment
✅ Deploys successfully to Hostinger production
✅ All existing pages still function correctly
✅ Multi-domain support maintained (codedart.cfornesa.com, codedart.fornesus.com, codedart.org)
✅ No broken links or missing assets
✅ config.php excluded from Git with config.example.php as template
✅ Secure password hashing with bcrypt
✅ CSRF protection on all forms
✅ SQL injection prevention with prepared statements

## Timeline Estimate

- **Phase 1:** Variable Consolidation - ~2-3 hours
- **Phase 2:** Database Architecture (7 tables) - ~3-4 hours
- **Phase 3:** User Authentication & Registration - ~4-5 hours
- **Phase 4:** Unified Admin Interface Development - ~6-8 hours
- **Phase 5:** CORS Proxy Implementation - ~2-3 hours
- **Phase 6:** Email Notification System - ~3-4 hours
- **Phase 7:** Gallery Pages Update - ~2-3 hours
- **Phase 8:** Template Consolidation - ~1-2 hours
- **Phase 9:** RECAPTCHA Integration - ~1-2 hours
- **Phase 10:** Testing & Compatibility - ~4-5 hours
- **Phase 11:** Security Audit - ~2-3 hours
- **Phase 12:** Documentation - ~2-3 hours

**Total Estimated Time:** ~32-45 hours of development work

**Note:** This is a significantly more complex system than originally planned due to:
- Multi-user authentication system
- User registration with email verification
- RECAPTCHA integration
- CORS proxy for image URLs
- Email notifications with detailed configuration snapshots
- Unified admin interface with role management
- Enhanced security measures

---

## Email Notification Format

### Trigger Events
Emails are sent automatically when users:
1. **Create** a new art piece
2. **Edit** an existing art piece
3. **Delete** an art piece

### Email Content Structure

**From:** admin@codedart.org
**To:** User's registered email address
**Subject:** `[CodedArt] {Action} - {Art Type} - {Piece Title}`

**Body Example (Create Action):**

```
Dear {User Name},

You have successfully CREATED a new art piece in the {Art Type} gallery.

Piece Details:
- ID: {piece_id}
- Title: {title}
- Type: {art_type}
- Status: {status}
- Created: {timestamp}

Configuration Details:
==================================================

[For A-Frame/Three.js pieces]
Shape 1:
  - Type: Sphere
  - Radius: 2.5
  - Position: (0, 1.5, -5)
  - Color: #FF6B6B
  - Texture URL: https://example.com/texture1.png

Shape 2:
  - Type: Box
  - Dimensions: 1 x 1 x 1
  - Position: (2, 0.5, -3)
  - Color: #4ECDC4
  - Rotation: (0, 45, 0)

[For P5.js pieces]
Canvas Setup:
  - Width: 800px
  - Height: 600px
  - Background: #FFFFFF

Drawing Elements:
  - Circles: 50
  - Color Palette: ["#FF6B6B", "#4ECDC4", "#45B7D1"]
  - Animation Speed: 0.05

[For C2.js pieces]
Canvases: 4
JavaScript Files:
  - /c2/scripts/piece1.js
  - /c2/scripts/piece1-1.js

==================================================

Image URLs:
- Thumbnail: {thumbnail_url}
- Texture 1: {texture_url_1}
- Texture 2: {texture_url_2}

This email serves as a backup of your art piece configuration.
Save this email for your records in case of system failure.

Best regards,
CodedArt Admin System
```

### Configuration Snapshot
- Full JSON configuration stored in `activity_log` table
- Human-readable format sent in email
- Includes all shape properties, dimensions, colors, positions, rotations
- Lists all image URLs used in the piece
- Timestamp of action for version tracking

---

## Notes

- This plan preserves your existing art piece structure
- Top-level files remain in place with only efficiency improvements
- The four main directories (a-frame, c2, p5, three-js) maintain their organization
- New unified admin interface at `/admin/` directory (root level)
- Database adds comprehensive management layer without architectural changes
- Image URLs replace file uploads for easier management and CDN integration
- CORS proxy ensures all images work regardless of source
- Multi-user system allows collaborative art management
- Email notifications provide automatic backup of all changes
- Compatible with both Replit development and Hostinger production environments
- Security-focused design with bcrypt, CSRF protection, and rate limiting
- config.php excluded from Git to protect sensitive credentials

**Status:** Updated with enhanced requirements - Ready for implementation
**Created:** 2026-01-19
**Updated:** 2026-01-19
**Agent:** Claude (Sonnet 4.5)
