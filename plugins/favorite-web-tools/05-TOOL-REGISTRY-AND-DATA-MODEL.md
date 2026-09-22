# Favorite Web Tools — Tool Registry \& Data Model Specification



## 1. Purpose



This document defines the data model and registry system for tools inside the `favorite-web-tools` plugin.



The Tool Registry is the central source for discovering, loading, configuring, and managing tools.



The implementation must follow the existing Favorite CMS architecture and database conventions.



---



## 2. Core Principle



Every tool must have a unique identity and a structured configuration.



Conceptually:



```text

Tool

├── Identity

├── Display Information

├── Category

├── Engine

├── Access Mode

├── Status

├── Configuration

└── Metadata

```



The registry must allow the system to add new tools without rewriting the core tool platform.



---



## 3. Tool Identity



Every tool must have:



### Required



* `id`

* `name`

* `slug`



### Rules



* `id` must be unique.

* `slug` must be unique.

* `name` is the human-readable tool name.

* `slug` is used for stable tool lookup and URLs.

* Slugs should be URL-safe.

* Slugs should remain stable after publication unless there is an explicit administrative reason to change them.



Example:



```text

id: 101

name: JSON Formatter

slug: json-formatter

```



---



## 4. Tool Display Information



A tool may contain:



* Name

* Short description

* Full description

* Icon

* Thumbnail

* Help text

* Usage instructions

* SEO title

* SEO description



Only fields actually supported or required by the existing CMS should be introduced.



Do not create unnecessary duplicate content systems.



---



## 5. Category Relationship



Each tool may belong to a category according to the plugin's category model.



Example:



```text

Developer

 ├── JSON Formatter

 ├── JSON Validator

 └── Base64 Encoder

```



Category and access mode remain independent.



Example:



```text

Category: Developer

Access: FREE

```



and:



```text

Category: Developer

Access: MEMBERSHIP\_REQUIRED

```



are both valid.



---



## 6. Engine



Each tool must identify which execution engine it uses.



Supported initial engines:



```text

HTML

CSS

JAVASCRIPT

PHP

PYTHON\_API

```



The engine should be represented using a stable internal value.



Conceptual example:



```text

engine = JAVASCRIPT

```



The final database representation must follow repository conventions.



---



## 7. Access Mode



Each tool must have one access mode:



```text

FREE

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



This field must integrate with the access-control system defined in:



```text

04-ACCESS-CONTROL.md

```



No other access levels should be introduced without a future specification change.



---



## 8. Tool Status



Each tool must have a lifecycle status.



Initial statuses:



```text

ACTIVE

DRAFT

DISABLED

```



### ACTIVE



The tool is available according to its access rules.



### DRAFT



The tool exists in the system but is not publicly available.



### DISABLED



The tool is intentionally unavailable.



A disabled tool must not execute even if the user otherwise has permission.



---



## 9. Tool Configuration



The registry should support engine-specific configuration.



Conceptually:



```text

configuration

```



may contain structured configuration appropriate to the engine.



Examples:



### JavaScript Tool



```text

{

  "execution": "client"

}

```



### PHP Tool



```text

{

  "endpoint": "...",

  "method": "POST"

}

```



### Python API Tool



```text

{

  "service": "...",

  "endpoint": "...",

  "method": "POST"

}

```



These are conceptual examples only.



The final schema must follow the actual implementation architecture.



---



## 10. Do Not Store Arbitrary Executable Code in Tool Metadata



The Tool Registry must not become an arbitrary code execution system.



Do not design a database field where administrators can insert arbitrary PHP and have the application execute it through mechanisms such as:



```text

eval()

```



or equivalent dynamic execution.



Tool execution must use controlled, explicitly implemented engines.



---



## 11. Tool Registry Responsibilities



The registry is responsible for:



* Registering tools

* Finding tools by ID

* Finding tools by slug

* Listing active tools

* Listing tools by category

* Loading tool configuration

* Determining engine

* Determining access mode

* Determining status

* Providing tool metadata to the frontend/admin system



The registry should not itself perform the complete execution logic.



---



## 12. Registry vs Engine



Keep the responsibilities separate.



```text

Tool Registry

     ↓

