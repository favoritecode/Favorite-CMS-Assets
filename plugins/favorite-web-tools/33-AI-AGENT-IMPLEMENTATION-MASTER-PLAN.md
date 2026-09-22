# 33 — AI AGENT IMPLEMENTATION MASTER PLAN



## 1. Document Purpose



This document is the final implementation master plan for the `favorite-web-tools` plugin.



It consolidates the requirements defined in:



```text

01-PROJECT-OVERVIEW.md

02-ARCHITECTURE.md

03-TOOL-SYSTEM.md

04-ACCESS-CONTROL.md

05-TOOL-REGISTRY-AND-DATA-MODEL.md

06-ENGINE-SYSTEM.md

07-PYTHON-API-SERVICE.md

08-ADMIN-PANEL.md

09-FRONTEND-TOOL-UI.md

10-TOOL-EXECUTION-API.md

11-TOOL-INPUT-OUTPUT-SYSTEM.md

12-TOOL-CATEGORY-DISCOVERY.md

13-DATABASE-MIGRATION-SYSTEM.md

14-TOOL-CONFIGURATION-SYSTEM.md

15-TOOL-LIFECYCLE-AND-VALIDATION.md

16-TOOL-SECURITY-AND-SANDBOX.md

17-TOOL-FRONTEND-RENDERING-AND-PREVIEW.md

18-TOOL-API-AND-ROUTING.md

19-TOOL-ADMIN-UI-AND-MANAGEMENT.md

20-TOOL-PLUGIN-INTEGRATION-AND-HOOKS.md

21-TOOL-EXTENSION-AND-DEVELOPER-API.md

22-TOOL-THEME-INTEGRATION-AND-RESPONSIVE-UI.md

23-TOOL-SEARCH-AND-DISCOVERY-API.md

24-TOOL-SEARCH-UI-AND-CATALOG.md

25-TOOL-ADMIN-FORMS-AND-DYNAMIC-BUILDER.md

26-TOOL-ENGINE-IMPLEMENTATION-SPECS.md

27-PYTHON-SERVICE-AND-API-INTEGRATION.md

28-TOOL-EXECUTION-AND-RESULT-HANDLING.md

29-TOOL-FRONTEND-PAGE-IMPLEMENTATION.md

30-DEFAULT-TOOL-CATALOG.md

31-TESTING-AND-QUALITY-ASSURANCE.md

32-INSTALLATION-MIGRATION-AND-DEPLOYMENT.md

```



These documents together form the implementation specification.



If any implementation decision conflicts with these documents, the more specific requirement takes precedence, and unresolved conflicts must be investigated against the actual Favorite CMS repository before coding.



---



# 2. Project Identity



```text

Plugin Name:

Favorite Web Tools



Slug:

favorite-web-tools



Platform:

Favorite CMS

```



The plugin is a modular web-tool platform inside Favorite CMS.



---



# 3. Primary Objective



Build a reusable platform where administrators can create, configure, test, activate, disable, and manage many web tools without creating separate hard-coded pages and implementations for every tool.



The platform must support:



```text

HTML

CSS

JavaScript

PHP

Python API

```



---



# 4. Critical Architecture Rule



**Inspect the repository before writing implementation code.**



The AI agent must first inspect:



```text

Plugin architecture

Plugin loader

Plugin lifecycle

Routing

Controllers

Services

Database

Migrations

Models/repositories

Authentication

Membership

Admin framework

Themes

Assets

Configuration

Request/response helpers

Error handling

Storage

Testing

```



Do not assume that conceptual names in these specifications exactly match repository class/function names.



Use the actual repository architecture.



---



# 5. CMS Ownership



Favorite CMS remains authoritative for:



* Plugin lifecycle.

* Authentication.

* Sessions.

* Users.

* Membership.

* Permissions.

* Routing.

* CSRF.

* Database.

* Migrations.

* Configuration.

* Storage.

* Themes.

* Header.

* Footer.

* Asset loading.

* Existing logging.

* Existing testing infrastructure.



