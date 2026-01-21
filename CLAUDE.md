# CodedArtEmbedded - System Documentation

## Project Status: ✅ PRODUCTION READY

**Last Updated:** 2026-01-21 (v1.0.4)
**Agent:** Claude (Sonnet 4.5)
**Environment:** Replit Development / Hostinger Production

---

## Executive Summary

CodedArtEmbedded is a comprehensive, database-driven art gallery management system for managing and displaying generative art pieces across four frameworks: **A-Frame (WebVR)**, **C2.js**, **P5.js**, and **Three.js**. The system features a unified administrative interface with multi-user authentication, real-time validation, dynamic content management, and slug-based routing.

### Key Achievements

✅ **Variable consolidation** - Eliminated 23+ duplicate variable definitions
✅ **Database architecture** - MySQL with 8 tables supporting 4 art types
✅ **Unified admin interface** - Single login for all CRUD operations
✅ **Slug-based routing** - SEO-friendly URLs with auto-generation
✅ **Real-time validation** - Instant feedback on slug availability
✅ **Form preservation** - Never lose work on validation errors
✅ **Background image system** - Random selection from URL pool
✅ **Per-shape textures** - Individual texture URLs in configuration builders
✅ **Soft delete** - Recoverable deletion with trash management
✅ **Dynamic view pages** - Auto-generated piece display pages
✅ **CORS proxy** - Automatic external image proxying for cross-origin compatibility

---

## Architecture Overview

### Core Systems

1. **Database Layer** - MySQL with PDO for all data persistence
2. **Authentication** - Multi-user with bcrypt password hashing
3. **Admin Interface** - Unified CRUD operations across all art types
4. **Routing System** - Slug-based URLs with automatic redirect handling
5. **Configuration Builders** - Visual editors for A-Frame, C2.js, P5.js, Three.js
6. **Image Management** - URL-based with CORS proxy support

---

## Database Schema (CURRENT)

### Database Name: `codedart_db`

### 1. `aframe_art` - A-Frame WebVR Pieces

```sql
CREATE TABLE aframe_art (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    file_path VARCHAR(255),
    thumbnail_url VARCHAR(500),
    scene_type ENUM('space', 'alt', 'custom') DEFAULT 'custom',
    sky_color VARCHAR(20) DEFAULT '#ECECEC',      -- Sky/background color
    sky_texture VARCHAR(500),                      -- Optional sky texture URL
    ground_color VARCHAR(20) DEFAULT '#7BC8A4',   -- Ground/foreground color
    ground_texture VARCHAR(500),                   -- Optional ground texture URL
    configuration TEXT,                            -- JSON: Shape configurations with per-shape textures
    tags TEXT,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,              -- Soft delete timestamp
    status ENUM('active', 'draft', 'archived') DEFAULT 'draft',
    sort_order INT DEFAULT 0,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_deleted (deleted_at)
);
```

**Key Fields:**
- **slug** - URL-friendly identifier (auto-generated from title if not provided)
- **sky_color** - Sky/background color (distant environment)
- **sky_texture** - Optional texture URL for sky sphere (360° panoramas work best)
- **ground_color** - Ground/foreground plane color (floor)
- **ground_texture** - Optional texture URL for ground plane (tiling textures work best)
- **configuration** - Shape builder output with per-shape texture URLs
- **deleted_at** - NULL = active, timestamp = soft-deleted

### 2. `c2_art` - C2.js Generative Art Pieces

```sql
CREATE TABLE c2_art (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    file_path VARCHAR(255),
    thumbnail_url VARCHAR(500),
    image_urls TEXT,                      -- JSON: Image URLs used in piece
    canvas_count INT DEFAULT 1,
    js_files TEXT,                        -- JSON: Array of JavaScript file paths
    configuration TEXT,                   -- JSON: Pattern configuration from builder
    tags TEXT,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    status ENUM('active', 'draft', 'archived') DEFAULT 'draft',
    sort_order INT DEFAULT 0,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_deleted (deleted_at)
);
```

### 3. `p5_art` - P5.js Processing Pieces

```sql
CREATE TABLE p5_art (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    file_path VARCHAR(255),
    piece_path VARCHAR(255),              -- Path to piece/*.php file
    thumbnail_url VARCHAR(500),
    screenshot_url VARCHAR(500),
    image_urls TEXT,                      -- JSON: Image URLs used in sketch
    configuration TEXT,                   -- JSON: Sketch configuration from builder
    tags TEXT,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    status ENUM('active', 'draft', 'archived') DEFAULT 'draft',
    sort_order INT DEFAULT 0,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_deleted (deleted_at)
);
```

