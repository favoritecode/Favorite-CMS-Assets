# 23 — TOOL SEARCH AND DISCOVERY API



## 1. Purpose



The **Favorite Web Tools Search and Discovery API** defines how users discover, search, browse, filter, and open tools in `favorite-web-tools`.



The system must support:



* Tool Home.

* Tool search.

* Category browsing.

* Category filtering.

* Tool discovery.

* Search suggestions where appropriate.

* Sorting.

* Pagination.

* Public tool metadata.

* Theme-integrated search UI.

* Mobile and desktop discovery.

* Access-aware UI.

* Future growth to a large tool catalog.



---



# 2. Core Principle



Tool discovery must be centralized around the Tool Registry.



```text

User

 ↓

Search / Category

 ↓

Discovery Layer

 ↓

Tool Registry

 ↓

ACTIVE Tools

 ↓

Tool Page

```



Do not maintain a second hard-coded tool catalog.



---



# 3. Public Discovery Rule



Only tools with:



```text

status = ACTIVE

```



should appear in public discovery.



The following must not appear publicly:



```text

DRAFT

DISABLED

```



unless the existing CMS explicitly provides an authenticated/admin preview mechanism.



---



# 4. Access Does Not Equal Visibility



A protected tool may remain publicly discoverable.



For example:



```text

Tool

Engine: PHP

Access: MEMBERSHIP\_REQUIRED

Status: ACTIVE

```



The tool can appear in search results.



Opening it should show the appropriate access requirement.



---



# 5. Access Modes



Discovery must recognize exactly:



```text

FREE

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



The discovery system must not introduce:



* Premium categories.

* Credits.

* Tokens.

* Usage limits.

* Daily limits.

* Monthly limits.

* Quotas.



---



# 6. Tool Home



Favorite Web Tools should provide a central Tool Home/Tools page.



Conceptually:



```text

Favorite Web Tools

        │

        ├── Search

        ├── Categories

        └── Tool Catalog

```



The exact page route follows Favorite CMS routing conventions.



---



# 7. Tool Search



Users should be able to search tools by relevant public metadata.



Search may include:



* Tool name.

* Tool slug.

* Short description.

* Full description where appropriate.

* Category name.



---



# 8. Search Scope



Search should operate only on public-safe tool information.



Do not search or expose:



* API credentials.

* Internal service URLs.

* Private configuration.

* Internal filesystem paths.

* Admin-only notes.

* Hidden implementation details.



---



# 9. Search Matching



Search should support normal case-insensitive matching according to the database/search capabilities available in Favorite CMS.



Exact implementation should follow the repository.



---



# 10. Search Examples



A user searching:



```text

json

```



may discover:



```text

JSON Formatter

JSON Validator

JSON Beautifier

JSON Minifier

```



A search for:



```text

base64

```



may discover relevant Base64 tools.



---



# 11. Search Result Relevance



Where the existing CMS/database supports relevance ranking, search results may prioritize:



1. Name match.

2. Slug match.

3. Category match.

4. Description match.



The exact ranking must follow the available search architecture.



Do not build a complex search engine unless actually required.



---



# 12. Search API



A public discovery endpoint may conceptually support:



```text

GET /api/tools

```



with optional query parameters.



Possible parameters:



```text

q

category

sort

page

per\_page

```



Exact route and parameter conventions must follow Favorite CMS.



---



# 13. Search Query



Conceptually:



```text

/api/tools?q=json

```



returns matching ACTIVE tools.



The actual API contract must follow the CMS's existing API conventions.



---



# 14. Category Filter



Conceptually:



```text

/api/tools?category=developer

```



returns ACTIVE tools belonging to the requested category.



---



# 15. Combined Search



Search and category filtering may work together.



Example:



```text

/api/tools?q=formatter\&category=developer

```



Only matching ACTIVE tools should be returned.



---



# 16. Pagination



The discovery system should support pagination when the catalog becomes large.



Conceptually:



```text

page=2

per\_page=24

```



Pagination is a presentation/discovery mechanism.



It is not a usage limit.



---



# 17. Pagination Rules



Pagination must not:



* Restrict tool usage.

* Count user usage.

* Create credits.

* Create quotas.

* Require membership.



It only controls how many catalog items are displayed per response/page.



---



# 18. Per-Page Value



The default page size should follow CMS conventions.



If the plugin needs a default, it should use a reasonable configurable presentation value.



The administrator must not interpret this as a user usage limit.



---



# 19. Sorting



Discovery may support sorting such as:



```text