Favorite Web Tools must integrate with these systems rather than replacing them.



---



# 6. Plugin Ownership



Favorite Web Tools owns:



* Tool Registry.

* Tool metadata.

* Tool categories.

* Tool configuration.

* Tool lifecycle.

* Tool execution orchestration.

* Tool-specific engines.

* Input/output definitions.

* Tool frontend components.

* Tool admin UI.

* Python service integration.

* Tool discovery/catalog.

* Plugin-owned database schema.

* Plugin-owned assets.



---



# 7. Strict Isolation



Do not modify unrelated CMS functionality.



Do not create:



```text

Second authentication system

Second membership system

Second router

Second database abstraction

Second migration system

Second admin framework

Second configuration framework

Second theme system

Second API connector

```



---



# 8. Access Control



Exactly three access modes are allowed:



```text

FREE

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



### FREE



Anonymous users can use the tool.



Logged-in users can also use it.



Membership is not required.



### LOGIN\_REQUIRED



User must be authenticated.



Membership is irrelevant.



### MEMBERSHIP\_REQUIRED



User must be authenticated and have an active membership.



---



# 9. Membership Rule



An active membership means:



**Unlimited use.**



There are no:



```text

Daily limits

Monthly limits

Hourly limits

Usage quotas

Credits

Tokens

Remaining uses

Usage counters

```



Do not implement them.



---



# 10. Access Matrix



| User                         | FREE  | LOGIN\_REQUIRED | MEMBERSHIP\_REQUIRED |

| ---------------------------- | ----- | -------------- | ------------------- |

| Anonymous                    | Allow | Deny           | Deny                |

| Logged-in, no membership     | Allow | Allow          | Deny                |

| Logged-in, active membership | Allow | Allow          | Allow               |



Server-side authorization is authoritative.



---



# 11. Tool Status



Tools have exactly these lifecycle states:



```text

DRAFT

ACTIVE

DISABLED

```



Only `ACTIVE` tools are publicly discoverable and executable.



DRAFT tools may be tested by authorized administrators where supported.



DISABLED tools are not publicly executable.



---



# 12. Tool Lifecycle



Implementation must follow:



```text

CREATE

 ↓

DRAFT

 ↓

CONFIGURE

 ↓

VALIDATE

 ↓

TEST

 ↓

ACTIVATE

 ↓

ACTIVE

 ↓

UPDATE / TEST

 ↓

DISABLE

 ↓

DELETE

```



Invalid configuration must not become ACTIVE.



---



# 13. Tool Registry



Implement one central Tool Registry.



It must provide the authoritative tool metadata/configuration used by:



```text

Admin

Frontend

Search

Discovery

Execution

Lifecycle

Engine Resolver

```



Do not maintain separate tool catalogs for different parts of the plugin.



---



# 14. Tool Identity



Each tool requires:



```text

id

name

slug

description

category

engine

access\_mode

status

configuration

```



Additional display metadata may include:



```text

icon

thumbnail

help

SEO title

SEO description

display order

```



Only fields actually needed by the repository should be implemented.



---



# 15. Tool Categories



Initial categories:



```text

HTML

CSS

JavaScript

Developer

Text

PHP

Python

```



Categories organize tools only.



They do not determine:



* Access.

* Membership.

* Pricing.

* Usage limits.



---



# 16. Supported Engines



Exactly these initial engines:



```text

HTML

CSS

JAVASCRIPT

PHP

PYTHON\_API

```



The architecture must allow future engines without rewriting the whole platform.



---



# 17. HTML Engine



Initial capabilities may include:



```text

Formatter

Minifier

Validator

Encoder

Decoder

Preview

```



HTML preview must be isolated from the main CMS application.



---



# 18. CSS Engine



Initial capabilities may include:



```text

Formatter

Minifier

Validator

Prefixer

Color Converter

Preview

```



CSS previews must not modify the CMS page's own styling.



---



# 19. JavaScript Engine



Initial capabilities:



```text

Formatter

Minifier

Validator

