# 🎨 Design System Quick Reference Guide

## Color System

### Primary Colors (Ocean Theme)
```css
/* Deep to Light progression */
--color-primary-900: #0a3d62  /* Deepest background */
--color-primary-800: #0c4c7a  /* Deep background */
--color-primary-700: #1565c0  /* Medium dark */
--color-primary-600: #1976d2  /* Base primary */
--color-primary-500: #2196f3  /* Medium light */
--color-primary-400: #42a5f5  /* Light - elevated elements */
--color-primary-300: #64b5f6  /* Lighter - hover states */
--color-primary-200: #90caf9  /* Lightest - selected states */
```

### When to Use:
- **900-800**: Deep backgrounds, headers
- **600**: Primary buttons, links
- **400-300**: Hover states, highlights
- **200**: Selected/active states

---

## Shadow System

### Small Shadows (Subtle Depth)
```css
box-shadow: 
  0 -1px 2px 0 rgba(255, 255, 255, 0.05),  /* Light top */
  0 2px 4px 0 rgba(0, 0, 0, 0.1);           /* Dark bottom */
```
**Use for:** Most UI elements, badges, small cards

### Medium Shadows (Standard Elevation)
```css
box-shadow: 
  0 -2px 4px 0 rgba(255, 255, 255, 0.06),  /* Light top */
  0 4px 8px 0 rgba(0, 0, 0, 0.15);          /* Dark bottom */
```
**Use for:** Cards, containers, buttons

### Large Shadows (Prominent Elements)
```css
box-shadow: 
  0 -3px 6px 0 rgba(255, 255, 255, 0.08),  /* Light top */
  0 8px 16px 0 rgba(0, 0, 0, 0.2);          /* Dark bottom */
```
**Use for:** Hover states, modals, floating elements

### Inset Shadows (Sunken Effect)
```css
box-shadow: 
  inset 0 2px 4px 0 rgba(0, 0, 0, 0.1),    /* Dark top */
  inset 0 -1px 2px 0 rgba(255, 255, 255, 0.05);  /* Light bottom */
```
**Use for:** Input fields, tables, containers

---

## Component Recipes

### Elevated Card
```html
<div class="card-elevated">
  <h3>Card Title</h3>
  <p>Card content</p>
</div>
```

**CSS:**
```css
.card-elevated {
  background: var(--color-neutral-600);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  padding: var(--spacing-lg);
  transition: all var(--transition-base);
}

.card-elevated:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-xl);
}
```

---

### Primary Button
```html
<button class="btn-primary">Click Me</button>
```

**CSS:**
```css
.btn-primary {
  background: linear-gradient(180deg, 
    var(--color-primary-500) 0%, 
    var(--color-primary-600) 100%);
  color: white;
  padding: var(--spacing-sm) var(--spacing-lg);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);
  border: none;
  cursor: pointer;
  transition: all var(--transition-base);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}
```

---

### Inset Input Field
```html
<input type="text" class="input-elevated" placeholder="Enter text">
```

**CSS:**
```css
.input-elevated {
  background: var(--color-neutral-700);
  border: 1px solid var(--color-neutral-600);
  border-radius: var(--radius-md);
  padding: var(--spacing-sm) var(--spacing-md);
  box-shadow: var(--shadow-inset);
  transition: all var(--transition-base);
}

.input-elevated:focus {
  background: var(--color-neutral-600);
  border-color: var(--color-primary-500);
  box-shadow: 
    var(--shadow-inset),
    0 0 0 3px rgba(33, 150, 243, 0.1);
  outline: none;
}
```

---

### Stat Card with Gradient Icon
```html
<div class="stat-card">
  <div class="stat-icon-wrapper stat-icon-primary">
    <i class="icon"></i>
  </div>
  <div class="stat-content">
    <p class="stat-label">Total Revenue</p>
    <h2 class="stat-value">$12,450</h2>
    <div class="stat-change stat-change-positive">
      +12.5%
    </div>
  </div>
</div>
```

**CSS:**
```css
.stat-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  gap: 1rem;
  box-shadow: var(--shadow-md);
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, 
    var(--color-primary-500), 
    var(--color-accent-400));
}

.stat-icon-wrapper {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  background: linear-gradient(135deg, 
    var(--color-primary-500), 
    var(--color-primary-600));
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 
    0 -2px 8px 0 rgba(255, 255, 255, 0.2) inset,
    0 4px 8px 0 rgba(0, 0, 0, 0.15);
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-xl);
}
```

---

### Product Card with Hover Effect
```html
<div class="product-card-enhanced">
  <div class="product-image-wrapper">
    <img src="product.jpg" alt="Product">
    <div class="product-overlay">
      <button class="btn-quick-view">Quick View</button>
    </div>
  </div>
  <div class="product-details">
    <h3>Product Name</h3>
    <p class="price">$29.99</p>
    <button class="btn-add-cart">Add to Cart</button>
  </div>
</div>
```