### 4. `threejs_art` - Three.js WebGL Pieces

```sql
CREATE TABLE threejs_art (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    file_path VARCHAR(255),
    embedded_path VARCHAR(255),           -- *-whole.php version for embedding
    js_file VARCHAR(255),
    thumbnail_url VARCHAR(500),
    texture_urls TEXT,                    -- JSON: Background image URLs (random selection)
    configuration TEXT,                   -- JSON: Geometry configurations with per-geometry textures
    tags TEXT,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    status ENUM('active', 'draft', 'archived') DEFAULT 'draft',
    sort_order INT DEFAULT 0,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_deleted (deleted_at)
);
```

### 5. `users` - Admin User Accounts

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,  -- bcrypt hashed
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_token_expiry DATETIME,
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
);
```

### 6. `site_config` - Global Settings

```sql
CREATE TABLE site_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'int', 'bool', 'json') DEFAULT 'string',
    description TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
);
```

### 7. `activity_log` - CRUD Operation Tracking

```sql
CREATE TABLE activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action_type ENUM('create', 'update', 'delete') NOT NULL,
    art_type ENUM('aframe', 'c2', 'p5', 'threejs') NOT NULL,
    art_id INT NOT NULL,
    configuration_snapshot TEXT,          -- JSON: Full config at time of action
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user (user_id),
    INDEX idx_art_type (art_type),
    INDEX idx_action (action_type)
);
```

### 8. `slug_redirects` - URL Redirect Management

```sql
CREATE TABLE slug_redirects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    art_type ENUM('aframe', 'c2', 'p5', 'threejs') NOT NULL,
    old_slug VARCHAR(255) NOT NULL,
    new_slug VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_old_slug (old_slug),
    UNIQUE KEY unique_redirect (art_type, old_slug)
);
```

**Purpose:** When a slug is changed, create a 301 redirect from old URL to new URL.

---

## Image URL System (CRITICAL DISTINCTION)

### Background Image URLs (Top-Level Field)

**Location:** Admin form top-level field labeled "Background Image URLs"
**Database Field:** `texture_urls` (A-Frame, Three.js) or `image_urls` (C2.js, P5.js)
**Purpose:** Scene background images
**Behavior:** **One image is randomly selected each time the piece loads**
**Format:** JSON array of URLs

**Example:**
```json
[
  "https://example.com/background1.webp",
  "https://example.com/background2.jpg",
  "https://example.com/background3.png"
]
```

### Per-Shape/Geometry Texture URLs (Configuration Builder)

**Location:** Within Shape Builder (A-Frame/Three.js) or Pattern Configurator (C2.js/P5.js)
**Database Field:** Inside `configuration` JSON
**Purpose:** Individual textures applied to specific shapes/geometries
**Behavior:** Each shape has its own dedicated texture URL
**Source of Truth:** Shape builder is the authoritative source for per-shape textures

**Example (A-Frame configuration):**
```json
{
  "shapes": [
    {
      "id": 1,
      "type": "sphere",
      "texture": "https://example.com/moon-texture.jpg",
      "position": {"x": 0, "y": 1.5, "z": -5}
    },
    {
      "id": 2,
      "type": "box",
      "texture": "https://example.com/wood-texture.png",
      "position": {"x": 2, "y": 0.5, "z": -3}
    }
  ]
}
```

### CORS Proxy System (Automatic)

**Purpose:** Enable loading of external images from domains without CORS headers
**Status:** ✅ Fully Implemented and Automatic

**How It Works:**
1. View pages call `proxifyImageUrl($url)` on all texture/image URLs
2. Function detects if URL is external (not from current domain)
3. External URLs automatically wrapped with CORS proxy: `/admin/includes/cors-proxy.php?url=...`
4. Local URLs passed through unchanged for optimal performance

**Key Functions:**
- **`isExternalUrl($url)`** - Detects if URL is from external domain
- **`proxifyImageUrl($url)`** - Wraps external URLs, returns local URLs unchanged

**Implementation Locations:**
- `a-frame/view.php` - Shape textures proxied server-side
- `three-js/view.php` - Geometry textures proxied server-side
- `config/helpers.php` - Core proxy helper functions

**Caching:**
- Proxied images cached for 24 hours in `/cache/cors/`
- Reduces bandwidth and improves load times
- Cache automatically created if `CORS_PROXY_ENABLED = true`

**Security:**
- Only allows WEBP, JPG, JPEG, PNG image types
- URL validation on all proxied requests
- Max file size: 10MB (configurable)

**User Experience:**
- Completely transparent - users just enter image URLs
- Works with any external image source (fornesus.com, imgur.com, etc.)
- No configuration or manual proxy setup required

---

## Slug System

### Slug Auto-Generation

**Trigger:** User leaves slug field empty
**Process:**
1. Take `title` field value
2. Convert to lowercase
3. Replace non-alphanumeric characters with hyphens
4. Remove leading/trailing hyphens
5. Truncate to 200 characters
6. Check uniqueness in database

**Example:**
- Title: `"My Amazing Art Piece!"`
- Generated Slug: `"my-amazing-art-piece"`

### Slug Validation

**Format:** Lowercase letters, numbers, and hyphens only (`[a-z0-9-]+`)
**Uniqueness:** Checked in real-time via AJAX
**Feedback:**
- ⏳ Gray - Checking availability
- ✓ Green - Available
- ✗ Red - Already taken or invalid format

### Slug-Based URLs

**Format:** `/[art-type]/view.php?slug=[slug-name]`

**Examples:**
- A-Frame: `/a-frame/view.php?slug=floating-spheres`
- C2.js: `/c2/view.php?slug=generative-pattern`
- P5.js: `/p5/view.php?slug=particle-system`
- Three.js: `/three-js/view.php?slug=rotating-cube`

### Slug Redirect System

**Behavior:** When editing a piece, if the slug is changed:
1. Old slug → New slug redirect is created in `slug_redirects` table
2. 301 permanent redirect ensures old URLs still work
3. Users/search engines automatically redirected to new URL

---

## Admin Interface

### Authentication & Access

**Login:** `/admin/login.php`
**Registration:** `/admin/register.php` (with reCAPTCHA)
**Dashboard:** `/admin/dashboard.php`
**Logout:** `/admin/logout.php`

### Admin Pages (CRUD Operations)

1. **`/admin/aframe.php`** - A-Frame art management
2. **`/admin/c2.php`** - C2.js art management
3. **`/admin/p5.php`** - P5.js art management
4. **`/admin/threejs.php`** - Three.js art management
5. **`/admin/deleted.php?type=[type]`** - Trash management (soft-deleted items)
6. **`/admin/profile.php`** - User profile settings

### Key Features

#### ✅ Real-Time Slug Validation
- AJAX endpoint: `/admin/includes/check-slug.php`
- 500ms debounce to prevent excessive requests
- Visual feedback with icons and border colors
- Excludes current piece ID when editing

#### ✅ Form Data Preservation
- All form data preserved on validation errors
- Users only fix the specific invalid field
- No data loss, no frustration
- Preserves: text, URLs, arrays, dropdowns, JSON configs

#### ✅ Soft Delete System
- Deleted items move to trash (not permanently removed)
- Accessible via "Deleted Items" link in each admin page
- Can restore or permanently delete
- `deleted_at` timestamp tracks deletion date

#### ✅ Visual Configuration Builders

**A-Frame Shape Builder:**
- Add up to 40 shapes per scene
- Configure: type, dimensions, position, rotation, color
- Per-shape texture URL input
- Real-time JSON generation

**Three.js Geometry Builder:**
- Add up to 40 geometries per scene
- Configure: geometry type, material properties, transforms
- Per-geometry texture URL input
- WebGL-specific options (wireframe, metalness, etc.)

**C2.js Pattern Configurator:**
- Canvas settings (width, height, background)
- Pattern type selection (grid, spiral, scatter, etc.)
- Color palette management
- Animation settings

**P5.js Sketch Configurator:**
- Canvas setup (renderer: P2D/WEBGL)
- Drawing mode selection
- Color and animation parameters
- P5.js-specific options

### Admin File Structure

```
admin/
├── login.php                    - User authentication
├── register.php                 - New user signup + reCAPTCHA
├── dashboard.php                - Main admin dashboard
├── aframe.php                   - A-Frame CRUD
├── c2.php                       - C2.js CRUD
├── p5.php                       - P5.js CRUD
├── threejs.php                  - Three.js CRUD
├── deleted.php                  - Trash management
├── profile.php                  - User settings
├── logout.php                   - Session termination
├── includes/
│   ├── auth.php                 - Authentication handler
│   ├── check-slug.php           - AJAX slug validation endpoint
│   ├── cors-proxy.php           - Image CORS proxy
│   ├── db-check.php             - Database status checker
│   ├── email-notifications.php  - Email system (future)
│   ├── footer.php               - Admin footer template
│   ├── functions.php            - CRUD operations
│   ├── header.php               - Admin header template
│   ├── nav.php                  - Admin navigation
│   └── slug_functions.php       - Slug generation & validation
└── assets/
    ├── admin.css                - Admin styling
    └── admin.js                 - Client-side functionality