```



Processing JavaScript source is different from executing JavaScript.



Do not automatically execute arbitrary user-provided JavaScript.



If execution is intentionally implemented later, it must be isolated and explicitly designed for that purpose.



---



# 20. PHP Engine



PHP tools must use controlled server-side handlers.



Never implement:



```text

eval()

Arbitrary PHP source execution

Shell execution

CMD execution

PowerShell execution

system command execution

```



A PHP tool configuration must reference an approved handler rather than allowing administrators/users to submit arbitrary executable PHP code.



---



# 21. Python API Engine



Python processing occurs through an external API/service.



Python may run:



```text

Same server

Private server

Separate server

Cloud service

```



but the CMS plugin communicates through the configured API boundary.



Do not execute arbitrary Python source inside the CMS.



---



# 22. Python Service Registry



Implement a centralized Python Service Registry where required by the actual repository architecture.



Conceptual fields:



```text

id

name

base\_url

authentication\_type

credential\_reference

default\_timeout

status

created\_at

updated\_at

```



Credentials remain server-side.



---



# 23. Python Service Security



The public frontend must never be able to override:



```text

Base URL

Endpoint

Credentials

Authentication headers

Internal service configuration

```



The browser communicates with Favorite Web Tools, not directly with private Python services by default.



---



# 24. Favorite API Connector Boundary



Favorite API Connector is a separate plugin/system.



Favorite Web Tools must not duplicate its functionality.



When integration is needed:



```text

Favorite Web Tools

        ↓

Supported API Connector interface

        ↓

External API

```



Do not build a second generic API connector inside Web Tools.



---



# 25. Database



Use the existing Favorite CMS database architecture.



Conceptual plugin-owned tables:



```text

favorite\_web\_tools

favorite\_web\_tool\_categories

favorite\_web\_tool\_python\_services

```



Actual names and schema must follow repository conventions.



---



# 26. Migration



Use the existing Favorite CMS migration system.



Migrations may modify only plugin-owned schema.



Never silently modify unrelated CMS tables.



---



# 27. Default Catalog



Initial categories and tools are defined by:



`30-DEFAULT-TOOL-CATALOG.md`



The initial public catalog includes tools such as:



```text

HTML Formatter

HTML Minifier

HTML Validator

HTML Encoder

HTML Decoder

HTML Preview



CSS Formatter

CSS Minifier

CSS Validator

CSS Prefixer

CSS Color Converter

CSS Preview



JavaScript Formatter

JavaScript Minifier

JavaScript Validator



JSON Formatter

JSON Validator

JSON Minifier

Base64 Encoder

Base64 Decoder

URL Encoder

URL Decoder

UUID Generator

Hash Generator

Timestamp Converter

Regex Tester

Lorem Ipsum Generator



Text Case Converter

Text Counter

Remove Duplicate Lines

Sort Lines

Find and Replace



PHP Formatter

PHP Validator

PHP Minifier

```



Python tools should not be publicly seeded until a real Python service and implementation exist.



---



# 28. Tool Seed Rule



Default tools must be data-driven.



Do not hard-code separate frontend pages for every tool.



Only implementation-ready tools should be ACTIVE.



Incomplete tools should remain DRAFT or be omitted until implemented.



---



# 29. Input System



Supported input types:



```text

TEXT

TEXTAREA

NUMBER

URL

FILE

SELECT

CHECKBOX

RADIO

JSON

```



Each input may define:



```text

id

label

type

required

placeholder

help

default

validation

options

file constraints

display order

```



---



# 30. Validation



Validation occurs at multiple levels:



```text

Frontend validation

        ↓

Backend validation

        ↓

Engine-specific validation

```



Backend validation is authoritative.



Never trust client-side validation.



---



# 31. Output System



Supported result types:



```text

TEXT

HTML

JSON

FILE

IMAGE

AUDIO

VIDEO

DOWNLOAD

```



The frontend must render results according to their declared type.



---



# 32. Execution Architecture



All server-side execution should follow:



```text

User

 ↓

Tool Page

 ↓

Execution Request

 ↓

Tool Registry

 ↓

Status Check

 ↓