Name A-Z

Name Z-A

Newest

Updated

Display Order

```



Only sorting supported by the actual catalog/data model should be implemented.



---



# 20. No Popularity Requirement



Popularity ranking is not required.



Do not introduce:



* Usage counters.

* Most-used tracking.

* User quotas.

* Usage analytics.



unless separately specified in a future architecture file.



---



# 21. Category Browsing



Users should be able to browse categories independently of search.



Conceptually:



```text

All Tools

HTML

CSS

JavaScript

Developer

Text

PHP

Python

```



The actual category list comes from the Category system.



---



# 22. Category Page



A category page should display:



* Category name.

* Description.

* ACTIVE tools.

* Search/filter controls where appropriate.



DRAFT and DISABLED tools must remain hidden.



---



# 23. Category Metadata



Public category metadata may include:



* Name.

* Slug.

* Description.

* Icon.

* Thumbnail.

* Display order.



Only public-safe fields should be exposed.



---



# 24. Tool Card



A discovery result should use a consistent Tool Card.



Conceptually:



```text

┌─────────────────────────────┐

│ Icon / Thumbnail             │

│                              │

│ JSON Formatter               │

│ Format and beautify JSON     │

│                              │

│ Developer • Free             │

│                              │

│ Open Tool                    │

└─────────────────────────────┘

```



The exact visual design follows the active CMS theme.



---



# 25. Access Indicator



Tool cards may show:



```text

Free

Login Required

Membership Required

```



This helps users understand access before opening the tool.



---



# 26. Access Indicator Is Not Authorization



The card's access label is only UI information.



Actual authorization must happen when the tool is opened/executed.



---



# 27. Theme Integration



The discovery UI must use the active CMS theme.



It must carry:



* Active theme Header.

* Active theme Footer.

* Active theme layout.

* Active theme typography where appropriate.

* Active theme dark/light state.



It must not create an independent site shell.



---



# 28. Dark/Light Search UI



Search components must follow the active theme's mode.



For example:



```text

Theme Light

 ↓

Light search field

Light cards

Light filters



Theme Dark

 ↓

Dark search field

Dark cards

Dark filters

```



If the theme uses `body.dark`, the Web Tools discovery UI must respond to it.



---



# 29. Theme Variables



Where the active theme provides CSS variables/tokens, reuse them for:



* Search field.

* Cards.

* Borders.

* Text.

* Buttons.

* Filters.

* Pagination.

* Empty states.



Do not duplicate the entire theme.



---



# 30. Search Input



The search field should provide:



* Clear label/placeholder.

* Search action.

* Clear action.

* Keyboard accessibility.

* Loading state when needed.

* Error state when needed.



---



# 31. Search Behavior



The implementation may use:



* Search on submit.

* Debounced search.

* Instant filtering.



The choice should follow the expected catalog size and CMS architecture.



Avoid unnecessary requests for every keystroke on large catalogs.



---



# 32. Search Suggestions



Autocomplete/suggestions are optional.



If implemented, suggestions should contain only public ACTIVE tools/categories.



No private metadata may be exposed.



---



# 33. Empty Search



If no query is provided, the Tools Home should display:



* Categories.

* Featured/ordered tools if configured.

* Tool catalog.

* Search interface.



No arbitrary hard-coded catalog should be required.



---



# 34. No Results State



When no tool matches:



```text

No tools found.

```



The UI may provide:



* Clear search.

* Browse categories.

* Return to all tools.



The exact wording follows the site's language/theme conventions.



---



# 35. Error State



Discovery errors should distinguish:



* Network error.

* Server error.

* Invalid filter.

* Invalid category.

* Temporary unavailable state.



Do not expose internal exceptions.



---



# 36. Invalid Category



If an invalid category slug is requested:



* Follow CMS route/error conventions.

* Return a controlled response.

* Do not expose database errors.



---



# 37. Tool Lookup by Slug



A public tool can be resolved through:



```text

GET /api/tools/{tool-slug}

