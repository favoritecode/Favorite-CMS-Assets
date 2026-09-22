# Favorite Web Tools — Tool Category \& Discovery System



## 1. Purpose



This document defines the Category and Tool Discovery System for the `favorite-web-tools` plugin.



The system is responsible for:



* tool categories

* category management

* tool discovery

* tool listing

* search

* filtering

* sorting

* category pages

* tool home page

* public tool navigation



The system must work with the Tool Registry defined in:



```text

05-TOOL-REGISTRY-AND-DATA-MODEL.md

```



and the frontend structure defined in:



```text

09-FRONTEND-TOOL-UI.md

```



---



## 2. Core Principle



Categories organize tools.



Categories do not control:



* authentication

* membership

* execution permissions

* usage limits

* credits

* pricing

* quotas



Access control remains completely independent from category.



Conceptually:



```text

Tool

 ├── Category

 ├── Engine

 ├── Access Mode

 └── Status

```



A category must never automatically make a tool:



* free

* login-required

* membership-required



---



## 3. Initial Categories



The initial system should support categories such as:



```text

HTML

CSS

JavaScript

Developer

Text

PHP

Python

```



These are initial examples, not permanent hard-coded categories.



Administrators should be able to create additional categories.



---



## 4. Category Identity



Each category should have:



* ID

* name

* slug

* description

* icon where supported

* thumbnail/image where supported

* display/order information where appropriate

* status where supported

* created timestamp

* updated timestamp



The exact database structure must follow Favorite CMS conventions.



---



## 5. Category Slug



Each category should have a unique URL-safe slug.



Examples:



```text

html

css

javascript

developer

text

php

python

```



The slug should remain stable unless an administrator explicitly changes it.



If changing a slug would break existing links, the implementation should follow the existing CMS redirect/slug conventions.



---



## 6. Category Relationship



A tool may belong to one primary category in the initial implementation.



Conceptually:



```text

Tool

  ↓

Category

```



If the actual repository architecture strongly supports many-to-many taxonomy relationships, the system may support multiple categories later.



Do not introduce many-to-many complexity unless it is actually required.



---



## 7. Category Management



The Admin Panel should allow administrators to:



* create categories

* edit categories

* reorder categories where supported

* disable categories where supported

* delete categories where safe



Category deletion must not silently orphan tools.



Before deletion, the system should identify tools using the category.



The administrator should either:



* reassign those tools

* or follow an existing safe deletion policy



---



## 8. Category Display Order



Categories may have a configurable display order.



Example:



```text

1\. HTML

2\. CSS

3\. JavaScript

4\. Developer

5\. Text

6\. PHP

7\. Python

```



The exact ordering implementation should follow the repository's existing admin/data conventions.



If no ordering system exists, a simple deterministic ordering may be used.



---



## 9. Public Category Listing



The public Tool Home should display available categories.



Only categories that have public-visible active tools should normally be displayed.



Draft or disabled tools must not appear as public tools.



Empty categories may be hidden from the public interface unless the design explicitly requires showing them.



---



## 10. Category Page



Conceptually:



```text

/tools/category/{category-slug}

```



The exact URL must follow the existing Favorite CMS routing conventions.



A category page should contain:



* category name

* category description

* available tools

* search/filter capability where appropriate

* tool cards



---



## 11. Tool Home Page



Conceptually:



```text

/tools

```



The exact route must follow the existing CMS architecture.



The Tool Home should provide:



* plugin title

* short description

* tool search

* category navigation

* tool listing

* appropriate access indicators



---



## 12. Tool Cards



A public tool card may display:



* tool name

* short description

* category

* icon

* thumbnail

* access mode indicator

* open/use action



Example:



```text

JSON Formatter

Format and beautify JSON instantly.



Developer

Free



\[Open Tool]

```



The card should not expose internal implementation details.



---



## 13. Access Indicators



Public tool discovery may show:



```text

Free

Login Required

Membership Required

```



These labels are informational UI.



They do not replace server-side authorization.



---



## 14. Search



The Tool Home should support searching tools by relevant public metadata.



Search may include:



* tool name

* slug

* short description

* full description

* category name



Search should return only tools that are publicly discoverable.



---



## 15. Search Behavior



Search should be:



* case-insensitive where appropriate

* tolerant of normal user input

* reasonably fast

* deterministic



The initial implementation does not require advanced AI/semantic search.



