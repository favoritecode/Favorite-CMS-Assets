# Favorite Web Tools — Admin Panel Specification



## 1. Purpose



This document defines the administration interface for the `favorite-web-tools` plugin.



The Admin Panel will allow authorized CMS administrators to manage:



* Tools

* Categories

* Tool configuration

* Access modes

* Tool status

* Python API services

* Tool testing

* Basic plugin settings



The implementation must reuse the existing Favorite CMS admin architecture.



---



# 2. Admin Architecture



The conceptual structure is:



```text

Favorite Web Tools

        │

        └── Admin

             ├── Dashboard

             ├── Tools

             ├── Categories

             ├── Python Services

             └── Settings

```



Exact menu names, routes, controllers, views, permissions, and UI components must follow the actual Favorite CMS repository conventions.



Do not create a separate admin framework.



---



# 3. Admin Access



The Web Tools administration interface must be accessible only to users who have the appropriate existing CMS administrative permission.



Do not create a separate administrator authentication system.



Do not bypass the CMS's existing authorization system.



The exact permission mechanism must be determined by inspecting the repository.



---



# 4. Admin Dashboard



The dashboard should provide a simple overview of the Web Tools plugin.



Possible information:



```text

Total Tools

Active Tools

Draft Tools

Disabled Tools

Total Categories

Python Services

Active Python Services

```



These are administrative statistics only.



Do not introduce usage counters or usage-limit systems.



---



# 5. Tools Management



The main Tools section should allow administrators to:



* View tools

* Add tool

* Edit tool

* Configure tool

* Test tool

* Activate tool

* Disable tool

* Delete tool where appropriate



Conceptually:



```text

Tools

├── All Tools

├── Add Tool

├── Edit

├── Test

├── Activate

└── Disable

```



---



# 6. Tool List



The tool list should provide enough information for administrators to identify the tool.



Recommended columns:



| Field    | Purpose           |

| -------- | ----------------- |

| Name     | Tool name         |

| Slug     | Public identifier |

| Category | Tool category     |

| Engine   | Execution engine  |

| Access   | Access mode       |

| Status   | Current status    |

| Updated  | Last update       |



The exact columns may be adjusted to match the existing CMS admin UI.



---



# 7. Tool Search



If the existing CMS admin framework provides search functionality, reuse it.



The Tools list should eventually support searching by:



* Tool name

* Slug

* Category



Do not introduce a separate search engine for the plugin.



---



# 8. Tool Filtering



Where supported by the existing admin architecture, tools may be filtered by:



* Category

* Engine

* Access Mode

* Status



Example:



```text

Status:

\[ All ]

\[ Active ]

\[ Draft ]

\[ Disabled ]

```



Filtering must not change the underlying tool data.



---



# 9. Add Tool



The Add Tool interface should collect the minimum information required to create a tool.



Conceptual fields:



```text

Name

Slug

Description

Category

Engine

Access Mode

Status

Configuration

```



A newly created tool should normally begin as:



```text

DRAFT

```



unless the existing workflow explicitly supports immediate activation after validation.



---



# 10. Tool Name



The administrator enters the human-readable tool name.



Example:



```text

JSON Formatter

```



The name should be validated according to existing CMS validation conventions.



---



# 11. Tool Slug



The administrator may provide a slug or the system may generate one from the tool name.



Example:



```text

JSON Formatter

        ↓

json-formatter

```



The final slug must be unique.



Duplicate slugs must not be allowed.



---



# 12. Tool Description



The administrator should be able to define a user-facing description.



Example:



```text

Format and beautify JSON data quickly.

```



The description is presentation content and must be properly validated/escaped according to CMS conventions.



---



# 13. Category Selection



The tool creation/edit form should allow the administrator to select a category.



Example:



```text

Category:

\[ Developer ▼ ]

```



Categories must remain independent from access control.



---



# 14. Engine Selection



The administrator should select one supported engine:



```text

HTML

CSS

JAVASCRIPT

PHP

PYTHON\_API

```



The form should display only the configuration fields relevant to the selected engine.



---



# 15. Access Mode Selection



The administrator must select exactly one access mode:



```text

FREE

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



No additional access mode should be presented.



There must be no:



```text

Premium Category

Credits

Tokens

Daily Limit

Monthly Limit

```



configuration.



---



# 16. Status Selection



The administrator should be able to configure:



```text

DRAFT

ACTIVE

DISABLED

```



An invalid or incomplete tool must not be activated.



---



# 17. Engine-Specific Configuration



The admin form should dynamically show relevant configuration.



Conceptually:



```text

Engine = JAVASCRIPT

        ↓

JavaScript Configuration

```



or:



```text

Engine = PYTHON\_API

        ↓

Python Service

Endpoint

Method

Input Mapping

Output Mapping

```



The exact fields depend on the engine.



---



# 18. PHP Tool Configuration



PHP tools should reference controlled PHP implementations/handlers.



The admin interface must not provide a field intended to accept arbitrary PHP source for execution.



Do not create:



```text