Access Check

 ↓

Input Validation

 ↓

Engine Resolver

 ↓

Engine

 ↓

Result Normalization

 ↓

Frontend Renderer

```



---



# 33. Execution API



Conceptual endpoint:



```text

POST /api/tools/{tool-slug}/execute

```



Exact routing must follow the actual Favorite CMS routing system.



---



# 34. Public Discovery



Conceptual endpoints:



```text

GET /api/tools

GET /api/tools/{tool-slug}

GET /api/tools/categories

```



Optional category/search routes may be implemented where appropriate.



Exact routes must follow CMS conventions.



---



# 35. Public Discovery Rules



Public discovery includes only:



```text

ACTIVE tools

```



DRAFT and DISABLED tools must not appear publicly.



Protected tools can remain discoverable.



---



# 36. Search



Search should support:



```text

Name

Slug

Short description

Full description

Category

```



Search must use safe database queries and existing CMS data access conventions.



Pagination is a UI/performance feature, not a usage limit.



---



# 37. Frontend Catalog



The public catalog should contain:



```text

Active Theme Header

 ↓

Page Title

 ↓

Search

 ↓

Categories / Filters

 ↓

Tool Grid

 ↓

Pagination

 ↓

Active Theme Footer

```



No independent site shell.



---



# 38. Tool Card



Tool cards may display:



```text

Icon

Thumbnail

Name

Short Description

Category

Access Mode

Open Tool

```



Access labels:



```text

Free

Login Required

Membership Required

```



Do not introduce:



```text

Premium

VIP

Credits

Tokens

Limited Uses

```



---



# 39. Individual Tool Page



Conceptual structure:



```text

Active Theme Header

 ↓

Breadcrumb

 ↓

Tool Header

 ↓

Description

 ↓

Access Information

 ↓

Dynamic Input UI

 ↓

Actions

 ↓

Processing State

 ↓

Result

 ↓

Errors

 ↓

Active Theme Footer

```



---



# 40. Theme Integration



This is mandatory.



The active Favorite CMS theme owns:



```text

Header

Footer

Site Shell

Theme State

Dark/Light Mode

Global Layout

```



Favorite Web Tools renders inside that theme.



---



# 41. Dark/Light Mode



Web Tools must follow the active theme's dark/light state.



If the active theme uses:



```css

body.dark

```



the Web Tools interface must respond accordingly.



Do not create another theme toggle.



Do not create a separate dark-mode state.



---



# 42. Theme Switching



If the administrator changes the active CMS theme, Web Tools must continue to render using the newly active theme's:



```text

Header

Footer

Layout

Theme tokens

Dark/light behavior

```



where the CMS architecture supports it.



---



# 43. CSS Isolation



Plugin CSS must be scoped.



Do not create broad selectors that unintentionally modify:



```text

CMS

Theme

Other Plugins

Admin UI

```



User previews must also be isolated.



---



# 44. Responsive Design



The frontend must support:



```text

Desktop

Tablet

Mobile

```



Use existing theme breakpoints/components where possible.



---



# 45. Accessibility



Implement:



* Proper labels.

* Keyboard navigation.

* Focus states.

* Accessible buttons.

* Error identification.

* Appropriate ARIA where needed.

* Sufficient semantic structure.



---



# 46. Frontend State



Tool pages may use a state model such as:



```text

INITIAL

READY

VALIDATING

SUBMITTING

PROCESSING

SUCCESS

ERROR

```



Do not create fake processing progress.



---



# 47. Duplicate Submission



Prevent accidental duplicate submissions at the UI level where appropriate.



This is a UX feature.



It must not be converted into a product usage limit.



---



# 48. Result Handling



Results must be normalized before frontend rendering.



For example:



```text

TEXT → Text Renderer

HTML → Source/Preview Renderer

JSON → Structured JSON Renderer

FILE → File Renderer

IMAGE → Image Renderer

AUDIO → Audio Renderer

VIDEO → Video Renderer

DOWNLOAD → Download Renderer