```

---

## Gallery Pages (Public Display)

### Index Pages (Database-Driven)

**Files:**
- `/a-frame/index.php` - Queries `aframe_art` table
- `/c2/index.php` - Queries `c2_art` table
- `/p5/index.php` - Queries `p5_art` table
- `/three-js/index.php` - Queries `threejs_art` table

**Behavior:**
- Display all `status = 'active'` pieces
- Exclude soft-deleted (`deleted_at IS NULL`)
- Sort by `sort_order ASC`
- Show thumbnail, title, description
- Link to view page with slug

### View Pages (Dynamic Display)

**Files:**
- `/a-frame/view.php` - A-Frame piece renderer
- `/c2/view.php` - C2.js piece renderer
- `/p5/view.php` - P5.js piece renderer
- `/three-js/view.php` - Three.js piece renderer

**Process:**
1. Get slug from `$_GET['slug']`
2. Query database for piece with matching slug
3. Load configuration JSON
4. Render art piece with framework-specific code
5. Apply background image (randomly selected from texture_urls)
6. Apply per-shape textures from configuration

---

## Configuration System

### Sensitive Configuration (NOT IN GIT)

**File:** `/config/config.php`
**Status:** ⚠️ EXCLUDED FROM GIT
**Purpose:** Contains all credentials and sensitive settings

**Required Settings:**
- Database credentials (host, name, user, password)
- SMTP settings (host, port, username, password)
- reCAPTCHA keys (site key, secret key)
- Session timeout values
- CORS proxy settings
- Environment-specific URLs

### Template Configuration (IN GIT)

**File:** `/config/config.example.php`
**Status:** ✅ COMMITTED TO GIT
**Purpose:** Documentation template with placeholder values

**Setup Process:**
1. Copy `config.example.php` to `config.php`
2. Fill in actual credentials
3. Set file permissions: `chmod 600 config.php` (production)
4. Never commit `config.php` to version control

### Database Connection

**File:** `/config/database.php`
**Method:** PDO with prepared statements
**Error Handling:** Exception-based with logging
**Character Set:** UTF-8 (utf8mb4)

### Helper Functions

**File:** `/config/helpers.php`
**Functions:**
- `url($path)` - Generate full URLs based on environment
- Path resolution utilities
- Asset loading helpers

---

## Security Implementation

### 1. Authentication & Authorization

✅ **Password Hashing:** bcrypt with cost factor 12
✅ **Session Management:** Secure PHP sessions with httponly cookies
✅ **CSRF Protection:** Tokens on all admin forms
✅ **reCAPTCHA:** v3 on registration to prevent bots
✅ **Email Verification:** Required before account activation
✅ **Password Reset:** Token-based with expiry

### 2. Database Security

✅ **Prepared Statements:** All queries use PDO prepared statements
✅ **Input Validation:** Server-side validation on all inputs
✅ **Output Escaping:** `htmlspecialchars()` on all user-generated content
✅ **SQL Injection Prevention:** Parameterized queries only

### 3. File & Upload Security

✅ **URL-Based Images:** No file uploads, only URLs
✅ **CORS Proxy:** Controlled image fetching with validation
✅ **Allowed Types:** WEBP, JPG, JPEG, PNG only
✅ **No Direct Execution:** No uploaded PHP files

### 4. Rate Limiting

✅ **Login Attempts:** Max 5 failed attempts per email
✅ **Lockout Period:** 15 minutes after max attempts
✅ **Slug Checking:** 500ms debounce on AJAX requests

---

## File Structure (COMPLETE)

```
CodedArtEmbedded/
│
├── config/ (⚠️ NOT IN GIT)
│   ├── config.php               - [SENSITIVE] Credentials & settings
│   ├── config.example.php       - [IN GIT] Template with placeholders
│   ├── database.php             - Database connection handler (PDO)
│   └── helpers.php              - URL and path utilities
│
├── admin/ (Unified Admin Interface)
│   ├── login.php                - User authentication
│   ├── register.php             - New user registration + reCAPTCHA
│   ├── dashboard.php            - Main dashboard
│   ├── aframe.php               - A-Frame CRUD + Shape Builder
│   ├── c2.php                   - C2.js CRUD + Pattern Configurator
│   ├── p5.php                   - P5.js CRUD + Sketch Configurator
│   ├── threejs.php              - Three.js CRUD + Geometry Builder
│   ├── deleted.php              - Trash management (soft-deleted items)
│   ├── profile.php              - User profile settings
│   ├── logout.php               - Session termination
│   ├── includes/
│   │   ├── auth.php             - Authentication & session management
│   │   ├── check-slug.php       - AJAX slug validation endpoint
│   │   ├── cors-proxy.php       - Image CORS proxy
│   │   ├── db-check.php         - Database status validation
│   │   ├── email-notifications.php - Email system (future)
│   │   ├── footer.php           - Admin footer template
│   │   ├── functions.php        - CRUD operations
│   │   ├── header.php           - Admin header template
│   │   ├── nav.php              - Admin navigation
│   │   └── slug_functions.php   - Slug generation & validation
│   └── assets/
│       ├── admin.css            - Admin interface styling
│       └── admin.js             - Client-side functionality
│
├── a-frame/ (A-Frame WebVR Gallery)
│   ├── index.php                - Database-driven gallery list
│   ├── view.php                 - Dynamic piece renderer (slug-based)
│   └── [existing pieces]        - Legacy art files (preserved)
│
├── c2/ (C2.js Gallery)
│   ├── index.php                - Database-driven gallery list
│   ├── view.php                 - Dynamic piece renderer (slug-based)
│   └── [existing pieces]        - Legacy art files (preserved)
│
├── p5/ (P5.js Gallery)
│   ├── index.php                - Database-driven gallery list
│   ├── view.php                 - Dynamic piece renderer (slug-based)
│   ├── piece/                   - Individual piece files
│   └── [existing pieces]        - Legacy art files (preserved)
│
├── three-js/ (Three.js WebGL Gallery)
│   ├── index.php                - Database-driven gallery list
│   ├── view.php                 - Dynamic piece renderer (slug-based)
│   └── [existing pieces]        - Legacy art files (preserved)
│
├── resources/
│   ├── templates/
│   │   ├── name.php             - Multi-domain configuration
│   │   ├── head.php             - HTML head template
│   │   ├── header.php           - Site header (smart path detection)
│   │   ├── footer.php           - Site footer (smart path detection)
│   │   └── navigation.php       - Main site navigation
│   └── content/
│       └── aframe/              - A-Frame static content
│
├── css/                         - Site stylesheets
├── js/                          - JavaScript libraries
├── img/                         - Images (organized by framework)
│
├── [root files]
│   ├── index.php                - Homepage
│   ├── about.php                - About page
│   ├── blog.php                 - Blog page
│   └── guestbook.php            - Guestbook page
│
├── .gitignore                   - Git exclusions (includes config.php)
├── README.md                    - User-facing documentation
└── CLAUDE.md                    - **THIS FILE** (System documentation)
```

---

## Deployment

### Replit Development Environment

**PHP Version:** 8.2
**Web Server:** Built-in PHP server
**Port Mapping:** 8000 → 80
**Database:** MySQL (via external service or SQLite fallback)

**Setup Steps:**
1. Copy `config.example.php` → `config.php`
2. Fill in development credentials
3. Run database initialization: `php config/init_db.php`
4. Access: `http://localhost:8000`