PHP Code:

\[ arbitrary user code ]

```



as an execution mechanism.



---



# 19. JavaScript Tool Configuration



JavaScript configuration should distinguish between processing logic and execution.



For example:



```text

Client-side formatter

```



is different from:



```text

JavaScript execution environment

```



The admin interface must not silently turn ordinary JavaScript processing tools into arbitrary code-execution tools.



---



# 20. Python Tool Configuration



When:



```text

Engine = PYTHON\_API

```



the administrator should be able to select a configured Python service.



Conceptually:



```text

Python Service:

\[ Python Processing Server ▼ ]



Endpoint:

\[ /process ]



Method:

\[ POST ]

```



Additional fields may be added when required by actual tools.



---



# 21. Python Service Management



A separate administration section should manage Python services.



Conceptually:



```text

Python Services

├── All Services

├── Add Service

├── Edit Service

├── Test Connection

└── Enable / Disable

```



---



# 22. Add Python Service



The Add Service form may contain:



```text

Service Name

Base URL

Authentication

Timeout

Status

```



Sensitive credential fields must be handled using the existing CMS configuration/security mechanisms.



---



# 23. Python Service List



The service list should provide:



| Field           | Purpose                   |

| --------------- | ------------------------- |

| Name            | Service name              |

| Base URL        | Configured endpoint base  |

| Status          | Active/Disabled           |

| Dependent Tools | Number/reference of tools |

| Updated         | Last update               |



Do not display private credentials.



---



# 24. Test Python Service



Administrators should have a way to test whether a configured Python service is reachable.



Conceptually:



```text

Python Service

      ↓

Test Connection

      ↓

Success / Failed

```



The test should not expose credentials.



The exact health-check mechanism should be implemented according to the Python service specification and existing CMS HTTP architecture.



---



# 25. Dependent Tool Detection



Before disabling or deleting a Python service, the system should identify tools that depend on it.



Example:



```text

Python Service

     ↓

Used by:

\- Image Processor

\- PDF Processor

\- Audio Processor

```



The admin should not accidentally break multiple tools without an appropriate warning.



---



# 26. Categories Management



The Categories section should allow administrators to:



* View categories

* Add category

* Edit category

* Disable/delete category where appropriate



Conceptually:



```text

Categories

├── All Categories

├── Add Category

└── Edit Category

```



---



# 27. Category Fields



A category may contain:



```text

Name

Slug

Description

Status

```



Only fields actually required by the implementation should be introduced.



---



# 28. Category Slug



Category slugs should be unique when used in public URLs or internal lookups.



Example:



```text

Developer

    ↓

developer

```



The implementation must follow existing CMS slug conventions.



---



# 29. Category Deletion



A category should not be deleted blindly when tools are assigned to it.



Possible workflow:



```text

Category

   ↓

Contains tools

   ↓

Warn administrator

```



The exact deletion behavior should follow repository conventions.



Disabling a category may be preferable where supported.



---



# 30. Tool Test Mode



The admin interface should allow administrators to test a tool before making it publicly active.



Conceptually:



```text

DRAFT

  ↓

Test

  ↓

Validate

  ↓

ACTIVE

```



The test mechanism must use the same engine architecture that the actual tool uses.



It must not create a separate execution system.



---



# 31. Access Testing



When testing a tool, the system should respect the configured access mode.



The admin's test workflow may provide an administrative testing mechanism where appropriate, but it must not alter the actual public access configuration.



The implementation must not accidentally make a membership-required tool publicly executable merely because an administrator is testing it.



---



# 32. Tool Preview



Where appropriate, administrators may preview how the public tool page will appear.



Conceptually:



```text

Edit Tool

   ↓

Preview

   ↓

Public Tool UI

```



The preview should use the normal frontend/tool rendering architecture rather than duplicating the tool UI.



---



# 33. Tool Activation



Before setting a tool to `ACTIVE`, the system should verify:



* Name exists

* Slug is valid and unique

* Category is valid

* Engine is supported

* Access mode is valid

* Required configuration exists

* Python service exists when required

* Tool configuration passes validation



If validation fails, activation should be blocked.



---



# 34. Tool Disable



An administrator can disable a tool without deleting it.



Conceptually:



```text

ACTIVE

  ↓

DISABLED

```



A disabled tool must not execute for any user.



Its configuration should remain available to administrators.



---



# 35. Bulk Actions



Bulk actions may be supported if the existing CMS admin architecture already provides them.



Possible actions:



```text

Activate

Disable

Delete

```



Bulk actions must include appropriate confirmation for destructive operations.



Do not implement bulk actions merely for the sake of complexity.



---



# 36. Admin Validation



All admin input must be validated.



Validation should cover:



* Required fields

* Slug format

* Unique slug

* Valid category

* Valid engine

* Valid access mode

* Valid status

* Engine configuration

* Python service reference



Existing Favorite CMS validation mechanisms should be reused.



---



# 37. Admin Forms



Forms should follow existing CMS conventions for:



* CSRF protection

* Validation

* Error messages

* Success messages

* Redirects

* Form state

* Permission checks



Do not create custom security mechanisms when the CMS already provides them.



---



# 38. Admin Routes



Admin routes must use the Favorite CMS routing mechanism.



Conceptual routes may look like:



```text

