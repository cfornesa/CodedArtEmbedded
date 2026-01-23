# Testing Guide: View Page Rewrite (v1.0.26)

## Overview

Steps 1 and 2 are complete:
- ✅ **Step 1:** Rewrote `three-js/view.php` following `first-whole.php` pattern
- ✅ **Step 2:** Rewrote `p5/view.php` following same pattern
- ⏳ **Step 3:** Test through admin interface (THIS DOCUMENT)

## What Changed

### Three.js View Page
- **Before:** Complex PHP-embedded rendering (400+ lines)
- **After:** Simple pattern like homepage (270 lines)
  - Minimal PHP: database query only
  - Clean JavaScript: pure Three.js code
  - Iframe-embeddable
  - All features preserved

### P5.js View Page
- **Before:** Complex PHP-embedded rendering
- **After:** Simple pattern (245 lines)
  - Minimal PHP: database query only
  - P5.js instance mode
  - Clean JavaScript generation
  - Iframe-embeddable
  - All features preserved

## Testing Instructions

### Test 1: Three.js Piece Creation

1. **Log into admin:**
   - URL: `http://[your-domain]/admin/login.php`
   - Navigate to Three.js management

2. **Create new piece:**
   - Click "Add New Piece"
   - Title: "Test Three.js View - Multiple Geometries"
   - Slug: (auto-generates, or use "test-threejs-view-multiple")
   - Description: "Testing rewritten view page with multiple geometries"

3. **Configure scene:**

   **Background:**
   - Background Color: `#1a1a2e` (dark blue)
   - Or Background Image URL: (optional)

   **Add Multiple Geometries:**

   **Geometry 1 - Rotating Sphere:**
   - Type: SphereGeometry
   - Radius: 1
   - Color: #4ecdc4
   - Position: x=0, y=0, z=-5
   - Rotation Animation: ✓ Enable, Duration: 5000ms
   - Opacity: 1.0

   **Geometry 2 - Box with Texture:**
   - Type: BoxGeometry
   - Width/Height/Depth: 1, 1, 1
   - Texture: (optional - use an image URL)
   - Position: x=-2, y=0, z=-5
   - Scale Animation: ✓ Enable, Min: 0.8, Max: 1.2, Duration: 3000ms
   - Opacity: 0.8

   **Geometry 3 - Moving Torus:**
   - Type: TorusGeometry
   - Radius: 0.7, Tube: 0.3
   - Color: #ff6b6b
   - Position: x=2, y=0, z=-5
   - Position Animation: ✓ Enable Y, Range: 2 units, Duration: 4000ms
   - Opacity: 0.9

4. **Save piece:**
   - Click "Create Piece"
   - Note the slug generated

5. **View piece:**
   - URL: `http://[your-domain]/three-js/view.php?slug=test-threejs-view-multiple`
   - Should see:
     - Dark blue background
     - Three geometries rendering
     - Sphere rotating
     - Box pulsing scale
     - Torus moving up/down
     - Smooth animations
     - No header/footer (iframe-ready)

6. **Test iframe embedding:**
   - Create test HTML file:
     ```html
     <!DOCTYPE html>
     <html>
     <head><title>Iframe Test</title></head>
     <body>
       <h1>Three.js Piece in Iframe</h1>
       <iframe
         src="http://[your-domain]/three-js/view.php?slug=test-threejs-view-multiple"
         width="800"
         height="600"
         frameborder="0"
         scrolling="no"
       ></iframe>
     </body>
     </html>
     ```
   - Should work like homepage iframe examples

### Test 2: P5.js Piece Creation

1. **Navigate to P5.js management:**
   - URL: `http://[your-domain]/admin/p5.php`

2. **Create new piece:**
   - Click "Add New Piece"
   - Title: "Test P5.js View - Pattern with Shapes"
   - Slug: (auto-generates, or use "test-p5-view-pattern")
   - Description: "Testing rewritten view page with pattern"

3. **Configure sketch:**

   **Canvas:**
   - Width: 800
   - Height: 600
   - Renderer: P2D
   - Background Color: #ffffff

   **Drawing:**
   - Shape Count: 20
   - Shape Size: 40
   - Fill Opacity: 200 (0-255)
   - Use Stroke: ✓ Enable
   - Stroke Weight: 2

   **Shapes:**
   - Add multiple shapes with different colors:
     - Shape 1: ellipse, #ED225D (pink)
     - Shape 2: rect, #4CC3D9 (blue)
     - Shape 3: triangle, #FFA000 (orange)

   **Animation:**
   - Enable Rotation: ✓
   - Enable Scale/Pulse: ✓
   - Enable Translation: ✓
   - Speed: 1.5

   **Interaction:**
   - Enable Mouse Interaction: ✓