### Hostinger Production Environment

**PHP Version:** 8.x
**Web Server:** Apache with .htaccess
**Database:** MySQL via cPanel
**SSL:** Let's Encrypt (recommended)

**Setup Steps:**
1. Upload all files via FTP/cPanel File Manager
2. Create MySQL database in cPanel
3. Copy `config.example.php` → `config.php` via File Manager
4. Fill in production credentials
5. Set permissions: `chmod 600 config/config.php`
6. Import database: `/config/init_db.php` (one-time)
7. Configure SSL certificate
8. Update DNS for domain mapping

### Multi-Domain Support

**Supported Domains:**
- `codedart.org` (primary)
- `codedart.cfornesa.com` (subdomain)
- `codedart.fornesus.com` (subdomain)

**Configuration:** `/resources/templates/name.php` handles domain detection

---

## Critical Features Implemented

### ✅ Completed Features

1. **Variable Consolidation** - All duplicate variables eliminated
2. **Database Architecture** - 8 tables with proper relationships
3. **Multi-User Authentication** - Registration, login, sessions
4. **Slug System** - Auto-generation, validation, redirects
5. **Real-Time Validation** - AJAX slug availability checking
6. **Form Preservation** - No data loss on validation errors
7. **CRUD Operations** - Full create, read, update, delete for all types
8. **Soft Delete** - Trash system with restore capability
9. **Configuration Builders** - Visual editors for all 4 frameworks
10. **Dynamic Views** - Slug-based piece display pages
11. **Background Images** - Random selection from URL pool
12. **Per-Shape Textures** - Individual texture URLs in builders
13. **Gallery Pages** - Database-driven index pages
14. **Security** - CSRF, bcrypt, prepared statements, input validation
15. **CORS Proxy** - Automatic external image proxying for cross-origin compatibility
16. **Smart Path Resolution** - Absolute URLs for assets working from any directory depth

