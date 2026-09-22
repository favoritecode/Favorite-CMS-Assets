# 24 — TOOL SEARCH UI AND CATALOG



## 1. Purpose



This document defines the user-facing Search, Tool Catalog, Category Navigation, Tool Cards, Filters, Pagination, Empty States, and responsive behavior for `favorite-web-tools`.



This document works together with:



* `09-FRONTEND-TOOL-UI.md`

* `12-TOOL-CATEGORY-DISCOVERY.md`

* `17-TOOL-FRONTEND-RENDERING-AND-PREVIEW.md`

* `22-TOOL-THEME-INTEGRATION-AND-RESPONSIVE-UI.md`

* `23-TOOL-SEARCH-AND-DISCOVERY-API.md`



The UI must consume the centralized Tool Registry/Discovery API.



---



# 2. Core Principle



The catalog UI must never maintain its own independent list of tools.



```text

Tool Registry

      ↓

Discovery API

      ↓

Search / Category / Pagination

      ↓

Catalog UI

      ↓

Tool Card

      ↓

Tool Page

```



---



# 3. Public Catalog



The public catalog displays only:



```text

ACTIVE

```



tools.



It must never normally display:



```text

DRAFT

DISABLED

```



tools.



---



# 4. Protected Tools



Protected tools may still appear in the catalog.



Their card should clearly indicate:



```text

Login Required

```



or:



```text

Membership Required

```



The user can discover the tool even when they cannot currently execute it.



---



# 5. Access Labels



The UI supports exactly:



```text

Free

Login Required

Membership Required

```



Do not introduce:



* Premium.

* VIP.

* Credits.

* Tokens.

* Daily limit.

* Monthly limit.

* Usage remaining.



---



# 6. Catalog Page Structure



Conceptually:



```text

ACTIVE CMS THEME

│

├── Header

│

├── Favorite Web Tools

│

├── Search

│

├── Categories / Filters

│

├── Result Summary

│

├── Tool Grid

│

├── Pagination

│

└── Footer

```



The active CMS theme remains responsible for the overall site shell.



---



# 7. Search Area



The main catalog should provide a prominent search field.



Example:



```text

┌──────────────────────────────────────────┐

│ Search tools...                     🔍   │

└──────────────────────────────────────────┘

```



The exact visual style follows the active theme.



---



# 8. Search Input Behavior



The search field should support:



* Text input.

* Search submission.

* Clear search.

* Keyboard interaction.

* Loading state.

* Accessible label.

* Appropriate placeholder.



---



# 9. Search Trigger



The implementation may use:



* Submit button.

* Enter key.

* Debounced search.

* Combination of the above.



The choice should follow the CMS/frontend architecture.



---



# 10. Debounced Search



If live search is implemented, use a reasonable debounce.



Example flow:



```text

User types

   ↓

Short debounce

   ↓

Discovery API

   ↓

Update results

```



Do not send a network request for every keystroke unnecessarily.



---



# 11. Search Is Not Usage



Search requests are discovery operations.



They must not be counted as:



* Tool usage.

* Credits.

* Tokens.

* Daily quota.

* Monthly quota.



---



# 12. Search Result Summary



The catalog may show a simple summary such as:



```text

24 tools found

```



or:



```text

Showing tools for "JSON"

```



This is catalog information only.



---



# 13. Category Navigation



The catalog should provide category navigation.



Example:



```text

All

HTML

CSS

JavaScript

Developer

Text

PHP

Python

```



Categories must come from the Category system.



Do not hard-code the category list into multiple frontend files.



---



# 14. All Tools



An `All Tools` state should display all publicly discoverable ACTIVE tools.



Conceptually:



```text

All Tools

```



is equivalent to having no category filter.



---



# 15. Category Selection



Selecting a category should update the catalog.



Example:



```text

Developer

       ↓

Discovery API

       ↓

ACTIVE Developer tools

```



---



# 16. Category + Search



Search and category selection must work together.



Example:



```text

Category: Developer

Search: JSON

```



returns only:



```text

ACTIVE

\+

Developer category

\+

JSON search match

```



---



# 17. Filter UI



If additional filters are implemented, they should be limited to useful discovery filters.



Possible filters:



* Category.

* Engine.

* Access Mode.



Do not expose internal configuration as public filters.



---



# 18. Engine Filter



An engine filter may be provided if useful:



```text

HTML

CSS

JavaScript

PHP

Python API

```



This is optional.



The implementation should not add unnecessary filters just because the data exists.



---



