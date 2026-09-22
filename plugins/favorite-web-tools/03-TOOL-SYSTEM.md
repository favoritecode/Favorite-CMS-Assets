# Favorite Web Tools — Tool System Specification



**Plugin:** Favorite Web Tools

**Slug:** `favorite-web-tools`



---



# 1. Purpose



The Tool System is the central part of Favorite Web Tools.



It defines how tools are:



* Registered

* Identified

* Categorized

* Configured

* Loaded

* Executed

* Enabled

* Disabled

* Displayed

* Access-controlled



The system must be modular so that new tools can be added without rewriting the core Tool System.



---



# 2. Tool Definition



Every tool must have a unique identity and a defined execution configuration.



Conceptual structure:



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



---



# 3. Required Tool Fields



Every tool must support the following logical fields.



## 3.1 Name



Human-readable tool name.



Example:



```text

HTML Formatter

```



---



## 3.2 Slug



Unique machine-readable identifier.



Example:



```text

html-formatter

```



The slug must be suitable for use in URLs and internal tool identification.



Slugs must be unique.



---



## 3.3 Description



Short user-facing description explaining what the tool does.



Example:



```text

Format and beautify HTML code online.

```



---



## 3.4 Category



Each tool belongs to a category.



Examples:



```text

HTML

CSS

JavaScript

Developer Tools

Text Tools

PHP

Python

```



Categories are organizational metadata only.



Category must not automatically determine access level.



---



## 3.5 Engine



Each tool must specify which engine is responsible for its execution.



Initial supported engines:



```text

HTML

CSS

JavaScript

PHP

Python API

```



---



## 3.6 Access Mode



Each tool must define one access mode.



Allowed values:



```text

FREE

LOGIN\_REQUIRED

MEMBERSHIP\_REQUIRED

```



Access control is defined separately in the Access Control specification.



---



## 3.7 Status



Each tool must have a status.



Minimum supported statuses:



```text

ACTIVE

DRAFT

DISABLED

```



Only active tools can be executed publicly.



---



# 4. Tool ID



The system should use an internal unique ID where required by the existing Favorite CMS database conventions.



The tool ID must not replace the slug as the public identifier when a human-readable URL is required.



Example:



```text

Internal ID:

42



Public Slug:

html-formatter

```



The exact ID type must follow the existing CMS database conventions.



---



# 5. Tool Registry



The Tool Registry is responsible for registering and discovering tools.



Conceptual flow:



```text

Tool Registration

       ↓

Tool Registry

       ↓

Available Tools

       ↓

Find by ID / Slug

       ↓

Tool Definition

```



The registry must provide a consistent interface for retrieving tools.



---



# 6. Tool Lookup



The system should support lookup by:



* Internal ID

* Slug



Example:



```text

getToolById(42)



getToolBySlug("html-formatter")

```



The exact implementation/API name must follow project coding conventions.



---



# 7. Tool Uniqueness



The following must be unique where applicable:



* Tool ID

* Tool slug



Two active tools must never use the same slug.



The system should validate uniqueness when creating or editing a tool.



---



# 8. Tool Registration Models



The architecture should support tools implemented in different ways.



Conceptually:



```text

Database-configured Tool

        ↓

Tool Registry

        ↓

Engine

```



and/or, where the CMS/plugin architecture supports it:



```text

Plugin-defined Tool

        ↓

Tool Registry

        ↓

Engine

```



The AI agent must inspect the Favorite CMS repository before choosing the exact registration mechanism.



Do not create duplicate registration systems unnecessarily.



---



# 9. Tool Engine



The engine determines how the tool is processed.



Initial engines:



```text

HTML

CSS

JavaScript

PHP

Python API

```



The Tool System must not hardcode the internal implementation of every engine into the registry.



Conceptually:



```text

Tool

 ↓

Engine Identifier

 ↓

Engine Resolver

 ↓

Engine Implementation

```



---



# 10. HTML Engine



The HTML Engine is intended for HTML-related tools.



Examples:



```text

HTML Formatter

HTML Minifier

HTML Validator

HTML Entity Encoder

HTML Entity Decoder

```



Depending on the individual tool, processing may happen client-side or server-side.



The tool definition must be able to specify the appropriate processing behavior if required.



---



# 11. CSS Engine



The CSS Engine is intended for CSS-related tools.



Examples:



```text

CSS Formatter

CSS Minifier

CSS Prefixer

CSS Color Converter

```



Processing may be client-side or server-side depending on the tool.



---



# 12. JavaScript Engine



The JavaScript Engine is intended for JavaScript and related developer tools.



Examples:



```text

JavaScript Formatter

JavaScript Minifier

JSON Formatter

JSON Validator

JSON Beautifier

```



A tool may use JavaScript in the browser where appropriate.



---



# 13. PHP Engine



The PHP Engine is for server-side PHP processing.



Conceptual flow:



```text

User Input

    ↓

Tool Request

    ↓

Access Check

    ↓

Input Validation

    ↓

PHP Engine

    ↓

Tool Processor

    ↓

Result

```



PHP tool execution must remain within the server/plugin boundary.



---



# 14. Python API Engine



The Python API Engine is for tools whose processing is performed by an external Python service.



Conceptual tool:



```text

Tool

├── Engine: Python API

├── Python Service

└── Endpoint

```



Execution:



```text

User

 ↓

Favorite Web Tools

 ↓

Access Check

 ↓

Python API Engine

 ↓

Configured Python Service

 ↓

Endpoint

 ↓

Result

```



The Python service must not be exposed directly to the public frontend when a server-side proxy/API layer can be used.



---



# 15. Tool Configuration



Some tools require configuration beyond the standard fields.



The architecture must support tool-specific configuration.



Example:



```text

Tool:

Image Processor



Engine:

Python API



Configuration:

{

    "service": "favorite-python",

    "endpoint": "/image/process"

}

```



The exact configuration format must follow the implementation architecture chosen after repository inspection.



---



# 16. Engine-Specific Configuration



Some configuration belongs to the engine rather than the individual tool.



For example:



```text

Python Service

├── Base URL

├── Authentication

└── Timeout

```



A tool may then reference the configured service:



```text

Tool

├── Engine: Python API

└── Service: favorite-python

```



Private service credentials must never be stored in frontend-accessible tool configuration.



---



# 17. Tool Input



Tools may require different input types.



The architecture should support common input types such as:



```text

Text

Textarea

Number

URL

File

Select

Checkbox

Radio

JSON

```



A tool may use one or multiple input fields.



Example:



```text

HTML Formatter



Input:

Textarea



Output:

Formatted HTML

```



Another example:



```text

Image Tool



Input:

File

```



---



# 18. Tool Output



Tools may return different types of output.



Examples:



```text

Text

HTML

JSON

File

Image

Audio

Video

Downloadable Result

```



The Tool System must support tool-specific output handling.



The exact response contract must be standardized during implementation so frontend components can handle different output types consistently.



---



# 19. Tool UI



A tool should be able to define its own input/output interface while using the common Favorite Web Tools platform structure.



Conceptual structure:



```text

Tool Page

├── Title

├── Description

├── Input

├── Actions

├── Processing State

├── Result

└── Error

```



The platform should not force every tool into an identical UI if the tool requires a specialized interface.



---



# 20. Tool Page Routing



Tools should be accessible through a consistent public URL structure.



Conceptual example:



```text

/tools/html-formatter

/tools/json-formatter

/tools/base64-encoder

```



The exact route must follow Favorite CMS routing conventions.



The public route should resolve the tool through its slug.



---



# 21. Tool Discovery



The system should provide a way to retrieve:



### All active tools



```text

getActiveTools()

```



### Tools by category



```text

getToolsByCategory(category)

```



### Tool by slug



```text

getToolBySlug(slug)

```



These are conceptual interfaces.



The actual method names must follow the project's coding conventions.



---



# 22. Tool Visibility



A tool may be:



```text

ACTIVE

DRAFT

DISABLED

```



Frontend listings should normally show only active tools.



Draft and disabled tools should not be publicly executable.



Admin users may still see and manage them according to their permissions.



---



# 23. Tool Access Is Independent



The following combinations must be valid:



```text

HTML Formatter

Category: HTML

Access: FREE

```



```text

Advanced HTML Tool

Category: HTML

Access: LOGIN\_REQUIRED

```



```text

Premium Processing Tool

Category: Developer Tools

Access: MEMBERSHIP\_REQUIRED

```



Therefore:



> Category and access mode must remain independent properties.



---



# 24. No Usage Limits



The Tool System must not implement a usage quota system.



Do not create fields or logic for:



```text

Daily Limit

Monthly Limit

Credits

Tokens

Remaining Uses

Usage Balance

```



Membership-required tools are unlimited while membership is active.



---



# 25. Tool Lifecycle



The expected lifecycle is:



```text

Create Tool

     ↓

Configure Tool

     ↓

Draft

     ↓

Test

     ↓

Active

     ↓

Public Use

     ↓

Disable / Update

```



The system should allow administrators to control the lifecycle without deleting the tool unnecessarily.



---



# 26. Deleting Tools



Deleting a tool should be treated carefully.



If a tool has historical configuration or references, permanent deletion should not break unrelated plugin data.



Where appropriate, disabling a tool should be preferred over destructive deletion.



The exact deletion behavior must follow the database architecture and dependency requirements.



---



# 27. Tool Independence



Individual tools should not directly depend on unrelated tools.



For example:



```text

HTML Formatter

```



must not require:



```text

CSS Formatter

```



to function.



Shared functionality should live in common services or engines.



---



# 28. Shared Services



Common functionality may be provided through shared platform services.



Examples:



```text

Input Validator

Response Formatter

Access Checker

Tool Registry

Engine Resolver

API Client

Python Client

Error Handler

```



Individual tools should reuse these services instead of duplicating them.



---



# 29. Tool Extension



Adding a new tool should ideally involve:



```text

1\. Create/register tool

2\. Select category

3\. Select engine

4\. Select access mode

5\. Configure tool

6\. Implement tool-specific processing/UI

7\. Test

8\. Activate

```



The existing Tool Registry and Engine system should not require modification for every normal new tool.



---



# 30. New Engine Extension



Future engines may be added.



For example:



```text

HTML

CSS

JavaScript

PHP

Python API

```



could later expand to:



```text

REST API

Node API

CLI

External Service

```



A new engine must integrate through the same engine-resolution architecture.



Existing engines and tools must continue working.



---



# 31. Admin Tool Management



The admin system should eventually provide:



```text

Tools

├── All Tools

├── Add Tool

├── Edit Tool

├── Activate

├── Disable

└── Delete

```



Tool editing should include at least:



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



The actual admin implementation must follow Favorite CMS conventions.



---



# 32. Tool Validation



Before a tool becomes active, the system should validate required configuration.



Examples:



```text

Tool Name exists

Slug exists

Slug is unique

Category exists

Engine is supported

Access Mode is valid

Required engine configuration exists

```



A Python API tool should not become active if its required Python service configuration is invalid.



---



# 33. Tool Execution Contract



Every engine should return a predictable result to the Tool System.



Conceptually:



```text

Success

├── success: true

└── data: ...



Failure

├── success: false

└── error: ...

```



The exact response structure must be finalized during implementation based on the Favorite CMS/API conventions.



The frontend should not need to understand the internal implementation of each engine.



---



# 34. Tool Registry and Database



If tools are stored in the database, the database must contain only the metadata required by the Tool System.



Tool-specific processing code should not be stored as arbitrary executable code in database fields.



Executable implementations should remain in controlled plugin/server-side code or configured external services.



---



# 35. No Arbitrary User Code Execution



The Tool System must NOT provide a general-purpose feature where an administrator or user can submit arbitrary PHP/Python code for execution.



A tool must refer to an explicitly implemented and controlled processor/endpoint.



For example:



```text

Allowed:



Tool

 ↓

Registered PHP Processor

```



Not:



```text

User Input

 ↓

eval()

 ↓

Arbitrary PHP

```



Likewise, Python tools must call explicitly configured API endpoints rather than accepting arbitrary Python source for execution.



---



# 36. Tool Security Boundary



Tool System security must be separated from tool-specific business logic.



The platform should handle common concerns such as:



* Authentication

* Authorization

* Input validation

* Request validation

* CSRF protection where applicable

* Error handling



Individual tools should focus on their actual processing logic.



---



# 37. Repository Compatibility



Before implementing the Tool Registry, the AI agent must inspect the existing Favorite CMS repository and determine:



* Existing plugin registration pattern

* Existing plugin service pattern

* Existing database model conventions

* Existing migration conventions

* Existing route registration

* Existing controller conventions

* Existing view conventions

* Existing admin conventions

* Existing API response conventions

* Existing authentication APIs

* Existing membership integration points



The implementation must follow those conventions.



---



# 38. Do Not Guess Existing APIs



If the Favorite CMS repository already provides a function, class, service, hook, route registration mechanism, or helper for a required operation, use it.



Do not invent a duplicate implementation without first verifying that the existing functionality cannot be reused.



---



# 39. Acceptance Criteria



The Tool System is acceptable when:



* Every tool has a unique identity.

* Every tool has a unique slug.

* Tools can be categorized.

* Tools can select an execution engine.

* Tools can select an access mode.

* Tools can be activated or disabled.

* Tools can be discovered by slug.

* Tools can be discovered by category.

* Tool configuration can be stored and retrieved.

* Different engines can coexist.

* PHP tools can use server-side processors.

* Python tools can use configured Python APIs.

* Client-side tools can use browser processing where appropriate.

* Tool access is independent of category.

* No usage-limit/credit system exists.

* Membership-required tools remain unlimited while membership is active.

* New tools can be added without rewriting the Tool System.

* Future engines can be added without rewriting existing engines.

* Arbitrary PHP/Python code execution is not provided.

* Existing Favorite CMS core remains untouched.



---



# 40. Final Implementation Rule



The AI agent must treat this document as the specification for the Tool System.



However, the actual Favorite CMS repository determines the implementation conventions.



Therefore:



> Inspect the repository first. Reuse existing CMS/plugin mechanisms. Implement the Tool System inside the Favorite Web Tools plugin boundary. Do not invent or duplicate CMS infrastructure without justification.



If an implementation detail is not defined here, the agent must use the existing Favorite CMS convention where one exists.



If no appropriate convention exists, the agent must document the proposed approach before introducing a new architectural pattern.