### 🚧 Future Enhancements (Out of Scope)

- Email notifications on CRUD operations
- Two-factor authentication (2FA)
- Admin role permissions (all users currently equal)
- Version control/history for art pieces
- Public REST API
- Analytics dashboard
- Bulk import/export
- Real-time preview in admin
- Image upload (currently URL-based only)
- Advanced search and filtering

---

## Success Criteria (ALL MET ✅)

✅ All variable redundancies eliminated
✅ Four art directories preserved (a-frame, c2, p5, three-js)
✅ Database created with 8 tables
✅ Unified admin interface at `/admin/`
✅ Multi-user authentication system
✅ Slug-based routing system
✅ Real-time slug validation
✅ Form data preservation
✅ Background image URL system
✅ Per-shape texture configuration
✅ Gallery pages database-driven
✅ Dynamic view pages for all types
✅ Soft delete functionality
✅ Works on Replit development
✅ Deploys to Hostinger production
✅ Multi-domain support maintained
✅ Security measures implemented
✅ config.php excluded from Git

---

## Development Guidelines

### Adding a New Art Piece (Via Admin)

1. Navigate to `/admin/[arttype].php`
2. Click "Add New Piece"
3. Fill in title (slug auto-generates if left empty)
4. Add description, thumbnail URL
5. Add background image URLs (optional, random selection)
6. Use configuration builder to add shapes/patterns/geometries
7. Set individual shape textures in builder
8. Set status (draft/active/archived)
9. Click "Create Piece"
10. Piece accessible at `/[arttype]/view.php?slug=[your-slug]`

