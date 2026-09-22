# 22 — TOOL THEME INTEGRATION AND RESPONSIVE UI



## 1. Purpose



The **Favorite Web Tools Theme Integration and Responsive UI System** defines how `favorite-web-tools` renders its frontend inside the currently active Favorite CMS theme.



The system must ensure:



* Active theme Header is preserved.

* Active theme Footer is preserved.

* Active theme layout is respected.

* Active theme dark/light mode is followed.

* Theme changes remain compatible.

* Tool UI works on mobile, tablet, and desktop.

* Tool-specific styling does not damage the active theme.

* Tool previews remain isolated from the CMS page.



Favorite Web Tools is a CMS plugin, not an independent website/theme.



---



# 2. Core Principle



The active Favorite CMS theme owns the overall website shell.



Favorite Web Tools owns only the tool content.



```text

ACTIVE CMS THEME

        │

        ├── Header

        │

        ├── Main Content

        │      │

        │      └── Favorite Web Tools

        │

        └── Footer

```



The plugin must not replace the site's existing Header/Footer.



---



# 3. Active Theme Requirement



Every normal public Favorite Web Tools page must render using the currently active CMS theme.



The implementation must inspect the actual Favorite CMS theme architecture before implementation.



The agent must identify:



* Active theme loader.

* Layout/template system.

* Header rendering mechanism.

* Footer rendering mechanism.

* Content sections.

* Theme CSS loading.

* Theme JavaScript loading.

* Theme responsive system.

* Theme dark/light mechanism.



---



# 4. No Independent Site Shell



Favorite Web Tools must not create a separate application shell containing its own:



* Header.

* Footer.

* Main site navigation.

* Site-wide theme system.



unless the CMS explicitly requires a plugin-specific layout wrapper.



---



# 5. Header Integration



The Web Tools frontend must carry the active theme's Header.



Conceptually:



```text

Theme Header

     ↓

Favorite Web Tools Page

```



The plugin must use the existing CMS/theme rendering mechanism.



Do not duplicate the Header markup inside the plugin.



---



# 6. Footer Integration



The Web Tools frontend must carry the active theme's Footer.



Conceptually:



```text

Favorite Web Tools Content

     ↓

Theme Footer

```



The plugin must use the existing CMS/theme mechanism.



Do not create a second plugin Footer.



---



# 7. Header/Footer Consistency



The following should remain consistent with normal CMS pages:



* Header appearance.

* Navigation.

* Logo.

* User/profile area.

* Theme controls.

* Footer content.

* Footer links.

* Theme scripts.



Favorite Web Tools should feel like another page of the same website.



---



# 8. Theme Layout Integration



If the CMS provides a standard page/content layout, Favorite Web Tools should render inside it.



Conceptually:



```text

CMS Page Layout

 ├── Header

 ├── Main Container

 │     └── Web Tools

 └── Footer

```



Do not bypass the normal theme layout without a documented reason.



---



# 9. Theme Switching



The plugin must not hard-code a particular theme.



If the administrator changes the active CMS theme:



```text

Theme A

   ↓

Theme B

```



Favorite Web Tools should use Theme B's supported layout, Header, Footer, and theme state automatically where the CMS architecture supports it.



---



# 10. Dark/Light Mode — Mandatory



Favorite Web Tools must be sensitive to the active theme's dark/light mode.



The plugin must follow the theme's existing mode rather than creating a separate independent mode system.



```text

Theme Light

     ↓

Web Tools Light



Theme Dark

     ↓

Web Tools Dark

```



---



# 11. Existing Theme Mode



The agent must inspect the actual active theme to determine how dark/light mode works.



Possible mechanisms include:



* `body.dark`.

* HTML class.

* Data attribute.

* CSS variable changes.

* Theme JavaScript state.

* Existing theme API/event.



The implementation must follow the actual mechanism.



---



# 12. `body.dark` Compatibility



If the active theme uses:



```text

body.dark

```



Favorite Web Tools must respond to it.



Conceptually:



```text

body.dark

    ↓

Favorite Web Tools dark styles

```



When `body.dark` is removed:



```text

Light state

    ↓

Favorite Web Tools light styles

```



The plugin must not force `body.dark` itself.



---



# 13. No Duplicate Theme Toggle



If the active theme already provides a dark/light toggle, Favorite Web Tools must not create another global theme toggle.



The active theme remains responsible for changing theme mode.



Favorite Web Tools only follows the active state.



---



# 14. Theme Mode Synchronization



If theme mode changes dynamically without page reload, Web Tools should update accordingly when practical.



Example:



```text

Light

  ↓

User changes theme

  ↓

Dark

  ↓

Web Tools updates

```



Use an existing theme event/API when available.



If no theme event exists, use a safe observation mechanism only when necessary.



---