```



where the route architecture supports it.



Only ACTIVE tools should be publicly returned.



---



# 38. Tool Page Navigation



Discovery results should link to the canonical tool page.



Conceptually:



```text

/tools/{tool-slug}

```



The exact route follows the CMS routing system.



---



# 39. Stable Tool Slugs



Tool slugs should remain stable after publication unless intentionally changed.



Changing a slug may affect:



* Search indexing.

* Bookmarks.

* Internal links.

* External links.



Any slug change should follow an intentional migration/redirect strategy where supported.



---



# 40. Category Slugs



Category slugs should also remain stable where possible.



---



# 41. Search Security



Public search parameters are untrusted.



The backend must:



* Validate query parameters.

* Sanitize according to context.

* Use safe database queries.

* Prevent SQL injection.

* Avoid arbitrary query construction.



---



# 42. Search Performance



Search should use appropriate database indexes where useful.



Indexes should be based on actual query patterns.



Do not create unnecessary indexes.



---



# 43. Search and Caching



Public tool metadata may be cached where appropriate.



Caching must not:



* Leak private configuration.

* Expose protected user data.

* Bypass access control.

* Return stale authorization decisions.



---



# 44. Protected Tool Caching



A protected tool may have publicly cached metadata.



However, actual execution authorization must always be evaluated server-side.



---



# 45. Search Result API Contract



A conceptual response may contain:



```json

{

  "success": true,

  "data": {

    "items": \[],

    "pagination": {

      "page": 1,

      "per\_page": 24,

      "total": 0

    }

  }

}

```



The actual response structure must follow Favorite CMS API conventions.



---



# 46. Public Tool Metadata



A discovery response may contain:



```text

id

name

slug

description

category

engine

access\_mode

icon

thumbnail

```



Only fields intended for public discovery should be returned.



---



# 47. Hidden Configuration



The following must never be included in public discovery responses:



* Credentials.

* API keys.

* Bearer tokens.

* Internal service URLs when sensitive.

* Private configuration.

* Internal handler paths.

* Server filesystem paths.

* Secret environment values.



---



# 48. Engine Visibility



The engine may be included as metadata if useful.



Example:



```text

HTML

CSS

JavaScript

PHP

Python API

```



However, exposing engine information must not expose implementation secrets.



---



# 49. Search and Access



Discovery should not attempt to perform full authorization for every search result.



The system should return public catalog metadata.



When the user opens or executes a protected tool, access control is evaluated by the appropriate backend layer.



---



# 50. Anonymous Discovery



Anonymous users may:



* Browse ACTIVE tools.

* Search tools.

* Browse categories.

* Open FREE tools.

* See protected tool access information.



They cannot execute:



```text

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



unless they satisfy the required access.



---



# 51. Logged-In Discovery



Logged-in users may discover the same ACTIVE catalog.



Execution behavior depends on access mode.



---



# 52. Active Member Discovery



Active members may discover the same ACTIVE catalog and can execute tools according to the membership access rule.



Membership remains unlimited.



No usage counter should be shown.



---



# 53. No Usage Indicators



The discovery system must never display:



```text

Used 5/10

Remaining 5

Credits left

Tokens left

Daily limit

Monthly limit

```



These concepts do not exist in the Favorite Web Tools product model.



---



# 54. Related Tools



A tool page may display related tools.



Initial related-tool logic may use:



* Same category.

* Similar public metadata.



Do not require usage analytics.



---



# 55. Related Tool Security



Related tools must contain only:



```text

ACTIVE

```



publicly discoverable tools.



---



# 56. Tool Ordering



If categories/tools have a configured `display\_order`, discovery may use it.



Otherwise, use a predictable default such as name or repository-defined ordering.



---



# 57. Featured Tools



Featured tools are optional.



If implemented, featured status must come from an explicit tool configuration field/system.



Do not infer it from usage.



---



# 58. Search Indexing



If the CMS has an existing search/indexing mechanism, Favorite Web Tools may integrate with it.



Do not build a second indexing engine unless required.



---



# 59. SEO



Public tool pages should use existing CMS SEO facilities where available.



The discovery system should provide appropriate:



* Tool title.

* Description.

* Canonical URL.

* Category relationship.



Exact implementation follows CMS conventions.



---



# 60. Frontend API Loading