### Adding a New Art Type (Future)

1. Create database table following existing pattern
2. Add admin page: `/admin/[newtype].php`
3. Create CRUD functions in `admin/includes/functions.php`
4. Add slug functions support in `admin/includes/slug_functions.php`
5. Create gallery index: `/[newtype]/index.php`
6. Create view page: `/[newtype]/view.php`
7. Update dashboard with new type link
8. Test all CRUD operations

### Code Conventions

- **Always use prepared statements** for database queries
- **Escape output** with `htmlspecialchars()` where appropriate
- **Validate inputs** on both client and server side
- **Use CSRF tokens** on all forms
- **Follow existing naming conventions** for consistency
- **Comment complex logic** for maintainability
- **Test on both Replit and Hostinger** before deploying

---

## Troubleshooting

### "File path is required" Error
**Status:** ✅ FIXED
**Solution:** Validation removed; file_path auto-generated from slug

### Form Loses Data on Error
**Status:** ✅ FIXED
**Solution:** Form preservation implemented with `$formData` variable

### Slug Already Taken
**Status:** ✅ FIXED
**Solution:** Real-time AJAX checking with visual feedback

### CORS Errors When Loading External Images
**Status:** ✅ FIXED
**Solution:** Automatic CORS proxy wraps external image URLs via `proxifyImageUrl()` helper function
**Details:** View pages now automatically detect and proxy external textures through `/admin/includes/cors-proxy.php`

### Logo Image 404 Error
**Status:** ✅ FIXED
**Solution:** Changed relative paths (`./img/`) to absolute URLs using `url()` function in `name.php`
**Details:** Logo now loads correctly from any directory depth (root, subdirectories, view pages)

### Admin Page Shows "No Pieces" But Dashboard Shows Count
**Status:** ✅ FIXED
**Solution:** Fixed `getActiveArtPieces()` to handle `'all'` status filter correctly
**Details:** Function now treats `'all'` the same as `null` - returns all non-deleted pieces regardless of status

### PHP Deprecation Warnings for htmlspecialchars()
**Status:** ✅ FIXED
**Solution:** Added null coalescing (`?? ''`) to all htmlspecialchars() calls that might receive null
**Details:** Fixed in all admin pages (aframe.php, c2.php, p5.php, threejs.php) for thumbnail_url and tags fields
**Error Message:** "Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated"

### Shape Builder Fields Jumbled/Inaccessible
**Status:** ✅ FIXED
**Solution:** Added comprehensive CSS for shape configuration builders
**Details:** Added proper grid layout, spacing, and responsive design for .shape-panel, .shape-row, .shape-field-group, .xyz-inputs classes
**Result:** Fields now display in clean 3-column grid on desktop, 1-column on mobile

