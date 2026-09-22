# Favorite Web Tools — Architecture Specification

**Plugin:** Favorite Web Tools
**Slug:** `favorite-web-tools`
**Platform:** Favorite CMS Universal
**Status:** Architecture Specification

---

# 1. Architecture Goal

`Favorite Web Tools` must be implemented as an isolated Favorite CMS plugin.

The plugin must use the existing Favorite CMS architecture and public plugin capabilities instead of creating a separate CMS/application architecture.

Favorite CMS Universal is a standalone PHP + MySQL/MariaDB CMS with a separation between:

* Core
* Plugins
* Themes
* Widgets & Layout

Specialized business/domain functionality belongs in plugins, while the CMS core provides APIs, hooks, filters, custom routes, admin capabilities, and database capabilities for plugins.

Therefore, Favorite Web Tools must remain inside the plugin boundary.

---

# 2. Repository Architecture Must Be Respected

The Favorite CMS repository currently contains major application areas including:

```text
app/
config/
database/migrations/
docs/
plugins/
public/
resources/views/
scripts/
storage/
tests/
themes/
bootstrap.php
index.php
migrate.php
composer.json
```

The plugin must not move, replace, duplicate, or restructure these core application areas.

The implementation must be placed inside the existing plugin system and must follow the actual conventions already used by Favorite CMS plugins.

---

# 3. Core Isolation Rule

The following rule is mandatory:

> Favorite Web Tools must not modify Favorite CMS core functionality unless the existing public plugin API explicitly requires a compatible integration point.

The implementation must not directly modify unrelated:

* Core controllers
* Core models
* Core services
* Core authentication files
* Core configuration
* Core bootstrap files
* Core routes
* Existing plugins
* Existing themes
* Existing frontend assets

If the plugin needs functionality from the CMS, it must first look for an existing:

* Plugin API
* Hook
* Filter
* Route registration mechanism
* Admin registration mechanism
* Database API
* Authentication API
* Membership integration point
* Asset registration mechanism

Only use the established mechanism.

---

# 4. Plugin Boundary

The plugin should conceptually exist as:

```text
plugins/
└── favorite-web-tools/
```

The exact internal folder structure must be determined by inspecting the existing Favorite CMS plugins.

Do not invent a completely new plugin layout if the repository already has a standard plugin structure.

The final implementation must follow the repository's established plugin conventions.

---

# 5. Plugin Responsibilities

Favorite Web Tools is responsible for:

1. Tool registration
2. Tool management
3. Tool categories
4. Tool execution
5. Tool access control
6. Frontend tool interfaces
7. Admin tool management
8. Python API integration
9. Tool configuration
10. Membership access integration
11. Tool status management
12. Tool-related validation
13. Tool-related error handling

The plugin is NOT responsible for rebuilding:

* CMS authentication
* CMS user management
* CMS roles
* CMS membership system
* CMS database abstraction
* CMS routing framework
* CMS admin framework

Existing CMS capabilities must be reused where available.

---

# 6. Tool Architecture

Each tool must be treated as an independent logical unit.

Conceptual model:

```text
Tool
├── ID
├── Name
├── Slug
├── Description
├── Category
├── Engine
├── Access Mode
├── Status
└── Configuration
```

A tool should not contain duplicated platform-level logic.

For example, a tool should not independently implement its own login check if the Favorite Web Tools access-control layer can perform that check.

---

# 7. Tool Registry

The Tool Registry is responsible for identifying and loading available tools.

Conceptual flow:

```text
Favorite Web Tools
        ↓
Tool Registry
        ↓
Find Tool by Slug / ID
        ↓
Validate Tool
        ↓
Check Status
        ↓
Check Access
        ↓
Resolve Engine
        ↓
Execute Tool
```

Each tool must have a unique slug.

Example:

```text
html-formatter
css-formatter
json-formatter
base64-encoder
```

The registry must support future tools without requiring changes to the core plugin architecture.

---

# 8. Tool Engine Architecture

The plugin must separate:

### What a tool does

from:

### How a tool executes

Initial engine types:

```text
HTML
CSS
JavaScript
PHP
Python API
```

Conceptual architecture:

```text
                    Tool
                      │
                      ▼
                Engine Resolver
                      │
       ┌──────────────┼──────────────┐
       ▼              ▼              ▼
   Client-side    PHP Engine    Python API Engine
   Processing
```

The engine system must be extensible.

Future engines must be addable without rewriting existing engines.

---

# 9. HTML / CSS / JavaScript Tools

