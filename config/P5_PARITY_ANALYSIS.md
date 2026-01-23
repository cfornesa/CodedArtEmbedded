# P5.js Preview/View Parity Analysis

## Executive Summary

**ROOT CAUSE IDENTIFIED:** P5.js preview and view use DIFFERENT rendering modes:
- **preview.php**: Global mode (functions in global scope)
- **view.php**: Instance mode (functions scoped to `p` object)

This fundamental architectural difference causes subtle rendering discrepancies.

## Detailed Analysis

### view.php (Production - Instance Mode)

```javascript
const sketch = (p) => {
    // All P5 functions and variables prefixed with p.
    p.preload = function() { ... }
    p.setup = function() { ... }
    p.draw = function() { ... }

    // Variables: p.frameCount, p.width, p.height
    // Functions: p.ellipse(), p.rect(), p.fill(), etc.
}

new p5(sketch);
```

**Characteristics:**
- Isolated namespace (no global pollution)
- All P5 API calls require `p.` prefix
- `p.frameCount` for animation timing
- Best practice for multiple sketches on one page

### preview.php (Admin - Global Mode)

```javascript
// No wrapper function - direct global scope
function preload() { ... }
function setup() { ... }
function draw() { ... }

// Variables: frameCount (global), width, height
// Functions: ellipse(), rect(), fill() (all global)
```

**Characteristics:**
- Pollutes global namespace
- Direct function calls (no prefix)
- `frameCount` global variable
- Simple but can conflict with other scripts

## Impact on Rendering

### Scale Animation Formula

**view.php:**
```javascript
Math.sin(p.frameCount / duration * Math.PI * 2)
```

**preview.php:**
```javascript
Math.sin(frameCount / duration * Math.PI * 2)
```

While both reference `frameCount`, they're **different** variables:
- `p.frameCount` is the instance's frame counter
- `frameCount` is global and may not be initialized correctly

### Configuration Access

**view.php:**
```javascript
const animationConfig = config.animation || {};
animationConfig.scale.min
```

**preview.php:**
```javascript
config.animation.scale.min
```

Preview directly accesses nested properties, view uses local variable.

## Cross-Framework Comparison

| Framework | Preview Mode | View Mode | Parity Status |
|-----------|--------------|-----------|---------------|
| **A-Frame** | Native A-Frame | Native A-Frame | ✅ Perfect |
| **C2.js** | Canvas 2D | Canvas 2D | ✅ Perfect |
| **Three.js** | THREE.js | THREE.js | ✅ Perfect |
| **P5.js** | Global Mode | Instance Mode | ❌ **BROKEN** |

**Why P5.js is Different:**
- A-Frame, C2.js, Three.js: Same rendering approach in preview and view
- P5.js: **Different approaches** causing parity issues

## Solution

**Option A: Convert preview.php to Instance Mode** ✅ RECOMMENDED
- Pros: Matches view.php (best practice), isolated namespace, consistent
- Cons: Requires refactoring preview.php

**Option B: Convert view.php to Global Mode** ❌ NOT RECOMMENDED
- Pros: Simple, matches current preview.php
- Cons: Global pollution, not best practice, breaks existing view pages

**Decision: Implement Option A**

## Implementation Plan

1. Wrap preview.php P5 rendering in instance mode:
   ```javascript
   const sketch = (p) => {
       // All global functions become p.functions
       p.preload = function() { ... }
       p.setup = function() { ... }
       p.draw = function() { ... }

       // All P5 API calls prefixed with p.
   }

   new p5(sketch);
   ```

2. Update all P5 API calls:
   - `createCanvas(...)` → `p.createCanvas(...)`
   - `background(...)` → `p.background(...)`
   - `ellipse(...)` → `p.ellipse(...)`
   - `frameCount` → `p.frameCount`
   - etc. (100+ occurrences to update)

3. Maintain variable scoping:
   - Move global variables inside sketch function
   - Ensure configuration remains accessible

4. Test all pattern types:
   - Grid, Random, Noise, Spiral, Radial, Flow
   - All animations (rotation, scale, translation, color)
   - Mouse and keyboard interaction

## Expected Outcome

After conversion:
- ✅ Preview and view use identical P5 rendering mode
- ✅ Scale animation formulas match exactly
- ✅ Frame timing synchronized
- ✅ All pattern rendering identical
- ✅ Animations work correctly in both contexts

## Files to Modify

- `/admin/includes/preview.php` - Lines 1000-1400 (P5 rendering section)

## Testing Checklist

- [ ] All pattern types render identically
- [ ] Scale animation min/max works correctly
- [ ] Rotation animation works
- [ ] Translation animation works
- [ ] Color animation works
- [ ] Background image displays correctly
- [ ] Mouse interaction works
- [ ] Keyboard interaction works
- [ ] No JavaScript console errors
- [ ] Visual comparison: preview === view

## Success Criteria

- User reports: "Preview and view now match perfectly"
- No visual discrepancies between preview and view
- All animations working in both contexts
- Code follows P5.js best practices