Frontend discovery may load results through the plugin's public discovery API or server-rendered CMS mechanism.



The choice should follow existing CMS architecture.



Do not introduce unnecessary client-side frameworks.



---



# 61. Loading UX



While searching/loading:



* Show a clear loading state.

* Prevent confusing duplicate interactions where appropriate.

* Preserve existing results until replacement is ready where practical.



This is UX behavior, not a usage limit.



---



# 62. Responsive Discovery



The discovery system must work on:



* Mobile.

* Tablet.

* Desktop.



Tool cards should adapt to available width.



---



# 63. Mobile Search



On mobile:



* Search input must remain easy to use.

* Filters may collapse where appropriate.

* Cards may use one-column layout.

* Pagination must remain usable.



---



# 64. Desktop Search



On desktop:



* Search may use wider layout.

* Categories may appear as navigation/sidebar.

* Tool cards may use multi-column grids.

* Filters may remain visible where appropriate.



Exact layout follows the active theme.



---



# 65. Accessibility



Search/discovery must support:



* Keyboard navigation.

* Accessible labels.

* Focus states.

* Semantic links.

* Accessible buttons.

* Screen-reader-friendly loading/error states.

* Sufficient contrast.



---



# 66. Browser URL State



Where appropriate, search/filter state may be reflected in the URL.



Example:



```text

/tools?q=json\&category=developer

```



This is optional and must follow CMS routing conventions.



---



# 67. Shareable Search



If URL-based search is implemented, users should be able to refresh/share the search state without losing the selected filters.



---



# 68. Back/Forward Navigation



Search/filter interactions should work naturally with browser navigation when URL state is used.



---



# 69. Admin Discovery Controls



Admin users may have additional filtering:



```text

Engine

Access Mode

Status

Category

```



These are admin management filters and must not leak into public discovery.



---



# 70. Public vs Admin Search



Public discovery:



```text

ACTIVE only

```



Admin management:



```text

DRAFT

ACTIVE

DISABLED

```



The two systems must remain separated.



---



# 71. Tool Count



The Tools Home may show total ACTIVE tool count if useful.



This is catalog metadata, not usage tracking.



---



# 72. Category Count



A category may show the number of ACTIVE tools it contains.



Example:



```text

Developer — 18 tools

```



This is catalog information only.



---



# 73. No Usage Analytics



Do not implement:



* Most used.

* Most executed.

* User usage ranking.

* Tool popularity counters.



unless separately specified.



---



# 74. API Connector Separation



Search/discovery must not become a generic API marketplace.



Favorite API Connector remains separate.



---



# 75. Python Service Separation



Python service details must not appear in public discovery unless intentionally exposed as safe metadata.



Do not expose:



* Base URLs.

* Credentials.

* Internal service configuration.



---



# 76. Extension Compatibility



The discovery system should allow future extensions through the Developer API defined in:



`21-TOOL-EXTENSION-AND-DEVELOPER-API.md`



Possible extension areas:



* Tool metadata.

* Categories.

* Search fields.

* Related tools.

* Display renderers.



Extensions must not bypass public visibility or security rules.



---



# 77. Performance for Large Catalogs



The architecture must remain suitable if the catalog grows from:



```text

10 tools

↓

50 tools

↓

100 tools

↓

500+ tools

```



The implementation should prefer:



* Database filtering.

* Database pagination.

* Indexed lookups.

* Controlled payload sizes.

* Lazy loading where appropriate.



Do not load the entire catalog unnecessarily on every request.



---



# 78. Search Debouncing



If instant client-side search is used, requests should be debounced appropriately.



This reduces unnecessary network traffic.



It is not a user usage limit.



---



# 79. Search Result Consistency



A tool should appear consistently across:



* Tool Home.

* Search.

* Category.

* Related tools.



provided that it is ACTIVE and publicly discoverable.



---



# 80. Activation/Discovery Relationship



When an administrator activates a tool:



```text

DRAFT

 ↓

Validation

 ↓

ACTIVE

 ↓

Public Discovery

```



When disabled:



```text

ACTIVE

 ↓

DISABLED

 ↓

Removed from Public Discovery

```



---



# 81. Search Cache Invalidation



If caching is used, changes such as:



* Tool activation.

* Tool disable.