# 15. Theme CSS Variables



If the active theme provides CSS custom properties, Web Tools should reuse them.



Examples conceptually:



```text

\--background

\--foreground

\--text

\--muted

\--border

\--primary

\--card

```



The actual variable names must be discovered from the theme.



Do not assume these exact names exist.



---



# 16. No Full Theme Duplication



Do not copy the active theme's complete color palette into the plugin.



Avoid maintaining a second independent:



* Light palette.

* Dark palette.

* Typography system.

* Spacing system.



Use theme values where appropriate and add only tool-specific styling.



---



# 17. Theme-Aware Component Styling



The following components must remain readable in both modes:



* Tool cards.

* Tool title.

* Tool description.

* Input fields.

* Select fields.

* Buttons.

* Checkboxes.

* Radio buttons.

* JSON editors.

* Code input areas.

* Result panels.

* Error messages.

* Success messages.

* Help text.

* Tool navigation.

* Search.

* Category filters.

* Pagination.



---



# 18. Contrast



Text and controls must remain sufficiently distinguishable in both light and dark modes.



Do not rely on a light-only design.



Do not assume dark mode means simply inverting colors.



---



# 19. Form Controls



Form controls must respect the active theme.



Examples:



```text

Input

Textarea

Select

Checkbox

Radio

File Input

```



The plugin should reuse theme form styles where compatible.



Otherwise, scoped Web Tools styles may be added.



---



# 20. Buttons



Buttons should follow the active theme's visual language where practical.



If the theme provides reusable button classes/components, reuse them.



Tool-specific button variants may be added when necessary.



---



# 21. Cards



Tool cards should respect:



* Theme background.

* Border.

* Shadow.

* Text color.

* Hover state.

* Dark/light state.



Avoid fixed colors that make cards unreadable in dark mode.



---



# 22. Search UI



The Tools Home search interface must follow the active theme.



Search fields, filters, category controls, and result cards must remain compatible with both modes.



---



# 23. Category UI



Category navigation must use the active theme's layout and visual language.



Category pages must remain responsive.



---



# 24. Tool Page Layout



A standard tool page should conceptually contain:



```text

Theme Header

     ↓

Tool Breadcrumb / Navigation

     ↓

Tool Title

     ↓

Tool Description

     ↓

Tool Input Area

     ↓

Tool Actions

     ↓

Processing State

     ↓

Result Area

     ↓

Related Tools (if enabled)

     ↓

Theme Footer

```



Exact structure may follow the active theme.



---



# 25. Responsive Design



Favorite Web Tools must support:



* Mobile.

* Tablet.

* Desktop.

* Large desktop screens.



The UI must not require horizontal scrolling for normal tool usage.



---



# 26. Mobile-First Principle



Where practical, tool interfaces should be designed from a mobile-first baseline and enhanced for larger screens.



---



# 27. Desktop Layout



On larger screens, the system may use:



* Multi-column input/output layouts.

* Wider code editors.

* Side-by-side panels.

* Tool category navigation.



Only where appropriate for the tool.



---



# 28. Mobile Layout



On smaller screens:



* Columns may stack.

* Buttons may expand or wrap.

* Input/output panels may become vertical.

* Tool cards may become single-column.

* Navigation should remain usable.

* Code/content areas must remain accessible.



---



# 29. Responsive Breakpoints



Do not blindly introduce a completely independent breakpoint system.



Inspect the active theme first.



Where possible, reuse the theme's existing responsive breakpoints.



Plugin-specific breakpoints may be added only where necessary.



---



# 30. Container Width



Favorite Web Tools should respect the active theme's content/container system where practical.



Do not unnecessarily force:



```text

width: 100vw

```



or another full-screen layout that breaks the theme.



---



# 31. Overflow



Tool components must safely handle:



* Long text.

* Long URLs.

* Large JSON.

* Long code.

* Large output.

* Wide tables.

* Long error messages.



Horizontal overflow should be contained within the relevant component when required.



---



# 32. Code Input/Output



Code-oriented tools may require:



* Monospace font.

* Horizontal scrolling.

* Line wrapping options.

* Copy controls.

* Clear controls.



These should remain theme compatible.



---



# 33. Large Results



Large tool outputs must not break the page layout.



Examples:



```text

Large JSON

Large HTML

Large CSS

Large JavaScript

Long text

```



Use controlled scrollable result areas where appropriate.



---



# 34. Accessibility



The responsive interface must support:



* Keyboard navigation.

* Visible focus states.

* Proper labels.

* Semantic controls.

* Accessible buttons.

* Screen-reader-friendly status messages.

* Sufficient contrast.

* Error association with inputs.



---



# 35. Focus State



Focus indicators must remain visible in both light and dark mode.



Do not remove browser/theme focus indicators without replacing them with an accessible equivalent.