**CSS:**
```css
.product-card-enhanced {
  background: white;
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  transition: all var(--transition-slow);
}

.product-card-enhanced:hover {
  transform: translateY(-8px);
  box-shadow: var(--shadow-xl);
}

.product-image-wrapper {
  position: relative;
  overflow: hidden;
}

.product-image-wrapper img {
  transition: transform var(--transition-slow);
}

.product-card-enhanced:hover img {
  transform: scale(1.05);
}

.product-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, 
    transparent 50%, 
    rgba(0,0,0,0.7));
  opacity: 0;
  transition: opacity var(--transition-base);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding: 1rem;
}

.product-card-enhanced:hover .product-overlay {
  opacity: 1;
}
```

---

## Design Patterns

### 1. **Layering Cards**
```
Background (darkest)
  └─ Container (dark)
      └─ Card (medium)
          └─ Element (light)
              └─ Hover (lightest)
```

### 2. **Button Hierarchy**
```
Primary: Full gradient + large shadow
Secondary: Outlined + small shadow
Ghost: No background + no shadow
```

### 3. **Form Elements**
```
Inputs: Inset shadow (sunken)
Buttons: Outset shadow (elevated)
Checkboxes: Custom with gradient on checked
```

### 4. **Hover States**
```
Cards: translateY(-4px) + larger shadow
Buttons: translateY(-2px) + larger shadow
Images: scale(1.05)
Overlays: Fade in
```

---

## Spacing Scale

```css
--spacing-xs: 0.25rem   /* 4px */
--spacing-sm: 0.5rem    /* 8px */
--spacing-md: 1rem      /* 16px */
--spacing-lg: 1.5rem    /* 24px */
--spacing-xl: 2rem      /* 32px */
--spacing-2xl: 3rem     /* 48px */
--spacing-3xl: 4rem     /* 64px */
```

---

## Border Radius Scale

```css
--radius-sm: 0.375rem   /* 6px - badges */
--radius-md: 0.5rem     /* 8px - buttons */
--radius-lg: 0.75rem    /* 12px - cards */
--radius-xl: 1rem       /* 16px - large cards */
--radius-2xl: 1.5rem    /* 24px - hero sections */
```

---

## Animation Timing

```css
--transition-fast: 150ms    /* Hover highlights */
--transition-base: 200ms    /* Most interactions */
--transition-slow: 300ms    /* Card reveals */
```

**Easing:**
```css
cubic-bezier(0.4, 0, 0.2, 1)  /* Standard ease-out */
```

---

## Common Combinations

### Hero Section
```css
background: linear-gradient(135deg, #1976d2, #2196f3, #42a5f5);
padding: 3rem 2rem;
box-shadow: 
  0 -2px 8px 0 rgba(255, 255, 255, 0.1) inset,
  0 4px 12px 0 rgba(0, 0, 0, 0.15);
```

### Glass Card
```css
background: rgba(255, 255, 255, 0.05);
backdrop-filter: blur(10px);
border: 1px solid rgba(255, 255, 255, 0.1);
border-radius: var(--radius-xl);
box-shadow: var(--shadow-xl);
```

### Progress Bar
```css
/* Container (inset) */
.progress-container {
  background: var(--color-neutral-800);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-inset);
  padding: 4px;
}

/* Fill (elevated) */
.progress-bar {
  background: linear-gradient(90deg, 
    var(--color-primary-500), 
    var(--color-accent-400));
  height: 8px;
  border-radius: var(--radius-sm);
  box-shadow: var(--shadow-sm);
}
```

---

## Best Practices

### ✅ DO:
- Layer lighter shades on darker backgrounds
- Use dual shadows (light + dark)
- Add hover states to interactive elements
- Maintain consistent spacing
- Use gradients for elevation effect
- Apply inset shadows to inputs
- Elevate on hover with transform + shadow

### ❌ DON'T:
- Use single flat shadows
- Mix too many shadow styles
- Forget hover states
- Use extreme transforms (>10px)
- Skip focus states
- Overuse animations
- Ignore mobile responsiveness

---

## Responsive Breakpoints

```css
/* Mobile */
@media (max-width: 640px) {
  /* Reduce hover effects */
  /* Single column layouts */
  /* Larger touch targets */
}

/* Tablet */
@media (max-width: 1024px) {
  /* 2-column layouts */
  /* Adjust spacing */
}

/* Desktop */
@media (min-width: 1025px) {
  /* Full effects */
  /* Multi-column layouts */
}
```

---

## Quick Tips

1. **Start with base shadow, increase on hover**
2. **Always add light from above (top lighter)**
3. **Use transform for smooth animations**
4. **Inset for inputs, outset for buttons**
5. **Gradient angle: 135deg for diagonal**
6. **Transition: 200ms is the sweet spot**
7. **Border radius: larger for bigger elements**
8. **Spacing: use consistent scale**

---

**Happy Designing! 🎨**

Need help? Check `UI_ENHANCEMENT_COMPLETE.md` for examples!