```



---



# 49. Download Security



Downloads must use controlled references.



Never expose raw filesystem paths.



Verify authorization where required.



Do not allow users to convert arbitrary filesystem paths into downloads.



---



# 50. Admin System



Admin functionality must use the existing Favorite CMS admin system.



Required management areas:



```text

Dashboard

Tools

Categories

Python Services

Settings

```



where supported by the actual CMS admin architecture.



---



# 51. Dynamic Tool Builder



The administrator should be able to configure:



```text

Tool Information

Category

Engine

Access Mode

Status

Inputs

Outputs

Engine Configuration

Frontend Configuration

Dependencies

```



without creating a new hard-coded page for each tool.



---



# 52. Dynamic Engine Configuration



The builder should display engine-specific configuration.



### HTML



```text

Formatter

Minifier

Validator

Encoder

Decoder

Preview

```



### CSS



```text

Formatter

Minifier

Validator

Prefixer

Color Converter

Preview

```



### JavaScript



```text

Formatter

Minifier

Validator

```



with execution separated from source processing.



### PHP



Controlled handler selection.



### Python API



```text

Python Service

Endpoint

Method

Request Mapping

Response Mapping

Timeout

```



---



# 53. Activation Validation



Before a tool becomes ACTIVE, validate:



```text

Identity

Slug

Category

Engine

Access Mode

Input Schema

Output Schema

Engine Configuration

Dependencies

```



Invalid tools cannot be activated.



---



# 54. Admin Testing



Admin testing must reuse the same central execution architecture where possible.



Testing a tool must not automatically activate it.



---



# 55. Dependency Management



Before disabling or deleting a Python service or other dependency, identify dependent tools.



Do not leave ACTIVE tools silently pointing to unavailable dependencies.



---



# 56. Security Boundary



Treat all browser input as untrusted.



Never trust:



```text

Client-side access state

Client-side tool configuration

Client-side engine

Client-side endpoint

Client-side credentials

Client-side membership state

```



The server must resolve these values from trusted configuration.



---



# 57. Preview Security



### HTML



Render in an isolated context where appropriate.



### CSS



Prevent preview CSS from modifying the CMS page.



### JavaScript



Never automatically execute arbitrary submitted JavaScript in the privileged application context.



---



# 58. File Security



Uploaded files must be treated as untrusted.



Validate:



```text

Extension

MIME type

Expected format

Size

Upload errors

Filename

Storage location

```



Prevent:



```text

Path traversal

Unsafe execution

Unauthorized download

Cross-user access

```



---



# 59. SSRF Protection



Never allow a public user to arbitrarily choose the server-side URL used by a Python service or other backend integration.



External destinations must come from trusted configuration.



---



# 60. PHP/Python/Shell Security



Never implement arbitrary:



```text

PHP execution

Python execution

Shell execution

CMD execution

PowerShell execution

System commands

```



---



# 61. API Credentials



Credentials must remain server-side.



Never expose them through:



```text

HTML

JavaScript

API response

Browser storage

Tool metadata

Public discovery

Logs

```



---



# 62. Error Handling



Use structured errors.



Errors may include:



```text

Validation Error

Configuration Error

Access Error

Dependency Error

Service Error

Network Error

Processing Error

File Error

Internal Error

```



Do not expose internal implementation details to public users.



---



# 63. Technical Resource Protection



Technical limits are allowed for infrastructure safety, including:



```text

Request size

Upload size

Execution timeout

Memory/resource safety

Connection timeout

Infrastructure rate limiting

```



These must never become product usage quotas.



---



# 64. No Product Limits



The implementation must contain no:



```text

Daily usage limit

Monthly usage limit

Hourly usage limit

Credits

Tokens

Quota

Remaining usage

Usage counter

Premium tool category

```



---



# 65. Logging



Use existing CMS logging where available.



Technical logs may contain:



```text

Tool identifier

Engine

Error type

Request identifier

Service failure

Processing failure

```



Never log secrets or sensitive payloads unnecessarily.



---



# 66. Search and Catalog Performance



The catalog must be designed for growth to approximately:



```text