---



# 36. Loading State



Loading states must work across theme modes.



Examples:



```text

Processing...

Uploading...

Preparing...

Generating...

```



Python/API tools may show meaningful stages when known.



Do not show fake percentage progress.



---



# 37. Error State



Errors must remain readable in both modes.



Error presentation should support:



* General errors.

* Field errors.

* Network errors.

* API errors.

* Timeout errors.

* Tool validation errors.



---



# 38. Success State



Success messages/results must also follow the active theme.



Do not hard-code a light-only success design.



---



# 39. HTML Preview Isolation



The tool UI must remain theme-integrated, but user-provided HTML preview must remain isolated.



Conceptually:



```text

Theme Page

   │

   └── Web Tools

         │

         └── Isolated HTML Preview

```



The preview must not modify:



* Theme Header.

* Theme Footer.

* CMS navigation.

* Web Tools controls.

* Other CMS content.



---



# 40. CSS Preview Isolation



CSS entered by a user must not accidentally style:



```text

body

header

footer

CMS navigation

other plugin UI

```



Use an isolated preview mechanism where appropriate.



---



# 41. JavaScript Preview Isolation



User JavaScript must not receive privileged access to the CMS page.



If JavaScript execution is intentionally supported, it must run in the defined isolated environment.



---



# 42. Theme vs Preview Separation



The following distinction is mandatory:



```text

Plugin UI

    ↓

Follows Active Theme



User Preview

    ↓

Isolated Sandbox

```



Theme integration must never weaken preview security.



---



# 43. CSS Scope



Favorite Web Tools styles should be scoped to the plugin/tool UI.



Avoid unnecessary global rules.



Prefer conceptual structure:



```text

.favorite-web-tools

    .tool-card

    .tool-input

    .tool-actions

    .tool-result

```



Exact class names may differ according to implementation.



---



# 44. JavaScript Scope



Plugin JavaScript should avoid polluting global browser namespace.



Use the CMS/plugin's existing JavaScript module/bundle convention where available.



---



# 45. Asset Loading



Tool CSS and JavaScript must use the existing CMS/theme/plugin asset system.



Avoid loading plugin assets on unrelated pages unless required.



---



# 46. Theme Asset Compatibility



The plugin must not:



* Replace theme CSS.

* Replace theme JavaScript.

* Override theme global styles unnecessarily.

* Disable theme scripts.

* Remove theme Header/Footer assets.



---



# 47. Theme Component Reuse



If the active theme provides reusable components, Web Tools should reuse them where practical.



Examples:



```text

Buttons

Cards

Forms

Alerts

Tabs

Modals

Containers

Breadcrumbs

```



The plugin may provide specialized components when a tool requires them.



---



# 48. User Theme Preference



If the active CMS theme already stores the user's theme preference, Web Tools should follow that system.



Do not create a separate Web Tools-only preference.



---



# 49. Logged-In and Anonymous Users



Theme integration must work for:



* Anonymous visitors.

* Logged-in users.

* Active members.



Access control remains governed by:



`04-ACCESS-CONTROL.md`



Theme state must never affect authorization.



---



# 50. Protected Tool UI



For:



```text

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



the UI should show the appropriate access state while still rendering inside the active theme.



---



# 51. Theme-Aware Access Messages



Access messages should respect the active theme.



Examples:



```text

Login required

Membership required

```



Do not create a visually disconnected plugin page.



---



# 52. SEO



Tool pages should use the existing CMS/theme SEO infrastructure where available.



The plugin should provide metadata through supported CMS mechanisms.



---



# 53. Browser Compatibility



The UI should use browser capabilities supported by the Favorite CMS target environment.



Do not add unnecessary polyfills or frameworks without a real requirement.



---



# 54. Performance



The frontend should avoid unnecessary:



* Large CSS files.

* Large JavaScript bundles.

* Global assets.

* Duplicate libraries.

* Theme library duplication.



If the active theme already provides a dependency, reuse it where safe.



---



# 55. Theme Change Acceptance



After switching the active theme, the following should be verified:



```text

Header → Active Theme Header

Footer → Active Theme Footer

Layout → Active Theme Layout

Colors → Active Theme compatible

Typography → Active Theme compatible

Dark/Light → Active Theme state

Responsive → Functional

```



---



# 56. Dark Mode Acceptance



At minimum, verify:



### Light



```text

Theme = Light

↓

Tool UI = Readable Light UI

```



### Dark



```text

Theme = Dark

↓

Tool UI = Readable Dark UI

```



### Dynamic Change



```text

Light

↓

Theme Toggle

↓

Dark

↓

Tool UI Updates

```



where dynamic theme switching is supported by the CMS/theme.



---



# 57. No Plugin-Owned Global Theme State



Favorite Web Tools must not maintain an independent global:



```text

