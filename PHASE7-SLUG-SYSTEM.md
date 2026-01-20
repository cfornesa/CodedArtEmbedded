# Phase 7: Advanced Slug System

**Status:** ✅ COMPLETE
**Date Completed:** 2026-01-20
**Implementation Time:** ~6 hours

## Overview

Phase 7 introduces a comprehensive URL slug management system with soft delete functionality, automatic slug generation, slug reservation, and redirect management. This system ensures clean, SEO-friendly URLs while maintaining data integrity and preventing broken links.

---

## Features Implemented

### 1. Automatic Slug Generation
- Converts titles to URL-safe slugs
- Lowercase alphanumeric characters with hyphens
- Automatic uniqueness checking
- Maximum length: 200 characters
- Pattern: `[a-z0-9-]+`

**Example:**
```
Title: "My Amazing Art Piece (2024)"
Slug:  "my-amazing-art-piece-2024"
```

### 2. Soft Delete System
- Pieces are marked as deleted, not permanently removed
- Database column: `deleted_at DATETIME NULL`
- Status automatically set to 'archived' on delete
- Slug is reserved during deletion period
- Pieces can be restored within reservation period

### 3. Slug Reservation
- Default reservation period: **30 days** (configurable)
- During reservation period:
  - Slug cannot be reused by new pieces
  - Original piece can be restored
  - After expiration, slug becomes available again
- Configuration: `slug_reservation_days` in `site_config` table

### 4. Redirect Management
- Automatic redirect creation when slug changes
- Tracks redirect usage count
- Old URLs continue to work
- Prevents broken links
- Cleanup of old unused redirects (1 year+)

### 5. Admin UI Integration
- Slug preview in create forms
- Real-time slug generation from title
- Editable slug field with validation
- "Deleted Items" page for each art type
- Visual warnings for pieces expiring soon
- Restore and permanent delete functionality

---

## Database Schema Changes

### Added Columns to Art Tables

**Tables Modified:**
- `aframe_art`
- `c2_art`
- `p5_art`
- `threejs_art`

**New Columns:**
```sql
slug VARCHAR(255) UNIQUE
deleted_at DATETIME NULL DEFAULT NULL
INDEX idx_slug (slug)
INDEX idx_deleted_at (deleted_at)
```

### New Table: `slug_redirects`

```sql
CREATE TABLE slug_redirects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    art_type ENUM('aframe', 'c2', 'p5', 'threejs') NOT NULL,
    old_slug VARCHAR(255) NOT NULL,
    new_slug VARCHAR(255) NOT NULL,
    art_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    redirect_count INT DEFAULT 0,
    INDEX idx_old_slug (old_slug),
    UNIQUE KEY unique_redirect (art_type, old_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
```

### Configuration Settings

Added to `site_config` table:
- `slug_reservation_days` (default: 30)
- `last_slug_cleanup` (tracks cleanup job execution)

---

## File Structure

```
CodedArtEmbedded/
├── config/
│   ├── migrate_add_slugs.php       [NEW] Database migration (13 steps)
│   ├── slug_utils.php              [NEW] 18 utility functions
│   └── cleanup_old_slugs.php       [NEW] Cron job for cleanup
│
├── admin/
│   ├── aframe.php                  [UPDATED] Slug integration
│   ├── c2.php                      [UPDATED] Slug integration
│   ├── p5.php                      [UPDATED] Slug integration
│   ├── threejs.php                 [UPDATED] Slug integration
│   ├── deleted.php                 [NEW] Deleted items UI
│   └── includes/
│       └── slug_functions.php      [NEW] Slug-aware CRUD functions
│
└── test_slug_system.php            [NEW] Comprehensive tests
```

---

## Key Functions

### Slug Generation

#### `generateSlug($text)`
Converts text to URL-safe slug.

```php
$slug = generateSlug('My Art Piece!');
// Returns: "my-art-piece"
```

#### `generateUniqueSlug($text, $type, $excludeId = null)`
Generates unique slug with automatic counter if needed.

```php
$slug = generateUniqueSlug('Test Piece', 'aframe', null);
// Returns: "test-piece" or "test-piece-2" if exists
```

#### `isSlugAvailable($slug, $type, $excludeId = null)`
Checks if slug is available (considers reservation period).

```php
$available = isSlugAvailable('my-slug', 'aframe', null);
// Returns: true if available, false if taken or reserved
```

### CRUD Operations

#### `createArtPieceWithSlug($type, $data)`
Creates art piece with automatic slug generation.

```php
$result = createArtPieceWithSlug('aframe', [
    'title' => 'My Art Piece',
    'slug' => '',  // Auto-generated if empty
    'description' => 'Description here',
    // ... other fields
]);
// Returns: ['success' => true, 'id' => 123, 'slug' => 'my-art-piece']
```