Identifies tool

     ↓

Provides configuration

     ↓

Engine Resolver

     ↓

Selected Engine

     ↓

Execution

```



The registry should not contain separate execution implementations for every engine.



---



## 13. Database Design



The plugin should use its own database tables where persistent tool data is required.



The implementation must follow the existing Favorite CMS migration system.



Do not modify unrelated CMS tables unless the existing architecture explicitly requires an integration.



Possible conceptual tables include:



```text

favorite\_web\_tools

favorite\_web\_tool\_categories

```



Additional tables should only be introduced when genuinely required.



Do not create tables merely because they might be useful in the future.



---



## 14. Tool Table — Conceptual Fields



The primary tool table may contain fields conceptually equivalent to:



| Field           | Purpose                       |

| --------------- | ----------------------------- |

| `id`            | Unique tool ID                |

| `name`          | Tool name                     |

| `slug`          | Unique URL-safe identifier    |

| `description`   | Tool description              |

| `category\_id`   | Category relationship         |

| `engine`        | Execution engine              |

| `access\_mode`   | Access requirement            |

| `status`        | Tool lifecycle state          |

| `configuration` | Engine-specific configuration |

| `created\_at`    | Creation timestamp            |

| `updated\_at`    | Last update timestamp         |



The exact field names, types, indexes, and timestamp conventions must follow the repository's existing database style.



---



## 15. Configuration Storage



Engine-specific configuration may require structured storage.



If the existing CMS/database conventions support JSON configuration cleanly, structured JSON may be used.



Example:



```text

configuration

    ↓

JSON

    ↓

Engine-specific settings

```



The implementation must validate configuration before using it.



Malformed configuration must not cause uncontrolled execution.



---



## 16. Tool ID and Slug Lookup



The registry must support:



```text

findById(id)

findBySlug(slug)

```



Slug lookup will be particularly important for public tool pages.



Example conceptual route:



```text

/tools/json-formatter

```



The actual route must follow the CMS routing architecture.



---



## 17. Unique Constraints



The database should enforce uniqueness where appropriate.



At minimum:



```text

tool.id → unique

tool.slug → unique

```



If categories use slugs:



```text

category.slug → unique

```



The exact database constraint syntax must follow the existing migration conventions.



---



## 18. Category Relationship Rules



A tool's category must reference a valid category when the category relationship is required.



The implementation should avoid orphaned category references.



If the existing CMS architecture supports foreign keys, use them according to repository conventions.



Otherwise, enforce relationship integrity through application logic.



Do not introduce database behavior that conflicts with the existing CMS database strategy.



---



## 19. Tool Configuration Validation



Before a tool becomes `ACTIVE`, the system should validate:



* Required identity fields

* Valid slug

* Valid category relationship

* Supported engine

* Valid access mode

* Valid status

* Required engine configuration

* Required Python service configuration for `PYTHON\_API`

* Required endpoint information where applicable



Invalid tools should remain unavailable rather than being activated with incomplete configuration.



---



## 20. Draft Workflow



A new tool should normally be created as:



```text

DRAFT

```



Then:



```text

Configure

    ↓

Validate

    ↓

Test

    ↓

ACTIVE

```



This allows incomplete tools to exist without exposing them publicly.



---



## 21. Disable Workflow



An existing tool can be changed to:



```text

DISABLED

```



A disabled tool:



* Should not appear as an available public tool.

* Should not execute.

* May remain visible to administrators.

* Should retain its configuration unless explicitly deleted.



Disabling is preferred over destructive deletion when historical configuration may be useful.



---



## 22. Tool Deletion



Deletion must be handled carefully.



Before deleting a tool, the implementation must consider:



* Category relationship

* Existing configuration

* Associated assets

* Logs or records, if introduced later

* References from other plugin components



The exact deletion behavior must follow the repository's data-management conventions.



Do not automatically cascade-delete unrelated CMS data.



---



## 23. No Usage Data Model



The Tool Registry must NOT contain fields such as:



```text

daily\_limit

monthly\_limit

credits

tokens

remaining\_uses

usage\_quota

membership\_usage