webToolsTheme = light/dark

```



as the authoritative theme state.



The active CMS theme remains authoritative.



---



# 58. No Theme Fork



Do not copy the active theme into the plugin.



Do not create:



```text

favorite-web-tools-theme

```



as a replacement for the CMS theme.



---



# 59. Extension Compatibility



Future custom renderers/components introduced through:



`21-TOOL-EXTENSION-AND-DEVELOPER-API.md`



must also follow:



* Active theme.

* Dark/light mode.

* Responsive behavior.

* Accessibility.

* CSS scope.



---



# 60. Developer Rules



The AI agent must:



1. Inspect the active theme architecture first.

2. Identify the existing Header mechanism.

3. Identify the existing Footer mechanism.

4. Identify the existing page/layout mechanism.

5. Identify dark/light implementation.

6. Identify theme CSS variables where available.

7. Identify responsive conventions.

8. Reuse existing theme components where practical.

9. Reuse CMS asset loading.

10. Keep Web Tools content inside the active theme layout.

11. Never create a duplicate global theme system.

12. Never create a duplicate Header.

13. Never create a duplicate Footer.

14. Never force dark/light state.

15. Keep preview content isolated.

16. Scope plugin CSS.

17. Keep plugin JavaScript scoped.

18. Preserve accessibility.

19. Preserve mobile compatibility.

20. Preserve desktop compatibility.

21. Test light mode.

22. Test dark mode.

23. Test theme switching where supported.

24. Test multiple active themes where practical.



---



# 61. Required Acceptance Criteria



The implementation is acceptable only when:



* Favorite Web Tools pages use the active CMS theme.

* Active theme Header is displayed.

* Active theme Footer is displayed.

* Plugin does not unnecessarily create a separate site shell.

* Theme navigation remains functional.

* Theme assets remain functional.

* Theme dark/light state is respected.

* `body.dark` is supported if used by the active theme.

* Plugin does not create a conflicting theme toggle.

* Theme CSS variables are reused where available.

* Plugin UI is readable in light mode.

* Plugin UI is readable in dark mode.

* Tool inputs are theme compatible.

* Tool outputs are theme compatible.

* Tool cards are theme compatible.

* Search/category UI is theme compatible.

* Error/success states are theme compatible.

* Tool pages are responsive.

* Mobile layouts work correctly.

* Desktop layouts work correctly.

* Plugin CSS does not unnecessarily override global theme styles.

* Plugin JavaScript does not pollute the global namespace.

* HTML preview is isolated.

* CSS preview is isolated.

* JavaScript preview is isolated.

* Theme integration does not weaken security.

* Theme switching remains compatible.

* Existing CMS theme architecture is reused.

* No duplicate theme system is introduced.



---



# 62. Final Theme Architecture



```text

                     FAVORITE CMS

                          │

                          ▼

                    ACTIVE THEME

                          │

              ┌───────────┴───────────┐

              │                       │

           HEADER                   FOOTER

              │                       │

              └───────────┬───────────┘

                          │

                    MAIN CONTENT

                          │

                          ▼

                FAVORITE WEB TOOLS

                          │

            ┌─────────────┼─────────────┐

            │             │             │

         Tool UI       Tool Result    Search

            │             │             │

            └─────────────┼─────────────┘

                          │

                   ACTIVE THEME MODE

                          │

                    ┌─────┴─────┐

                    │           │

                  LIGHT        DARK

                    │           │

                    ▼           ▼

               Theme-aware  Theme-aware

                 UI            UI

```



The final principle is:



```text

Favorite CMS Theme

        ↓

Owns Header + Footer + Theme State

        ↓

Favorite Web Tools

        ↓

Owns Tool UI + Tool Logic

        ↓

Follows Theme

        ↓

Light / Dark / Responsive

```



Favorite Web Tools must look and behave like a **native part of the active Favorite CMS theme**, not like a separate application embedded inside the website.




---

# Implementation Hardening Addendum — Active Theme Boundary

The active Favorite CMS theme is the sole owner of the global public shell. Favorite Web Tools must render content through the canonical CMS theme renderer rather than directly including a hardcoded theme header/footer path.

Additional requirements:

- Do not register plugin/theme template paths globally if doing so can change template resolution for another active theme.
- Do not use regex/string stripping of header/footer markup as a duplicate-shell workaround. Fix the rendering boundary instead.
- Public not-found and access-gate states are content views rendered inside the active theme shell.
- JSON/API/execution/health endpoints remain machine responses and are not wrapped in the theme shell.
- When switching themes, the next request must resolve only the newly active theme; no stale template-path state may leak across themes.

These rules refine Sections 4–7 and do not change the requirement to preserve the active theme's header, footer, responsive behavior, and dark/light state.