A conventional database/text search is sufficient unless the existing CMS already provides a suitable search mechanism.



---



## 16. Search Results



Search results should display the same basic Tool Card structure.



Example:



```text

Search: JSON



Results:

\- JSON Formatter

\- JSON Validator

\- JSON Beautifier

\- JSON Minifier

```



Only active/public tools should appear.



---



## 17. Search Empty State



If no tools match the search query, display a clear empty state.



Example:



```text

No tools found.

Try another search term.

```



The exact wording can be adjusted during UI implementation.



---



## 18. Search Security



Search input must be treated as untrusted user input.



The backend must use the CMS's existing query parameterization/database safety mechanisms.



Do not construct unsafe SQL queries from raw search strings.



---



## 19. Category Filtering



The Tool Home may allow filtering by category.



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



Selecting a category should show only matching active tools.



---



## 20. Engine Filtering



Engine filtering may be supported in the Admin Panel.



Example:



```text

HTML

CSS

JavaScript

PHP

Python API

```



Public users do not necessarily need engine filtering because engine type is an implementation detail.



---



## 21. Admin Tool Filtering



The Admin Tool list should support filtering by:



* category

* engine

* access mode

* status



This makes tool management easier as the number of tools grows.



---



## 22. Access Filtering



Admin users may filter tools by:



```text

FREE

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



This is an administrative discovery feature only.



It does not modify access.



---



## 23. Status Filtering



Admin users may filter by:



```text

ACTIVE

DRAFT

DISABLED

```



Public discovery must not expose DRAFT or DISABLED tools unless an explicit admin preview mode is being used.



---



## 24. Sorting



Public tool listings may use a deterministic default order.



Possible ordering:



* configured display order

* category order + tool order

* alphabetical order



The exact default should follow the product/UI decision made during implementation.



Admin lists may additionally sort by:



* name

* updated date

* status

* category



Do not introduce unnecessary complex sorting systems.



---



## 25. Pagination



Pagination may be introduced if the number of tools becomes large.



The initial implementation may use pagination or a reasonable tool count threshold according to the actual repository/UI architecture.



Pagination is a discovery/UI concern.



It must not be used as a usage-limit mechanism.



---



## 26. No Usage Restrictions



Category and discovery must never introduce:



* tool access limits

* search limits

* category limits

* daily limits

* monthly limits

* credits

* tokens

* quotas



Users with access to a tool can use it without a usage counter.



---



## 27. Disabled Tools



Disabled tools must be excluded from normal public discovery.



They may remain visible in the Admin Panel.



Administrators should be able to identify why a tool is unavailable through its configuration/status.



---



## 28. Draft Tools



Draft tools must not appear in normal public:



* search results

* category pages

* tool home listings



They may be available through Admin Panel testing/preview.



---



## 29. Category and Access Independence



Example:



```text

Developer

 ├── JSON Formatter → FREE

 ├── JSON Validator → LOGIN\_REQUIRED

 └── Advanced Analyzer → MEMBERSHIP\_REQUIRED

```



All three tools can exist in the same category.



The category must not change their access behavior.



---



## 30. Category and Engine Independence



Example:



```text

Developer

 ├── Tool A → JavaScript

 ├── Tool B → PHP

 └── Tool C → Python API

```



Category assignment must not determine the engine.



---



## 31. Tool Search by Slug



The system should support exact lookup by slug.



Example:



```text

json-formatter

```



This should resolve directly to the corresponding Tool Registry record.



Slug lookup is the preferred mechanism for public tool URLs.



---



## 32. Tool Search by ID



Internal/admin operations may use the tool ID.



The public frontend should generally use the stable slug.



Internal IDs should not be unnecessarily exposed to public users.



---



## 33. Category Search by Slug



Category pages should resolve the category through its slug.



Example:



```text

/tools/category/developer

```



The exact URL is conceptual only.



The actual route must follow Favorite CMS conventions.



---



## 34. Public Discovery Rules



A tool is publicly discoverable only when:



```text

Tool exists

AND

Tool status = ACTIVE

```



Additional frontend visibility rules may apply based on existing CMS conventions.



Access mode does not determine discoverability.



For example, a `MEMBERSHIP\_REQUIRED` tool may be publicly listed while its execution remains protected.



---



## 35. Protected Tool Discovery



A membership-protected tool may appear publicly with:



```text