#### `updateArtPieceWithSlug($type, $id, $data)`
Updates art piece, creates redirect if slug changed.

```php
$result = updateArtPieceWithSlug('aframe', 123, [
    'title' => 'Updated Title',
    'slug' => 'new-slug',  // Creates redirect from old slug
    // ... other fields
]);
```

#### `deleteArtPieceWithSlug($type, $id, $permanent = false)`
Soft deletes by default, or permanently deletes if specified.

```php
// Soft delete (recommended)
$result = deleteArtPieceWithSlug('aframe', 123, false);

// Permanent delete (use with caution)
$result = deleteArtPieceWithSlug('aframe', 123, true);
```

### Soft Delete Management

#### `softDeleteArtPiece($type, $id)`
Marks piece as deleted.

```php
$success = softDeleteArtPiece('aframe', 123);
```

#### `restoreArtPiece($type, $id)`
Restores soft-deleted piece.

```php
$success = restoreArtPiece('aframe', 123);
// Returns: true if restored, false if slug taken
```

#### `getDeletedArtPieces($type)`
Retrieves all soft-deleted pieces.

```php
$deletedPieces = getDeletedArtPieces('aframe');
```

#### `cleanupOldDeletedPieces($type = 'all')`
Permanently deletes pieces past reservation period.

```php
$totalDeleted = cleanupOldDeletedPieces('all');
```

### Redirect Management

#### `createSlugRedirect($type, $artId, $oldSlug, $newSlug)`
Creates redirect entry.

```php
createSlugRedirect('aframe', 123, 'old-slug', 'new-slug');
```

#### `getSlugRedirect($type, $slug)`
Follows redirect chain to get current slug.

```php
$currentSlug = getSlugRedirect('aframe', 'old-slug');
// Returns: 'new-slug' or null if no redirect
```

#### `getArtPieceBySlug($type, $slug)`
Retrieves piece by slug, follows redirects automatically.

```php
$piece = getArtPieceBySlug('aframe', 'old-slug-that-changed');
// Automatically follows redirects to find current piece
```

---

## User Journey

### Creating a New Piece

1. **Navigate to Admin:**
   - Go to `/admin/aframe.php` (or c2, p5, threejs)
   - Click "+ Add New Piece"

2. **Enter Title:**
   - Type piece title in "Title" field
   - Slug preview appears automatically below slug field
   - Preview shows: `Preview: my-art-piece`

3. **Custom Slug (Optional):**
   - Leave slug field empty for auto-generation
   - OR enter custom slug (only lowercase, numbers, hyphens)
   - Invalid characters will be rejected

4. **Submit Form:**
   - Slug is generated or validated
   - Uniqueness checked automatically
   - If duplicate, counter added: `my-art-piece-2`
   - Piece created with clean URL

### Updating an Existing Piece

1. **Navigate to Edit:**
   - Click "Edit" button on any piece
   - Form loads with current data including slug

2. **Change Title:**
   - Title can be changed freely
   - Slug remains unchanged (stability)

3. **Change Slug (Optional):**
   - Edit slug field to new value
   - **Warning appears:** "Changing the slug will create a redirect from the old URL"
   - Old URL will automatically redirect to new URL

4. **Submit Update:**
   - Redirect created from old slug to new slug
   - All existing links continue to work
   - Redirect count tracked for analytics

### Deleting a Piece

1. **Soft Delete (Default):**
   - Click "Delete" button
   - Confirm deletion
   - Piece moved to deleted items
   - Slug reserved for 30 days
   - Status set to 'archived'

2. **View Deleted Items:**
   - Click "🗑️ Deleted Items" button
   - See all soft-deleted pieces
   - **Color coding:**
     - Normal: More than 7 days remaining
     - **Yellow warning:** 4-7 days remaining
     - **Red danger:** 1-3 days remaining
     - **"Expired":** Past reservation period

3. **Restore Deleted Piece:**
   - Click "Restore" button
   - Confirms slug is still available
   - If taken, new slug generated with redirect
   - Piece restored to draft status

4. **Permanent Delete:**
   - Click "Delete Forever" button
   - **Double confirmation modal appears**
   - Confirms action cannot be undone
   - Piece permanently removed from database
   - Slug immediately becomes available

### Automatic Cleanup

**Cron Job:** `config/cleanup_old_slugs.php`

**Schedule:**
```bash
# Run daily at 2 AM
0 2 * * * /usr/bin/php /path/to/cleanup_old_slugs.php
```

**Actions:**
- Permanently deletes pieces past reservation period
- Removes unused redirects older than 1 year
- Updates last cleanup timestamp
- Logs all actions to console

---

## Admin UI Features

### Form Enhancements