40–100+ tools

```



Do not build the frontend around a hard-coded small number of tools.



Use database filtering/pagination where appropriate.



---



# 67. Caching



Public metadata/search results may be cached where safe.



Caching must never bypass:



```text

Authentication

Membership

Authorization

Current access state

```



---



# 68. Testing



Before release, test:



```text

Registry

Categories

Access Control

Lifecycle

Configuration

Inputs

Outputs

All Engines

Python Services

Execution API

Frontend

Search

Admin

Database

Migrations

Security

Theme

Responsive UI

Plugin Isolation

```



---



# 69. Mandatory Access Tests



Verify:



```text

FREE

Anonymous → Allowed

Logged-in → Allowed

Active member → Allowed



LOGIN\_REQUIRED

Anonymous → Denied

Logged-in → Allowed

Active member → Allowed



MEMBERSHIP\_REQUIRED

Anonymous → Denied

Logged-in non-member → Denied

Active member → Allowed

```



---



# 70. Mandatory Security Tests



Verify protection against:



```text

XSS

SQL Injection

CSRF

Path Traversal

SSRF

Unauthorized Downloads

Authentication Bypass

Membership Bypass

Credential Exposure

Unsafe HTML Preview

Unsafe CSS Preview

Unsafe JS Execution

Arbitrary PHP Execution

Arbitrary Python Execution

Shell/CMD/PowerShell Execution

Cross-user Data Leakage

```



---



# 71. Migration Tests



Test:



```text

Fresh Installation

Repeated Migration

Upgrade

Rollback where supported

Seed

Existing Configuration Preservation

```



---



# 72. Theme Tests



Verify:



```text

Header

Footer

Light Mode

Dark Mode

body.dark

Theme Switching

Responsive Layout

No Duplicate Theme Toggle

No Global CSS Conflict

```



---



# 73. Plugin Isolation Tests



Confirm that implementation does not unintentionally modify:



```text

CMS Core

Existing Plugins

Themes

Authentication

Membership

Database Infrastructure

Routing Infrastructure

```



---



# 74. Installation



Installation must use the existing Favorite CMS plugin lifecycle.



Conceptually:



```text

Plugin Discovery

 ↓

Registration

 ↓

Migration

 ↓

Seed

 ↓

Activation

```



---



# 75. Upgrade



Upgrades must:



* Use incremental migrations.

* Preserve administrator configuration.

* Add missing defaults safely.

* Avoid overwriting existing customization.

* Validate changed configuration.



---



# 76. Deployment



Production deployment should follow:



```text

Backup

 ↓

Deploy Plugin

 ↓

Run Migration

 ↓

Verify Configuration

 ↓

Seed Missing Defaults

 ↓

Activate

 ↓

Smoke Test

```



Use the existing CMS deployment process.



---



# 77. Git Safety



Before commit, verify:



```text

Changed Files

Plugin Files

Migrations

Assets

Tests

Configuration

```



Ensure unrelated modifications are not included.



Never commit secrets.



---



# 78. Implementation Phases



The AI agent should implement incrementally.



## Phase 1 — Repository Discovery



Inspect the repository completely enough to understand the required integration points.



Do not code before this phase is complete.



---



## Phase 2 — Plugin Skeleton



Create the minimum plugin structure using actual Favorite CMS conventions.



Verify plugin discovery and lifecycle.



---



## Phase 3 — Database



Implement:



```text

Migrations

Tool schema

Category schema

Python service schema

Seed mechanism

```



Run migration tests.



---



## Phase 4 — Core Registry



Implement:



```text

Tool Registry

Category Registry

Configuration Loading

Lifecycle

Validation

```



---



## Phase 5 — Access Control



Implement the exact three access modes using existing CMS authentication/membership.



Test the complete access matrix.



---



## Phase 6 — Engine Architecture



Implement:



```text

Engine Resolver

HTML Engine

CSS Engine

JavaScript Engine

PHP Engine

Python API Engine

```



Do not enable arbitrary code execution.



---



## Phase 7 — Execution Layer



Implement:



```text

