# Responsive Design Testing Guide

## L'Arsenal - Marché Noir

### Test Environment Setup

#### Tools Required

- Chrome DevTools (F12) with responsive mode
- Firefox Responsive Design Mode (Ctrl+Shift+M)
- Optional: Real devices (iPhone, iPad)

#### Browser Setup

1. Open DevTools
2. Click device toggle or press Ctrl+Shift+M
3. Set viewport width and test at each breakpoint

---

## Breakpoint Testing Matrix

### 1️⃣ MOBILE (iPhone) - Width ≤767px

**Target Devices** (portrait by default)

- iPhone 12/13/14: 390×844px
- iPhone 15: 430×932px
- iPhone SE: 375×667px

#### Visual Checklist

- [ ] Header height: 60px
- [ ] Hamburger menu visible (icons only in actions)
- [ ] Logo and title visible
- [ ] Theme toggle visible
- [ ] Hamburger button on right
- [ ] Search bar hidden
- [ ] Wallet hidden (only icons visible later)

#### Mobile Menu (Drawer) Tests

- [ ] Hamburger click opens drawer from left
- [ ] Drawer width: ~280px (80% of screen max)
- [ ] Overlay appears behind drawer
- [ ] Search input in drawer (placeholder: "Rechercher...")
- [ ] Wallet info shows in drawer (if connected)
- [ ] User actions show vertically:
  - [ ] Profile
  - [ ] Cart
  - [ ] Logout/Register/Login
- [ ] Clicking action closes drawer
- [ ] Clicking overlay closes drawer
- [ ] ESC key closes drawer
- [ ] Focus returns to hamburger after close
- [ ] Body scroll locked when drawer open
- [ ] No horizontal scroll on any page

#### Page: index.php (Catalog)

- [ ] Sidebar HIDDEN
- [ ] Grid: 1 column (full width items)
- [ ] Item rows stack vertically
- [ ] Prices and stock info visible
- [ ] Buy buttons: full-width, ≥44px height
- [ ] Filter section NOT visible (would be in drawer if needed)

#### Page: inventory.php

- [ ] Sidebar HIDDEN
- [ ] Grid: 2-4 columns (100-122px per card)
- [ ] Cards clickable
- [ ] Quantity badge visible (top-right)
- [ ] Item name readable (2-line clamp)

#### Page: details.php

- [ ] Single column layout (image on top)
- [ ] Item image: centered, ~250px height
- [ ] Title, price, rating stacked vertically
- [ ] Stats: 1 column
- [ ] Description readable
- [ ] Properties: 1 column
- [ ] Quantity/Buy controls: full-width
- [ ] "Buy" button: ≥44px height, full-width
- [ ] Banners (left/right): HIDDEN
- [ ] No overflow on sides

#### Page: panier.php

- [ ] Cart title visible
- [ ] Cart rows vertical layout:
  - [ ] Item image on top
  - [ ] Item name
  - [ ] Quantity controls (full row)
  - [ ] Price
  - [ ] Delete button
- [ ] Total summary centered
- [ ] Confirm button: full-width, ≥44px height
- [ ] Footer sticky at bottom

#### Page: login.php / register

- [ ] Form container: 100% width (no max-width)
- [ ] Title centered
- [ ] Form inputs: full-width
- [ ] Inputs: 44px+ height with large padding
- [ ] Submit button: full-width, ≥44px
- [ ] Links/switches readable
- [ ] No horizontal overflow

#### Page: profile.php

- [ ] Avatar: 70px (smaller)
- [ ] Hero section centered, single column
- [ ] Profile cards: 1 column
- [ ] Input fields: full-width
- [ ] Buttons: full-width, ≥44px height
- [ ] Danger section readable
- [ ] All text sizes readable (no overflow)

#### Page: enigme.php / backgroundview.php

- [ ] Background image fills viewport
- [ ] Centered content readable
- [ ] No overflow
- [ ] Header and footer properly positioned

#### Landscape Orientation Test (e.g., 844×390)

- [ ] Header still ~60px height
- [ ] No vertical scroll on simple pages
- [ ] Drawer still accessible
- [ ] All critical content visible without scroll

---

### 2️⃣ TABLET (iPad) - Width 768px to 1199px

**Target Devices**