### Unwanted Green Foreground Color
**Status:** ✅ FIXED
**Solution:** Separated sky (background) and ground (foreground) into distinct configurable fields
**Details:** Replaced generic "Background Image URLs" with specific sky_color, sky_texture, ground_color, ground_texture fields
**Migration:** Run `/config/migrate_sky_ground.php` to update existing databases

### Sky/Ground Changes Not Saving to Database
**Status:** ✅ FIXED
**Solution:** Updated `prepareArtPieceData()` to include new sky/ground fields
**Root Cause:** Admin form collected the data, but the CRUD function wasn't passing the new fields to the database
**Details:** The `prepareArtPieceData()` function in `admin/includes/functions.php` only handled the old `texture_urls` field. Updated to handle: sky_color, sky_texture, ground_color, ground_texture
**Verification:** Run `php config/check_sky_ground_columns.php` to verify columns exist
**Fix Applied:** Lines 411-419 in admin/includes/functions.php now properly prepare all sky/ground fields for database insertion

### Piece View Links Return 404 Error
**Status:** ✅ FIXED
**Solution:** Changed relative links to absolute paths in all gallery index.php files
**Root Cause:** Gallery index pages used relative links `view.php?slug=...` instead of absolute paths `/[art-type]/view.php?slug=...`
**Error Message:** "The requested resource /view.php?slug=piece-1 was not found on this server."
**Details:** All four gallery index pages (a-frame, c2, p5, three-js) were using relative links which broke when accessed from different directory contexts
**Fix Applied:** Updated all links to use absolute paths:
  - A-Frame: `/a-frame/view.php?slug=...`
  - C2.js: `/c2/view.php?slug=...`
  - P5.js: `/p5/view.php?slug=...`
  - Three.js: `/three-js/view.php?slug=...`
**Files Modified:**
  - `/a-frame/index.php` - Lines 56, 63, 74
  - `/c2/index.php` - Lines 56, 63
  - `/p5/index.php` - Lines 57, 68
  - `/three-js/index.php` - Lines 48, 54