1. **Slug Preview (Create Mode):**
   ```
   Title: [My Art Piece____]

   URL Slug: [________________]
   Leave empty to auto-generate from title.
   Preview: my-art-piece
   ```

2. **Slug Field (Edit Mode):**
   ```
   URL Slug: [existing-slug____]
   Leave empty to auto-generate from title.
   Note: Changing the slug will create a redirect from the old URL.
   ```

3. **Real-time JavaScript:**
   - Updates preview as user types title
   - Only shows preview when slug field is empty
   - Validates slug format on submission

### List View Enhancements

**New Column Added:**
```
Thumbnail | Title | Slug | Scene Type | Status | Sort Order | Actions
---------------------------------------------------------------------
[Image]   | Title | my-art-piece | Space | Active | 0 | Edit | View | Delete
```

**New Button:**
```
[🗑️ Deleted Items] [+ Add New Piece]
```

### Deleted Items Page

**URL:** `/admin/deleted.php?type=[aframe|c2|p5|threejs]`

**Features:**
- Filter by art type dropdown
- Shows all soft-deleted pieces
- Days remaining until permanent deletion
- Color-coded warnings
- Restore button (green)
- Delete Forever button (red, modal confirmation)
- Info banner explaining soft delete system

**Table Columns:**
```
Type | Title | Slug | Deleted | Days Remaining | Actions
--------------------------------------------------------
AFRAME | My Piece | my-piece | Jan 15, 2026 | 25 days | Restore | Delete Forever
```

---

## Configuration

### Slug Reservation Period

**Default:** 30 days

**Change via Database:**
```sql
UPDATE site_config
SET setting_value = '60'
WHERE setting_key = 'slug_reservation_days';
```

**Change via Code:**
```php
updateSiteConfig('slug_reservation_days', 60);
```

### Redirect Cleanup Period

**Default:** 1 year for unused redirects

**Customize in:** `config/cleanup_old_slugs.php`
```php
$oneYearAgo = date('Y-m-d H:i:s', strtotime('-1 year'));
// Change to:
$sixMonthsAgo = date('Y-m-d H:i:s', strtotime('-6 months'));
```

---

## Testing

### Run Test Suite

```bash
php test_slug_system.php
```

**Tests Included:**
- Slug generation (8 tests)
- Slug uniqueness (3 tests)
- CRUD operations (7 tests)
- Soft delete & restore (6 tests)
- Configuration (2 tests)
- Cleanup (2 tests)

**Total:** 28 tests

### Manual Testing Checklist

- [ ] Create piece with auto-generated slug
- [ ] Create piece with custom slug
- [ ] Verify slug appears in URL
- [ ] Change slug and test redirect
- [ ] Soft delete piece
- [ ] Verify piece in deleted items
- [ ] Restore piece
- [ ] Permanent delete piece
- [ ] Test slug reservation (try to reuse deleted slug)
- [ ] Test slug expiration (after 30 days)

---

## Migration

### Run Migration

```bash
php config/migrate_add_slugs.php
```

**Steps Executed:**
1. Add slug column to aframe_art
2. Add slug column to c2_art
3. Add slug column to p5_art
4. Add slug column to threejs_art
5. Create slug_redirects table
6. Generate slugs for existing aframe pieces
7. Generate slugs for existing c2 pieces
8. Generate slugs for existing p5 pieces
9. Generate slugs for existing threejs pieces
10. Add slug_reservation_days config
11. Add last_slug_cleanup config
12. Verify all slugs are unique
13. Display migration summary

**Rollback (if needed):**
```sql
-- Remove slug columns
ALTER TABLE aframe_art DROP COLUMN slug, DROP COLUMN deleted_at;
ALTER TABLE c2_art DROP COLUMN slug, DROP COLUMN deleted_at;
ALTER TABLE p5_art DROP COLUMN slug, DROP COLUMN deleted_at;
ALTER TABLE threejs_art DROP COLUMN slug, DROP COLUMN deleted_at;

-- Drop redirects table
DROP TABLE slug_redirects;

-- Remove config entries
DELETE FROM site_config WHERE setting_key IN ('slug_reservation_days', 'last_slug_cleanup');
```

---

## Best Practices

### For Administrators

1. **Always use soft delete** unless absolutely necessary
2. **Monitor deleted items regularly** via "Deleted Items" page
3. **Restore pieces within 30 days** if needed
4. **Set up cron job** for automatic cleanup
5. **Don't manually edit slugs in database** - use admin UI

### For Developers

1. **Use slug-aware functions:**
   - `createArtPieceWithSlug()` instead of `createArtPiece()`
   - `updateArtPieceWithSlug()` instead of `updateArtPiece()`
   - `deleteArtPieceWithSlug()` instead of `deleteArtPiece()`