# 19. Access Filter



An access filter may optionally allow:



```text

Free

Login Required

Membership Required

```



This is optional and should be used only if it improves discovery.



---



# 20. No Premium Filter



There must be no:



```text

Premium

VIP

Paid Tools

Credits

```



filter.



Membership Required is an access mode, not a premium category.



---



# 21. Filter Reset



When filters are active, the UI should provide a clear/reset option where appropriate.



Example:



```text

Clear Filters

```



Resetting filters returns the catalog to its default state.



---



# 22. Tool Card



Each tool should be represented by a consistent card.



Conceptually:



```text

┌────────────────────────────────────┐

│             ICON                   │

│                                    │

│ JSON Formatter                     │

│ Format and beautify JSON data.     │

│                                    │

│ Developer     •     Free           │

│                                    │

│              Open Tool             │

└────────────────────────────────────┘

```



---



# 23. Tool Card Metadata



A public card may contain:



* Icon.

* Thumbnail.

* Tool name.

* Short description.

* Category.

* Access mode.

* Open Tool action.



Only available public metadata should be displayed.



---



# 24. Tool Name



The tool name must be visually prominent.



Long names should wrap naturally without breaking the card layout.



---



# 25. Tool Description



The card should use the short description where available.



If a short description is not configured, the system may use an appropriate excerpt from the public description.



Avoid exposing internal configuration.



---



# 26. Tool Icon



If a tool icon exists, use it.



If not, use the platform/theme's standard fallback.



Do not require every tool to have a custom icon.



---



# 27. Tool Thumbnail



If a thumbnail is configured, it may be displayed.



The image must:



* Use controlled URLs.

* Have appropriate dimensions.

* Include accessible alternative text where applicable.

* Not break card layout.



---



# 28. Access Badge



Each card may display an access badge.



Example:



```text

FREE

```



```text

LOGIN REQUIRED

```



```text

MEMBERSHIP REQUIRED

```



The visual treatment should follow the active theme.



---



# 29. Open Tool Action



Each card should provide a clear action:



```text

Open Tool

```



The action should navigate to the canonical tool page.



---



# 30. Card Click Behavior



The entire card may be clickable if the active theme's interaction pattern supports it.



However, the primary destination must remain the canonical tool URL.



---



# 31. Tool URL



Conceptually:



```text

/tools/{tool-slug}

```



The actual route follows Favorite CMS routing conventions.



---



# 32. Protected Tool Card



Example:



```text

┌───────────────────────────────┐

│ Hash Generator                │

│ Generate secure hashes.       │

│                               │

│ Developer                     │

│ Membership Required            │

│                               │

│ Open Tool                     │

└───────────────────────────────┘

```



The user should not be blocked from discovering it.



---



# 33. Logged-In State



The catalog does not need to rebuild the entire tool list based on login status.



The ACTIVE catalog remains discoverable.



The tool page/access layer determines whether execution is allowed.



---



# 34. Membership State



Active membership does not require a special catalog.



The same tool remains:



```text

Membership Required

```



but the user can use it because their membership satisfies the access requirement.



---



# 35. Unlimited Membership



The UI must not display usage counters for members.



Do not show:



```text

Unlimited: 999 uses remaining

```



or similar artificial counters.



If desired, a simple statement such as:



```text

Membership Required

```



is sufficient.



---



# 36. Catalog Grid



The tool catalog should use a responsive grid.



Conceptually:



```text

Desktop:

\[Card] \[Card] \[Card] \[Card]



Tablet:

\[Card] \[Card] \[Card]



Mobile:

\[Card]

\[Card]

\[Card]

```



Exact column counts follow the active theme and available width.



---



# 37. Desktop Layout



Desktop may use:



* Wide search.

* Category navigation.

* Multi-column tool grid.

* Sorting/filter controls.

* Pagination.



The layout should avoid excessive empty space.



---



# 38. Tablet Layout



Tablet should reduce the number of columns where necessary.



Cards must remain readable and usable.



---



# 39. Mobile Layout



Mobile should prioritize:



* Search.

* Category selection.

* Tool cards.

* Simple filtering.

* Easy navigation.



Avoid forcing desktop-style sidebars onto narrow screens.



---



# 40. Mobile Category Navigation



Categories may use:



* Horizontal scrolling chips.

* Dropdown/select.

* Collapsible filter.

* Theme-supported category navigation.



Choose the approach that best matches the active theme.



---



# 41. Search + Category State



The selected category and search query should remain understandable to the user.



If URL state is supported:



```text

/tools?q=json\&category=developer

```



may represent the current state.



---



# 42. Browser Navigation



If URL state is implemented:



* Back should restore previous search/filter state.

* Forward should restore the next state.

* Refresh should preserve the current state.



---



# 43. Pagination



When the catalog contains enough tools, pagination should be shown.



Example:



```text

Previous   1   2   3   4   Next

```



---



# 44. Pagination UX



Pagination should:



* Clearly show the current page.

* Disable unavailable previous/next actions.

* Preserve search/filter state.

* Work on mobile.



---



# 45. Pagination Is Not a Limit



Pagination does not limit:



* Tool access.

* Tool execution.

* Membership usage.

* User accounts.



It only controls catalog presentation.



---



# 46. Result Loading



During a catalog/search request:



```text

Loading tools...

```



may be shown.



The UI should avoid unnecessary flashing or complete layout shifts where practical.



---



# 47. Loading Skeleton



A card skeleton may be used.



Example:



```text

\[████████]

\[████████████]

\[████████████████]

```



The skeleton must follow the active theme.



---



# 48. Empty Catalog



If no ACTIVE tools exist:



```text

No tools available yet.

```



The UI should provide an appropriate empty state.



---



# 49. No Search Results



If a query returns no results:



```text

No tools found.

```



Possible actions:



```text

Clear Search

Browse All Tools

Browse Categories

```



---



# 50. No Category Results



If a category contains no ACTIVE tools:



```text

No tools found in this category.

```



Provide a clear way to return to all tools.



---



# 51. Error State



If the discovery API fails:



```text

Unable to load tools.

Please try again.

```



The UI should provide a retry action where appropriate.



---



# 52. Network Error



Network failures should not expose internal technical details.



Do not display:



* SQL errors.

* Stack traces.

* Server paths.

* API credentials.

* Internal exception messages.



---



# 53. Search Query Validation



The frontend may perform basic validation such as:



* Empty query handling.

* Excessively long query handling.



The backend remains authoritative.



---



# 54. Search Result Escaping



Tool names, descriptions, category names, and other public metadata must be safely rendered.



Do not blindly inject returned strings as privileged HTML.



---



# 55. Public HTML Metadata



If a tool description contains formatting, the renderer must use the appropriate safe rendering rules.



Do not allow discovery metadata to become an XSS vector.



---



# 56. Theme Integration



The catalog UI must be rendered inside the active CMS theme.



Required:



```text

Theme Header

     ↓

Web Tools Catalog

     ↓

Theme Footer

```



No independent Web Tools website shell is allowed.



---



# 57. Theme Switching



If the administrator/user switches the active CMS theme, the catalog must continue working with the new theme.



The plugin must not depend on one specific theme's markup.



---



# 58. Dark/Light Mode



The catalog must follow the active theme's dark/light mode.



If the theme uses:



```css

body.dark

```



the Web Tools catalog must respond accordingly.



---



# 59. No Plugin Theme Toggle



Favorite Web Tools must not introduce its own global light/dark toggle.



The active CMS theme remains the source of truth.



---



# 60. Theme CSS



Plugin CSS should be scoped to the Web Tools UI.



Avoid:



```css

body { ... }

a { ... }

button { ... }

input { ... }

```



global overrides unless explicitly required by the CMS/theme architecture.



Prefer a plugin namespace/container.



---



# 61. Theme Variables



Where available, reuse existing theme variables for:



* Background.

* Surface.

* Text.

* Muted text.

* Border.

* Accent.

* Buttons.

* Inputs.

* Focus state.



---



# 62. Card Theme Behavior



Cards must remain readable in both:



```text

Light Mode

Dark Mode

```



without creating a second theme system.



---



# 63. Accessibility



The catalog must support:



* Keyboard navigation.

* Semantic links/buttons.

* Visible focus states.

* Accessible search labels.

* Accessible filter controls.

* Accessible pagination.

* Appropriate image alt text.

* Screen-reader-friendly loading/error states.



---



# 64. Keyboard Search



Users should be able to:



```text

Tab → Search

Enter → Submit

Tab → Filters

Tab → Tool

Enter → Open

```



without requiring mouse interaction.



---



# 65. Tool Card Accessibility



If the entire card is clickable, avoid nested interactive elements that create invalid or confusing interaction patterns.



Use semantic HTML according to the actual UI structure.



---



# 66. Performance



The catalog should avoid unnecessarily loading:



* Large images.

* Tool execution code.

* Python service information.

* Tool-specific assets.