HTML, CSS, and JavaScript tools may use client-side processing when appropriate.

Example:

```text
User
 ↓
Tool Page
 ↓
Input
 ↓
Browser Processing
 ↓
Result
```

Client-side execution is acceptable for tools where the processing logic is not intended to be protected server-side.

The architecture must not assume that every HTML/CSS/JavaScript tool must execute on the server.

---

# 10. PHP Tools

PHP tools are server-side tools.

Conceptual flow:

```text
User
 ↓
Tool Request
 ↓
Favorite Web Tools
 ↓
Access Check
 ↓
Input Validation
 ↓
PHP Tool Execution
 ↓
Result
 ↓
User
```

PHP tool code must remain within the plugin/tool boundary.

PHP tools must not modify CMS core files.

---

# 11. Python API Tools

Python tools will be external services from the perspective of Favorite CMS.

The CMS does not need to run Python itself.

Conceptual flow:

```text
User
 ↓
Favorite Web Tools
 ↓
Access Check
 ↓
Input Validation
 ↓
Python API Adapter
 ↓
Python Service
 ↓
Python Tool
 ↓
Response
 ↓
Favorite Web Tools
 ↓
User
```

Python services may run:

* On the same server
* On a separate server
* On a dedicated machine
* On a private network
* On another authorized environment

The plugin must communicate with the Python service through an API.

---

# 12. Python Service Configuration

The plugin must support administrator-configured Python services.

Conceptual configuration:

```text
Python Service
├── Name
├── Base URL
├── Authentication
├── Timeout
└── Status
```

Example:

```text
Name:
Favorite Python Server

Base URL:
Configured by administrator

Authentication:
Configured securely

Timeout:
Configured value

Status:
Active / Disabled
```

The exact storage and authentication mechanism must follow Favorite CMS conventions and must be finalized after inspecting the repository's existing configuration/storage patterns.

---

# 13. Access Control

Access control belongs to the Favorite Web Tools platform, not individual tools.

Supported access modes:

```text
FREE
LOGIN_REQUIRED
MEMBERSHIP_REQUIRED
```

Every tool must have exactly one access mode.

---

# 14. Free Tools

Free tools are available to anonymous users.

Flow:

```text
Anonymous User
 ↓
Tool
 ↓
Access Check
 ↓
Allowed
 ↓
Execute
```

No login is required.

---

# 15. Login Required Tools

Login-required tools require an authenticated Favorite CMS user.

Flow:

```text
Anonymous User
 ↓
Access Check
 ↓
Login Required
```

Authenticated user:

```text
Logged-in User
 ↓
Access Check
 ↓
Allowed
 ↓
Execute
```

The plugin must reuse the existing Favorite CMS authentication/session system.

It must not create a second user authentication system.

---

# 16. Membership Required Tools

Membership-required tools require:

1. A valid authenticated user.
2. An active membership.

Flow:

```text
User
 ↓
Authentication Check
 ↓
Membership Check
 ↓
Active?
 ├── YES → Allow
 └── NO  → Deny
```

---

# 17. Membership Usage Rule

Membership-required tools have unlimited usage while membership is active.

There must be NO:

```text
Daily Usage Limit
Monthly Usage Limit
Credit System
Token System
Per-tool Usage Limit
Usage Deduction
```

Membership is an access condition, not a usage quota.

Example:

```text
Active Member
 ↓
Membership Tool
 ↓
Use
Use
Use
Use
Use
...
Unlimited while membership remains active
```

---

# 18. Membership Integration

Favorite Web Tools must not create its own membership/subscription system.

It should integrate with the existing Favorite CMS membership capability/plugin when available.

Conceptual boundary:

```text
Favorite Web Tools
        ↓
Membership Adapter / Integration
        ↓
Existing Favorite CMS Membership System
```

The actual integration method must be determined by inspecting the repository and existing membership plugin implementation.

The AI agent must not guess the membership API.

---

# 19. Category System

Categories are organizational only.

Example categories:

```text
HTML
CSS
JavaScript
Developer Tools
Text Tools
PHP
Python
```

Categories must NOT automatically determine access.

For example:

```text
Category:
Developer Tools

Access:
Free
```

and:

```text
Category:
Developer Tools

Access:
Membership Required
```

must both be valid.

---

# 20. Tool Status

Every tool must have a status.

Minimum statuses:

```text
ACTIVE
DRAFT
DISABLED
```

Only `ACTIVE` tools may be executed by users.