- iPad Air: 768×1024px
- iPad (11"): 834×1112px
- iPad Pro (12.9"): 1024×1366px

#### Visual Checklist

- [ ] Header height: 65px
- [ ] Hamburger still visible
- [ ] Search bar hidden (in drawer)
- [ ] Wallet visible but compact
- [ ] All action icons visible

#### Tablet Menu Tests

- [ ] Same drawer behavior as mobile
- [ ] Drawer slightly wider (300px max)
- [ ] All navigation items accessible

#### Page: index.php

- [ ] Sidebar visible but RÉTRACTABLE
- [ ] Grid: 2 columns for items
- [ ] Sidebar toggles wide/collapsed
- [ ] Toggle button visible (circle on right)
- [ ] Collapsed width: 80px
- [ ] Normal width: 280px

#### Page: inventory.php

- [ ] Sidebar visible (rétractable)
- [ ] Grid: 2 columns (130px cards)
- [ ] Better density than mobile
- [ ] Spacing optimized

#### Page: details.php

- [ ] 2-column layout: image (300px) + info
- [ ] Image height: ~300px
- [ ] Title/price/specs organized
- [ ] Stats: 2 columns
- [ ] Properties: 2 columns
- [ ] Banners: HIDDEN (not added until desktop)

#### Page: panier.php

- [ ] Cart rows: horizontal layout
- [ ] Image | Name | Qty | Price | Delete
- [ ] Better use of width
- [ ] Footer sticky, readable
- [ ] Overall spacing cleaner

#### Page: login.php

- [ ] Form: larger padding
- [ ] Max-width still respected
- [ ] Inputs larger than mobile

#### Page: profile.php

- [ ] Hero: single row (avatar + name/role)
- [ ] Grid: still 1 column (not 2 until desktop)
- [ ] Better padding than mobile
- [ ] Forms wider, more spacious

#### Landscape Mode (834×1112 -> landscape 1112×834)

- [ ] All content fits
- [ ] No unwanted scroll
- [ ] Header properly positioned

---

### 3️⃣ DESKTOP (Original) - Width ≥1200px

**Target Viewports**

- 1366×768 (common laptop)
- 1920×1080 (Full HD)
- 2560×1440 (4K)

#### Visual Checklist

- [ ] Header height: 70px
- [ ] Hamburger HIDDEN
- [ ] Search bar visible inline (400px)
- [ ] Wallet visible with full text
- [ ] All original actions visible
- [ ] No drawer visible/accessible

#### Page: index.php

- [ ] Sidebar visible: 280px wide
- [ ] Grid: 4 columns
- [ ] Original styling preserved
- [ ] Toggle button on sidebar right
- [ ] Collapsed mode works (80px)

#### Page: inventory.php

- [ ] Sidebar visible (rétractable)
- [ ] Grid: auto-fill columns (~148px)
- [ ] Original card density
- [ ] Tooltip functionality intact

#### Page: details.php

- [ ] 2-column: 380px image + 1fr content
- [ ] Image height: 380px
- [ ] Banners visible (left & right)
- [ ] Banner animations work
- [ ] Original styling 100% preserved

#### Page: panier.php

- [ ] Original layout
- [ ] Banners visible
- [ ] Animations work
- [ ] Footer behavior unchanged

#### Page: login.php

- [ ] Form max-width: 550px
- [ ] Centered on page
- [ ] Original appearance

#### Page: profile.php

- [ ] Grid: 2 columns (2fr + 1fr)
- [ ] Hero row layout
- [ ] Original styling preserved

---

## Critical User Journeys

### Journey 1: Browse & Purchase

**Mobile Flow:**

1. [ ] Open app on mobile
2. [ ] Toggle hamburger menu
3. [ ] Menu opens with search bar
4. [ ] Type search, press enter
5. [ ] Redirects to inventory with results
6. [ ] Tap item card
7. [ ] Details page loads single-column
8. [ ] Scroll to see quantity/buy button
9. [ ] Select quantity
10. [ ] Tap "Add to Cart" (full-width button)
11. [ ] Success message appears
12. [ ] Tap cart icon (in header or drawer)
13. [ ] View panier (vertical layout)
14. [ ] Modify quantities
15. [ ] Confirm purchase

**Desktop Flow:**

1. [ ] Verify original workflow unchanged
2. [ ] Sidebar filters work
3. [ ] Grid shows 4 columns
4. [ ] Details normal layout
5. [ ] Cart normal sticky footer
6. [ ] All original interactions work

### Journey 2: User Account

**Mobile:**

1. [ ] Open hamburger menu
2. [ ] Tap "Mon Profil" or "Connexion"
3. [ ] Login/Profile loads full-width
4. [ ] Form inputs are large (44px+)
5. [ ] Avatar visible
6. [ ] All profile sections readable
7. [ ] Can update info
8. [ ] Can change password
9. [ ] Delete account section clear

**Desktop:**

1. [ ] Original profile layout (2-column)
2. [ ] All original features intact

---

## Accessibility Tests

### Keyboard Navigation (All Breakpoints)

- [ ] Tab through all interactive elements
- [ ] Focus outline visible on every focusable element
- [ ] Menu can be opened/closed with keyboard
- [ ] ESC key closes mobile menu
- [ ] Links and buttons reachable via tab

### Touch Targets (Mobile/Tablet)

- [ ] All buttons: ≥44×44px
- [ ] All form inputs: ≥44px height
- [ ] Menu items: ≥44px tall
- [ ] Spacing between targets: ≥8px

### Screen Reader (VoiceOver / NVDA)

- [ ] Header navigation labeled ("Navigation" role)
- [ ] Mobile menu toggle: aria-label + aria-expanded
- [ ] Button labels clear and descriptive
- [ ] Form fields labeled clearly
- [ ] Error messages announced

---

## Rotation & Orientation Tests

### Mobile (iPhone)

**Portrait -> Landscape**

- [ ] Header collapses properly
- [ ] Menu still accessible
- [ ] No content overflow
- [ ] Grid adapts if needed
- [ ] Landscape back **Portrait**
- [ ] Everything reverts smoothly
- [ ] No layout breaks

### Tablet (iPad)

**Portrait -> Landscape**

- [ ] Sidebar behavior consistent
- [ ] More columns show if space allows
- [ ] Header adjusts height if needed
- [ ] Back to portrait works smoothly

---

## Performance & Visual Regression

### Load Times

- [ ] index.php: <3s on 3G (mobile)
- [ ] details.php: <2s on 3G
- [ ] No layout shift on load
- [ ] Images load progressively
- [ ] No CLS (Cumulative Layout Shift) >0.1

### Visual Regression

- [ ] Desktop at 1920×1080: **visually identical** to pre-responsive
- [ ] All hover states work
- [ ] All animations smooth
- [ ] No flickering on menu toggle
- [ ] Scrolling smooth (not janky)

### Browser Compatibility

- [ ] Chrome/Edge 90+
- [ ] Firefox 88+
- [ ] Safari 14+ (iOS 14+)
- [ ] No console errors
- [ ] No broken CSS rules

---

## Bug Report Template

If issues found:

### Template

```
**Issue:** [Brief description]
**Breakpoint:** 390px / 768px / 1200px+
**Page:** [URL]
**Steps to Reproduce:**
1. [Step 1]
2. [Step 2]
3. [Expected vs Actual]

**Screenshot/Video:**
[Attach or describe]

**Fix Location:** [File + line approx]
```

---

## Sign-Off Checklist

### All Breakpoints Verified

- [ ] Mobile (<=767px): All pages tested
- [ ] Tablet (768-1199px): All pages tested
- [ ] Desktop (>=1200px): No regression
- [ ] Landscape modes: No breaks

### Critical Features Working

- [ ] Mobile menu: Open/close, Escape, overlay
- [ ] Catalog: Grids responsive, sidebar behavior
- [ ] Details: Reflow, banners hidden on small
- [ ] Cart: Layout changes, controls accessible
- [ ] Forms: Full-width, large hits, validation
- [ ] Profile: Avatar, layout shifts
- [ ] Header: Always accessible, properly sized

### Accessibility Met

- [ ] Touch targets >=44px (mobile/tablet)
- [ ] Focus visible on all interactive
- [ ] ARIA labels correct
- [ ] Keyboard navigation works
- [ ] No mobile zoom-on-focus issues

### No Desktop Regression

- [ ] Sidebar, grids, banners, animations
- [ ] All original layouts preserved
- [ ] No unintended breakpoint CSS applies
- [ ] Performance maintained

### Sign-Off By: ********\_******** Date: ********\_********

---

## Notes

- **Breakpoint Logic:** All new CSS uses mobile-first with `@media (min-width: Xpx)` for progressively larger screens
- **Cascade:** responsive.css loaded AFTER style.css ensures proper override
- **Custom Props:** Using `clamp()` for fluid sizing avoids hard breakpoints for type
- **Menu:**Mobile drawer is overlay-based, doesn't shift layout
- **Sidebar:** Hidden on mobile/tablet, visible but rétractable on desktop
- **Banners:** Decorative elements hidden on <=1199px to save space

---

**Version:** 1.0  
**Date Created:** 2026-04-01  
**Last Updated:** 2026-04-01