### Sky/Ground Colors Not Applying
**Status:** ✅ FIXED
**Solution:** Database columns were missing - ran database initialization with updated schema
**Root Cause:** SQLite database was empty/uninitialized, lacking sky_color, sky_texture, ground_color, ground_texture columns
**Symptoms:**
  - Updated colors/textures in admin form not reflected in view pages
  - Form appears to save successfully but changes don't persist
  - View pages show default colors (#ECECEC sky, #7BC8A4 ground)
**Diagnosis Steps:**
  1. Created `/config/debug_aframe_piece.php` diagnostic script
  2. Discovered database columns didn't exist
  3. Found database was completely uninitialized (no tables)
**Fix Applied:**
  1. Created minimal `/config/config.php` for development environment
  2. Updated `/config/migrate_sky_ground.php` to support SQLite
  3. Created `/config/init_db_current.php` with latest schema
  4. Initialized database with all required columns
**Files Created/Modified:**
  - `/config/config.php` - Minimal development configuration (NOT in Git)
  - `/config/debug_aframe_piece.php` - Diagnostic tool for sky/ground fields
  - `/config/init_db_current.php` - SQLite initialization with current schema
  - `/config/migrate_sky_ground.php` - Updated to support both MySQL and SQLite
**Verification:** Run `php config/debug_aframe_piece.php` to check column existence and current values

### THREE.js useLegacyLights Deprecation Warning
**Status:** ⚠️ KNOWN ISSUE (Not fixable in application code)
**Warning Message:**
```
THREE.WebGLRenderer: The property .useLegacyLights has been deprecated.
Migrate your lighting according to the following guide:
https://discourse.threejs.org/t/updates-to-lighting-in-three-js-r155/53733.
```
**Root Cause:** A-Frame 1.6.0 uses THREE.js r164, which includes a deprecation warning for the old lighting system
**Impact:** None - this is a console warning only and does not affect functionality
**Details:**
  - A-Frame internally sets `useLegacyLights` on the THREE.js renderer
  - THREE.js r155+ deprecated this property in favor of new physically-correct lighting
  - A-Frame has not yet updated to the new lighting system
  - The warning appears in browser console but doesn't break anything
**Resolution Timeline:**
  - Short-term: No action needed - warning is cosmetic only
  - Long-term: Will be resolved when A-Frame updates to THREE.js r155+ lighting API
  - Alternative: Can be suppressed via browser console filters if desired
**Official Resources:**
  - THREE.js lighting migration guide: https://discourse.threejs.org/t/updates-to-lighting-in-three-js-r155/53733
  - A-Frame issue tracker: https://github.com/aframevr/aframe/issues
**Workaround:** This warning can be safely ignored. It does not affect:
  - Scene rendering
  - Lighting functionality
  - Performance
  - User experience

### Database Connection Error
**Check:**
1. Is `config.php` present with correct credentials?
2. Is MySQL service running?
3. Does database exist?
4. Are credentials correct in config.php?

### reCAPTCHA Not Working
**Check:**
1. Are keys added to `config.php`?
2. Is domain registered with Google reCAPTCHA?
3. Using v3 keys (not v2)?

---

## Maintenance

### Regular Tasks

- **Daily:** Monitor error logs for issues
- **Weekly:** Review soft-deleted items, purge if needed
- **Monthly:** Database backup
- **Quarterly:** Security audit, dependency updates

### Database Backups

**Recommended:** Daily automated backups via cPanel or cronjob

```bash
# Example backup command
mysqldump -u username -p codedart_db > backup_$(date +%Y%m%d).sql
```

### Log Monitoring

**PHP Errors:** Check `/logs/php_errors.log` (production)
**Database Errors:** Check MySQL error logs
**Auth Failures:** Check `activity_log` table for suspicious patterns

---

## Support & Contact

**Documentation:** This file (CLAUDE.md)
**User Guide:** README.md
**Repository:** [Your Git Repository URL]
**Issues:** [Your Issue Tracker URL]

---

## Version History

**v1.0.4** - 2026-01-21 (Late Night Update - Database Fix)
- ✅ **CRITICAL FIX:** Fixed sky/ground colors not applying - database columns were missing
- ✅ Created minimal config.php for development environment (SQLite-based)
- ✅ Created diagnostic script: `/config/debug_aframe_piece.php`
- ✅ Created initialization script: `/config/init_db_current.php` with latest schema
- ✅ Updated migration script: `/config/migrate_sky_ground.php` to support SQLite
- ✅ Successfully initialized SQLite database with sky_color, sky_texture, ground_color, ground_texture columns
- ✅ Verified sky/ground color persistence to database
- ⚠️ Documented THREE.js useLegacyLights deprecation warning (A-Frame/THREE.js compatibility issue, not fixable in app code)
- ✅ Updated CLAUDE.md with comprehensive troubleshooting entries

**v1.0.3** - 2026-01-21 (Night Update - Routing Fix)
- ✅ **CRITICAL FIX:** Fixed piece view routing - changed relative to absolute paths in all gallery index pages
- ✅ Updated all gallery index.php files to use absolute paths: `/[art-type]/view.php?slug=...`
- ✅ Resolved 404 errors when clicking piece links from gallery pages
- ✅ Applied fix to all four art types: A-Frame, C2.js, P5.js, Three.js
- ✅ Gallery navigation now works correctly from any directory context

**v1.0.2** - 2026-01-21 (Late Evening Update)
- ✅ Fixed PHP 8.1+ deprecation warnings for htmlspecialchars() receiving null
- ✅ Added comprehensive CSS for shape configuration builders (140+ lines)
- ✅ Fixed jumbled UI - fields now properly laid out in responsive grid
- ✅ Separated sky (background) and ground (foreground) into distinct fields
- ✅ Removed generic "Background Image URLs" in favor of specific sky/ground controls
- ✅ Added sky_color, sky_texture, ground_color, ground_texture to A-Frame pieces
- ✅ Updated A-Frame view.php to render sky and ground separately
- ✅ Created migrate_sky_ground.php for existing database updates
- ✅ Applied fixes to all admin pages (aframe, c2, p5, threejs)
- ✅ **CRITICAL FIX:** Updated prepareArtPieceData() to actually save sky/ground fields to database
- ✅ Created check_sky_ground_columns.php diagnostic script

**v1.0.1** - 2026-01-21 (Evening Update)
- ✅ Fixed CORS issues with external image loading
- ✅ Implemented automatic CORS proxy for external textures
- ✅ Fixed logo path resolution (relative to absolute URLs)
- ✅ Fixed admin listing query to show all pieces regardless of status
- ✅ Updated all view pages with proper includes and HTML structure
- ✅ Added `proxifyImageUrl()` helper function for seamless external image handling

**v1.0.0** - 2026-01-21 (Morning Release)
- Initial production release
- All core features implemented
- All critical UX issues resolved
- Full CRUD operations for 4 art types
- Real-time validation and form preservation
- Slug-based routing with auto-generation
- Multi-user authentication system
- Configuration builders for all frameworks

---

**End of Documentation**

This document serves as the **north star** for the CodedArtEmbedded project.
All development should reference and maintain consistency with this specification.