A disabled tool must not execute even if the user otherwise has permission.

---

# 21. Request Lifecycle

All server-side tool requests should conceptually follow:

```text
1. Receive Request
        ↓
2. Identify Tool
        ↓
3. Validate Tool
        ↓
4. Check Tool Status
        ↓
5. Check Authentication
        ↓
6. Check Membership if Required
        ↓
7. Validate Input
        ↓
8. Resolve Engine
        ↓
9. Execute Tool
        ↓
10. Validate Result
        ↓
11. Return Response
```

The exact implementation must use Favorite CMS's existing request/routing conventions.

---

# 22. Frontend Architecture

Each public tool should have a consistent tool-page structure.

Conceptually:

```text
Tool Page
├── Title
├── Description
├── Input Area
├── Action Controls
├── Processing State
├── Result Area
└── Error Area
```

The frontend must follow the active Favorite CMS theme/frontend conventions.

The plugin must not unnecessarily replace or bypass the CMS theme system.

---

# 23. Admin Architecture

The plugin must integrate into the existing Favorite CMS admin system.

Conceptual areas:

```text
Favorite Web Tools
├── Dashboard
├── Tools
│   ├── All Tools
│   ├── Add Tool
│   └── Edit Tool
├── Categories
├── Python Services
└── Settings
```

These are logical requirements.

The exact admin routes, controllers, views, menus, permissions, and registration mechanism must be based on the actual Favorite CMS plugin/admin architecture.

---

# 24. Database Architecture

If persistent database storage is required, the plugin must follow the Favorite CMS database/migration conventions.

Potential plugin-owned data may include:

```text
Tools
Categories
Python Services
Tool Configuration
```

The plugin must not modify unrelated core tables unless an existing supported integration explicitly requires it.

Database schema must be isolated and clearly identifiable as belonging to Favorite Web Tools.

The AI agent must inspect existing migrations and plugin database patterns before creating new migrations.

---

# 25. Routes

Public and administrative routes must be registered through the Favorite CMS's supported routing mechanism.

Do not modify core route files simply to make the plugin work.

Conceptual route groups:

```text
Public
/tools/{tool-slug}

/tools/{tool-slug}/execute
```

Admin routes:

```text
/admin/.../web-tools
```

These are conceptual examples only.

The actual route paths must follow Favorite CMS conventions discovered during repository inspection.

---

# 26. Assets

Plugin-specific:

* CSS
* JavaScript
* Images
* Other frontend assets

must remain inside the plugin's asset boundary where supported by the CMS.

The plugin must use the CMS's existing asset loading/registration mechanism if one exists.

It must not modify global theme assets unnecessarily.

---

# 27. Configuration

Separate configuration levels must be maintained.

### Plugin-level configuration

General Favorite Web Tools settings.

### Tool-level configuration

Settings specific to one tool.

### Python-service configuration

Settings required to communicate with a Python service.

A tool must not directly manipulate unrelated global CMS configuration.

---

# 28. API / Service Layer

External API communication must be centralized.

Do not place raw HTTP/API communication logic throughout individual tools.

Conceptually:

```text
Tool
 ↓
Python/API Service
 ↓
HTTP Client
 ↓
External Service
```

This makes the system easier to maintain and extend.

---

# 29. Error Handling

The plugin should provide consistent handling for:

```text
Tool Not Found
Tool Disabled
Authentication Required
Membership Required
Invalid Input
Validation Error
Execution Error
Python API Error
Connection Error
Timeout
External Service Error
Unexpected Error
```

User-facing errors should be understandable.

Internal implementation details, credentials, stack traces, or sensitive service information must not be unnecessarily exposed.

---

# 30. Dependency Rule

Favorite Web Tools should use existing Favorite CMS functionality whenever possible.

Do not introduce:

* A new framework
* A second router
* A second authentication system
* A second database abstraction
* A separate admin framework
* An unnecessary build system

unless repository inspection proves that it is necessary.

Any new dependency must be documented before being introduced.

---

# 31. Plugin-to-Plugin Integration

Favorite Web Tools may integrate with other Favorite CMS plugins through supported interfaces.

Potential integrations include:

```text
Favorite Membership
Favorite API Connector
Future Favorite plugins
```

Integration must be loosely coupled.

A missing optional plugin should not break the entire Favorite Web Tools plugin unless that dependency is explicitly declared as mandatory.

---

# 32. Favorite API Connector Relationship

`Favorite API Connector` is a separate plugin.