Membership Required

```



Users may open its tool page.



The execution API will enforce the membership requirement.



The system must not hide all protected tools simply because the visitor is not currently authorized.



This allows users to discover available tools and understand their access requirements.



---



## 36. Anonymous User Discovery



Anonymous users should be able to discover:



* public tools

* categories

* descriptions

* access requirements



For `FREE` tools, they may execute directly.



For protected tools, the UI should guide them toward login/membership as appropriate.



---



## 37. Logged-In User Discovery



Logged-in users should see the same general tool catalog.



Their access state may affect:



* action buttons

* access messages

* login/membership prompts



but should not arbitrarily remove tools from discovery.



---



## 38. Membership User Discovery



Active members should be able to discover all active tools.



For membership-required tools, the action should allow normal execution.



Active membership means unlimited use.



---



## 39. SEO Metadata



Tool and category pages may expose SEO metadata through the existing Favorite CMS SEO/page mechanisms.



Possible metadata:



* title

* description

* canonical URL

* social preview metadata where supported



Do not create a separate SEO framework.



---



## 40. Search Engine Visibility



The SEO behavior of:



* Tool Home

* Category Pages

* Individual Tool Pages



should follow the site's existing CMS SEO architecture.



Draft/disabled tools should not normally be publicly indexed.



The exact robots/indexing behavior should be implemented according to existing CMS conventions.



---



## 41. Navigation



The frontend should provide logical navigation:



```text

Tools

 ↓

Category

 ↓

Tool

```



and:



```text

Tool

 ↓

Category

 ↓

Other tools

```



Users should be able to return from a tool page to its category or the main tools page.



---



## 42. Breadcrumbs



Where consistent with the site's existing UI, tool pages may use breadcrumbs:



```text

Tools

 → Developer

 → JSON Formatter

```



Breadcrumb implementation should reuse existing CMS/theme components where possible.



---



## 43. Related Tools



A tool page may display related tools.



Related tools may be selected using:



* same category

* configured relationship

* similar public metadata



The initial implementation can use same-category tools.



Do not introduce AI-based recommendation logic unless specifically required.



---



## 44. Category Description



A category may contain a short public description.



Example:



```text

Developer Tools

Useful tools for coding, formatting, validation, encoding and development workflows.

```



Descriptions should be stored as content/configuration, not hard-coded into frontend templates.



---



## 45. Tool Description Search



Search may use the tool's public description.



Internal/private configuration must never become searchable public metadata.



---



## 46. Search Performance



The implementation should use appropriate indexes/query patterns supported by the actual database architecture.



As the tool catalog grows, search should remain efficient.



Do not introduce an external search engine for the initial implementation unless the repository already provides one or the project actually requires it.



---



## 47. Category Cache



Caching may be used if the Favorite CMS already provides a suitable caching system.



Caching must not cause stale authorization decisions.



Category/tool discovery caching may cache public metadata.



Access authorization must always be evaluated independently during execution.



---



## 48. Admin Category UI



The Admin Panel should provide:



```text

Categories

 ├── List

 ├── Add

 ├── Edit

 ├── Reorder

 └── Delete/Disable

```



The exact controls depend on the existing CMS admin architecture.



---



## 49. Admin Tool Assignment



When creating/editing a tool, the administrator should be able to select its category.



The selected category must be validated against an existing category.



An invalid/deleted category must prevent activation where the category is required.



---



## 50. Category Integrity



The system should prevent inconsistent states such as:



```text

Tool → nonexistent category

```



If a category is removed, the system must handle dependent tools safely.



Possible strategies:



* require reassignment before deletion

* soft-disable the category

* follow existing CMS foreign-key behavior



The final strategy must follow the actual database architecture.



---



## 51. No Hard-Coded Tool Catalog



The public Tool Home must not depend on a manually maintained HTML list of tools.



Tools should be discovered from the Tool Registry.



This allows new tools to become discoverable without rewriting the main Tools page.



---



## 52. No Hard-Coded Category Catalog



Categories should come from the Category system.



Initial categories may be seeded/configured during plugin setup, but the public UI should not require manually editing frontend templates to add categories.



---



## 53. Tool Discovery API



Where the frontend requires server-provided discovery data, the plugin may expose a read-only discovery endpoint using the existing Favorite CMS API architecture.



Conceptually:



```text

GET /api/tools