```



Favorite Web Tools has no usage-limit system.



Active membership provides unlimited access.



---



## 24. Python API Service Relationship



A `PYTHON\_API` tool should reference a configured Python service rather than storing Python source code inside the tool record.



Conceptually:



```text

Tool

  ↓

Python Service

  ↓

API Endpoint

```



This allows multiple tools to use the same Python backend service.



Python service configuration should be managed separately from ordinary tool metadata where appropriate.



---



## 25. API Credentials



Sensitive API credentials must not be stored as frontend-visible tool configuration.



If a Python service requires authentication, credentials must remain server-side.



The final storage mechanism must follow the existing Favorite CMS configuration/security conventions.



Do not expose credentials through:



* HTML

* JavaScript

* Public API responses

* Tool metadata endpoints



---



## 26. Tool Registry API



The internal registry should provide a clean interface similar to:



```text

register()

find()

findById()

findBySlug()

all()

active()

byCategory()

update()

disable()

```



These names are conceptual.



The actual class/function names must follow Favorite CMS coding conventions.



---



## 27. Repository Compatibility



Before implementation, the AI agent must inspect:



* Existing plugin structure

* Existing migrations

* Existing database helpers

* Existing model/repository patterns

* Existing configuration patterns

* Existing admin patterns

* Existing route patterns

* Existing asset loading patterns



The agent must reuse existing mechanisms where possible.



Do not create a parallel framework.



---



## 28. No Core Modification



Tool Registry implementation must remain inside:



```text

Favorite-CMS-Universal/plugins/favorite-web-tools/

```



and its corresponding plugin-specific assets location if the CMS architecture requires one.



Do not modify:



* CMS core

* Existing plugins

* Global authentication

* Global membership logic

* Core database abstractions

* Core bootstrap

* Existing unrelated migrations

* Existing unrelated assets



unless an explicit future specification authorizes an integration.



---



## 29. Future Extensibility



The registry must make it possible to add:



```text

New Tool

New Category

New Engine

New Python Service

```



without rewriting the existing tool platform.



For example:



```text

Existing Engines

├── HTML

├── CSS

├── JAVASCRIPT

├── PHP

└── PYTHON\_API



Future Engine

└── NEW\_ENGINE

```



Adding a future engine should require implementing the appropriate engine contract rather than rewriting the registry.



---



## 30. AI Agent Implementation Rules



The implementation agent must:



1. Inspect the repository before creating database structures.

2. Follow existing migration conventions.

3. Follow existing model/repository conventions.

4. Follow existing plugin conventions.

5. Avoid duplicate infrastructure.

6. Keep tool data isolated to the plugin.

7. Keep registry and execution responsibilities separate.

8. Keep category and access control independent.

9. Support the five initial engines.

10. Support the three defined access modes.

11. Keep tool status independent from access mode.

12. Avoid arbitrary executable code stored in database metadata.

13. Do not add usage-limit fields.

14. Do not add credit/token systems.

15. Do not expose API credentials.

16. Do not modify CMS core files.

17. Do not guess undocumented CMS APIs.

18. If repository conventions conflict with a conceptual field or structure in this document, inspect the repository and adapt the implementation while preserving the intended behavior.



---



## 31. Acceptance Criteria



The Tool Registry is considered correctly implemented when:



* Every tool has a unique identity.

* Tools can be found by ID and slug.

* Tools can be categorized.

* Tools identify their execution engine.

* Tools have exactly one access mode.

* Tools have a lifecycle status.

* Tool configuration is structured and validated.

* Active, draft, and disabled states work correctly.

* Registry and engine execution are separate responsibilities.

* Python tools reference configured Python services.

* Tool metadata does not contain arbitrary executable code.

* No usage-limit/credit/token system exists.

* Database changes are isolated to the plugin.

* Existing CMS migration/database conventions are respected.

* Existing CMS infrastructure is reused.

* CMS core remains untouched.



---



## 32. Final Data Model



Conceptually, the system follows:



```text

Category

   │

   └── Tool

        ├── Identity

        ├── Engine

        ├── Access Mode

        ├── Status

        └── Configuration

               │

               └── Python Service (when engine = PYTHON\_API)

```



The Tool Registry is the authoritative source for tool discovery and configuration, while execution belongs to the appropriate engine.



