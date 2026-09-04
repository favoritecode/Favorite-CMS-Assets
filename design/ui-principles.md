# Favorite CMS — UI Principles

Foundational design principles governing user interfaces across Favorite CMS and all future ecosystem extensions.

---

## 1. Core Principles

### Simple
Interfaces should feel immediate and intuitive. Present clear paths to complete tasks, eliminate unnecessary confirmation dialogs for non-destructive actions, and prefer obvious, standard design patterns over unconventional gimmicks.

### Clean
Clarity stems from structure. Leverage generous whitespace, purposeful grouping, and a cohesive grid to guide user attention. Every visual element on the screen must earn its place.

### Accessible
Accessibility is a first-class architectural requirement, not an afterthought. All interfaces must satisfy WCAG 2.1 AA standards as an absolute baseline: high text contrast, full keyboard navigability, meaningful ARIA semantics, and touch targets suitable for all dexterity levels.

### Responsive & Adaptive
Layouts must scale fluidly across mobile phones, tablets, laptops, and ultra-wide desktop monitors. Breakpoints must respond naturally to content constraints rather than arbitrary device dimensions.

### Lightweight & Fast
Speed is a core user feature. Interfaces must avoid heavy client-side JavaScript runtimes, unnecessary third-party CSS frameworks, and massive graphic payloads. Every millisecond saved during initial paint and interaction empowers users.

### Content-First
The CMS is built to create, manage, and publish content. Chrome, sidebars, toolbars, and administrative panels must always frame and support the user's content—never overshadow, clip, or compete with it.

### Consistent
Interactions, keyboard shortcuts, modal behaviors, and form validation styles must behave identically whether an admin is configuring Favorite CMS Core or managing transactions in Favorite Pay.

### Professional Quality
Ecosystem interfaces should exude reliability, stability, and craft. Meticulous typography, aligned baselines, crisp icons, and respectful color balance communicate enterprise-grade trustworthiness.

---

## 2. Visual Restraint & Performance

### Avoid Unnecessary Visual Effects
- **No Skeuomorphism**: Avoid imitation textures, faux leather, stamped metal, or glossy plastic gradients.
- **No Heavy Glassmorphism**: Avoid deep `backdrop-filter: blur()` effects that trigger expensive GPU compositing layers on low-power mobile GPUs.
- **No Gratuitous Shadows**: Use single-layer, subtle drop shadows strictly to indicate true elevation (dropdowns, popovers, modals). Avoid multi-colored glowing shadows.

### Avoid Excessive Animations
- **Functional Motion Only**: Motion must communicate state changes (e.g., drawer sliding out, accordion opening) rather than pure decoration.
- **Snappy Durations**: Transitions must remain between `150ms` and `200ms` with standard `ease-out` timing curves. Sluggish animations make software feel unresponsive.
- **Respect Motion Preferences**: Strictly observe `prefers-reduced-motion: reduce` by replacing sliding or scaling animations with simple, instantaneous opacity fades or direct cuts.

### Usable on Low-End Devices and Slower Connections
- Optimize all SVGs and compress images before distribution.
- Ensure all essential page content and administrative forms are fully functional even before client-side scripts complete execution.
- Maintain a lean DOM depth and avoid deeply nested layout containers.

---

## 3. Ecosystem Visual Hierarchy

Favorite CMS is the core foundation. Future ecosystem products (Favorite Pay, Favorite Digital, Favorite Shop, Favorite Web) branch from this core with clear, predictable visual harmony:

1. **Shared Foundation**:
   - Every ecosystem application shares the exact same base typography, border radii (`6px` controls, `8px` cards), 4px spacing scale, and neutral color palette.
2. **Sub-Brand Accents**:
   - Each ecosystem product uses its signature accent hue on high-visibility touchpoints:
     - **Favorite CMS**: Favorite Blue (`#2563EB`) & Vivid Sky (`#0EA5E9`)
     - **Favorite Pay**: Emerald Green (`#10B981`)
     - **Favorite Digital**: Violet (`#8B5CF6`)
     - **Favorite Shop**: Amber (`#F59E0B`)
     - **Favorite Web**: Cyan / Teal (`#06B6D4`)
3. **Parent Brand Anchor**:
   - The primary navigation header always displays the ecosystem product logo with the Favorite CMS parent mark, establishing clear brand provenance while giving each extension its unique operational identity.