GET /api/tools/categories

GET /api/tools/{slug}

```



Exact routes must follow actual CMS routing conventions.



These endpoints should expose only safe public metadata.



---



## 54. Public Tool Metadata



Public discovery responses may include:



* name

* slug

* description

* category

* icon

* thumbnail

* access mode

* public input metadata where needed

* public output metadata where needed



Do not expose:



* database credentials

* Python API credentials

* internal filesystem paths

* private service configuration

* secret keys

* internal implementation details



---



## 55. Admin vs Public Discovery



The system must maintain two logical views:



```text

Public Discovery

```



and:



```text

Admin Discovery

```



Public discovery:



* ACTIVE tools only

* public metadata only



Admin discovery:



* ACTIVE

* DRAFT

* DISABLED

* configuration metadata appropriate for administrators



Admin-only information must not leak through public endpoints.



---



## 56. Empty Catalog



If no active tools exist, the public Tool Home should show an appropriate empty state rather than an application error.



Example:



```text

No tools are currently available.

```



---



## 57. Missing Category



If a requested category does not exist, return the CMS/plugin's standard not-found behavior.



Do not silently display unrelated tools.



---



## 58. Missing Tool



If a requested tool slug does not exist, use the standard CMS/plugin 404 behavior.



Do not attempt to dynamically interpret unknown slugs.



---



## 59. Repository Compatibility



Before implementation, the AI agent must inspect the actual Favorite CMS repository for:



* existing taxonomy/category systems

* slug utilities

* search/query helpers

* pagination

* sorting

* public page routing

* SEO mechanisms

* breadcrumbs/navigation components

* caching

* admin category interfaces

* existing API listing conventions



If an existing mechanism can satisfy the requirement, reuse it.



Do not create duplicate category, search, routing, or SEO systems.



---



## 60. Filesystem Isolation



Implementation must remain within:



```text

Favorite-CMS-Universal/plugins/favorite-web-tools/

```



and:



```text

Favorite-CMS-Assets/plugin-assets/favorite-web-tools/

```



Do not modify CMS core files or unrelated plugins.



---



## 61. Implementation Rules for AI Agent



The AI agent must:



1. Read this specification before implementation.

2. Inspect the actual repository first.

3. Reuse existing category/taxonomy functionality where available.

4. Reuse existing search/query mechanisms where suitable.

5. Reuse existing routing conventions.

6. Reuse existing SEO/navigation components.

7. Implement dynamic tool discovery from the Tool Registry.

8. Implement dynamic category discovery.

9. Keep category independent from access control.

10. Keep category independent from engine selection.

11. Exclude DRAFT and DISABLED tools from public discovery.

12. Allow protected tools to remain publicly discoverable.

13. Keep internal configuration private.

14. Avoid hard-coded public tool lists.

15. Avoid hard-coded category lists.

16. Avoid unnecessary external search infrastructure.

17. Do not introduce usage limits or quotas.

18. Keep the implementation isolated inside the plugin.

19. Test search and category filtering.

20. Test anonymous, logged-in, and active-member discovery.

21. Test missing tools/categories.

22. Test draft/disabled visibility.

23. Test category deletion/reassignment behavior.

24. Report repository conflicts before changing architecture.



---



## 62. Acceptance Criteria



This specification is complete when:



* Categories can be created and managed.

* Categories have unique slugs.

* Tools can be assigned to categories.

* Categories remain independent from access control.

* Categories remain independent from engines.

* Tool Home dynamically discovers active tools.

* Category pages dynamically discover active tools.

* Public search works against public tool metadata.

* Category filtering works.

* Admin filtering works by category, engine, access, and status.

* DRAFT tools are excluded from public discovery.

* DISABLED tools are excluded from public discovery.

* Protected tools can remain publicly discoverable.

* Anonymous users can discover FREE and protected tools.

* Logged-in users can discover the same active catalog.

* Active members can discover all active tools.

* Search does not introduce usage limits.

* Pagination, if implemented, is not treated as a usage limit.

* Public endpoints expose only safe metadata.

* Internal secrets/configuration are never exposed.

* Tool and category slugs work with the existing CMS routing system.

* SEO follows the existing CMS architecture.

* Navigation follows the existing CMS/theme architecture.

* No duplicate category/search/routing/SEO framework is created.

* Implementation remains isolated inside `favorite-web-tools`.