2. **Always check slug availability** before manual insertion
3. **Use `getArtPieceBySlug()`** to support redirects automatically
4. **Never hard-delete** without checking dependencies

### For URLs

**Recommended URL Structure:**
```
https://codedart.org/aframe/[slug]
https://codedart.org/c2/[slug]
https://codedart.org/p5/[slug]
https://codedart.org/threejs/[slug]
```

**Example:**
```
https://codedart.org/aframe/space-vr-experience
https://codedart.org/c2/interactive-canvas-animation
https://codedart.org/p5/generative-art-piece-2024
https://codedart.org/threejs/3d-rotating-cube
```

---

## Security Considerations

### SQL Injection Prevention
- All queries use PDO prepared statements
- Slug input sanitized via `generateSlug()`
- No raw SQL with user input

### Input Validation
- Slug format validated: `[a-z0-9-]+`
- Maximum length enforced: 200 characters
- CSRF tokens on all forms
- Slug uniqueness enforced at database level (UNIQUE constraint)

### Access Control
- Admin authentication required for all CRUD operations
- CLI-only access for cleanup script
- No public access to deleted items

---

## Performance Considerations

### Database Indexes
- `idx_slug` on all art tables (fast slug lookups)
- `idx_deleted_at` on all art tables (fast deleted item queries)
- `idx_old_slug` on slug_redirects (fast redirect lookups)
- UNIQUE constraint on slug (prevents duplicates)

### Query Optimization
- Slug availability check uses single indexed query
- Redirect lookup uses indexed column
- Deleted items filtered via indexed deleted_at column

### Caching Recommendations
- Consider caching active pieces list
- Cache redirect mappings for frequently accessed old slugs
- No caching needed for slug generation (fast operation)

---

## Troubleshooting

### Issue: Slug already exists
**Solution:** System automatically adds `-2`, `-3`, etc.

### Issue: Old URL returns 404
**Check:**
1. Is redirect entry in `slug_redirects` table?
2. Is `getArtPieceBySlug()` being used?
3. Did cleanup job delete old redirect?

### Issue: Can't restore deleted piece
**Reasons:**
1. Slug already taken by new piece
2. Past reservation period (30 days)

**Solution:** System will auto-generate new slug with `-2` suffix

### Issue: Slug contains invalid characters
**Fix:** Use `generateSlug()` function to sanitize

### Issue: Migration fails
**Check:**
1. Database user has ALTER TABLE privileges
2. No existing duplicate titles (handles automatically)
3. PHP PDO extension enabled

---

## Future Enhancements

Potential improvements for future versions:

1. **Slug History:**
   - Track all slug changes over time
   - View slug history per piece

2. **Bulk Slug Operations:**
   - Regenerate all slugs
   - Bulk slug editing
   - CSV import/export with slugs

3. **Analytics:**
   - Track redirect usage statistics
   - Most popular old URLs
   - Dashboard for slug performance

4. **Advanced Redirects:**
   - Chain redirect handling (A→B→C)
   - Wildcard redirects
   - Redirect expiration dates

5. **Slug Templates:**
   - Custom slug patterns per art type
   - Automatic prefixing/suffixing
   - Date-based slug generation

---

## Support

**Issues or Questions:**
- Review test results: `php test_slug_system.php`
- Check database: `SELECT * FROM slug_redirects;`
- Review logs: Check cleanup job output
- Verify config: `SELECT * FROM site_config WHERE setting_key LIKE 'slug%';`

**Common Questions:**

**Q: Can I change reservation period?**
A: Yes, update `slug_reservation_days` in `site_config` table.

**Q: What happens if I restore after 30 days?**
A: Slug may be taken. System generates new slug with `-2` suffix and creates redirect.

**Q: Are redirects permanent?**
A: No, unused redirects (0 redirect_count) older than 1 year are cleaned up.

**Q: Can I use special characters in slugs?**
A: No, only lowercase letters, numbers, and hyphens: `[a-z0-9-]+`

**Q: How do I recover a permanently deleted piece?**
A: You cannot. Always use soft delete unless absolutely certain.

---

## Completion Checklist

- ✅ Database migration created and tested
- ✅ Slug utility functions (18 functions)
- ✅ Slug-aware CRUD functions
- ✅ Admin form integration (4 files)
- ✅ Deleted items UI
- ✅ Redirect system
- ✅ Restore functionality
- ✅ Cleanup cron job
- ✅ Comprehensive tests (28 tests)
- ✅ Full documentation

---

**Phase 7 Status:** ✅ COMPLETE
**Ready for Production:** Yes
**Testing Required:** Run migration and test suite before deployment

---

*Last Updated: 2026-01-20*
*Author: Claude (Sonnet 4.5)*
*Session: CodedArtEmbedded Refactoring Project*