4. **Save piece:**
   - Click "Create Piece"
   - Note the slug generated

5. **View piece:**
   - URL: `http://[your-domain]/p5/view.php?slug=test-p5-view-pattern`
   - Should see:
     - 20 shapes in pattern
     - Mixed ellipses, rectangles, triangles
     - All shapes rotating
     - All shapes pulsing
     - All shapes moving
     - Mouse interaction (shapes repel from cursor)
     - No header/footer (iframe-ready)

6. **Test iframe embedding:**
   - Same process as Three.js test
   - Should work seamlessly

## Success Criteria

### Visual Quality (Compare to Homepage)

**Homepage Three.js Example:** `/three-js/first-whole.php`
- Multiple geometries: ✓
- Rich visual complexity: ✓
- Smooth animations: ✓
- Random positioning: ✓ (via config)
- Textures: ✓ (supported)

**Your Test Piece Should Match:**
- Multiple geometries visible
- Professional appearance
- Smooth animations
- No visual glitches
- Iframe-embeddable

### Technical Requirements

**Both view pages should:**
- ✓ Load without errors (check browser console)
- ✓ Render all configured geometries/shapes
- ✓ Display animations correctly
- ✓ Work in iframe without issues
- ✓ Have no header/footer (fullscreen)
- ✓ Responsive to window resize (Three.js)
- ✓ Mouse interaction works (P5.js)

### Admin Interface Compatibility

**Should work correctly:**
- ✓ Pieces save successfully
- ✓ file_path auto-generated from slug
- ✓ Configuration JSON stored correctly
- ✓ View page reads configuration correctly
- ✓ No database errors

## Troubleshooting

### Issue: "Art piece not found"
- **Check:** Slug is correct in URL
- **Check:** Piece has `deleted_at IS NULL` in database
- **Fix:** Verify slug matches exactly

### Issue: "Error loading art piece"
- **Check:** Browser console for JavaScript errors
- **Check:** Server logs for PHP errors
- **Fix:** Verify configuration JSON is valid

### Issue: Animations not working
- **Check:** Animation checkboxes are enabled in config
- **Check:** Duration values are reasonable (not too fast/slow)
- **Fix:** Edit piece, verify animation settings

### Issue: Geometries/shapes not appearing
- **Check:** Configuration JSON has geometries/shapes array
- **Check:** Browser console for errors
- **Fix:** Verify config was saved correctly

### Issue: Iframe doesn't work
- **Check:** View page has no header/footer includes
- **Check:** View page has fullscreen CSS (`margin: 0; overflow: hidden`)
- **Fix:** Already fixed in Steps 1 & 2

## Expected Results

### Three.js Test Piece
```
✓ Dark blue background
✓ 3 geometries visible (sphere, box, torus)
✓ Sphere rotating continuously
✓ Box pulsing scale (0.8x to 1.2x)
✓ Torus moving up/down
✓ Smooth 60fps animation
✓ No UI chrome (no header/nav/footer)
✓ Works in iframe
```

### P5.js Test Piece
```
✓ White background
✓ 20 shapes visible
✓ Mixed shape types (ellipse, rect, triangle)
✓ All shapes rotating
✓ All shapes pulsing
✓ All shapes translating
✓ Mouse interaction (repel effect)
✓ No UI chrome
✓ Works in iframe
```

## Next Steps After Testing

### If Tests Pass:
1. Create more pieces with different configurations
2. Test backward compatibility (edit old pieces)
3. Test on different browsers (Chrome, Firefox, Safari)
4. Deploy to production

### If Tests Fail:
1. Note exact error messages (browser console, server logs)
2. Note which specific feature failed
3. Provide detailed description of expected vs actual behavior
4. We'll fix the specific issue

## Comparison to Homepage Examples

### Homepage Pattern (Working):
- **index.php:** Embeds iframe: `<iframe src="/three-js/first-whole.php">`
- **first-whole.php:** 16 lines of minimal HTML + script tag
- **first-whole.js:** Clean JavaScript with scene generation

### New View Pages (Should Match):
- **view.php:** Minimal PHP + clean JavaScript generation
- **Configuration-driven:** JavaScript built from database config
- **Same aesthetic:** Multiple geometries, animations, textures

**Goal:** View pages should produce results visually similar to homepage examples, but driven by admin configuration instead of hardcoded JavaScript.

## Summary

This testing validates the architectural pivot from complex database-driven PHP rendering to simple, clean JavaScript generation following the proven homepage pattern.

**Steps 1 & 2:** Code rewritten ✅
**Step 3:** Your testing validation ⏳

The system is ready for testing. Please follow the instructions above and report results.