Execution Request

Tool Resolution

Status Check

Access Check

Input Validation

Engine Execution

Result Normalization

Error Handling

```



---



## Phase 8 — Python Service Integration



Implement:



```text

Python Service Registry

Service Client

Authentication

Request Mapping

Response Mapping

Timeout Handling

File Handling

Dependency Checks

```



Only if required by actual architecture.



---



## Phase 9 — Admin



Implement:



```text

Dashboard

Tool Management

Dynamic Builder

Categories

Python Services

Validation

Testing

Activation

Disable/Delete

```



Use existing CMS admin UI.



---



## Phase 10 — Public Discovery



Implement:



```text

Tool Catalog

Categories

Search

Filtering

Pagination

Tool Cards

```



---



## Phase 11 — Frontend Tool Page



Implement the dynamic tool page.



It must render from Tool Registry configuration.



---



## Phase 12 — Theme Integration



Verify:



```text

Active Header

Active Footer

Light Mode

Dark Mode

body.dark

Theme Switching

Responsive UI

```



---



## Phase 13 — Default Tools



Implement the default catalog tools.



Only activate tools that are genuinely implemented and tested.



---



## Phase 14 — Testing



Run the complete test suite.



Fix implementation issues rather than disabling tests.



---



## Phase 15 — Deployment Verification



Test:



```text

Installation

Migration

Upgrade

Activation

Frontend

Admin

Execution

Security

Theme

Regression

```



---



# 79. AI Agent Working Rules



The AI agent must follow these rules throughout implementation.



### Rule 1



Inspect before modifying.



### Rule 2



Reuse before creating.



### Rule 3



Follow existing CMS conventions.



### Rule 4



Do not modify CMS core unnecessarily.



### Rule 5



Do not create duplicate infrastructure.



### Rule 6



Keep Web Tools modular.



### Rule 7



Keep tools data-driven.



### Rule 8



Keep access control centralized.



### Rule 9



Keep execution centralized.



### Rule 10



Keep credentials server-side.



### Rule 11



Never execute arbitrary user code.



### Rule 12



Never introduce product usage quotas.



### Rule 13



Do not expose internal configuration publicly.



### Rule 14



Do not bypass the active CMS theme.



### Rule 15



Do not create a second theme toggle.



### Rule 16



Test every major change.



### Rule 17



Do not disable tests to make the build pass.



### Rule 18



Do not silently change existing CMS behavior.



### Rule 19



Do not overwrite administrator configuration during upgrades.



### Rule 20



If the repository contradicts an assumption in these specifications, inspect the repository and adapt the implementation to the actual architecture without violating the functional requirements.



---



# 80. File-by-File Implementation Rule



The AI agent should work incrementally.



For each implementation phase:



```text

Inspect

 ↓

Plan

 ↓

Implement

 ↓

Test

 ↓

Review

 ↓

Continue

```



Do not generate the entire plugin blindly in one operation.



---



# 81. Change Reporting



After each meaningful implementation phase, report:



```text

Files Created

Files Modified

Files Not Modified

Database Changes

Tests Added

Tests Run

Known Issues

Next Step

```



---



# 82. No False Completion



The AI agent must never claim:



```text

Implemented

Tested

Secure

Production Ready

```



unless the corresponding work has actually been performed.



---



# 83. Final Acceptance Criteria



Favorite Web Tools is considered implementation-complete only when:



```text

□ Plugin loads through Favorite CMS

□ Plugin is isolated

□ Database migrations work

□ Default catalog works

□ Tool Registry works

□ Categories work

□ Three access modes work

□ Membership integration works

□ No usage quotas exist

□ All required engines work

□ PHP does not allow arbitrary execution

□ Python uses API boundary

□ Python credentials remain private

□ Execution API works

□ Input validation works

□ Output rendering works

□ Search works

□ Catalog works

□ Admin builder works

□ Tool lifecycle works

□ Theme Header works

□ Theme Footer works

□ Light mode works

□ Dark mode works

□ body.dark works where applicable