The catalog only needs discovery metadata.



---



# 67. Lazy Loading



Images and non-critical visual assets may use lazy loading where appropriate.



Tool execution assets should load only on the tool page when needed.



---



# 68. API Payload



The frontend should request only the catalog data required for the current view.



Avoid returning the full configuration of every tool.



---



# 69. Large Catalog



The architecture should remain practical for:



```text

10 tools

50 tools

100 tools

500+ tools

```



The frontend should not download hundreds of complete tool configurations merely to display cards.



---



# 70. Search Result Rendering



The frontend should use a reusable result/card renderer rather than duplicating card markup across:



* Search results.

* Category pages.

* All Tools page.

* Related tools.



---



# 71. Category Result Rendering



Category pages should use the same Tool Card component where possible.



---



# 72. Related Tool Rendering



Related tools should also reuse the same card system.



---



# 73. Sorting UI



If sorting is enabled, the UI may provide:



```text

Sort by

Name

Newest

Updated

Display Order

```



Only options supported by the backend should be shown.



---



# 74. Default Sorting



The default ordering should follow the discovery API.



The frontend must not independently reorder results in a way that contradicts the backend.



---



# 75. Search State Preservation



When users open a tool and return to the catalog, preserving the previous search/category/page state is desirable where the CMS/browser architecture supports it.



---



# 76. URL-Based State



If query parameters are used, the following may be represented:



```text

q

category

sort

page

per\_page

```



The exact parameter names follow the Discovery API contract.



---



# 77. SEO



The public catalog should integrate with the existing CMS SEO system.



Search result states generally should not create unnecessary indexable duplicate pages.



Canonical/public tool pages remain the primary SEO targets.



---



# 78. Internationalization



All user-facing UI strings should use the CMS's existing translation/i18n mechanism if available.



Do not hard-code language architecture separately inside the plugin.



---



# 79. Bengali/English Compatibility



The UI must support text lengths appropriate for both English and Bengali where the CMS supports multilingual content.



Cards and buttons should not assume fixed English-only widths.



---



# 80. No Hard-Coded Tools



Do not create frontend code such as:



```javascript

const tools = \[

    "JSON Formatter",

    "Base64 Encoder",

    "HTML Minifier"

];

```



The catalog must come from the Discovery/Registry system.



---



# 81. No Hard-Coded Categories



Do not duplicate category lists across JavaScript, PHP, HTML, and CSS.



Use the central category data source.



---



# 82. Public Metadata Boundary



Catalog UI receives public metadata only.



The frontend must never receive:



```text

API key

Bearer token

Password

Secret

Internal service credential

Private filesystem path

```



---



# 83. Python Tools



Python-based tools appear in the catalog like any other tool.



The catalog should not expose:



* Python server address.

* Python credentials.

* Internal endpoint details.



---



# 84. PHP Tools



PHP tools also appear normally.



The catalog should not expose internal handler implementation.



---



# 85. HTML/CSS/JavaScript Tools



HTML, CSS, and JavaScript tools should have the same catalog presentation model.



Engine-specific behavior belongs to the tool page, not the catalog.



---



# 86. Favorite API Connector



The catalog must not become an API Connector UI.



If a tool uses Favorite API Connector internally, the catalog only displays the tool's public metadata.



---



# 87. Membership Integration



The catalog should not implement membership logic independently.



It only displays the configured access mode.



Actual membership verification belongs to Access Control.



---



# 88. Authentication Integration



Login state should come from the existing Favorite CMS authentication system.



Do not create a second login/session system.



---



# 89. Error Recovery



For recoverable discovery failures:



```text

Try Again

```



should retry the existing discovery request.



Do not reload the entire site unnecessarily.



---



# 90. Filter Persistence



Filter persistence may use URL state.



Do not store sensitive authentication or membership data in browser storage.



---



# 91. Browser Storage



The catalog must not store:



* API credentials.

* Membership secrets.

* Session secrets.

* Python service credentials.



---



# 92. Client-Side Security



The frontend must be treated as untrusted.



Users can inspect:



* HTML.

* CSS.

* JavaScript.

* Network requests.



Therefore, security and authorization must remain server-side.



---



# 93. Catalog Cache



Public catalog data may be cached.



Cached catalog data must not include private information.



---



# 94. Access Changes



Changing a tool from:



```text

FREE

```



to:



```text

LOGIN\_REQUIRED

```



or:



```text

MEMBERSHIP\_REQUIRED

```



must not require rebuilding the frontend.