* Tool update.

* Category update.



should invalidate or refresh relevant public discovery data according to the CMS caching mechanism.



---



# 82. API Error Handling



The discovery API must return controlled errors using existing CMS response conventions.



Do not expose:



* SQL errors.

* Stack traces.

* Internal exception details.

* Credentials.

* Filesystem paths.



---



# 83. AI Agent Implementation Rules



The AI agent must:



1. Inspect the actual CMS search/routing/database conventions.

2. Reuse the existing CMS API response format.

3. Reuse the existing database layer.

4. Use the central Tool Registry.

5. Query ACTIVE tools for public discovery.

6. Keep DRAFT tools hidden.

7. Keep DISABLED tools hidden.

8. Keep protected tools discoverable when ACTIVE.

9. Enforce authorization during tool access/execution.

10. Keep access modes exactly FREE/LOGIN\_REQUIRED/MEMBERSHIP\_REQUIRED.

11. Never introduce quotas.

12. Never introduce credits.

13. Never introduce tokens.

14. Never introduce usage counters.

15. Never create a popularity system.

16. Reuse existing CMS search where practical.

17. Use safe database queries.

18. Keep public metadata safe.

19. Respect active theme Header/Footer.

20. Respect active theme dark/light mode.

21. Keep discovery responsive.

22. Keep HTML/CSS/JS previews isolated.

23. Keep API Connector separate.

24. Keep Python Service details private.

25. Avoid a duplicate search engine unless required.

26. Avoid a duplicate router.

27. Avoid a duplicate API framework.

28. Add tests for search/filter/category/pagination.

29. Add tests for public visibility.

30. Add tests for protected tools.



---



# 84. Required Acceptance Criteria



The implementation is acceptable only when:



* Tool Home exists through the CMS/theme architecture.

* ACTIVE tools are publicly discoverable.

* DRAFT tools are hidden.

* DISABLED tools are hidden.

* Search works by relevant public metadata.

* Category filtering works.

* Search + category filtering works.

* Pagination works where required.

* Sorting works where configured.

* Tool lookup by slug works.

* Public metadata contains no secrets.

* Protected tools can remain discoverable.

* Access labels are shown appropriately.

* Actual access is enforced separately.

* No usage limits exist.

* No credits exist.

* No tokens exist.

* No quotas exist.

* No usage counters exist.

* No popularity tracking is required.

* Search uses safe database queries.

* Discovery scales to a large catalog.

* Search UI is responsive.

* Category UI is responsive.

* Tool cards are responsive.

* Active theme Header is preserved.

* Active theme Footer is preserved.

* Active theme dark/light state is respected.

* `body.dark` is respected if used by the theme.

* No duplicate global theme system is introduced.

* Search/discovery UI follows theme styling.

* Accessibility requirements are satisfied.

* API errors are controlled.

* Public and admin discovery remain separated.

* Existing CMS routing/API/database/search mechanisms are reused.

* Integration tests pass.



---



# 85. Final Search and Discovery Model



```text

                    FAVORITE WEB TOOLS

                            │

                            ▼

                       TOOL HOME

                            │

             ┌──────────────┼──────────────┐

             │              │              │

          Search         Category       All Tools

             │              │              │

             └──────────────┼──────────────┘

                            │

                            ▼

                     TOOL REGISTRY

                            │

                            ▼

                       ACTIVE TOOLS

                            │

                ┌───────────┼───────────┐

                │           │           │

              FREE       LOGIN       MEMBERSHIP

                │        REQUIRED      REQUIRED

                │           │           │

                └───────────┼───────────┘

                            │

                            ▼

                       TOOL PAGE

                            │

                            ▼

                       EXECUTION API

```



Frontend shell:



```text

ACTIVE CMS THEME

        │

        ├── HEADER

        │

        ├── TOOL SEARCH / DISCOVERY

        │

        ├── TOOL CONTENT

        │

        └── FOOTER

```



Theme mode:



```text

ACTIVE THEME

     │

     ├── LIGHT → Web Tools Light UI

     │

     └── DARK  → Web Tools Dark UI

```



The final principle is:



**Tool Registry controls what exists, Discovery controls how users find it, Access Control controls who can use it, and the Active CMS Theme controls how the entire experience looks.**