□ Theme switching works

□ Responsive UI works

□ Preview isolation works

□ Download security works

□ Security tests pass

□ Migration tests pass

□ Regression tests pass

□ Production smoke tests pass

```



---



# 84. Final Architecture



```text

                         FAVORITE CMS

                              │

        ┌─────────────────────┼─────────────────────┐

        │                     │                     │

   Authentication        Membership             Theme

        │                     │                     │

        └─────────────────────┼─────────────────────┘

                              │

                              ▼

                  FAVORITE WEB TOOLS

                              │

        ┌─────────────┬───────┼────────┬─────────────┐

        ▼             ▼       ▼        ▼             ▼

      Registry     Access   Config   Discovery    Admin

        │             │       │        │             │

        └─────────────┴───────┼────────┴─────────────┘

                              ▼

                       Execution Layer

                              │

                 ┌────────────┼────────────┐

                 ▼            ▼            ▼

              HTML/CSS/JS    PHP       Python API

                                           │

                                           ▼

                                   Python Service

                                           │

                              ┌────────────┴────────────┐

                              │                         │

                       External API              Private Service

```



---



# 85. Final User Flow



```text

User

 ↓

Favorite CMS Theme

 ↓

Web Tools Catalog

 ↓

Search / Category

 ↓

Tool Page

 ↓

Access Check

 ↓

Input

 ↓

Execution API

 ↓

Tool Engine

 ↓

Result

 ↓

Copy / Preview / Download

```



---



# 86. Final Administrative Flow



```text

Admin

 ↓

Favorite CMS Admin

 ↓

Favorite Web Tools

 ↓

Create Tool

 ↓

Configure

 ↓

Select Category

 ↓

Select Engine

 ↓

Select Access

 ↓

Configure Inputs

 ↓

Configure Outputs

 ↓

Configure Dependencies

 ↓

Validate

 ↓

Test

 ↓

Activate

 ↓

Public Tool

```



---



# 87. Final Principle



**Favorite Web Tools is a platform, not a collection of unrelated hard-coded pages.**



The implementation must provide one reusable architecture capable of supporting many tools through:



```text

Tool Registry

\+

Configuration

\+

Access Control

\+

Engine System

\+

Input/Output System

\+

Execution API

\+

Discovery

\+

Admin Builder

\+

Theme Integration

\+

Testing

```



Favorite CMS remains the foundation.



The plugin must integrate with the CMS rather than recreate it.



The browser is untrusted.



The server is authoritative.



Protected functionality must be enforced server-side.



Python must remain behind an API boundary.



Arbitrary code execution is prohibited.



Membership provides unlimited access when required.



There are no product usage limits, credits, tokens, quotas, or usage counters.



The active CMS theme owns the Header, Footer, layout, and dark/light state.



All implementation decisions must begin with repository inspection and must preserve existing Favorite CMS functionality.



**This document marks the end of the specification/planning phase. After this file is approved, implementation begins.**




---

# Final Agent Guardrails Addendum

Before starting implementation, read `00-READ-ME-FIRST.md` and `34-DOCUMENT-REVIEW-CORRECTIONS-AND-GUARDRAILS.md`.

The implementation agent must also follow these execution rules:

1. Inspect actual Favorite CMS contracts before inventing plugin abstractions.
2. Keep core-boundary values in the native types expected by Favorite CMS.
3. Keep public plugin views content-only and let the active theme own the global shell.
4. Do not globally pollute template resolution with plugin/theme paths.
5. Never add arbitrary PHP/Python/shell execution.
6. Do not expand the access model beyond `FREE`, `LOGIN_REQUIRED`, `MEMBERSHIP_REQUIRED`.
7. Fix the exact reproduced root cause before broad refactoring.
8. Use focused tests during development; run the full suite at the release gate.
9. Build the final ZIP, install the exact ZIP, and verify it in a real browser before declaring completion.
10. Record the final package SHA-256 and release only those verified bytes.

Tests, generated reports, or simulated requests must not be described as production verification unless the real deployed environment/browser was actually checked.