The card should use the current access metadata.



---



# 95. Status Changes



When a tool becomes:



```text

DISABLED

```



it must disappear from normal public catalog results according to the Discovery API.



---



# 96. Activation



When a tool becomes:



```text

ACTIVE

```



it should become discoverable through the normal catalog/search/category mechanisms.



---



# 97. Catalog Consistency



The following must use the same source of truth:



```text

All Tools

Search

Categories

Related Tools

Tool Page

```



Tool identity and public metadata must not diverge.



---



# 98. Extension Compatibility



The catalog should be extensible through the Developer API defined in:



`21-TOOL-EXTENSION-AND-DEVELOPER-API.md`



Possible future extensions:



* Custom card metadata.

* Custom badges.

* Custom filters.

* Custom renderers.

* Tool recommendation relationships.



Extensions must respect:



* ACTIVE visibility.

* Access Control.

* Public metadata boundaries.

* Theme integration.



---



# 99. AI Agent Implementation Rules



The AI agent must:



1. Inspect the actual Favorite CMS frontend/theme architecture.

2. Reuse the existing Header.

3. Reuse the existing Footer.

4. Reuse the active theme's layout.

5. Respect active dark/light mode.

6. Respect `body.dark` if used.

7. Avoid a duplicate theme toggle.

8. Use the Discovery API/Tool Registry.

9. Never hard-code the tool catalog.

10. Never hard-code categories in multiple locations.

11. Display ACTIVE tools only.

12. Keep DRAFT tools hidden.

13. Keep DISABLED tools hidden.

14. Allow protected tools to remain discoverable.

15. Display the correct access label.

16. Keep authorization server-side.

17. Avoid usage counters.

18. Avoid quotas.

19. Avoid credits.

20. Avoid tokens.

21. Avoid popularity tracking.

22. Use responsive layouts.

23. Reuse shared Tool Card rendering.

24. Reuse existing CMS translation/i18n.

25. Reuse existing CMS routing.

26. Reuse existing CMS API/response handling.

27. Avoid unnecessary frontend frameworks.

28. Keep plugin CSS scoped.

29. Keep public metadata safe.

30. Add responsive/accessibility tests.



---



# 100. Required Acceptance Criteria



The implementation is complete when:



* Tool Home/Catalog UI exists.

* Search UI exists.

* Category navigation exists.

* ACTIVE tools appear.

* DRAFT tools are hidden.

* DISABLED tools are hidden.

* Protected tools remain discoverable.

* Access labels are correct.

* Search works.

* Category filtering works.

* Search + category filtering works.

* Sorting works where configured.

* Pagination works where needed.

* Empty states work.

* Error states work.

* Retry works where appropriate.

* Tool cards are reusable.

* Tool URLs are canonical.

* Mobile layout works.

* Tablet layout works.

* Desktop layout works.

* Keyboard navigation works.

* Accessibility requirements are satisfied.

* Bengali/English text lengths do not break the UI.

* Active CMS Header is preserved.

* Active CMS Footer is preserved.

* Active theme switching works.

* Active dark/light mode works.

* `body.dark` is respected where applicable.

* No plugin-level theme toggle is created.

* Plugin CSS does not globally override the CMS theme.

* Public API data contains no secrets.

* Python service details remain private.

* Authentication is reused from CMS.

* Membership is reused from CMS.

* No usage limits are introduced.

* No credits are introduced.

* No tokens are introduced.

* No quotas are introduced.

* No usage counters are introduced.

* No second catalog/database/search engine is created unnecessarily.

* Existing CMS architecture remains unchanged.

* Plugin isolation is preserved.



---



# 101. Final UI Model



```text

                 ACTIVE CMS THEME

                        │

             ┌──────────┴──────────┐

             │                     │

           HEADER                FOOTER

             │

             ▼

      FAVORITE WEB TOOLS

             │

             ▼

          SEARCH

             │

             ▼

      CATEGORY / FILTER

             │

             ▼

        DISCOVERY API

             │

             ▼

       ACTIVE TOOL LIST

             │

       ┌─────┼─────┐

       ▼     ▼     ▼

     CARD   CARD   CARD

       │

       ▼

    TOOL PAGE

       │

       ▼

  ACCESS CONTROL

       │

       ▼

 TOOL EXECUTION API

```



## Final Principle



**The Discovery API decides what tools are publicly available; the Catalog UI decides how users find and view them; the Tool Page handles interaction; Access Control decides who can use them; and the active CMS Theme controls the overall visual experience.**