Its primary purpose is reusable third-party API connectivity.

Example:

```text
Favorite API Connector
├── Fraud Checker API
├── SMM Panel API
└── Other External APIs
```

Favorite Web Tools is the user-facing tool platform.

The two plugins may communicate through a supported plugin interface when required.

Neither plugin should copy the other's entire functionality.

---

# 33. Security Boundary

Security must be handled according to Favorite CMS's existing security architecture.

The plugin must reuse existing protections where available, including:

* CSRF protection
* Authentication
* Authorization
* Prepared database queries
* Input validation
* Output escaping
* Existing permission checks

Favorite CMS already documents protections such as prepared PDO queries, content sanitization, executable-upload restrictions, CSRF validation, and secure password hashing. The plugin must not bypass these protections.

Any new server-side processing introduced by Favorite Web Tools must follow the same security principles.

---

# 34. Sensitive Configuration

Private credentials such as:

* API keys
* API secrets
* Python service authentication tokens

must never be exposed to frontend users.

The AI agent must inspect how Favorite CMS currently stores sensitive configuration before choosing the implementation.

Do not invent a credential-storage mechanism before checking the existing repository.

---

# 35. Source-Code Exposure

The project may use client-side HTML/CSS/JavaScript processing where appropriate.

However, client-side code delivered to a browser cannot be treated as secret.

Therefore:

```text
Client-side tool
→ Logic is browser-visible.

Server-side tool
→ Logic remains server-side.
```

Any proprietary or sensitive processing logic that must remain server-side should use:

* PHP server-side execution
* Python API execution
* Another authorized server-side service

The architecture must not make an unrealistic claim that browser-delivered JavaScript can be made impossible to inspect.

---

# 36. Repository Inspection Requirement

Before implementation, the AI agent MUST inspect the actual Favorite CMS repository.

At minimum it must inspect:

```text
plugins/
app/
config/
database/migrations/
resources/views/
public/
tests/
bootstrap.php
composer.json
```

The agent must identify the actual conventions for:

* Plugin registration
* Plugin loading
* Routes
* Controllers
* Views
* Admin menus
* Permissions
* Database migrations
* Assets
* Authentication
* User/session access
* Membership integration
* APIs/services
* Testing

The agent must use those conventions instead of guessing.

---

# 37. Architecture Conflict Rule

If this specification conflicts with the actual Favorite CMS implementation:

1. Do not modify CMS core to force compatibility.
2. Do not silently invent a new architecture.
3. Identify the conflict.
4. Determine whether an existing CMS extension point solves it.
5. Report the finding.
6. Update the project specification if necessary.
7. Continue implementation only after the architecture is clear.

---

# 38. Implementation Principle

Implementation must be incremental.

The AI agent must not generate the entire plugin blindly in one step.

The recommended sequence is:

```text
Repository Inspection
        ↓
Plugin Skeleton
        ↓
Plugin Registration
        ↓
Tool Registry
        ↓
Tool Model / Storage
        ↓
Access Control
        ↓
First Simple Tools
        ↓
Admin Management
        ↓
Python API Engine
        ↓
Membership Integration
        ↓
Testing
```

The exact order may be adjusted after repository inspection.

---

# 39. Architecture Acceptance Criteria

The architecture is considered ready for implementation when:

* Favorite Web Tools is isolated inside the plugin system.
* Favorite CMS core remains untouched.
* Existing plugin conventions are followed.
* Tools are independently registered.
* Tools can select an execution engine.
* Access control is centralized.
* Free tools work for anonymous users.
* Login-required tools require authentication.
* Membership-required tools require active membership.
* Active members have unlimited access.
* Categories are independent from access modes.
* Tools can be enabled/disabled.
* PHP tools can use server-side execution.
* Python tools can communicate with configured Python services.
* Admin functionality integrates with the existing CMS admin system.
* Database changes follow existing migration conventions.
* Routes use the existing CMS routing mechanism.
* Assets use the existing CMS/plugin asset mechanism.
* Future tools can be added without rewriting the platform.
* Future engines can be added without rewriting existing engines.

---

# 40. Final Rule for the AI Agent

This document defines the intended architecture.

However, the actual Favorite CMS repository is authoritative for implementation conventions.

Therefore:

> Inspect first. Reuse existing CMS extension points. Implement inside the plugin boundary. Do not guess. Do not modify core unnecessarily. Do not silently change architecture.

If a required CMS integration point cannot be identified from the repository, stop that implementation step and report what was found before creating a workaround.