/admin/favorite-web-tools

/admin/favorite-web-tools/tools

/admin/favorite-web-tools/categories

/admin/favorite-web-tools/python-services

/admin/favorite-web-tools/settings

```



These are conceptual only.



The actual routes must follow the repository's existing admin route conventions.



Do not modify the core router unnecessarily.



---



# 39. Admin Views



Admin views should remain inside the plugin's own view/template structure where supported.



Do not place Web Tools templates inside unrelated CMS plugin directories.



Use the existing CMS view-loading mechanism.



---



# 40. Admin Assets



Plugin-specific admin CSS and JavaScript should remain inside the Web Tools plugin asset structure.



Do not globally modify unrelated CMS assets.



Admin assets should load only where necessary.



---



# 41. Settings



A Settings section may eventually contain plugin-level configuration such as:



* General plugin behavior

* Default tool settings

* Python API defaults

* Frontend display settings



Only genuinely global settings should be placed here.



Tool-specific configuration belongs to the individual tool.



---



# 42. No Usage Settings



The Settings page must not contain:



```text

Daily Limit

Monthly Limit

Credits

Tokens

Usage Quota

Membership Usage Limit

```



Favorite Web Tools has no usage-limit system.



---



# 43. Audit / Logging



If the existing CMS provides administrative logging, the plugin may integrate with it for important administrative changes.



Examples:



```text

Tool created

Tool updated

Tool activated

Tool disabled

Python service updated

Category changed

```



Do not create a separate logging framework unless required.



---



# 44. Error Handling



Admin errors should be understandable.



Examples:



```text

Tool configuration is incomplete.

Invalid engine configuration.

Python service is unavailable.

Category does not exist.

Slug already exists.

Tool cannot be activated.

```



Technical details should not be unnecessarily exposed in the normal admin UI.



---



# 45. Destructive Actions



Destructive actions such as deletion should require appropriate confirmation.



Where possible:



```text

Disable

```



should be preferred over immediate deletion when historical configuration may be useful.



---



# 46. Repository Compatibility



Before implementation, the AI agent must inspect:



* Existing admin menus

* Existing admin routes

* Existing admin controllers

* Existing permission system

* Existing form handling

* Existing CSRF implementation

* Existing validation

* Existing admin templates

* Existing admin assets

* Existing notification/message patterns



The implementation must follow these conventions.



---



# 47. No Parallel Admin System



The plugin must not introduce:



* A second admin dashboard framework

* A second authentication system

* A second permission system

* A second routing framework

* A second template framework



Use Favorite CMS infrastructure wherever possible.



---



# 48. AI Agent Implementation Rules



The implementation agent must:



1. Inspect the repository before implementation.

2. Reuse the existing CMS admin architecture.

3. Reuse existing authorization/permission mechanisms.

4. Reuse existing CSRF protection.

5. Reuse existing validation.

6. Reuse existing route conventions.

7. Reuse existing view/template conventions.

8. Reuse existing admin asset conventions.

9. Provide Tool management.

10. Provide Category management.

11. Provide Python Service management.

12. Support the three access modes only.

13. Support the five initial engines.

14. Support ACTIVE/DRAFT/DISABLED states.

15. Never create usage-limit settings.

16. Never create credit/token settings.

17. Never provide arbitrary PHP execution.

18. Never expose Python/API credentials.

19. Do not modify CMS core unnecessarily.

20. Do not guess undocumented CMS APIs.



---



# 49. Acceptance Criteria



The Admin Panel is correctly implemented when:



* Authorized administrators can manage tools.

* Tools can be created and edited.

* Tool engine can be selected.

* Access mode can be selected.

* Tool status can be managed.

* Categories can be managed.

* Python services can be managed.

* Python service connection can be tested where supported.

* Tool dependencies can be identified.

* Tool configuration can be validated.

* Tools can be tested before activation.

* Disabled tools cannot execute.

* Existing CMS admin authentication/authorization is reused.

* Existing CMS CSRF and validation mechanisms are reused.

* No separate admin framework is created.

* No usage-limit system is introduced.

* No premium-category system is introduced.

* CMS core remains isolated from plugin implementation.



---



# 50. Final Admin Structure



The final conceptual structure is:



```text

Favorite Web Tools

│

└── Admin

    │

    ├── Dashboard

    │

    ├── Tools

    │   ├── All Tools

    │   ├── Add Tool

    │   ├── Edit Tool

    │   └── Test Tool

    │

    ├── Categories

    │   ├── All Categories

    │   └── Add/Edit Category

    │

    ├── Python Services

    │   ├── All Services

    │   ├── Add Service

    │   ├── Edit Service

    │   └── Test Connection

    │

    └── Settings

```



This structure is the source of truth for the Favorite Web Tools administration interface.



